<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/koneksi.php';
cekRole('admin', 'pengajar', 'pelajar');

$pageTitle = 'Profil Saya — Questudy';
$userId = $_SESSION['id_user'];

// Ambil data user terbaru
$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: " . base_url('auth/logout.php'));
    exit();
}

$errorProfil = '';
$successProfil = '';
$errorPass = '';
$successPass = '';

// Handle Update Profil & Upload Foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profil') {
    $nama = trim($_POST['nama'] ?? '');
    $keahlian = trim($_POST['keahlian'] ?? '');

    if (empty($nama)) {
        $errorProfil = 'Nama lengkap tidak boleh kosong!';
    } else {
        $fotoName = $user['foto_profil'];

        // Cek jika ada unggahan foto baru
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['foto_profil']['tmp_name'];
            $fileName = $_FILES['foto_profil']['name'];
            $fileSize = $_FILES['foto_profil']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($fileExt, $allowedExts)) {
                $errorProfil = 'Format gambar tidak didukung! Gunakan format JPG, PNG, atau WEBP.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $errorProfil = 'Ukuran berkas foto maksimal adalah 2MB!';
            } else {
                $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExt;
                $uploadDir = BASE_PATH . '/uploads/profil/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                    // Hapus foto lama jika bukan default.png
                    if ($fotoName && $fotoName !== 'default.png' && file_exists($uploadDir . $fotoName)) {
                        @unlink($uploadDir . $fotoName);
                    }
                    $fotoName = $newFileName;
                } else {
                    $errorProfil = 'Gagal menyimpan file foto ke server!';
                }
            }
        }

        if (empty($errorProfil)) {
            $stmtUpdate = $conn->prepare("UPDATE users SET nama = ?, keahlian = ?, foto_profil = ? WHERE id_user = ?");
            $stmtUpdate->execute([$nama, $keahlian ?: null, $fotoName, $userId]);

            // Update sesi
            $_SESSION['nama'] = $nama;
            $_SESSION['foto_profil'] = $fotoName;

            setFlash('success', 'Profil Anda berhasil diperbarui!');
            header("Location: " . base_url('profil.php'));
            exit();
        }
    }
}

// Handle Ganti Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ganti_password') {
    $passLama = $_POST['password_lama'] ?? '';
    $passBaru = $_POST['password_baru'] ?? '';
    $passKonfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (empty($passLama) || empty($passBaru) || empty($passKonfirmasi)) {
        $errorPass = 'Seluruh kolom kata sandi wajib diisi!';
    } elseif (!password_verify($passLama, $user['password'])) {
        $errorPass = 'Kata sandi saat ini tidak cocok!';
    } elseif (strlen($passBaru) < 6) {
        $errorPass = 'Kata sandi baru minimal harus 6 karakter!';
    } elseif ($passBaru !== $passKonfirmasi) {
        $errorPass = 'Konfirmasi kata sandi baru tidak cocok!';
    } else {
        $newHash = password_hash($passBaru, PASSWORD_BCRYPT);
        $stmtUpdatePass = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
        $stmtUpdatePass->execute([$newHash, $userId]);

        setFlash('success', 'Kata sandi berhasil diubah! Silakan gunakan kata sandi baru untuk login berikutnya.');
        header("Location: " . base_url('profil.php'));
        exit();
    }
}

