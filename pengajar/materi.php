<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Kelola Materi & Forum — Questudy';
$userId = $_SESSION['id_user'];

$idKelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;
$idMateriAktif = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil kelas pengajar ini
$stmtK = $conn->prepare("SELECT id_kelas, nama_kelas, kode_kelas FROM kelas WHERE id_pengajar = ? ORDER BY nama_kelas ASC");
$stmtK->execute([$userId]);
$myClasses = $stmtK->fetchAll();

if ($idKelas === 0 && !empty($myClasses)) {
    $idKelas = (int)$myClasses[0]['id_kelas'];
}

$errorMateri = '';

// Handle Tambah Materi Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_materi') {
    $targetKelas = (int)$_POST['id_kelas'];
    $judul = trim($_POST['judul'] ?? '');
    $tipe = $_POST['tipe'] ?? 'teks';
    $urutan = (int)($_POST['urutan'] ?? 1);
    $kontenTeks = trim($_POST['konten_teks'] ?? '');
    $tautanUrl = trim($_POST['tautan_url'] ?? '');
    $fileMateri = null;

    if (empty($judul)) {
        $errorMateri = 'Judul materi wajib diisi!';
    } else {
        // Cek file upload jika tipe file
        if ($tipe === 'file' && isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
            $tmpFile = $_FILES['file_materi']['tmp_name'];
            $origName = $_FILES['file_materi']['name'];
            $fileSize = $_FILES['file_materi']['size'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            $allowed = ['pdf', 'docx', 'pptx', 'zip', 'png', 'jpg', 'jpeg'];
            if (!in_array($ext, $allowed)) {
                $errorMateri = 'Format file tidak didukung! Format yang diizinkan: PDF, DOCX, PPTX, ZIP, PNG, JPG.';
            } elseif ($fileSize > 15 * 1024 * 1024) {
                $errorMateri = 'Ukuran berkas materi maksimal adalah 15MB!';
            } else {
                $fileMateri = 'mat_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.]/', '', $origName);
                $destDir = BASE_PATH . '/uploads/materi/';
                if (!is_dir($destDir)) mkdir($destDir, 0777, true);
                move_uploaded_file($tmpFile, $destDir . $fileMateri);
            }
        }

        if (empty($errorMateri)) {
            $stmtIns = $conn->prepare("
                INSERT INTO materi (id_kelas, judul, tipe, konten_teks, file_materi, tautan_url, urutan, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([
                $targetKelas,
                $judul,
                $tipe,
                ($tipe === 'teks') ? $kontenTeks : null,
                ($tipe === 'file') ? $fileMateri : null,
                ($tipe === 'tautan') ? $tautanUrl : null,
                $urutan
            ]);

            setFlash('success', 'Materi pembelajaran berhasil ditambahkan!');
            header("Location: " . base_url('pengajar/materi.php?id_kelas=' . $targetKelas));
            exit();
        }
    }
}

// Handle Hapus Materi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_materi') {
    $idDel = (int)$_POST['id_materi'];
    $stmtDel = $conn->prepare("
        DELETE m FROM materi m 
        JOIN kelas k ON m.id_kelas = k.id_kelas 
        WHERE m.id_materi = ? AND k.id_pengajar = ?
    ");
    $stmtDel->execute([$idDel, $userId]);
    setFlash('info', 'Materi berhasil dihapus.');
    header("Location: " . base_url('pengajar/materi.php?id_kelas=' . $idKelas));
    exit();
}

// Handle Balas Komentar Forum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'balas_forum') {
    $idM = (int)$_POST['id_materi'];
    $komentar = trim($_POST['isi_komentar'] ?? '');
    if (!empty($komentar)) {
        $stmtF = $conn->prepare("INSERT INTO forum_materi (id_materi, id_user, isi_komentar, created_at) VALUES (?, ?, ?, NOW())");
        $stmtF->execute([$idM, $userId, $komentar]);
        setFlash('success', 'Jawaban/tanggapan Anda berhasil dikirim ke forum.');
    }
    header("Location: " . base_url('pengajar/materi.php?id_kelas=' . $idKelas . '&id=' . $idM));
    exit();
}

