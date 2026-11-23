<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pengajar');

$pageTitle = 'Kelola Kelas & Pengumuman — Questudy';
$userId = $_SESSION['id_user'];

$idKelasAktif = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle Buat Kelas Baru (Screen 11)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_kelas') {
    $namaKelas = trim($_POST['nama_kelas'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    $kategori = trim($_POST['kategori'] ?? 'Lainnya');
    $tingkat = $_POST['tingkat'] ?? 'pemula';
    $kuota = (int)($_POST['kuota'] ?? getPengaturan($conn, 'kuota_kelas_default', 20));
    $maksKelas = getPengaturan($conn, 'maks_kelas_tutor', 3);
    $stmtJml = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE id_pengajar = ?");
    $stmtJml->execute([$userId]);
    $jumlahKelas = (int)$stmtJml->fetchColumn();

    if (empty($namaKelas)) {
        setFlash('error', 'Nama kelas tidak boleh kosong!');
    } elseif (!in_array($kategori, daftarKategori()) || !in_array($tingkat, ['pemula', 'menengah', 'lanjut'])) {
        setFlash('error', 'Kategori atau tingkat kelas tidak valid!');
    } elseif ($kuota < 1 || $kuota > 100) {
        setFlash('error', 'Kuota peserta harus antara 1 dan 100!');
    } elseif ($jumlahKelas >= $maksKelas) {
        setFlash('error', 'Batas kelas per tutor tercapai (maksimal ' . $maksKelas . ' kelas).');
    } else {
        // Generate kode unik 6 karakter
        $kodeUnik = '';
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $kodeUnik = substr(str_shuffle($characters), 0, 6);
            $stmtCek = $conn->prepare("SELECT id_kelas FROM kelas WHERE kode_kelas = ?");
            $stmtCek->execute([$kodeUnik]);
        } while ($stmtCek->fetch());

        $stmtIns = $conn->prepare("INSERT INTO kelas (id_pengajar, kode_kelas, nama_kelas, deskripsi, kategori, tingkat, kuota, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmtIns->execute([$userId, $kodeUnik, $namaKelas, $deskripsi ?: null, $kategori, $tingkat, $kuota]);

        setFlash('success', "Kelas berhasil dibuat dengan kode unik: $kodeUnik!");
        header("Location: " . base_url('pengajar/kelas.php'));
        exit();
    }
}

// Handle Edit Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_kelas') {
    $idEdit = (int)$_POST['id_kelas'];
    $namaKelas = trim($_POST['nama_kelas'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!empty($namaKelas)) {
        $stmtUp = $conn->prepare("UPDATE kelas SET nama_kelas = ?, deskripsi = ? WHERE id_kelas = ? AND id_pengajar = ?");
        $stmtUp->execute([$namaKelas, $deskripsi ?: null, $idEdit, $userId]);
        setFlash('success', 'Informasi kelas berhasil diperbarui!');
    }
    header("Location: " . base_url('pengajar/kelas.php?id=' . $idEdit));
    exit();
}

// Handle Hapus Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_kelas') {
    $idDel = (int)$_POST['id_kelas'];
    $stmtDel = $conn->prepare("DELETE FROM kelas WHERE id_kelas = ? AND id_pengajar = ?");
    $stmtDel->execute([$idDel, $userId]);
    setFlash('info', 'Kelas dan seluruh datanya berhasil dihapus.');
    header("Location: " . base_url('pengajar/kelas.php'));
    exit();
}

// Handle Keluarkan Anggota Pelajar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'keluarkan_anggota') {
    $idAnggota = (int)$_POST['id_anggota'];
    $stmtOut = $conn->prepare("
        DELETE ak FROM anggota_kelas ak 
        JOIN kelas k ON ak.id_kelas = k.id_kelas 
        WHERE ak.id_anggota = ? AND k.id_pengajar = ?
    ");
    $stmtOut->execute([$idAnggota, $userId]);
    setFlash('info', 'Pelajar berhasil dikeluarkan dari kelas.');
    header("Location: " . base_url('pengajar/kelas.php?id=' . $idKelasAktif));
    exit();
}

