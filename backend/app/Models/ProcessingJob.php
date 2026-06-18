<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ProcessingJob extends Model
{
    protected $fillable = [
        'document_id',
        'fastapi_job_id',
        'status',
        'result_payload'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
