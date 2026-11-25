<?php
/**
 * Maskot paus (SVG) dengan animasi CSS.
 * $kelas: kelas tambahan (mis. lebar), $id: id elemen agar bisa diubah lewat JavaScript.
 * Mode: tambahkan kelas "maskot-fokus" atau "maskot-istirahat" pada elemen ini.
 */
function maskotPaus($kelas = '', $id = '') {
    $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES) . '"' : '';
    $svg = <<<'SVG'
<svg viewBox="0 0 240 190" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Maskot paus">
  <g class="paus-semprot" fill="#CFE8FA" stroke="#5798D7" stroke-width="2.5" stroke-linejoin="round">
    <path d="M78 40 C66 28 66 14 78 4 C90 14 90 28 78 40 Z"/>
    <path d="M58 46 C44 40 42 28 48 18 C60 24 64 36 58 46 Z"/>
    <path d="M98 46 C112 40 114 28 108 18 C96 24 92 36 98 46 Z"/>
  </g>
  <g class="gelembung" fill="#E6F1FA" stroke="#5798D7" stroke-width="2">
    <circle cx="16" cy="104" r="5"/><circle cx="8" cy="86" r="3.5"/><circle cx="18" cy="70" r="3"/>
  </g>
  <g class="zzz" fill="#5798D7" font-weight="800" font-family="sans-serif">
    <text x="136" y="40" font-size="20">Z</text><text x="154" y="22" font-size="15">z</text>
  </g>
  <g class="paus-badan">
    <g class="paus-ekor">
      <path d="M184 112 C206 108 220 88 220 60 C206 64 196 72 190 82 C190 70 184 58 174 52 C172 78 174 98 184 112 Z" fill="#5798D7" stroke="#2F5F94" stroke-width="5" stroke-linejoin="round"/>
    </g>
    <path d="M26 112 C22 76 52 52 94 52 C140 52 176 74 184 108 C186 142 146 166 100 166 C56 166 28 148 26 112 Z" fill="#5798D7" stroke="#2F5F94" stroke-width="5" stroke-linejoin="round"/>
    <path d="M34 120 C50 150 96 160 134 152 C158 146 176 132 182 116 C150 136 96 140 34 120 Z" fill="#FBEFD5"/>
    <path d="M60 138 C78 148 108 150 130 144" fill="none" stroke="#E9D6AE" stroke-width="3" stroke-linecap="round"/>
    <path d="M40 78 C48 66 66 60 84 60" fill="none" stroke="#8FC1EC" stroke-width="5" stroke-linecap="round" opacity=".7"/>
    <g class="paus-mata">
      <ellipse cx="66" cy="108" rx="9" ry="12" fill="#1F3A5A"/><circle cx="63" cy="103" r="3.6" fill="#fff"/>
      <ellipse cx="108" cy="104" rx="9" ry="12" fill="#1F3A5A"/><circle cx="105" cy="99" r="3.6" fill="#fff"/>
    </g>
    <circle cx="44" cy="124" r="9" fill="#FF9DBF" opacity=".75"/>
    <circle cx="132" cy="118" r="9" fill="#FF9DBF" opacity=".75"/>
    <path d="M74 126 C82 136 94 136 102 126" fill="none" stroke="#1F3A5A" stroke-width="4" stroke-linecap="round"/>
    <path d="M150 140 C160 156 176 158 182 150 C174 148 166 140 162 128 Z" fill="#7FB3E3" stroke="#2F5F94" stroke-width="4" stroke-linejoin="round"/>
  </g>
</svg>
SVG;
    return '<div class="maskot-paus ' . htmlspecialchars($kelas, ENT_QUOTES) . '"' . $idAttr . '>' . $svg . '</div>';
}

/**
 * Paus menghadap depan: berkedip, sirip melambai, semprotan air di atas kepala.
 */
