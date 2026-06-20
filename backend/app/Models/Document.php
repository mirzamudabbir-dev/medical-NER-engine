<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'original_filename',
        'storage_path',
        'mime_type',
        'status',
        'organization_id',
        'uploaded_by'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function processingJob()
    {
        return $this->hasOne(ProcessingJob::class);
    }

    public function claim()
    {
        return $this->hasOne(Claim::class);
    }
}
