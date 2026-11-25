@extends('layouts.app')
@section('judul', $sto->exists ? 'Edit STO' : 'Tambah STO')

@section('konten')
<div class="row justify-content-center">
    <div class="col-12 col-md-6">
        <div class="p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">
            <h5 class="mb-4" style="color: var(--teks);">{{ $sto->exists ? 'Edit STO' : 'Tambah STO' }}</h5>

            <form method="POST" action="{{ $sto->exists ? route('sto.update', $sto) : route('sto.store') }}">
                @csrf
                @if($sto->exists) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label" style="color: var(--teks);">Kode STO</label>
                    <input type="text" name="kode_sto" value="{{ old('kode_sto', $sto->kode_sto) }}" class="form-control" required maxlength="5">
                    @error('kode_sto') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" style="color: var(--teks);">Nama STO</label>
                    <input type="text" name="nama_sto" value="{{ old('nama_sto', $sto->nama_sto) }}" class="form-control" required>
                    @error('nama_sto') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label" style="color: var(--teks);">Wilayah</label>
                    <input type="text" name="wilayah" value="{{ old('wilayah', $sto->wilayah) }}" class="form-control" required>
                    @error('wilayah') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-danger">Simpan</button>
                <a href="{{ route('sto.index') }}" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection