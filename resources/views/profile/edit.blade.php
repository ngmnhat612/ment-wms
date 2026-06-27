@extends('layouts.app')

@section('title', 'Cài đặt tài khoản — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item active">Cài đặt tài khoản</li>
@endsection

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Cài đặt tài khoản</h4>
      <small class="text-body-secondary">Quản lý thông tin cá nhân và bảo mật</small>
    </div>
  </div>

  @if (session('status') === 'profile-updated')
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
      Thông tin tài khoản đã được cập nhật.
      <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">

    {{-- Thông tin cá nhân --}}
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header fw-semibold">
          <svg class="icon me-2 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use></svg>
          Thông tin cá nhân
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            <div class="mb-3">
              <label for="name" class="form-label fw-medium">Họ và tên <span class="text-danger">*</span></label>
              <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $user->name) }}" required autocomplete="name">
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-4">
              <label for="email" class="form-label fw-medium">Email <span class="text-danger">*</span></label>
              <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $user->email) }}" required autocomplete="username">
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="form-text text-warning">
                  <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
                  Email chưa được xác minh.
                </div>
              @endif
            </div>

            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              Lưu thay đổi
            </button>
          </form>
        </div>
      </div>
    </div>

    {{-- Đổi mật khẩu --}}
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header fw-semibold">
          <svg class="icon me-2 text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
          Đổi mật khẩu
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
              <label for="current_password" class="form-label fw-medium">Mật khẩu hiện tại <span class="text-danger">*</span></label>
              <input type="password" id="current_password" name="current_password"
                class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                autocomplete="current-password">
              @error('current_password', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label for="password" class="form-label fw-medium">Mật khẩu mới <span class="text-danger">*</span></label>
              <input type="password" id="password" name="password"
                class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                autocomplete="new-password">
              @error('password', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-4">
              <label for="password_confirmation" class="form-label fw-medium">Xác nhận mật khẩu <span class="text-danger">*</span></label>
              <input type="password" id="password_confirmation" name="password_confirmation"
                class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                autocomplete="new-password">
              @error('password_confirmation', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            @if (session('status') === 'password-updated')
              <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                Mật khẩu đã được cập nhật.
                <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
              </div>
            @endif

            <button type="submit" class="btn btn-warning">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
              Đổi mật khẩu
            </button>
          </form>
        </div>
      </div>
    </div>

    {{-- Xoá tài khoản --}}
    <div class="col-12">
      <div class="card border-danger">
        <div class="card-header fw-semibold text-danger">
          <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
          Xoá tài khoản
        </div>
        <div class="card-body">
          <p class="text-body-secondary mb-3">
            Sau khi xoá, toàn bộ dữ liệu của tài khoản sẽ bị xoá vĩnh viễn và không thể khôi phục.
          </p>
          <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal" data-coreui-target="#deleteAccountModal">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
            Xoá tài khoản của tôi
          </button>
        </div>
      </div>
    </div>

  </div>

  {{-- Modal xác nhận xoá --}}
  <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title text-danger fw-semibold" id="deleteAccountModalLabel">
            <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
            Xác nhận xoá tài khoản
          </h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-body-secondary mb-3">Nhập mật khẩu để xác nhận bạn muốn xoá tài khoản vĩnh viễn.</p>
          <form method="POST" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
            @csrf
            @method('DELETE')
            <div class="mb-3">
              <label for="delete_password" class="form-label fw-medium">Mật khẩu <span class="text-danger">*</span></label>
              <input type="password" id="delete_password" name="password"
                class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                placeholder="Nhập mật khẩu hiện tại" autocomplete="current-password">
              @error('password', 'userDeletion')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </form>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Huỷ</button>
          <button type="submit" form="deleteAccountForm" class="btn btn-danger">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
            Xác nhận xoá
          </button>
        </div>
      </div>
    </div>
  </div>

@endsection