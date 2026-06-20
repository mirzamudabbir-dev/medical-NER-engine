<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingJob extends Model
{
    protected $fillable = [
        'document_id',
        'fastapi_job_id',
        'status',
        'result_payload'
    ];

    protected $casts = [
        'result_payload' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
