<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';

if (isset($_SESSION['id_user'])) {
    header("Location: " . base_url());
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';
    $role = $_POST['role'] ?? 'pelajar';
    $keahlian = trim($_POST['keahlian'] ?? '');

    // Validasi
    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan kata sandi wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal terdiri dari 6 karakter!';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi kata sandi tidak cocok!';
    } elseif (!in_array($role, ['pelajar', 'pengajar'])) {
        $error = 'Peran yang dipilih tidak valid!';
    } else {
        // Cek email ganda
        $stmtCheck = $conn->prepare("SELECT id_user FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $error = 'Alamat email sudah terdaftar! Silakan gunakan email lain atau login.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Status Akun: Pengajar butuh persetujuan admin (pending), Pelajar langsung aktif
            $statusAkun = ($role === 'pengajar') ? 'pending' : 'aktif';

            $stmtInsert = $conn->prepare("
                INSERT INTO users (nama, email, password, role, status_akun, keahlian, foto_profil, xp, level, streak_harian, created_at)
                VALUES (?, ?, ?, ?, ?, 'default.png', 0, 1, 1, NOW())
            ");
            $success = $stmtInsert->execute([$nama, $email, $hashedPassword, $role, $statusAkun, $keahlian ?: null]);

            if ($success) {
                if ($role === 'pengajar') {
                    setFlash('warning', 'Pendaftaran berhasil! Akun Pengajar Anda saat ini berstatus PENDING dan sedang menunggu verifikasi dari Administrator.');
                } else {
                    setFlash('success', 'Pendaftaran berhasil! Akun Pelajar Anda telah aktif. Silakan masuk.');
                }
                header("Location: " . base_url('auth/login.php'));
                exit();
            } else {
                $error = 'Terjadi kesalahan sistem saat mendaftarkan akun. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun Baru — Questudy LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0F294A; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-[#0F294A] via-[#153660] to-slate-900 text-slate-800">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden my-8">
        <!-- Header Banner -->
        <div class="bg-[#0F294A] p-6 text-center text-white relative">
            <div class="w-12 h-12 mx-auto rounded-xl bg-gradient-to-tr from-[#0066CC] to-blue-400 flex items-center justify-center text-white text-2xl font-bold shadow-lg mb-3">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Daftar Questudy</h1>
            <p class="text-xs text-blue-200 mt-1">Bergabunglah dalam Ekosistem Belajar Inklusif SDGs 4</p>
        </div>

        <div class="p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Buat Akun Baru</h2>
            <p class="text-xs text-slate-500 mb-5">Lengkapi formulir di bawah untuk memulai perjalanan belajar Anda.</p>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-base flex-shrink-0"></i>
                    <span><?= e($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="nama" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition" placeholder="Contoh: Budi Santoso" value="<?= e($_POST['nama'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email</label>
                    <input type="email" name="email" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition" placeholder="nama@email.com" value="<?= e($_POST['email'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Peran (Role)</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:border-blue-400 cursor-pointer transition has-[:checked]:border-[#0066CC] has-[:checked]:bg-blue-50/50">
                            <input type="radio" name="role" value="pelajar" class="text-[#0066CC] focus:ring-[#0066CC]" checked>
                            <div>
                                <div class="text-xs font-bold text-slate-800">Pelajar</div>
                                <div class="text-[10px] text-slate-500">Langsung Aktif</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:border-blue-400 cursor-pointer transition has-[:checked]:border-[#0066CC] has-[:checked]:bg-blue-50/50">
                            <input type="radio" name="role" value="pengajar" class="text-[#0066CC] focus:ring-[#0066CC]" <?= (($_POST['role'] ?? '') === 'pengajar') ? 'checked' : ''; ?>>
                            <div>
                                <div class="text-xs font-bold text-slate-800">Pengajar</div>
                                <div class="text-[10px] text-amber-600">Perlu Verifikasi</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keahlian / Bidang Minat (Opsional)</label>
                    <input type="text" name="keahlian" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition" placeholder="Contoh: Web Development, Algoritma, UI/UX" value="<?= e($_POST['keahlian'] ?? ''); ?>">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                        <input type="password" name="password" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition" placeholder="Min 6 karakter">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ulangi Sandi</label>
                        <input type="password" name="konfirmasi_password" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition" placeholder="Sama dengan sandi">
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition duration-150">
                    Daftar Sekarang
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-500">
                Sudah memiliki akun? 
                <a href="<?= base_url('auth/login.php'); ?>" class="text-[#0066CC] font-bold hover:underline">Masuk di sini</a>
            </div>

            <div class="mt-4 text-center">
                <a href="<?= base_url(); ?>" class="text-xs text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

</body>
</html>