// Handle Tambah Pengumuman Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_pengumuman') {
    $idK = (int)$_POST['id_kelas'];
    $judulP = trim($_POST['judul'] ?? '');
    $isiP = trim($_POST['isi'] ?? '');

    if (!empty($judulP) && !empty($isiP)) {
        $stmtP = $conn->prepare("INSERT INTO pengumuman (id_kelas, judul, isi, created_at) VALUES (?, ?, ?, NOW())");
        $stmtP->execute([$idK, $judulP, $isiP]);
        setFlash('success', 'Pengumuman kelas berhasil dipublikasikan!');
    }
    header("Location: " . base_url('pengajar/kelas.php?id=' . $idK));
    exit();
}

// Handle Hapus Pengumuman Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_pengumuman') {
    $idP = (int)$_POST['id_pengumuman'];
    $stmtHapusP = $conn->prepare("
        DELETE p FROM pengumuman p 
        JOIN kelas k ON p.id_kelas = k.id_kelas 
        WHERE p.id_pengumuman = ? AND k.id_pengajar = ?
    ");
    $stmtHapusP->execute([$idP, $userId]);
    setFlash('info', 'Pengumuman berhasil dihapus.');
    header("Location: " . base_url('pengajar/kelas.php?id=' . $idKelasAktif));
    exit();
}

