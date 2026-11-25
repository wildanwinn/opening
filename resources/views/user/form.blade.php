@extends('layouts.app')
@section('judul', $user->exists ? 'Edit User' : 'Tambah User')

@push('skrip')
<style>
    .form-label-gelap { color: var(--teks); font-weight: 600; font-size: .88rem; }
    .form-teks-mute-gelap { color: var(--teks-mute); font-size: .8rem; }
</style>
@endpush

@section('konten')
<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-6">
        <div class="panel-utama">
            <h5 class="mb-4" style="color: var(--teks);">{{ $user->exists ? 'Edit User' : 'Tambah User' }}</h5>

            <form method="POST" action="{{ $user->exists ? route('user.update', $user) : route('user.store') }}">
                @csrf
                @if($user->exists) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label form-label-gelap">NIK</label>
                    <input type="text" name="nik" class="form-control" value="{{ old('nik', $user->nik) }}" required>
                    @error('nik') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label form-label-gelap">
                        Kode Teknisi
                        <span class="form-teks-mute-gelap">(isi kalau role = Teknisi, harus sama persis dengan CHIEF_CODE di data Excel)</span>
                    </label>
                    <input type="text" name="kode_teknisi" class="form-control" value="{{ old('kode_teknisi', $user->kode_teknisi) }}">
                    @error('kode_teknisi') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label form-label-gelap">Nama</label>
                    <input type="text" name="nama" class="form-control" value="{{ old('nama', $user->nama) }}" required>
                    @error('nama') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label form-label-gelap">
                        Password
                        @if($user->exists)
                            <span class="form-teks-mute-gelap">(kosongkan jika tidak diganti)</span>
                        @endif
                    </label>
                    <input type="password" name="password" class="form-control" {{ $user->exists ? '' : 'required' }}>
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label form-label-gelap">Role</label>
                    <select name="role" class="form-select" required>
                        @foreach([
                            'admin'        => 'Admin',
                            'hd'           => 'HD',
                            'teknisi'      => 'Teknisi',
                            'edp'          => 'EDP',
                            'tsel'         => 'TSEL',
                            'tif'          => 'TIF',
                            'service_area' => 'Service Area',
                            'oc3'          => 'OC3',
                        ] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('role', $user->role) === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label form-label-gelap">STO (opsional)</label>
                    <select name="sto_id" class="form-select">
                        <option value="">-- Tidak terikat STO --</option>
                        @foreach($daftarSto as $s)
                            <option value="{{ $s->id }}" @selected(old('sto_id', $user->sto_id) == $s->id)>
                                {{ $s->kode_sto }} — {{ $s->nama_sto }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-danger">Simpan</button>
                <a href="{{ route('user.index') }}" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection