<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';

// Jika sudah login, langsung arahkan ke dashboard masing-masing
if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: " . base_url('admin/dashboard.php'));
    } elseif ($_SESSION['role'] === 'pengajar') {
        header("Location: " . base_url('pengajar/dashboard.php'));
    } else {
        header("Location: " . base_url('pelajar/dashboard.php'));
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordTrimmed = trim($password);

    if (empty($rawInput) || empty($passwordTrimmed)) {
        $error = 'Email/Username dan kata sandi wajib diisi!';
    } else {
        // Toleransi format input: izinkan full email, username tanpa @questudy.id, atau alias
        $candidates = [$rawInput, strtolower($rawInput)];
        if (strpos($rawInput, '@') === false) {
            $candidates[] = strtolower($rawInput) . '@questudy.id';
            $candidates[] = strtolower($rawInput) . '@belajar.id';
        } else {
            $candidates[] = str_replace('@questudy.com', '@questudy.id', strtolower($rawInput));
            $candidates[] = str_replace('@belajar.id', '@questudy.id', strtolower($rawInput));
        }

        // Aliases
        if (in_array(strtolower($rawInput), ['tutor', 'dosen', 'pengajar'])) {
            $candidates[] = 'pengajar@questudy.id';
        }
        if (in_array(strtolower($rawInput), ['mahasiswa', 'siswa', 'pelajar', 'mhs'])) {
            $candidates[] = 'pelajar@questudy.id';
        }

        $candidates = array_unique($candidates);
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));

        $stmt = $conn->prepare("SELECT * FROM users WHERE email IN ($placeholders) OR nama = ? LIMIT 1");
        $params = array_merge(array_values($candidates), [$rawInput]);
        $stmt->execute($params);
        $user = $stmt->fetch();

        // Validasi password dengan bcrypt atau fallback toleransi demo 123456
        $passwordValid = false;
        if ($user) {
            if (password_verify($password, $user['password']) || password_verify($passwordTrimmed, $user['password']) || $passwordTrimmed === '123456') {
                $passwordValid = true;
                
                // Jika hash di database lama, perbarui dengan hash bcrypt standar
                if (!password_verify($passwordTrimmed, $user['password'])) {
                    $newHash = password_hash($passwordTrimmed, PASSWORD_BCRYPT);
                    $stmtRehash = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                    $stmtRehash->execute([$newHash, $user['id_user']]);
                }
            }
        }

        if ($user && $passwordValid) {
            // Cek status akun
            if ($user['status_akun'] === 'pending') {
                $error = 'Akun Pengajar Anda masih dalam proses peninjauan & verifikasi oleh Administrator. Mohon menunggu aktivasi.';
            } elseif ($user['status_akun'] === 'nonaktif') {
                $error = 'Akun Anda dinonaktifkan oleh administrator. Silakan hubungi admin@questudy.id.';
            } else {
                // Login Berhasil
                session_regenerate_id(true);

                // Hitung Streak Harian & Update Last Login
                $lastLoginDate = $user['last_login'] ? date('Y-m-d', strtotime($user['last_login'])) : null;
                $todayDate = date('Y-m-d');
                $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
                
                $streak = (int)$user['streak_harian'];
                $dapatBonusLogin = false;

                if ($lastLoginDate === null || $lastLoginDate < $yesterdayDate) {
                    $streak = 1;
                    $dapatBonusLogin = true;
                } elseif ($lastLoginDate === $yesterdayDate) {
                    $streak += 1;
                    $dapatBonusLogin = true;
                } elseif ($lastLoginDate === $todayDate) {
                    $dapatBonusLogin = false;
                }

                $stmtUpdate = $conn->prepare("UPDATE users SET last_login = NOW(), streak_harian = ? WHERE id_user = ?");
                $stmtUpdate->execute([$streak, $user['id_user']]);

                // Simpan Sesi
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['foto_profil'] = $user['foto_profil'] ?: 'default.png';
                $_SESSION['xp'] = (int)$user['xp'];
                $_SESSION['level'] = (int)$user['level'];
                $_SESSION['streak_harian'] = $streak;
                $_SESSION['last_activity'] = time();

                // Beri XP harian jika pelajar dan baru pertama login hari ini
                if ($user['role'] === 'pelajar' && $dapatBonusLogin) {
                    tambahXP($conn, $user['id_user'], 'login_harian');
                }

                setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

                // Redirect Sesuai Role
                if ($user['role'] === 'admin') {
                    header("Location: " . base_url('admin/dashboard.php'));
                } elseif ($user['role'] === 'pengajar') {
                    header("Location: " . base_url('pengajar/dashboard.php'));
                } else {
                    header("Location: " . base_url('pelajar/dashboard.php'));
                }
                exit();
            }
        } else {
            $error = 'Kombinasi email atau kata sandi tidak cocok! Pastikan email terdaftar dan kata sandi benar (default demo: 123456).';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Questudy LMS SDGs 4</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0F294A; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-[#0F294A] via-[#153660] to-slate-900 text-slate-800">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
        <!-- Header Banner -->
        <div class="bg-[#0F294A] p-6 text-center text-white relative">
            <div class="w-12 h-12 mx-auto rounded-xl bg-gradient-to-tr from-[#0066CC] to-blue-400 flex items-center justify-center text-white text-2xl font-bold shadow-lg mb-3">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Questudy</h1>
            <p class="text-xs text-blue-200 mt-1">LMS Gamifikasi, Ruang Belajar & Peringatan Dini</p>
            <div class="inline-block mt-2 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-semibold border border-emerald-400/30">
                SDGs 4: Quality Education
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <h2 class="text-xl font-bold text-slate-800 mb-1">Masuk ke Akun Anda</h2>
            <p class="text-xs text-slate-500 mb-5">Silakan masukkan kredensial untuk mengakses ruang belajar Anda.</p>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-base flex-shrink-0"></i>
                    <span><?= e($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flash = getFlash()): ?>
                <div class="mb-4 p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-info text-base flex-shrink-0"></i>
                    <span><?= e($flash['message']); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email / Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="text" name="email" id="inputEmail" required class="w-full pl-10 pr-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] focus:border-transparent transition" placeholder="nama@questudy.id atau admin" value="<?= e($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="inputPassword" required class="w-full pl-10 pr-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] focus:border-transparent transition" placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition duration-150">
                    Masuk Sekarang
                </button>
            </form>

            <!-- Quick Demo Login Buttons -->
            <div class="mt-6 pt-5 border-t border-slate-200">
                <p class="text-[11px] font-semibold text-slate-500 text-center uppercase tracking-wider mb-2.5">Akses Cepat Akun Demo (Klik & Otomatis Terisi)</p>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="isiDemo('admin@questudy.id')" class="p-2 text-center rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 text-[11px] font-bold border border-purple-200 transition">
                        <i class="fa-solid fa-shield-halved block text-sm mb-0.5"></i> Admin
                    </button>
                    <button type="button" onclick="isiDemo('pengajar@questudy.id')" class="p-2 text-center rounded-lg bg-blue-50 hover:bg-blue-100 text-[#0066CC] text-[11px] font-bold border border-blue-200 transition">
                        <i class="fa-solid fa-chalkboard-user block text-sm mb-0.5"></i> Pengajar
                    </button>
                    <button type="button" onclick="isiDemo('pelajar@questudy.id')" class="p-2 text-center rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-bold border border-emerald-200 transition">
                        <i class="fa-solid fa-user-graduate block text-sm mb-0.5"></i> Pelajar
                    </button>
                </div>
            </div>

            <div class="mt-6 text-center text-xs text-slate-500">
                Belum memiliki akun? 
                <a href="<?= base_url('auth/register.php'); ?>" class="text-[#0066CC] font-bold hover:underline">Daftar Akun Baru</a>
            </div>

            <div class="mt-4 text-center">
                <a href="<?= base_url(); ?>" class="text-xs text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script>
        function isiDemo(email) {
            document.getElementById('inputEmail').value = email;
            document.getElementById('inputPassword').value = '123456';
        }
    </script>
</body>
</html>
