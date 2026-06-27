<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Đăng nhập</title>
    @include('layouts.partials.head')
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card-group d-block d-md-flex row">

                    {{-- Form đăng nhập --}}
                    <div class="card col-md-7 p-4 mb-0">
                        <div class="card-body">

                            <h1>Đăng nhập</h1>
                            <div class="mb-4"></div>

                            {{-- Session status --}}
                            @if (session('status'))
                                <div class="alert alert-success mb-3">{{ session('status') }}</div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                {{-- Tên đăng nhập --}}
                                <div class="input-group mb-3">
                                    <span class="input-group-text">
                                        <svg class="icon">
                                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use>
                                        </svg>
                                    </span>
                                    <input class="form-control @error('username') is-invalid @enderror"
                                           type="text"
                                           name="username"
                                           value="{{ old('username') }}"
                                           placeholder="Tên đăng nhập"
                                           autocomplete="username"
                                           autofocus>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Mật khẩu --}}
                                <div class="input-group mb-4">
                                    <span class="input-group-text">
                                        <svg class="icon">
                                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
                                        </svg>
                                    </span>
                                    <input class="form-control @error('password') is-invalid @enderror"
                                           type="password"
                                           name="password"
                                           placeholder="Mật khẩu"
                                           autocomplete="current-password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Remember me --}}
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <button class="btn btn-primary px-4" type="submit">Đăng nhập</button>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>

                    {{-- Panel bên phải --}}
                    <div class="card col-md-5 text-white bg-primary py-5">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <div class="mt-3">
                                <img src="{{ asset('images/MENT.ico') }}" 
                                    alt="Logo công ty" 
                                    style="height: 80px; opacity: 0.85;">
                            </div>
                            <h2>MenT WMS</h2>
                        </div>
                    </div>

                </div>
                {{-- /.card-group --}}

            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/coreui/js/coreui.bundle.min.js') }}"></script>

</body>
</html>