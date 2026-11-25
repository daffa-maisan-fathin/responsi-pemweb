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
        if (in_array(strtolower($rawInput), ['tutor', 'pengajar', 'pengajar'])) {
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

        // Validasi password dengan bcrypt
        $passwordValid = false;
        if ($user) {
            if (password_verify($password, $user['password']) || password_verify($passwordTrimmed, $user['password'])) {
                $passwordValid = true;
            }
        }

        if ($user && $passwordValid) {
            // Cek status akun
            if ($user['status_akun'] === 'pending') {
                $error = 'Akun Tutor Anda masih dalam proses peninjauan & verifikasi oleh Administrator. Mohon menunggu aktivasi.';
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
            $error = 'Kombinasi email atau kata sandi tidak cocok! Pastikan email terdaftar dan kata sandi benar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Questudy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { indigo:{50:'#F1F7FD',100:'#E6F1FA',200:'#C9E0F5',300:'#A3C8EE',400:'#7DB1E4',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0',800:'#264F82',900:'#1F3A5A'},blue:{50:'#F1F7FD',100:'#E6F1FA',200:'#C9E0F5',300:'#A3C8EE',400:'#7DB1E4',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0',800:'#264F82',900:'#1F3A5A'},sky:{50:'#F2F9FE',100:'#E1F0FC',200:'#BFDFF7',300:'#93C8F0',400:'#6BB0E7',500:'#5798D7',600:'#3D7BBF',700:'#2F62A0'},violet:{50:'#FAF3FE',100:'#F4E4FC',200:'#E7BEF8',300:'#D9A0F1',400:'#C67FE6',500:'#AE63D6',600:'#944FBA',700:'#783F96'},amber:{50:'#FEFDEB',100:'#FBF8CC',200:'#F6F1A4',300:'#EDE986',400:'#E1DC5E',500:'#C8C23A',600:'#8F8A18',700:'#6F6A12',800:'#55510F',900:'#3F3C0C',gold:'#F59E0B'},emerald:{50:'#F7F9E8',100:'#EEF2CC',200:'#DFE79F',300:'#CCD777',400:'#B9C255',500:'#9DA83C',600:'#6E7A22',700:'#566018',800:'#434B13',900:'#33390F',fresh:'#10B981'},pink:{50:'#FEF1F6',100:'#FDE3EE',200:'#FAC5DC',300:'#F79BC0',400:'#F47BAE',500:'#F2619C',600:'#D84582',700:'#B2336A'},rose:{50:'#FEF1F6',100:'#FDE3EE',200:'#FAC5DC',300:'#F79BC0',400:'#F47BAE',500:'#F2619C',600:'#D84582',700:'#B2336A'} }, fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] } } } }</script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .grad { background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF); }
        .grad-text { background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .btn-grad { background-image: linear-gradient(135deg, #6CA9E4, #3D7BBF); color: #fff; transition: transform .18s ease, box-shadow .18s ease, filter .18s ease; }
        .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(61, 123, 191, .4); filter: brightness(1.06); }
        .lift { transition: transform .2s ease, box-shadow .2s ease; }
        .lift:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(61, 123, 191, .2); }
        .float-a { animation: fl 6s ease-in-out infinite; }
        .float-b { animation: fl 7s ease-in-out infinite reverse; }
        @keyframes fl { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        details > summary { list-style: none; cursor: pointer; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .chev { transform: rotate(180deg); }
        .field { width: 100%; padding: .65rem 1rem; border-radius: 1rem; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 15px; transition: all .18s ease; }
        .field:focus { outline: none; background: #fff; border-color: #78AADE; box-shadow: 0 0 0 4px rgba(120, 170, 222, .2); }
        .field:hover { border-color: #BED8F1; }
    </style>
    <?= maskotGayaCss(); ?>
</head>
<body class="font-sans text-slate-800 antialiased">
<div class="min-h-screen grid lg:grid-cols-2">

    <!-- Panel deskripsi -->
    <section class="relative hidden lg:flex flex-col overflow-hidden px-10 py-8 text-white bg-gradient-to-br from-indigo-600 via-indigo-500 to-sky-400">
        <?= latarLaut(); ?>
        <div class="relative z-10 flex-1 flex flex-col">
            <a href="<?= base_url(); ?>" class="flex items-center gap-3 text-lg font-extrabold">
                <span class="w-10 h-10 rounded-2xl bg-white/20 grid place-items-center"><i class="fa-solid fa-graduation-cap"></i></span>
                Questudy
            </a>
            <div class="my-auto pb-32">
                <h1 class="text-4xl xl:text-5xl font-extrabold leading-tight">Belajar jadi<br>lebih seru.</h1>
                <p class="mt-3 text-base text-white/85 max-w-sm">Kumpulkan XP, jaga streak, dan temukan tutor yang pas.</p>
                <div class="mt-6 space-y-2.5 max-w-sm">
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 backdrop-blur-sm hover:bg-white/25 hover:translate-x-1 transition">
                        <span class="w-9 h-9 rounded-xl bg-white/25 grid place-items-center"><i class="fa-solid fa-book-open"></i></span>
                        <span class="font-semibold text-sm">Kelas skill dari banyak tutor</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 backdrop-blur-sm hover:bg-white/25 hover:translate-x-1 transition">
                        <span class="w-9 h-9 rounded-xl bg-white/25 grid place-items-center"><i class="fa-solid fa-bolt"></i></span>
                        <span class="font-semibold text-sm">XP, level, dan lencana</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 backdrop-blur-sm hover:bg-white/25 hover:translate-x-1 transition">
                        <span class="w-9 h-9 rounded-xl bg-white/25 grid place-items-center"><i class="fa-solid fa-hand-holding-heart"></i></span>
                        <span class="font-semibold text-sm">Tutor siap mendampingi</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form -->
    <section class="flex items-center justify-center p-6 bg-gradient-to-br from-sky-50 via-white to-violet-50">
        <div class="w-full max-w-md">
            <div class="lg:hidden relative overflow-hidden rounded-3xl h-36 mb-4 text-white bg-gradient-to-br from-indigo-600 via-indigo-500 to-sky-400">
                <?= latarLaut(); ?>
                <div class="relative z-10 p-5">
                    <div class="flex items-center gap-2 text-lg font-extrabold"><i class="fa-solid fa-graduation-cap"></i> Questudy</div>
                    <p class="text-sm text-white/85 mt-1">Belajar jadi lebih seru.</p>
                </div>
            </div>
                        <div class="bg-white rounded-[28px] shadow-xl shadow-indigo-100 border border-indigo-50 p-6">
                <div class="w-12 h-12 mx-auto rounded-2xl grad grid place-items-center text-white text-xl shadow-lg shadow-indigo-200">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <h2 class="mt-3 text-center text-2xl font-extrabold grad-text">Masuk</h2>
                <p class="text-center text-slate-500 mt-1">Lanjutkan belajarmu.</p>

                <?php if ($flash = getFlash()): ?>
                    <div class="mt-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"><?= e($flash['message']); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="mt-5 p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-start gap-2">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i><span><?= e($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="formLogin" class="mt-5 space-y-3.5">
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" name="email" id="inputEmail" required placeholder="Email" value="<?= e($_POST['email'] ?? ''); ?>" class="field !pl-11">
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" name="password" id="inputPassword" required placeholder="Kata sandi" class="field !pl-11 !pr-20">
                        <button type="button" id="togglePw" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-indigo-600 hover:text-indigo-800 transition">Lihat</button>
                    </div>
                    <button type="submit" class="btn-grad w-full py-3.5 rounded-full text-base font-bold">Masuk</button>
                </form>

                <p class="mt-4 text-center text-slate-500 text-sm">
                    Belum punya akun?
                    <a href="<?= base_url('auth/register.php'); ?>" class="font-bold text-indigo-600 hover:underline">Daftar</a>
                </p>
            </div>
            <p class="mt-3 text-center">
                <a href="<?= base_url(); ?>" class="text-sm text-slate-400 hover:text-indigo-600 transition"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke beranda</a>
            </p>
        </div>
    </section>
</div>

<script>
    document.getElementById('togglePw').addEventListener('click', function () {
        var p = document.getElementById('inputPassword');
        var tampil = p.type === 'password';
        p.type = tampil ? 'text' : 'password';
        this.textContent = tampil ? 'Sembunyi' : 'Lihat';
    });
</script>
</body>
</html>
