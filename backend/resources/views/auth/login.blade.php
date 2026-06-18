@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-6">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-head font-bold mb-2">Welcome Back</h1>
            <p class="text-white/50 text-sm">Sign in to access your clinical dashboard.</p>
        </div>

        <!-- Glassmorphic Card -->
        <div class="p-8 rounded-3xl bg-[#121214] border border-white/5 shadow-2xl relative overflow-hidden group">
            <!-- Subtle Glow -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#4a5d23]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

            <form method="POST" action="{{ route('login') }}" class="relative z-10">
                @csrf

                <!-- Email Input -->
                <div class="mb-6">
                    <label for="email" class="block text-xs font-medium text-white/50 uppercase tracking-wider mb-2">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                        class="w-full bg-[#1a1a1d] border @error('email') border-red-500 @else border-white/10 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] transition-all text-sm placeholder-white/20" placeholder="doctor@clinic.com">
                    
                    @error('email')
                        <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-medium text-white/50 uppercase tracking-wider">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs text-[#4a5d23] hover:text-[#5e772b] transition-colors">Forgot Password?</a>
                        @endif
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full bg-[#1a1a1d] border @error('password') border-red-500 @else border-white/10 @enderror rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#4a5d23] transition-all text-sm placeholder-white/20" placeholder="••••••••">

                    @error('password')
                        <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="mb-8 flex items-center">
                    <input class="w-4 h-4 rounded bg-[#1a1a1d] border-white/10 text-[#4a5d23] focus:ring-[#4a5d23] focus:ring-offset-[#121214]" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="ml-2 text-sm text-white/70 cursor-pointer" for="remember">
                        Remember me for 30 days
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-4 rounded-xl bg-white text-[#0a0a0b] font-bold hover:bg-gray-200 transition-colors shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                    Sign In
                </button>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-white/50">Don't have an account? <a href="{{ route('register') }}" class="text-white hover:underline transition-all">Sign up</a></p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
