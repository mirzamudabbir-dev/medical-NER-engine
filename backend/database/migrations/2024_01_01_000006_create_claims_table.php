<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('patient_name')->nullable();
            $table->string('dob')->nullable();
            $table->string('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('facility')->nullable();
            $table->text('facility_address')->nullable();
            $table->string('doctor')->nullable();
            $table->string('admission_date')->nullable();
            $table->string('discharge_date')->nullable();
            $table->string('duration_of_stay')->nullable();
            $table->string('dos')->nullable();
            $table->text('disease')->nullable();
            $table->text('secondary_diagnosis')->nullable();
            $table->string('icd_code')->nullable();
            $table->text('nature_of_treatment')->nullable();
            $table->text('chief_complaints')->nullable();
            $table->text('procedure')->nullable();
            $table->text('cpt_codes')->nullable();
            $table->string('room_rent_category')->nullable();
            $table->text('itemised_bill_totals')->nullable();
            $table->json('prescriptions')->nullable();
            $table->json('lab_test_results')->nullable();
            $table->string('claim_amount')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
