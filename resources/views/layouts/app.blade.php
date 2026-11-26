<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Monitoring') — OPENING</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script>
        (function () {
            var tema = localStorage.getItem('tema') || 'light';
            document.documentElement.setAttribute('data-tema', tema);
        })();
    </script>

    <style>
        :root {
            --bg-app: #151110;
            --bg-panel: #1d1714;
            --bg-card: #201a17;
            --bg-hover: #28201c;
            --border: #382b24;
            --border-soft: #30251f;
            --teks: #f7f2ee;
            --teks-mute: #a99b93;
            --badge-bg: #2a211d;
            --primary: #ff6473;
            --success: #2ed391;
            --warning: #f5a623;
            --danger: #ff5d6c;
        }

        html[data-tema="light"] {
            --bg-app: #f6f3f0;
            --bg-panel: #ffffff;
            --bg-card: #ffffff;
            --bg-hover: #f5efea;
            --border: #e6ddd6;
            --border-soft: #eee7e2;
            --teks: #2c2420;
            --teks-mute: #8b7d74;
            --badge-bg: #f7f1ed;
        }

        * { box-sizing: border-box; }

        body.bg-app {
            min-height: 100vh;
            margin: 0;
            background: var(--bg-app);
            color: var(--teks);
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
            background: var(--bg-app);
        }

        /* ================= SIDEBAR MENYATU BERSIH TANPA GARIS ================= */
        .sidebar-gelap {
            width: 280px;
            min-width: 280px;
            height: 100vh;
            height: 100dvh;
            margin: 0;
            padding: 1.5rem 1rem;
            background: var(--bg-panel);
            
            border: none !important;
            border-radius: 0 !important; 
            box-shadow: none !important;
            
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            align-self: flex-start;
            max-height: 100vh;
            max-height: 100dvh;
            z-index: 1050;
            transition: width .22s ease, min-width .22s ease;
            overflow: visible;
        }

        .sidebar-gelap.collapsed {
            width: 80px;
            min-width: 80px;
            padding-left: .5rem;
            padding-right: .5rem;
        }

        .sidebar-scroll {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 2px;
            padding-bottom: 1.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        .sidebar-scroll > * { flex-shrink: 0; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        .sidebar-brand-minimal {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 0 .2rem 1.1rem;
            margin-bottom: 1.1rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            flex-shrink: 0;
        }
        .sidebar-brand-minimal .brand-dot {
            width: 10px; height: 10px; background: var(--primary); border-radius: 50%;
        }
        .sidebar-brand-minimal .brand-name {
            font-size: 1.05rem; font-weight: 800; color: var(--teks);
        }
        .sidebar-brand-minimal .brand-sub {
            font-size: .65rem; color: var(--teks-mute); display: block;
        }

        .tombol-collapse {
            position: absolute; 
            top: 24px; 
            right: -12px; 
            width: 28px; 
            height: 28px;
            border-radius: 50%; 
            background: var(--bg-panel); 
            border: 1px solid var(--border);
            color: var(--teks-mute); 
            display: flex; 
            align-items: center; 
            justify-content: center;
            cursor: pointer; 
            z-index: 100; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
        }

        .sidebar-gelap.collapsed .tombol-collapse { transform: rotate(180deg); }

        .grup-label {
            margin: .9rem 0 .45rem .6rem; color: var(--teks-mute);
            font-size: .65rem; font-weight: 700; text-transform: uppercase;
        }

        .nav-gelap { list-style: none; padding: 0; margin: 0; }
        .nav-gelap li { margin-bottom: 3px; }

        .nav-gelap a {
            display: flex; align-items: center; gap: .85rem; min-height: 44px;
            padding: .55rem .8rem; border-radius: 12px;
            color: var(--teks-mute); text-decoration: none; font-size: .86rem; font-weight: 500;
            transition: all .18s ease;
        }

        .nav-gelap a i { width: 20px; text-align: center; }
        .nav-gelap a:hover { background: var(--bg-hover); color: var(--teks); }

        .nav-gelap a.active {
            background: var(--bg-hover); color: var(--primary); font-weight: 700;
        }
        .nav-gelap a.active i { color: var(--primary); }

        .tombol-logout-gelap {
            width: 100%; margin-top: auto; padding: .6rem;
            border: 1px solid var(--border); background: transparent; color: var(--teks-mute);
            border-radius: 12px; font-size: .82rem;
        }

        .sidebar-gelap.collapsed .brand-info,
        .sidebar-gelap.collapsed .grup-label,
        .sidebar-gelap.collapsed .nav-gelap a span,
        .sidebar-gelap.collapsed .tombol-logout-gelap span { display: none; }
        .sidebar-gelap.collapsed .nav-gelap a { justify-content: center; }

        .konten-gelap {
            flex: 1;
            min-width: 0;
            padding: 20px 30px;
            background: var(--bg-app);
        }

        .tombol-tema { width: 42px; height: 42px; border-radius: 13px; background: var(--bg-panel); border: 1px solid var(--border); color: var(--teks); display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .konten-gelap .card, .konten-gelap .stat-card { background: var(--bg-card) !important; border: 1px solid var(--border) !important; border-radius: 18px !important; }

        @media (max-width: 767.98px) {
            .sidebar-gelap {
                width: 285px; min-width: 285px; height: 100vh; height: 100dvh; margin: 0;
                border-radius: 0 20px 20px 0 !important; position: fixed; left: -310px; top: 0; bottom: 0;
            }
            .sidebar-gelap.show { left: 0; }
            .tombol-collapse { display: none; }
            #overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1040; }
            #overlay.show { display: block; }
        }

        body.bg-login {
            min-height: 100vh; background-color: #1a1f2e;
            background-image: linear-gradient(rgba(0,0,0,.35), rgba(0,0,0,.35)), url("{{ asset('images/gege.jpg') }}");
            background-size: cover; background-position: center;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500..700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/merahputih.css') }}?v={{ @filemtime(public_path('css/merahputih.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/desain-hero.css') }}?v={{ @filemtime(public_path('css/desain-hero.css')) }}">
</head>
<body class="@auth bg-app @endauth @guest bg-login @endguest">

@auth
<div class="app-shell">

    <nav id="sidebar" class="sidebar-gelap">
        <div class="tombol-collapse d-none d-md-flex" onclick="toggleCollapse()">
            <i class="bi bi-chevron-left"></i>
        </div>

        <div class="sidebar-scroll">
            <a href="{{ route('dashboard') }}" class="sidebar-brand-minimal">
                <div class="brand-dot"><i class="bi bi-diagram-3-fill"></i></div>
                <div class="brand-info">
                    <span class="brand-name">OPENING</span>
                    <span class="brand-sub">Operasional Provisioning</span>
                </div>
            </a>

            <div class="grup-label">Utama</div>
            <ul class="nav-gelap">
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('dashboard.harian') }}" class="{{ request()->routeIs('dashboard.harian*') ? 'active' : '' }}">
                        <i class="bi bi-activity"></i> <span>Dashboard Harian</span>
                    </a>
                </li>
            </ul>

            <div class="grup-label">Operasional</div>
            <ul class="nav-gelap">
                <li>
                    <a href="{{ route('monitoring.index') }}" class="{{ request()->routeIs('monitoring.index') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-data"></i> <span>Data Monitoring</span>
                    </a>
                </li>
                @if(in_array(auth()->user()->role, ['admin', 'hd'], true))
                    <li>
                        <a href="{{ route('tugas.index') }}" class="{{ request()->routeIs('tugas.*') ? 'active' : '' }}">
                            <i class="bi bi-phone"></i> <span>Mode Teknisi</span>
                        </a>
                    </li>
                @endif
            </ul>

            @if(auth()->user()->isAdmin())
                <div class="grup-label">Master Data</div>
                <ul class="nav-gelap">
                    {{-- MENU DATA ODP SUDAH DIHAPUS DI SINI --}}
                    <li><a href="{{ route('monitoring.upload') }}" class="{{ request()->routeIs('monitoring.upload') ? 'active' : '' }}"><i class="bi bi-cloud-arrow-up"></i> <span>Upload Data</span></a></li>
                </ul>

                <div class="grup-label">Pengaturan</div>
                <ul class="nav-gelap">
                    <li><a href="{{ route('user.index') }}" class="{{ request()->routeIs('user.*', 'teknisi.*', 'session.*') ? 'active' : '' }}"><i class="bi bi-person-badge"></i> <span>Manajemen User</span></a></li>
                </ul>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="tombol-logout-gelap w-100 mt-3">
                    <i class="bi bi-box-arrow-left"></i> <span>Logout</span>
                </button>
            </form>
        </div>
    </nav>

    <div id="overlay" onclick="toggleSidebar()"></div>

    <div class="konten-gelap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-sm d-md-none" style="background:var(--bg-panel); color:var(--teks); border:1px solid var(--border);" onclick="toggleSidebar()">
                <i class="bi bi-list"></i> Menu
            </button>
            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="chip-user" title="NIK {{ auth()->user()->nik }}">
                    <div class="avatar-user">{{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}</div>
                    <div class="d-none d-sm-block">
                        <div class="nama">{{ auth()->user()->nama }}</div>
                        <div class="peran">{{ strtoupper(auth()->user()->role) }}</div>
                    </div>
                </div>
                <button class="tombol-tema" onclick="toggleTema()" title="Ganti tema">
                    <i class="bi" id="ikonTema"></i>
                </button>
            </div>
        </div>

        @if(session('sukses')) <div class="alert alert-success">{{ session('sukses') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        @yield('konten')
    </div>
</div>
@else
    @yield('konten')
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function() {
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    })();

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('overlay').classList.toggle('show');
    }
    
    function toggleCollapse() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebar_collapsed', isCollapsed);
    }

    function updateIkonTema() {
        const tema = document.documentElement.getAttribute('data-tema');
        const ikon = document.getElementById('ikonTema');
        if (ikon) {
            ikon.className = tema === 'light' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
        }
    }

    function toggleTema() {
        const sekarang = document.documentElement.getAttribute('data-tema');
        const baru = sekarang === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-tema', baru);
        localStorage.setItem('tema', baru);
        updateIkonTema();
    }
    updateIkonTema();
</script>
<script>
    document.querySelectorAll('.nav-gelap a').forEach(function (a) {
        var s = a.querySelector('span');
        if (s) a.title = s.textContent.trim();
    });
</script>
@stack('skrip')
</body>
</html>