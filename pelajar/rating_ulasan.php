<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/koneksi.php';
cekRole('pelajar');

$pageTitle = 'Beri Rating & Ulasan Tutor — Questudy';
$userId = $_SESSION['id_user'];

$idBooking = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data booking yang relevan
$stmtB = $conn->prepare("
    SELECT b.*, u.nama AS nama_tutor, u.foto_profil AS foto_tutor, u.keahlian AS keahlian_tutor, k.nama_kelas
    FROM booking_tutor b
    JOIN users u ON b.id_tutor = u.id_user
    LEFT JOIN kelas k ON b.id_kelas = k.id_kelas
    WHERE b.id_booking = ? AND b.id_mahasiswa = ?
");
$stmtB->execute([$idBooking, $userId]);
$booking = $stmtB->fetch();

if (!$booking || $booking['status'] !== 'selesai') {
    setFlash('error', 'Sesi mentoring belum selesai atau data booking tidak ditemukan.');
    header("Location: " . base_url('pelajar/booking_tutor.php'));
    exit();
}

$errorRating = '';

// Handle Submit Rating & Ulasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_ulasan') {
    $rating = (int)($_POST['rating'] ?? 0);
    $ulasan = trim($_POST['ulasan'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errorRating = 'Silakan pilih bintang penilaian antara 1 hingga 5!';
    } elseif (empty($ulasan)) {
        $errorRating = 'Mohon tuliskan ulasan singkat mengenai jalannya sesi mentoring.';
    } else {
        $isFirstTimeRating = ($booking['rating'] === null);

        $stmtUp = $conn->prepare("UPDATE booking_tutor SET rating = ?, ulasan = ? WHERE id_booking = ?");
        $stmtUp->execute([$rating, $ulasan, $idBooking]);

        $msg = 'Terima kasih! Ulasan dan rating bintang berhasil disimpan.';
        if ($isFirstTimeRating) {
            $rw = tambahXP($conn, $userId, 'sesi_tutor');
            $msg .= ' 🎉 Anda mendapatkan bonus +30 XP atas kontribusi mentoring!';
            if (!empty($rw['badges_baru'])) {
                $msg .= ' 🏆 Anda membuka badge: ' . implode(', ', $rw['badges_baru']);
            }
        }

        setFlash('success', $msg);
        header("Location: " . base_url('pelajar/booking_tutor.php'));
        exit();
    }
}

require_once BASE_PATH . '/includes/layout_header.php';
?>

<!-- BREADCRUMB & HEADER (Screen 02) -->
<div class="space-y-1 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= base_url('pelajar/booking_tutor.php'); ?>" class="hover:text-[#0066CC]">Booking Tutor</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Rating & Ulasan</span>
    </div>
    <h1 class="text-2xl font-extrabold text-slate-800">Beri Rating & Ulasan Sesi Mentoring</h1>
    <p class="text-xs text-slate-500">Masukan Anda sangat berharga untuk peningkatan mutu pembelajaran dan kualitas pendampingan tutor.</p>
</div>

<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
        
        <!-- Info Ringkas Tutor & Sesi -->
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center gap-4">
            <img src="<?= base_url('uploads/profil/' . e($booking['foto_tutor'])); ?>" class="w-14 h-14 rounded-2xl object-cover border border-slate-200 shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($booking['nama_tutor']); ?>&background=0066CC&color=fff';">
            <div>
                <span class="text-[10px] font-bold text-[#0066CC] bg-blue-100 px-2 py-0.5 rounded">
                    <?= e($booking['nama_kelas'] ?: 'Konsultasi Privat'); ?>
                </span>
                <h3 class="text-base font-bold text-slate-800 mt-1"><?= e($booking['nama_tutor']); ?></h3>
                <p class="text-xs text-slate-500">Sesi selesai pada <?= date('d M Y', strtotime($booking['tanggal_sesi'])); ?> pukul <?= date('H:i', strtotime($booking['jam_sesi'])); ?> WIB</p>
            </div>
        </div>

        <div class="p-6 sm:p-8 space-y-6">

            <?php if (!empty($errorRating)): ?>
                <div class="p-3 rounded-xl bg-red-50 text-red-700 text-xs flex items-center gap-2 border border-red-200">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <span><?= e($errorRating); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="simpan_ulasan">
                <input type="hidden" name="rating" id="selectedRating" value="<?= (int)($booking['rating'] ?: 5); ?>">

                <!-- Star Rating Interaktif (Amber #F59E0B) -->
                <div class="text-center space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Berapa bintang untuk sesi mentoring ini?
                    </label>
                    <div id="starContainer" class="flex items-center justify-center gap-2 text-3xl py-2 cursor-pointer">
                        <?php $currentRating = (int)($booking['rating'] ?: 5); ?>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-solid fa-star star-btn transition transform hover:scale-110 <?= ($i <= $currentRating) ? 'text-[#F59E0B]' : 'text-slate-200'; ?>" data-val="<?= $i; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div id="ratingText" class="text-xs font-bold text-amber-600">
                        <?= ($currentRating === 5) ? 'Sangat Memuaskan (5/5)' : "Skor: $currentRating/5"; ?>
                    </div>
                </div>

                <!-- Textarea Ulasan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tuliskan Ulasan / Masukan Pembelajaran
                    </label>
                    <textarea name="ulasan" rows="4" required placeholder="Contoh: Penjelasan tutor sangat jelas, ramah, dan membantu memecahkan kendala logika query pada proyek saya..." class="w-full p-3.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#0066CC] transition"><?= e($booking['ulasan'] ?? ''); ?></textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="<?= base_url('pelajar/booking_tutor.php'); ?>" class="text-xs text-slate-500 hover:text-slate-700 font-semibold">
                        &larr; Kembali
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#0066CC] hover:bg-[#0052A3] text-white text-xs font-bold transition shadow-md shadow-blue-500/20">
                        Simpan Ulasan & Kirim
                    </button>
                </div>
            </form>

        </div>

    </div>
</div>

<script>
    const stars = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('selectedRating');
    const ratingText = document.getElementById('ratingText');

    const labels = {
        1: 'Kurang Bermanfaat (1/5)',
        2: 'Cukup Membantu (2/5)',
        3: 'Baik (3/5)',
        4: 'Sangat Baik (4/5)',
        5: 'Sangat Memuaskan (5/5)'
    };

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const val = parseInt(this.getAttribute('data-val'));
            ratingInput.value = val;
            ratingText.textContent = labels[val] || (val + '/5');
            renderStars(val);
        });
    });

    function renderStars(activeVal) {
        stars.forEach(s => {
            const val = parseInt(s.getAttribute('data-val'));
            if (val <= activeVal) {
                s.classList.remove('text-slate-200');
                s.classList.add('text-[#F59E0B]');
            } else {
                s.classList.remove('text-[#F59E0B]');
                s.classList.add('text-slate-200');
            }
        });
    }
</script>

<?php require_once BASE_PATH . '/includes/layout_footer.php'; ?>
