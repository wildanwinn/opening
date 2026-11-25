@extends('layouts.mobile')
@section('judul', 'Daftar Tugas')

@section('konten')
@include('tugas._header')

<div class="stats">
    <div class="stat"><b>{{ $stat['belum'] }}</b><span>Belum mulai</span></div>
    <div class="stat hot"><b>{{ $stat['jalan'] }}</b><span>Dikerjakan</span></div>
    <div class="stat"><b>{{ $stat['selesai'] }}</b><span>Selesai hari ini</span></div>
</div>

<div class="isi">
    @include('tugas._alert')

    <form class="cari" method="GET" action="{{ route('tugas.index') }}">
        <label for="q" class="sr">Cari tugas</label>
        <input id="q" name="q" value="{{ $q }}" placeholder="Cari ODP, nomor WO, atau pelanggan…" autocomplete="off">
        <button type="submit" aria-label="Cari"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></button>
    </form>

    @if($aktif)
    @php $cur = $aktif->tahap === 'jalan' ? 2 : 1; @endphp
    <div class="kartu muncul" style="--i:0">
        <div class="baris-judul"><h3>Sedang dikerjakan</h3><span class="muted">{{ $aktif->odp_name ?? $aktif->wonum }}</span></div>
        <div class="alur">
            <div class="garis"></div><div class="isi-garis" style="width:{{ $cur * 25 }}%"></div>
            <div class="grid">
                @foreach(['Mulai','Pre','Jalan','Selesai'] as $i => $n)
                    <div>
                        <div class="n {{ $i < $cur ? 'done' : ($i === $cur ? 'now' : '') }}">
                            @if($i < $cur)<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            @else<span style="font-weight:700">{{ $i + 1 }}</span>@endif
                        </div>
                        <div class="l {{ $i === $cur ? 'now' : '' }}">{{ $n }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <a class="btn kecil-btn" href="{{ $aktif->tahap === 'pre' ? route('tugas.pre', $aktif) : route('tugas.selesai', $aktif) }}">Lanjutkan tugas ini</a>
    </div>
    @endif

    <div class="baris-judul" style="margin-top:22px"><h3 style="font-size:16px">Daftar Tugas</h3><span class="muted">{{ $tugas->count() }} tugas</span></div>

    @forelse($tugas as $i => $t)
        <div class="tugas muncul {{ $aktif && $aktif->id === $t->id ? 'aktif' : '' }}" style="--i:{{ $i + 1 }}">
            <div class="no">{{ $i + 1 }}</div>
            <div class="teks">
                <div class="odp">{{ $t->odp_name ?? '-' }}<span class="chip {{ $t->tahap }}">{{ $t->tahap ? strtoupper($t->tahap) : 'BELUM' }}</span></div>
                <div class="muted">{{ $t->wonum }} · STO {{ $t->sto }}@if($t->customer_name) · {{ \Illuminate\Support\Str::limit($t->customer_name, 18) }}@endif</div>
            </div>
            <div class="aksi">
                <a class="btn-k" href="{{ route('tugas.lokasi', $t) }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Lokasi</a>
                @if($t->tahap === null)
                    <form method="POST" action="{{ route('tugas.mulai', $t) }}">@csrf<button class="btn-k isi" style="width:100%">Mulai</button></form>
                @else
                    <a class="btn-k isi" href="{{ $t->tahap === 'pre' ? route('tugas.pre', $t) : route('tugas.selesai', $t) }}">Lanjut</a>
                @endif
            </div>
        </div>
    @empty
        <div class="kartu kosong-list">
            <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 3h6v4H9zM9 14l2 2 4-4"/></svg>
            <div><b>{{ $q !== '' ? 'Tidak ada hasil' : 'Belum ada tugas' }}</b></div>
            <div class="muted">{{ $q !== '' ? 'Coba kata kunci lain.' : 'Tugas dari admin akan muncul di sini.' }}</div>
        </div>
    @endforelse
</div>
@endsection
