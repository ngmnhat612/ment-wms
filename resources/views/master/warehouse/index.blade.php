@extends('layouts.app')

@section('title', 'Kho hàng — Ment WMS')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Kho hàng</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Kho hàng</h4>
      <small class="text-body-secondary">Quản lý danh sách kho và phân công nhân viên</small>
    </div>
    <button class="btn btn-primary" onclick="openModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm kho
    </button>
  </div>

  {{-- THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-factory') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng số kho</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $activeCount }}</div>
            <div class="text-body-secondary small">Đang hoạt động</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách kho hàng</span>
      <form method="GET" action="{{ route('master.warehouse.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:240px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã, tên kho...">
        </div>
        <select class="form-select" name="status" style="width:130px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || (request('status') !== null && request('status') !== ''))
          <a href="{{ route('master.warehouse.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:55px">#</th>
              <th style="width:110px">Mã kho</th>
              <th>Tên kho</th>
              <th style="width:160px">Quản lý kho</th>
              <th style="width:130px">Số điện thoại</th>
              <th style="width:180px">Địa chỉ</th>
              <th class="text-center" style="width:100px">Nhân viên</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:110px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($warehouses as $index => $wh)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($warehouses->currentPage() - 1) * $warehouses->perPage() + $index + 1 }}
                </td>
                <td><code class="text-primary fw-medium">{{ $wh->code }}</code></td>
                <td>
                  <div class="fw-medium">{{ $wh->name }}</div>
                  @if ($wh->email)
                    <div class="small text-body-secondary">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-envelope-closed') }}"></use></svg>
                      {{ $wh->email }}
                    </div>
                  @endif
                </td>
                <td>
                  @if ($wh->manager)
                    <div class="fw-medium">{{ $wh->manager->name }}</div>
                    <div class="small text-body-secondary font-monospace">{{ $wh->manager->code }}</div>
                  @else
                    <span class="text-body-secondary small">— Chưa phân công</span>
                  @endif
                </td>
                <td class="small text-body-secondary">
                  @if ($wh->phone)
                    <a href="tel:{{ $wh->phone }}" class="text-body text-decoration-none">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-phone') }}"></use></svg>
                      {{ $wh->phone }}
                    </a>
                  @else
                    —
                  @endif
                </td>
                <td class="small text-body-secondary text-truncate" style="max-width:180px">
                  {{ $wh->address ?: '—' }}
                </td>
                <td class="text-center">
                  <span class="badge bg-info-subtle text-info border border-info-subtle">
                    {{ $wh->employees_count ?? $wh->employees->count() }} NV
                  </span>
                </td>
                <td class="text-center">
                  @if ($wh->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openModal(
                            {{ $wh->id }},
                            '{{ addslashes($wh->code) }}',
                            '{{ addslashes($wh->name) }}',
                            {{ $wh->manager_id ?? 'null' }},
                            '{{ addslashes($wh->phone ?? '') }}',
                            '{{ addslashes($wh->email ?? '') }}',
                            '{{ addslashes($wh->address ?? '') }}',
                            {{ $wh->status }}
                          )"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $wh->id }}, '{{ addslashes($wh->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-factory') }}"></use>
                  </svg>
                  Chưa có kho nào
                  @if(request('search'))
                    <div class="small mt-1">Không tìm thấy kết quả cho "<strong>{{ request('search') }}</strong>"</div>
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($warehouses->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $warehouses->firstItem() }}–{{ $warehouses->lastItem() }}
          trong tổng số {{ $warehouses->total() }} kho
        </small>
        {{ $warehouses->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- OFFCANVAS FORM --}}
  <div class="offcanvas offcanvas-end" style="width:480px" tabindex="-1" id="warehouseOffcanvas">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="warehouseOffcanvasTitle">Thêm kho hàng</h5>
      <button type="button" class="btn-close" data-coreui-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="warehouseForm" method="POST">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="mb-3 fw-semibold text-primary border-bottom pb-1">Thông tin kho</div>

        <div class="row g-3 mb-3">
          <div class="col-5">
            <label class="form-label">Mã kho <span class="text-danger">*</span></label>
            <input type="text" class="form-control text-uppercase font-monospace"
                   id="wCode" name="code" placeholder="VD: WH001" required maxlength="50">
          </div>
          <div class="col-7">
            <label class="form-label">Tên kho <span class="text-danger">*</span></label>
            <input type="text" class="form-control"
                   id="wName" name="name" placeholder="VD: Kho chính HCM" required maxlength="200">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Quản lý kho</label>
          <select class="form-select" id="wManager" name="manager_id">
            <option value="">— Chưa phân công —</option>
            @foreach ($employees as $emp)
              <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->code }})</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Thông tin liên hệ</div>

        <div class="mb-3">
          <label class="form-label">Số điện thoại</label>
          <div class="input-group">
            <span class="input-group-text">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-phone') }}"></use></svg>
            </span>
            <input type="text" class="form-control" id="wPhone" name="phone"
                   placeholder="VD: 0901234567" maxlength="20">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <div class="input-group">
            <span class="input-group-text">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-envelope-closed') }}"></use></svg>
            </span>
            <input type="email" class="form-control" id="wEmail" name="email"
                   placeholder="VD: warehouse@company.com" maxlength="200">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Địa chỉ</label>
          <div class="input-group">
            <span class="input-group-text">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-location-pin') }}"></use></svg>
            </span>
            <textarea class="form-control" id="wAddress" name="address"
                      rows="2" maxlength="500" placeholder="Địa chỉ đầy đủ..."></textarea>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-medium">Trạng thái</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status"
                     id="wStatusActive" value="1" checked>
              <label class="form-check-label text-success" for="wStatusActive">Hoạt động</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status"
                     id="wStatusInactive" value="0">
              <label class="form-check-label text-secondary" for="wStatusInactive">Ngừng hoạt động</label>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            Lưu kho
          </button>
          <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="offcanvas">Hủy</button>
        </div>
      </form>
    </div>
  </div>

  {{-- MODAL XÁC NHẬN XÓA --}}
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center px-4 pb-2">
          <svg class="icon icon-3xl text-danger mb-3">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
          </svg>
          <h6 class="fw-semibold mb-1">Xác nhận xóa</h6>
          <p class="text-body-secondary small mb-0">
            Bạn có chắc muốn xóa kho<br>
            <strong id="deleteWarehouseName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Không thể xóa nếu còn nhân viên hoặc tồn kho liên quan.</p>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  const routeStore = '{{ route('master.warehouse.store') }}';
  const routeBase  = '{{ url('master/warehouse') }}';

  function openModal(id = null, code = '', name = '', managerId = null,
                     phone = '', email = '', address = '', status = 1) {
    const offcanvas = new coreui.OffCanvas(document.getElementById('warehouseOffcanvas'));
    const form      = document.getElementById('warehouseForm');
    const title     = document.getElementById('warehouseOffcanvasTitle');
    const method    = document.getElementById('formMethod');

    form.reset();
    document.getElementById('wStatusActive').checked = true;

    if (id) {
      title.textContent = 'Chỉnh sửa kho hàng';
      form.action       = `${routeBase}/${id}`;
      method.value      = 'PUT';
      document.getElementById('wCode').value    = code;
      document.getElementById('wName').value    = name;
      document.getElementById('wManager').value = managerId ?? '';
      document.getElementById('wPhone').value   = phone;
      document.getElementById('wEmail').value   = email;
      document.getElementById('wAddress').value = address;
      document.getElementById(status == 1 ? 'wStatusActive' : 'wStatusInactive').checked = true;
    } else {
      title.textContent = 'Thêm kho hàng';
      form.action       = routeStore;
      method.value      = 'POST';
    }

    offcanvas.show();
    setTimeout(() => document.getElementById('wCode').focus(), 400);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteWarehouseName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // Auto viết hoa mã kho
  document.getElementById('wCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });
</script>
@endpush