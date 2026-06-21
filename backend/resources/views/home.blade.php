@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-end mb-12">
        <div>
            <h1 class="text-4xl md:text-5xl mb-2 tracking-tight">Intelligence Dashboard</h1>
            <p class="text-white/50 text-lg">Upload, track, and process medical documents instantly using our AI pipeline.</p>
        </div>
        <a href="{{ route('documents.export') }}" class="mt-6 md:mt-0 flex items-center gap-2 px-6 py-3 rounded-full border border-white/10 hover:bg-[#4a5d23]/20 hover:border-[#4a5d23]/50 transition-all font-medium text-sm shadow-[0_0_15px_rgba(74,93,35,0.1)]">
            <svg class="w-4 h-4 text-[#4a5d23]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Export Claims to CSV
        </a>
    </div>

    <!-- Alerts -->
    @if (session('success'))
        <div class="mb-8 p-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-emerald-200 text-sm">{{ session('success') }}</span>
            </div>
        </div>
    @endif
    
    @if ($errors->any())
        <div class="mb-8 p-4 rounded-xl border border-red-500/30 bg-red-500/10">
            <ul class="list-disc list-inside text-red-200 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Upload Section -->
        <div class="col-span-1">
            <div class="glow-card interactive-glow p-8 h-full flex flex-col">
                <h4 class="text-xl mb-6">Upload Document</h4>
                
                <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-grow">
                    @csrf
                    
                    <button type="submit" class="w-full py-4 mb-6 rounded-full bg-white text-[#0a0a0b] font-bold hover:bg-gray-200 transition-colors shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                        Process Document
                    </button>
                    
                    <div class="flex-grow border-2 border-dashed border-white/10 rounded-2xl p-8 flex flex-col items-center justify-center text-center hover:border-[#4a5d23]/50 hover:bg-[#4a5d23]/5 transition-all group cursor-pointer relative">
                        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-[#4a5d23]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        </div>
                        <h5 class="text-white/80 font-medium mb-1">Drag & drop your file</h5>
                        <p class="text-white/40 text-xs">PDF, JPG, PNG up to 10MB</p>
                        
                        <input type="file" name="document" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Documents Section -->
        <div class="col-span-1 lg:col-span-2">
            <div class="glow-card interactive-glow p-8 h-full flex flex-col">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-xl">Recent Processing Jobs</h4>
                    <button class="w-8 h-8 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/5 transition-colors" onclick="window.location.reload();">
                        <svg class="w-4 h-4 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                </div>
                
                <div class="flex-grow overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="py-4 text-xs font-medium text-white/40 uppercase tracking-wider">Filename</th>
                                <th class="py-4 text-xs font-medium text-white/40 uppercase tracking-wider">Status</th>
                                <th class="py-4 text-xs font-medium text-white/40 uppercase tracking-wider">Date</th>
                                <th class="py-4 text-xs font-medium text-white/40 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($documents as $doc)
                            <tr class="group hover:bg-white/[0.02] transition-colors">
                                <td class="py-4 pr-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-[#c86b51]/10 border border-[#c86b51]/20 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-[#c86b51]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-white/90">{{ Str::limit($doc->original_filename, 30) }}</span>
                                            <span class="text-xs text-white/40 uppercase tracking-wider">{{ pathinfo($doc->original_filename, PATHINFO_EXTENSION) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4">
                                    @if($doc->status == 'uploaded')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/5 text-white/60 border border-white/10">Uploaded</span>
                                    @elseif($doc->status == 'processing')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            <span class="w-3 h-3 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span> Processing
                                        </span>
                                    @elseif($doc->status == 'failed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">Failed</span>
                                    @elseif($doc->status == 'cancelled')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-500/10 text-gray-400 border border-gray-500/20">Cancelled</span>
                                    @else
                                        @if($doc->claim && $doc->claim->status == 'approved')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Approved
                                            </span>
                                        @elseif($doc->claim && $doc->claim->status == 'rejected')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg> Rejected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-sky-500/10 text-sky-400 border border-sky-500/20">Needs Review</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-4 text-xs text-white/50">{{ $doc->created_at->diffForHumans() }}</td>
                                <td class="py-4 text-right">
                                    @if($doc->status == 'processing')
                                        <form action="{{ route('documents.cancel', $doc->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="px-4 py-1.5 rounded-full text-xs font-medium border border-red-500/30 text-red-400 hover:bg-red-500/10 transition-colors">Cancel Job</button>
                                        </form>
                                    @elseif($doc->status == 'failed' || $doc->status == 'uploaded')
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('documents.retry', $doc->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="px-4 py-1.5 rounded-full text-xs font-medium border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 transition-colors">Retry AI</button>
                                            </form>
                                            <a href="{{ route('documents.show', $doc->id) }}" class="px-4 py-1.5 rounded-full text-xs font-medium bg-[#c86b51] text-white hover:bg-[#b05d45] transition-colors inline-block">Review Manually</a>
                                        </div>
                                    @elseif($doc->status == 'completed')
                                        @if($doc->claim && $doc->claim->status != 'pending')
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('documents.show', $doc->id) }}" class="px-4 py-1.5 rounded-full text-xs font-medium border border-white/20 text-white/80 hover:bg-white/10 transition-colors inline-block">View Details</a>
                                                <a href="{{ route('documents.export.single', $doc->id) }}" class="px-4 py-1.5 rounded-full text-xs font-medium bg-[#4a5d23]/20 border border-[#4a5d23]/50 text-[#4a5d23] hover:bg-[#4a5d23]/30 transition-colors inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg> Export</a>
                                            </div>
                                        @else
                                            <a href="{{ route('documents.show', $doc->id) }}" class="px-4 py-1.5 rounded-full text-xs font-medium bg-[#c86b51] text-white hover:bg-[#b05d45] transition-colors shadow-[0_0_15px_rgba(200,107,81,0.2)] inline-block">Review Claim</a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/5 border border-white/10 mb-4">
                                        <svg class="w-8 h-8 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                    </div>
                                    <h5 class="text-white/80 font-medium mb-1">No documents processed yet.</h5>
                                    <p class="text-white/40 text-sm">Upload your first document to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $processingDocs = $documents->where('status', 'processing');
@endphp

@if($processingDocs->count() > 0)
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const processingIds = {!! json_encode($processingDocs->pluck('id')) !!};
        const maxPolls = 100;
        let pollsCount = 0;
        
        let pollInterval = setInterval(() => {
            pollsCount++;
            let allCompleted = true;
            let promises = processingIds.map(id => 
                fetch(`/documents/${id}/status`)
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'processing') {
                            allCompleted = false;
                            if (pollsCount >= maxPolls) {
                                fetch(`/documents/${id}/cancel`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                            }
                        }
                    })
                    .catch(err => console.error(err))
            );

            Promise.all(promises).then(() => {
                if(allCompleted || pollsCount >= maxPolls) {
                    clearInterval(pollInterval);
                    window.location.reload();
                }
            });
        }, 3000);
    });
</script>
@endif
@endsection
