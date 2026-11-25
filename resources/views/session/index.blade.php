@extends('layouts.app')
@section('judul', 'Session Log')

@push('skrip')
<style>
    .tabel-data { width: 100%; border-collapse: collapse; }
    .tabel-data th { text-align: left; font-size: .72rem; text-transform: uppercase; color: var(--teks-mute); padding: .8rem 1rem; border-bottom: 1px solid var(--border); }
    .tabel-data td { padding: .9rem 1rem; border-bottom: 1px solid var(--border-soft); font-size: .86rem; color: var(--teks) !important; }
    .tabel-data tr:hover td { background: var(--bg-hover) !important; }
    .form-control { background-color: var(--bg-card) !important; border-color: var(--border) !important; color: var(--teks) !important; }
    .form-control:focus { border-color: var(--primary) !important; box-shadow: none !important; }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0">
    <div class="p-4 rounded-4 shadow-sm" style="background: var(--bg-card); border: 1px solid var(--border);">

        @include('user._tab')

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-bold" style="color: var(--teks);">Session Log</h5>
            <small style="color: var(--teks-mute);">Total: {{ $sessions->total() }} session</small>
        </div>

        <form action="{{ route('session.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-12 col-md-5">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm" placeholder="Cari nama / NIK / IP...">
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button class="btn btn-danger btn-sm w-100"><i class="bi bi-search"></i> Cari</button>
                @if(request('cari'))
                    <a href="{{ route('session.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>NIK</th>
                        <th>Role</th>
                        <th>IP Address</th>
                        <th>Browser</th>
                        <th>Device</th>
                        <th>Aktivitas Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($sessions as $s)
                    <tr>
                        <td><strong>{{ $s->user_nama ? strtoupper($s->user_nama) : 'Guest' }}</strong></td>
                        <td>{{ $s->user_nik ?? '-' }}</td>
                        <td>{{ $s->user_role ? ucfirst($s->user_role) : '-' }}</td>
                        <td style="font-family: monospace;">{{ $s->ip_address ?? '-' }}</td>
                        <td>{{ $s->browser }}</td>
                        <td>{{ $s->device }}</td>
                        <td style="color: var(--teks-mute);">{{ $s->last_activity_formatted }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4" style="color: var(--teks-mute);">Belum ada session.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $sessions->links() }}</div>
    </div>
</div>
@endsection