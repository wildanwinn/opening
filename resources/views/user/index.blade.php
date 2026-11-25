@extends('layouts.app')
@section('judul', 'Daftar User')

@push('skrip')
<style>
    .panel-utama {
        width: 100%;
        overflow-x: auto;
    }
    .tabel-user { width: 100%; border-collapse: collapse; }
    
    .tabel-user th {
        text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px;
        color: var(--teks-mute); font-weight: 600;
        padding: .8rem 1rem; border-bottom: 1px solid var(--border);
    }
    
    .tabel-user td {
        padding: .9rem 1rem; border-bottom: 1px solid var(--border-soft); font-size: .88rem; vertical-align: middle;
        color: var(--teks) !important;
    }

    .tabel-user tr:hover td { 
        background: var(--bg-hover) !important; 
    }
    .tabel-user tr:last-child td { border-bottom: none; }

    /* Penyesuaian Kotak Pencarian agar Senada dan Gelap */
    .form-control {
        background-color: var(--bg-card) !important;
        border-color: var(--border) !important;
        color: var(--teks) !important;
    }
    .form-control:focus {
        background-color: var(--bg-card) !important;
        color: var(--teks) !important;
        border-color: var(--primary) !important;
        box-shadow: none !important;
    }
    .form-control::placeholder {
        color: var(--teks-mute) !important;
    }

    /* --- STYLING SWEETALERT2 YANG KONTRAS & JELAS --- */
    .swal2-popup {
        background: #2b2320 !important; 
        color: var(--teks) !important;
        border: 1px solid #4a3a32 !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.6) !important;
    }
    .swal2-title, 
    .swal2-html-container {
        color: var(--teks) !important;
    }

    .badge-role {
        display: inline-block;
        font-size: .7rem;
        font-weight: 600;
        padding: .3rem .7rem;
        border-radius: 20px;
    }
    .badge-admin        { background: rgba(220,53,69,.15);  color: #ff5d6c; }
    .badge-hd           { background: rgba(108,117,125,.18); color: var(--teks-mute); }
    .badge-teknisi      { background: rgba(46,211,145,.15); color: #2ed391; }
    .badge-edp          { background: rgba(109,147,255,.18); color: #6d93ff; }
    .badge-tsel         { background: rgba(109,147,255,.18); color: #6d93ff; }
    .badge-tif          { background: rgba(109,147,255,.18); color: #6d93ff; }
    .badge-service_area { background: rgba(245,166,35,.18); color: #f5a623; }
    .badge-oc3          { background: rgba(245,166,35,.18); color: #f5a623; }

    .tombol-aksi .btn { --bs-btn-padding-y: .25rem; --bs-btn-padding-x: .6rem; font-size: .8rem; }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">
    <div class="panel-utama p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">

        @include('user._tab')

        <!-- Header Judul & Tombol Tambah User -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="mb-0 fw-bold" style="color: var(--teks);">Daftar User</h5>
            <a href="{{ route('user.create') }}" class="btn btn-danger btn-sm px-3 py-2">
                <i class="bi bi-plus-lg me-1"></i> Tambah User
            </a>
        </div>

        <!-- Kotak Pencarian dengan Tombol Reset (X) -->
        <div class="row mb-4">
            <div class="col-12 col-md-5">
                <form action="{{ route('user.index') }}" method="GET" class="input-group input-group-sm">
                    <input type="text" name="cari" value="{{ request('cari') }}" class="form-control" placeholder="Cari nama atau NIK...">
                    <button class="btn btn-secondary" type="submit" title="Cari">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('cari'))
                        <a href="{{ route('user.index') }}" class="btn btn-outline-secondary" title="Reset Pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Tabel Daftar User -->
        <div class="table-responsive">
            <table class="tabel-user">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>ROLE</th>
                        <th>STO</th>
                        <th class="text-end" style="padding-right: 4.5rem;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td class="mono">{{ $u->nik }}</td>
                        <td>{{ $u->nama }}</td>
                        <td><span class="badge-role badge-{{ $u->role }}">{{ in_array($u->role, ['hd', 'tif', 'oc3', 'edp', 'tsel']) ? strtoupper($u->role) : ucwords(str_replace('_', ' ', $u->role)) }}</span></td>
                        <td style="color: var(--teks-mute);">{{ $u->sto->kode_sto ?? '-' }}</td>
                        <td class="text-end tombol-aksi">
                            <a href="{{ route('user.edit', $u) }}" class="btn btn-outline-secondary">Edit</a>
                            
                            <!-- Form Hapus dengan SweetAlert2 -->
                            <form id="form-hapus-{{ $u->id }}" method="POST" action="{{ route('user.destroy', $u) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="hapusUser('{{ $u->id }}', '{{ $u->nama }}')" class="btn btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4" style="color: var(--teks-mute);">Belum ada user.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</div>

<!-- Script SweetAlert2 & Auto Hide Alert (5 Detik) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function hapusUser(id, nama) {
    Swal.fire({
        title: 'Hapus User?',
        text: "Apakah Anda yakin ingin menghapus user " + nama + "?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        
        width: '320px',
        padding: '1rem',
        customClass: {
            popup: 'rounded-4 fs-6'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-hapus-' + id).submit();
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    let alertBox = document.querySelector('.alert, .alert-success, [class*="alert"]');
    
    if (alertBox) {
        setTimeout(function() {
            alertBox.style.transition = "opacity 0.5s ease";
            alertBox.style.opacity = "0";
            
            setTimeout(function() {
                alertBox.remove();
            }, 500); 
        }, 5000); 
    }
});
</script>
@endsection