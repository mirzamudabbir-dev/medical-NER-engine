<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clinical Boutique API</title>

    <!-- Fonts -->
    <link href="https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@800&f[]=satoshi@400,500&display=swap" rel="stylesheet">
    
    <!-- Vite / Tailwind -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

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

        /* Shuffler Container */
        .shuffler-container {
            perspective: 1000px;
            position: relative;
            height: 100%;
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .shuffler-card {
            position: absolute;
            width: 70%;
            height: 120px;
            background: #1a1a1d;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .shuffler-container:hover .sc-1 {
            transform: translate(-20px, -10px) rotate(-4deg);
        }

        .shuffler-container:hover .sc-2 {
            transform: translate(20px, 10px) rotate(4deg);
        }

        .shuffler-container:hover .sc-3 {
            transform: scale(1.05) translate(0, -15px);
            z-index: 10;
            border-color: rgba(74, 93, 35, 0.5);
        }

        /* Stacking Protocol Cards */
        .protocol-card {
            position: sticky;
            top: 128px;
            min-height: 400px;
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.5rem;
            padding: 3rem;
            margin-bottom: 2rem;
            overflow: hidden;
            will-change: transform, filter, opacity;
        }

        .step-number {
            position: absolute;
            top: -2rem;
            right: -1rem;
            font-size: 15rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.03);
            line-height: 1;
            font-family: var(--font-head);
            pointer-events: none;
            z-index: 0;
        }
        
        .stagger-line {
            overflow: hidden;
            display: block;
        }
        .stagger-text {
            display: block;
            transform: translateY(100%);
        }
    </style>
