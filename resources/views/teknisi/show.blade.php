@extends('layouts.app')
@section('judul', 'Detail Teknisi')

@push('skrip')
<style>
    .tabel-data { width: 100%; border-collapse: collapse; }
    .tabel-data th { text-align: left; font-size: .72rem; text-transform: uppercase; color: var(--teks-mute); padding: .7rem 1rem; border-bottom: 1px solid var(--border); }
    .tabel-data td { padding: .8rem 1rem; border-bottom: 1px solid var(--border-soft); font-size: .86rem; color: var(--teks) !important; }
    .tabel-data tr:hover td { background: var(--bg-hover) !important; }
    .stat-card {
        background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px;
        padding: 1rem 1.2rem; text-align: center;
    }
    .stat-card .angka { font-size: 1.8rem; font-weight: 700; }
    .stat-card .label { font-size: .72rem; text-transform: uppercase; color: var(--teks-mute); margin-top: .2rem; }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('teknisi.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h5 class="mb-0 fw-bold" style="color: var(--teks);">Detail Teknisi</h5>
    </div>

    <div class="p-4 rounded-4 mb-4" style="background: var(--bg-card); border: 1px solid var(--border);">
        <div class="d-flex align-items-center gap-3">
            <div style="width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,#4d78e0,#2547b0); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.5rem;">
                {{ strtoupper(substr($teknisi->nama, 0, 1)) }}
            </div>
            <div>
                <h5 class="mb-1" style="color: var(--teks);">{{ strtoupper($teknisi->nama) }}</h5>
                <div style="color: var(--teks-mute); font-size:.88rem;">
                    NIK: {{ $teknisi->nik }}
                    @if($teknisi->kode_teknisi) · Kode: {{ $teknisi->kode_teknisi }} @endif
                    @if($teknisi->sto) · {{ $teknisi->sto->kode_sto }} ({{ $teknisi->sto->wilayah }}) @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="angka" style="color: var(--teks);">{{ $stats['total'] }}</div>
                <div class="label">Total Order</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="angka" style="color: #2ed391;">{{ $stats['comply'] }}</div>
                <div class="label">Selesai</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="angka" style="color: #ff5d6c;">{{ $stats['ov'] }}</div>
                <div class="label">Gagal</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="angka" style="color: #f5a623;">{{ $stats['progress'] }}</div>
                <div class="label">Progress</div>
            </div>
        </div>
    </div>

    <div class="p-4 rounded-4" style="background: var(--bg-card); border: 1px solid var(--border);">
        <h6 class="mb-3 fw-bold" style="color: var(--teks);">Riwayat Order</h6>
        <div class="table-responsive">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>WONUM</th>
                        <th>STO</th>
                        <th>Pelanggan</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td style="font-family: monospace;">{{ $o->wonum }}</td>
                        <td>{{ $o->sto ?? '-' }}</td>
                        <td>{{ $o->customer_name ?? '-' }}</td>
                        <td>{{ $o->status ?? '-' }}</td>
                        <td style="color: var(--teks-mute);">{{ $o->datecreated?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4" style="color: var(--teks-mute);">Belum ada order.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
</div>
@endsection