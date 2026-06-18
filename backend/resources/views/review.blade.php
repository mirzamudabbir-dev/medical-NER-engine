@extends('layouts.app')

@section('content')
<div class="max-w-[1600px] mx-auto px-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-end mb-8">
        <div>
            <h2 class="text-3xl font-bold mb-1">Claim Review</h2>
            <p class="text-white/50 text-sm">Review the AI extracted data against the original document.</p>
        </div>
        <a href="{{ route('home') }}" class="mt-4 md:mt-0 flex items-center gap-2 px-5 py-2.5 rounded-full border border-white/10 hover:bg-white/5 transition-colors text-sm font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Back to Dashboard
        </a>
    </div>

    <!-- 50/50 Split Layout with Independent Scrolling -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 h-[calc(100vh-12rem)] min-h-[800px]">
        
        <!-- Left Side: Original Document Viewer -->
        <div class="glow-card interactive-glow h-full flex flex-col border-white/10">
            <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                <h5 class="font-medium text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#4a5d23]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Original Document
                </h5>
                <span class="px-3 py-1 rounded-full bg-white/5 text-white/50 text-xs border border-white/10">{{ $document->original_filename }}</span>
            </div>
            <div class="flex-grow bg-[#0a0a0b]/80 relative overflow-hidden">
                @php
                    $ext = strtolower(pathinfo($document->original_filename, PATHINFO_EXTENSION));
                @endphp
                @if(in_array($ext, ['jpg', 'jpeg', 'png']))
                    <img src="{{ route('documents.file', $document->id) }}" class="w-full h-full object-contain" alt="Medical Document">
                @elseif($ext == 'pdf')
                    <iframe src="{{ route('documents.file', $document->id) }}#toolbar=0" class="w-full h-full border-0 absolute inset-0"></iframe>
                @else
                    <div class="flex items-center justify-center h-full text-white/40">Preview not available</div>
                @endif
            </div>
        </div>

        <!-- Right Side: Data Form (Scrollable) -->
        <div class="glow-card h-full flex flex-col border-white/10 relative">
            <div class="absolute inset-x-0 top-0 h-10 bg-gradient-to-b from-[#121214] to-transparent z-10 pointer-events-none"></div>
            <div class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-[#121214] to-transparent z-10 pointer-events-none"></div>
            
            <div class="overflow-y-auto flex-grow p-8 pt-10 pb-10" style="scrollbar-width: thin; scrollbar-color: #2a2a2d #121214;">
                <form action="{{ route('documents.update', $document->id) }}" method="POST" id="claimForm">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="mb-6 p-4 rounded-xl border border-red-500/30 bg-red-500/10">
                            <ul class="list-disc list-inside text-red-200 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $c = $document->claim ?? new \App\Models\Claim();
                    @endphp

                    <!-- 1. Patient Identifiers -->
                    <div class="mb-10">
                        <h4 class="text-xl text-[#4a5d23] mb-4 pb-2 border-b border-white/5">1. Patient Identifiers</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Patient Name</label>
                                <input type="text" name="patient_name" value="{{ old('patient_name', $c->patient_name) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] focus:ring-1 focus:ring-[#4a5d23] transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">DOB</label>
                                <input type="text" name="dob" value="{{ old('dob', $c->dob) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] focus:ring-1 focus:ring-[#4a5d23] transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Gender</label>
                                <input type="text" name="gender" value="{{ old('gender', $c->gender) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] focus:ring-1 focus:ring-[#4a5d23] transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Age</label>
                                <input type="text" name="age" value="{{ old('age', $c->age) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] focus:ring-1 focus:ring-[#4a5d23] transition-all text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Provider & Facility -->
                    <div class="mb-10">
                        <h4 class="text-xl text-[#c86b51] mb-4 pb-2 border-b border-white/5">2. Provider & Facility</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Hospital/Clinic Name</label>
                                <input type="text" name="hospital_name" value="{{ old('hospital_name', $c->facility) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Treating Doctor</label>
                                <input type="text" name="doctor_name" value="{{ old('doctor_name', $c->doctor) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Facility Address</label>
                                <textarea name="facility_address" rows="2" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">{{ old('facility_address', $c->facility_address) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Timeline of Care -->
                    <div class="mb-10">
                        <h4 class="text-xl text-[#4a5d23] mb-4 pb-2 border-b border-white/5">3. Timeline of Care</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Admission Date</label>
                                <input type="text" name="admission_date" value="{{ old('admission_date', $c->admission_date) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Discharge Date</label>
                                <input type="text" name="discharge_date" value="{{ old('discharge_date', $c->discharge_date) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Stay Duration</label>
                                <input type="text" name="stay_duration" value="{{ old('stay_duration', $c->duration_of_stay) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] transition-all text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- 4. Clinical Details -->
                    <div class="mb-10">
                        <h4 class="text-xl text-white mb-4 pb-2 border-b border-white/5">4. Clinical Details</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Primary Diagnosis</label>
                                <input type="text" name="primary_diagnosis" value="{{ old('primary_diagnosis', $c->disease) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-white/30 transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Secondary Diagnosis</label>
                                <input type="text" name="secondary_diagnosis" value="{{ old('secondary_diagnosis', $c->secondary_diagnosis) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-white/30 transition-all text-sm">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">ICD-10 Code</label>
                                    <input type="text" name="icd10_code" value="{{ old('icd10_code', $c->icd_code) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-white/30 transition-all text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Nature of Treatment</label>
                                    <input type="text" name="nature_of_treatment" value="{{ old('nature_of_treatment', $c->nature_of_treatment) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-white/30 transition-all text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Chief Complaints</label>
                                <textarea name="chief_complaints" rows="3" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-white/30 transition-all text-sm">{{ old('chief_complaints', $c->chief_complaints) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Financials & Billing -->
                    <div class="mb-10">
                        <h4 class="text-xl text-[#c86b51] mb-4 pb-2 border-b border-white/5">5. Financials & Billing</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Procedure / CPT Codes</label>
                                <input type="text" name="cpt_codes" value="{{ old('cpt_codes', $c->cpt_codes) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Room Rent Category</label>
                                    <input type="text" name="room_category" value="{{ old('room_category', $c->room_rent_category) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Total Bill Amount ($)</label>
                                    <input type="text" name="total_bill_amount" value="{{ old('total_bill_amount', $c->claim_amount) }}" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-emerald-400 font-bold focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Itemised Bill Totals</label>
                                <textarea name="itemised_totals" rows="3" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm font-mono">{{ old('itemised_totals', $c->itemised_bill_totals) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Post-Discharge Plan -->
                    <div class="mb-10">
                        <h4 class="text-xl text-[#4a5d23] mb-4 pb-2 border-b border-white/5">6. Post-Discharge Plan</h4>
                        <div>
                            <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Follow-up Instructions</label>
                            <textarea name="follow_up_instructions" rows="2" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] transition-all text-sm">{{ old('follow_up_instructions', $c->follow_up_instructions) }}</textarea>
                        </div>
                    </div>

                    <!-- 7. Medications & Diagnostics -->
                    <div class="mb-10">
                        <h4 class="text-xl text-[#c86b51] mb-4 pb-2 border-b border-white/5">7. Medications & Diagnostics</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <div id="medications-container">
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2 flex justify-between items-center">
                                    Prescription Medicines & Timings
                                    <button type="button" onclick="addMedicationRow()" class="text-[#c86b51] hover:text-white transition-colors text-xs flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add
                                    </button>
                                </label>
                                
                                @php
                                    $meds = old('prescription_medicines', is_array($c->prescriptions) ? $c->prescriptions : (is_string($c->prescriptions) && !empty($c->prescriptions) ? [['medication' => $c->prescriptions, 'timing' => '']] : []));
                                @endphp
                                
                                <div id="meds-list" class="space-y-2">
                                    @forelse($meds as $index => $med)
                                        <div class="flex gap-2 med-row">
                                            <input type="text" name="prescription_medicines[{{ $index }}][medication]" value="{{ $med['medication'] ?? '' }}" placeholder="Medication Name" class="w-2/3 bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                            <input type="text" name="prescription_medicines[{{ $index }}][timing]" value="{{ $med['timing'] ?? '' }}" placeholder="Timing (e.g., Twice a day)" class="w-1/3 bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                            <button type="button" onclick="this.closest('.med-row').remove()" class="px-3 text-white/30 hover:text-red-400 transition-colors">&times;</button>
                                        </div>
                                    @empty
                                        <div class="flex gap-2 med-row">
                                            <input type="text" name="prescription_medicines[0][medication]" placeholder="Medication Name" class="w-2/3 bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                            <input type="text" name="prescription_medicines[0][timing]" placeholder="Timing (e.g., Twice a day)" class="w-1/3 bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                            <button type="button" onclick="this.closest('.med-row').remove()" class="px-3 text-white/30 hover:text-red-400 transition-colors">&times;</button>
                                        </div>
                                    @endforelse
                                </div>
                                <small class="text-white/30 text-xs mt-2 block">Extracted from discharge summary or prescription slips. Timings and dosages are processed by the AI.</small>
                            </div>
                            
                            <script>
                                function addMedicationRow() {
                                    const container = document.getElementById('meds-list');
                                    const index = Date.now(); // unique index
                                    const row = document.createElement('div');
                                    row.className = 'flex gap-2 med-row';
                                    row.innerHTML = `
                                        <input type="text" name="prescription_medicines[${index}][medication]" placeholder="Medication Name" class="w-2/3 bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                        <input type="text" name="prescription_medicines[${index}][timing]" placeholder="Timing (e.g., Twice a day)" class="w-1/3 bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">
                                        <button type="button" onclick="this.closest('.med-row').remove()" class="px-3 text-white/30 hover:text-red-400 transition-colors">&times;</button>
                                    `;
                                    container.appendChild(row);
                                }
                            </script>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @php
                                    $labNames = is_array($c->lab_test_results) ? ($c->lab_test_results['names'] ?? '') : $c->lab_test_results;
                                    $labValues = is_array($c->lab_test_results) ? ($c->lab_test_results['values'] ?? '') : '';
                                @endphp
                                <div>
                                    <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Lab Test Names</label>
                                    <textarea name="lab_test_names" rows="3" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm">{{ old('lab_test_names', $labNames) }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Lab Test Values</label>
                                    <textarea name="lab_test_values" rows="3" class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm font-mono">{{ old('lab_test_values', $labValues) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Decision -->
                    <div class="p-6 rounded-2xl bg-[#1a1a1d] border border-white/5 mb-8">
                        <h4 class="text-lg font-bold text-white mb-4">Approval Decision</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Final Status</label>
                                
                                <div class="relative w-full" id="customStatusDropdown">
                                    <input type="hidden" name="status" id="statusInput" value="{{ old('status', $c->status ?? 'pending') }}">
                                    
                                    <button type="button" id="statusTrigger" class="w-full bg-[#121214] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] transition-all text-sm flex items-center justify-between">
                                        <span id="statusLabel" class="flex items-center gap-2">
                                            @if(old('status', $c->status) == 'approved')
                                                <div class="w-2 h-2 rounded-full bg-emerald-400"></div> Approve Claim
                                            @elseif(old('status', $c->status) == 'rejected')
                                                <div class="w-2 h-2 rounded-full bg-red-400"></div> Reject Claim
                                            @else
                                                <div class="w-2 h-2 rounded-full bg-sky-400"></div> Pending Review
                                            @endif
                                        </span>
                                        <svg class="w-4 h-4 text-white/50 transition-transform duration-200" id="statusIcon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>

                                    <div id="statusMenu" class="absolute z-50 w-full mt-2 bg-[#1a1a1d] border border-white/10 rounded-xl shadow-2xl opacity-0 invisible transition-all duration-200 transform origin-top -translate-y-2">
                                        <div class="p-2 space-y-1">
                                            <div class="status-option px-4 py-2.5 rounded-lg hover:bg-white/5 cursor-pointer text-sm text-white/80 transition-colors flex items-center gap-2" data-value="pending" data-label="Pending Review">
                                                <div class="w-2 h-2 rounded-full bg-sky-400"></div> Pending Review
                                            </div>
                                            <div class="status-option px-4 py-2.5 rounded-lg hover:bg-[#4a5d23]/20 cursor-pointer text-sm text-emerald-400 transition-colors flex items-center gap-2" data-value="approved" data-label="Approve Claim">
                                                <div class="w-2 h-2 rounded-full bg-emerald-400"></div> Approve Claim
                                            </div>
                                            <div class="status-option px-4 py-2.5 rounded-lg hover:bg-red-500/20 cursor-pointer text-sm text-red-400 transition-colors flex items-center gap-2" data-value="rejected" data-label="Reject Claim">
                                                <div class="w-2 h-2 rounded-full bg-red-400"></div> Reject Claim
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="rejectionReasonDiv" style="{{ old('status', $c->status) == 'rejected' ? '' : 'display:none;' }}">
                                <label class="block text-xs font-medium text-red-400/80 uppercase tracking-wider mb-2">Rejection Reason</label>
                                <input type="text" name="rejection_reason" value="{{ old('rejection_reason', $c->rejection_reason) }}" class="w-full bg-red-500/5 border border-red-500/20 rounded-xl px-4 py-3 text-red-300 focus:outline-none focus:border-red-500/50 transition-all text-sm" placeholder="Why is this rejected?">
                            </div>
                        </div>
                        <p class="text-xs text-white/40">Select the appropriate action after verifying the extracted values. If rejecting, please provide a reason.</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <button type="submit" class="px-8 py-3 rounded-full bg-white text-[#0a0a0b] font-bold hover:scale-105 transition-transform duration-300 shadow-[0_0_20px_rgba(255,255,255,0.1)] flex-grow">
                            Save & Process Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const trigger = document.getElementById('statusTrigger');
        const menu = document.getElementById('statusMenu');
        const icon = document.getElementById('statusIcon');
        const input = document.getElementById('statusInput');
        const label = document.getElementById('statusLabel');
        const options = document.querySelectorAll('.status-option');
        const rejectionDiv = document.getElementById('rejectionReasonDiv');
        
        // Toggle dropdown
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('opacity-0');
            menu.classList.toggle('invisible');
            menu.classList.toggle('-translate-y-2');
            icon.classList.toggle('rotate-180');
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('opacity-0', 'invisible', '-translate-y-2');
                icon.classList.remove('rotate-180');
            }
        });

        // Handle option selection
        options.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                
                // Update hidden input
                input.value = value;
                
                // Update label HTML (cloning the option's inner HTML to keep the colored dot)
                label.innerHTML = this.innerHTML;
                
                // Close menu
                menu.classList.add('opacity-0', 'invisible', '-translate-y-2');
                icon.classList.remove('rotate-180');
                
                // Show/hide rejection reason
                if(value === 'rejected') {
                    rejectionDiv.style.display = 'block';
                } else {
                    rejectionDiv.style.display = 'none';
                }
            });
        });
    });
</script>
@endsection
