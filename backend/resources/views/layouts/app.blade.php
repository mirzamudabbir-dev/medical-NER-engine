<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ClinIQ AI Dashboard') }}</title>
    
    <!-- Fonts -->
    <link href="https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@800&f[]=satoshi@400,500&display=swap" rel="stylesheet">
    
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- GSAP for any animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        :root {
            --charcoal: #0a0a0b;
            --moss-green: #4a5d23;
            --clay: #c86b51;
            --card-bg: #121214;
            --font-head: 'Cabinet Grotesk', sans-serif;
            --font-body: 'Satoshi', sans-serif;
        }

        body {
            background-color: var(--charcoal);
            color: white;
            font-family: var(--font-body);
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-head);
            font-weight: 800;
        }

        /* SVG Noise Texture */
        .noise-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.04;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        /* Hover Glow Card */
        .glow-card {
            position: relative;
            background: var(--card-bg);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.5rem;
        }

        .glow-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(
                800px circle at var(--mouse-x, 0) var(--mouse-y, 0),
                rgba(74, 93, 35, 0.15),
                transparent 40%
            );
            opacity: 0;
            transition: opacity 0.5s ease;
            pointer-events: none;
            z-index: 1;
        }

        .glow-card:hover::before {
            opacity: 1;
        }

        .glow-card > * {
            position: relative;
            z-index: 2;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0a0a0b;
        }
        ::-webkit-scrollbar-thumb {
            background: #2a2a2d;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #3a3a3d;
        }
    </style>
</head>
<body class="antialiased">
    <div class="noise-overlay"></div>

    <nav id="navbar" class="fixed top-6 left-1/2 -translate-x-1/2 w-[95%] max-w-7xl z-50 rounded-full border border-white/5 bg-[#121214]/60 backdrop-blur-[12px] transition-all duration-300 flex items-center justify-between px-6 py-4">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-[#4a5d23] flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <span class="font-head font-bold text-lg tracking-wide">ClinIQ AI</span>
        </a>
        
        <div class="hidden md:flex items-center gap-8 text-sm font-medium text-white/70">
            @auth
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-white' : 'hover:text-white transition-colors' }}">Dashboard</a>
            @endauth
        </div>
        
        <div class="flex items-center gap-4">
            @guest
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="text-sm font-medium text-white/70 hover:text-white transition-colors">Login</a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="px-5 py-2 rounded-full border border-white/10 hover:bg-white/5 transition-colors text-sm font-medium">Get Started</a>
                @endif
            @else
                <div class="relative group">
                    <button class="flex items-center gap-2 text-sm font-medium text-white/90 hover:text-white">
                        {{ Auth::user()->name }}
                        <svg class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <!-- Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 py-2 bg-[#1a1a1d] border border-white/10 rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                        <a class="block px-4 py-2 text-sm text-red-400 hover:bg-white/5" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                </div>
            @endguest
        </div>
    </nav>

    <main class="pt-32 pb-20 min-h-screen relative z-10" id="app">
        @yield('content')
    </main>

    <script>
        // Global interactive glow initialization
        function initInteractiveGlow() {
            const interactives = document.querySelectorAll('.interactive-glow');
            interactives.forEach(el => {
                if(el.dataset.glowInit) return; // Prevent double init
                el.dataset.glowInit = true;
                el.addEventListener('mousemove', e => {
                    const rect = el.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    el.style.setProperty('--mouse-x', `${x}px`);
                    el.style.setProperty('--mouse-y', `${y}px`);
                });
            });
        }
        
        document.addEventListener('DOMContentLoaded', initInteractiveGlow);
    </script>
</body>
</html>
