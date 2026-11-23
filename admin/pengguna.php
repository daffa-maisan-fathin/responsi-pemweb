<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('admin');

$pageTitle = 'Kelola Pengguna — Questudy';
$userId = $_SESSION['id_user'];

$filter = $_GET['filter'] ?? 'semua'; // 'semua', 'pending', 'pelajar', 'pengajar', 'admin'
$q = trim($_GET['q'] ?? '');

// Handle Verifikasi Cepat (Terima / Tolak)
if (isset($_GET['action']) && $_GET['action'] === 'verifikasi' && isset($_GET['id']) && isset($_GET['status'])) {
    $targetId = (int)$_GET['id'];
    $statusAksi = $_GET['status'];

    if ($statusAksi === 'aktif') {
        $stmtVerif = $conn->prepare("UPDATE users SET status_akun = 'aktif' WHERE id_user = ? AND role = 'pengajar'");
        $stmtVerif->execute([$targetId]);
        setFlash('success', 'Akun Pengajar berhasil diverifikasi dan diaktifkan!');
    } elseif ($statusAksi === 'tolak') {
        $stmtTolak = $conn->prepare("DELETE FROM users WHERE id_user = ? AND role = 'pengajar' AND status_akun = 'pending'");
        $stmtTolak->execute([$targetId]);
        setFlash('info', 'Pendaftaran akun pengajar ditolak dan telah dihapus.');
    }
    header("Location: " . base_url('admin/pengguna.php?filter=' . $filter));
    exit();
}

// Handle Reset Password ke 123456
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $targetId = (int)$_POST['id_user'];
    $defaultHash = password_hash('123456', PASSWORD_BCRYPT);

    $stmtReset = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
    $stmtReset->execute([$defaultHash, $targetId]);

    setFlash('success', 'Kata sandi pengguna berhasil direset ke nilai default (123456).');
    header("Location: " . base_url('admin/pengguna.php?filter=' . $filter));
    exit();
}

// Handle Toggle Status Akun (Aktif / Nonaktif)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $targetId = (int)$_POST['id_user'];
    $newStatus = ($_POST['current_status'] === 'aktif') ? 'nonaktif' : 'aktif';

    if ($targetId === $userId) {
        setFlash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri!');
    } else {
        $stmtToggle = $conn->prepare("UPDATE users SET status_akun = ? WHERE id_user = ?");
        $stmtToggle->execute([$newStatus, $targetId]);
        setFlash('success', "Status akun berhasil diubah menjadi: $newStatus.");
    }
    header("Location: " . base_url('admin/pengguna.php?filter=' . $filter));
    exit();
}

// Handle Hapus Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_user') {
    $targetId = (int)$_POST['id_user'];

    if ($targetId === $userId) {
        setFlash('error', 'Anda tidak dapat menghapus akun Anda sendiri!');
    } else {
        $stmtDel = $conn->prepare("DELETE FROM users WHERE id_user = ?");
        $stmtDel->execute([$targetId]);
        setFlash('info', 'Akun pengguna berhasil dihapus dari sistem.');
    }
    header("Location: " . base_url('admin/pengguna.php?filter=' . $filter));
    exit();
}

// Handle Tambah Pengguna Baru oleh Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_user') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '123456';
    $role = $_POST['role'] ?? 'pelajar';
    $status = $_POST['status_akun'] ?? 'aktif';
    $keahlian = trim($_POST['keahlian'] ?? '');

    if (empty($nama) || empty($email)) {
        setFlash('error', 'Nama dan alamat email wajib diisi!');
    } else {
        // Cek email kembar
        $stmtCekE = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmtCekE->execute([$email]);
        if ($stmtCekE->fetch()) {
            setFlash('error', 'Alamat email sudah terdaftar di sistem!');
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmtNew = $conn->prepare("
                INSERT INTO users (nama, email, password, role, status_akun, keahlian, foto_profil, xp, level, streak_harian, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'default.png', 0, 1, 1, NOW())
            ");
            $stmtNew->execute([$nama, $email, $hash, $role, $status, $keahlian ?: null]);
            setFlash('success', "Pengguna baru ($nama) berhasil didaftarkan!");
        }
    }
    header("Location: " . base_url('admin/pengguna.php?filter=' . $filter));
    exit();
}

