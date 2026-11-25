{{-- Tab Manajemen User: Semua User | Teknisi | Session Log --}}
<style>
    .tab-user { display: flex; gap: .4rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
    .tab-user a {
        padding: .6rem 1.1rem; font-size: .88rem; font-weight: 600; text-decoration: none;
        color: var(--teks-mute); border-bottom: 2px solid transparent; margin-bottom: -1px;
    }
    .tab-user a:hover { color: var(--teks); }
    .tab-user a.aktif { color: var(--primary); border-bottom-color: var(--primary); }
</style>
<div class="tab-user">
    <a href="{{ route('user.index') }}" class="{{ request()->routeIs('user.*') ? 'aktif' : '' }}"><i class="bi bi-person-badge me-1"></i> Semua User</a>
    <a href="{{ route('teknisi.index') }}" class="{{ request()->routeIs('teknisi.*') ? 'aktif' : '' }}"><i class="bi bi-people me-1"></i> Data Teknisi</a>
    <a href="{{ route('session.index') }}" class="{{ request()->routeIs('session.*') ? 'aktif' : '' }}"><i class="bi bi-shield-lock me-1"></i> Session Log</a>
</div>
