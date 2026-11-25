            </div>
        </main>
    </div>

    <!-- FOOTER -->
    <footer class="py-5 text-center text-sm text-slate-500 no-print">
        &copy; <?= date('Y'); ?> <strong class="text-slate-700">Questudy</strong>
    </footer>

    <?php if (isset($_SESSION['celebration'])): 
        $cel = $_SESSION['celebration'];
        unset($_SESSION['celebration']);
    ?>
    <!-- MODAL PERAYAAN GAMIFIKASI & LEVEL UP -->
    <div id="modalCelebration" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
        <div class="relative w-full max-w-sm bg-white rounded-3xl p-6 sm:p-7 text-center shadow-2xl border-4 border-amber-300 transform scale-100 animate-in fade-in duration-300">
            <?= maskotPausDepan('w-28 mx-auto mb-2'); ?>
            <h3 class="text-xl font-black text-slate-800"><?= e($cel['title'] ?? 'Selamat!'); ?></h3>
            <p class="text-xs text-slate-500 mt-1"><?= e($cel['message'] ?? 'Pencapaian belajarmu luar biasa!'); ?></p>
            
            <?php if (isset($cel['skor'])): ?>
                <div class="my-3 py-2 px-4 rounded-2xl bg-amber-50 border border-amber-200 inline-block">
                    <span class="text-[10px] uppercase font-bold text-amber-700 tracking-wider">Hasil Skor</span>
                    <div class="text-3xl font-black text-amber-600"><?= (int)$cel['skor']; ?><span class="text-sm text-amber-500 font-semibold">/100</span></div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-2 my-3 text-xs font-bold">
                <?php if (!empty($cel['xp'])): ?>
                    <div class="p-2.5 rounded-xl bg-blue-50 text-[#0066CC] border border-blue-100 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-bolt text-amber-500"></i>
                        <span>+<?= (int)$cel['xp']; ?> XP</span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($cel['level_baru'])): ?>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center gap-1.5 animate-bounce">
                        <i class="fa-solid fa-crown text-amber-500"></i>
                        <span>Naik Level <?= (int)$cel['level_baru']; ?>!</span>
                    </div>
                <?php else: ?>
                    <div class="p-2.5 rounded-xl bg-slate-50 text-slate-600 border border-slate-100 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-medal text-amber-500"></i>
                        <span>Streak Aktif</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($cel['badges'])): ?>
                <div class="p-2.5 rounded-2xl bg-gradient-to-r from-amber-100 to-amber-50 border border-amber-300 text-xs text-amber-800 font-bold mb-3">
                    Lencana Baru: <?= implode(', ', array_map('e', $cel['badges'])); ?>!
                </div>
            <?php endif; ?>

            <button type="button" onclick="document.getElementById('modalCelebration').remove()" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-[#0066CC] to-indigo-600 hover:from-[#0052A3] hover:to-indigo-700 text-white font-extrabold text-sm shadow-md transition">
                Lanjutkan Belajar
            </button>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        if (typeof confetti === 'function') {
            confetti({ particleCount: 70, spread: 60, origin: { y: 0.6 } });
            setTimeout(function() {
                confetti({ particleCount: 40, angle: 60, spread: 50, origin: { x: 0 } });
                confetti({ particleCount: 40, angle: 120, spread: 50, origin: { x: 1 } });
            }, 200);
        }
    </script>
    <?php endif; ?>

    <!-- JavaScript Mobile Sidebar Toggle & Interactivity -->
    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainSidebar = document.getElementById('mainSidebar');

        if (sidebarToggle && mainSidebar) {
            sidebarToggle.addEventListener('click', () => {
                mainSidebar.classList.toggle('hidden');
                mainSidebar.classList.toggle('fixed');
                mainSidebar.classList.toggle('inset-y-0');
                mainSidebar.classList.toggle('left-0');
                mainSidebar.classList.toggle('z-50');
                mainSidebar.classList.toggle('shadow-2xl');
            });
        }
    </script>
    <script>
        // Angka naik perlahan dan lingkaran progres terisi perlahan
        (function () {
            var kurang = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            function animasi(durasi, langkah, akhir) {
                var mulai = null;
                function f(t) {
                    if (mulai === null) mulai = t;
                    var p = Math.min((t - mulai) / durasi, 1);
                    langkah(1 - Math.pow(1 - p, 3));
                    if (p < 1) requestAnimationFrame(f); else if (akhir) akhir();
                }
                requestAnimationFrame(f);
            }
            document.querySelectorAll('[data-hitung]').forEach(function (el) {
                var teks = el.getAttribute('data-hitung');
                var target = parseFloat(teks);
                if (isNaN(target) || kurang) return;
                var desimal = (teks.split('.')[1] || '').length;
                var opsi = { minimumFractionDigits: desimal, maximumFractionDigits: desimal };
                el.textContent = (0).toLocaleString('id-ID', opsi);
                animasi(1000, function (e) { el.textContent = (target * e).toLocaleString('id-ID', opsi); }, function () { el.textContent = target.toLocaleString('id-ID', opsi); });
            });
            document.querySelectorAll('[data-sudut]').forEach(function (el) {
                var target = parseFloat(el.getAttribute('data-sudut'));
                if (isNaN(target) || kurang) return;
                el.style.setProperty('--sudut', '0deg');
                animasi(1100, function (e) { el.style.setProperty('--sudut', (target * e) + 'deg'); }, null);
            });
        })();
    </script>
</body>
</html>
