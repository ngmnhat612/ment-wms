<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Warehouse System')</title>

{{-- Favicon --}}
<link rel="icon" type="image/png" href="{{ asset('images/MENT.ico') }}">

{{-- Vendors --}}
<link rel="stylesheet" href="{{ asset('vendor/coreui/simplebar/simplebar.min.css') }}">

{{-- CoreUI CSS --}}
<link rel="stylesheet" href="{{ asset('vendor/coreui/css/style.min.css') }}">

{{-- CoreUI Charts --}}
<link rel="stylesheet" href="{{ asset('vendor/coreui/chartjs/coreui-chartjs.css') }}">

{{-- Stack CSS riêng từng trang --}}
@stack('styles')

{{-- htmx — boost link/form thành AJAX, chỉ thay nội dung chính, giữ nguyên sidebar --}}
<script src="https://unpkg.com/htmx.org@1.9.12" defer></script>
