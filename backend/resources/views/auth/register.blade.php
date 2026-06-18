@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-6 my-12">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-head font-bold mb-2">Create an Account</h1>
            <p class="text-white/50 text-sm">Join ClinIQ AI to automate your medical coding.</p>
        </div>

        <!-- Glassmorphic Card -->
        <div class="p-8 rounded-3xl bg-[#121214] border border-white/5 shadow-2xl relative overflow-hidden group">
            <!-- Subtle Glow -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#c86b51]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

            <form method="POST" action="{{ route('register') }}" class="relative z-10">
                @csrf

                <!-- Name Input -->
                <div class="mb-6">
                    <label for="name" class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Full Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus
                        class="w-full bg-[#1a1a1d] border @error('name') border-red-500 @else border-white/10 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm placeholder-white/20" placeholder="Dr. Jane Doe">
                    
                    @error('name')
                        <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email Input -->
                <div class="mb-6">
                    <label for="email" class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                        class="w-full bg-[#1a1a1d] border @error('email') border-red-500 @else border-white/10 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm placeholder-white/20" placeholder="jane@clinic.com">
                    
                    @error('email')
                        <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="mb-6">
                    <label for="password" class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full bg-[#1a1a1d] border @error('password') border-red-500 @else border-white/10 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm placeholder-white/20" placeholder="••••••••">

                    @error('password')
                        <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password Input -->
                <div class="mb-8">
                    <label for="password-confirm" class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Confirm Password</label>
                    <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full bg-[#1a1a1d] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#c86b51] transition-all text-sm placeholder-white/20" placeholder="••••••••">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-4 rounded-xl bg-white text-[#0a0a0b] font-bold hover:bg-gray-200 transition-colors shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                    Sign Up
                </button>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-white/50">Already have an account? <a href="{{ route('login') }}" class="text-white hover:underline transition-all">Sign in</a></p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
