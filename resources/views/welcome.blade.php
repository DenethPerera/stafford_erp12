<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'CertTrack') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600,700,900&display=swap" rel="stylesheet" />

        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                background-color: #f8f9fa;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .navbar {
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .hero-section {
                flex: 1;
                display: flex;
                align-items: center;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            }
            .feature-card {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                border: none;
                border-radius: 1rem;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            .brand-logo {
                width: 48px;
                height: 48px;
                background: linear-gradient(135deg, #0dcaf0, #0d6efd);
                color: white;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 900;
                font-size: 24px;
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bolder" href="{{ url('/') }}">
                    <div class="brand-logo">C</div>
                    {{ config('app.name', 'CertTrack') }}
                </a>
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLinks">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarLinks">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 fw-semibold">
                        @if (Route::has('login'))
                            @auth
                                <li class="nav-item">
                                    <a href="{{ url('/home') }}" class="nav-link text-dark hover-primary">Dashboard</a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a href="{{ route('login') }}" class="nav-link text-dark">Log in</a>
                                </li>
                                @if (Route::has('register'))
                                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                                        <a href="{{ route('register') }}" class="btn btn-primary rounded-pill px-4 fw-bold">Register</a>
                                    </li>
                                @endif
                            @endauth
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        <main class="hero-section py-5 py-lg-0">
            <div class="container">
                <div class="row align-items-center justify-content-between g-5">
                    <div class="col-lg-6 text-center text-lg-start">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold mb-3" style="letter-spacing: 1px;">CERTIFICATE TRACKING SYSTEM</span>
                        <h1 class="display-3 fw-bolder text-dark mb-4 lh-sm">Manage Certifications with <span class="text-primary">Confidence</span></h1>
                        <p class="lead text-muted fw-semibold mb-5" style="max-width: 500px; margin: 0 auto 0 0;">
                            A comprehensive platform for tracking product certifications, factories, and compliance standards efficiently.
                        </p>
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/home') }}" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">Go to Dashboard <i class="bi bi-arrow-right ms-2"></i></a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm me-2">Get Started</a>
                                <a href="#" class="btn btn-outline-secondary btn-lg rounded-pill px-4 fw-bold">Learn More</a>
                            @endauth
                        @endif
                    </div>
                    
                    <div class="col-lg-5 d-none d-lg-block">
                        <div class="position-relative">
                            <!-- Abstract Illustration / Dashboard Mockup placeholder -->
                            <div class="bg-white p-4 rounded-4 shadow-lg border">
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                                    <div class="d-flex gap-2">
                                        <div class="rounded-circle bg-danger" style="width: 12px; height: 12px;"></div>
                                        <div class="rounded-circle bg-warning" style="width: 12px; height: 12px;"></div>
                                        <div class="rounded-circle bg-success" style="width: 12px; height: 12px;"></div>
                                    </div>
                                    <div class="bg-light rounded-pill" style="height: 10px; width: 100px;"></div>
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-6"><div class="bg-primary bg-opacity-10 rounded-3" style="height: 80px;"></div></div>
                                    <div class="col-6"><div class="bg-info bg-opacity-10 rounded-3" style="height: 80px;"></div></div>
                                </div>
                                
                                <div class="bg-light rounded-3 mb-3" style="height: 20px; width: 60%;"></div>
                                <div class="bg-light rounded-3 mb-2" style="height: 12px; width: 100%;"></div>
                                <div class="bg-light rounded-3 mb-2" style="height: 12px; width: 100%;"></div>
                                <div class="bg-light rounded-3 mb-2" style="height: 12px; width: 80%;"></div>
                            </div>
                            
                            <!-- Decorative elements -->
                            <div class="position-absolute top-0 end-0 bg-warning rounded-circle opacity-50" style="width: 100px; height: 100px; margin-top: -30px; margin-right: -30px; filter: blur(20px); z-index: -1;"></div>
                            <div class="position-absolute bottom-0 start-0 bg-info rounded-circle opacity-50" style="width: 120px; height: 120px; margin-bottom: -40px; margin-left: -40px; filter: blur(25px); z-index: -1;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="bg-white py-4 border-top">
            <div class="container text-center">
                <p class="text-muted small fw-semibold mb-0">
                    &copy; {{ date('Y') }} {{ config('app.name', 'CertTrack') }}. All rights reserved.
                </p>
                <div class="text-muted small mt-1">
                    Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                </div>
            </div>
        </footer>

        <!-- Bootstrap 5 JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
