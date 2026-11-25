@extends('layouts.app')
@section('judul', 'Data Monitoring')

@push('skrip')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
    .panel-utama { width: 100%; overflow-x: auto; }

    .tabel-monitoring { width: 100%; border-collapse: collapse; }
    .tabel-monitoring th {
        text-align: left; font-size: .72rem; text-transform: uppercase;
        letter-spacing: .5px; color: var(--teks-mute); font-weight: 600;
        padding: .8rem 1rem; border-bottom: 1px solid var(--border);
    }
    .tabel-monitoring td {
        padding: .9rem 1rem; border-bottom: 1px solid var(--border-soft);
        font-size: .88rem; vertical-align: middle; color: var(--teks) !important;
    }
    .tabel-monitoring td, .tabel-monitoring th { white-space: nowrap; }
    .tabel-monitoring tr:hover td { background: var(--bg-hover) !important; }
    .tabel-monitoring tr:last-child td { border-bottom: none; }

    .badge-status {
        display: inline-block; font-size: .68rem; font-weight: 600;
        padding: .25rem .65rem; border-radius: 20px; letter-spacing: .3px;
    }
    .badge-STARTWORK { background: rgba(77,120,224,.15);  color: #6d93ff; }
    .badge-CONTWORK  { background: rgba(245,166,35,.18);  color: #f5a623; }
    .badge-COMPWORK  { background: rgba(46,211,145,.15);  color: #2ed391; }
    .badge-WORKFAIL  { background: rgba(220,53,69,.15);   color: #ff5d6c; }
    .badge-CANCLWORK { background: rgba(108,117,125,.18); color: var(--teks-mute); }

    .form-control, .form-select {
        background-color: var(--bg-card) !important;
        border-color: var(--border) !important;
        color: var(--teks) !important;
    }
    .form-control:focus, .form-select:focus {
        background-color: var(--bg-card) !important;
        color: var(--teks) !important;
        border-color: var(--primary) !important;
        box-shadow: none !important;
    }
    .form-control::placeholder { color: var(--teks-mute) !important; }

    /* ===== Custom Tom Select biar cocok sama tema dark ===== */
    .ts-wrapper .ts-control {
        background-color: var(--bg-card) !important;
        border-color: var(--border) !important;
        color: var(--teks) !important;
        border-radius: 10px !important;
        padding: .4rem .75rem !important;
        font-size: .85rem !important;
        min-height: 38px !important;
    }
    .ts-wrapper.focus .ts-control {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 .2rem rgba(255,100,115,.15) !important;
    }
    .ts-wrapper .ts-control input {
        color: var(--teks) !important;
        font-size: .85rem !important;
    }
    .ts-wrapper .ts-control input::placeholder { color: var(--teks-mute) !important; }
    .ts-wrapper .ts-control .item {
        color: var(--teks) !important;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
    }
    .ts-dropdown {
        background-color: var(--bg-panel) !important;
        border-color: var(--border) !important;
        color: var(--teks) !important;
        border-radius: 10px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,.4) !important;
    }
    .ts-dropdown .option {
        color: var(--teks) !important;
        font-size: .85rem !important;
        padding: .5rem .75rem !important;
    }
    .ts-dropdown .option:hover,
    .ts-dropdown .option.active {
        background-color: var(--bg-hover) !important;
        color: var(--primary) !important;
    }
    .ts-dropdown .option.selected {
        background-color: rgba(255,100,115,.1) !important;
        color: var(--primary) !important;
    }
    .ts-dropdown .no-results {
        color: var(--teks-mute) !important;
        font-size: .82rem !important;
        padding: .6rem .75rem !important;
    }
    .ts-wrapper.single .ts-control::after {
        border-color: var(--teks-mute) transparent transparent transparent !important;
    }
    .ts-wrapper.single.input-active .ts-control::after {
        border-color: transparent transparent var(--teks-mute) transparent !important;
    }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">
    <div class="panel-utama p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="mb-0 fw-bold" style="color: var(--teks);">Data Monitoring</h5>
            <small style="color: var(--teks-mute);">Total: {{ $dataMonitoring->total() }} order</small>
        </div>

        <form action="{{ route('monitoring.index') }}" method="GET" class="row g-2 mb-4 align-items-center">
            <div class="col-12 col-md-3">
                <input type="text" name="cari" value="{{ request('cari') }}"
                       class="form-control form-control-sm"
                       placeholder="Cari WONUM / SC Order / Pelanggan / ODP"
                       style="height: 38px;">
            </div>
            <div class="col-12 col-md-3">
                <select name="sto" id="comboSto">
                    <option value="">-- Semua STO --</option>
                    @foreach($daftarSto as $s)
                        <option value="{{ $s->kode_sto }}" @selected(request('sto') === $s->kode_sto)>
                            {{ $s->kode_sto }} — {{ $s->nama_sto }} ({{ $s->wilayah }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <select name="status" id="comboStatus">
                    <option value="">-- Semua Status --</option>
                    @foreach($daftarStatus as $kode => $label)
                        <option value="{{ $kode }}" @selected(request('status') === $kode)>
                            {{ $label }} ({{ $kode }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="tsel" class="form-select form-select-sm" style="height: 38px;" aria-label="Filter TSEL">
                    <option value="">Semua (TSEL &amp; non)</option>
                    <option value="ya" @selected(request('tsel') === 'ya')>Hanya TSEL</option>
                    <option value="tidak" @selected(request('tsel') === 'tidak')>Hanya non TSEL</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-danger btn-sm flex-grow-1" style="height: 38px;">
                    <i class="bi bi-search"></i> Filter
                </button>
                @if(request()->hasAny(['sto', 'status', 'cari', 'tsel']))
                    <a href="{{ route('monitoring.index') }}" class="btn btn-outline-secondary btn-sm"
                       style="height: 38px;" title="Reset">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="tabel-monitoring">
                <thead>
                    <tr>
                        <th>WONUM</th>
                        <th>STO</th>
                        <th>ODP</th>
                        <th>LAT ODP</th>
                        <th>LONG ODP</th>
                        <th>CUSTOMER_NAME</th>
                        <th>PACKAGE_NAME</th>
                        <th>Teknisi</th>
                        <th>Status (BIMA)</th>
                        <th>TSEL</th>
                        <th>Tanggal</th>
                        <th>SCORDERNO</th>
                        <th>SERVICENUM</th>
                        <th>LATITUDE_INS</th>
                        <th>LONGITUDE_INS</th>
                        <th>TGL_MANJA</th>
                        <th>CHIEF_CODE</th>
                        <th>STATUSDATE</th>
                        <th>ENGINEERMEMO</th>
                        <th>ERRORCODE_AKHIR</th>
                        <th>SUBERRORCODE_AKHIR</th>
                        <th>FLAG_HOMEPASSID</th>
                        <th>LONG_INST_UT</th>
                        <th>LAT_INST_UT</th>
                        <th>DURASI_TTI</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($dataMonitoring as $row)
                    <tr>
                        <td style="font-family: monospace;">{{ $row->wonum }}</td>
                        <td>{{ $row->sto ?? '-' }}</td>
                        <td>{{ $row->odp->nama_odp ?? $row->odp_name ?? '-' }}</td>
                        {{-- LAT/LONG ODP diambil dari master ODP (seperti VLOOKUP di Excel); #N/A = ODP belum ada di master ODP --}}
                        @php
                            $latOdp  = $row->odp?->latitude  !== null ? rtrim(rtrim($row->odp->latitude, '0'), '.')  : null;
                            $longOdp = $row->odp?->longitude !== null ? rtrim(rtrim($row->odp->longitude, '0'), '.') : null;
                        @endphp
                        <td>{!! $latOdp  ?? ($row->odp_name ? '<span style="color: var(--teks-mute);">#N/A</span>' : '-') !!}</td>
                        <td>{!! $longOdp ?? ($row->odp_name ? '<span style="color: var(--teks-mute);">#N/A</span>' : '-') !!}</td>
                        <td>{{ $row->customer_name ?? '-' }}</td>
                        <td>{{ $row->package_name ?? '-' }}</td>
                        <td>
                            @if($row->chief_name)
                                {{ strtoupper($row->chief_name) }}
                            @else
                                <span style="color: var(--teks-mute); font-style: italic;">Belum di-assign</span>
                            @endif
                        </td>
                        <td>
                            @php $st = $row->status_tampil; @endphp
                            <span class="badge-status badge-{{ $st ?? 'CANCLWORK' }}">{{ $st ?? 'UNKNOWN' }}</span>
                        </td>
                        <td style="white-space: nowrap;">
                            @if($row->tsel)
                                <span class="badge-status badge-tsel" title="Order ID TSEL">TSEL</span>
                                <span style="font-family: monospace; font-size:.76rem; color: var(--teks-mute);">{{ $row->tsel->order_id_tsel }}</span>
                            @else
                                <span style="color: var(--teks-mute); font-size:.82rem;">Non TSEL</span>
                            @endif
                        </td>
                        <td style="color: var(--teks-mute); font-size:.82rem;">
                            {{ $row->datecreated?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        {{-- kolom tambahan = header kuning Excel DATA MASTER --}}
                        <td style="font-family: monospace; font-size:.8rem;">{{ $row->scorderno ?? '-' }}</td>
                        <td>{{ $row->servicenum ?? '-' }}</td>
                        <td>{{ $row->latitude_ins ?? '-' }}</td>
                        <td>{{ $row->longitude_ins ?? '-' }}</td>
                        <td style="color: var(--teks-mute); font-size:.82rem;">{{ $row->tgl_manja?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $row->chief_code ?? '-' }}</td>
                        <td style="color: var(--teks-mute); font-size:.82rem;">{{ $row->statusdate?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td style="min-width: 220px; white-space: normal; font-size:.82rem;">{{ $row->engineermemo ? \Illuminate\Support\Str::limit($row->engineermemo, 80) : '-' }}</td>
                        <td>{{ $row->errorcode_akhir ?? '-' }}</td>
                        <td>{{ $row->suberrorcode_akhir ?? '-' }}</td>
                        <td>{{ $row->homepass_id ?? '-' }}</td>
                        <td>{{ $row->long_inst_ut ?? '-' }}</td>
                        <td>{{ $row->lat_inst_ut ?? '-' }}</td>
                        <td>{{ $row->durasi_tti ?? '-' }}</td>
                        <td class="text-end" style="white-space: nowrap;">
                            @if($bisaKirim)
                                <button type="button" class="btn btn-danger btn-sm"
                                        @disabled($row->tahap !== null)
                                        title="{{ $row->tahap !== null ? 'Sudah dikerjakan teknisi' : ($row->chief_name ? 'Kirim ulang ke teknisi lain' : 'Kirim tugas ke teknisi') }}"
                                        data-send-url="{{ route('monitoring.assign', $row) }}"
                                        data-send-wo="{{ $row->wonum }}" data-send-sto="{{ $row->sto }}"
                                        data-send-nama="{{ $row->chief_name }}">
                                    <i class="bi bi-send"></i> Send
                                </button>
                            @endif
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    data-edit-url="{{ route('monitoring.show', $row) }}">
                                <i class="bi bi-pencil-square"></i> Edit Data
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="26" class="text-center py-4" style="color: var(--teks-mute);">
                            Belum ada data monitoring.
                            @if(request()->hasAny(['sto', 'status', 'cari', 'tsel']))
                                <br>
                                <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    Reset Filter
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($dataMonitoring->hasPages())
            <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small style="color: var(--teks-mute);">
                    Menampilkan {{ $dataMonitoring->firstItem() }}–{{ $dataMonitoring->lastItem() }}
                    dari {{ $dataMonitoring->total() }} data
                </small>
                <div>{{ $dataMonitoring->links() }}</div>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        new TomSelect('#comboSto', {
            placeholder: 'Cari STO...',
            allowEmptyOption: true,
            maxOptions: 100,
            sortField: { field: 'text', direction: 'asc' },
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">STO "' + escape(data.input) + '" tidak ditemukan</div>';
                }
            }
        });

        new TomSelect('#comboStatus', {
            placeholder: 'Cari Status...',
            allowEmptyOption: true,
            maxOptions: 100,
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">Status "' + escape(data.input) + '" tidak ditemukan</div>';
                }
            }
        });
    });
</script>
@if($bisaKirim)
<div class="modal fade" id="modalSend" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formSend" class="modal-content" style="background: var(--bg-panel); color: var(--teks); border: 1px solid var(--border); border-radius: 18px;">
            @csrf
            <div class="modal-header" style="border-color: var(--border);">
                <h6 class="modal-title fw-bold">Kirim tugas ke teknisi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2" style="font-size:.85rem; color: var(--teks-mute);">
                    Order <strong id="sendWo" style="color: var(--teks); font-family: monospace;"></strong>
                    <span id="sendSaatIni"></span>
                </div>
                <label for="sendTeknisi" class="form-label" style="font-size:.85rem;">Pilih teknisi</label>
                <select name="teknisi_id" id="sendTeknisi" class="form-select" required>
                    <option value="">-- Pilih teknisi --</option>
                    @foreach($daftarTeknisi as $t)
                        <option value="{{ $t->id }}" data-sto="{{ $t->sto?->kode_sto }}">{{ $t->nama }}{{ $t->sto ? ' — STO ' . $t->sto->kode_sto : '' }}</option>
                    @endforeach
                </select>
                <div class="form-text" style="color: var(--teks-mute);">Teknisi akan melihat tugas ini di aplikasi mobile (/tugas). Teknisi dengan STO yang sama dengan order ditampilkan lebih dulu.</div>
            </div>
            <div class="modal-footer" style="border-color: var(--border);">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger btn-sm px-3"><i class="bi bi-send"></i> Kirim</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('modalSend'), modal = new bootstrap.Modal(el);
    const sel = document.getElementById('sendTeknisi');
    document.addEventListener('click', function (e) {
        const b = e.target.closest('[data-send-url]');
        if (!b || b.disabled) return;
        document.getElementById('formSend').action = b.dataset.sendUrl;
        document.getElementById('sendWo').textContent = b.dataset.sendWo;
        document.getElementById('sendSaatIni').textContent = b.dataset.sendNama ? '(saat ini: ' + b.dataset.sendNama + ')' : '(belum ada teknisi)';
        const sto = b.dataset.sendSto;
        Array.from(sel.options).filter(o => o.value).sort((a, c) =>
            (c.dataset.sto === sto) - (a.dataset.sto === sto) || a.text.localeCompare(c.text)
        ).forEach(o => sel.appendChild(o));
        sel.value = '';
        modal.show();
    });
});
</script>
@endif

<div class="modal fade" id="modalEditData" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content" id="isiModalEdit"></div>
    </div>
</div>

@push('skrip')
<style>
    /* Popup Edit Data: layar penuh, warna ikut tema (sebelumnya teks pucat karena latar putih) */
    #modalEditData .modal-content { background: var(--bg-panel); color: var(--teks); border: 0; border-radius: 0; }
    #modalEditData .modal-header, #modalEditData .modal-footer { border-color: var(--border); padding-left: 2rem; padding-right: 2rem; }
    #modalEditData .modal-body { padding: 1.5rem 2rem; }
    #modalEditData .isi-edit { max-width: 1500px; margin: 0 auto; }
    #modalEditData .modal-title { color: var(--teks); }
    html[data-tema="dark"] #modalEditData .btn-close { filter: invert(1); }
    #modalEditData .form-control:disabled { background-color: var(--bg-hover) !important; color: var(--teks) !important; opacity: .9; }
    .badge-kosong { font-size: .68rem; font-weight: 700; padding: .1rem .5rem; border-radius: 999px;
                    background: color-mix(in srgb, var(--primary) 16%, transparent); color: var(--primary); margin-left: .35rem; }
    .isi-kosong { border-color: var(--primary) !important; }
    .form-control:disabled, .form-select:disabled { opacity: .75; cursor: not-allowed; }
</style>
<script>
(function () {
    const elModal = document.getElementById('modalEditData');
    const isi     = document.getElementById('isiModalEdit');
    const modal   = new bootstrap.Modal(elModal);
    const XHR     = { 'X-Requested-With': 'XMLHttpRequest' };

    document.addEventListener('click', function (e) {
        const tombol = e.target.closest('[data-edit-url]');
        if (!tombol) return;
        e.preventDefault();

        isi.innerHTML = '<div class="modal-body text-center py-5"><div class="spinner-border text-danger" role="status"></div>'
                      + '<div class="mt-3" style="color: var(--teks-mute);">Memuat data…</div></div>';
        modal.show();

        fetch(tombol.dataset.editUrl, { headers: XHR })
            .then(r => { if (!r.ok) throw r; return r.text(); })
            .then(html => { isi.innerHTML = html; })
            .catch(() => {
                isi.innerHTML = '<div class="modal-body py-4 text-center">Data tidak bisa dimuat (mungkin kamu tidak punya akses ke order ini).</div>'
                              + '<div class="modal-footer"><button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>';
            });
    });

    elModal.addEventListener('submit', async function (e) {
        const form = e.target;
        if (form.id !== 'formEditData') return;
        e.preventDefault();

        const pesan  = document.getElementById('pesanEdit');
        const simpan = document.querySelector('button[type="submit"][form="formEditData"]');
        pesan.classList.add('d-none');
        if (simpan) simpan.disabled = true;

        try {
            const res  = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: Object.assign({ 'Accept': 'application/json' }, XHR)
            });
            const json = await res.json().catch(() => ({}));

            if (res.ok) {
                modal.hide();
                Swal.fire({ icon: 'success', title: json.pesan || 'Tersimpan', timer: 1300, showConfirmButton: false })
                    .then(() => location.reload());
                return;
            }

            const galat = json.errors ? Object.values(json.errors)[0][0] : (json.message || 'Gagal menyimpan.');
            pesan.textContent = galat;
            pesan.classList.remove('d-none');
            elModal.querySelector('.modal-body').scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            pesan.textContent = 'Koneksi bermasalah. Coba lagi.';
            pesan.classList.remove('d-none');
        } finally {
            if (simpan) simpan.disabled = false;
        }
    });
})();
</script>
@endpush

@endsection