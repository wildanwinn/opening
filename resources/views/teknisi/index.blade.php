@extends('layouts.app')
@section('judul', 'Data Teknisi')

@push('skrip')
<style>
    .tabel-data { width: 100%; border-collapse: collapse; }
    .tabel-data th {
        text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px;
        color: var(--teks-mute); font-weight: 600; padding: .8rem 1rem;
        border-bottom: 1px solid var(--border);
    }
    .tabel-data td {
        padding: .9rem 1rem; border-bottom: 1px solid var(--border-soft);
        font-size: .88rem; color: var(--teks) !important; vertical-align: middle;
    }
    .tabel-data tr:hover td { background: var(--bg-hover) !important; }
    .tabel-data tr:last-child td { border-bottom: none; }
    .form-control, .form-select {
        background-color: var(--bg-card) !important;
        border-color: var(--border) !important;
        color: var(--teks) !important;
    }
    .form-select:focus { border-color: var(--primary) !important; box-shadow: none !important; }
    .avatar-teknisi {
        width: 38px; height: 38px; border-radius: 50%;
        background: linear-gradient(135deg, #4d78e0, #2547b0);
        color: #fff; display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .95rem;
    }
    .badge-aktif { background: rgba(46,211,145,.15); color: #2ed391; font-size: .7rem; font-weight: 600; padding: .3rem .7rem; border-radius: 20px; }
    .badge-idle  { background: rgba(108,117,125,.15); color: var(--teks-mute); font-size: .7rem; font-weight: 600; padding: .3rem .7rem; border-radius: 20px; }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">
    <div class="p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">

        @include('user._tab')

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="mb-0 fw-bold" style="color: var(--teks);">Data Teknisi</h5>
            <small style="color: var(--teks-mute);">Total: {{ $daftarTeknisi->count() }} teknisi</small>
        </div>

        <form action="{{ route('teknisi.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-12 col-md-5">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm" placeholder="Cari nama / NIK / kode teknisi...">
            </div>
            <div class="col-12 col-md-4">
                <select name="sto_id" class="form-select form-select-sm">
                    <option value="">-- Semua STO --</option>
                    @foreach($daftarSto as $s)
                        <option value="{{ $s->id }}" @selected(request('sto_id') == $s->id)>{{ $s->kode_sto }} — {{ $s->nama_sto }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-1">
                <button class="btn btn-danger btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
                @if(request()->hasAny(['cari', 'sto_id']))
                    <a href="{{ route('teknisi.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>Teknisi</th>
                        <th>Kode</th>
                        <th>STO</th>
                        <th class="text-center">Total Order</th>
                        <th class="text-center">Selesai</th>
                        <th class="text-center">Gagal</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarTeknisi as $t)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-teknisi">{{ strtoupper(substr($t->nama, 0, 1)) }}</div>
                                <div>
                                    <div><strong>{{ strtoupper($t->nama) }}</strong></div>
                                    <small style="color: var(--teks-mute);">{{ $t->nik }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $t->kode_teknisi ?? '-' }}</td>
                        <td>{{ $t->sto->kode_sto ?? '-' }}</td>
                        <td class="text-center">{{ $t->total_order }}</td>
                        <td class="text-center" style="color: #2ed391;">{{ $t->order_selesai }}</td>
                        <td class="text-center" style="color: #ff5d6c;">{{ $t->order_gagal }}</td>
                        <td class="text-center">
                            <span class="{{ $t->sedang_aktif ? 'badge-aktif' : 'badge-idle' }}">
                                {{ $t->sedang_aktif ? 'Aktif' : 'Idle' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('teknisi.show', $t) }}" class="btn btn-outline-secondary btn-sm">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4" style="color: var(--teks-mute);">Belum ada teknisi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection