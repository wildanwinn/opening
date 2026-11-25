@extends('layouts.mobile')
@section('judul', 'Selesai')

@section('konten')
<div class="isi">
@include('tugas._judul', ['judul' => 'Selesai · Tutup Tugas', 'kembali' => route('tugas.lokasi', $m)])

<form method="POST" action="{{ route('tugas.selesai.simpan', $m) }}" enctype="multipart/form-data" class="form">
    @csrf
    <div class="kartu muncul" style="--i:0">
        <h3>Ringkasan material terpakai</h3>
        <div style="margin-top:8px">
            @forelse($ringkas as $r)
                <div class="baris"><span>{{ $r->material->nama }}</span><b>{{ $r->qty }} {{ $r->material->satuan }}</b></div>
            @empty
                <div class="muted">Tidak ada material dicatat.</div>
            @endforelse
        </div>
    </div>

    <div class="kartu muncul" style="--i:1">
        <h3>Foto ODP sesudah</h3>
        <div class="thumbs" id="thumbs">@foreach($fotos as $f)<img src="{{ route('foto.tampil', $f) }}" alt="Foto sesudah {{ $loop->iteration }}">@endforeach</div>
        <label class="unggah" style="min-height:76px">
            <span id="infoFoto">Ambil / pilih foto</span>
            <input type="file" name="foto[]" accept="image/*" multiple onchange="pratinjau(this)">
        </label>
    </div>

    <div class="kartu muncul" style="--i:2">
        <label for="catatan" style="font-size:14.5px;font-weight:600">Catatan (opsional)</label>
        <textarea id="catatan" name="catatan" rows="3" placeholder="Contoh: pelanggan sudah aktif, redaman normal.">{{ old('catatan', $m->catatan_teknisi) }}</textarea>
    </div>

    <div class="bawah"><button class="btn" type="submit">Selesaikan Tugas</button></div>
</form>
</div>
<script>
function pratinjau(inp){
    document.getElementById('infoFoto').textContent=inp.files.length+' foto dipilih';
    var t=document.getElementById('thumbs');
    Array.prototype.forEach.call(inp.files,function(f){var i=document.createElement('img');i.src=URL.createObjectURL(f);i.alt='Foto baru';t.appendChild(i)});
}
</script>
@endsection
