@php $inisial = collect(preg_split('/\s+/', trim(Auth::user()->nama)))->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->take(2)->implode(''); @endphp
<div class="hero">
    <div class="hero-top">
        <span class="avatar" title="{{ Auth::user()->nama }}">{{ $inisial }}</span>
        <div style="flex:1;min-width:0">
            <div class="kecil">{{ now()->translatedFormat('l, d F Y') }}</div>
            <div class="halo">Halo, {{ \Illuminate\Support\Str::of(Auth::user()->nama)->before(' ')->title() }}</div>
        </div>
        @include('tugas._ikon')
        <details class="dd">
            <summary class="ib" aria-label="Buka menu">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </summary>
            <div class="menu">
                <a href="{{ route('tugas.index') }}">Daftar Tugas</a>
                @unless(Auth::user()->isTeknisi())<a href="{{ route('dashboard') }}">Kembali ke Dashboard</a>@endunless
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
            </div>
        </details>
    </div>
    <div class="jam-besar" id="jam">{{ now()->format('H:i:s') }}</div>
    <div class="kecil">Waktu setempat</div>
</div>
<script>
(function(){var e=document.getElementById('jam');if(!e)return;function p(n){return(n<10?'0':'')+n}
setInterval(function(){var d=new Date();e.textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds())},1000)})();
</script>
