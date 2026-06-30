<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
  <div class="sidebar-header border-bottom">
    <div class="sidebar-brand d-flex align-items-center gap-2">
      <img src="{{ asset('images/MENT.ico') }}" 
          alt="Logo" 
          style="height: 28px; opacity: 0.85;">
      <span class="sidebar-brand-full fw-semibold fs-5">MenT WMS</span>
    </div>
    <button class="btn-close d-lg-none" type="button"
      data-coreui-theme="dark"
      aria-label="Close"
      onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
    </button>
  </div>

  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>

    {{-- TỔNG QUAN --}}
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
         href="{{ route('dashboard') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-speedometer') }}"></use>
        </svg>
        Dashboard
      </a>
    </li>

    {{-- NGHIỆP VỤ KHO --}}
    <li class="nav-title">Nghiệp vụ kho</li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}"
         href="{{ route('stock-requests.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
        </svg>
        Yêu cầu vật tư
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}"
         href="{{ route('receipts.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
        </svg>
        Nhập kho
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('issues.*') ? 'active' : '' }}"
         href="{{ route('issues.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use>
        </svg>
        Xuất kho
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('stocktakes.*') ? 'active' : '' }}"
         href="{{ route('stocktakes.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clipboard') }}"></use>
        </svg>
        Kiểm kê
      </a>
    </li>

    {{-- TỒN KHO --}}
    <li class="nav-title">Tồn kho</li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}"
         href="{{ route('inventory.index') }}">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
        </svg>
        Tồn kho hiện tại
      </a>
    </li>

    {{-- DANH MỤC --}}
    <li class="nav-title">Danh mục</li>

    <li class="nav-group {{ request()->routeIs('master.product.*', 'master.category.*', 'master.uom*', 'master.brand.*') ? 'show' : '' }}">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use>
        </svg>
        Vật tư
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.product.*') ? 'active' : '' }}"
             href="{{ route('master.product.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Danh sách vật tư
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.category.*') ? 'active' : '' }}"
             href="{{ route('master.category.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Danh mục vật tư
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.reorder-rule.*') ? 'active' : '' }}"
             href="{{ route('master.reorder-rule.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Gán Min-Max
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.putaway-rule.*') ? 'active' : '' }}"
             href="{{ route('master.putaway-rule.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Gán gợi ý vị trí
          </a>
        </li>
      </ul>
    </li>

    <li class="nav-group {{ request()->routeIs('master.supplier.*', 'master.location.*') ? 'show' : '' }}">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-building') }}"></use>
        </svg>
        Đối tác & Kho
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.supplier.*') ? 'active' : '' }}"
             href="{{ route('master.supplier.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Nhà cung cấp
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.location.*') ? 'active' : '' }}"
             href="{{ route('master.location.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Vị trí kho
          </a>
        </li>
      </ul>
    </li>

    {{-- BÁO CÁO --}}
    <li class="nav-title">Báo cáo</li>

    {{-- Tạm thời comment toàn bộ mục con báo cáo --}}
    {{-- <li class="nav-group {{ request()->routeIs('reports.*') ? 'show' : '' }}">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-chart-pie') }}"></use>
        </svg>
        Báo cáo
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}"
             href="{{ route('reports.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Tổng hợp NXT
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('reports.alerts.below_min') ? 'active' : '' }}"
             href="{{ route('reports.alerts.below_min') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Dưới định mức
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('reports.alerts.slow_moving') ? 'active' : '' }}"
             href="{{ route('reports.alerts.slow_moving') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Hàng đọng kho
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('reports.alerts.near_expiry') ? 'active' : '' }}"
             href="{{ route('reports.alerts.near_expiry') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Hàng cận date
          </a>
        </li>
      </ul>
    </li> --}}

    {{-- ADMIN --}}
    <li class="nav-divider"></li>
    <li class="nav-title">Admin</li>

    <li class="nav-group {{ request()->routeIs('master.employee.*', 'master.warehouse.*') ? 'show' : '' }}">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-settings') }}"></use>
        </svg>
        Quản lý
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.uom.index') ? 'active' : '' }}"
             href="{{ route('master.uom.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Đơn vị tính
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.brand.*') ? 'active' : '' }}"
             href="{{ route('master.brand.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Thương hiệu
          </a>
        </li>
        <!-- <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.warehouse.*') ? 'active' : '' }}"
             href="{{ route('master.warehouse.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Kho hàng
          </a>
        </li> -->
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('master.employee.*') ? 'active' : '' }}"
             href="{{ route('master.employee.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Người dùng
          </a>
        </li>
      </ul>
    </li>

    <li class="nav-group {{ request()->routeIs('activity-log.*', 'transaction-log.*') ? 'show' : '' }}">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
        </svg>
        Nhật ký
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('transaction-log.*') ? 'active' : '' }}"
             href="{{ route('transaction-log.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Giao dịch
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('activity-log.*') ? 'active' : '' }}"
             href="{{ route('activity-log.index') }}">
            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
            Hệ thống
          </a>
        </li>
      </ul>
    </li>

  </ul>

  <div class="sidebar-footer border-top d-none d-md-flex">
    <button class="sidebar-toggler" type="button" data-coreui-toggle="narrow"></button>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const sidebar    = document.getElementById('sidebar');
  const sidebarObj = () => coreui.Sidebar.getOrCreateInstance(sidebar);

  sidebar.addEventListener('click', function (e) {
    if (!sidebar.classList.contains('sidebar-narrow')) return;

    const toggle = e.target.closest('.nav-group-toggle');
    if (!toggle) return;

    e.preventDefault();
    e.stopPropagation();

    const navGroup = toggle.closest('.nav-group');

    sidebarObj().reset();

    setTimeout(function () {
      if (!navGroup.classList.contains('show')) {
        const navEl = sidebar.querySelector('[data-coreui="navigation"]');
        if (navEl) {
          coreui.Navigation.getOrCreateInstance(navEl);
          toggle.click();
        }
      }
    }, 200);
  });
});
</script>
@endpush