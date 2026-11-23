<?php
/**
 * QUESTUDY — Database Connection & Global Helper Functions
 * Platform Pembelajaran Kolaboratif, Gamifikasi & Early Warning
 * Tema SDGs 4: Quality Education
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inactivity timeout: 30 menit (1800 detik)
if (isset($_SESSION['id_user'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 30 menit. Silakan login kembali.'];
        header("Location: " . base_url('auth/login.php'));
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Database Credentials
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_questudy';
$db_port = 3306;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $conn = new PDO($dsn, $db_user, $db_pass, $options);
    $pdo = $conn; // Alias
} catch (PDOException $e) {
    // Jika database belum dibuat atau koneksi bermasalah
    die("<div style='font-family:sans-serif;padding:24px;background:#FEF2F2;border:1px solid #F87171;color:#991B1B;border-radius:12px;margin:24px auto;max-width:600px;'>
        <h3 style='margin:0 0 8px 0;font-size:18px;'>Gagal Menghubungkan ke Database Questudy</h3>
        <p style='margin:0 0 12px 0;font-size:14px;color:#7F1D1D;'>Pastikan MySQL di Laragon telah aktif dan database <code>db_questudy</code> sudah diimpor dari file <code>database.sql</code>.</p>
        <small style='color:#B91C1C;'>Detail Kesalahan: " . htmlspecialchars($e->getMessage()) . "</small>
    </div>");
}

/**
 * Base URL helper untuk path dinamis di Laragon
 */
function base_url($path = '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    // Cari root project Questudy
    $projectDir = str_replace('\\', '/', dirname(__DIR__));
    $relPath = str_replace($docRoot, '', $projectDir);
    $base = '/' . trim($relPath, '/');
    if ($base === '/') {
        $base = '';
    }
    
    $path = ltrim($path, '/');
    return ($base ? $base . '/' : '/') . $path;
}

/**
 * Sanitasi output teks untuk mencegah Cross-Site Scripting (XSS)
 */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message helpers
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Proteksi autentikasi & otorisasi role
 */
function cekRole(...$roles) {
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        setFlash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header("Location: " . base_url('auth/login.php'));
        exit();
    }
    
    if (!empty($roles) && !in_array($_SESSION['role'], $roles)) {
        setFlash('error', 'Akses ditolak! Anda tidak memiliki izin untuk halaman tersebut.');
        if ($_SESSION['role'] === 'admin') {
            header("Location: " . base_url('admin/dashboard.php'));
        } elseif ($_SESSION['role'] === 'pengajar') {
            header("Location: " . base_url('pengajar/dashboard.php'));
        } else {
            header("Location: " . base_url('pelajar/dashboard.php'));
        }
        exit();
    }
}

/**
 * Logika Gamifikasi: Tambah XP, Hitung Level, dan Buka Badge Otomatis
 * Level = floor(xp / 100) + 1
 */
function tambahXP($conn, $id_pelajar, $aksi) {
    try {
        // Ambil nilai XP dari tabel aturan
        $stmtAturan = $conn->prepare("SELECT xp_didapat, keterangan FROM gamifikasi_aturan WHERE aksi = ? LIMIT 1");
        $stmtAturan->execute([$aksi]);
        $aturan = $stmtAturan->fetch();
        
        $xpTambahan = $aturan ? (int)$aturan['xp_didapat'] : 10;
        
        // Ambil data XP saat ini
        $stmtUser = $conn->prepare("SELECT xp, level FROM users WHERE id_user = ?");
        $stmtUser->execute([$id_pelajar]);
        $user = $stmtUser->fetch();
        
        if (!$user) return false;
        
        $newXP = $user['xp'] + $xpTambahan;
        $newLevel = floor($newXP / 100) + 1;
        
        // Update user XP dan Level
        $stmtUpdate = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id_user = ?");
        $stmtUpdate->execute([$newXP, $newLevel, $id_pelajar]);
        
        // Update session jika yang login adalah pelajar tersebut
        if (isset($_SESSION['id_user']) && $_SESSION['id_user'] == $id_pelajar) {
            $_SESSION['xp'] = $newXP;
            $_SESSION['level'] = $newLevel;
        }
        
        // Periksa dan berikan badge baru jika syarat XP terpenuhi
        $stmtBadge = $conn->prepare("
            SELECT b.id_badge, b.nama_badge 
            FROM badges b 
            WHERE b.syarat_xp <= ? 
            AND b.id_badge NOT IN (
                SELECT id_badge FROM user_badges WHERE id_pelajar = ?
            )
        ");
        $stmtBadge->execute([$newXP, $id_pelajar]);
        $unlockedBadges = $stmtBadge->fetchAll();
        
        $newBadgesUnlocked = [];
        if (!empty($unlockedBadges)) {
            $stmtInsertUB = $conn->prepare("INSERT INTO user_badges (id_pelajar, id_badge) VALUES (?, ?)");
            foreach ($unlockedBadges as $b) {
                $stmtInsertUB->execute([$id_pelajar, $b['id_badge']]);
                $newBadgesUnlocked[] = $b['nama_badge'];
            }
        }
        
        return [
            'xp_didapat' => $xpTambahan,
            'xp_total' => $newXP,
            'level' => $newLevel,
            'badges_baru' => $newBadgesUnlocked
        ];
    } catch (Exception $e) {
        error_log("Error tambahXP: " . $e->getMessage());
        return false;
    }
}
