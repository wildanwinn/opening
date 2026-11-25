@once
<style>
    .maskot-kucing { display: flex; justify-content: center; margin-bottom: .4rem; }
    .maskot-kucing .mk-badan { transform-origin: 60px 100px; animation: mk-loncat .9s ease-in-out infinite; }
    .maskot-kucing .mk-mata  { transform-origin: 60px 58px; animation: mk-kedip 3.2s infinite; }
    .maskot-kucing.selesai .mk-badan, .maskot-kucing.selesai .mk-mata { animation: none; }
    .maskot-kucing.pelan .mk-badan { animation-duration: 2.4s; }
    @keyframes mk-loncat { 0%,100% { transform: translateY(0) rotate(-2deg); } 50% { transform: translateY(-8px) rotate(2deg); } }
    @keyframes mk-kedip  { 0%,92%,100% { transform: scaleY(1); } 96% { transform: scaleY(.1); } }
    @media (prefers-reduced-motion: reduce) { .maskot-kucing .mk-badan, .maskot-kucing .mk-mata { animation: none; } }
</style>
@endonce
{{-- Maskot kucing (gambar asli buatan sendiri, SVG). Dipakai sebagai indikator loading. Beri class "selesai" untuk menghentikan animasinya. --}}
<div class="maskot-kucing {{ $kelas ?? '' }}" aria-hidden="true">
    <svg viewBox="0 0 120 110" width="{{ $ukuran ?? 96 }}" height="{{ round(($ukuran ?? 96) * 110 / 120) }}" xmlns="http://www.w3.org/2000/svg">
        <g class="mk-badan">
            {{-- telinga --}}
            <path d="M22 40 L26 8 L50 28 Z" fill="#F4A261"/><path d="M98 40 L94 8 L70 28 Z" fill="#F4A261"/>
            <path d="M28 34 L30 17 L43 28 Z" fill="#FFC9C9"/><path d="M92 34 L90 17 L77 28 Z" fill="#FFC9C9"/>
            {{-- kepala --}}
            <ellipse cx="60" cy="58" rx="42" ry="36" fill="#F4A261"/>
            <path d="M46 24 Q60 36 74 24 Q60 30 46 24Z" fill="#E07B39"/>
            <path d="M54 25 L54 33 M60 26 L60 35 M66 25 L66 33" stroke="#E07B39" stroke-width="3" stroke-linecap="round"/>
            {{-- mata --}}
            <g class="mk-mata">
                <ellipse cx="44" cy="58" rx="6" ry="7.5" fill="#2C2420"/><ellipse cx="76" cy="58" rx="6" ry="7.5" fill="#2C2420"/>
                <circle cx="46" cy="55.5" r="2" fill="#fff"/><circle cx="78" cy="55.5" r="2" fill="#fff"/>
            </g>
            {{-- pipi, hidung, mulut, kumis --}}
            <ellipse cx="33" cy="70" rx="6" ry="4" fill="#FF9AA2" opacity=".7"/><ellipse cx="87" cy="70" rx="6" ry="4" fill="#FF9AA2" opacity=".7"/>
            <path d="M56 67 L64 67 L60 72 Z" fill="#D9534F"/>
            <path d="M60 72 Q55 78 50 74 M60 72 Q65 78 70 74" stroke="#2C2420" stroke-width="2" fill="none" stroke-linecap="round"/>
            <path d="M24 64 L10 61 M24 69 L9 71 M96 64 L110 61 M96 69 L111 71" stroke="#2C2420" stroke-width="1.6" stroke-linecap="round"/>
            {{-- syal merah-putih --}}
            <path d="M24 80 Q60 106 96 80 L94 98 Q60 114 26 98 Z" fill="#C8222F"/>
            <path d="M40 92 L45 105 M60 97 L65 110 M80 92 L85 105" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
        </g>
    </svg>
</div>
