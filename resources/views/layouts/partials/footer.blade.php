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

{{-- Stack JS riêng từng trang --}}
@stack('scripts')
