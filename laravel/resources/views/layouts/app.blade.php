@php
    $instansi = \App\Models\Instansi::getFirst();
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ !empty($instansi->favicon) ? asset($instansi->favicon) : asset('assets/favicon.ico') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', 'Sultan Banten - Kemenkumham Banten')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link href="{{ asset('assets/css/modern-theme.css') }}" rel="stylesheet">
    <script>
        // Set tema seawal mungkin agar tidak berkedip
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <style>
        /* Dark Mode Switch Styles */
        .dark-mode-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 15px;
        }
        .dark-mode-switch .switch-label {
            font-size: 13px;
            color: var(--text-secondary, #6c757d);
        }
        .dark-mode-switch .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
            margin-bottom: 0;
        }
        .dark-mode-switch .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .dark-mode-switch .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 24px;
        }
        .dark-mode-switch .slider:before {
            position: absolute;
            content: "\f185";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 11px;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .dark-mode-switch input:checked + .slider {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .dark-mode-switch input:checked + .slider:before {
            transform: translateX(24px);
            content: "\f186";
            color: #818cf8;
        }

        /* No Sidebar Mode - for public view */
        .main-content.no-sidebar {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .main-content.no-sidebar .topbar {
            background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 0;
            margin-bottom: 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .main-content.no-sidebar .topbar .public-logo {
            height: 45px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            animation: fadeInLeft 0.5s ease-out;
        }
        .main-content.no-sidebar .topbar .public-title {
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: fadeInLeft 0.5s ease-out 0.1s both;
        }
        .main-content.no-sidebar .topbar .public-subtitle {
            color: rgba(255,255,255,0.9);
            text-shadow: 0 1px 2px rgba(0,0,0,0.15);
            animation: fadeInLeft 0.5s ease-out 0.2s both;
        }
        .main-content.no-sidebar .topbar .btn-login-public {
            background: rgba(255,255,255,0.95);
            color: var(--primary-color, #667eea) !important;
            border: none;
            padding: 8px 20px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-radius: 20px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .main-content.no-sidebar .topbar .btn-login-public:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            color: var(--primary-color, #667eea) !important;
        }
        .main-content.no-sidebar .topbar .dark-mode-switch .switch-label {
            color: rgba(255,255,255,0.8);
        }
        .main-content.no-sidebar .topbar .dark-mode-switch .slider {
            background-color: rgba(255,255,255,0.3);
        }
        .main-content.no-sidebar .topbar .dark-mode-switch .slider:before {
            background-color: white;
        }

        /* Marquee styles */
        .marquee-container {
            position: fixed;
            bottom: 0;
            left: 260px;
            right: 0;
            height: 38px;
            background: var(--bg-card, #ffffff);
            border-top: 1px solid var(--border-color, #e2e8f0);
            display: flex;
            align-items: center;
            overflow: hidden;
            z-index: 1020;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        .marquee-container.full-width {
            left: 0 !important;
        }
        .marquee-label {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 1px;
            padding: 0 16px;
            height: 100%;
            display: flex;
            align-items: center;
            white-space: nowrap;
            z-index: 2;
        }
        .marquee-content {
            display: flex;
            align-items: center;
            white-space: nowrap;
            animation: marqueeScroll 35s linear infinite;
        }
        .marquee-container:hover .marquee-content {
            animation-play-state: paused;
        }
        .marquee-item {
            display: inline-flex;
            align-items: center;
            margin-right: 40px;
            font-size: 13px;
        }
        .global-greeting {
            color: #1e40af;
            font-weight: 700;
            text-transform: uppercase;
        }
        [data-theme="dark"] .global-greeting {
            color: #60a5fa;
        }
        .kategori-pengumuman { background: var(--primary-500, #6366f1); color: white; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        .kategori-berita { background: var(--success-500, #22c55e); color: white; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        .kategori-urgent { background: var(--danger-500, #ef4444); color: white; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        .kategori-info { background: var(--accent-500, #06b6d4); color: #000; border-radius: 4px; padding: 2px 6px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        @keyframes marqueeScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        @media (max-width: 991.98px) {
            .marquee-container {
                left: 0 !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    @if($user)
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            @if(!empty($instansi->logo))
                <img src="{{ asset($instansi->logo) }}" alt="Logo">
            @else
                <i class="fas fa-building fa-3x text-white mb-2"></i>
            @endif
            <h5>{{ $instansi->nama_instansi ?? 'Sultan Banten' }}</h5>
        </div>
        <div class="sidebar-menu">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            
            @if($user->hasAccess('penghuni') || $user->hasAccess('overstaying') || $user->hasAccess('integrasi') || $user->hasAccess('bebas_harian'))
            <div class="menu-parent {{ request()->routeIs('penghuni.*') || request()->routeIs('overstaying.*') || request()->routeIs('integrasi.*') || request()->routeIs('bebas-harian.*') || request()->routeIs('kroscek.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuInputData">
                <i class="fas fa-edit"></i>
                <span>Input Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('penghuni.*') || request()->routeIs('overstaying.*') || request()->routeIs('integrasi.*') || request()->routeIs('bebas-harian.*') || request()->routeIs('kroscek.*') ? 'show' : '' }}" id="menuInputData">
                <div class="sidebar-submenu">
                    @if($user->hasAccess('penghuni'))
                    <a href="{{ route('penghuni.index') }}" class="{{ request()->routeIs('penghuni.*') ? 'active' : '' }}"><i class="fas fa-users"></i><span>Data Penghuni</span></a>
                    @endif
                    @if($user->hasAccess('overstaying'))
                    <a href="{{ route('overstaying.index') }}" class="{{ request()->routeIs('overstaying.*') ? 'active' : '' }}"><i class="fas fa-clock"></i><span>Data Overstaying</span></a>
                    @endif
                    @if($user->hasAccess('integrasi'))
                    <a href="{{ route('integrasi.index') }}" class="{{ request()->routeIs('integrasi.*') ? 'active' : '' }}"><i class="fas fa-exchange-alt"></i><span>Data Integrasi</span></a>
                    @endif
                    @if($user->hasAccess('bebas_harian'))
                    <a href="{{ route('bebas-harian.index') }}" class="{{ request()->routeIs('bebas-harian.*') ? 'active' : '' }}"><i class="fas fa-door-open"></i><span>Bebas Harian</span></a>
                    @endif
                    <a href="{{ route('kroscek.index') }}" class="{{ request()->routeIs('kroscek.*') ? 'active' : '' }}">
                        <i class="fas fa-check-double"></i><span>Kroscek Data</span>
                    </a>
                </div>
            </div>
            @endif

            @if($user->hasAccess('laporan'))
            <div class="menu-parent {{ request()->routeIs('laporan.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuLaporan">
                <i class="fas fa-print"></i>
                <span>Cetak Laporan</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('laporan.*') ? 'show' : '' }}" id="menuLaporan">
                <div class="sidebar-submenu">
                    <a href="{{ route('laporan.index', ['type' => 'penghuni']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'penghuni' ? 'active' : '' }}"><i class="fas fa-users"></i><span>Lap. Penghuni</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'overstaying']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'overstaying' ? 'active' : '' }}"><i class="fas fa-clock"></i><span>Lap. Overstaying</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'integrasi']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'integrasi' ? 'active' : '' }}"><i class="fas fa-exchange-alt"></i><span>Lap. Integrasi</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'bebas_harian']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'bebas_harian' ? 'active' : '' }}"><i class="fas fa-door-open"></i><span>Lap. Bebas Harian</span></a>
                    <a href="{{ route('laporan.index', ['type' => 'rekapitulasi']) }}" class="{{ request()->routeIs('laporan.*') && request('type') == 'rekapitulasi' ? 'active' : '' }}"><i class="fas fa-chart-bar"></i><span>Rekapitulasi</span></a>
                </div>
            </div>
            @endif

            <div class="menu-parent {{ request()->routeIs('upt.*') || request()->routeIs('users.*') || request()->routeIs('instansi.*') || request()->routeIs('pengumuman.*') || request()->routeIs('activity-log.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuMaster">
                <i class="fas fa-database"></i>
                <span>Master Data</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('upt.*') || request()->routeIs('users.*') || request()->routeIs('instansi.*') || request()->routeIs('pengumuman.*') || request()->routeIs('activity-log.*') ? 'show' : '' }}" id="menuMaster">
                <div class="sidebar-submenu">
                    @if($user->hasAccess('upt'))
                    <a href="{{ route('upt.index') }}" class="{{ request()->routeIs('upt.*') ? 'active' : '' }}"><i class="fas fa-building"></i><span>Data UPT</span></a>
                    @endif
                    @if($user->hasAccess('user'))
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}"><i class="fas fa-user-cog"></i><span>Manajemen User</span></a>
                    @endif
                    @if($user->hasAccess('instansi'))
                    <a href="{{ route('instansi.index') }}" class="{{ request()->routeIs('instansi.*') ? 'active' : '' }}"><i class="fas fa-cog"></i><span>Pengaturan Instansi</span></a>
                    @endif
                    @if($user->hasAccess('pengumuman'))
                    <a href="{{ route('pengumuman.index') }}" class="{{ request()->routeIs('pengumuman.*') ? 'active' : '' }}"><i class="fas fa-bullhorn"></i><span>Pengumuman</span></a>
                    @endif
                    @if($user->hasAccess('activity_log'))
                    <a href="{{ route('activity-log.index') }}" class="{{ request()->routeIs('activity-log.*') ? 'active' : '' }}"><i class="fas fa-history"></i><span>Activity Log</span></a>
                    @endif
                </div>
            </div>
            
            @if($user->hasAccess('wa_settings') || $user->hasAccess('wa_schedule'))
            <div class="menu-parent {{ request()->routeIs('wa.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuWA">
                <i class="fab fa-whatsapp"></i>
                <span>Fitur WhatsApp</span>
                <i class="fas fa-chevron-down arrow"></i>
            </div>
            <div class="collapse {{ request()->routeIs('wa.*') ? 'show' : '' }}" id="menuWA">
                <div class="sidebar-submenu">
                    @if($user->hasAccess('wa_settings'))
                    <a href="{{ route('wa.settings') }}" class="{{ request()->routeIs('wa.settings*') ? 'active' : '' }}"><i class="fas fa-cog"></i><span>Pengaturan WA</span></a>
                    @endif
                    @if($user->hasAccess('wa_schedule'))
                    <a href="{{ route('wa.schedule') }}" class="{{ request()->routeIs('wa.schedule*') ? 'active' : '' }}"><i class="fas fa-clock"></i><span>Jadwal Laporan</span></a>
                    <a href="{{ route('wa.pimpinan') }}" class="{{ request()->routeIs('wa.pimpinan*') ? 'active' : '' }}"><i class="fas fa-user-tie"></i><span>Daftar Pimpinan</span></a>
                    @endif
                </div>
            </div>
            @endif

            <a href="{{ route('profile.index') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="fas fa-user-circle"></i><span>Profil Saya</span></a>
            <form action="{{ route('logout') }}" method="POST" id="logoutForm" class="d-inline">
                @csrf
                <a href="javascript:void(0)" onclick="document.getElementById('logoutForm').submit();">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </form>
        </div>
    </nav>
    @endif

    <!-- Main Content -->
    <div class="main-content {{ !$user ? 'no-sidebar' : '' }}">
        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                @if($user)
                <button class="toggle-btn d-lg-none me-2" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <h4 class="mb-0">@yield('page_title', 'Dashboard')</h4>
                @else
                <!-- Public Header with Logo -->
                @if(!empty($instansi->logo))
                <img src="{{ asset($instansi->logo) }}" alt="Logo" class="public-logo me-3">
                @endif
                <div>
                    <h5 class="mb-0 fw-bold public-title">{{ $instansi->nama_instansi ?? 'Sultan Banten' }}</h5>
                    <small class="public-subtitle"><i class="fas fa-chart-line me-1"></i>Dashboard Sistem Update Laporan Terpadu Harian</small>
                </div>
                @endif
            </div>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <!-- Dark Mode Switch -->
                <div class="dark-mode-switch">
                    <span class="switch-label d-none d-md-inline"><i class="fas fa-sun text-warning"></i></span>
                    <label class="switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="slider"></span>
                    </label>
                    <span class="switch-label d-none d-md-inline"><i class="fas fa-moon text-primary"></i></span>
                </div>

                @if($user)
                <div class="me-2">{!! $user->getAvatarHtml(32) !!}</div>
                <span class="d-none d-sm-inline me-2 fw-semibold">{{ $user->nama_lengkap ?? $user->username }}</span>
                <span class="badge bg-primary px-2.5 py-1.5">{{ ucfirst($user->role) }}</span>
                @else
                <a href="{{ route('login') }}" class="btn btn-login-public">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')

        <!-- Footer -->
        <footer class="text-center py-3 mt-auto" style="background: transparent; color: var(--text-secondary); font-size: 12px;">
            © Ditjenpas Banten {{ date('Y') }}
        </footer>
    </div>

    @php
        $marqueeAnnouncements = \App\Models\Pengumuman::where('aktif', 1)
            ->where('tampilkan_marquee', 1)
            ->where(function($q) {
                $today = date('Y-m-d');
                $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $today);
            })
            ->where(function($q) {
                $today = date('Y-m-d');
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $today);
            })
            ->where(function($q) use ($user) {
                $uptId = $user ? $user->upt_id : null;
                $q->where('tipe', 'Global');
                if ($uptId) {
                    $q->orWhere('upt_id', $uptId);
                }
            })
            ->orderByDesc('created_at')
            ->get();
        $globalRunningText = $instansi->running_text ?? '';
    @endphp

    @if($marqueeAnnouncements->count() > 0 || !empty($globalRunningText))
    <div class="marquee-container {{ !$user ? 'full-width' : '' }}">
        @if($marqueeAnnouncements->count() > 0)
        <div class="marquee-label">
            <i class="fas fa-bullhorn me-2"></i> INFO
        </div>
        @endif
        <div class="marquee-content">
            @if(!empty($globalRunningText))
            <div class="marquee-item global-greeting">
                <i class="fas fa-star me-2 text-warning"></i>
                <strong>{{ $globalRunningText }}</strong>
                <i class="fas fa-star ms-2 text-warning"></i>
            </div>
            @endif

            @foreach ($marqueeAnnouncements as $m)
            <div class="marquee-item">
                <span class="kategori kategori-{{ strtolower($m->kategori) }}">{{ $m->kategori }}</span>
                <strong>{{ $m->judul }}:</strong>
                <span class="ms-2">{{ strip_tags($m->isi) }}</span>
            </div>
            @endforeach

            @if(!empty($globalRunningText))
            <div class="marquee-item global-greeting">
                <i class="fas fa-star me-2 text-warning"></i>
                <strong>{{ $globalRunningText }}</strong>
                <i class="fas fa-star ms-2 text-warning"></i>
            </div>
            @endif

            @foreach ($marqueeAnnouncements as $m)
            <div class="marquee-item">
                <span class="kategori kategori-{{ strtolower($m->kategori) }}">{{ $m->kategori }}</span>
                <strong>{{ $m->judul }}:</strong>
                <span class="ms-2">{{ strip_tags($m->isi) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('toggleSidebar');
        
        function toggleSidebar() {
            sidebar?.classList.toggle('show');
            overlay?.classList.toggle('show');
        }
        
        toggleBtn?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', toggleSidebar);

        // Dark Mode Logic
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            if (darkModeToggle) darkModeToggle.checked = true;
        }

        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                }
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
