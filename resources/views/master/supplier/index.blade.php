@extends('layouts.app')

@section('title', 'Nhà cung cấp — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Nhà cung cấp</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Nhà cung cấp</h4>
      <small class="text-body-secondary">Quản lý danh sách nhà cung cấp hàng hóa</small>
    </div>
    <button class="btn btn-primary" onclick="openForm()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm mới
    </button>
  </div>

  {{-- CARDS THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-building') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng nhà cung cấp</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $activeCount }}</div>
            <div class="text-body-secondary small">Đang hợp tác</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách nhà cung cấp</span>
      <form method="GET" action="{{ route('master.supplier.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:260px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã, tên, MST, SĐT, email...">
        </div>
        <select class="form-select" name="status" style="width:130px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || (request('status') !== null && request('status') !== ''))
          <a href="{{ route('master.supplier.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:55px">#</th>
              <th style="width:110px">Mã NCC</th>
              <th>Tên nhà cung cấp</th>
              <th style="width:130px">Mã số thuế</th>
              <th style="width:130px">Số điện thoại</th>
              <th style="width:180px">Email</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:110px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($suppliers as $index => $supplier)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($suppliers->currentPage() - 1) * $suppliers->perPage() + $index + 1 }}
                </td>
                <td><code class="text-primary fw-medium">{{ $supplier->code }}</code></td>
                <td>
                  <div class="fw-medium">{{ $supplier->name }}</div>
                  @if ($supplier->address)
                    <div class="small text-body-secondary text-truncate" style="max-width:280px">
                      <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-location-pin') }}"></use></svg>
                      {{ $supplier->address }}
                    </div>
                  @endif
                </td>
                <td class="small text-body-secondary">{{ $supplier->tax_code ?? '—' }}</td>
                <td class="small">
                  @if ($supplier->phone)
                    <a href="tel:{{ $supplier->phone }}" class="text-body text-decoration-none">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-phone') }}"></use></svg>
                      {{ $supplier->phone }}
                    </a>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
                <td class="small">
                  @if ($supplier->email)
                    <a href="mailto:{{ $supplier->email }}" class="text-body text-decoration-none">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-envelope-closed') }}"></use></svg>
                      {{ $supplier->email }}
                    </a>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
                <td class="text-center">
                  @if ($supplier->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openForm({{ $supplier->id }})"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $supplier->id }}, '{{ addslashes($supplier->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-building') }}"></use>
                  </svg>
                  Chưa có nhà cung cấp nào
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

    @if ($suppliers->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }}
          trong tổng số {{ $suppliers->total() }} nhà cung cấp
        </small>
        {{ $suppliers->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- OFFCANVAS FORM --}}
  <div class="offcanvas offcanvas-end" style="width:480px" tabindex="-1" id="supplierOffcanvas">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="supplierOffcanvasTitle">Thêm nhà cung cấp</h5>
      <button type="button" class="btn-close" data-coreui-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="supplierForm" method="POST">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        {{-- THÔNG TIN CƠ BẢN --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1">Thông tin cơ bản</div>

        <div class="row g-3 mb-3">
          <div class="col-5">
            <label class="form-label">Mã NCC <span class="text-danger">*</span></label>
            <input type="text" class="form-control text-uppercase" id="sCode" name="code"
                   placeholder="VD: NCC001" required maxlength="50">
          </div>
          <div class="col-7">
            <label class="form-label">Tên nhà cung cấp <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="sName" name="name"
                   placeholder="Tên công ty / cá nhân" required maxlength="200">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Mã số thuế</label>
          <input type="text" class="form-control" id="sTaxCode" name="tax_code"
                 placeholder="VD: 0123456789" maxlength="20">
        </div>

        {{-- LIÊN HỆ --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Thông tin liên hệ</div>

        <div class="mb-3">
          <label class="form-label">Số điện thoại</label>
          <div class="input-group">
            <span class="input-group-text">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-phone') }}"></use></svg>
            </span>
            <input type="text" class="form-control" id="sPhone" name="phone"
                   placeholder="VD: 0901234567" maxlength="20">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <div class="input-group">
            <span class="input-group-text">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-envelope-closed') }}"></use></svg>
            </span>
            <input type="email" class="form-control" id="sEmail" name="email"
                   placeholder="VD: contact@supplier.com" maxlength="200">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Địa chỉ</label>
          <div class="input-group">
            <span class="input-group-text">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-location-pin') }}"></use></svg>
            </span>
            <textarea class="form-control" id="sAddress" name="address"
                      rows="2" maxlength="500"
                      placeholder="Địa chỉ đầy đủ..."></textarea>
          </div>
        </div>

        {{-- TRẠNG THÁI --}}
        <div class="mb-4">
          <label class="form-label fw-medium">Trạng thái</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status" id="sStatusActive" value="1" checked>
              <label class="form-check-label text-success" for="sStatusActive">Đang hợp tác</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status" id="sStatusInactive" value="0">
              <label class="form-check-label text-secondary" for="sStatusInactive">Ngừng hợp tác</label>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            Lưu nhà cung cấp
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
            Bạn có chắc muốn xóa nhà cung cấp<br>
            <strong id="deleteSupplierName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Không thể xóa nếu đã có phiếu nhập kho liên quan.</p>
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
  const routeStore = '{{ route('master.supplier.store') }}';
  const routeBase  = '{{ url('master/supplier') }}';
  const suppliers  = @json($suppliers->keyBy('id'));

  function openForm(id = null) {
    const offcanvas = new coreui.OffCanvas(document.getElementById('supplierOffcanvas'));
    const form      = document.getElementById('supplierForm');
    const title     = document.getElementById('supplierOffcanvasTitle');
    const method    = document.getElementById('formMethod');

    form.reset();
    document.getElementById('sStatusActive').checked = true;

    if (id && suppliers[id]) {
      const s = suppliers[id];
      title.textContent = 'Chỉnh sửa nhà cung cấp';
      form.action       = `${routeBase}/${id}`;
      method.value      = 'PUT';

      document.getElementById('sCode').value     = s.code ?? '';
      document.getElementById('sName').value     = s.name ?? '';
      document.getElementById('sTaxCode').value  = s.tax_code ?? '';
      document.getElementById('sPhone').value    = s.phone ?? '';
      document.getElementById('sEmail').value    = s.email ?? '';
      document.getElementById('sAddress').value  = s.address ?? '';
      document.getElementById(s.status == 1 ? 'sStatusActive' : 'sStatusInactive').checked = true;
    } else {
      title.textContent = 'Thêm nhà cung cấp';
      form.action       = routeStore;
      method.value      = 'POST';
    }

    offcanvas.show();
    setTimeout(() => document.getElementById('sCode').focus(), 400);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteSupplierName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // Auto viết hoa mã
  document.getElementById('sCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });
</script>
@endpush