@extends('layouts.mobile')
@section('judul', 'Pre · Upload Material')

@section('konten')
<div class="isi">
@include('tugas._judul', ['judul' => 'Pre · Upload Material'])

<form method="POST" action="{{ route('tugas.pre.simpan', $m) }}" enctype="multipart/form-data" class="form">
    @csrf
    <div class="kartu muncul" style="--i:0">
        <div class="baris-judul"><h3>Material dibawa</h3><span class="muted">{{ $materials->count() }} jenis</span></div>
        <div style="margin-top:6px">
            @foreach($materials as $mat)
                <div class="mat">
                    <div class="nm">{{ $mat->nama }}<div class="muted">satuan: {{ $mat->satuan }}</div></div>
                    <button type="button" aria-label="Kurangi {{ $mat->nama }}" onclick="ubah({{ $mat->id }},-1)">−</button>
                    <input type="number" min="0" inputmode="numeric" id="qty{{ $mat->id }}" name="qty[{{ $mat->id }}]" value="{{ old('qty.'.$mat->id, $qty[$mat->id] ?? 0) }}" aria-label="Jumlah {{ $mat->nama }}">
                    <button type="button" class="p" aria-label="Tambah {{ $mat->nama }}" onclick="ubah({{ $mat->id }},1)">+</button>
                </div>
            @endforeach
        </div>
    </div>

    <div class="kartu muncul" style="--i:1">
        <div class="baris-judul"><h3>Foto bukti material</h3><span class="muted">{{ $fotos->count() }} dari min. {{ $minFoto }}</span></div>
        <div class="thumbs" id="thumbs">@foreach($fotos as $f)<img src="{{ route('foto.tampil', $f) }}" alt="Foto material {{ $loop->iteration }}">@endforeach</div>
        <label class="unggah">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            <span id="infoFoto">Ambil / pilih foto</span>
            <input type="file" name="foto[]" accept="image/*" multiple onchange="pratinjau(this)">
        </label>
    </div>

    <div class="bawah">
        <button class="btn" type="submit">Simpan &amp; Mulai Jalan
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </div>
</form>
</div>
<script>
function ubah(id,d){var e=document.getElementById('qty'+id);e.value=Math.max(0,(parseInt(e.value)||0)+d)}
function pratinjau(inp){
    document.getElementById('infoFoto').textContent=inp.files.length+' foto dipilih';
    var t=document.getElementById('thumbs');
    Array.prototype.forEach.call(inp.files,function(f){var i=document.createElement('img');i.src=URL.createObjectURL(f);i.alt='Foto baru';t.appendChild(i)});
}
</script>
@endsection