// Query Pengguna Sesuai Filter & Pencarian
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($filter === 'pending') {
    $sql .= " AND status_akun = 'pending'";
} elseif ($filter === 'pelajar') {
    $sql .= " AND role = 'pelajar'";
} elseif ($filter === 'pengajar') {
    $sql .= " AND role = 'pengajar' AND status_akun != 'pending'";
} elseif ($filter === 'admin') {
    $sql .= " AND role = 'admin'";
}

if (!empty($q)) {
    $sql .= " AND (nama LIKE ? OR email LIKE ? OR keahlian LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$userList = $stmt->fetchAll();

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 13) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('admin/dashboard.php'); ?>" class="hover:text-[#0066CC]">Dashboard</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Kelola Pengguna</span>
    </div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Manajemen Pengguna & Verifikasi Pengajar</h1>
            <p class="text-xs text-slate-500">Verifikasi calon pengajar, atur hak akses peran, atur status akun, dan reset kata sandi.</p>
        </div>
        <div>
            <button onclick="document.getElementById('modalTambahUser').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-user-plus"></i>
                <span>Tambah Pengguna Baru</span>
            </button>
        </div>
    </div>
</div>

<!-- FILTER TABS & SEARCH -->
<div class="bg-white rounded-xl border border-[#E2E8F0] p-4 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <!-- Tabs -->
    <div class="flex flex-wrap items-center gap-1.5 text-xs font-bold">
        <a href="?filter=semua" class="px-3.5 py-2 rounded-lg transition <?= ($filter === 'semua') ? 'bg-[#0066CC] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            Semua Pengguna
        </a>
        <a href="?filter=pending" class="px-3.5 py-2 rounded-lg transition <?= ($filter === 'pending') ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200'; ?>">
            Pending Verifikasi
        </a>
        <a href="?filter=pelajar" class="px-3.5 py-2 rounded-lg transition <?= ($filter === 'pelajar') ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            Pelajar
        </a>
        <a href="?filter=pengajar" class="px-3.5 py-2 rounded-lg transition <?= ($filter === 'pengajar') ? 'bg-[#0066CC] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            Pengajar Aktif
        </a>
        <a href="?filter=admin" class="px-3.5 py-2 rounded-lg transition <?= ($filter === 'admin') ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            Admin
        </a>
    </div>

    <!-- Search input -->
    <form action="" method="GET" class="flex items-center gap-2">
        <input type="hidden" name="filter" value="<?= e($filter); ?>">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" name="q" value="<?= e($q); ?>" placeholder="Cari nama atau email..." class="pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC]">
        </div>
        <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
            Cari
        </button>
        <?php if (!empty($q)): ?>
            <a href="?filter=<?= e($filter); ?>" class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- TABEL PENGGUNA (Screen 13) -->