function maskotPausDepan($kelas = '') {
    $svg = <<<'SVG'
<svg viewBox="0 0 240 232" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Maskot paus menghadap depan">
  <g class="pd-semprot" fill="#CFE8FA" stroke="#5798D7" stroke-width="2.5" stroke-linejoin="round">
    <path d="M120 38 C108 26 108 12 120 2 C132 12 132 26 120 38 Z"/>
    <path d="M98 44 C84 38 82 26 88 16 C100 22 104 34 98 44 Z"/>
    <path d="M142 44 C156 38 158 26 152 16 C140 22 136 34 142 44 Z"/>
  </g>
  <g class="pd-badan">
    <path d="M100 206 C90 224 72 226 60 214 C74 214 84 206 90 194 Z" fill="#5798D7" stroke="#2F5F94" stroke-width="5" stroke-linejoin="round"/>
    <path d="M140 206 C150 224 168 226 180 214 C166 214 156 206 150 194 Z" fill="#5798D7" stroke="#2F5F94" stroke-width="5" stroke-linejoin="round"/>
    <g class="pd-sirip-kiri"><ellipse cx="36" cy="140" rx="17" ry="30" transform="rotate(24 36 140)" fill="#7FB3E3" stroke="#2F5F94" stroke-width="5"/></g>
    <g class="pd-sirip-kanan"><ellipse cx="204" cy="140" rx="17" ry="30" transform="rotate(-24 204 140)" fill="#7FB3E3" stroke="#2F5F94" stroke-width="5"/></g>
    <ellipse cx="120" cy="124" rx="90" ry="86" fill="#5798D7" stroke="#2F5F94" stroke-width="5"/>
    <ellipse cx="120" cy="172" rx="54" ry="36" fill="#FBEFD5"/>
    <path d="M60 84 C72 60 98 48 122 48" fill="none" stroke="#8FC1EC" stroke-width="6" stroke-linecap="round" opacity=".7"/>
    <g class="pd-mata">
      <ellipse cx="88" cy="106" rx="10" ry="14" fill="#1F3A5A"/><circle cx="85" cy="100" r="4" fill="#fff"/>
      <ellipse cx="152" cy="106" rx="10" ry="14" fill="#1F3A5A"/><circle cx="149" cy="100" r="4" fill="#fff"/>
    </g>
    <circle cx="66" cy="134" r="10" fill="#FF9DBF" opacity=".75"/>
    <circle cx="174" cy="134" r="10" fill="#FF9DBF" opacity=".75"/>
    <path d="M104 130 C112 144 128 144 136 130" fill="none" stroke="#1F3A5A" stroke-width="4" stroke-linecap="round"/>
  </g>
</svg>
SVG;
    return '<div class="paus-depan ' . htmlspecialchars($kelas, ENT_QUOTES) . '">' . $svg . '</div>';
}

/**
 * Tampilan "kosong" dengan paus. $pesan dan $tombolHtml harus berupa HTML tepercaya.
 */
function keadaanKosong($pesan, $tombolHtml = '') {
    return '<div class="flex flex-col items-center text-center py-4">'
        . maskotPausDepan('w-20')
        . '<p class="mt-2 text-sm text-slate-500 max-w-xs">' . $pesan . '</p>'
        . $tombolHtml
        . '</div>';
}

/**
 * Gaya CSS maskot dan latar laut. Panggil di <head> pada halaman yang memakai maskotPaus() atau latarLaut().
 */
