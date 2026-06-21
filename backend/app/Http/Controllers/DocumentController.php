<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\ProcessingJob;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // For the dashboard
        $documents = Document::with('processingJob', 'claim')->orderBy('created_at', 'desc')->get();
        return view('home', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('document');
        $path = $file->store('documents'); // default is local disk (storage/app/private in L11)

        $document = Document::create([
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'status' => 'uploaded',
            'uploaded_by' => auth()->id(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Uploaded Document',
            'entity_type' => 'Document',
            'entity_id' => $document->id,
            'details' => 'Uploaded ' . $file->getClientOriginalName()
        ]);

        // Call FastAPI Microservice
        try {
            $aiUrl = config('services.ai_service.url');
            $aiKey = config('services.ai_service.key');
            $response = Http::timeout(10)
                ->withHeaders(['X-Api-Key' => $aiKey])
                ->attach('file', file_get_contents(Storage::path($path)), $file->getClientOriginalName())
                ->post("{$aiUrl}/process-document");

            if ($response->successful()) {
                $data = $response->json();
                $document->processingJob()->create([
                    'fastapi_job_id' => $data['job_id'],
                    'status' => $data['status'],
                ]);
                $document->update(['status' => 'processing']);
            } else {
                $document->update(['status' => 'failed']);
            }
        } catch (\Exception $e) {
            $document->update(['status' => 'failed']);
        }

        return redirect()->back()->with('success', 'Document uploaded and processing started!');
    }

    public function show(Document $document)
    {
        $document->load('claim.corrections', 'processingJob');
        return view('review', compact('document'));
    }

    public function file(Document $document)
    {
        return response()->file(Storage::path($document->storage_path));
    }

    public function update(Request $request, Document $document)
    {
        $claimData = [
            'patient_name' => $request->patient_name,
            'dob' => $request->dob,
            'age' => $request->age,
            'gender' => $request->gender,
            'facility' => $request->hospital_name,
            'facility_address' => $request->facility_address,
            'doctor' => $request->doctor_name,
            'admission_date' => $request->admission_date,
            'discharge_date' => $request->discharge_date,
            'duration_of_stay' => $request->stay_duration,
            'disease' => $request->primary_diagnosis,
            'secondary_diagnosis' => $request->secondary_diagnosis,
            'icd_code' => $request->icd10_code,
            'nature_of_treatment' => $request->nature_of_treatment,
            'chief_complaints' => $request->chief_complaints,
            'cpt_codes' => $request->cpt_codes,
            'room_rent_category' => $request->room_category,
            'claim_amount' => $request->total_bill_amount,
            'itemised_bill_totals' => $request->itemised_totals,
            'follow_up_instructions' => $request->follow_up_instructions,
            'prescriptions' => $request->prescription_medicines,
            'lab_test_results' => ['names' => $request->lab_test_names, 'values' => $request->lab_test_values],
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
            'reviewer_id' => auth()->id(),
        ];

        if ($document->claim) {
            $document->claim->update($claimData);
            $claimId = $document->claim->id;
        } else {
            $claim = $document->claim()->create($claimData);
            $claimId = $claim->id;
            $document->update(['status' => 'completed']);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => ucfirst($request->status) . ' Claim',
            'entity_type' => 'Claim',
            'entity_id' => $claimId,
            'details' => ucfirst($request->status) . ' claim for ' . $request->patient_name . ' (' . $request->icd10_code . ')'
        ]);

        return redirect()->route('home')->with('success', 'Claim updated successfully!');
    }

    public function retry(Document $document)
    {
        if (!in_array($document->status, ['failed', 'uploaded'])) {
            return redirect()->back()->withErrors(['message' => 'Only failed or pending documents can be retried.']);
        }

        try {
            $aiUrl = config('services.ai_service.url');
            $aiKey = config('services.ai_service.key');
            $response = Http::timeout(10)
                ->withHeaders(['X-Api-Key' => $aiKey])
                ->attach('file', file_get_contents(Storage::path($document->storage_path)), $document->original_filename)
                ->post("{$aiUrl}/process-document");

            if ($response->successful()) {
                $data = $response->json();
                $document->processingJob()->updateOrCreate(
                    ['document_id' => $document->id],
                    ['fastapi_job_id' => $data['job_id'], 'status' => $data['status']]
                );
                $document->update(['status' => 'processing']);
                return redirect()->route('home')->with('success', 'Resubmitted for processing.');
            } else {
                $document->update(['status' => 'failed']);
                return redirect()->route('home')->withErrors(['message' => 'AI service returned an error. Check AI_SERVICE_URL and AI_SERVICE_KEY env vars.']);
            }
        } catch (\Exception $e) {
            $document->update(['status' => 'failed']);
            return redirect()->route('home')->withErrors(['message' => 'Could not reach AI service: ' . $e->getMessage()]);
        }
    }

    public function status(Document $document)
    {
        if ($document->status === 'processing' && $document->processingJob) {
            try {
                $aiUrl = config('services.ai_service.url');
                $aiKey = config('services.ai_service.key');
                $response = Http::timeout(5)
                    ->withHeaders(['X-Api-Key' => $aiKey])
                    ->get("{$aiUrl}/result/{$document->processingJob->fastapi_job_id}");

                if ($response->successful() && isset($response->json()['data'])) {
                    $data = $response->json()['data'];
                    
                    // If status is cancelled, update and return
                    if (isset($data['status']) && $data['status'] === 'cancelled') {
                        $document->processingJob->update(['status' => 'cancelled']);
                        $document->update(['status' => 'cancelled']);
                        return response()->json(['status' => 'cancelled']);
                    }
                    
                    if (isset($data['status']) && $data['status'] === 'failed') {
                        $document->processingJob->update(['status' => 'failed']);
                        $document->update(['status' => 'failed']);
                        return response()->json(['status' => 'failed']);
                    }

                    if (!$document->claim) {
                        $disease_str = isset($data['entities']['diseases']) ? implode(', ', $data['entities']['diseases']) : null;
                        $icd_str = isset($data['icd_codes']) ? implode(', ', array_values($data['icd_codes'])) : null;
                        
                        $document->claim()->create([
                            'patient_name' => $data['entities']['patient_name'] ?? null,
                            'dob' => $data['entities']['dob'] ?? null,
                            'age' => $data['entities']['age'] ?? null,
                            'gender' => $data['entities']['gender'] ?? null,
                            'facility' => $data['entities']['facility'] ?? null,
                            'facility_address' => $data['entities']['facility_address'] ?? null,
                            'doctor' => $data['entities']['doctor'] ?? null,
                            'admission_date' => $data['entities']['admission_date'] ?? null,
                            'discharge_date' => $data['entities']['discharge_date'] ?? null,
                            'duration_of_stay' => $data['entities']['duration_of_stay'] ?? null,
                            'dos' => $data['entities']['dos'] ?? null,
                            'disease' => $disease_str,
                            'secondary_diagnosis' => $data['entities']['secondary_diagnosis'] ?? null,
                            'icd_code' => $icd_str,
                            'nature_of_treatment' => $data['entities']['nature_of_treatment'] ?? null,
                            'chief_complaints' => $data['entities']['chief_complaints'] ?? null,
                            'procedure' => $data['entities']['procedure'] ?? null,
                            'cpt_codes' => $data['entities']['cpt_codes'] ?? null,
                            'room_rent_category' => $data['entities']['room_rent_category'] ?? null,
                            'itemised_bill_totals' => $data['entities']['itemised_bill_totals'] ?? null,
                            'prescriptions' => $data['entities']['prescriptions'] ?? null,
                            'lab_test_results' => ['names' => $data['entities']['lab_test_names'] ?? null, 'values' => $data['entities']['lab_test_values'] ?? null],
                            'claim_amount' => $data['entities']['claim_amount'] ?? null,
                            'follow_up_instructions' => $data['entities']['follow_up_instructions'] ?? null,
                            'confidence' => $data['confidence'] ?? null,
                            'status' => 'pending',
                        ]);
                    }

                    $document->processingJob->update(['status' => 'completed']);
                    $document->update(['status' => 'completed']);
                }
            } catch (\Exception $e) {
                // Silently ignore, just return current status
            }
        }

        return response()->json(['status' => $document->status]);
    }

    public function export()
    {
        $claims = \App\Models\Claim::whereIn('status', ['approved', 'rejected'])->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=medical_claims_export.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Patient Name', 'DOB', 'Age', 'Gender', 'Facility', 'Facility Address', 'Doctor', 'Admission Date', 'Discharge Date', 'Duration of Stay', 'Date of Service', 'Primary Diagnosis', 'Secondary Diagnosis', 'ICD Code', 'Nature of Treatment', 'Chief Complaints', 'Procedure', 'CPT Codes', 'Room Rent Category', 'Itemised Bill', 'Prescriptions', 'Lab Test Results', 'Claim Amount', 'Follow-up', 'Confidence Score', 'Status'];

        $callback = function() use($claims, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($claims as $claim) {
                $prescriptions_str = '';
                if (is_array($claim->prescriptions)) {
                    $meds = [];
                    foreach ($claim->prescriptions as $p) {
                        $name = $p['medication'] ?? '';
                        $timing = $p['timing'] ?? '';
                        $meds[] = $timing ? "$name ($timing)" : $name;
                    }
                    $prescriptions_str = implode(', ', $meds);
                } else {
                    $prescriptions_str = $claim->prescriptions;
                }

                $lab_results_str = '';
                if (is_array($claim->lab_test_results)) {
                    $names = explode("\n", $claim->lab_test_results['names'] ?? '');
                    $values = explode("\n", $claim->lab_test_results['values'] ?? '');
                    $lab_items = [];
                    foreach ($names as $idx => $name) {
                        $name = trim($name);
                        $val = isset($values[$idx]) ? trim($values[$idx]) : '';
                        if ($name) {
                            $lab_items[] = $val ? "$name: $val" : $name;
                        }
                    }
                    $lab_results_str = implode(', ', $lab_items);
                } else {
                    $lab_results_str = $claim->lab_test_results;
                }

                $row = [
                    $claim->id,
                    $claim->patient_name,
                    $claim->dob,
                    $claim->age,
                    $claim->gender,
                    $claim->facility,
                    $claim->facility_address,
                    $claim->doctor,
                    $claim->admission_date,
                    $claim->discharge_date,
                    $claim->duration_of_stay,
                    $claim->dos,
                    $claim->disease,
                    $claim->secondary_diagnosis,
                    $claim->icd_code,
                    $claim->nature_of_treatment,
                    $claim->chief_complaints,
                    $claim->procedure,
                    $claim->cpt_codes,
                    $claim->room_rent_category,
                    $claim->itemised_bill_totals,
                    $prescriptions_str,
                    $lab_results_str,
                    $claim->claim_amount,
                    $claim->follow_up_instructions,
                    $claim->confidence,
                    $claim->status
                ];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportSingle(Document $document)
    {
        $claim = $document->claim;
        if (!$claim || !in_array($claim->status, ['approved', 'rejected'])) {
            return redirect()->back()->withErrors(['message' => 'Cannot export this document. The claim is not finalized.']);
        }

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=claim_export_{$document->id}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Patient Name', 'DOB', 'Age', 'Gender', 'Facility', 'Facility Address', 'Doctor', 'Admission Date', 'Discharge Date', 'Duration of Stay', 'Date of Service', 'Primary Diagnosis', 'Secondary Diagnosis', 'ICD Code', 'Nature of Treatment', 'Chief Complaints', 'Procedure', 'CPT Codes', 'Room Rent Category', 'Itemised Bill', 'Prescriptions', 'Lab Test Results', 'Claim Amount', 'Follow-up', 'Confidence Score', 'Status'];

        $callback = function() use($claim, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $prescriptions_str = '';
            if (is_array($claim->prescriptions)) {
                $meds = [];
                foreach ($claim->prescriptions as $p) {
                    $name = $p['medication'] ?? '';
                    $timing = $p['timing'] ?? '';
                    $meds[] = $timing ? "$name ($timing)" : $name;
                }
                $prescriptions_str = implode(', ', $meds);
            } else {
                $prescriptions_str = $claim->prescriptions;
            }

            $lab_results_str = '';
            if (is_array($claim->lab_test_results)) {
                $names = explode("\n", $claim->lab_test_results['names'] ?? '');
                $values = explode("\n", $claim->lab_test_results['values'] ?? '');
                $lab_items = [];
                foreach ($names as $idx => $name) {
                    $name = trim($name);
                    $val = isset($values[$idx]) ? trim($values[$idx]) : '';
                    if ($name) {
                        $lab_items[] = $val ? "$name: $val" : $name;
                    }
                }
                $lab_results_str = implode(', ', $lab_items);
            } else {
                $lab_results_str = $claim->lab_test_results;
            }

            $row = [
                $claim->id,
                $claim->patient_name,
                $claim->dob,
                $claim->age,
                $claim->gender,
                $claim->facility,
                $claim->facility_address,
                $claim->doctor,
                $claim->admission_date,
                $claim->discharge_date,
                $claim->duration_of_stay,
                $claim->dos,
                $claim->disease,
                $claim->secondary_diagnosis,
                $claim->icd_code,
                $claim->nature_of_treatment,
                $claim->chief_complaints,
                $claim->procedure,
                $claim->cpt_codes,
                $claim->room_rent_category,
                $claim->itemised_bill_totals,
                $prescriptions_str,
                $lab_results_str,
                $claim->claim_amount,
                $claim->follow_up_instructions,
                $claim->confidence,
                $claim->status
            ];
            fputcsv($file, $row);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function cancel(Document $document)
    {
        if ($document->status === 'processing' && $document->processingJob) {
            try {
                $aiUrl = config('services.ai_service.url');
                $aiKey = config('services.ai_service.key');
                $response = Http::timeout(5)
                    ->withHeaders(['X-Api-Key' => $aiKey])
                    ->post("{$aiUrl}/cancel/{$document->processingJob->fastapi_job_id}");
                
                if ($response->successful()) {
                    $document->processingJob->update(['status' => 'cancelled']);
                    $document->update(['status' => 'cancelled']);
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }
        
        // If called via ajax/fetch
        if (request()->expectsJson()) {
            return response()->json(['status' => 'cancelled']);
        }
        
        return redirect()->back()->with('success', 'Processing cancelled.');
    }
}
