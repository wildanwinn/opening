<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#D62839">
    <title>@yield('judul', 'Tugas') — OPENING</title>
    <script>
        try { document.documentElement.setAttribute('data-tema', localStorage.getItem('tema') || 'light'); }
        catch (e) { document.documentElement.setAttribute('data-tema', 'light'); }
    </script>
    <link rel="stylesheet" href="{{ asset('css/mobile-teknisi.css') }}?v={{ @filemtime(public_path('css/mobile-teknisi.css')) }}">
</head>
<body>
<div class="hp">
    @yield('konten')
</div>
<script>
function gantiTema(){
    var h=document.documentElement,b=h.getAttribute('data-tema')==='dark'?'light':'dark';
    h.setAttribute('data-tema',b);try{localStorage.setItem('tema',b)}catch(e){}
}
</script>
</body>
</html>
