@extends('layouts.mobile')
@section('judul', 'Lokasi ODP')

@section('konten')
<div class="isi">
@include('tugas._judul', ['judul' => 'Jalan · Lokasi ODP'])

@php $ada = $odp && $odp->latitude !== null && $odp->longitude !== null; @endphp
<div class="peta muncul" style="--i:0">
    @if($ada)
        @php
            $lat = (float) $odp->latitude; $lng = (float) $odp->longitude; $d = 0.004;
            $bbox = ($lng - $d) . ',' . ($lat - $d) . ',' . ($lng + $d) . ',' . ($lat + $d);
        @endphp
        <iframe title="Peta lokasi {{ $m->odp_name }}" loading="lazy"
            src="https://www.openstreetmap.org/export/embed.html?bbox={{ $bbox }}&layer=mapnik&marker={{ $lat }},{{ $lng }}"></iframe>
    @else
        <div class="kosong">Koordinat ODP ini belum ada di Data ODP.<br>Minta admin mengunggah Excel ODP.</div>
    @endif
</div>

<div class="kartu muncul" style="--i:1">
    <div style="font-size:18px;font-weight:600">{{ $m->odp_name ?? '-' }}</div>
    <div class="muted" style="margin-top:2px">STO {{ $m->sto }} · {{ $m->wonum }}</div>
    @if($ada)
        <div class="koor"><div>Latitude<b>{{ $odp->latitude }}</b></div><div>Longitude<b>{{ $odp->longitude }}</b></div></div>
    @endif
</div>

<div class="bawah" style="display:flex;flex-direction:column;gap:10px">
    @if($ada)
        <a class="btn lain" href="https://www.google.com/maps/dir/?api=1&destination={{ $odp->latitude }},{{ $odp->longitude }}" target="_blank" rel="noopener">Buka di Google Maps</a>
    @endif
    @if($m->tahap === 'jalan')
        <a class="btn" href="{{ route('tugas.selesai', $m) }}">Saya sudah tiba · Lanjut Selesai</a>
    @elseif($m->tahap === 'pre')
        <a class="btn" href="{{ route('tugas.pre', $m) }}">Lanjut ke Pre (material)</a>
    @endif
</div>
</div>
@endsection
