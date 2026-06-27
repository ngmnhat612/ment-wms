<x-guest-layout>
    <div class="card p-4">
        <div class="card-body">
            <p class="text-body-secondary mb-4">
                {{ __('Đây là khu vực bảo mật. Vui lòng xác nhận mật khẩu trước khi tiếp tục.') }}
            </p>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Mật khẩu') }}</label>
                    <input id="password"
                           type="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required
                           autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Konfirmasi') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>