// Handle Hapus Komentar Forum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_komentar') {
    $idF = (int)$_POST['id_forum'];
    $idM = (int)$_POST['id_materi'];
    $stmtDelF = $conn->prepare("
        DELETE f FROM forum_materi f
        JOIN materi m ON f.id_materi = m.id_materi
        JOIN kelas k ON m.id_kelas = k.id_kelas
        WHERE f.id_forum = ? AND k.id_pengajar = ?
    ");
    $stmtDelF->execute([$idF, $userId]);
    setFlash('info', 'Komentar berhasil dihapus.');
    header("Location: " . base_url('pengajar/materi.php?id_kelas=' . $idKelas . '&id=' . $idM));
    exit();
}

// Ambil materi kelas terpilih
$materiList = [];
if ($idKelas > 0) {
    $stmtM = $conn->prepare("
        SELECT m.*,
            (SELECT COUNT(*) FROM progres_materi pm WHERE pm.id_materi = m.id_materi) AS total_siswa_selesai,
            (SELECT COUNT(*) FROM forum_materi fm WHERE fm.id_materi = m.id_materi) AS total_komentar
        FROM materi m
        WHERE m.id_kelas = ?
        ORDER BY m.urutan ASC, m.id_materi ASC
    ");
    $stmtM->execute([$idKelas]);
    $materiList = $stmtM->fetchAll();
}

// Detail materi terpilih untuk tab Progres & Forum
$materiAktif = null;
$siswaSelesaiList = [];
$forumList = [];

if (!empty($materiList)) {
    if ($idMateriAktif > 0) {
        foreach ($materiList as $ml) {
            if ($ml['id_materi'] === $idMateriAktif) {
                $materiAktif = $ml;
                break;
            }
        }
    }
    if (!$materiAktif) {
        $materiAktif = $materiList[0];
        $idMateriAktif = $materiAktif['id_materi'];
    }

    // Ambil daftar siswa yang sudah menyelesaikan materi ini
    $stmtProgress = $conn->prepare("
        SELECT u.nama, u.email, u.foto_profil, pm.selesai_pada
        FROM progres_materi pm
        JOIN users u ON pm.id_pelajar = u.id_user
        WHERE pm.id_materi = ?
        ORDER BY pm.selesai_pada DESC
    ");
    $stmtProgress->execute([$idMateriAktif]);
    $siswaSelesaiList = $stmtProgress->fetchAll();

    // Ambil komentar forum untuk materi ini
    $stmtForum = $conn->prepare("
        SELECT fm.*, u.nama, u.role, u.foto_profil
        FROM forum_materi fm
        JOIN users u ON fm.id_user = u.id_user
        WHERE fm.id_materi = ?
        ORDER BY fm.created_at ASC
    ");
    $stmtForum->execute([$idMateriAktif]);
    $forumList = $stmtForum->fetchAll();
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Kelola Materi</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Manajemen Materi & Forum Diskusi</h1>
            <p class="text-xs text-slate-500">Unggah bahan ajar teks, PDF, tautan dan pantau mahasiswa yang telah menyelesaikan modul.</p>
        </div>
        <div>
            <form action="" method="GET" class="flex items-center gap-2">
                <select name="id_kelas" onchange="this.form.submit()" class="px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0066CC] shadow-sm">
                    <?php foreach ($myClasses as $mc): ?>
                        <option value="<?= $mc['id_kelas']; ?>" <?= ($idKelas === $mc['id_kelas']) ? 'selected' : ''; ?>>
                            <?= e($mc['nama_kelas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- SISI KIRI (1 Kolom): FORM TAMBAH & LIST MATERI -->
    <div class="space-y-5">
        
        <!-- Form Tambah Materi -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-5 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-plus text-[#0066CC]"></i>
                <span>Tambah Materi Pembelajaran</span>
            </h3>

            <?php if (!empty($errorMateri)): ?>
                <div class="p-2.5 rounded-lg bg-red-50 text-red-700 text-xs border border-red-200">
                    <?= e($errorMateri); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="action" value="tambah_materi">
                <input type="hidden" name="id_kelas" value="<?= $idKelas; ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Materi</label>
                    <input type="text" name="judul" required placeholder="Contoh: Modul 02 — Normalisasi Database" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Materi</label>
                        <select name="tipe" id="tipeSelect" onchange="toggleTipeInput(this.value)" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                            <option value="teks">Teks / Artikel</option>
                            <option value="file">Berkas Dokumen / PDF</option>
                            <option value="tautan">Tautan Eksternal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Modul</label>
                        <input type="number" name="urutan" value="<?= count($materiList) + 1; ?>" min="1" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    </div>
                </div>

                <!-- Input Dinamis Berdasarkan Tipe -->
                <div id="wrapperTeks">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Konten Teks</label>
                    <textarea name="konten_teks" rows="4" placeholder="Tuliskan materi atau ringkasan teori..." class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
                </div>

                <div id="wrapperFile" class="hidden">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih File Materi (PDF, DOCX, ZIP)</label>
                    <input type="file" name="file_materi" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0066CC]">
                </div>

                <div id="wrapperTautan" class="hidden">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">URL Tautan Eksternal</label>
                    <input type="url" name="tautan_url" placeholder="https://..." class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                    Simpan Materi
                </button>
            </form>
        </div>

        <!-- Daftar Materi Kelas -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm space-y-3">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider pb-2 border-b border-slate-100">
                Silabus Modul (<?= count($materiList); ?>)
            </h4>

            <?php if (empty($materiList)): ?>
                <p class="text-xs text-slate-400 py-4 text-center">Belum ada materi di kelas ini.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($materiList as $mat): ?>
                        <?php $isAct = ($materiAktif && $materiAktif['id_materi'] === $mat['id_materi']); ?>
                        <div class="p-3 rounded-xl border transition flex items-center justify-between gap-2 <?= $isAct ? 'border-[#0066CC] bg-blue-50/70' : 'border-slate-100 bg-slate-50/60 hover:bg-slate-50'; ?>">
                            <a href="<?= base_url('pengajar/materi.php?id_kelas=' . $idKelas . '&id=' . $mat['id_materi']); ?>" class="flex-1 truncate">
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                                    <span class="font-bold text-[#0066CC]">#<?= (int)$mat['urutan']; ?></span>
                                    <span>&bull;</span>
                                    <span class="capitalize"><?= $mat['tipe']; ?></span>
                                    <span>&bull;</span>
                                    <span class="text-emerald-600 font-bold"><?= (int)$mat['total_siswa_selesai']; ?> Selesai</span>
                                </div>
                                <h5 class="text-xs font-bold text-slate-800 line-clamp-1 mt-0.5"><?= e($mat['judul']); ?></h5>
                            </a>
                            <form action="" method="POST" onsubmit="return confirm('Hapus materi ini?');">
                                <input type="hidden" name="action" value="hapus_materi">
                                <input type="hidden" name="id_materi" value="<?= $mat['id_materi']; ?>">
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

    <!-- SISI KANAN (2 Kolom): DETAIL MATERI, PROGRES SISWA & FORUM DISKUSI -->
    <div class="lg:col-span-2 space-y-6">

        <?php if (!$materiAktif): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                <i class="fa-solid fa-book-open-reader text-4xl text-slate-300 mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-700">Pilih Materi Pembelajaran</h3>
                <p class="text-xs text-slate-400 mt-1">Pilih salah satu materi di sisi kiri untuk melihat konten, daftar mahasiswa yang selesai, dan forum diskusi.</p>
            </div>
        <?php else: ?>

            <!-- KARTU PREVIEW MATERI -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC] uppercase">
                            Modul Urutan Ke-<?= (int)$materiAktif['urutan']; ?> &bull; <?= ucfirst($materiAktif['tipe']); ?>
                        </span>
                        <h2 class="text-lg font-extrabold text-slate-800 mt-1"><?= e($materiAktif['judul']); ?></h2>
                    </div>
                </div>

                <div class="text-xs text-slate-700 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <?php if ($materiAktif['tipe'] === 'teks'): ?>
                        <div class="whitespace-pre-line"><?= e($materiAktif['konten_teks']); ?></div>
                    <?php elseif ($materiAktif['tipe'] === 'file'): ?>
                        <div class="flex items-center justify-between">
                            <span class="font-bold"><?= e($materiAktif['file_materi']); ?></span>
                            <a href="<?= base_url('uploads/materi/' . e($materiAktif['file_materi'])); ?>" download class="px-3 py-1.5 rounded-lg bg-[#0066CC] text-white font-bold text-xs">
                                <i class="fa-solid fa-download mr-1"></i> Unduh File
                            </a>
                        </div>
                    <?php elseif ($materiAktif['tipe'] === 'tautan'): ?>
                        <a href="<?= e($materiAktif['tautan_url']); ?>" target="_blank" class="font-bold text-[#0066CC] hover:underline flex items-center gap-1.5">
                            <i class="fa-solid fa-external-link"></i> <?= e($materiAktif['tautan_url']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PROGRES KELULUSAN MAHASISWA -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                        <h3 class="text-sm font-bold text-slate-800">Mahasiswa yang Sudah Menyelesaikan Modul Ini</h3>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                        <?= count($siswaSelesaiList); ?> Siswa
                    </span>
                </div>

                <?php if (empty($siswaSelesaiList)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center italic">Belum ada mahasiswa yang menandai selesai materi ini.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($siswaSelesaiList as $ss): ?>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-3">
                                <img src="<?= base_url('uploads/profil/' . e($ss['foto_profil'])); ?>" class="w-8 h-8 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($ss['nama']); ?>&background=0066CC&color=fff';">
                                <div class="truncate text-xs">
                                    <span class="font-bold text-slate-800 block truncate"><?= e($ss['nama']); ?></span>
                                    <span class="text-[10px] text-slate-400">Selesai: <?= date('d M Y, H:i', strtotime($ss['selesai_pada'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FORUM DISKUSI MODUL -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-comments text-[#0066CC]"></i>
                        <h3 class="text-sm font-bold text-slate-800">Forum Tanya Jawab Modul Ini</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-500"><?= count($forumList); ?> Komentar</span>
                </div>

                <?php if (empty($forumList)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center italic">Belum ada diskusi untuk modul ini.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($forumList as $f): ?>
                            <div class="p-3.5 rounded-xl border <?= ($f['role'] === 'pengajar') ? 'bg-blue-50/60 border-blue-200' : 'bg-slate-50 border-slate-100'; ?> space-y-1">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <img src="<?= base_url('uploads/profil/' . e($f['foto_profil'])); ?>" class="w-6 h-6 rounded-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($f['nama']); ?>&background=0066CC&color=fff';">
                                        <span class="text-xs font-bold text-slate-800"><?= e($f['nama']); ?></span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded font-semibold <?= ($f['role'] === 'pengajar') ? 'bg-blue-200 text-[#0066CC]' : 'bg-slate-200 text-slate-700'; ?>">
                                            <?= ucfirst($f['role']); ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-400"><?= date('d M Y, H:i', strtotime($f['created_at'])); ?></span>
                                        <form action="" method="POST" onsubmit="return confirm('Hapus komentar ini?');">
                                            <input type="hidden" name="action" value="hapus_komentar">
                                            <input type="hidden" name="id_forum" value="<?= $f['id_forum']; ?>">
                                            <input type="hidden" name="id_materi" value="<?= $materiAktif['id_materi']; ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-600 p-0.5">
                                                <i class="fa-solid fa-trash text-[11px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-700 pl-8 leading-relaxed"><?= nl2br(e($f['isi_komentar'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Form Balas Komentar Pengajar -->
                <form action="" method="POST" class="pt-2 flex gap-2">
                    <input type="hidden" name="action" value="balas_forum">
                    <input type="hidden" name="id_materi" value="<?= $materiAktif['id_materi']; ?>">
                    <input type="text" name="isi_komentar" required placeholder="Tuliskan jawaban atau arahan sebagai pengajar..." class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition flex-shrink-0">
                        Kirim Jawaban
                    </button>
                </form>
            </div>

        <?php endif; ?>

    </div>

</div>

<script>
    function toggleTipeInput(val) {
        document.getElementById('wrapperTeks').classList.add('hidden');
        document.getElementById('wrapperFile').classList.add('hidden');
        document.getElementById('wrapperTautan').classList.add('hidden');

        if (val === 'teks') document.getElementById('wrapperTeks').classList.remove('hidden');
        else if (val === 'file') document.getElementById('wrapperFile').classList.remove('hidden');
        else if (val === 'tautan') document.getElementById('wrapperTautan').classList.remove('hidden');
    }
</script>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
