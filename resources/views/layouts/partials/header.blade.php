<header class="header sticky p-0 shadow-sm"
        style="height: var(--cui-sidebar-brand-height)">
  <div class="container-fluid px-4">
    <button class="header-toggler" type="button"
      onclick="window.location.href='{{ route('dashboard') }}'"
      style="margin-inline-start: -14px;">
      <svg class="icon icon-lg">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-home') }}"></use>
      </svg>
    </button>

    {{-- Breadcrumb ở header (desktop) --}}
    <nav class="d-none d-md-flex ms-3" aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="{{ route('dashboard') }}">Trang chủ</a>
        </li>
        @yield('breadcrumb')
      </ol>
    </nav>

    <ul class="header-nav ms-auto">
      {{-- Thông báo --}}
      <li class="nav-item dropdown d-none d-md-block">
        <a class="nav-link" href="#" role="button" data-coreui-toggle="dropdown">
          <svg class="icon icon-lg">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-bell') }}"></use>
          </svg>
        </a>
        <ul class="dropdown-menu dropdown-menu-end pt-0">
          <li class="dropdown-header bg-body-tertiary fw-semibold py-2">Thông báo</li>
          <li><a class="dropdown-item text-body-secondary py-2" href="#">Không có thông báo mới</a></li>
        </ul>
      </li>

      {{-- Avatar + dropdown user --}}
      <li class="nav-item dropdown">
        <a class="nav-link" href="#" role="button" data-coreui-toggle="dropdown">
          <svg class="icon icon-lg text-body-secondary">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-user') }}"></use>
          </svg>
        </a>
        <ul class="dropdown-menu dropdown-menu-end pt-0">
          <li class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-2">
            {{ auth()->user()->name ?? 'Cài đặt' }}
            <div class="small text-body-secondary fw-normal">
              {{ auth()->user()->email ?? '' }}
            </div>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('under-construction') }}">
              <svg class="icon me-2">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-settings') }}"></use>
              </svg>
              Cài đặt
            </a>
          </li>
          <li>
              <hr class="dropdown-divider">
          </li>
          <li>
              <button type="submit" form="logoutForm" class="dropdown-item text-danger">
                  <svg class="icon me-2">
                      <use hx-boost="false" xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-account-logout') }}"></use>
                  </svg>
                  Đăng xuất
              </button>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</header>

{{-- Form logout — đặt ngoài header để tránh lồng form --}}
<form id="logoutForm" method="POST" action="{{ route('logout') }}" class="d-none" hx-boost="false">
    @csrf
</form>
