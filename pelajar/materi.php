<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$userId = $_SESSION['id_user'];
$pageTitle = 'Materi Pembelajaran — Questudy';

// Ambil id_kelas atau id_materi dari query
$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;
$idMateri = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verifikasi akses: Pelajar harus menjadi anggota kelas tersebut
if ($idMateri > 0 && $idKelas === 0) {
    $stmtM = $conn->prepare("SELECT id_kelas FROM materi WHERE id_materi = ?");
    $stmtM->execute([$idMateri]);
    $resM = $stmtM->fetch();
    if ($resM) $idKelas = (int)$resM['id_kelas'];
}

// Jika belum ada idKelas, ambil kelas pertama yang diikuti
if ($idKelas === 0) {
    $stmtFirst = $conn->prepare("SELECT id_kelas FROM anggota_kelas WHERE id_pelajar = ? ORDER BY bergabung_pada DESC LIMIT 1");
    $stmtFirst->execute([$userId]);
    $f = $stmtFirst->fetch();
    if ($f) $idKelas = (int)$f['id_kelas'];
}

// Cek kepesertaan kelas
$stmtCheckAkses = $conn->prepare("SELECT k.* FROM anggota_kelas ak JOIN kelas k ON ak.id_kelas = k.id_kelas WHERE ak.id_kelas = ? AND ak.id_pelajar = ?");
$stmtCheckAkses->execute([$idKelas, $userId]);
$kelas = $stmtCheckAkses->fetch();

if (!$kelas) {
    setFlash('error', 'Kelas tidak ditemukan atau Anda belum bergabung ke kelas tersebut.');
    header("Location: " . base_url('pelajar/kelas.php'));
    exit();
}

// Handle Aksi: Tandai Selesai (+XP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tandai_selesai') {
    $targetMateri = (int)$_POST['id_materi'];
    
    // Cek apakah sudah selesai sebelumnya
    $stmtCek = $conn->prepare("SELECT id_progres FROM progres_materi WHERE id_materi = ? AND id_pelajar = ?");
    $stmtCek->execute([$targetMateri, $userId]);
    if (!$stmtCek->fetch()) {
        $stmtIns = $conn->prepare("INSERT INTO progres_materi (id_materi, id_pelajar, selesai_pada) VALUES (?, ?, NOW())");
        $stmtIns->execute([$targetMateri, $userId]);

        // Tambah XP gamifikasi
        $reward = tambahXP($conn, $userId, 'selesai_materi');
        $msg = 'Materi berhasil diselesaikan! +20 XP ditambahkan ke profil Anda.';
        if (!empty($reward['badges_baru'])) {
            $msg .= ' Selamat, Anda membuka badge baru: ' . implode(', ', $reward['badges_baru']) . '!';
        }
        setFlash('success', $msg);
    }
    header("Location: " . base_url('pelajar/materi.php?id_kelas=' . $idKelas . '&id=' . $targetMateri));
    exit();
}

// Handle Aksi: Simpan Catatan Pribadi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_catatan') {
    $targetMateri = (int)$_POST['id_materi'];
    $isiCatatan = trim($_POST['isi_catatan'] ?? '');

    if (!empty($isiCatatan)) {
        // Upsert catatan pribadi
        $stmtNote = $conn->prepare("
            INSERT INTO catatan_pribadi (id_materi, id_pelajar, isi_catatan, updated_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE isi_catatan = VALUES(isi_catatan), updated_at = NOW()
        ");
        $stmtNote->execute([$targetMateri, $userId, $isiCatatan]);
        setFlash('success', 'Catatan pribadi Anda berhasil disimpan!');
    }
    header("Location: " . base_url('pelajar/materi.php?id_kelas=' . $idKelas . '&id=' . $targetMateri));
    exit();
}

// Handle Aksi: Kirim Komentar Forum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'kirim_forum') {
    $targetMateri = (int)$_POST['id_materi'];
    $isiKomentar = trim($_POST['isi_komentar'] ?? '');

    if (!empty($isiKomentar)) {
        $stmtForum = $conn->prepare("INSERT INTO forum_materi (id_materi, id_user, isi_komentar, created_at) VALUES (?, ?, ?, NOW())");
        $stmtForum->execute([$targetMateri, $userId, $isiKomentar]);
        setFlash('success', 'Pertanyaan/komentar Anda berhasil dikirim ke forum diskusi.');
    }
    header("Location: " . base_url('pelajar/materi.php?id_kelas=' . $idKelas . '&id=' . $targetMateri));
    exit();
}

