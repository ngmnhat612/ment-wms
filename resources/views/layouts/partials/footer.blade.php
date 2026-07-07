<footer class="footer">
    <div class="container-fluid px-4">
        <span>MenT WMS &copy; 2026 - MenT Automation</span>
    </div>
</footer>

{{-- CoreUI Bundle (Bootstrap + CoreUI JS) --}}
<script src="{{ asset('vendor/coreui/js/coreui.bundle.min.js') }}"></script>

{{-- public/js/number-input-guard.js --}}
<script src="{{ asset('js/number-input-guard.js') }}"></script>

{{-- public/js/crud-modal-helpers.js --}}
<script src="{{ asset('js/crud-modal-helpers.js') }}"></script>

{{-- Simplebar (scrollbar tuỳ chỉnh cho sidebar) --}}
<script src="{{ asset('vendor/coreui/simplebar/simplebar.min.js') }}"></script>

{{-- Chart.js + CoreUI Chart plugin (dùng cho dashboard) --}}
<script src="{{ asset('vendor/coreui/chartjs/chart.umd.js') }}"></script>
<script src="{{ asset('vendor/coreui/chartjs/coreui-chartjs.js') }}"></script>

{{-- Tạm thời comment lại color-modes để tránh lỗi querySelector khi header không có UI switch theme --}}
{{-- <script src="{{ asset('vendor/coreui/js/custom/color-modes.js') }}"></script> --}}

{{-- UPDATE --}}
{{-- Tắt validate mặc định của trình duyệt cho các form CRUD dùng modal (category, department,
     sn, brand, location, supplier, uom, warehouse, employee...), để lỗi luôn đi qua server và
     hiển thị đồng bộ qua banner "Vui lòng kiểm tra lại". Giữ nguyên required trong HTML (không xoá).
     CHÚ Ý: không chọn '.offcanvas form' — productForm dùng offcanvas và đã được xử lý riêng. --}}
<script>
(function () {
    function disableNativeValidationOnModals() {
        document.querySelectorAll('.modal form').forEach(function (form) {
            form.setAttribute('novalidate', 'novalidate');
        });
    }

    document.body.addEventListener('htmx:afterSwap', disableNativeValidationOnModals);
    document.addEventListener('DOMContentLoaded', disableNativeValidationOnModals);
})();
</script>

{{-- Stack JS riêng từng trang --}}
@stack('scripts')