// Lencana pelajar (yang sudah diraih dan yang masih terkunci)
$daftarBadge = [];
$jumlahBadgeDapat = 0;
if ($user['role'] === 'pelajar') {
    $stmtBadge = $conn->prepare("
        SELECT b.*, ub.diperoleh_pada
        FROM badges b
        LEFT JOIN user_badges ub ON ub.id_badge = b.id_badge AND ub.id_pelajar = ?
        ORDER BY b.syarat_xp ASC
    ");
    $stmtBadge->execute([$userId]);
    $daftarBadge = $stmtBadge->fetchAll();
    foreach ($daftarBadge as $bdg) {
        if ($bdg['diperoleh_pada']) $jumlahBadgeDapat++;
    }
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url(); ?>" class="hover:text-[#0066CC]">Beranda</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Profil Pengguna</span>
    </div>
    <h1 class="text-2xl font-extrabold text-slate-800">Pengaturan Profil & Akun</h1>
    <p class="text-xs text-slate-500">Kelola identitas diri, foto profil, dan kredensial keamanan akun Questudy Anda.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- KARTU RINGKASAN PROFIL (SISI KIRI) -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm text-center h-fit">
        <div class="relative inline-block mx-auto mb-4">
            <img src="<?= base_url('uploads/profil/' . e($user['foto_profil'])); ?>" alt="Avatar" class="w-28 h-28 rounded-full object-cover ring-4 ring-slate-100 shadow-md" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['nama']); ?>&size=128&background=0066CC&color=fff';">
            <span class="absolute bottom-1 right-1 w-6 h-6 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-white text-xs" title="Akun Aktif">
                <i class="fa-solid fa-check"></i>
            </span>
        </div>

        <h2 class="text-lg font-bold text-slate-800"><?= e($user['nama']); ?></h2>
        <p class="text-xs text-slate-500 mt-0.5"><?= e($user['email']); ?></p>

        <div class="mt-3 flex items-center justify-center gap-2">
            <span class="px-2.5 py-1 rounded-full text-xs font-bold 
                <?php 
                    if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-700';
                    elseif ($user['role'] === 'pengajar') echo 'bg-blue-100 text-[#0066CC]';
                    else echo 'bg-emerald-100 text-emerald-700';
                ?>">
                <?= ucfirst($user['role']); ?>
            </span>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                <?= e($user['status_akun']); ?>
            </span>
        </div>

        <?php if ($user['role'] === 'pelajar'): ?>
            <!-- STATS KHUSUS PELAJAR -->
            <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-3 gap-2">
                <div class="p-2.5 rounded-lg bg-amber-50/70 border border-amber-200/60">
                    <div class="text-base font-extrabold text-amber-600">Lvl <?= (int)$user['level']; ?></div>
                    <div class="text-[10px] font-semibold text-slate-500 uppercase">Level</div>
                </div>
                <div class="p-2.5 rounded-lg bg-blue-50/70 border border-blue-200/60">
                    <div class="text-base font-extrabold text-[#0066CC]"><?= number_format($user['xp']); ?></div>
                    <div class="text-[10px] font-semibold text-slate-500 uppercase">XP Poin</div>
                </div>
                <div class="p-2.5 rounded-lg bg-orange-50/70 border border-orange-200/60">
                    <div class="text-base font-extrabold text-orange-600"><?= (int)$user['streak_harian']; ?> Hari</div>
                    <div class="text-[10px] font-semibold text-slate-500 uppercase">Streak</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-6 pt-5 border-t border-slate-100 text-left text-xs text-slate-500 space-y-2">
            <div class="flex items-center justify-between">
                <span>Bergabung:</span>
                <span class="font-semibold text-slate-700"><?= date('d M Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span>Login Terakhir:</span>
                <span class="font-semibold text-slate-700"><?= $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : '-'; ?></span>
            </div>
        </div>
    </div>

    <!-- FORM EDIT PROFIL & GANTI PASSWORD (SISI KANAN) -->
    <div class="lg:col-span-2 space-y-6">

        <?php if ($user['role'] === 'pelajar' && !empty($daftarBadge)): ?>
        <!-- LENCANA -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-800">Lencana Saya</h3>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-700"><?= $jumlahBadgeDapat; ?> dari <?= count($daftarBadge); ?></span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php foreach ($daftarBadge as $bdg): $dapat = !empty($bdg['diperoleh_pada']); ?>
                    <div title="<?= e($bdg['deskripsi']); ?>" class="p-4 rounded-2xl text-center transition hover:-translate-y-1 hover:shadow-lg <?= $dapat ? 'bg-gradient-to-br from-amber-100 to-amber-50 border border-amber-200' : 'bg-slate-50 border border-slate-200'; ?>">
                        <div class="text-4xl <?= $dapat ? '' : 'grayscale opacity-40'; ?>"><?= e($bdg['ikon']); ?></div>
                        <div class="mt-2 text-sm font-bold <?= $dapat ? 'text-slate-800' : 'text-slate-500'; ?>"><?= e($bdg['nama_badge']); ?></div>
                        <div class="mt-0.5 text-xs text-slate-500">
                            <?php if ($dapat): ?>
                                Diraih <?= date('d M Y', strtotime($bdg['diperoleh_pada'])); ?>
                            <?php else: ?>
                                <i class="fa-solid fa-lock text-[10px] mr-1"></i>Butuh <?= number_format($bdg['syarat_xp']); ?> XP
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- FORM 1: DATA DIRI & FOTO -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-800 mb-1">Informasi Data Pribadi</h3>
            <p class="text-xs text-slate-500 mb-5">Perbarui nama tampilan, bidang keahlian, dan unggah foto profil terbaru.</p>

            <?php if (!empty($errorProfil)): ?>
                <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-xs flex items-center gap-2 border border-red-200">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= e($errorProfil); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="update_profil">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= e($user['nama']); ?>" required class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email (Permanen)</label>
                        <input type="email" value="<?= e($user['email']); ?>" disabled class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keahlian / Minat Akademik</label>
                    <input type="text" name="keahlian" value="<?= e($user['keahlian'] ?? ''); ?>" placeholder="Contoh: Web Programming, AI, Algoritma" class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unggah Foto Profil Baru</label>
                    <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0066CC] hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Mendukung format JPG, PNG, atau WEBP. Ukuran maksimal 2MB.</p>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                        Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>

        <!-- FORM 2: GANTI KATA SANDI -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-800 mb-1">Keamanan & Kata Sandi</h3>
            <p class="text-xs text-slate-500 mb-5">Pastikan menggunakan kombinasi kata sandi yang kuat untuk menjaga keamanan akun Anda.</p>

            <?php if (!empty($errorPass)): ?>
                <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-xs flex items-center gap-2 border border-red-200">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= e($errorPass); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="ganti_password">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Saat Ini</label>
                    <input type="password" name="password_lama" required placeholder="••••••••" class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru</label>
                        <input type="password" name="password_baru" required placeholder="Min 6 karakter" class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Sandi Baru</label>
                        <input type="password" name="konfirmasi_password" required placeholder="Ulangi sandi baru" class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition">
                    </div>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition shadow-sm">
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
