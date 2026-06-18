<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Claim extends Model
{
    protected $fillable = [
        'document_id',
        'patient_name',
        'dob',
        'age',
        'gender',
        'facility',
        'facility_address',
        'doctor',
        'admission_date',
        'discharge_date',
        'duration_of_stay',
        'dos',
        'disease',
        'secondary_diagnosis',
        'icd_code',
        'nature_of_treatment',
        'chief_complaints',
        'procedure',
        'cpt_codes',
        'room_rent_category',
        'itemised_bill_totals',
        'prescriptions',
        'lab_test_results',
        'claim_amount',
        'follow_up_instructions',
        'confidence',
        'status',
        'reviewer_id'
    ];

    protected $casts = [
        'prescriptions' => 'array',
        'lab_test_results' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class);
    }
}
