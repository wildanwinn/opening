@extends('layouts.app')
@section('judul', 'Data ODP')

@push('skrip')
<style>
    .tabel-odp { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .tabel-odp th { background: var(--bg-hover); color: var(--teks-mute); text-align: left; padding: .6rem .8rem; font-weight: 700; }
    .tabel-odp td { padding: .55rem .8rem; border-top: 1px solid var(--border-soft); color: var(--teks); }
    .tabel-odp tr:hover td { background: var(--bg-hover); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">
    <div class="panel-utama p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h5 class="mb-0 fw-bold" style="color: var(--teks);">Data ODP</h5>
                <small style="color: var(--teks-mute);">{{ $daftarOdp->total() }} ODP · kunci: nama ODP · per STO · dipasangkan ke order lewat nama ODP</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalUploadOdp">
                    <i class="bi bi-cloud-arrow-up"></i> Upload Excel ODP
                </button>
                <button class="btn btn-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalOdp"
                        data-aksi="{{ route('odp.store') }}" data-metode="POST" data-nama="" data-sto="" data-lat="" data-lng="" data-judul="Tambah ODP">
                    <i class="bi bi-plus-lg"></i> Tambah ODP
                </button>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('odp.index') }}" class="row g-2 mb-3">
            <div class="col-12 col-md-5">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm" placeholder="Cari nama ODP" style="height: 38px;">
            </div>
            <div class="col-12 col-md-4">
                <select name="sto" class="form-select form-select-sm" style="height: 38px;">
                    <option value="">-- Semua STO --</option>
                    @foreach($daftarSto as $s)
                        <option value="{{ $s->kode_sto }}" @selected(request('sto') === $s->kode_sto)>{{ $s->kode_sto }} — {{ $s->nama_sto }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-1">
                <button class="btn btn-danger btn-sm flex-grow-1" style="height: 38px;"><i class="bi bi-search"></i> Filter</button>
                @if(request()->hasAny(['cari', 'sto']))
                    <a href="{{ route('odp.index') }}" class="btn btn-outline-secondary btn-sm" style="height: 38px;" title="Reset"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="tabel-odp">
                <thead><tr><th>Nama ODP</th><th>STO</th><th>Latitude</th><th>Longitude</th><th class="text-end">Jumlah order</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                @forelse($daftarOdp as $o)
                    <tr>
                        <td style="font-family: monospace;">{{ $o->nama_odp }}</td>
                        <td>{{ $o->sto ?? '-' }}</td>
                        <td style="font-family: monospace;">{{ $o->latitude !== null ? rtrim(rtrim($o->latitude, '0'), '.') : '-' }}</td>
                        <td style="font-family: monospace;">{{ $o->longitude !== null ? rtrim(rtrim($o->longitude, '0'), '.') : '-' }}</td>
                        <td class="text-end">{{ $o->monitorings_count }}</td>
                        <td class="text-end" style="white-space: nowrap;">
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalOdp"
                                    data-aksi="{{ route('odp.update', $o) }}" data-metode="PUT"
                                    data-nama="{{ $o->nama_odp }}" data-sto="{{ $o->sto }}"
                                    data-lat="{{ $o->latitude !== null ? rtrim(rtrim($o->latitude, '0'), '.') : '' }}"
                                    data-lng="{{ $o->longitude !== null ? rtrim(rtrim($o->longitude, '0'), '.') : '' }}" data-judul="Edit ODP">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('odp.destroy', $o) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus ODP {{ $o->nama_odp }}? Order tetap ada, hanya hubungannya ke tabel ODP yang dilepas.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4" style="color: var(--teks-mute);">Belum ada data ODP. Upload Excel atau tambah manual.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($daftarOdp->hasPages())
            <div class="mt-3">{{ $daftarOdp->links() }}</div>
        @endif
    </div>
</div>

<div class="modal fade" id="modalOdp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="formOdp">
            @csrf
            <input type="hidden" name="_method" id="metodeOdp" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalOdp">Tambah ODP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="namaOdp" style="color: var(--teks-mute); font-size: .85rem;">Nama ODP</label>
                    <input type="text" name="odp_name" id="namaOdp" class="form-control" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="stoOdp" style="color: var(--teks-mute); font-size: .85rem;">STO</label>
                    <select name="sto" id="stoOdp" class="form-select">
                        <option value="">-- Tanpa STO --</option>
                        @foreach($daftarSto as $s)
                            <option value="{{ $s->kode_sto }}">{{ $s->kode_sto }} — {{ $s->nama_sto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label" for="latOdp" style="color: var(--teks-mute); font-size: .85rem;">Latitude</label>
                        <input type="text" name="latitude" id="latOdp" class="form-control" inputmode="decimal" placeholder="-7.2575">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="lngOdp" style="color: var(--teks-mute); font-size: .85rem;">Longitude</label>
                        <input type="text" name="longitude" id="lngOdp" class="form-control" inputmode="decimal" placeholder="112.7521">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger btn-sm px-4">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalUploadOdp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('odp.import') }}" enctype="multipart/form-data" id="formUploadOdp">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Upload Excel ODP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p style="font-size: .85rem; color: var(--teks-mute);">
                    Boleh pilih banyak file sekaligus atau satu file <code>.zip</code>. Judul kolom mengikuti header master:
                    <code style="word-break: break-word;">{{ implode(', ', \App\Models\Odp::HEADER) }}</code>.
                    Yang wajib hanya <code>ODP_NAME</code>. ODP yang namanya sudah ada diperbarui, yang baru ditambah, lalu order dengan nama ODP yang sama otomatis dipasangkan.
                </p>
                <a href="{{ route('odp.template') }}" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-download"></i> Unduh template</a>
                <input type="file" name="file[]" id="fileOdp" class="form-control" accept=".xlsx,.xls,.csv,.zip" multiple required>

                <div id="loadingOdp" class="text-center pt-3 d-none">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="mt-2" style="font-size: .85rem; color: var(--teks-mute);">Memproses file ODP, jangan tutup halaman ini…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger btn-sm px-4" id="tombolUploadOdp">Upload</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('skrip')
<script>
document.getElementById('formUploadOdp').addEventListener('submit', function () {
    const t = document.getElementById('tombolUploadOdp');
    t.disabled = true;
    t.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses…';
    document.getElementById('loadingOdp').classList.remove('d-none');
});
document.getElementById('modalOdp').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    if (!b) return;
    document.getElementById('formOdp').action = b.dataset.aksi;
    document.getElementById('metodeOdp').value = b.dataset.metode;
    document.getElementById('judulModalOdp').textContent = b.dataset.judul;
    document.getElementById('namaOdp').value = b.dataset.nama || '';
    document.getElementById('stoOdp').value = b.dataset.sto || '';
    document.getElementById('latOdp').value = b.dataset.lat || '';
    document.getElementById('lngOdp').value = b.dataset.lng || '';
});
</script>
@endpush
