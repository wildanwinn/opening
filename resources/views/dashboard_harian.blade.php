@extends('layouts.app')
@section('judul', 'Dashboard Harian')

@push('skrip')
<style>
    .dh-wrap{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px}
    @media(max-width:1100px){.dh-wrap{grid-template-columns:1fr}}
    .dh-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
    .dh-live{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:7px 14px;font-size:.78rem;font-weight:700;background:rgba(46,158,107,.15);color:#2E9E6B}
    .dh-live i{width:9px;height:9px;border-radius:50%;background:#2E9E6B;animation:dhdenyut 1.4s infinite}
    .dh-live.off{background:rgba(214,40,57,.15);color:#D62839}.dh-live.off i{background:#D62839;animation:none}
    @keyframes dhdenyut{0%{box-shadow:0 0 0 0 rgba(46,158,107,.55)}100%{box-shadow:0 0 0 9px rgba(46,158,107,0)}}
    .dh-jam{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:6px 14px;font-size:1.3rem;font-weight:600;font-variant-numeric:tabular-nums}
    .dh-ring{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
    .dh-ring div{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:8px 16px;display:flex;align-items:baseline;gap:8px}
    .dh-ring b{font-size:1.4rem}.dh-ring span{font-size:.78rem;opacity:.7}
    .dh-kolom{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    @media(max-width:900px){.dh-kolom{grid-template-columns:repeat(2,minmax(0,1fr))}}
    .dh-col{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:12px;min-height:260px}
    .dh-col h6{font-size:.75rem;letter-spacing:.8px;font-weight:700;display:flex;justify-content:space-between;margin-bottom:10px}
    .dh-card{border:1px solid var(--border);border-left:4px solid var(--c);border-radius:10px;padding:9px 11px;margin-bottom:8px;font-size:.82rem;background:rgba(127,127,127,.06)}
    .dh-card .n{font-weight:600}.dh-card .m{font-size:.72rem;opacity:.7}
    .dh-card .t{font-variant-numeric:tabular-nums;font-weight:600;color:var(--c)}
    .dh-flash{animation:dhflash 1.6s}
    @keyframes dhflash{0%{background:rgba(242,184,75,.45)}100%{background:rgba(127,127,127,.06)}}
    .dh-log{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:12px;max-height:640px;overflow:auto}
    .dh-log h6{font-size:.75rem;letter-spacing:.8px;font-weight:700;margin-bottom:10px}
    .dh-item{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:.8rem}
    .dh-item:last-child{border:0}
    .dh-item .j{font-variant-numeric:tabular-nums;opacity:.7;min-width:62px;font-size:.74rem}
    .dh-chip{display:inline-block;border-radius:6px;padding:1px 7px;font-size:.65rem;font-weight:700;color:#fff;background:var(--c)}
    .dh-kosong{opacity:.55;font-size:.78rem;text-align:center;padding:14px 0}
</style>
<script>
(function(){
    const URL_DATA = @json(route('dashboard.harian.data'));
    const WARNA = {IDLE:'#2E9E6B',STARTWORK:'#3A66BE',CONTWORK:'#C28A1E',COMPWORK:'#D62839'};
    const NAMA  = {IDLE:'IDLE',STARTWORK:'STARTWORK',CONTWORK:'CONTWORK',COMPWORK:'COMPWORK'};
    let selisih = 0, kolom = {}, log = [], lastKey = null, prevStatus = {}, adaData = false;
    const $ = id => document.getElementById(id);
    const pad = n => (n<10?'0':'')+n;
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const nowSrv = () => Math.floor(Date.now()/1000) + selisih;
    const hms = d => pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds());
    const dur = s => { s=Math.max(0,s); const h=Math.floor(s/3600),m=Math.floor(s/60)%60; return h>0 ? h+' j '+m+' m' : pad(m)+':'+pad(s%60); };
    const ago = s => { s=Math.max(0,s); return s<60 ? s+' dtk lalu' : s<3600 ? Math.floor(s/60)+' mnt lalu' : s<86400 ? Math.floor(s/3600)+' jam lalu' : Math.floor(s/86400)+' hari lalu'; };

    function gambar(){
        const n = nowSrv();
        Object.keys(WARNA).forEach(st => {
            const arr = kolom[st] || [];
            $('jml-'+st).textContent = arr.length;
            $('kol-'+st).innerHTML = arr.length ? arr.map(t => {
                const flash = prevStatus[t.id] !== undefined && prevStatus[t.id] !== st ? ' dh-flash' : '';
                return '<div class="dh-card'+flash+'" style="--c:'+WARNA[st]+'"><div class="n">'+esc(t.nama)+'</div>'
                    + '<div class="m">STO '+esc(t.sto||'-')+(t.wonum?' · '+esc(t.wonum):'')+'</div>'
                    + (t.ts ? '<div class="m"><span class="t" data-ts="'+t.ts+'">'+(st==='IDLE'?'':dur(n-t.ts))+'</span> · '+ago(n-t.ts)+'</div>' : '<div class="m">belum ada order</div>')
                    + '</div>';
            }).join('') : '<div class="dh-kosong">—</div>';
        });
        $('log').innerHTML = log.length ? log.map(l =>
            '<div class="dh-item"><div class="j">'+hms(new Date(l.ts*1000))+'</div><div><span class="dh-chip" style="--c:'+(WARNA[l.status]||'#888')+'">'+esc(l.status)+'</span> <b>'+esc(l.nama)+'</b><div class="m" style="opacity:.7">'+esc(l.wonum)+' · '+esc(l.sto||'-')+(l.odp?' · '+esc(l.odp):'')+'</div></div></div>'
        ).join('') : '<div class="dh-kosong">Belum ada aktivitas teknisi.</div>';
    }

    async function ambil(){
        try{
            const r = await fetch(URL_DATA, {headers:{'Accept':'application/json'}, credentials:'same-origin'});
            if(!r.ok) throw 0;
            const d = await r.json();
            selisih = d.now - Math.floor(Date.now()/1000);
            const baru = {};
            Object.entries(d.kolom).forEach(([st,arr]) => arr.forEach(t => baru[t.id] = st));
            kolom = d.kolom; log = d.log;
            const key = log.length ? log[0].key : null;
            gambar();
            prevStatus = baru;
            lastKey = key;
            $('r-teknisi').textContent = d.ringkas.teknisi;
            $('r-bekerja').textContent = d.ringkas.bekerja;
            $('r-idle').textContent = d.ringkas.idle;
            $('r-selesai').textContent = d.ringkas.selesai;
            $('live').className = 'dh-live'; $('live').lastChild.textContent = 'LIVE';
        }catch(e){
            $('live').className = 'dh-live off'; $('live').lastChild.textContent = 'TERPUTUS';
        }
    }

    function detik(){
        $('jam').textContent = hms(new Date(nowSrv()*1000));
        const n = nowSrv();
        document.querySelectorAll('.dh-card .t[data-ts]').forEach(el => {
            const st = el.closest('.dh-col').dataset.st;
            if(st !== 'IDLE') el.textContent = dur(n - el.dataset.ts);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        ambil(); setInterval(ambil, 3000); setInterval(detik, 1000); detik();
    });
})();
</script>
@endpush

@section('konten')
<div class="dh-head">
    <div>
        <div class="small opacity-75">{{ now()->translatedFormat('l, d F Y') }}</div>
        <h4 class="mb-0 fw-semibold">Dashboard Harian</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span id="live" class="dh-live"><i></i>LIVE</span>
        <span id="jam" class="dh-jam">--:--:--</span>
    </div>
</div>

<div class="dh-ring">
    <div><b id="r-teknisi">0</b><span>teknisi</span></div>
    <div><b id="r-bekerja">0</b><span>bekerja</span></div>
    <div><b id="r-idle">0</b><span>idle</span></div>
    <div><b id="r-selesai">0</b><span>selesai hari ini</span></div>
</div>

<div class="dh-wrap">
    <div class="dh-kolom">
        @foreach(['IDLE','STARTWORK','CONTWORK','COMPWORK'] as $st)
        <div class="dh-col" data-st="{{ $st }}">
            <h6>{{ $st }} <span id="jml-{{ $st }}">0</span></h6>
            <div id="kol-{{ $st }}"></div>
        </div>
        @endforeach
    </div>
    <div class="dh-log">
        <h6>AKTIVITAS LANGSUNG</h6>
        <div id="log"></div>
        <div class="dh-kosong mt-2">Diperbarui otomatis tiap 3 detik</div>
    </div>
</div>
@endsection