// Ambil seluruh materi di kelas ini
$stmtMateriList = $conn->prepare("
    SELECT m.*, pm.selesai_pada
    FROM materi m
    LEFT JOIN progres_materi pm ON m.id_materi = pm.id_materi AND pm.id_pelajar = ?
    WHERE m.id_kelas = ?
    ORDER BY m.urutan ASC, m.id_materi ASC
");
$stmtMateriList->execute([$userId, $idKelas]);
$daftarMateri = $stmtMateriList->fetchAll();

// Tentukan materi aktif
$materiAktif = null;
if (!empty($daftarMateri)) {
    if ($idMateri > 0) {
        foreach ($daftarMateri as $dm) {
            if ($dm['id_materi'] === $idMateri) {
                $materiAktif = $dm;
                break;
            }
        }
    }
    if (!$materiAktif) {
        $materiAktif = $daftarMateri[0];
        $idMateri = $materiAktif['id_materi'];
    }
}

// Ambil catatan pribadi untuk materi aktif
$catatanSaya = '';
if ($materiAktif) {
    $stmtC = $conn->prepare("SELECT isi_catatan FROM catatan_pribadi WHERE id_materi = ? AND id_pelajar = ?");
    $stmtC->execute([$materiAktif['id_materi'], $userId]);
    $cat = $stmtC->fetch();
    if ($cat) $catatanSaya = $cat['isi_catatan'];
}

// Ambil komentar forum untuk materi aktif
$forumList = [];
if ($materiAktif) {
    $stmtF = $conn->prepare("
        SELECT f.*, u.nama, u.role, u.foto_profil
        FROM forum_materi f
        JOIN users u ON f.id_user = u.id_user
        WHERE f.id_materi = ?
        ORDER BY f.created_at ASC
    ");
    $stmtF->execute([$materiAktif['id_materi']]);
    $forumList = $stmtF->fetchAll();
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/kelas.php'); ?>" class="hover:text-[#0066CC]">Kelas Saya</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold"><?= e($kelas['nama_kelas']); ?></span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800"><?= e($kelas['nama_kelas']); ?></h1>
            <p class="text-xs text-slate-500">Kode Kelas: <span class="font-mono font-bold text-[#0066CC]"><?= e($kelas['kode_kelas']); ?></span> &bull; Pelajari materi dan buat catatan pribadi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= base_url('pelajar/tugas.php?id_kelas=' . $idKelas); ?>" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                Tugas Kelas
            </a>
            <a href="<?= base_url('pelajar/kuis.php?id_kelas=' . $idKelas); ?>" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                Kuis Kelas
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- SIDEBAR DAFTAR MATERI (1 Kolom) -->
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Silabus Modul</span>
                <span class="text-[11px] font-bold text-[#0066CC] bg-blue-50 px-2 py-0.5 rounded">
                    <?= count($daftarMateri); ?> Modul
                </span>
            </div>

            <?php if (empty($daftarMateri)): ?>
                <?= keadaanKosong('Belum ada materi dari tutor.'); ?>
            <?php else: ?>
                <div class="space-y-1.5">
                    <?php foreach ($daftarMateri as $idx => $m): ?>
                        <?php 
                            $isActive = ($materiAktif && $materiAktif['id_materi'] === $m['id_materi']);
                            $isDone = !empty($m['selesai_pada']);
                        ?>
                        <a href="<?= base_url('pelajar/materi.php?id_kelas=' . $idKelas . '&id=' . $m['id_materi']); ?>" class="flex items-start gap-2.5 p-2.5 rounded-xl text-xs transition <?= $isActive ? 'bg-[#EFF6FF] text-[#0066CC] font-bold border border-blue-200' : 'hover:bg-slate-50 text-slate-700 font-medium'; ?>">
                            <div class="mt-0.5">
                                <?php if ($isDone): ?>
                                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-circle text-slate-300 text-sm"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 truncate">
                                <div class="truncate"><?= e($m['judul']); ?></div>
                                <div class="text-[10px] text-slate-400 capitalize flex items-center gap-1 mt-0.5">
                                    <i class="fa-solid <?= ($m['tipe'] === 'file') ? 'fa-file-pdf' : (($m['tipe'] === 'tautan') ? 'fa-link' : 'fa-align-left'); ?>"></i>
                                    <span><?= $m['tipe']; ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KONTEN MATERI UTAMA, CATATAN & FORUM (3 Kolom) -->
    <div class="lg:col-span-3 space-y-6">

        <?php if (!$materiAktif): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                <i class="fa-solid fa-book-open text-4xl text-slate-300 mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-700">Pilih Modul Pembelajaran</h3>
                <p class="text-xs text-slate-400 mt-1">Silakan pilih salah satu modul di panel samping untuk mulai membaca.</p>
            </div>
        <?php else: ?>
            <?php 
                $sudahSelesai = !empty($materiAktif['selesai_pada']);
            ?>

            <!-- KARTU VIEW KONTEN MATERI -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Header Materi -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                                Tipe: <?= ucfirst($materiAktif['tipe']); ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                Diunggah <?= date('d M Y', strtotime($materiAktif['created_at'])); ?>
                            </span>
                        </div>
                        <h2 class="text-xl font-extrabold text-slate-800"><?= e($materiAktif['judul']); ?></h2>
                    </div>

                    <!-- Tombol Tandai Selesai / Status -->
                    <div>
                        <?php if ($sudahSelesai): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                <i class="fa-solid fa-check"></i>
                                <span>Modul Telah Selesai</span>
                            </span>
                        <?php else: ?>
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="tandai_selesai">
                                <input type="hidden" name="id_materi" value="<?= $materiAktif['id_materi']; ?>">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Tandai Selesai (+20 XP)</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Isi Konten Sesuai Tipe -->
                <div class="text-sm text-slate-700 leading-relaxed">
                    <?php if ($materiAktif['tipe'] === 'teks'): ?>
                        <div class="prose max-w-none whitespace-pre-line bg-slate-50/70 p-5 rounded-xl border border-slate-100 font-normal">
                            <?= e($materiAktif['konten_teks']); ?>
                        </div>

                    <?php elseif ($materiAktif['tipe'] === 'file'): ?>
                        <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                            <i class="fa-solid fa-file-pdf text-4xl text-red-500"></i>
                            <h4 class="text-sm font-bold text-slate-800"><?= e($materiAktif['file_materi']); ?></h4>
                            <p class="text-xs text-slate-500">Dokumen materi siap untuk diunduh dan dipelajari secara luring.</p>
                            <a href="<?= base_url('uploads/materi/' . e($materiAktif['file_materi'])); ?>" download class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                                <i class="fa-solid fa-download"></i>
                                <span>Unduh Berkas Materi</span>
                            </a>
                        </div>

                    <?php elseif ($materiAktif['tipe'] === 'tautan'): ?>
                        <div class="p-6 rounded-xl bg-blue-50/60 border border-blue-200 space-y-3">
                            <div class="flex items-center gap-2 text-[#0066CC] font-bold text-sm">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                <span>Referensi Tautan Eksternal</span>
                            </div>
                            <p class="text-xs text-slate-600">Klik tautan di bawah ini untuk mengakses materi rujukan di tab baru:</p>
                            <a href="<?= e($materiAktif['tautan_url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm truncate max-w-full">
                                <span>Buka: <?= e($materiAktif['tautan_url']); ?></span>
                                <i class="fa-solid fa-external-link text-[10px]"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB / SECTION 1: CATATAN PRIBADI PELAJAR -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-[#0066CC]"></i>
                        <h3 class="text-sm font-bold text-slate-800">Catatan Pribadi Anda</h3>
                    </div>
                    <span class="text-[10px] text-slate-400">Hanya dapat dilihat oleh Anda sendiri</span>
                </div>

                <form action="" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="simpan_catatan">
                    <input type="hidden" name="id_materi" value="<?= $materiAktif['id_materi']; ?>">
                    <textarea name="isi_catatan" rows="3" placeholder="Tulis ringkasan rumus, poin penting, atau pertanyaan pribadi di sini..." class="w-full p-3 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"><?= e($catatanSaya); ?></textarea>
                    <div class="text-right">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
                            Simpan Catatan
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB / SECTION 2: FORUM DISKUSI MATERI -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-comments text-[#0066CC]"></i>
                        <h3 class="text-sm font-bold text-slate-800">Forum Tanya Jawab Modul</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-500"><?= count($forumList); ?> Komentar</span>
                </div>

                <!-- Daftar Komentar -->
                <?php if (empty($forumList)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Belum ada diskusi untuk modul ini. Jadilah yang pertama bertanya!</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($forumList as $f): ?>
                            <div class="p-3.5 rounded-xl border <?= ($f['role'] === 'pengajar') ? 'bg-blue-50/60 border-blue-200' : 'bg-slate-50 border-slate-100'; ?> space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <img src="<?= base_url('uploads/profil/' . e($f['foto_profil'])); ?>" class="w-6 h-6 rounded-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($f['nama']); ?>&background=0066CC&color=fff';">
                                        <span class="text-xs font-bold text-slate-800"><?= e($f['nama']); ?></span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold <?= ($f['role'] === 'pengajar') ? 'bg-blue-200 text-[#0066CC]' : 'bg-slate-200 text-slate-700'; ?>">
                                            <?= ucfirst($f['role']); ?>
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400"><?= date('d M Y, H:i', strtotime($f['created_at'])); ?></span>
                                </div>
                                <p class="text-xs text-slate-700 pl-8 leading-relaxed"><?= nl2br(e($f['isi_komentar'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Form Kirim Komentar -->
                <form action="" method="POST" class="pt-2 flex gap-2">
                    <input type="hidden" name="action" value="kirim_forum">
                    <input type="hidden" name="id_materi" value="<?= $materiAktif['id_materi']; ?>">
                    <input type="text" name="isi_komentar" placeholder="Tulis pertanyaan atau tanggapan Anda..." required class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition flex-shrink-0">
                        Kirim
                    </button>
                </form>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