function maskotGayaCss() {
    return <<<'CSS'
<style>
        /* ===== Maskot paus ===== */
        .maskot-paus svg { width: 100%; height: auto; display: block; overflow: visible; }
        .paus-badan { transform-box: fill-box; transform-origin: 50% 70%; animation: pausMelayang 3.8s ease-in-out infinite; }
        .paus-ekor { transform-box: fill-box; transform-origin: 0% 100%; animation: pausEkor 2.6s ease-in-out infinite; }
        .paus-semprot path { transform-box: fill-box; transform-origin: 50% 100%; opacity: 0; animation: pausSemprot 3s ease-out infinite; }
        .paus-semprot path:nth-child(2) { animation-delay: .3s; }
        .paus-semprot path:nth-child(3) { animation-delay: .6s; }
        .paus-mata { transform-box: fill-box; transform-origin: 50% 50%; animation: pausKedip 5s infinite; }
        .gelembung { display: none; }
        .gelembung circle { opacity: 0; animation: pausGelembung 2.4s ease-in infinite; }
        .gelembung circle:nth-child(2) { animation-delay: .8s; }
        .gelembung circle:nth-child(3) { animation-delay: 1.6s; }
        .zzz { opacity: 0; }
        .maskot-paus:hover .paus-badan { animation-duration: 1.4s; }
        .maskot-fokus .paus-badan { animation-duration: 1.6s; }
        .maskot-fokus .paus-ekor { animation-duration: .9s; }
        .maskot-fokus .paus-semprot path { animation-duration: 1.6s; }
        .maskot-fokus .gelembung { display: block; }
        .maskot-istirahat .paus-badan { animation-duration: 6s; }
        .maskot-istirahat .paus-mata { animation: none; transform: scaleY(.12); }
        .maskot-istirahat .paus-semprot { display: none; }
        .maskot-istirahat .zzz { animation: pausZzz 2.4s ease-in-out infinite; }
        @keyframes pausMelayang { 0%, 100% { transform: translateY(0) rotate(-1deg); } 50% { transform: translateY(-8px) rotate(1.5deg); } }
        @keyframes pausEkor { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(-12deg); } }
        @keyframes pausSemprot { 0% { transform: translateY(10px) scale(.3); opacity: 0; } 30% { opacity: 1; } 100% { transform: translateY(-10px) scale(1); opacity: 0; } }
        @keyframes pausKedip { 0%, 92%, 100% { transform: scaleY(1); } 95% { transform: scaleY(.1); } }
        @keyframes pausGelembung { 0% { transform: translateY(0); opacity: 0; } 20% { opacity: .9; } 100% { transform: translateY(-26px); opacity: 0; } }
        @keyframes pausZzz { 0%, 100% { opacity: .2; transform: translateY(4px); } 50% { opacity: 1; transform: translateY(-4px); } }
        
        /* ===== Latar laut (login dan daftar) ===== */
        .latar-laut { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
        .laut-paus { position: absolute; left: 4vw; bottom: 26px; width: clamp(96px, 12vw, 150px); opacity: .92; animation: pausBerenang 30s ease-in-out infinite; }
        .laut-ombak { position: absolute; left: 0; bottom: 0; width: 200%; height: 110px; }
        .laut-ombak-1 { animation: ombakGeser 16s linear infinite; }
        .laut-ombak-2 { bottom: -10px; height: 92px; animation: ombakGeser 10s linear infinite reverse; }
        .laut-gelembung span { position: absolute; bottom: -12px; border-radius: 50%; background: rgba(255, 255, 255, .28); border: 1px solid rgba(255, 255, 255, .55); animation: naikGelembung 10s ease-in infinite; }
        .laut-gelembung span:nth-child(1) { left: 14%; width: 10px; height: 10px; animation-delay: 0s; }
        .laut-gelembung span:nth-child(2) { left: 32%; width: 16px; height: 16px; animation-delay: 2.5s; animation-duration: 12s; }
        .laut-gelembung span:nth-child(3) { left: 55%; width: 8px; height: 8px; animation-delay: 5s; }
        .laut-gelembung span:nth-child(4) { left: 74%; width: 14px; height: 14px; animation-delay: 1.5s; animation-duration: 13s; }
        .laut-gelembung span:nth-child(5) { left: 90%; width: 9px; height: 9px; animation-delay: 6.5s; }
        @keyframes pausBerenang {
            0% { transform: translateX(0) scaleX(-1); }
            47% { transform: translateX(34vw) scaleX(-1); }
            50% { transform: translateX(34vw) scaleX(1); }
            97% { transform: translateX(0) scaleX(1); }
            100% { transform: translateX(0) scaleX(-1); }
        }
        @keyframes ombakGeser { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        @keyframes naikGelembung { 0% { transform: translateY(0); opacity: 0; } 12% { opacity: 1; } 100% { transform: translateY(-75vh); opacity: 0; } }

        /* ===== Paus menghadap depan ===== */
        .paus-depan svg { width: 100%; height: auto; display: block; overflow: visible; }
        .pd-badan { transform-box: fill-box; transform-origin: 50% 85%; animation: pdMelayang 4s ease-in-out infinite; }
        .pd-mata { transform-box: fill-box; transform-origin: 50% 50%; animation: pausKedip 4s infinite; }
        .pd-sirip-kiri { transform-box: fill-box; transform-origin: 70% 15%; animation: pdSiripKiri 3s ease-in-out infinite; }
        .pd-sirip-kanan { transform-box: fill-box; transform-origin: 30% 15%; animation: pdSiripKanan 3s ease-in-out infinite; }
        .pd-semprot path { transform-box: fill-box; transform-origin: 50% 100%; opacity: 0; animation: pausSemprot 3s ease-out infinite; }
        .pd-semprot path:nth-child(2) { animation-delay: .3s; }
        .pd-semprot path:nth-child(3) { animation-delay: .6s; }
        .paus-depan:hover .pd-badan { animation-duration: 1.6s; }
        @keyframes pdMelayang { 0%, 100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-10px) scale(1.02); } }
        @keyframes pdSiripKiri { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(14deg); } }
        @keyframes pdSiripKanan { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(-14deg); } }
        @media (prefers-reduced-motion: reduce) { .maskot-paus *, .latar-laut *, .paus-depan * { animation: none !important; } }
</style>
CSS;
}

/**
 * Latar laut: ombak bergerak, gelembung naik, dan paus berenang kanan-kiri.
 * Taruh sebagai anak pertama elemen berposisi relative dan overflow-hidden.
 */
function latarLaut() {
    $ombak1 = '<svg class="laut-ombak laut-ombak-1" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M0 60 C100 20 200 100 300 60 C400 20 500 100 600 60 C700 20 800 100 900 60 C1000 20 1100 100 1200 60 L1200 120 L0 120 Z" fill="rgba(255,255,255,0.16)"/></svg>';
    $ombak2 = '<svg class="laut-ombak laut-ombak-2" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M0 70 C100 40 200 100 300 70 C400 40 500 100 600 70 C700 40 800 100 900 70 C1000 40 1100 100 1200 70 L1200 120 L0 120 Z" fill="rgba(255,255,255,0.26)"/></svg>';
    return '<div class="latar-laut" aria-hidden="true">'
        . '<div class="laut-gelembung"><span></span><span></span><span></span><span></span><span></span></div>'
        . '<div class="laut-paus">' . maskotPaus('w-full') . '</div>'
        . $ombak1 . $ombak2
        . '</div>';
}
