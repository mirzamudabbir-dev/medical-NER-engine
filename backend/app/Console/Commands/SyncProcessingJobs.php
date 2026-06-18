<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProcessingJob;
use App\Models\Document;
use Illuminate\Support\Facades\Http;

class SyncProcessingJobs extends Command
{
    protected $signature = 'jobs:sync';
    protected $description = 'Fallback sync: polls FastAPI for any processing jobs the web request missed';

    public function handle()
    {
        $jobs = ProcessingJob::where('status', 'processing')->get();
        $this->info("Found {$jobs->count()} jobs to sync.");

        $apiKey = config('services.ai_service.key');
        $apiUrl = config('services.ai_service.url', 'http://127.0.0.1:8000');

        foreach ($jobs as $job) {
            try {
                $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                    ->timeout(10)
                    ->get("{$apiUrl}/result/{$job->fastapi_job_id}");

                if (!$response->successful() || !isset($response->json()['data'])) {
                    $this->info("Job {$job->fastapi_job_id} is still processing.");
                    continue;
                }

                $data = $response->json()['data'];

                if (in_array($data['status'] ?? '', ['failed', 'cancelled'])) {
                    $job->update(['status' => $data['status']]);
                    $document = Document::find($job->document_id);
                    $document?->update(['status' => $data['status']]);
                    continue;
                }

                $document = Document::find($job->document_id);
                if ($document && !$document->claim) {
                    $entities  = $data['entities'] ?? [];
                    $icdCodes  = $data['icd_codes'] ?? [];
                    $diseaseStr = implode(', ', $entities['diseases'] ?? []);
                    $icdStr     = implode(', ', array_values($icdCodes));

                    $document->claim()->create([
                        'patient_name'          => $entities['patient_name'] ?? null,
                        'dob'                   => $entities['dob'] ?? null,
                        'age'                   => $entities['age'] ?? null,
                        'gender'                => $entities['gender'] ?? null,
                        'facility'              => $entities['facility'] ?? null,
                        'facility_address'      => $entities['facility_address'] ?? null,
                        'doctor'                => $entities['doctor'] ?? null,
                        'admission_date'        => $entities['admission_date'] ?? null,
                        'discharge_date'        => $entities['discharge_date'] ?? null,
                        'duration_of_stay'      => $entities['duration_of_stay'] ?? null,
                        'dos'                   => $entities['dos'] ?? null,
                        'disease'               => $diseaseStr ?: null,
                        'secondary_diagnosis'   => $entities['secondary_diagnosis'] ?? null,
                        'icd_code'              => $icdStr ?: null,
                        'nature_of_treatment'   => $entities['nature_of_treatment'] ?? null,
                        'chief_complaints'      => $entities['chief_complaints'] ?? null,
                        'procedure'             => $entities['procedure'] ?? null,
                        'cpt_codes'             => $entities['cpt_codes'] ?? null,
                        'room_rent_category'    => $entities['room_rent_category'] ?? null,
                        'itemised_bill_totals'  => $entities['itemised_bill_totals'] ?? null,
                        'prescriptions'         => $entities['prescriptions'] ?? null,
                        'lab_test_results'      => [
                            'names'  => $entities['lab_test_names'] ?? null,
                            'values' => $entities['lab_test_values'] ?? null,
                        ],
                        'claim_amount'          => $entities['claim_amount'] ?? null,
                        'follow_up_instructions'=> $entities['follow_up_instructions'] ?? null,
                        'confidence'            => $data['confidence'] ?? null,
                        'status'                => 'pending',
                    ]);
                }

                $job->update(['status' => 'completed']);
                $document?->update(['status' => 'completed']);
                $this->info("Job {$job->fastapi_job_id} synced.");

            } catch (\Exception $e) {
                $this->error("Failed to sync job {$job->fastapi_job_id}: " . $e->getMessage());
            }
        }
    }
}
