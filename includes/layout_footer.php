            </div>
        </main>
    </div>

    <!-- FOOTER -->
    <footer class="py-5 text-center text-sm text-slate-500">
        &copy; <?= date('Y'); ?> <strong class="text-slate-700">Questudy</strong>
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
