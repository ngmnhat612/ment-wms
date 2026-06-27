<!DOCTYPE html>
<html lang="vi">

<head>
    @include('layouts.partials.head')
</head>

<body data-pfa="{{ session('product_form_action', '') }}">

    @include('layouts.partials.sidebar')

    <div class="wrapper d-flex flex-column min-vh-100">

        @include('layouts.partials.header')

        <div class="body flex-grow-1">
            <div class="container-fluid px-4 py-4">

                {{-- Alert thông báo chung --}}
                @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <svg class="icon me-2">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                    </svg>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <svg class="icon me-2">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
                @endif

                {{-- Nội dung chính của từng trang --}}
                @yield('content')

            </div>
        </div>

        @include('layouts.partials.footer')

    </div>

</body>

</html>
