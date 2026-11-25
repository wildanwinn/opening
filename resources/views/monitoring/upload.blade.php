@extends('layouts.app')
@section('judul', 'Upload Data')

@push('skrip')
<style>
    .maskot-kucing { display: flex; justify-content: center; margin-bottom: .4rem; }
    .maskot-kucing .mk-badan { transform-origin: 60px 100px; animation: mk-loncat .9s ease-in-out infinite; }
    .maskot-kucing .mk-mata  { transform-origin: 60px 58px; animation: mk-kedip 3.2s infinite; }
    .maskot-kucing.selesai .mk-badan, .maskot-kucing.selesai .mk-mata { animation: none; }
    @keyframes mk-loncat { 0%,100% { transform: translateY(0) rotate(-2deg); } 50% { transform: translateY(-8px) rotate(2deg); } }
    @keyframes mk-kedip  { 0%,92%,100% { transform: scaleY(1); } 96% { transform: scaleY(.1); } }
    @media (prefers-reduced-motion: reduce) { .maskot-kucing .mk-badan, .maskot-kucing .mk-mata { animation: none; } }
</style>
<style>
    .langkah { display: flex; gap: .5rem; flex-wrap: wrap; margin: 0 0 1.25rem; padding: 0; list-style: none; }
    .langkah li { font-size: .8rem; font-weight: 700; padding: .3rem .85rem; border-radius: 999px;
                  background: var(--bg-hover); color: var(--teks-mute); border: 1px solid var(--border-soft); }
    .langkah li.aktif { background: var(--primary); border-color: var(--primary); color: #fff; }
    .langkah li.selesai { color: var(--ok); border-color: var(--ok); }

    input[type="file"] { background-color: var(--bg-app) !important; color: var(--teks) !important;
                         border: 1px solid var(--border) !important; border-radius: 10px; padding: .4rem; }
    input[type="file"]::file-selector-button { background-color: var(--badge-bg) !important; color: var(--teks) !important;
        border: 1px solid var(--border) !important; padding: .45rem .9rem; border-radius: 8px; cursor: pointer; margin-right: 1rem; }

    .ringkas { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .ringkas > div { padding: .8rem 1rem; border-radius: 16px; background: var(--bg-hover); border: 1px solid var(--border-soft); }
    .ringkas .n { font-size: 1.6rem; font-weight: 700; color: var(--teks); line-height: 1.1; }
    .ringkas .l { font-size: .74rem; color: var(--teks-mute); }

    .tabel-pratinjau-wrap { overflow-x: auto; border: 1px solid var(--border-soft); border-radius: 14px; }
    .tabel-pratinjau { width: 100%; border-collapse: collapse; font-size: .78rem; white-space: nowrap; }
    .tabel-pratinjau th { background: var(--bg-hover); color: var(--teks-mute); text-align: left; padding: .55rem .75rem; font-weight: 700; }
    .tabel-pratinjau td { padding: .45rem .75rem; color: var(--teks); border-top: 1px solid var(--border-soft); max-width: 220px; overflow: hidden; text-overflow: ellipsis; }
    .tabel-pratinjau td.kosong { color: var(--teks-mute); font-style: italic; }

    .tab-jenis { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
    .tab-jenis a { padding: .5rem 1.1rem; border-radius: 12px; font-size: .85rem; font-weight: 600; text-decoration: none;
                   background: var(--bg-hover); color: var(--teks-mute); border: 1px solid var(--border-soft); transition: all .2s; }
    .tab-jenis a:hover { color: var(--teks); border-color: var(--border); }
    .tab-jenis a.aktif { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 6px 16px rgba(214,40,57,.28); }
    .kotak-info { border-radius: 14px; padding: .8rem 1rem; background: var(--bg-hover); border: 1px solid var(--border-soft);
                  font-size: .82rem; color: var(--teks-mute); margin-bottom: 1rem; }
    .kotak-info code { color: var(--teks); background: transparent; }
    .daftar-header { columns: 3 200px; font-size: .75rem; margin: .5rem 0 0; padding-left: 1.1rem; color: var(--teks-mute); }
    .tabel-riwayat { width: 100%; border-collapse: collapse; font-size: .82rem; }
    .tabel-riwayat th { color: var(--teks-mute); text-align: left; padding: .5rem .6rem; font-weight: 700; }
    .tabel-riwayat td { padding: .5rem .6rem; border-top: 1px solid var(--border-soft); color: var(--teks); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">
    <div class="panel-utama p-4 rounded-4 shadow-sm mb-4" style="background: var(--bg-card); border: 1px solid var(--border);">
        <h5 class="fw-bold mb-3" style="color: var(--teks);">Upload Data</h5>

        <nav class="tab-jenis" aria-label="Jenis data">
            @foreach($semua as $kode => $d)
                <a href="{{ route('monitoring.upload', ['jenis' => $kode]) }}" class="{{ $kode === $jenis ? 'aktif' : '' }}">{{ $d['label'] }}</a>
            @endforeach
        </nav>

        <div class="kotak-info">
            <strong style="color: var(--teks);">{{ $definisi['label'] }}</strong> — {{ $definisi['ringkas'] }}.
            Primary key: <code>{{ strtoupper($definisi['kunci']) }}</code> (wajib ada dan tidak boleh kosong).
            File dengan header yang tidak sesuai, header tambahan, atau salah jenis akan <strong>ditolak</strong> sebelum ada data yang disimpan.
            @if($jenis === 'odp')
                <br>Judul kolom nama ODP boleh <code>NAMA_ODP</code> atau <code>ODP_NAME</code> (file asli dari sumber). File Data Master / KPRO tetap ditolak karena headernya tidak cocok.
            @elseif($jenis === 'bima')
                <br>Kolom <code>Order ID TSEL</code> disimpan di tabel TSEL tersendiri: WO yang terisi = TSEL, yang kosong = non TSEL. Semua WO BIMA otomatis muncul di Data Monitoring, dan statusnya diambil dari BIMA.
            @endif
            <details class="mt-2"><summary style="cursor:pointer; color: var(--teks);">Lihat header yang diterima ({{ count($definisi['header']) }} kolom)</summary>
                <ul class="daftar-header">@foreach($definisi['header'] as $h)<li>{{ $h }}</li>@endforeach</ul>
            </details>
        </div>


        <ol class="langkah" id="penanda">
            <li data-l="1" class="aktif">1 · Pilih file</li>
            <li data-l="2">2 · Pratinjau</li>
            <li data-l="3">3 · Simpan</li>
        </ol>

        {{-- Langkah 1 --}}
        <div id="langkah1">
            <label class="form-label" for="berkas" style="color: var(--teks-mute);">{{ $jenis === 'odp' ? 'File ODP (.xlsx, .xls, .csv, atau .zip berisi banyak file)' : 'File Excel (.xlsx, .xls, .csv)' }}</label>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="file" id="berkas" class="form-control" style="max-width: 460px;" accept="{{ $jenis === 'odp' ? '.xlsx,.xls,.csv,.zip' : '.xlsx,.xls,.csv' }}" @if($jenis === 'odp') multiple @endif>
                <button type="button" id="btnBaca" class="btn btn-danger px-4">
                    <i class="bi bi-eye"></i> Baca &amp; pratinjau
                </button>
            </div>
            <div class="form-text mt-2" style="color: var(--teks-mute);">
                Baris pertama harus berisi judul kolom sesuai format {{ $definisi['label'] }}. Ukuran maksimal 50 MB.
                @if($jenis === 'odp')
                    Boleh pilih banyak file sekaligus. ODP yang <code>NAMA_ODP</code>-nya sudah ada akan diperbarui, yang belum ada ditambahkan.
                @endif
            </div>

            <div id="progresUnggah" class="mt-3 d-none">
                @include('partials.maskot-kucing', ['ukuran' => 64])
                <div class="progress" style="height: 10px;">
                    <div id="barUnggah" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 0%"></div>
                </div>
                <small id="teksUnggah" style="color: var(--teks-mute);"></small>
            </div>
            <div id="pesanUpload" class="alert alert-danger mt-3 d-none"></div>
            <div id="galatHeader" class="alert alert-danger mt-2 d-none" style="font-size: .82rem;"></div>
        </div>

        {{-- Langkah 2 --}}
        <div id="langkah2" class="d-none">
            <div class="mb-2" style="color: var(--teks-mute); font-size: .85rem;">
                File: <strong id="namaFile" style="color: var(--teks);"></strong>
            </div>
            <div class="ringkas">
                <div><div class="n" id="rValid">0</div><div class="l">baris siap disimpan</div></div>
                <div><div class="n" id="rBaru">0</div><div class="l">data baru</div></div>
                <div><div class="n" id="rUpdate">0</div><div class="l">data diperbarui</div></div>
                <div><div class="n" id="rTanpa">0</div><div class="l">dilewati (tanpa {{ strtoupper($definisi['kunci']) }})</div></div>
                <div><div class="n" id="rDobel">0</div><div class="l">{{ strtoupper($definisi['kunci']) }} dobel di file</div></div>
            </div>
            <div id="peringatan" class="alert alert-warning d-none"></div>

            <div class="mb-2 fw-bold" style="color: var(--teks);">Pratinjau 15 baris pertama</div>
            <div class="tabel-pratinjau-wrap mb-3">
                <table class="tabel-pratinjau"><thead id="kepalaPratinjau"></thead><tbody id="isiPratinjau"></tbody></table>
            </div>

            <div class="d-flex gap-2">
                <button type="button" id="btnKonfirmasi" class="btn btn-danger px-4"><i class="bi bi-cloud-arrow-up"></i> Konfirmasi &amp; simpan</button>
                <button type="button" id="btnBatal" class="btn btn-outline-secondary">Batal</button>
            </div>
        </div>

        {{-- Langkah 3 --}}
        <div id="langkah3" class="d-none">
            <div id="maskotProses">@include('partials.maskot-kucing')</div>
            <div class="d-flex justify-content-between mb-1">
                <strong id="judulProses" style="color: var(--teks);">Menyimpan data…</strong>
                <span id="persenProses" style="color: var(--teks-mute);">0%</span>
            </div>
            <div class="progress mb-2" style="height: 14px;">
                <div id="barProses" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 0%"></div>
            </div>
            <small id="teksProses" style="color: var(--teks-mute);"></small>

            <div id="hasilAkhir" class="mt-3 d-none">
                <div class="ringkas">
                    <div><div class="n" id="hBaru">0</div><div class="l">data baru</div></div>
                    <div><div class="n" id="hUpdate">0</div><div class="l">data diperbarui</div></div>
                    <div><div class="n" id="hGagal">0</div><div class="l">baris gagal</div></div>
                </div>
                <div id="catatanGagal" class="alert alert-danger d-none" style="font-size: .8rem;"></div>
                <a href="{{ route('monitoring.index') }}" class="btn btn-danger btn-sm px-4">Lihat Data Monitoring</a>
                <a href="{{ route('monitoring.upload') }}" class="btn btn-outline-secondary btn-sm">Upload lagi</a>
            </div>
            <div id="pesanProses" class="alert alert-danger mt-3 d-none"></div>
        </div>
    </div>

    <div class="panel-utama p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">
        <h6 class="fw-bold mb-3" style="color: var(--teks);">Riwayat upload</h6>
        <div class="table-responsive">
            <table class="tabel-riwayat">
                <thead>
                    <tr><th>Waktu</th><th>Jenis</th><th>File</th><th>Oleh</th><th>Status</th><th class="text-end">Baru</th><th class="text-end">Update</th><th class="text-end">Gagal</th></tr>
                </thead>
                <tbody>
                @forelse($riwayat as $b)
                    <tr>
                        <td style="white-space: nowrap;">{{ $b->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $semua[$b->jenis]['label'] ?? $b->jenis }}</td>
                        <td>{{ $b->nama_file }}</td>
                        <td>{{ $b->user->nama ?? '-' }}</td>
                        <td>{{ ['pratinjau' => 'Dibatalkan / belum disimpan', 'proses' => 'Terhenti di tengah', 'selesai' => 'Selesai'][$b->status] ?? $b->status }}</td>
                        <td class="text-end">{{ $b->baris_baru }}</td>
                        <td class="text-end">{{ $b->baris_update }}</td>
                        <td class="text-end">{{ $b->baris_gagal }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-3" style="color: var(--teks-mute);">Belum ada riwayat upload.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('skrip')
<script>
(function () {
    const $    = id => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const HDR  = { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const JENIS = @json($jenis);
    let batch  = null;

    function tampil(id, ya) { $(id).classList.toggle('d-none', !ya); }

    function langkah(n) {
        [1, 2, 3].forEach(i => {
            tampil('langkah' + i, i === n);
            const li = document.querySelector('#penanda [data-l="' + i + '"]');
            li.classList.toggle('aktif', i === n);
            li.classList.toggle('selesai', i < n);
        });
    }

    function galat(teks) {
        $('pesanUpload').textContent = teks || '';
        tampil('pesanUpload', !!teks);
    }

    // ---- Langkah 1: unggah + baca (dengan indikator unggah)
    $('btnBaca').addEventListener('click', function () {
        const files = $('berkas').files;
        if (!files.length) { galat('Pilih file dulu.'); return; }
        galat('');
        tampil('galatHeader', false);

        const fd  = new FormData();
        if (JENIS === 'odp') { Array.from(files).forEach(f => fd.append('file[]', f)); }
        else { fd.append('file', files[0]); }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', @json(route('upload.preview', ['jenis' => $jenis])));
        Object.keys(HDR).forEach(k => xhr.setRequestHeader(k, HDR[k]));

        xhr.upload.onprogress = function (e) {
            if (!e.lengthComputable) return;
            const p = Math.round(e.loaded / e.total * 100);
            $('barUnggah').style.width = p + '%';
            $('teksUnggah').textContent = p < 100 ? 'Mengunggah file… ' + p + '%' : 'File terunggah. Membaca isi file…';
        };

        xhr.onload = function () {
            $('btnBaca').disabled = false;
            tampil('progresUnggah', false);
            let json = {};
            try { json = JSON.parse(xhr.responseText); } catch (e) {}

            if (xhr.status === 200) { pratinjau(json); return; }
            galat(json.message || (json.errors && Object.values(json.errors)[0][0]) || 'Gagal membaca file.');
            if (json.errors_header && json.errors_header.length) {
                $('galatHeader').innerHTML = json.errors_header.map(esc).join('<br>');
                tampil('galatHeader', true);
            }
        };
        xhr.onerror = function () {
            $('btnBaca').disabled = false;
            tampil('progresUnggah', false);
            galat('Koneksi terputus saat mengunggah. Coba lagi.');
        };

        $('btnBaca').disabled = true;
        $('barUnggah').style.width = '0%';
        $('teksUnggah').textContent = 'Mengunggah file… 0%';
        tampil('progresUnggah', true);
        xhr.send(fd);
    });

    // ---- Langkah 2: pratinjau
    function esc(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function pratinjau(j) {
        batch = j;
        $('namaFile').textContent = j.nama_file;
        $('rValid').textContent  = j.valid;
        $('rBaru').textContent   = j.baru;
        $('rUpdate').textContent = j.update;
        $('rTanpa').textContent  = j.tanpa_kunci;
        $('rDobel').textContent  = j.dobel;

        const kunci = Object.keys(j.kolom);
        $('kepalaPratinjau').innerHTML = '<tr>' + kunci.map(k => '<th>' + esc(j.kolom[k]) + '</th>').join('') + '</tr>';
        $('isiPratinjau').innerHTML = j.contoh.map(b =>
            '<tr>' + kunci.map(k => (b[k] === null || b[k] === '')
                ? '<td class="kosong">kosong</td>'
                : '<td title="' + esc(b[k]) + '">' + esc(b[k]) + '</td>').join('') + '</tr>'
        ).join('');

        const p = [];
        if (j.kolom_hilang.length) p.push('Kolom tidak ditemukan di Excel: <strong>' + j.kolom_hilang.map(esc).join(', ') + '</strong>. Kolom ini akan kosong.');
        if (j.tanpa_kunci)         p.push(j.tanpa_kunci + ' baris tanpa ' + j.kunci + ' akan dilewati.');
        if (j.dobel)               p.push(j.dobel + ' baris memakai ' + j.kunci + ' yang sama dengan baris lain; yang dipakai baris paling bawah.');
        (j.tambahan || []).forEach(t => p.push(esc(t)));
        $('peringatan').innerHTML = p.join('<br>');
        tampil('peringatan', p.length > 0);

        langkah(2);
    }

    $('btnBatal').addEventListener('click', function () {
        if (batch) fetch(batch.url_batal, { method: 'DELETE', headers: HDR }).catch(() => {});
        batch = null;
        $('berkas').value = '';
        langkah(1);
    });

    // ---- Langkah 3: simpan per potongan, progress mengikuti baris yang benar-benar tersimpan
    $('btnKonfirmasi').addEventListener('click', async function () {
        langkah(3);
        tampil('hasilAkhir', false);
        tampil('pesanProses', false);

        try {
            let selesai = false;
            while (!selesai) {
                const res  = await fetch(batch.url_proses, { method: 'POST', headers: HDR });
                const json = await res.json().catch(() => ({}));

                if (!res.ok) { throw new Error(json.message || 'Proses terhenti.'); }

                const p = json.total ? Math.round(json.diproses / json.total * 100) : 100;
                $('barProses').style.width = p + '%';
                $('persenProses').textContent = p + '%';
                $('teksProses').textContent = json.diproses + ' dari ' + json.total + ' baris tersimpan';

                selesai = json.selesai;
                if (selesai) tampilHasil(json);
            }
        } catch (err) {
            $('pesanProses').textContent = err.message + ' Baris yang sudah tersimpan tetap aman; ulangi upload untuk melanjutkan.';
            tampil('pesanProses', true);
            $('barProses').classList.remove('progress-bar-animated');
            document.querySelector('#maskotProses .maskot-kucing').classList.add('selesai');
        }
    });

    function tampilHasil(j) {
        $('judulProses').textContent = 'Selesai';
        document.querySelector('#maskotProses .maskot-kucing').classList.add('selesai');
        $('barProses').classList.remove('progress-bar-animated');
        $('hBaru').textContent   = j.baru;
        $('hUpdate').textContent = j.update;
        $('hGagal').textContent  = j.gagal;
        if (j.catatan && j.catatan.length) {
            $('catatanGagal').innerHTML = '<strong>Baris gagal (maks. 20):</strong><br>' + j.catatan.map(esc).join('<br>');
            tampil('catatanGagal', true);
        }
        tampil('hasilAkhir', true);
    }
})();
</script>
@endpush
