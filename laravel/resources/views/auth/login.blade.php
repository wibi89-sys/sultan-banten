@php
    $instansi = \App\Models\Instansi::getFirst();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('assets/favicon.ico') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sultan Banten</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .bg-primary { background-color: var(--primary-color); }
        .bg-secondary { background-color: var(--secondary-color); }
        .text-primary { color: var(--primary-color); }
        .border-primary { border-color: var(--primary-color); }
        .ring-primary { --tw-ring-color: var(--primary-color); }
        
        .hero-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
        }
        
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Hero Section -->
    <section class="hero-gradient text-white relative overflow-hidden min-h-screen flex flex-col">
        <!-- Decorative Elements - Hidden on mobile -->
        <div class="hidden sm:block absolute top-0 left-0 w-full h-full opacity-10">
            <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-48 h-48 bg-white rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-white rounded-full blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
        </div>
        
        <!-- Header -->
        <header class="relative z-10">
            <div class="max-w-6xl mx-auto px-4 py-3 sm:py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 sm:gap-3">
                        @if (!empty($instansi->logo))
                            <img src="{{ asset($instansi->logo) }}" alt="Logo" class="w-10 h-10 sm:w-12 sm:h-12 object-contain bg-white rounded-lg p-1">
                        @else
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-balance-scale text-lg sm:text-2xl text-primary"></i>
                            </div>
                        @endif
                        <div>
                            <h1 class="font-bold text-lg sm:text-xl">{{ $instansi->nama_instansi ?? 'SULTAN BANTEN' }}</h1>
                            <p class="text-xs text-white/80 hidden sm:block">Ditjenpas Banten</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="bg-white text-gray-800 hover:bg-gray-100 px-3 sm:px-6 py-1.5 sm:py-2.5 rounded-lg sm:rounded-xl font-medium flex items-center gap-1 sm:gap-2 shadow-lg transition-all text-sm">
                        <i class="fas fa-home"></i> 
                        <span class="hidden sm:inline">Lihat Dashboard</span>
                        <span class="sm:hidden">Dashboard</span>
                    </a>
                </div>
            </div>
        </header>
        
        <!-- Mobile: Simplified Layout -->
        <div class="sm:hidden relative z-10 flex-1 flex flex-col justify-center px-4 py-6">
            <div class="bg-white rounded-2xl shadow-2xl p-5">
                <div class="text-center mb-4">
                    <h3 class="text-2xl font-extrabold text-gray-800 tracking-tight">Login</h3>
                </div>
                
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg mb-3 flex items-center gap-2 text-sm">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                @if (session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded-lg mb-3 flex items-center gap-2 text-sm">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                
                <form action="{{ route('login') }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" name="username" value="{{ old('username') }}" class="input-field w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-lg focus:outline-none text-gray-800 text-sm placeholder:text-gray-400" placeholder="Username" required autofocus>
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="password" name="password" class="input-field w-full pl-10 pr-3 py-3 border-2 border-gray-200 rounded-lg focus:outline-none text-gray-800 text-sm placeholder:text-gray-400" placeholder="Password" required>
                    </div>
                    <div class="flex items-center text-xs text-gray-600">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="mr-2 rounded"> Ingat Sesi Saya
                        </label>
                    </div>
                    <button type="submit" class="w-full bg-primary hover:opacity-90 text-white py-3 rounded-lg font-semibold transition-all flex items-center justify-center gap-2 text-sm" style="background-color: #1e40af;">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Masuk</span>
                    </button>
                </form>
                
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <a href="{{ route('dashboard') }}" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-lg font-medium transition-all flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-home"></i>
                        <span>Kembali ke Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Desktop: Full Layout -->
        <div class="hidden sm:flex relative z-10 max-w-6xl mx-auto w-full px-4 py-8 lg:py-16 flex-1 flex-col justify-center">
            <div class="grid lg:grid-cols-2 gap-8 items-center">
                <!-- Left Content -->
                <div class="text-center lg:text-left">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-3 leading-tight">
                        Sistem Update Laporan Terpadu Harian
                    </h2>
                    
                    <p class="text-xs sm:text-sm lg:text-base text-white/90 mb-6 max-w-lg mx-auto lg:mx-0 leading-relaxed">
                        SULTAN BANTEN - Sistem terintegrasi untuk monitoring dan pelaporan harian UPT Lapas/Rutan di wilayah Banten.
                    </p>
                    
                    <div class="flex flex-wrap justify-center lg:justify-start gap-3 text-sm">
                        <div class="flex items-center gap-2 bg-white/10 backdrop-blur px-3 py-2 rounded-lg">
                            <i class="fas fa-clock text-yellow-300"></i>
                            <span>Real-time Update</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/10 backdrop-blur px-3 py-2 rounded-lg">
                            <i class="fas fa-shield-alt text-green-300"></i>
                            <span>Aman & Terpercaya</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/10 backdrop-blur px-3 py-2 rounded-lg">
                            <i class="fas fa-mobile-alt text-blue-300"></i>
                            <span>Mobile Friendly</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Content - Login Box -->
                <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 animate-float" style="animation-delay: 0.2s;">
                    <div class="text-center mb-6">
                        <h3 class="text-3xl font-extrabold text-gray-800 tracking-tight">Login</h3>
                    </div>
                    
                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif
                    
                    <form action="{{ route('login') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="relative">
                            <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="username" value="{{ old('username') }}" class="input-field w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none text-gray-800 placeholder:text-gray-400" placeholder="Username" required autofocus>
                        </div>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="password" class="input-field w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none text-gray-800 placeholder:text-gray-400" placeholder="Password" required>
                        </div>
                        <div class="flex items-center text-xs text-gray-600">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="remember" class="mr-2 rounded"> Ingat Sesi Saya
                            </label>
                        </div>
                        <button type="submit" class="w-full bg-primary hover:opacity-90 text-white py-4 rounded-xl font-semibold text-lg transition-all hover:shadow-lg flex items-center justify-center gap-2" style="background-color: #1e40af;">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Masuk</span>
                        </button>
                    </form>
                    
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <a href="{{ route('dashboard') }}" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-medium transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-home"></i>
                            <span>Kembali ke Dashboard</span>
                        </a>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            Hubungi administrator jika lupa password
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Wave Divider - Hidden on mobile -->
        <div class="hidden sm:block absolute bottom-0 left-0 w-full">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="#f3f4f6"></path>
            </svg>
        </div>
    </section>

    <!-- Info Section - Hidden on mobile -->
    <section class="hidden sm:block bg-gray-100 py-8">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Card 1 -->
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background-color: rgba(30, 64, 175, 0.1);">
                        <i class="fas fa-chart-line text-xl text-primary"></i>
                    </div>
                    <h4 class="font-semibold text-gray-800 mb-2">Monitoring Real-time</h4>
                    <p class="text-sm text-gray-600">Pantau data penghuni dan laporan harian secara real-time dengan visualisasi yang informatif.</p>
                </div>
                
                <!-- Card 2 -->
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background-color: rgba(30, 64, 175, 0.1);">
                        <i class="fas fa-file-alt text-xl text-primary"></i>
                    </div>
                    <h4 class="font-semibold text-gray-800 mb-2">Laporan Terintegrasi</h4>
                    <p class="text-sm text-gray-600">Sistem pelaporan terpadu untuk seluruh UPT Lapas/Rutan dengan format yang terstandarisasi.</p>
                </div>
                
                <!-- Card 3 -->
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background-color: rgba(30, 64, 175, 0.1);">
                        <i class="fas fa-shield-alt text-xl text-primary"></i>
                    </div>
                    <h4 class="font-semibold text-gray-800 mb-2">Keamanan Terjamin</h4>
                    <p class="text-sm text-gray-600">Data diamankan dengan sistem keamanan berlapis dan akses terbatas sesuai wewenang pengguna.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t py-3 sm:py-6 mt-auto">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 sm:gap-4">
                <div class="flex items-center gap-2 sm:gap-3">
                    @if (!empty($instansi->logo))
                        <img src="{{ asset($instansi->logo) }}" alt="Logo" class="w-6 h-6 sm:w-8 sm:h-8 object-contain">
                    @else
                        <div class="w-6 h-6 sm:w-8 sm:h-8 bg-gray-100 rounded flex items-center justify-center">
                            <i class="fas fa-balance-scale text-xs sm:text-sm text-primary"></i>
                        </div>
                    @endif
                    <div>
                        <p class="font-semibold text-gray-800 text-sm sm:text-base">SULTAN BANTEN</p>
                        <p class="text-xs text-gray-500 hidden sm:block">Ditjenpas Banten</p>
                    </div>
                </div>
                <div class="text-center sm:text-right text-xs sm:text-sm text-gray-500">
                    <p>&copy; {{ date('Y') }} SULTAN BANTEN</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
