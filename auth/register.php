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
    $deskripsiTutor = trim($_POST['deskripsi_tutor'] ?? '');
    $portofolio = trim($_POST['portofolio_url'] ?? '');
    $setuju = isset($_POST['setuju']);

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
    } elseif (!$setuju) {
        $error = 'Anda harus menyetujui aturan penggunaan untuk mendaftar!';
    } elseif ($role === 'pengajar' && ($keahlian === '' || strlen($deskripsiTutor) < 30 || !filter_var($portofolio, FILTER_VALIDATE_URL))) {
        $error = 'Tutor wajib mengisi keahlian, deskripsi (minimal 30 karakter), dan tautan portofolio yang valid!';
    } else {
        // Cek email ganda
        $stmtCheck = $conn->prepare("SELECT id_user FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $error = 'Alamat email sudah terdaftar! Silakan gunakan email lain atau login.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Status Akun: Tutor butuh persetujuan admin (pending), Pelajar langsung aktif
            $statusAkun = ($role === 'pengajar') ? 'pending' : 'aktif';

            $stmtInsert = $conn->prepare("
                INSERT INTO users (nama, email, password, role, status_akun, keahlian, deskripsi_tutor, portofolio_url, foto_profil, xp, level, streak_harian, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'default.png', 0, 1, 1, NOW())
            ");
            $success = $stmtInsert->execute([$nama, $email, $hashedPassword, $role, $statusAkun, $keahlian ?: null, ($role === 'pengajar') ? $deskripsiTutor : null, ($role === 'pengajar') ? $portofolio : null]);

            if ($success) {
                if ($role === 'pengajar') {
                    setFlash('warning', 'Pendaftaran berhasil! Akun Tutor Anda saat ini berstatus PENDING dan sedang menunggu verifikasi dari Administrator.');
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
    <title>Daftar — Questudy</title>
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
                <h1 class="text-4xl xl:text-5xl font-extrabold leading-tight">Gabung,<br>yuk!</h1>
                <p class="mt-3 text-base text-white/85 max-w-sm">Satu akun untuk satu peran.</p>
                <div class="mt-6 space-y-2.5 max-w-sm">
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 backdrop-blur-sm hover:bg-white/25 hover:translate-x-1 transition">
                        <span class="w-9 h-9 rounded-xl bg-white/25 grid place-items-center"><i class="fa-solid fa-user-graduate"></i></span>
                        <span class="font-semibold text-sm">Pelajar: gabung kelas, kumpulkan XP</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 backdrop-blur-sm hover:bg-white/25 hover:translate-x-1 transition">
                        <span class="w-9 h-9 rounded-xl bg-white/25 grid place-items-center"><i class="fa-solid fa-chalkboard-user"></i></span>
                        <span class="font-semibold text-sm">Tutor: bagikan keahlian, buka kelas</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 backdrop-blur-sm hover:bg-white/25 hover:translate-x-1 transition">
                        <span class="w-9 h-9 rounded-xl bg-white/25 grid place-items-center"><i class="fa-solid fa-shield-heart"></i></span>
                        <span class="font-semibold text-sm">Tutor diverifikasi admin</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form -->
    <section class="flex items-center justify-center p-6 bg-gradient-to-br from-violet-50 via-white to-sky-50">
        <div class="w-full max-w-md py-6">
            <div class="bg-white rounded-[28px] shadow-xl shadow-indigo-100 border border-indigo-50 p-6">
                <h2 class="text-center text-3xl font-extrabold grad-text">Buat akun</h2>
                <p class="text-center text-slate-500 mt-1">Gratis dan cepat.</p>

                <?php if (!empty($error)): ?>
                    <div class="mt-5 p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-start gap-2">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i><span><?= e($error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="mt-5 space-y-3">
                    <input type="text" name="nama" required placeholder="Nama lengkap" value="<?= e($_POST['nama'] ?? ''); ?>" class="field">
                    <input type="email" name="email" required placeholder="Email" value="<?= e($_POST['email'] ?? ''); ?>" class="field">

                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="pelajar" class="peer sr-only" <?= (($_POST['role'] ?? 'pelajar') !== 'pengajar') ? 'checked' : ''; ?>>
                            <div class="p-4 rounded-2xl border-2 border-slate-200 text-center transition hover:border-indigo-300 hover:-translate-y-0.5 peer-checked:border-indigo-500 peer-checked:bg-indigo-50">
                                <i class="fa-solid fa-user-graduate text-xl text-indigo-500"></i>
                                <div class="font-bold mt-1">Pelajar</div>
                                <div class="text-xs text-slate-500">Langsung aktif</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="pengajar" class="peer sr-only" <?= (($_POST['role'] ?? '') === 'pengajar') ? 'checked' : ''; ?>>
                            <div class="p-4 rounded-2xl border-2 border-slate-200 text-center transition hover:border-sky-300 hover:-translate-y-0.5 peer-checked:border-sky-500 peer-checked:bg-sky-50">
                                <i class="fa-solid fa-chalkboard-user text-xl text-sky-500"></i>
                                <div class="font-bold mt-1">Tutor</div>
                                <div class="text-xs text-slate-500">Perlu verifikasi</div>
                            </div>
                        </label>
                    </div>

                    <input type="text" name="keahlian" value="<?= e($_POST['keahlian'] ?? ''); ?>" placeholder="Keahlian atau minat (contoh: Desain grafis)" class="field">

                    <div id="blokTutor" class="space-y-4 <?= (($_POST['role'] ?? '') === 'pengajar') ? '' : 'hidden'; ?>">
                        <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
                            Akun tutor disetujui admin dulu sebelum bisa membuat kelas.
                        </div>
                        <textarea name="deskripsi_tutor" rows="3" placeholder="Pengalamanmu dan yang bisa kamu ajarkan (min. 30 karakter)" class="field"><?= e($_POST['deskripsi_tutor'] ?? ''); ?></textarea>
                        <input type="url" name="portofolio_url" value="<?= e($_POST['portofolio_url'] ?? ''); ?>" placeholder="Tautan portofolio (https://...)" class="field">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="password" name="password" required placeholder="Kata sandi" class="field">
                        <input type="password" name="konfirmasi_password" required placeholder="Ulangi sandi" class="field">
                    </div>

                    <label class="flex items-start gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="setuju" class="mt-1 accent-indigo-500" <?= isset($_POST['setuju']) ? 'checked' : ''; ?>>
                        <span>Saya setuju memakai platform ini dengan sopan dan jujur.</span>
                    </label>

                    <button type="submit" class="btn-grad w-full py-3.5 rounded-full text-base font-bold">Daftar</button>
                </form>

                <p class="mt-5 text-center text-slate-500">
                    Sudah punya akun?
                    <a href="<?= base_url('auth/login.php'); ?>" class="font-bold text-indigo-600 hover:underline">Masuk</a>
                </p>
            </div>
            <p class="mt-5 text-center">
                <a href="<?= base_url(); ?>" class="text-sm text-slate-400 hover:text-indigo-600 transition"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke beranda</a>
            </p>
        </div>
    </section>
</div>

<script>
    document.querySelectorAll('input[name="role"]').forEach(function (r) {
        r.addEventListener('change', function () {
            var tutor = document.querySelector('input[name="role"]:checked').value === 'pengajar';
            document.getElementById('blokTutor').classList.toggle('hidden', !tutor);
        });
    });
</script>
</body>
</html>
