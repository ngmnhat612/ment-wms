<footer class="footer">
    <div class="container-fluid px-4">
        <span>MenT WMS &copy; 2026 - MenT Automation</span>
    </div>
</footer>

{{-- CoreUI Bundle (Bootstrap + CoreUI JS) --}}
<script src="{{ asset('vendor/coreui/js/coreui.bundle.min.js') }}"></script>

{{-- Simplebar (scrollbar tuỳ chỉnh cho sidebar) --}}
<script src="{{ asset('vendor/coreui/simplebar/simplebar.min.js') }}"></script>

{{-- Chart.js + CoreUI Chart plugin (dùng cho dashboard) --}}
<script src="{{ asset('vendor/coreui/chartjs/chart.umd.js') }}"></script>
<script src="{{ asset('vendor/coreui/chartjs/coreui-chartjs.js') }}"></script>

{{-- Tạm thời comment lại color-modes để tránh lỗi querySelector khi header không có UI switch theme --}}
{{-- <script src="{{ asset('vendor/coreui/js/custom/color-modes.js') }}"></script> --}}

{{-- ===== HTMX: cập nhật title + active state sidebar sau mỗi lần điều hướng ===== --}}
<script>
(function () {
    function updateTitle() {
        var main = document.getElementById('main-content');
        if (main && main.dataset.title) {
            document.title = main.dataset.title;
        }
    }

    function updateSidebarActiveState() {
        var path = window.location.pathname;

        document.querySelectorAll('.sidebar-nav .nav-link[href]').forEach(function (link) {
            var linkPath = new URL(link.href, window.location.origin).pathname;
            var isActive = (linkPath === path);
            link.classList.toggle('active', isActive);

            if (isActive) {
                var group = link.closest('.nav-group');
                if (group) group.classList.add('show');
            }
        });
    }

    function closeOffcanvasOnNavigate() {
        // Đóng các offcanvas (ví dụ form thêm/sửa) còn mở từ trang trước,
        // tránh kẹt overlay sau khi nội dung đã được thay bằng htmx.
        document.querySelectorAll('.offcanvas.show').forEach(function (el) {
            var instance = window.coreui && window.coreui.Offcanvas
                ? window.coreui.Offcanvas.getInstance(el)
                : null;
            if (instance) instance.hide();
        });
    }

    document.body.addEventListener('htmx:afterSwap', function () {
        updateTitle();
        updateSidebarActiveState();
        closeOffcanvasOnNavigate();
    });

    document.addEventListener('DOMContentLoaded', function () {
        updateTitle();
        updateSidebarActiveState();
    });
})();
</script>

{{-- Stack JS riêng từng trang --}}
@stack('scripts')