</head>
<body class="antialiased">
    <div class="noise-overlay"></div>

    <!-- Floating Navbar -->
    <nav id="navbar" class="fixed top-6 left-1/2 -translate-x-1/2 w-[95%] max-w-7xl z-50 rounded-full border border-white/5 bg-[#121214]/20 backdrop-blur-[4px] transition-all duration-300 flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-[#4a5d23] flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <span class="font-head font-bold text-lg tracking-wide">ClinIQ AI</span>
        </div>
        <div class="hidden md:flex items-center gap-8 text-sm font-medium text-white/70">
            <a href="#" class="hover:text-white transition-colors">Platform</a>
            <a href="#" class="hover:text-white transition-colors">Solutions</a>
            <a href="#" class="hover:text-white transition-colors">Documentation</a>
        </div>
        <div class="flex items-center gap-4">
            @auth
                <div class="hidden md:flex items-center gap-2 mr-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="text-xs font-medium text-white/50 uppercase tracking-wider">Logged in as {{ Auth::user()->name }}</span>
                </div>
                <a href="{{ route('home') }}" class="px-5 py-2 rounded-full bg-[#4a5d23] text-white hover:bg-[#5a712a] transition-colors text-sm font-bold border border-[#4a5d23]/50 shadow-[0_0_15px_rgba(74,93,35,0.3)]">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-white/70 hover:text-white transition-colors">Login</a>
                <a href="{{ route('register') }}" class="px-5 py-2 rounded-full border border-white/10 hover:bg-white/5 transition-colors text-sm font-medium">Sign Up</a>
            @endauth
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center pt-32 pb-20 overflow-hidden">
        <!-- Background Image & Gradient -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-gradient-to-b from-[#0a0a0b]/80 to-[#0a0a0b] z-10"></div>
            <img src="https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?auto=format&fit=crop&q=80&w=2000" alt="Dark Forest" class="w-full h-full object-cover opacity-30">
        </div>

        <div class="relative z-10 w-full max-w-7xl mx-auto px-6 flex flex-col items-center text-center">
            <div class="mb-8 inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-white/10 bg-white/5 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-medium tracking-wide text-white/80 uppercase">System Online</span>
            </div>

            <h1 class="text-[64px] md:text-[96px] leading-[1.05] tracking-tight mb-8">
                <span class="stagger-line"><span class="stagger-text">Precision diagnostics</span></span>
                <span class="stagger-line"><span class="stagger-text text-[#c86b51]">meets organic</span></span>
                <span class="stagger-line"><span class="stagger-text">intelligence.</span></span>
            </h1>

            <p class="text-lg md:text-xl text-white/60 max-w-2xl mb-12 font-body font-light">
                Our bespoke AI extracts, maps, and analyzes unstructured medical data into a clean, unified schema—accelerating clinical workflows with uncompromising accuracy.
            </p>

            <div class="flex flex-col sm:flex-row items-center gap-4">
                <a href="{{ route('home') }}" class="px-8 py-4 rounded-full bg-white text-[#0a0a0b] font-bold hover:scale-105 transition-transform duration-300">Launch Dashboard</a>
                <a href="#" class="px-8 py-4 rounded-full border border-white/20 text-white hover:bg-white/5 transition-colors duration-300">Read the Docs</a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 opacity-50 z-10">
            <span class="text-xs uppercase tracking-widest">Scroll</span>
            <div class="w-[1px] h-12 bg-gradient-to-b from-white to-transparent"></div>
        </div>
    </section>

    <!-- Intelligence Architecture Grid -->
    <section class="py-32 px-6 max-w-7xl mx-auto">
        <h2 class="text-4xl md:text-5xl mb-16 text-center">Intelligence Architecture</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div class="glow-card h-[450px] p-8 flex flex-col items-center justify-between group">
                <div class="text-center mt-4 z-10">
                    <h3 class="text-2xl mb-2">Intelligent Parsing</h3>
                    <p class="text-white/50 text-sm">High-fidelity extraction of clinical PDFs and images.</p>
                </div>
                <div class="shuffler-container w-full">
                    <div class="shuffler-card sc-1"><span class="text-white/30 text-xs">Lab_Results.pdf</span></div>
                    <div class="shuffler-card sc-2"><span class="text-white/30 text-xs">Itemized_Bill.pdf</span></div>
                    <div class="shuffler-card sc-3 border-[#c86b51]/30 bg-[#1a1a1d]"><span class="text-white/80 font-medium">Discharge.pdf</span></div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="glow-card h-[450px] p-8 flex flex-col justify-between">
                <div>
                    <h3 class="text-2xl mb-2">Automated Extraction</h3>
                    <p class="text-white/50 text-sm">Real-time conversion of text into 25+ structured fields.</p>
                </div>
                <div class="w-full flex-grow mt-6 bg-[#0a0a0b] border border-white/5 rounded-xl p-4 font-mono text-xs overflow-hidden relative">
                    <div class="absolute top-2 left-4 text-[#4a5d23] opacity-50">>_ extract_data()</div>
                    <div id="typewriter" class="mt-8 text-white/70 h-full"></div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="glow-card h-[450px] p-8 interactive-glow flex flex-col justify-between">
                <div>
                    <h3 class="text-2xl mb-2">Clinical Dashboard</h3>
                    <p class="text-white/50 text-sm">Purpose-built interface for medical data verification.</p>
                </div>
                <div class="flex-grow flex items-center justify-center relative mt-6 border border-white/5 rounded-xl bg-gradient-to-tr from-transparent to-[#4a5d23]/10 overflow-hidden">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')]"></div>
                    <div class="w-24 h-24 rounded-full border border-[#4a5d23]/30 flex items-center justify-center relative z-10 shadow-[0_0_40px_rgba(74,93,35,0.2)]">
                        <div class="w-2 h-2 rounded-full bg-[#4a5d23] animate-ping absolute"></div>
                        <div class="w-2 h-2 rounded-full bg-[#4a5d23]"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Parallax Statement -->
    <section class="py-40 bg-[#121214] border-y border-white/5 relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
            <h2 class="text-3xl md:text-5xl leading-tight font-light text-white/80">
                "We abstract the chaos of raw medical data into pure, structured clarity—empowering clinicians to focus on care, not paperwork."
            </h2>
        </div>
    </section>

    <!-- Sticky Stacking Protocols -->
    <section class="py-32 px-6 max-w-5xl mx-auto" id="protocol-container">
        <div class="mb-20 text-center">
            <h2 class="text-4xl md:text-5xl mb-4">Operational Protocol</h2>
            <p class="text-white/50">How the ClinIQ AI engine processes documents.</p>
        </div>

        <div class="protocol-card" id="card-1">
            <div class="step-number">01</div>
            <h3 class="text-3xl mb-4 relative z-10">Ingestion & Routing</h3>
            <p class="text-white/60 max-w-lg text-lg relative z-10">Documents are securely uploaded via our dashboard or API. The routing matrix automatically determines the document class (Discharge, Lab, Billing) and pre-processes the files for maximum OCR fidelity.</p>
        </div>

        <div class="protocol-card" id="card-2">
            <div class="step-number">02</div>
            <h3 class="text-3xl mb-4 relative z-10 text-[#4a5d23]">Entity Recognition</h3>
            <p class="text-white/60 max-w-lg text-lg relative z-10">Our custom SciSpacy models scan the raw text to identify diseases, medications, CPT codes, and timelines. Regex heuristics extract precise metrics, building a massive relational graph of the patient's visit.</p>
        </div>

        <div class="protocol-card" id="card-3">
            <div class="step-number">03</div>
            <h3 class="text-3xl mb-4 relative z-10 text-[#c86b51]">Human-in-the-Loop</h3>
            <p class="text-white/60 max-w-lg text-lg relative z-10">The structured data is immediately presented in our clinical dashboard. Agents can review, correct, and finalize the structured fields with dynamic confidence scoring highlighting potential anomalies.</p>
        </div>
    </section>



    <!-- Footer -->
    <footer class="bg-[#0a0a0b] border-t border-white/5 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-12 mb-20">
                <div>
                    <h4 class="font-head text-lg mb-6">ClinIQ AI</h4>
                    <p class="text-white/40 text-sm leading-relaxed max-w-xs">Building the structural foundation for medical intelligence and autonomous coding.</p>
                </div>
                <div>
                    <h5 class="text-white/80 font-medium mb-6">Platform</h5>
                    <ul class="space-y-3 text-sm text-white/40">
                        <li><a href="#" class="hover:text-white transition-colors">OCR Engine</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Dashboard</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API Reference</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Security (HIPAA)</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-white/80 font-medium mb-6">Company</h5>
                    <ul class="space-y-3 text-sm text-white/40">
                        <li><a href="#" class="hover:text-white transition-colors">About</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-white/80 font-medium mb-6">Legal</h5>
                    <ul class="space-y-3 text-sm text-white/40">
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-col md:flex-row items-center justify-between pt-8 border-t border-white/5">
                <p class="text-white/30 text-xs mb-4 md:mb-0">© 2026 ClinIQ AI Medical Intelligence. All rights reserved.</p>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/5 bg-[#121214]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#10b981] animate-pulse"></span>
                    <span class="text-[10px] uppercase tracking-wider text-white/60">System Operational</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script type="module">
        gsap.registerPlugin(ScrollTrigger);

        // 1. Hero Staggered Text Reveal
        gsap.to(".stagger-text", {
            y: "0%",
            duration: 1.2,
            stagger: 0.15,
            ease: "power4.out",
            delay: 0.2
        });

        // 2. Floating Navbar Scroll Morph
        ScrollTrigger.create({
            start: "top -50",
            end: 99999,
            toggleClass: {className: 'bg-[#121214]/80 backdrop-blur-[12px]', targets: '#navbar'}
        });

        // 3. Sticky Stacking Cards
        const cards = gsap.utils.toArray('.protocol-card');
        cards.forEach((card, i) => {
            if(i !== cards.length - 1) {
                gsap.to(card, {
                    scale: 0.94,
                    opacity: 0.4,
                    filter: "blur(8px)",
                    scrollTrigger: {
                        trigger: card,
                        start: "top 128px",
                        endTrigger: cards[i+1],
                        end: "top 128px",
                        scrub: true,
                        invalidateOnRefresh: true
                    }
                });
            }
        });

        // 4. Interactive Hover Glow
        const interactives = document.querySelectorAll('.interactive-glow');
        interactives.forEach(el => {
            el.addEventListener('mousemove', e => {
                const rect = el.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                el.style.setProperty('--mouse-x', `${x}px`);
                el.style.setProperty('--mouse-y', `${y}px`);
            });
        });

        // 5. Telemetry Typewriter Effect
        const lines = [
            '{"event":"INGEST","id":"doc_8x9A","type":"discharge_summary"}',
            '[AI] -> Extracting entities...',
            '>> Found: "Acute Myocardial Infarction" (CONF: 0.98)',
            '>> Found: "CPT: 92928" (CONF: 0.99)',
            '{"event":"MAP","schema":"FHIR","status":"success"}',
            '[DB] -> Committing to cluster...',
            'Waiting for next stream...'
        ];
        
        let tw = document.getElementById('typewriter');
        let currentLine = 0;
        let currentChar = 0;

        function type() {
            if(currentLine < lines.length) {
                if(currentChar < lines[currentLine].length) {
                    tw.innerHTML += lines[currentLine].charAt(currentChar);
                    currentChar++;
                    setTimeout(type, Math.random() * 50 + 20);
                } else {
                    tw.innerHTML += '<br>';
                    currentLine++;
                    currentChar = 0;
                    setTimeout(type, 500);
                }
            } else {
                setTimeout(() => {
                    tw.innerHTML = '';
                    currentLine = 0;
                    currentChar = 0;
                    type();
                }, 3000);
            }
        }
        
        setTimeout(type, 1500);
    </script>
</body>
</html>
