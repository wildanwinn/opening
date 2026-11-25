@extends('layouts.app')
@section('judul', 'Detail Order')

@section('konten')
@php
    $user    = auth()->user();
    $bisaUbah = in_array($user->role, ['admin', 'teknisi']);
    $baris = [
        'WONUM'            => $monitoring->wonum,
        'Tipe Order'       => $monitoring->crmordertype,
        'STO'              => $monitoring->sto,
        'Datel'            => $monitoring->datel,
        'Paket'            => $monitoring->package_name,
        'Produk'           => $monitoring->product,
        'No. Service'      => $monitoring->servicenum,
        'Pelanggan'        => $monitoring->customer_name,
        'Alamat'           => $monitoring->customer_address,
        'No. HP'           => $monitoring->no_hp,
        'Koordinat'        => ($monitoring->latitude_ins && $monitoring->longitude_ins) ? $monitoring->latitude_ins.', '.$monitoring->longitude_ins : null,
        'ODP'              => $monitoring->odp_name,
        'Tanggal Order'    => $monitoring->datecreated?->format('d/m/Y H:i'),
        'Tanggal Manja'    => $monitoring->tgl_manja?->format('d/m/Y H:i'),
        'Status Survey'    => $monitoring->status_survey,
        'AM Crew'          => $monitoring->amcrew,
        'Teknisi'          => $monitoring->chief_name ? strtoupper($monitoring->chief_name).($monitoring->chief_code ? ' ('.$monitoring->chief_code.')' : '') : null,
        'Status'           => $monitoring->status,
        'Update Status'    => $monitoring->statusdate?->format('d/m/Y H:i'),
        'Error Awal'       => trim(($monitoring->errorcode_awal ?? '').' '.($monitoring->suberrorcode_awal ?? '')) ?: null,
        'Error Akhir'      => trim(($monitoring->errorcode_akhir ?? '').' '.($monitoring->suberrorcode_akhir ?? '')) ?: null,
        'Durasi Instalasi' => $monitoring->durasi_instalasi,
        'Durasi TTI'       => $monitoring->durasi_tti,
        'Memo Teknisi'     => $monitoring->engineermemo,
    ];
@endphp

<div class="container-fluid px-0">
    <div class="p-4 rounded-4 shadow-sm mb-4" style="background: var(--bg-card); border: 1px solid var(--border);">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="mb-0 fw-bold" style="color: var(--teks);">
                Detail Order <span style="font-family: monospace;">{{ $monitoring->wonum }}</span>
            </h5>
            <a href="{{ url()->previous() === url()->current() ? route('monitoring.index') : url()->previous() }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="row g-0">
            @foreach($baris as $label => $nilai)
                <div class="col-12 col-md-6">
                    <div class="d-flex py-2 px-1" style="border-bottom: 1px solid var(--border-soft);">
                        <div style="width: 150px; flex-shrink: 0; font-size: .78rem; text-transform: uppercase; letter-spacing: .4px; color: var(--teks-mute);">
                            {{ $label }}
                        </div>
                        <div style="font-size: .9rem; color: var(--teks); word-break: break-word;">
                            {{ filled($nilai) ? $nilai : '-' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($bisaUbah)
    <div class="p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">
        <h6 class="fw-bold mb-3" style="color: var(--teks);">Update Status Pekerjaan</h6>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('monitoring.update', $monitoring) }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-12 col-md-4">
                <label class="form-label" style="color: var(--teks-mute); font-size: .8rem;">Status</label>
                <select name="status" class="form-select" required>
                    @foreach($daftarStatus as $kode => $label)
                        <option value="{{ $kode }}" @selected(old('status', $monitoring->status) === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-8">
                <label class="form-label" style="color: var(--teks-mute); font-size: .8rem;">Memo</label>
                <textarea name="engineermemo" rows="2" maxlength="1000" class="form-control">{{ old('engineermemo', $monitoring->engineermemo) }}</textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-danger" type="submit"><i class="bi bi-check2-circle"></i> Simpan</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
