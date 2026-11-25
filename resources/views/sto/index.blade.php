@extends('layouts.app')
@section('judul', 'Area & STO')

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
        font-size: .88rem; color: var(--teks) !important;
    }
    .tabel-data tr:hover td { background: var(--bg-hover) !important; }
    .tabel-data tr:last-child td { border-bottom: none; }
    .form-control, .form-select {
        background-color: var(--bg-card) !important;
        border-color: var(--border) !important;
        color: var(--teks) !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary) !important;
        box-shadow: none !important;
        background-color: var(--bg-card) !important;
        color: var(--teks) !important;
    }
    .badge-wilayah {
        display: inline-block; font-size: .7rem; font-weight: 600;
        padding: .3rem .7rem; border-radius: 20px;
        background: rgba(109,147,255,.15); color: #6d93ff;
    }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">
    <div class="p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="mb-0 fw-bold" style="color: var(--teks);">Area & STO</h5>
            <a href="{{ route('sto.create') }}" class="btn btn-danger btn-sm px-3 py-2">
                <i class="bi bi-plus-lg me-1"></i> Tambah STO
            </a>
        </div>

        <form action="{{ route('sto.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-12 col-md-4">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm" placeholder="Cari kode / nama / wilayah...">
            </div>
            <div class="col-12 col-md-3">
                <select name="wilayah" class="form-select form-select-sm">
                    <option value="">-- Semua Wilayah --</option>
                    @foreach($daftarWilayah as $w)
                        <option value="{{ $w }}" @selected(request('wilayah') === $w)>{{ $w }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button class="btn btn-danger btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
                @if(request()->hasAny(['cari', 'wilayah']))
                    <a href="{{ route('sto.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>Kode STO</th>
                        <th>Nama STO</th>
                        <th>Wilayah</th>
                        <th class="text-center">Jumlah Order</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($daftarSto as $s)
                    <tr>
                        <td><strong>{{ $s->kode_sto }}</strong></td>
                        <td>{{ $s->nama_sto }}</td>
                        <td><span class="badge-wilayah">{{ $s->wilayah }}</span></td>
                        <td class="text-center">{{ $s->monitorings_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('sto.edit', $s) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                            <form method="POST" action="{{ route('sto.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Hapus STO {{ $s->kode_sto }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4" style="color: var(--teks-mute);">Belum ada STO.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $daftarSto->links() }}</div>
    </div>
</div>
@endsection