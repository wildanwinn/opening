@extends('layouts.app')
@section('judul', 'Login')

@push('skrip')
<style>
    /* ===== Splash loading sebelum login ===== */
    #splash {
        position: fixed; inset: 0; z-index: 3000; overflow: hidden;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;
        background: radial-gradient(circle at 50% 40%, #ffffff 0%, #fff4f4 60%, #ffe3e5 100%);
        transition: opacity .55s ease, visibility .55s ease;
    }
    #splash.selesai { opacity: 0; visibility: hidden; }
    #splash .splash-maskot {
        width: min(760px, 120vw); max-width: none; height: auto; margin: -70px 0 -50px; mix-blend-mode: multiply;
        animation: splash-masuk .7s cubic-bezier(.2,1.3,.4,1) both, splash-melayang 2.2s ease-in-out .7s infinite;
    }
    #splash .splash-nama { font-family: 'Fraunces', serif; font-weight: 700; font-size: 1.6rem; letter-spacing: .5px; color: #C8222F; margin: 0; }
    #splash .splash-bar { width: min(260px, 60vw); height: 7px; border-radius: 99px; background: rgba(200,34,47,.15); overflow: hidden; }
    #splash .splash-bar span { display: block; height: 100%; width: 0; border-radius: 99px; background: linear-gradient(90deg, #E34A5C, #C8222F); animation: splash-isi var(--splash-durasi, 2.8s) ease-in-out forwards; }
    #splash .splash-teks { font-size: .8rem; color: #8b5a5f; margin: 0; }
    @keyframes splash-masuk { from { opacity: 0; transform: translateY(24px) scale(.85); } to { opacity: 1; transform: none; } }
    @keyframes splash-melayang { 0%,100% { transform: translateY(0) rotate(-1deg); } 50% { transform: translateY(-14px) rotate(1deg); } }
    @keyframes splash-isi { 0% { width: 0; } 60% { width: 72%; } 100% { width: 100%; } }
    @media (prefers-reduced-motion: reduce) { #splash .splash-maskot { animation: none; } }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body {
    background:
        linear-gradient(
            rgba(45, 0, 5, 0.35),
            rgba(45, 0, 5, 0.35)
        ),
        url("{{ asset('images/bglogin.png') }}") center center / cover no-repeat fixed !important;
}
    .kartu-login {
        background: rgba(255,255,255,.94);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.5);
        border-radius: 20px;
        box-shadow: 0 25px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(220,53,69,.08);
    }

    .lingkaran-ikon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #dc3545, #a30d1f);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        box-shadow: 0 8px 20px rgba(220,53,69,.4);
    }
    .lingkaran-ikon i {
        color: #fff;
        font-size: 1.75rem;
    }

    .kartu-login h4 {
        background: linear-gradient(135deg, #dc3545, #8b0e1f);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: .5px;
        font-weight: 700;
    }

    .input-berikon {
        position: relative;
    }
    .input-berikon i.ikon-kiri {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        transition: color .15s ease;
        pointer-events: none;
    }
    .input-berikon input {
        padding-left: 42px;
        border-radius: 10px;
        border: 1.5px solid #e2e2e2;
        padding-top: .65rem;
        padding-bottom: .65rem;
    }
    .input-berikon input:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 .2rem rgba(220,53,69,.15);
    }
    .input-berikon input:focus ~ i.ikon-kiri {
        color: #dc3545;
    }

    .toggle-password {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
    }
    .toggle-password:hover { color: #dc3545; }

    .btn-login-gradient {
        background: linear-gradient(135deg, #e64353, #c41c2f);
        border: none;
        border-radius: 10px;
        padding: .7rem;
        font-weight: 600;
        letter-spacing: .4px;
        color: #fff;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn-login-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(220,53,69,.45);
        color: #fff;
    }
    .btn-login-gradient:active {
        transform: translateY(0);
    }

    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }
</style>
@endpush

@section('konten')
<div id="splash" role="status" aria-live="polite">
    <img src="{{ asset('images/maskot-telkom.png') }}" alt="" class="splash-maskot">
    <p class="splash-nama">OPENING</p>
    <div class="splash-bar"><span></span></div>
    <p class="splash-teks">Memuat…</p>
</div>
<script>
    // Splash hanya tampil sekali per sesi browser (tidak muncul lagi saat login gagal / halaman dimuat ulang).
    (function () {
        var el = document.getElementById('splash');
        try { if (sessionStorage.getItem('splash_dilihat')) { el.remove(); } } catch (e) {}
    })();
</script>
<div class="row justify-content-center align-items-center" style="min-height: 90vh;">
    <div class="col-11 col-sm-8 col-md-5 col-lg-4">
        <div class="card kartu-login p-2">
            <div class="card-body p-4">

                <div class="text-center mb-3">
                    <img src="{{ asset('images/logo-opera.png') }}" alt="Logo OPERA" style="width: 84px; height: 84px; object-fit: contain;">
                </div>

                <div class="text-center mb-4">
                    <h4 class="mb-1">OPENING</h4>
                    <p class="text-muted small mb-0">Branch Pekalongan</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">NIK</label>
                        <div class="input-berikon">
                            <i class="bi bi-person-fill ikon-kiri"></i>
                            <input type="text" name="nik" value="{{ old('nik') }}"
                                   class="form-control" autofocus required placeholder="Masukkan NIK">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-berikon">
                            <i class="bi bi-lock-fill ikon-kiri"></i>
                            <input type="password" name="password" id="inputPassword"
                                   class="form-control" required placeholder="Masukkan password" style="padding-right: 42px;">
                            <button type="button" class="toggle-password" onclick="toggleLihatPassword()">
                                <i class="bi bi-eye-fill" id="ikonMata"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label small text-muted">Ingat saya</label>
                    </div>

                    <button class="btn btn-login-gradient w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleLihatPassword() {
        const input = document.getElementById('inputPassword');
        const ikon = document.getElementById('ikonMata');
        if (input.type === 'password') {
            input.type = 'text';
            ikon.classList.remove('bi-eye-fill');
            ikon.classList.add('bi-eye-slash-fill');
        } else {
            input.type = 'password';
            ikon.classList.remove('bi-eye-slash-fill');
            ikon.classList.add('bi-eye-fill');
        }
    }
</script>
<script>
    (function () {
        var el = document.getElementById('splash');
        if (!el) return;
        var durasi = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 600 : 2800;
        el.style.setProperty('--splash-durasi', durasi + 'ms');
        setTimeout(function () {
            el.classList.add('selesai');
            try { sessionStorage.setItem('splash_dilihat', '1'); } catch (e) {}
            setTimeout(function () { el.remove(); }, 700);
        }, durasi + 200);
    })();
</script>
@endsection