            </div>
        </main>
    </div>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-[#E2E8F0] py-4 px-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                &copy; <?= date('Y'); ?> <strong class="text-slate-700">Questudy</strong> — LMS Gamifikasi, Ruang Belajar Pribadi & Peringatan Dini.
            </div>
        </div>
    </footer>

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
</body>
</html>
