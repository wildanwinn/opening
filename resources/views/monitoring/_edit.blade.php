@php
    // Kolom = header kuning Excel DATA MASTER (selain STATUS & ENGINEERMEMO yang ada di bagian bawah)
    $kolomLengkapi = [
        ['sto',                'STO',                'text'],
        ['scorderno',          'SCORDERNO',          'text'],
        ['servicenum',         'SERVICENUM',         'text'],
        ['latitude_ins',       'LATITUDE_INS',       'text'],
        ['longitude_ins',      'LONGITUDE_INS',      'text'],
        ['datecreated',        'DATECREATED',        'datetime'],
        ['tgl_manja',          'TGL_MANJA',          'datetime'],
        ['statusdate',         'STATUSDATE',         'datetime'],
        ['odp_name',           'ODP_NAME',           'text'],
        ['errorcode_akhir',    'ERRORCODE_AKHIR',    'text'],
        ['suberrorcode_akhir', 'SUBERRORCODE_AKHIR', 'text'],
        ['homepass_id',        'FLAG_HOMEPASSID',    'text'],
        ['long_inst_ut',       'LONG_INST_UT',       'text'],
        ['lat_inst_ut',        'LAT_INST_UT',        'text'],
        ['durasi_tti',         'DURASI_TTI',         'text'],
    ];

    $info = [
        'Pelanggan'    => $monitoring->customer_name,
        'No. HP'       => $monitoring->no_hp,
        'Alamat'       => $monitoring->customer_address,
        'Paket'        => $monitoring->package_name,
        'Datel'        => $monitoring->datel,
        'Teknisi'      => $monitoring->chief_name ? strtoupper($monitoring->chief_name) . ($monitoring->chief_code ? ' (' . $monitoring->chief_code . ')' : '') : null,
        'Error Awal'   => trim(($monitoring->errorcode_awal ?? '') . ' ' . ($monitoring->suberrorcode_awal ?? '')) ?: null,
        'ODP (master)' => $monitoring->odp ? $monitoring->odp->nama_odp . ($monitoring->odp->sto ? ' · STO ' . $monitoring->odp->sto : '') : null,
        'Lat ODP'      => $monitoring->odp?->latitude !== null ? rtrim(rtrim($monitoring->odp->latitude, '0'), '.') : ($monitoring->odp_name ? '#N/A' : null),
        'Long ODP'     => $monitoring->odp?->longitude !== null ? rtrim(rtrim($monitoring->odp->longitude, '0'), '.') : ($monitoring->odp_name ? '#N/A' : null),
        'Flag ODP'     => $monitoring->odp_name ? (($monitoring->odp && $monitoring->odp->latitude !== null) ? 'ODP VALID' : 'ODP TIDAK VALID (belum ada di master ODP)') : null,
    ];
@endphp

<div class="modal-header">
    <h5 class="modal-title">Edit Data <span style="font-family: monospace; font-size:.9em;">{{ $monitoring->wonum }}</span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>

<div class="modal-body">
  <div class="isi-edit">
    <form id="formEditData" method="POST" action="{{ route('monitoring.update', $monitoring) }}" novalidate>
        @csrf
        @method('PUT')

        <div id="pesanEdit" class="alert alert-danger d-none"></div>

        <div class="row g-2 mb-3">
            @foreach($info as $label => $nilai)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="d-flex py-1" style="border-bottom: 1px solid var(--border-soft);">
                        <div style="width: 110px; flex-shrink: 0; font-size: .74rem; text-transform: uppercase; letter-spacing: .4px; color: var(--teks-mute);">{{ $label }}</div>
                        <div style="font-size: .86rem; color: var(--teks); word-break: break-word;">{{ filled($nilai) ? $nilai : '-' }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <h6 class="fw-bold mb-1" style="color: var(--teks);">Data order</h6>
        <p class="mb-3" style="font-size: .8rem; color: var(--teks-mute);">
            @if($bisaEdit)
                Kolom yang sudah terisi dari Excel dikunci <i class="bi bi-lock-fill"></i>. Kolom kosong bisa dilengkapi.
            @else
                Role kamu hanya bisa melihat data ini.
            @endif
        </p>

        <div class="row g-3 mb-3">
            @foreach($kolomLengkapi as [$nama, $label, $tipe])
                @php
                    $nilai    = $monitoring->{$nama};
                    $kosong   = blank($nilai);
                    $tampil   = $nilai instanceof \Carbon\Carbon ? $nilai->format('Y-m-d\TH:i') : $nilai;
                    $bisaIsi  = $kosong && $bisaEdit;
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
                    <label class="form-label mb-1" for="f_{{ $nama }}" style="font-size: .8rem; color: var(--teks-mute);">
                        {{ $label }}
                        @if($bisaIsi)
                            <span class="badge-kosong">kosong · bisa diisi</span>
                        @elseif(! $kosong)
                            <i class="bi bi-lock-fill" title="Sudah terisi dari Excel"></i>
                        @endif
                    </label>
                    <input id="f_{{ $nama }}"
                           type="{{ $tipe === 'datetime' ? 'datetime-local' : 'text' }}"
                           name="{{ $nama }}"
                           value="{{ $tampil }}"
                           @if(in_array($nama, ['latitude_ins', 'longitude_ins', 'long_inst_ut', 'lat_inst_ut', 'durasi_tti'])) inputmode="decimal" @endif
                           class="form-control form-control-sm {{ $bisaIsi ? 'isi-kosong' : '' }}"
                           placeholder="{{ $bisaIsi ? 'Kosong di Excel — isi di sini' : '' }}"
                           @disabled(! $bisaIsi)>
                </div>
            @endforeach
        </div>

        <h6 class="fw-bold mb-2" style="color: var(--teks);">Status pekerjaan</h6>
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label mb-1" for="f_status" style="font-size: .8rem; color: var(--teks-mute);">Status</label>
                <select id="f_status" name="status" class="form-select form-select-sm" @disabled(! $bisaEdit)>
                    @if(blank($monitoring->status))
                        <option value="">-</option>
                    @endif
                    @foreach($daftarStatus as $kode => $label)
                        <option value="{{ $kode }}" @selected($monitoring->status === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-9">
                <label class="form-label mb-1" for="f_memo" style="font-size: .8rem; color: var(--teks-mute);">Engineer Memo</label>
                <textarea id="f_memo" name="engineermemo" rows="3" maxlength="1000"
                          class="form-control form-control-sm" @disabled(! $bisaEdit)>{{ $monitoring->engineermemo }}</textarea>
            </div>
        </div>
    </form>

    @if($riwayat->isNotEmpty())
        <hr style="border-color: var(--border-soft);">
        <h6 class="fw-bold mb-2" style="color: var(--teks);">Riwayat edit terakhir</h6>
        <ul class="list-unstyled mb-0" style="font-size: .8rem; color: var(--teks-mute);">
            @foreach($riwayat as $log)
                <li class="py-1">
                    <strong style="color: var(--teks);">{{ $log->kolom }}</strong>:
                    {{ $log->nilai_lama ?? '(kosong)' }} → {{ \Illuminate\Support\Str::limit($log->nilai_baru ?? '(kosong)', 60) }}
                    · {{ $log->user->nama ?? 'sistem' }}, {{ $log->created_at->format('d/m/Y H:i') }}
                </li>
            @endforeach
        </ul>
    @endif
  </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
    @if($bisaEdit)
        <button type="submit" form="formEditData" class="btn btn-danger btn-sm px-4">
            <i class="bi bi-check2-circle"></i> Simpan
        </button>
    @endif
</div>
