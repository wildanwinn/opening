@extends('layouts.app')
@section('judul', 'Dashboard')

@push('skrip')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
    .kartu-gelap {
        background: var(--bg-panel);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        height: 100%;
        transition: all .2s ease;
        cursor: pointer;
        position: relative;
    }
    .kartu-gelap:hover {
        transform: translateY(-4px);
        border-color: var(--primary) !important;
        box-shadow: 0 12px 28px rgba(255, 100, 115, .15);
    }
    .kartu-gelap:active { transform: translateY(-1px); }
    .kartu-gelap .label {
        font-size: .72rem; text-transform: uppercase; letter-spacing: .5px;
        color: var(--teks-mute); font-weight: 600;
        display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem;
    }
    .kartu-gelap .label i { font-size: 1rem; }
    .kartu-gelap .angka { font-size: 1.7rem; font-weight: 700; color: var(--teks); }

    .warna-baik   { color: #45c98a; }
    .warna-info   { color: #6d93ff; }
    .warna-amber  { color: #f0a83e; }
    .warna-merah  { color: #ff6b7d; }
    .warna-netral { color: var(--teks-mute); }

    .panel-gelap {
        background: var(--bg-panel); border: 1px solid var(--border);
        border-radius: 12px; height: 100%;
    }
    .panel-gelap .kepala {
        padding: 1rem 1.3rem; border-bottom: 1px solid var(--border);
        font-weight: 600; font-size: .9rem; color: var(--teks);
        display: flex; justify-content: space-between; align-items: center;
    }
    .panel-gelap .kepala .sub { font-size: .74rem; color: var(--teks-mute); font-weight: 400; }

    .tabel-gelap { width: 100%; border-collapse: collapse; color: var(--teks); }
    .tabel-gelap th {
        text-align: left; font-size: .68rem; color: var(--teks-mute); font-weight: 600;
        padding: .6rem 1rem; border-bottom: 1px solid var(--border);
        text-transform: uppercase;
    }
    .tabel-gelap td {
        padding: .6rem 1rem; border-bottom: 1px solid var(--border);
        font-size: .82rem; color: var(--teks);
    }
    .tabel-gelap tr:hover td { background: var(--bg-hover); }

    .badge-status {
        display: inline-block; font-size: .65rem; font-weight: 600;
        padding: .2rem .6rem; border-radius: 20px;
    }
    .badge-COMPWORK { background: rgba(46,211,145,.15); color: #2ed391; }
    .badge-WORKFAIL { background: rgba(255,107,125,.15); color: #ff6b7d; }
    .badge-CANCLWORK { background: rgba(108,117,125,.15); color: #a99b93; }
    .badge-STARTWORK { background: rgba(77,120,224,.15); color: #6d93ff; }
    .badge-CONTWORK { background: rgba(245,166,35,.15); color: #f0a83e; }

    .modal-content {
        background: var(--bg-panel); color: var(--teks);
        border: 1px solid var(--border);
    }
    .modal-header, .modal-footer { border-color: var(--border); }
    html[data-tema="dark"] .btn-close { filter: invert(1); }
</style>
@endpush

@section('konten')
<div class="panel-utama">

@php
    $wib = now('Asia/Jakarta');
    $sapa = $wib->hour < 11 ? 'Selamat pagi' : ($wib->hour < 15 ? 'Selamat siang' : ($wib->hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $periodeAktif = request('periode', 'semua');
    $daftarPeriode = ['semua' => 'Semua data', 'hariini' => 'Hari ini', '7hari' => '7 hari', '30hari' => '30 hari', 'bulanini' => 'Bulan ini'];
    $namaDepan = \Illuminate\Support\Str::title(\Illuminate\Support\Str::words(auth()->user()->nama, 1, ''));
@endphp
<div class="banner-sapa">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="flex-shrink-0 d-none d-sm-block" style="margin-bottom:-.4rem;">@include('partials.maskot-kucing', ['ukuran' => 76, 'kelas' => 'pelan'])</div>
            <div>
            <h2 class="sapa">{{ $sapa }}, {{ $namaDepan }}</h2>
            <p class="ringkas">Ringkasan operasional &mdash; {{ strtolower($daftarPeriode[$periodeAktif] ?? 'semua data') }}{{ ($periodeAktif === '7hari' || $periodeAktif === '30hari') ? ' terakhir' : '' }}.</p>
            </div>
        </div>
        <div class="chip-tanggal">
            <i class="bi bi-calendar3"></i>
            {{ $wib->locale('id')->translatedFormat('l, d F Y') }}
            <span>&middot;</span> {{ $wib->format('H:i') }} WIB
        </div>
    </div>
    <div class="chip-periode">
        @foreach($daftarPeriode as $kode => $label)
            <a href="{{ route('dashboard', ['periode' => $kode]) }}" class="{{ $periodeAktif === $kode ? 'aktif' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="kartu-gelap" onclick="showModal('modalTeknisi')">
            <div class="label warna-netral"><i class="bi bi-people-fill"></i> Total Teknisi</div>
            <div class="angka">{{ $totalTeknisi }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kartu-gelap" onclick="showModal('modalTeknisiHariIni')">
            <div class="label warna-baik"><i class="bi bi-check-circle-fill"></i> Teknisi Hari Ini</div>
            <div class="angka">{{ $teknisiHariIni }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kartu-gelap" onclick="showModal('modalTeknisiTerorder')">
            <div class="label warna-info"><i class="bi bi-clipboard-check-fill"></i> Teknisi Terorder</div>
            <div class="angka">{{ $teknisiTerorder }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kartu-gelap" onclick="showModal('modalTeknisiIdle')">
            <div class="label warna-amber"><i class="bi bi-clock-fill"></i> Teknisi Idle</div>
            <div class="angka">{{ $teknisiIdle }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-4">
        <div class="kartu-gelap kartu-utama" onclick="showModal('modalTotalOrder')">
            <div class="label warna-netral"><i class="bi bi-file-earmark-text-fill"></i> Total Order</div>
            <div class="angka">{{ $totalOrder }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="kartu-gelap kartu-utama" onclick="showModal('modalOV')">
            <div class="label warna-merah"><i class="bi bi-x-circle-fill"></i> OV</div>
            <div class="angka">{{ $ov }}</div>
            <div class="sub-angka">{{ $totalOrder ? round($ov / $totalOrder * 100) : 0 }}% dari total order</div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="kartu-gelap kartu-utama" onclick="showModal('modalComply')">
            <div class="label warna-baik"><i class="bi bi-check-circle-fill"></i> Comply</div>
            <div class="angka">{{ $comply }}</div>
            <div class="sub-angka">{{ $totalOrder ? round($comply / $totalOrder * 100) : 0 }}% dari total order</div>
        </div>
    </div>
</div>

@php
    $dasar = max($totalOrder, 1);
    $alur = [
        ['modalUnassigned', 'Unassigned', 'bi-person-dash-fill',  $unassigned, 'netral'],
        ['modalAssigned',   'Assigned',   'bi-person-check-fill', $assigned,   'info'],
        ['modalProgress',   'Progress',   'bi-arrow-repeat',      $progress,   'amber'],
        ['modalDone',       'Done',       'bi-check2-circle',     $done,       'baik'],
    ];
@endphp
<div class="panel-gelap mb-4" style="height:auto;">
    <div class="kepala">Alur Penugasan <span class="sub">dari {{ $totalOrder }} order</span></div>
    <div class="p-3">
        <div class="row g-3">
            @foreach($alur as [$modal, $nama, $ikon, $jumlah, $tone])
                <div class="col-6 col-lg-3">
                    <div class="item-alur tone-{{ $tone }}" onclick="showModal('{{ $modal }}')">
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span class="nama"><i class="bi {{ $ikon }}"></i>{{ $nama }}</span>
                            <span class="angka-kecil">{{ $jumlah }}</span>
                        </div>
                        <div class="bar"><span style="width: {{ round($jumlah / $dasar * 100) }}%"></span></div>
                        <small>{{ round($jumlah / $dasar * 100) }}% dari total</small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel-gelap">
            <div class="kepala">TTR Performance <span class="sub">{{ $ttrLabel ?? '30 hari terakhir' }}</span></div>
            <div class="p-3">
                @if($ttrHarian->isEmpty())
                    <div class="text-center py-5" style="color: var(--teks-mute);">
                        Tidak ada order dalam 30 hari terakhir.
                    </div>
                @else
                    <canvas id="grafikTtr" height="230"></canvas>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel-gelap">
            <div class="kepala">Rekap per Service Area <span class="sub">{{ $rekapWilayah->count() }} wilayah</span></div>
            <div class="table-responsive">
                <table class="tabel-gelap">
                    <thead>
                        <tr>
                            <th>Service Area</th><th>OV</th><th>Comply</th><th>Unass.</th>
                            <th>Assigned</th><th>Progress</th><th>Done</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapWilayah as $i => $w)
                            <tr style="cursor:pointer;" onclick="toggleWilayah({{ $i }})">
                                <td>
                                    <strong style="color: var(--teks);">{{ $w['wilayah'] }}</strong>
                                    <span style="color: var(--teks-mute); font-size:.78rem;">({{ $w['jumlahSto'] }} STO)</span>
                                </td>
                                <td class="warna-merah">{{ $w['ov'] }}</td>
                                <td class="warna-baik">{{ $w['comply'] }}</td>
                                <td>{{ $w['unassigned'] }}</td>
                                <td>{{ $w['assigned'] }}</td>
                                <td class="warna-amber">{{ $w['progress'] }}</td>
                                <td class="warna-baik">{{ $w['done'] }}</td>
                            </tr>
                            @foreach($w['sto'] as $s)
                                <tr class="baris-sto" data-grup="{{ $i }}" style="display:none;">
                                    <td style="padding-left:2rem;">{{ $s->kode_sto }} — {{ $s->nama_sto }}</td>
                                    <td>{{ $s->ov_count }}</td>
                                    <td>{{ $s->comply_count }}</td>
                                    <td>{{ $s->unassigned_count }}</td>
                                    <td>{{ $s->assigned_count }}</td>
                                    <td>{{ $s->progress_count }}</td>
                                    <td>{{ $s->done_count }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="7" class="text-center py-4" style="color: var(--teks-mute);">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

{{-- ================= MODAL ================= --}}
<div class="modal fade" id="modalTeknisi" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Total Teknisi ({{ $totalTeknisi }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>Nama</th><th>NIK</th><th>Kode</th><th>STO</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($modalData['teknisi'] as $t)
                            <tr>
                                <td><strong>{{ strtoupper($t->nama) }}</strong></td>
                                <td>{{ $t->nik }}</td>
                                <td>{{ $t->kode_teknisi ?? '-' }}</td>
                                <td>{{ $t->sto->kode_sto ?? '-' }}</td>
                                <td><span class="badge-status {{ $t->sedang_aktif ? 'badge-COMPWORK' : 'badge-CANCLWORK' }}">{{ $t->sedang_aktif ? 'Aktif' : 'Idle' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTeknisiHariIni" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teknisi Aktif Hari Ini ({{ $teknisiHariIni }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>Nama</th><th>NIK</th><th>Kode</th><th>STO</th></tr></thead>
                    <tbody>
                        @forelse($modalData['teknisi']->where('sedang_aktif', true) as $t)
                            <tr><td><strong>{{ strtoupper($t->nama) }}</strong></td><td>{{ $t->nik }}</td><td>{{ $t->kode_teknisi ?? '-' }}</td><td>{{ $t->sto->kode_sto ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4" style="color: var(--teks-mute);">Belum ada teknisi aktif hari ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTeknisiTerorder" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teknisi dengan Order Aktif ({{ $teknisiTerorder }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>Nama</th><th>NIK</th><th>Kode</th><th>STO</th></tr></thead>
                    <tbody>
                        @forelse($modalData['teknisi']->where('sedang_aktif', true) as $t)
                            <tr><td><strong>{{ strtoupper($t->nama) }}</strong></td><td>{{ $t->nik }}</td><td>{{ $t->kode_teknisi ?? '-' }}</td><td>{{ $t->sto->kode_sto ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4" style="color: var(--teks-mute);">Belum ada order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTeknisiIdle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teknisi Idle ({{ $teknisiIdle }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>Nama</th><th>NIK</th><th>Kode</th><th>STO</th></tr></thead>
                    <tbody>
                        @forelse($modalData['teknisi']->where('sedang_aktif', false) as $t)
                            <tr><td><strong>{{ strtoupper($t->nama) }}</strong></td><td>{{ $t->nik }}</td><td>{{ $t->kode_teknisi ?? '-' }}</td><td>{{ $t->sto->kode_sto ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4" style="color: var(--teks-mute);">Semua teknisi aktif! 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTotalOrder" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Total Order ({{ $totalOrder }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>WONUM</th><th>STO</th><th>Pelanggan</th><th>Teknisi</th><th>Status</th><th>Tgl</th></tr></thead>
                    <tbody>
                        @forelse($modalData['order'] as $o)
                            <tr>
                                <td style="font-family: monospace;">{{ $o->wonum }}</td>
                                <td>{{ $o->sto ?? '-' }}</td>
                                <td>{{ $o->customer_name ?? '-' }}</td>
                                <td>{{ $o->chief_name ?? '-' }}</td>
                                <td><span class="badge-status badge-{{ $o->status ?? 'CANCLWORK' }}">{{ $o->status ?? '-' }}</span></td>
                                <td style="font-size:.75rem; color: var(--teks-mute);">{{ $o->datecreated?->format('d/m/y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4" style="color: var(--teks-mute);">Belum ada order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalOV" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">OV / Order Bermasalah ({{ $ov }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>WONUM</th><th>STO</th><th>Pelanggan</th><th>Teknisi</th><th>Error</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($modalData['ov'] as $o)
                            <tr>
                                <td style="font-family: monospace;">{{ $o->wonum }}</td>
                                <td>{{ $o->sto ?? '-' }}</td>
                                <td>{{ $o->customer_name ?? '-' }}</td>
                                <td>{{ $o->chief_name ?? '-' }}</td>
                                <td style="font-size:.78rem; color: var(--teks-mute);">{{ $o->errorcode_akhir ?? '-' }}</td>
                                <td><span class="badge-status badge-{{ $o->status ?? 'CANCLWORK' }}">{{ $o->status ?? '-' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4" style="color: var(--teks-mute);">Tidak ada order bermasalah.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalComply" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comply / Order Selesai ({{ $comply }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>WONUM</th><th>STO</th><th>Pelanggan</th><th>Teknisi</th><th>Durasi TTI</th><th>Tgl</th></tr></thead>
                    <tbody>
                        @forelse($modalData['comply'] as $o)
                            <tr>
                                <td style="font-family: monospace;">{{ $o->wonum }}</td>
                                <td>{{ $o->sto ?? '-' }}</td>
                                <td>{{ $o->customer_name ?? '-' }}</td>
                                <td>{{ $o->chief_name ?? '-' }}</td>
                                <td>{{ $o->durasi_tti ?? '-' }} jam</td>
                                <td style="font-size:.75rem; color: var(--teks-mute);">{{ $o->datecreated?->format('d/m/y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4" style="color: var(--teks-mute);">Belum ada order selesai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUnassigned" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unassigned / Belum Ada Teknisi ({{ $unassigned }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>WONUM</th><th>STO</th><th>Pelanggan</th><th>Alamat</th><th>Tgl Masuk</th></tr></thead>
                    <tbody>
                        @forelse($modalData['unassigned'] as $o)
                            <tr>
                                <td style="font-family: monospace;">{{ $o->wonum }}</td>
                                <td>{{ $o->sto ?? '-' }}</td>
                                <td>{{ $o->customer_name ?? '-' }}</td>
                                <td style="font-size:.78rem;">{{ \Str::limit($o->customer_address, 50) }}</td>
                                <td style="font-size:.75rem; color: var(--teks-mute);">{{ $o->datecreated?->format('d/m/y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4" style="color: var(--teks-mute);">Semua order sudah di-assign! 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAssigned" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assigned / Sudah Di-Assign ({{ $assigned }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>WONUM</th><th>STO</th><th>Pelanggan</th><th>Teknisi</th><th>Kode</th><th>Tgl</th></tr></thead>
                    <tbody>
                        @forelse($modalData['assigned'] as $o)
                            <tr>
                                <td style="font-family: monospace;">{{ $o->wonum }}</td>
                                <td>{{ $o->sto ?? '-' }}</td>
                                <td>{{ $o->customer_name ?? '-' }}</td>
                                <td>{{ $o->chief_name ?? '-' }}</td>
                                <td>{{ $o->chief_code ?? '-' }}</td>
                                <td style="font-size:.75rem; color: var(--teks-mute);">{{ $o->datecreated?->format('d/m/y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4" style="color: var(--teks-mute);">Belum ada order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProgress" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Progress / Sedang Dikerjakan ({{ $progress }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>WONUM</th><th>STO</th><th>Pelanggan</th><th>Teknisi</th><th>Tgl</th></tr></thead>
                    <tbody>
                        @forelse($modalData['progress'] as $o)
                            <tr>
                                <td style="font-family: monospace;">{{ $o->wonum }}</td>
                                <td>{{ $o->sto ?? '-' }}</td>
                                <td>{{ $o->customer_name ?? '-' }}</td>
                                <td>{{ $o->chief_name ?? '-' }}</td>
                                <td style="font-size:.75rem; color: var(--teks-mute);">{{ $o->datecreated?->format('d/m/y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4" style="color: var(--teks-mute);">Belum ada order progress.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDone" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Done / Selesai ({{ $done }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="tabel-gelap">
                    <thead><tr><th>WONUM</th><th>STO</th><th>Pelanggan</th><th>Teknisi</th><th>Durasi TTI</th><th>Tgl</th></tr></thead>
                    <tbody>
                        @forelse($modalData['done'] as $o)
                            <tr>
                                <td style="font-family: monospace;">{{ $o->wonum }}</td>
                                <td>{{ $o->sto ?? '-' }}</td>
                                <td>{{ $o->customer_name ?? '-' }}</td>
                                <td>{{ $o->chief_name ?? '-' }}</td>
                                <td>{{ $o->durasi_tti ?? '-' }} jam</td>
                                <td style="font-size:.75rem; color: var(--teks-mute);">{{ $o->datecreated?->format('d/m/y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4" style="color: var(--teks-mute);">Belum ada order selesai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function showModal(id) {
        const el = document.getElementById(id);
        if (el) new bootstrap.Modal(el).show();
    }

    function toggleWilayah(i) {
        document.querySelectorAll(`.baris-sto[data-grup="${i}"]`).forEach(b => {
            b.style.display = b.style.display === 'none' ? 'table-row' : 'none';
        });
    }

    @if($ttrHarian->isNotEmpty())
    const warnaGrid = getComputedStyle(document.documentElement).getPropertyValue('--border').trim();
    const warnaTeks = getComputedStyle(document.documentElement).getPropertyValue('--teks-mute').trim();

    Chart.defaults.font.family = "'Nunito', system-ui, sans-serif";
    new Chart(document.getElementById('grafikTtr'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($ttrHarian->pluck('tanggal')->map(fn($t) => \Carbon\Carbon::parse($t)->format('d/m'))) !!},
            datasets: [
                { label: 'Comply', data: {!! json_encode($ttrHarian->pluck('comply')) !!}, backgroundColor: '#2E9E6B', borderRadius: 8, borderSkipped: false },
                { label: 'Overtime', data: {!! json_encode($ttrHarian->pluck('overtime')) !!}, backgroundColor: '#D62839', borderRadius: 8, borderSkipped: false },
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, color: warnaTeks }, grid: { color: warnaGrid } },
                x: { ticks: { color: warnaTeks }, grid: { color: warnaGrid } }
            },
            plugins: { legend: { position: 'bottom', labels: { color: warnaTeks } } }
        }
    });
    @endif
</script>
@endsection