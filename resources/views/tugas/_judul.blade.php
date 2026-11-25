<div class="atas">
    <a href="{{ $kembali ?? route('tugas.index') }}" class="ib solid" aria-label="Kembali">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
    </a>
    <div style="flex:1;min-width:0">
        <h1>{{ $judul }}</h1>
        <div class="muted">{{ $m->wonum }} · {{ $m->odp_name ?? 'ODP -' }}</div>
    </div>
    @include('tugas._ikon')
</div>
@php
    $cur  = match ($m->tahap) { 'pre' => 1, 'jalan' => 2, 'selesai' => 4, default => 0 };
    $nama = ['Mulai', 'Pre', 'Jalan', 'Selesai'];
@endphp
<div class="mini">
    @foreach($nama as $i => $n)
        <div><i class="{{ $i <= $cur ? 'on' : '' }}"></i><span class="{{ $i === $cur ? 'now' : '' }}">{{ $n }}{{ $i < $cur ? ' ✓' : '' }}</span></div>
    @endforeach
</div>
@include('tugas._alert')
