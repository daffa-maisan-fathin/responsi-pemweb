<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Ruang Belajar Pribadi — Questudy';
$userId = $_SESSION['id_user'];
$activeTab = $_GET['tab'] ?? 'pomodoro'; // pomodoro, flashcard, planner, statistik

// Handle AJAX / POST Selesai Sesi Pomodoro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_pomodoro') {
    $durasi = (int)($_POST['durasi_menit'] ?? 25);
    $stmtP = $conn->prepare("INSERT INTO sesi_pomodoro (id_pelajar, durasi_menit, tanggal, created_at) VALUES (?, ?, CURDATE(), NOW())");
    $stmtP->execute([$userId, $durasi]);

    // Beri XP
    $rw = tambahXP($conn, $userId, 'sesi_pomodoro');
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'xp' => $rw['xp_didapat'] ?? 15]);
        exit();
    }
    setFlash('success', 'Selamat! Sesi fokus Pomodoro 25 menit selesai. +15 XP ditambahkan.');
    header("Location: " . base_url('pelajar/ruang_belajar.php?tab=pomodoro'));
    exit();
}

// Handle CRUD Flashcard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_flashcard') {
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $jawaban = trim($_POST['jawaban'] ?? '');
    if (!empty($pertanyaan) && !empty($jawaban)) {
        $stmtFC = $conn->prepare("INSERT INTO flashcards (id_pelajar, pertanyaan, jawaban, status_hafal, created_at) VALUES (?, ?, ?, 'belum', NOW())");
        $stmtFC->execute([$userId, $pertanyaan, $jawaban]);
        setFlash('success', 'Flashcard baru berhasil ditambahkan!');
    }
    header("Location: " . base_url('pelajar/ruang_belajar.php?tab=flashcard'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status_flashcard') {
    $idCard = (int)$_POST['id_card'];
    $statusHafal = ($_POST['status'] === 'hafal') ? 'hafal' : 'belum';
    $stmtFCUp = $conn->prepare("UPDATE flashcards SET status_hafal = ? WHERE id_card = ? AND id_pelajar = ?");
    $stmtFCUp->execute([$statusHafal, $idCard, $userId]);
    setFlash('success', 'Status hafalan kartu berhasil diperbarui.');
    header("Location: " . base_url('pelajar/ruang_belajar.php?tab=flashcard'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_flashcard') {
    $idCard = (int)$_POST['id_card'];
    $stmtFCDel = $conn->prepare("DELETE FROM flashcards WHERE id_card = ? AND id_pelajar = ?");
    $stmtFCDel->execute([$idCard, $userId]);
    setFlash('info', 'Flashcard berhasil dihapus.');
    header("Location: " . base_url('pelajar/ruang_belajar.php?tab=flashcard'));
    exit();
}

// Handle CRUD Planner Target
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_planner') {
    $target = trim($_POST['judul_target'] ?? '');
    $tgl = $_POST['tanggal_target'] ?? date('Y-m-d');
    if (!empty($target)) {
        $stmtPlan = $conn->prepare("INSERT INTO planner (id_pelajar, judul_target, tanggal_target, status, created_at) VALUES (?, ?, ?, 'belum', NOW())");
        $stmtPlan->execute([$userId, $target, $tgl]);
        setFlash('success', 'Target belajar baru berhasil dijadwalkan!');
    }
    header("Location: " . base_url('pelajar/ruang_belajar.php?tab=planner'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_planner') {
    $idPlan = (int)$_POST['id_plan'];
    $currentStatus = $_POST['current_status'] ?? 'belum';
    $newStatus = ($currentStatus === 'selesai') ? 'belum' : 'selesai';
    $stmtPlanT = $conn->prepare("UPDATE planner SET status = ? WHERE id_plan = ? AND id_pelajar = ?");
    $stmtPlanT->execute([$newStatus, $idPlan, $userId]);
    header("Location: " . base_url('pelajar/ruang_belajar.php?tab=planner'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_planner') {
    $idPlan = (int)$_POST['id_plan'];
    $stmtPlanD = $conn->prepare("DELETE FROM planner WHERE id_plan = ? AND id_pelajar = ?");
    $stmtPlanD->execute([$idPlan, $userId]);
    setFlash('info', 'Target belajar dihapus.');
    header("Location: " . base_url('pelajar/ruang_belajar.php?tab=planner'));
    exit();
}

// Data Tab 1: Pomodoro
$stmtPomoToday = $conn->prepare("SELECT COUNT(*) AS total_today FROM sesi_pomodoro WHERE id_pelajar = ? AND tanggal = CURDATE()");
$stmtPomoToday->execute([$userId]);
$pomoCountToday = (int)$stmtPomoToday->fetch()['total_today'];

// Data Tab 2: Flashcards
$stmtCards = $conn->prepare("SELECT * FROM flashcards WHERE id_pelajar = ? ORDER BY id_card ASC");
$stmtCards->execute([$userId]);
$flashcards = $stmtCards->fetchAll();

// Data Tab 3: Planner (Gabungan Tugas, Kuis & Target Pribadi)
$stmtTarget = $conn->prepare("SELECT * FROM planner WHERE id_pelajar = ? ORDER BY tanggal_target ASC");
$stmtTarget->execute([$userId]);
$daftarTarget = $stmtTarget->fetchAll();

$stmtTugasUpcoming = $conn->prepare("
    SELECT t.judul, t.tenggat_waktu, k.nama_kelas, 'tugas' AS tipe
    FROM tugas t
    JOIN kelas k ON t.id_kelas = k.id_kelas
    JOIN anggota_kelas ak ON k.id_kelas = ak.id_kelas
    WHERE ak.id_pelajar = ? AND t.tenggat_waktu >= NOW()
    ORDER BY t.tenggat_waktu ASC LIMIT 5
");
$stmtTugasUpcoming->execute([$userId]);
$upcomingDeadlines = $stmtTugasUpcoming->fetchAll();

// Data Tab 4: Statistik
$stmtPomoTotal = $conn->prepare("SELECT SUM(durasi_menit) AS total_menit, COUNT(*) AS total_sesi FROM sesi_pomodoro WHERE id_pelajar = ?");
$stmtPomoTotal->execute([$userId]);
$pomoTotal = $stmtPomoTotal->fetch();
$totalMenitFokus = (int)($pomoTotal['total_menit'] ?? 0);
$totalJamFokus = round($totalMenitFokus / 60, 1);
$totalSesiFokus = (int)($pomoTotal['total_sesi'] ?? 0);

$stmtFCStats = $conn->prepare("
    SELECT 
        SUM(CASE WHEN status_hafal = 'hafal' THEN 1 ELSE 0 END) AS hafal,
        SUM(CASE WHEN status_hafal = 'belum' THEN 1 ELSE 0 END) AS belum
    FROM flashcards WHERE id_pelajar = ?
");
$stmtFCStats->execute([$userId]);
$fcStats = $stmtFCStats->fetch();
$totalHafal = (int)($fcStats['hafal'] ?? 0);
$totalBelum = (int)($fcStats['belum'] ?? 0);

// Aktivitas 7 hari terakhir untuk grafik batang
$hariAktivitas = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $namaHari = date('D', strtotime($tgl));
    $namaHariIndo = ['Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab','Sun'=>'Min'][$namaHari] ?? $namaHari;

    $stmtDay = $conn->prepare("SELECT SUM(durasi_menit) AS menit FROM sesi_pomodoro WHERE id_pelajar = ? AND tanggal = ?");
    $stmtDay->execute([$userId, $tgl]);
    $dur = (int)($stmtDay->fetch()['menit'] ?? 0);

    $hariAktivitas[] = [
        'tanggal' => $tgl,
        'hari' => $namaHariIndo,
        'menit' => $dur
    ];
}
$maxMenitGrafik = max(60, max(array_column($hariAktivitas, 'menit')));

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screens 04-07) -->
<div class="space-y-1 mb-6">
    <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Ruang Belajar Pribadi</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Ruang Belajar Terfokus & Mandiri</h1>
        </div>
    </div>
</div>

<!-- NAVIGASI 4 TAB INTERAKTIF -->
<div class="bg-white rounded-xl border border-[#E2E8F0] p-1.5 shadow-sm mb-6 flex flex-wrap gap-1">
    <a href="?tab=pomodoro" class="flex-1 min-w-[130px] text-center py-2.5 px-4 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 <?= ($activeTab === 'pomodoro') ? 'bg-[#0066CC] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'; ?>">
        <i class="fa-solid fa-stopwatch"></i>
        <span>1. Pomodoro</span>
    </a>
    <a href="?tab=flashcard" class="flex-1 min-w-[130px] text-center py-2.5 px-4 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 <?= ($activeTab === 'flashcard') ? 'bg-[#0066CC] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'; ?>">
        <i class="fa-solid fa-layer-group"></i>
        <span>2. Flashcards</span>
    </a>
    <a href="?tab=planner" class="flex-1 min-w-[130px] text-center py-2.5 px-4 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 <?= ($activeTab === 'planner') ? 'bg-[#0066CC] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'; ?>">
        <i class="fa-regular fa-calendar-check"></i>
        <span>3. Planner</span>
    </a>
    <a href="?tab=statistik" class="flex-1 min-w-[130px] text-center py-2.5 px-4 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 <?= ($activeTab === 'statistik') ? 'bg-[#0066CC] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'; ?>">
        <i class="fa-solid fa-chart-column"></i>
        <span>4. Statistik</span>
    </a>
</div>

<!-- ================= TAB 1: POMODORO (Screen 04) ================= -->
<?php if ($activeTab === 'pomodoro'): ?>
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl border border-[#E2E8F0] p-8 shadow-sm text-center space-y-6">
            
            <!-- Mode Switch -->
            <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200">
                <button type="button" id="btnModeFokus" class="px-5 py-2 rounded-lg text-xs font-bold bg-[#0066CC] text-white shadow-sm transition">
                    Fokus Belajar (25m)
                </button>
                <button type="button" id="btnModeIstirahat" class="px-5 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                    Istirahat Singkat (5m)
                </button>
            </div>

            <!-- Maskot Paus -->
            <div class="flex flex-col items-center">
                <?= maskotPaus('w-44 sm:w-52', 'maskotPomo'); ?>
                <div id="maskotBicara" class="mt-2 px-4 py-1.5 rounded-full bg-sky-100 text-indigo-700 text-sm font-semibold">Halo! Siap belajar?</div>
                <div class="mt-3 flex items-center gap-3 px-4 py-2 rounded-full bg-white border border-indigo-100 shadow-sm">
                    <button type="button" id="btnSuaraAir" class="flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800 transition whitespace-nowrap">
                        <i class="fa-solid fa-water"></i><span>Suara air</span><span id="labelSuaraAir" class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-500">Mati</span>
                    </button>
                    <input type="range" id="volSuaraAir" min="0" max="100" value="50" class="w-24 accent-indigo-500" aria-label="Volume suara air">
                </div>
            </div>

            <!-- Big Digital Timer Display -->
            <div class="py-2">
                <div id="pomoTimeDisplay" class="text-7xl sm:text-8xl font-black font-mono tracking-wider text-slate-800 drop-shadow-sm">
                    25:00
                </div>
                <p id="pomoStatusText" class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-3">
                    Siap Memulai Sesi Belajar
                </p>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center justify-center gap-3">
                <button type="button" id="pomoStartBtn" class="px-8 py-3 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-sm font-bold shadow-lg shadow-blue-500/20 transition flex items-center gap-2">
                    <i class="fa-solid fa-play"></i>
                    <span>Mulai</span>
                </button>
                <button type="button" id="pomoPauseBtn" class="px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold shadow transition hidden flex items-center gap-2">
                    <i class="fa-solid fa-pause"></i>
                    <span>Jeda</span>
                </button>
                <button type="button" id="pomoResetBtn" class="px-6 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset</span>
                </button>
            </div>

            <!-- Today's Session Count & XP Incentive -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Sesi Tuntas Hari Ini: <strong class="text-slate-800 font-bold"><?= $pomoCountToday; ?> Sesi</strong> (<?= $pomoCountToday * 25; ?> Menit)</span>
                <span class="text-amber-600 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-bolt"></i> +15 XP per sesi 25 menit
                </span>
            </div>
        </div>
    </div>

    <!-- Hidden Form for saving completed session -->
    <form id="formSavePomodoro" action="" method="POST" class="hidden">
        <input type="hidden" name="action" value="simpan_pomodoro">
        <input type="hidden" name="durasi_menit" value="25">
    </form>

    <script>
        let currentMode = 'fokus'; // fokus (25m) atau istirahat (5m)
        let totalSeconds = 25 * 60;
        let timerInterval = null;
        let isRunning = false;

        const timeDisplay = document.getElementById('pomoTimeDisplay');
        const statusText = document.getElementById('pomoStatusText');
        const startBtn = document.getElementById('pomoStartBtn');
        const pauseBtn = document.getElementById('pomoPauseBtn');
        const resetBtn = document.getElementById('pomoResetBtn');
        const btnFokus = document.getElementById('btnModeFokus');
        const btnIstirahat = document.getElementById('btnModeIstirahat');
        const formSave = document.getElementById('formSavePomodoro');

        // Maskot paus: mode '' (santai), 'fokus', atau 'istirahat'
        function setMaskot(mode, teks) {
            const m = document.getElementById('maskotPomo');
            const b = document.getElementById('maskotBicara');
            if (m) {
                m.classList.remove('maskot-fokus', 'maskot-istirahat');
                if (mode) m.classList.add('maskot-' + mode);
            }
            if (b && teks) b.textContent = teks;
            if (mode) { suaraAir.putarJikaAktif(); } else { suaraAir.henti(); }
        }

        // Suara air yang menenangkan, dibuat langsung di browser (tanpa file audio)
        const suaraAir = (function () {
            let ctx = null, master = null, sumber = [], gains = [], tetes = null, preview = null;
            let aktif = false, main = false;
            try { aktif = localStorage.getItem('suaraAir') === '1'; } catch (e) {}
            const tombol = document.getElementById('btnSuaraAir');
            const label = document.getElementById('labelSuaraAir');
            const slider = document.getElementById('volSuaraAir');

            function sedangBelajar() {
                const m = document.getElementById('maskotPomo');
                return !!m && (m.classList.contains('maskot-fokus') || m.classList.contains('maskot-istirahat'));
            }
            function perbaruiTombol() {
                label.textContent = aktif ? 'Hidup' : 'Mati';
                label.className = 'px-2 py-0.5 rounded-full text-xs ' + (aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500');
            }
            function aturVolume() { if (master) master.gain.value = (slider.value / 100) * 0.8; }
            function derau(jenis) {
                const panjang = ctx.sampleRate * 4;
                const buf = ctx.createBuffer(2, panjang, ctx.sampleRate);
                for (let c = 0; c < 2; c++) {
                    const d = buf.getChannelData(c);
                    let last = 0;
                    for (let i = 0; i < panjang; i++) {
                        const w = Math.random() * 2 - 1;
                        if (jenis === 'cokelat') { last = (last + 0.02 * w) / 1.02; d[i] = last * 3.5; } else { d[i] = w; }
                    }
                }
                return buf;
            }
            function tetesAir() {
                if (!main) return;
                const t = ctx.currentTime;
                const o = ctx.createOscillator();
                o.type = 'sine';
                const f = 380 + Math.random() * 520;
                o.frequency.setValueAtTime(f, t);
                o.frequency.exponentialRampToValueAtTime(f * 1.9, t + 0.09);
                const g = ctx.createGain();
                g.gain.setValueAtTime(0.0001, t);
                g.gain.exponentialRampToValueAtTime(0.12, t + 0.015);
                g.gain.exponentialRampToValueAtTime(0.0001, t + 0.22);
                o.connect(g); g.connect(master);
                o.start(t); o.stop(t + 0.25);
            }
            function jadwalkanTetes() {
                clearTimeout(tetes);
                tetes = setTimeout(function () { tetesAir(); jadwalkanTetes(); }, 900 + Math.random() * 3200);
            }
            function mulai() {
                if (main) return;
                const AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) return;
                if (!ctx) { ctx = new AC(); master = ctx.createGain(); master.connect(ctx.destination); }
                if (ctx.state === 'suspended') ctx.resume();
                aturVolume();
                const t = ctx.currentTime;

                const a = ctx.createBufferSource(); a.buffer = derau('cokelat'); a.loop = true;
                const lp = ctx.createBiquadFilter(); lp.type = 'lowpass'; lp.frequency.value = 650;
                const g1 = ctx.createGain(); g1.gain.setValueAtTime(0, t); g1.gain.linearRampToValueAtTime(0.9, t + 2);
                const lfo = ctx.createOscillator(); lfo.frequency.value = 0.12;
                const lfoG = ctx.createGain(); lfoG.gain.value = 220;
                lfo.connect(lfoG); lfoG.connect(lp.frequency);
                a.connect(lp); lp.connect(g1); g1.connect(master);

                const b = ctx.createBufferSource(); b.buffer = derau('putih'); b.loop = true;
                const bp = ctx.createBiquadFilter(); bp.type = 'bandpass'; bp.frequency.value = 1700; bp.Q.value = 0.8;
                const g2 = ctx.createGain(); g2.gain.setValueAtTime(0, t); g2.gain.linearRampToValueAtTime(0.05, t + 2.5);
                const lfo2 = ctx.createOscillator(); lfo2.frequency.value = 0.3;
                const lfo2G = ctx.createGain(); lfo2G.gain.value = 0.03;
                lfo2.connect(lfo2G); lfo2G.connect(g2.gain);
                b.connect(bp); bp.connect(g2); g2.connect(master);

                [a, b, lfo, lfo2].forEach(function (n) { n.start(); });
                sumber = [a, b, lfo, lfo2];
                gains = [g1, g2];
                main = true;
                jadwalkanTetes();
            }
            function henti() {
                clearTimeout(preview);
                if (!main) return;
                main = false;
                clearTimeout(tetes);
                const t = ctx.currentTime;
                gains.forEach(function (g) {
                    g.gain.cancelScheduledValues(t);
                    g.gain.setValueAtTime(g.gain.value, t);
                    g.gain.linearRampToValueAtTime(0, t + 0.8);
                });
                const lama = sumber;
                sumber = [];
                setTimeout(function () { lama.forEach(function (n) { try { n.stop(); } catch (e) {} }); }, 1000);
            }
            function putarJikaAktif() { clearTimeout(preview); if (aktif) mulai(); }

            tombol.addEventListener('click', function () {
                aktif = !aktif;
                try { localStorage.setItem('suaraAir', aktif ? '1' : '0'); } catch (e) {}
                perbaruiTombol();
                if (aktif) {
                    mulai();
                    if (!sedangBelajar()) {
                        // pratinjau singkat agar pengguna bisa mendengar suaranya
                        preview = setTimeout(function () { if (!sedangBelajar()) henti(); }, 6000);
                    }
                } else {
                    henti();
                }
            });
            slider.addEventListener('input', aturVolume);
            perbaruiTombol();
            return { mulai: mulai, henti: henti, putarJikaAktif: putarJikaAktif };
        })();

        function renderTime() {
            const m = Math.floor(totalSeconds / 60);
            const s = totalSeconds % 60;
            timeDisplay.textContent = 
                (m < 10 ? '0' : '') + m + ':' + 
                (s < 10 ? '0' : '') + s;
        }

        btnFokus.addEventListener('click', () => {
            if (isRunning) return;
            currentMode = 'fokus';
            totalSeconds = 25 * 60;
            btnFokus.className = 'px-5 py-2 rounded-lg text-xs font-bold bg-[#0066CC] text-white shadow-sm transition';
            btnIstirahat.className = 'px-5 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition';
            statusText.textContent = 'Siap Memulai Sesi Belajar';
            setMaskot('', 'Siap fokus?');
            renderTime();
        });

        btnIstirahat.addEventListener('click', () => {
            if (isRunning) return;
            currentMode = 'istirahat';
            totalSeconds = 5 * 60;
            btnIstirahat.className = 'px-5 py-2 rounded-lg text-xs font-bold bg-[#10B981] text-white shadow-sm transition';
            btnFokus.className = 'px-5 py-2 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition';
            statusText.textContent = 'Mode Istirahat Singkat';
            setMaskot('', 'Mau istirahat sebentar?');
            renderTime();
        });

        startBtn.addEventListener('click', () => {
            if (!isRunning) {
                isRunning = true;
                startBtn.classList.add('hidden');
                pauseBtn.classList.remove('hidden');
                statusText.textContent = (currentMode === 'fokus') ? 'Fokus Sedang Berjalan... Tetap Semangat!' : 'Istirahatkan Pikiran Anda...';
                setMaskot(currentMode === 'fokus' ? 'fokus' : 'istirahat', currentMode === 'fokus' ? 'Ayo fokus, kamu pasti bisa!' : 'Santai dulu ya, tarik napas.');

                timerInterval = setInterval(() => {
                    totalSeconds--;
                    renderTime();

                    if (totalSeconds <= 0) {
                        clearInterval(timerInterval); suaraAir.henti();
                        isRunning = false;
                        startBtn.classList.remove('hidden');
                        pauseBtn.classList.add('hidden');

                        if (currentMode === 'fokus') {
                            alert('Luar biasa! Anda telah menyelesaikan 25 menit sesi fokus belajar. XP Anda telah bertambah!');
                            formSave.submit();
                        } else {
                            alert('Waktu istirahat selesai! Mari kembali ke sesi fokus.');
                            btnFokus.click();
                        }
                    }
                }, 1000);
            }
        });

        pauseBtn.addEventListener('click', () => {
            if (isRunning) {
                clearInterval(timerInterval); suaraAir.henti();
                isRunning = false;
                startBtn.classList.remove('hidden');
                pauseBtn.classList.add('hidden');
                statusText.textContent = 'Sesi Dijeda';
                setMaskot('', 'Dijeda dulu. Lanjut kalau sudah siap ya.');
            }
        });

        resetBtn.addEventListener('click', () => {
            clearInterval(timerInterval); suaraAir.henti();
            isRunning = false;
            startBtn.classList.remove('hidden');
            pauseBtn.classList.add('hidden');
            totalSeconds = (currentMode === 'fokus') ? 25 * 60 : 5 * 60;
            statusText.textContent = (currentMode === 'fokus') ? 'Siap Memulai Sesi Belajar' : 'Mode Istirahat Singkat';
            setMaskot('', 'Halo! Siap belajar?');
            renderTime();
        });
    </script>
<?php endif; ?>

<!-- ================= TAB 2: FLASHCARD 3D (Screen 05) ================= -->
<?php if ($activeTab === 'flashcard'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- KARTU FLASHCARD INTERAKTIF FLIP 3D (2 Kolom) -->
        <div class="lg:col-span-2 space-y-4">
            <?php if (empty($flashcards)): ?>
                <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                    <i class="fa-solid fa-layer-group text-4xl text-slate-300 mb-3 block"></i>
                    <h3 class="text-base font-bold text-slate-700">Belum Ada Flashcard</h3>
                    <p class="text-xs text-slate-400 mt-1">Buat kartu hafalan pertama Anda menggunakan formulir di samping.</p>
                </div>
            <?php else: ?>
                <!-- Carousel Flashcard Interaktif -->
                <div class="bg-white rounded-2xl border border-[#E2E8F0] p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Kartu <span id="fcCurrentIdx">1</span> dari <?= count($flashcards); ?>
                        </span>
                        <span id="fcStatusBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold">
                            <!-- Diupdate oleh JS -->
                        </span>
                    </div>

                    <!-- 3D FLIP CONTAINER -->
                    <div class="w-full h-64 sm:h-72 perspective-1000 cursor-pointer" id="flashcardBox" onclick="flipCard()">
                        <div id="cardInner" class="w-full h-full relative transition-transform duration-500 transform-style-preserve-3d rounded-2xl shadow-md border border-slate-200">
                            
                            <!-- SISI DEPAN: PERTANYAAN -->
                            <div class="absolute inset-0 backface-hidden bg-gradient-to-br from-slate-50 to-blue-50/50 p-6 flex flex-col justify-between rounded-2xl">
                                <div class="flex items-center justify-between text-xs font-bold text-[#0066CC]">
                                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-question"></i> PERTANYAAN</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Klik untuk melihat jawaban</span>
                                </div>
                                <div id="fcQuestionText" class="text-lg sm:text-xl font-bold text-slate-800 text-center leading-relaxed px-4">
                                    <!-- Pertanyaan JS -->
                                </div>
                                <div class="text-center text-[11px] text-slate-400">
                                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Klik kartu untuk membalik
                                </div>
                            </div>

                            <!-- SISI BELAKANG: JAWABAN -->
                            <div class="absolute inset-0 backface-hidden rotate-y-180 bg-gradient-to-br from-[#0F294A] to-[#153660] text-white p-6 flex flex-col justify-between rounded-2xl">
                                <div class="flex items-center justify-between text-xs font-bold text-blue-200">
                                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-lightbulb text-amber-400"></i> KUNCI JAWABAN</span>
                                    <span class="text-[10px] text-slate-300 font-normal">Klik untuk kembali</span>
                                </div>
                                <div id="fcAnswerText" class="text-sm sm:text-base font-medium text-slate-100 text-center leading-relaxed px-4">
                                    <!-- Jawaban JS -->
                                </div>
                                <div class="text-center text-[11px] text-blue-200">
                                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Klik untuk membalik kartu
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Navigasi & Aksi Status -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                        <!-- Tombol Prev / Next -->
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnPrevCard" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                &larr; Sebelumnya
                            </button>
                            <button type="button" id="btnNextCard" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                Selanjutnya &rarr;
                            </button>
                        </div>

                        <!-- Update Status Hafal / Belum -->
                        <form action="" method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="action" value="update_status_flashcard">
                            <input type="hidden" name="id_card" id="formCardId" value="">
                            <input type="hidden" name="status" id="formCardStatus" value="">

                            <button type="submit" onclick="document.getElementById('formCardStatus').value='hafal'" class="px-4 py-2 rounded-xl bg-[#10B981] hover:bg-emerald-600 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-check"></i>
                                <span>Tandai Sudah Hafal</span>
                            </button>
                            <button type="submit" onclick="document.getElementById('formCardStatus').value='belum'" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>Perlu Diulang</span>
                            </button>
                        </form>
                    </div>
                </div>

                <script>
                    const cardData = <?= json_encode($flashcards); ?>;
                    let activeCardIndex = 0;
                    let isFlipped = false;

                    const cardInner = document.getElementById('cardInner');
                    const qElem = document.getElementById('fcQuestionText');
                    const aElem = document.getElementById('fcAnswerText');
                    const idxElem = document.getElementById('fcCurrentIdx');
                    const badgeElem = document.getElementById('fcStatusBadge');
                    const idInput = document.getElementById('formCardId');

                    function loadCard(index) {
                        isFlipped = false;
                        cardInner.classList.remove('rotate-y-180');
                        
                        const item = cardData[index];
                        qElem.textContent = item.pertanyaan;
                        aElem.textContent = item.jawaban;
                        idxElem.textContent = index + 1;
                        idInput.value = item.id_card;

                        if (item.status_hafal === 'hafal') {
                            badgeElem.textContent = 'Sudah Hafal';
                            badgeElem.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700';
                        } else {
                            badgeElem.textContent = 'Belum Hafal';
                            badgeElem.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800';
                        }
                    }

                    function flipCard() {
                        isFlipped = !isFlipped;
                        if (isFlipped) {
                            cardInner.classList.add('rotate-y-180');
                        } else {
                            cardInner.classList.remove('rotate-y-180');
                        }
                    }

                    document.getElementById('btnPrevCard').addEventListener('click', () => {
                        if (activeCardIndex > 0) {
                            activeCardIndex--;
                            loadCard(activeCardIndex);
                        }
                    });

                    document.getElementById('btnNextCard').addEventListener('click', () => {
                        if (activeCardIndex < cardData.length - 1) {
                            activeCardIndex++;
                            loadCard(activeCardIndex);
                        }
                    });

                    // Inisialisasi kartu pertama
                    if (cardData.length > 0) {
                        loadCard(0);
                    }
                </script>
            <?php endif; ?>
        </div>

        <!-- FORM TAMBAH & KELOLA FLASHCARD (1 Kolom) -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-plus text-[#0066CC]"></i>
                    <span>Tambah Flashcard Baru</span>
                </h3>
                <p class="text-xs text-slate-500 mb-4">Buat kartu tanya jawab untuk konsep atau rumus yang ingin Anda kuasai.</p>

                <form action="" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="tambah_flashcard">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pertanyaan / Istilah</label>
                        <textarea name="pertanyaan" rows="2" required placeholder="Contoh: Apa prinsip utama 1NF pada database?" class="w-full p-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jawaban / Definisi Kunci</label>
                        <textarea name="jawaban" rows="3" required placeholder="Contoh: Setiap kolom bernilai tunggal (atomik) dan tidak ada pengulangan data." class="w-full p-2.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2 px-3 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                        Simpan Kartu
                    </button>
                </form>
            </div>

            <!-- Daftar List Ringkas Kartu & Hapus -->
            <?php if (!empty($flashcards)): ?>
                <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm space-y-2">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Daftar Kartu Saya</h4>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        <?php foreach ($flashcards as $c): ?>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 text-xs">
                                <span class="truncate max-w-[170px] text-slate-700 font-medium"><?= e($c['pertanyaan']); ?></span>
                                <form action="" method="POST" onsubmit="return confirm('Hapus kartu ini?');">
                                    <input type="hidden" name="action" value="hapus_flashcard">
                                    <input type="hidden" name="id_card" value="<?= $c['id_card']; ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-600 p-1">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
<?php endif; ?>

<!-- ================= TAB 3: PLANNER TARGET (Screen 06) ================= -->
<?php if ($activeTab === 'planner'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- AGENDA OTOMATIS & TENGGAT (1 Kolom) -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <i class="fa-regular fa-clock text-[#0066CC]"></i>
                    <h3 class="text-sm font-bold text-slate-800">Tenggat Otomatis</h3>
                </div>
                <p class="text-xs text-slate-500">Jadwal tugas yang terhubung otomatis dari kelas Anda:</p>

                <?php if (empty($upcomingDeadlines)): ?>
                    <p class="text-xs text-slate-400 py-3 italic">Tidak ada tenggat tugas dalam waktu dekat.</p>
                <?php else: ?>
                    <div class="space-y-2.5">
                        <?php foreach ($upcomingDeadlines as $ud): ?>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1">
                                <span class="text-[10px] font-bold text-[#0066CC] bg-blue-100 px-1.5 py-0.2 rounded">
                                    <?= e($ud['nama_kelas']); ?>
                                </span>
                                <div class="font-bold text-slate-800"><?= e($ud['judul']); ?></div>
                                <div class="text-[10px] text-red-600 font-semibold flex items-center gap-1">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span>Tenggat: <?= date('d M Y, H:i', strtotime($ud['tenggat_waktu'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TARGET BELAJAR PRIBADI & FORM TAMBAH (2 Kolom) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-5">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Target Belajar Harian Saya</h3>
                    <p class="text-xs text-slate-500">Rencanakan pencapaian mandiri untuk menjaga konsistensi belajar Anda.</p>
                </div>

                <!-- Form Tambah Target -->
                <form action="" method="POST" class="flex flex-col sm:flex-row gap-2">
                    <input type="hidden" name="action" value="tambah_planner">
                    <input type="text" name="judul_target" required placeholder="Contoh: Selesaikan modul 3 PDO dan buat rangkuman..." class="flex-1 px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    <input type="date" name="tanggal_target" value="<?= date('Y-m-d'); ?>" class="px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition flex-shrink-0">
                        + Tambah
                    </button>
                </form>

                <!-- Daftar Checklist Target -->
                <div class="pt-2">
                    <?php if (empty($daftarTarget)): ?>
                        <p class="text-xs text-slate-400 py-6 text-center italic">Belum ada target yang dijadwalkan.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($daftarTarget as $plan): ?>
                                <?php $isDone = ($plan['status'] === 'selesai'); ?>
                                <div class="flex items-center justify-between p-3 rounded-xl border transition <?= $isDone ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200/80'; ?>">
                                    <div class="flex items-center gap-3">
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_planner">
                                            <input type="hidden" name="id_plan" value="<?= $plan['id_plan']; ?>">
                                            <input type="hidden" name="current_status" value="<?= $plan['status']; ?>">
                                            <button type="submit" class="w-5 h-5 rounded border flex items-center justify-center transition <?= $isDone ? 'bg-[#10B981] border-emerald-500 text-white' : 'border-slate-300 bg-white text-transparent'; ?>">
                                                <i class="fa-solid fa-check text-xs"></i>
                                            </button>
                                        </form>
                                        <div>
                                            <div class="text-xs font-semibold <?= $isDone ? 'line-through text-slate-400' : 'text-slate-800'; ?>">
                                                <?= e($plan['judul_target']); ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400">
                                                Jadwal: <?= date('d M Y', strtotime($plan['tanggal_target'])); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <form action="" method="POST" onsubmit="return confirm('Hapus target ini?');">
                                        <input type="hidden" name="action" value="hapus_planner">
                                        <input type="hidden" name="id_plan" value="<?= $plan['id_plan']; ?>">
                                        <button type="submit" class="text-slate-400 hover:text-red-500 p-1">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
<?php endif; ?>

<!-- ================= TAB 4: STATISTIK BELAJAR (Screen 07) ================= -->
<?php if ($activeTab === 'statistik'): ?>
    <div class="space-y-6">

        <!-- KARTU INDIKATOR STATISTIK UTAMA -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Total Jam Fokus -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Waktu Fokus</span>
                    <i class="fa-solid fa-stopwatch text-[#0066CC] text-xl"></i>
                </div>
                <div class="text-3xl font-extrabold text-slate-800"><?= $totalJamFokus; ?> Jam</div>
                <p class="text-xs text-slate-500 mt-1"><?= $totalSesiFokus; ?> sesi Pomodoro tuntas tercatat.</p>
            </div>

            <!-- Streak Belajar -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Streak Konsistensi</span>
                    <i class="fa-solid fa-fire text-orange-500 text-xl"></i>
                </div>
                <div class="text-3xl font-extrabold text-slate-800"><?= (int)$_SESSION['streak_harian'] ?? 1; ?> Hari</div>
                <p class="text-xs text-slate-500 mt-1">Hari berturut-turut aktif di Questudy.</p>
            </div>

            <!-- Daya Ingat Flashcard -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Daya Ingat Materi</span>
                    <i class="fa-solid fa-brain text-emerald-500 text-xl"></i>
                </div>
                <div class="text-3xl font-extrabold text-slate-800"><?= $totalHafal; ?> Kartu</div>
                <p class="text-xs text-slate-500 mt-1"><?= $totalBelum; ?> kartu masih dalam tahap pengulangan.</p>
            </div>
        </div>

        <!-- GRAFIK BATANG AKTIVITAS MINGGUAN (Tailwind Blue #0066CC) -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Aktivitas Belajar 7 Hari Terakhir</h3>
                    <p class="text-xs text-slate-500">Durasi fokus belajar Pomodoro harian (dalam menit).</p>
                </div>
                <span class="text-xs font-bold text-[#0066CC] bg-blue-50 px-3 py-1 rounded-full">
                    Maks: <?= $maxMenitGrafik; ?> Menit
                </span>
            </div>

            <!-- Bar Chart Component -->
            <div class="pt-6 pb-2">
                <div class="h-48 flex items-end justify-between gap-3 sm:gap-6 px-4">
                    <?php foreach ($hariAktivitas as $ha): ?>
                        <?php 
                            $tinggiPersen = ($maxMenitGrafik > 0) ? round(($ha['menit'] / $maxMenitGrafik) * 100) : 0;
                            $tinggiPersen = max(6, $tinggiPersen); // minimum bar height
                        ?>
                        <div class="flex-1 flex flex-col items-center gap-2 group">
                            <!-- Tooltip nilai -->
                            <span class="text-[10px] font-bold text-slate-500 group-hover:text-[#0066CC] transition">
                                <?= $ha['menit']; ?>m
                            </span>
                            <!-- Bar Batang -->
                            <div class="w-full bg-[#0066CC] rounded-t-lg transition-all duration-500 group-hover:bg-[#0052A3]" style="height: <?= $tinggiPersen; ?>%"></div>
                            <!-- Label Hari -->
                            <span class="text-xs font-bold text-slate-600 mt-1">
                                <?= $ha['hari']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