<div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
    <div class="p-4 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-700 flex items-center justify-between">
        <span>Daftar Data Pengguna (<?= count($userList); ?> Akun)</span>
    </div>

    <?php if (empty($userList)): ?>
        <p class="text-xs text-slate-400 p-8 text-center italic">Tidak ada pengguna yang sesuai dengan filter pencarian.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3.5">Nama & Email</th>
                        <th class="p-3.5">Peran</th>
                        <th class="p-3.5">Status Akun</th>
                        <th class="p-3.5">Keahlian / Info</th>
                        <th class="p-3.5">XP & Level</th>
                        <th class="p-3.5">Terdaftar</th>
                        <th class="p-3.5 text-right">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($userList as $u): ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <img src="<?= base_url('uploads/profil/' . e($u['foto_profil'])); ?>" class="w-8 h-8 rounded-full object-cover border" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u['nama']); ?>&background=0066CC&color=fff';">
                                    <div>
                                        <span class="font-bold text-slate-800 block"><?= e($u['nama']); ?></span>
                                        <span class="text-[10px] text-slate-400"><?= e($u['email']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold 
                                    <?php 
                                        if ($u['role'] === 'admin') echo 'bg-purple-100 text-purple-700';
                                        elseif ($u['role'] === 'pengajar') echo 'bg-blue-100 text-[#0066CC]';
                                        else echo 'bg-emerald-100 text-emerald-700';
                                    ?>">
                                    <?= ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                    <?php 
                                        if ($u['status_akun'] === 'aktif') echo 'bg-emerald-100 text-emerald-700';
                                        elseif ($u['status_akun'] === 'pending') echo 'bg-amber-100 text-amber-800 animate-pulse';
                                        else echo 'bg-red-100 text-red-700';
                                    ?>">
                                    <?= ucfirst($u['status_akun']); ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-slate-600 truncate max-w-[150px]">
                                <?= e($u['keahlian'] ?: '-'); ?>
                            </td>
                            <td class="p-3.5">
                                <?php if ($u['role'] === 'pelajar'): ?>
                                    <span class="font-bold text-amber-600">Lvl <?= (int)$u['level']; ?></span>
                                    <span class="text-slate-400 text-[10px]"> (<?= number_format($u['xp']); ?> XP)</span>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-slate-400 text-[10px]">
                                <?= date('d M Y', strtotime($u['created_at'])); ?>
                            </td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <?php if ($u['status_akun'] === 'pending'): ?>
                                        <!-- Aksi Cepat Verifikasi Pengajar -->
                                        <a href="?action=verifikasi&id=<?= $u['id_user']; ?>&status=aktif&filter=<?= e($filter); ?>" onclick="return confirm('Terima pengajar ini?');" class="px-2 py-1 rounded bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-[10px] shadow-sm">
                                            Terima
                                        </a>
                                        <a href="?action=verifikasi&id=<?= $u['id_user']; ?>&status=tolak&filter=<?= e($filter); ?>" onclick="return confirm('Tolak dan hapus akun ini?');" class="px-2 py-1 rounded bg-red-100 hover:bg-red-200 text-red-700 font-bold text-[10px]">
                                            Tolak
                                        </a>
                                    <?php else: ?>
                                        <!-- Toggle Status Aktif/Nonaktif -->
                                        <?php if ($u['id_user'] !== $userId): ?>
                                            <form action="" method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id_user" value="<?= $u['id_user']; ?>">
                                                <input type="hidden" name="current_status" value="<?= $u['status_akun']; ?>">
                                                <button type="submit" title="Ubah status akun" class="p-1.5 rounded hover:bg-slate-100 text-slate-600">
                                                    <i class="fa-solid <?= ($u['status_akun'] === 'aktif') ? 'fa-ban text-orange-500' : 'fa-check text-emerald-600'; ?>"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Reset Password -->
                                        <form action="" method="POST" class="inline" onsubmit="return confirm('Reset password pengguna ini ke default 123456?');">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="id_user" value="<?= $u['id_user']; ?>">
                                            <button type="submit" title="Reset password ke 123456" class="p-1.5 rounded hover:bg-slate-100 text-amber-500">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                        </form>

                                        <!-- Hapus User -->
                                        <?php if ($u['id_user'] !== $userId): ?>
                                            <form action="" method="POST" class="inline" onsubmit="return confirm('Hapus pengguna ini secara permanen? Seluruh data yang terkait akan terhapus.');">
                                                <input type="hidden" name="action" value="hapus_user">
                                                <input type="hidden" name="id_user" value="<?= $u['id_user']; ?>">
                                                <button type="submit" title="Hapus pengguna" class="p-1.5 rounded hover:bg-slate-100 text-red-500">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL TAMBAH USER -->
<div id="modalTambahUser" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800">Daftarkan Pengguna Baru</h3>
            <button onclick="document.getElementById('modalTambahUser').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="tambah_user">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC]">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Peran (Role)</label>
                    <select name="role" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="pelajar">Pelajar</option>
                        <option value="pengajar">Pengajar</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akun</label>
                    <select name="status_akun" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                        <option value="aktif">Aktif</option>
                        <option value="pending">Pending</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keahlian / Bidang Minat (Opsional)</label>
                <input type="text" name="keahlian" placeholder="Misal: Web, AI, MySQL" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Awal</label>
                <input type="text" name="password" value="123456" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300">
                <span class="text-[10px] text-slate-400">Default: 123456</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalTambahUser').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-sm">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
