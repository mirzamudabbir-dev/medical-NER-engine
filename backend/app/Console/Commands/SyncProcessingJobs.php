<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProcessingJob;
use App\Models\Document;
use App\Models\Claim;
use Illuminate\Support\Facades\Http;

class SyncProcessingJobs extends Command
{
    protected $signature = 'jobs:sync';
    protected $description = 'Sync processing jobs from FastAPI backend';

    public function handle()
    {
        $jobs = ProcessingJob::where('status', 'processing')->get();
        $this->info("Found {$jobs->count()} jobs to sync.");

        foreach ($jobs as $job) {
            try {
                $response = Http::get('http://127.0.0.1:8001/result/' . $job->fastapi_job_id);

                if ($response->successful() && isset($response->json()['data'])) {
                    $data = $response->json()['data'];

                    // Create Claim using relation to enforce ObjectId on document_id
                    $document = Document::find($job->document_id);
                    if ($document && !$document->claim) {
                        $document->claim()->create([
                            'patient_name' => $data['patient_name'] ?? null,
                            'age' => $data['age'] ?? null,
                            'disease' => $data['disease'] ?? null,
                            'procedure' => $data['procedure'] ?? null,
                            'doctor' => $data['doctor'] ?? null,
                            'claim_amount' => $data['claim_amount'] ?? null,
                            'icd_code' => $data['icd_code'] ?? null,
                            'status' => 'pending', // pending review
                        ]);
                    }

                    // Update Job and Document
                    $job->update(['status' => 'completed']);
                    if ($document) {
                        $document->update(['status' => 'completed']);
                    }

                    $this->info("Job {$job->fastapi_job_id} completed successfully.");
                } else {
                    $this->info("Job {$job->fastapi_job_id} is still processing.");
                }
            } catch (\Exception $e) {
                $this->error("Failed to sync job {$job->fastapi_job_id}: " . $e->getMessage());
            }
        }
    }
}