// Ambil semua kelas pengajar ini
$stmtKelas = $conn->prepare("
    SELECT k.*, 
        (SELECT COUNT(*) FROM anggota_kelas ak WHERE ak.id_kelas = k.id_kelas) AS total_siswa,
        (SELECT COUNT(*) FROM pengumuman p WHERE p.id_kelas = k.id_kelas) AS total_pengumuman
    FROM kelas k
    WHERE k.id_pengajar = ?
    ORDER BY k.created_at DESC
");
$stmtKelas->execute([$userId]);
$daftarKelas = $stmtKelas->fetchAll();

// Tentukan kelas aktif yang sedang dikelola
$kelasAktif = null;
$anggotaList = [];
$pengumumanList = [];

if (!empty($daftarKelas)) {
    if ($idKelasAktif > 0) {
        foreach ($daftarKelas as $dk) {
            if ($dk['id_kelas'] === $idKelasAktif) {
                $kelasAktif = $dk;
                break;
            }
        }
    }
    if (!$kelasAktif) {
        $kelasAktif = $daftarKelas[0];
        $idKelasAktif = $kelasAktif['id_kelas'];
    }

    // Ambil anggota kelas aktif
    $stmtAnggota = $conn->prepare("
        SELECT ak.id_anggota, ak.bergabung_pada, u.id_user, u.nama, u.email, u.foto_profil, u.xp, u.level
        FROM anggota_kelas ak
        JOIN users u ON ak.id_pelajar = u.id_user
        WHERE ak.id_kelas = ?
        ORDER BY u.nama ASC
    ");
    $stmtAnggota->execute([$idKelasAktif]);
    $anggotaList = $stmtAnggota->fetchAll();

    // Ambil pengumuman kelas aktif
    $stmtP = $conn->prepare("SELECT * FROM pengumuman WHERE id_kelas = ? ORDER BY created_at DESC");
    $stmtP->execute([$idKelasAktif]);
    $pengumumanList = $stmtP->fetchAll();
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 11) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pengajar/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Kelola Kelas</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Manajemen Kelas & Pengumuman</h1>
            <p class="text-xs text-slate-500">Buat kelas baru, kelola anggota pelajar, dan bagikan informasi akademik.</p>
        </div>
        <div>
            <!-- Tombol Trigger Modal Tambah Kelas -->
            <button onclick="document.getElementById('modalTambahKelas').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Buat Kelas Baru</span>
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- SISI KIRI (1 Kolom): DAFTAR KELAS -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100">
                Kelas yang Anda Kelola (<?= count($daftarKelas); ?>)
            </h3>

            <?php if (empty($daftarKelas)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada kelas yang dibuat.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($daftarKelas as $k): ?>
                        <?php $isSel = ($kelasAktif && $kelasAktif['id_kelas'] === $k['id_kelas']); ?>
                        <a href="<?= base_url('pengajar/kelas.php?id=' . $k['id_kelas']); ?>" class="block p-3.5 rounded-xl border transition <?= $isSel ? 'border-[#0066CC] bg-blue-50/70 shadow-sm' : 'border-slate-100 bg-slate-50/60 hover:bg-slate-50'; ?>">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-100 text-[#0066CC]">
                                    <?= e($k['kode_kelas']); ?>
                                </span>
                                <span class="text-[11px] text-slate-400">
                                    <?= (int)$k['total_siswa']; ?> / <?= (int)$k['kuota']; ?> Pelajar
                                </span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800 line-clamp-1"><?= e($k['nama_kelas']); ?></h4>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SISI KANAN (2 Kolom): DETAIL KELAS, ANGGOTA & PENGUMUMAN -->
    <div class="lg:col-span-2 space-y-6">
        <?php if (!$kelasAktif): ?>
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-12 text-center shadow-sm">
                <i class="fa-solid fa-chalkboard text-4xl text-slate-300 mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-700">Mulai Buat Kelas Pertama Anda</h3>
                <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Buat Kelas Baru" di atas untuk menamai kelas dan menghasilkan kode acak unik.</p>
            </div>
        <?php else: ?>

            <!-- KARTU INFO KELAS AKTIF -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-mono font-black px-2.5 py-1 rounded-lg bg-blue-100 text-[#0066CC] border border-blue-200">
                                Kode: <?= e($kelasAktif['kode_kelas']); ?>
                            </span>
                            <span class="text-xs text-slate-400">Bagikan kode ini kepada pelajar untuk bergabung</span>
                        </div>
                        <h2 class="text-xl font-extrabold text-slate-800 mt-2"><?= e($kelasAktif['nama_kelas']); ?></h2>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?= e($kelasAktif['deskripsi'] ?: 'Tidak ada deskripsi.'); ?></p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form action="" method="POST" onsubmit="return confirm('Peringatan: Menghapus kelas akan menghapus seluruh materi, tugas, dan nilai di dalamnya! Lanjutkan?');">
                            <input type="hidden" name="action" value="hapus_kelas">
                            <input type="hidden" name="id_kelas" value="<?= $kelasAktif['id_kelas']; ?>">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold border border-red-200 transition">
                                <i class="fa-solid fa-trash mr-1"></i> Hapus Kelas
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tautan Pintas Modul -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <a href="<?= base_url('pengajar/materi.php?id_kelas=' . $kelasAktif['id_kelas']); ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-book text-[#0066CC]"></i>
                        <span>Kelola Materi</span>
                    </a>
                    <a href="<?= base_url('pengajar/tugas.php?id_kelas=' . $kelasAktif['id_kelas']); ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check text-[#0066CC]"></i>
                        <span>Kelola Tugas</span>
                    </a>
                    <a href="<?= base_url('pengajar/kuis.php?id_kelas=' . $kelasAktif['id_kelas']); ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-question text-amber-500"></i>
                        <span>Kelola Kuis</span>
                    </a>
                    <a href="<?= base_url('pengajar/rapor.php?id_kelas=' . $kelasAktif['id_kelas']); ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-square-poll-vertical text-emerald-600"></i>
                        <span>Rapor Kelas</span>
                    </a>
                </div>
            </div>

            <!-- TABEL KELOLA ANGGOTA KELAS -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users text-[#0066CC]"></i>
                        <h3 class="text-sm font-bold text-slate-800">Daftar Anggota Pelajar (<?= count($anggotaList); ?>)</h3>
                    </div>
                </div>

                <?php if (empty($anggotaList)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center italic">Belum ada pelajar yang bergabung ke kelas ini.</p>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Nama Pelajar</th>
                                    <th class="p-3">Reputasi Belajar</th>
                                    <th class="p-3">Bergabung</th>
                                    <th class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($anggotaList as $mhs): ?>
                                    <tr>
                                        <td class="p-3">
                                            <div class="flex items-center gap-2.5">
                                                <img src="<?= base_url('uploads/profil/' . e($mhs['foto_profil'])); ?>" class="w-7 h-7 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($mhs['nama']); ?>&background=0066CC&color=fff';">
                                                <div>
                                                    <span class="font-bold text-slate-800 block"><?= e($mhs['nama']); ?></span>
                                                    <span class="text-[10px] text-slate-400"><?= e($mhs['email']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <span class="text-amber-600 font-bold">Lvl <?= (int)$mhs['level']; ?></span>
                                            <span class="text-slate-400"> (<?= number_format($mhs['xp']); ?> XP)</span>
                                        </td>
                                        <td class="p-3 text-slate-500">
                                            <?= date('d M Y', strtotime($mhs['bergabung_pada'])); ?>
                                        </td>
                                        <td class="p-3 text-right">
                                            <form action="" method="POST" onsubmit="return confirm('Keluarkan pelajar ini dari kelas?');">
                                                <input type="hidden" name="action" value="keluarkan_anggota">
                                                <input type="hidden" name="id_anggota" value="<?= $mhs['id_anggota']; ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold p-1">
                                                    Keluarkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PENGUMUMAN KELAS -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-bullhorn text-[#0066CC]"></i>
                        <h3 class="text-sm font-bold text-slate-800">Pengumuman Kelas</h3>
                    </div>
                </div>

                <!-- Form Tambah Pengumuman -->
                <form action="" method="POST" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <input type="hidden" name="action" value="tambah_pengumuman">
                    <input type="hidden" name="id_kelas" value="<?= $kelasAktif['id_kelas']; ?>">

                    <div>
                        <input type="text" name="judul" required placeholder="Judul Pengumuman (misal: Perubahan Jadwal Praktikum)" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    </div>
                    <div>
                        <textarea name="isi" rows="2" required placeholder="Tulis rincian pengumuman kepada pelajar..." class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition">
                            Publikasikan Pengumuman
                        </button>
                    </div>
                </form>

                <!-- Daftar Pengumuman -->
                <div class="space-y-3 pt-2">
                    <?php if (empty($pengumumanList)): ?>
                        <p class="text-xs text-slate-400 italic text-center py-2">Belum ada pengumuman di kelas ini.</p>
                    <?php else: ?>
                        <?php foreach ($pengumumanList as $pgm): ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-slate-800"><?= e($pgm['judul']); ?></h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-400"><?= date('d M Y, H:i', strtotime($pgm['created_at'])); ?></span>
                                        <form action="" method="POST" onsubmit="return confirm('Hapus pengumuman ini?');">
                                            <input type="hidden" name="action" value="hapus_pengumuman">
                                            <input type="hidden" name="id_pengumuman" value="<?= $pgm['id_pengumuman']; ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-600 p-0.5">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-slate-600 leading-relaxed"><?= nl2br(e($pgm['isi'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

</div>

<!-- MODAL TAMBAH KELAS -->
<div id="modalTambahKelas" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800">Buat Kelas Pembelajaran Baru</h3>
            <button onclick="document.getElementById('modalTambahKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="tambah_kelas">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kelas atau Skill</label>
                <input type="text" name="nama_kelas" required placeholder="Contoh: Desain Grafis untuk Pemula" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                    <select name="kategori" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                        <?php foreach (daftarKategori() as $kat): ?>
                            <option value="<?= e($kat); ?>"><?= e($kat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat</label>
                    <select name="tingkat" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="pemula">Pemula</option>
                        <option value="menengah">Menengah</option>
                        <option value="lanjut">Lanjut</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kuota Peserta</label>
                    <input type="number" name="kuota" min="1" max="100" value="<?= getPengaturan($conn, 'kuota_kelas_default', 20); ?>" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat Kelas</label>
                <textarea name="deskripsi" rows="3" placeholder="Apa yang akan dipelajari dan hasil akhirnya..." class="w-full p-3 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"></textarea>
            </div>

            <div class="p-3 rounded-lg bg-blue-50 border border-blue-200 text-xs text-[#0066CC]">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Kode unik 6 karakter acak akan dibuat otomatis oleh sistem setelah kelas disimpan.
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalTambahKelas').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                    Buat Kelas Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
