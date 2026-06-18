<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Correction extends Model
{
    protected $fillable = [
        'document_id',
        'claim_id',
        'field_name',
        'original_value',
        'corrected_value',
        'reviewer_id'
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
