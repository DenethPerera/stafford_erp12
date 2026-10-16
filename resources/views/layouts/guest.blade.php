<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sign in to CertTrack — Certificate Tracking System">
    <title>{{ config('app.name', 'CertTrack') }} — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:         #1a4fd8;
            --blue-dk:      #1239a8;
            --cyan:         #00d4f0;
            --cyan-glow:    rgba(0, 212, 240, 0.6);
            --navy:         rgba(5, 16, 65, 0.35);
            --navy-light:   rgba(8, 22, 80, 0.15);
            --navy-deep:    rgba(3, 10, 45, 0.50);
            --white:        #ffffff;
            --gray-bg:      #ffffff;
            --text-white:   #ffffff;
            --text-muted:   rgba(255,255,255,0.75);

            --hero-img: url('/images/login-hero.png');
        }

        html, body {
            height: 100%;
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* ═══════════════════════════════════════
           OUTER WRAPPER
        ═══════════════════════════════════════ */
        .auth-shell {
            display: flex;
            height: 100vh;
            position: relative;
            overflow: hidden;
            background: var(--gray-bg);
        }

        /* ═══════════════════════════════════════
           LEFT PANEL — HERO / BRANDING
        ═══════════════════════════════════════ */
        .lp {
            position: relative;
            flex: 0 0 45%;
            min-width: 420px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 2;
            clip-path: polygon(0 0, 80% 0, 100% 50%, 80% 100%, 0 100%);
        }
        /* Hero image layer */
        .lp-bg {
            position: absolute;
            inset: 0;
            background-image: var(--hero-img);
            background-size: cover;
            background-position: center;
            z-index: 0;
            filter: brightness(1.3) contrast(1.05);
        }

        /* Dark gradient overlay */
        .lp-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg,
                    var(--navy) 0%,
                    var(--navy-light) 40%,
                    var(--navy-light) 55%,
                    var(--navy-deep) 100%
                );
            z-index: 1;
        }

        /* All content above bg */
        .lp-inner {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 0;
        }

        /* ── Left panel top section ───────────────── */
        .lp-top {
            padding: 2rem 2.2rem 0;
        }
        .lp-live-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(0, 212, 240, 0.12);
            border: 1px solid rgba(0, 212, 240, 0.30);
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--cyan);
        }
        .lp-live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--cyan);
            animation: blink 1.8s ease-in-out infinite;
        }
        @keyframes blink {
            0%,100% { opacity: 1; transform: scale(1); }
            50%     { opacity: 0.4; transform: scale(1.5); }
        }

        /* ── Hero text block matching screenshot ────────────────────────── */
        .lp-hero {
            padding: 1.8rem 2.5rem 1.5rem;
        }
        .lp-welcome-label {
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.01em;
            margin-bottom: 0.2rem;
        }
        .lp-title {
            font-size: clamp(2.2rem, 3.4vw, 3rem);
            font-weight: 900;
            color: var(--white);
            line-height: 1.08;
            margin-bottom: 0.5rem;
            letter-spacing: -0.03em;
        }
        .lp-title em {
            font-style: normal;
            color: var(--cyan);
        }
        .lp-tagline {
            font-size: 1.05rem;
            font-weight: 600;
            color: #ffffff;
            opacity: 0.95;
            margin-bottom: 1.2rem;
            letter-spacing: -0.01em;
        }
        .lp-accent-line {
            width: 3rem;
            height: 3px;
            background: linear-gradient(90deg, var(--cyan), transparent);
            border-radius: 3px;
            margin-bottom: 1.4rem;
        }
        .lp-keywords {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-white);
            letter-spacing: -0.01em;
        }
        .lp-keywords span { 
            color: var(--cyan); 
            font-weight: 800; 
            font-size: 0.8rem; 
        }

        /* ── Spacer — pushes contact to bottom ──────── */
        .lp-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* ── Contact block matching screenshot ─────────────────────────── */
        .lp-contact {
            padding: 0 2.5rem 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .ci {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
        }
        .ci-badge {
            flex-shrink: 0;
            width: 42px; height: 42px;
            border-radius: 50%;
            background: rgba(0, 114, 245, 0.25);
            border: 2px solid #0072f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }
        .ci-badge svg { width: 18px; height: 18px; }
        .ci-text { line-height: 1.35; padding-top: 2px; }
        .ci-label { font-size: 0.82rem; font-weight: 700; color: var(--white); }
        .ci-value { font-size: 0.82rem; font-weight: 500; color: #e2e8f0; }

        /* ── Copyright footer ─────────────────────── */
        .lp-foot {
            padding: 0.85rem 2.5rem 1.2rem;
            font-size: 0.72rem;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
            border-top: 1px solid rgba(255,255,255,0.12);
        }

        /* ═══════════════════════════════════════
           WAVE SEPARATOR — SVG curved edge + cyan line
        ═══════════════════════════════════════ */
        .lp-edge {
            position: absolute;
            top: 0;
            left: 0;
            width: 45%;
            min-width: 420px;
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }
        .lp-edge svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* ═══════════════════════════════════════
           RIGHT PANEL — FORM AREA
        ═══════════════════════════════════════ */
        .rp {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--white);
            overflow-y: auto;
            position: relative;
            z-index: 1;
        }

        /* ── Top bar: logo + language/theme controls ── */
        .rp-topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 1.8rem 2.8rem 0;
            flex-shrink: 0;
        }

        .rp-topbar-spacer { flex: 1; }

        /* Logo block matching screenshot */
        .rp-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .rp-logo-diamond {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #0f52ba 0%, #08348c 100%);
            transform: rotate(45deg);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(15,82,186,0.3);
        }
        .rp-logo-diamond span {
            transform: rotate(-45deg);
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--white);
            letter-spacing: -0.5px;
        }
        .rp-logo-text { display: flex; flex-direction: column; }
        .rp-logo-divider {
            width: 100%;
            height: 2px;
            background: #000000;
            margin: 2px 0 3px;
        }
        
        .rp-logo-name {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0b132a;
            line-height: 1;
            letter-spacing: -0.03em;
        }
        .rp-logo-sub {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            color: #0b132a;
            text-transform: uppercase;
        }

        /* ── Controls (below logo on right side) ────── */
        .rp-controls-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 2.8rem 0;
            flex-shrink: 0;
        }
        .ctrl-lang {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #1e293b;
            background: #ffffff;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .ctrl-lang:hover { border-color: var(--blue); }
        .ctrl-lang svg { width: 15px; height: 15px; color: #64748b; }

        /* Light / Dark toggle switch */
        .ctrl-theme {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .ctrl-theme svg { width: 15px; height: 15px; color: #64748b; }
        .theme-pill {
            position: relative;
            width: 42px; height: 22px;
        }
        .theme-pill input { opacity: 0; width: 0; height: 0; }
        .theme-track {
            position: absolute;
            inset: 0;
            background: #0f52ba;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .theme-track::after {
            content: '';
            position: absolute;
            left: 3px; top: 3px;
            width: 16px; height: 16px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s;
        }
        .theme-pill input:checked + .theme-track { background: #334155; }
        .theme-pill input:checked + .theme-track::after { transform: translateX(20px); }

        /* ── Main form container ───────────────────── */
        .rp-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 2.8rem 1rem;
        }
        .rp-form-card {
            width: 100%;
            max-width: 450px;
        }

        /* ── Responsive ─────────────────────────────── */
        @media (max-width: 1024px) {
            html, body { overflow-y: auto; }
            .auth-shell { flex-direction: column; height: auto; min-height: 100vh; }
            .lp { 
                flex: none; 
                width: 100%; 
                min-width: 100%; 
                height: 320px; 
                clip-path: none; 
            }
            .lp-edge { display: none; }
            .lp-contact { display: none; }
            .rp { width: 100%; padding-bottom: 2rem; }
            .rp-topbar { padding: 1.5rem 1.5rem 0; }
            .rp-controls-row { padding: 0.5rem 1.5rem 0; }
            .rp-body { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="auth-shell">

    <aside class="lp" aria-label="CertTrack branding panel">
        <div class="lp-bg"></div>
        <div class="lp-overlay"></div>
        <div class="lp-inner">

            <div class="lp-center">
                <div class="lp-hero">
                    <p class="lp-welcome-label">Welcome to</p>
                    <h1 class="lp-title">STAFFORD <em>ERP</em></h1>
                    <p class="lp-tagline">Enterprise Resource Planning System</p>
                    <div class="lp-accent-line"></div>
                    <div class="lp-keywords">
                        Innovate
                        <span>●</span>
                        Produce
                        <span>●</span>
                        Deliver
                    </div>
                </div>
            </div>

            <div class="lp-spacer"></div>

            <div class="lp-contact">
                <div class="ci">
                    <div class="ci-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 6.75Z"/>
                        </svg>
                    </div>
                    <div class="ci-text">
                        <div class="ci-label">Tel:</div>
                        <div class="ci-value">(416) 847-3394</div>
                    </div>
                </div>
                <div class="ci">
                    <div class="ci-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                        </svg>
                    </div>
                    <div class="ci-text">
                        <div class="ci-label">Fax:</div>
                        <div class="ci-value">(416) 252-0573</div>
                    </div>
                </div>
                <div class="ci">
                    <div class="ci-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                        </svg>
                    </div>
                    <div class="ci-text">
                        <div class="ci-label">E-mail:</div>
                        <div class="ci-value">info@staftex.com</div>
                    </div>
                </div>
                <div class="ci">
                    <div class="ci-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                        </svg>
                    </div>
                    <div class="ci-text">
                        <div class="ci-label">Address:</div>
                        <div class="ci-value">1 Eva Road, Suite 101<br>Toronto, ON M9C4Z5<br>CANADA</div>
                    </div>
                </div>
            </div>

            <div class="lp-foot">
                © {{ date('Y') }}  ERav technologies. All Rights Reserved.
            </div>

        </div>
    </aside>

    <div class="lp-edge" aria-hidden="true">
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="cyanLine" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="rgba(0,212,240,0)"/>
                    <stop offset="15%"  stop-color="rgba(0,212,240,0.9)"/>
                    <stop offset="85%"  stop-color="rgba(0,212,240,0.9)"/>
                    <stop offset="100%" stop-color="rgba(0,212,240,0)"/>
                </linearGradient>
                <filter id="glow">
                    <feGaussianBlur stdDeviation="1.2" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
            </defs>
            <path d="M 77 0 L 100 50 L 77 100"
                  fill="none"
                  stroke="url(#cyanLine)"
                  stroke-width="0.6"
                  opacity="0.95"
                  filter="url(#glow)"/>
        </svg>
    </div>

    <main class="rp" role="main">

        <div class="rp-topbar">
            <div class="rp-topbar-spacer"></div>
            <div class="rp-logo">
                <div class="rp-logo-diamond">
                    <span>S</span>
                </div>
                <div class="rp-logo-text">
                    <div class="rp-logo-name">Staftex</div>
                    <div class="rp-logo-divider"></div>
                    <div class="rp-logo-sub">Stafford Textiles Limited</div>
                </div>
            </div>
        </div>

        <div class="rp-controls-row">
            <button class="ctrl-lang" type="button" aria-label="Select language">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253"/>
                </svg>
                English
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div class="ctrl-theme">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                </svg>
                <label class="theme-pill" aria-label="Toggle dark mode">
                    <input type="checkbox" id="dark-toggle">
                    <span class="theme-track"></span>
                </label>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                </svg>
            </div>
        </div>

        <div class="rp-body">
            <div class="rp-form-card">
                {{ $slot }}
            </div>
        </div>

    </main>

</div>

</body>
</html>