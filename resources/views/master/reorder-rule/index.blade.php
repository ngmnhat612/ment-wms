@extends('layouts.app')

@section('title', 'Gán Min-Max')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Gán Min-Max</li>
@endsection

@section('content')

  {{-- SORT HELPER --}}
  @php
    $sort = request('sort', '');
    $dir  = request('dir', '');
    $nextDir = function($col) use ($sort, $dir) {
      if ($sort !== $col) return 'asc';
      if ($dir === 'asc')  return 'desc';
      return '';
    };
    $sortUrl = function($col) use ($sort, $dir, $nextDir) {
      $nd = $nextDir($col);
      if ($nd === '') return request()->fullUrlWithQuery(['sort' => '', 'dir' => '', 'page' => 1]);
      return request()->fullUrlWithQuery(['sort' => $col, 'dir' => $nd, 'page' => 1]);
    };
    $sortIcon = function($col) use ($sort, $dir) {
      if ($sort !== $col || $dir === '') {
        $icon = 'cil-swap-vertical';
      } elseif ($dir === 'asc') {
        $icon = 'cil-sort-alpha-up';
      } else {
        $icon = 'cil-sort-alpha-down';
      }
      return "<svg class=\"icon icon-sm ms-1\"><use xlink:href=\"" . asset('vendor/coreui/icons/sprites/free.svg#' . $icon) . "\"></use></svg>";
    };
  @endphp

  {{-- HEADER --}}
  <div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" onclick="openModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm mới
    </button>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="fw-semibold flex-shrink-0">Gán Min-Max</span>
      <form method="GET" action="{{ route('master.reorder-rule.index') }}"
            class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">

        <div class="input-group" style="min-width:260px;flex:2">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tìm theo mã hoặc tên vật tư">
        </div>

        <select class="form-select" name="status" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Trạng thái</option>
          @foreach (\App\Enums\ActiveStatus::options() as $val => $label)
            <option value="{{ $val }}" {{ request('status') === (string) $val ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>

        @php
          $hasFilter = request('search') || (request('status') !== null && request('status') !== '');
        @endphp
        @if ($hasFilter)
          <a href="{{ route('master.reorder-rule.index') }}" class="btn btn-outline-secondary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter-x') }}"></use></svg>
          </a>
        @else
          <button type="submit" class="btn btn-primary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
          </button>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:4%">#</th>
              <th style="width:8%">
                <a href="{{ $sortUrl('code') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Mã MenT {!! $sortIcon('code') !!}
                </a>
              </th>
              <th>
                <a href="{{ $sortUrl('product_name') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Tên vật tư {!! $sortIcon('product_name') !!}
                </a>
              </th>
              <th style="width:8%">
                <a href="{{ $sortUrl('min_qty') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Min {!! $sortIcon('min_qty') !!}
                </a>
              </th>
              <th style="width:8%">
                <a href="{{ $sortUrl('max_qty') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Max {!! $sortIcon('max_qty') !!}
                </a>
              </th>
              <th style="width:14%">Người phụ trách</th>
              <th style="width:14%">Ghi chú</th>
              <th class="text-center" style="width:8%">Trạng thái</th>
              <th class="text-center" style="width:8%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($rules as $index => $rule)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($rules->currentPage() - 1) * $rules->perPage() + $index + 1 }}
                </td>
                <td>
                  <code class="text-primary fw-medium">{{ $rule->product->code ?? '-' }}</code>
                </td>
                <td class="fw-medium">{{ $rule->product->name ?? '-' }}</td>
                <td>{{ number_format($rule->min_qty, 0) }}</td>
                <td>{{ number_format($rule->max_qty, 0) }}</td>
                <td>
                  @if ($rule->employee)
                    <div class="fw-medium">{{ $rule->employee->name }}</div>
                    <div class="small text-body-secondary font-monospace">{{ $rule->employee->code }}</div>
                  @else
                    <span class="text-body-secondary small">-</span>
                  @endif
                </td>
                <td class="small">{{ $rule->note ?: '-' }}</td>
                <td class="text-center">
                  @if ($rule->status === \App\Enums\ActiveStatus::Active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                    onclick="openModal(
                        {{ $rule->id }},
                        {{ $rule->product_id }},
                        {{ $rule->employee_id ?? 'null' }},
                        {{ $rule->min_qty }},
                        {{ $rule->max_qty }},
                        '{{ addslashes($rule->note ?? '') }}',
                        {{ $rule->status->value }}
                    )" title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $rule->id }}, '{{ addslashes($rule->product->name ?? '') }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                  </svg>
                  Chưa có quy tắc nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
        Hiển thị <strong>{{ $rules->firstItem() }}</strong>-<strong>{{ $rules->lastItem() }}</strong>
        trong tổng số <strong>{{ $rules->total() }}</strong> quy tắc
      </small>
      {{ $rules->appends(request()->query())->links('pagination::bootstrap-5') }}
      <style>.card-footer .pagination { margin-bottom: 0; }</style>
    </div>
  </div>

  {{-- ===== MODAL TẠO / SỬA ===== --}}
  <div class="modal fade" id="ruleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="ruleForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="formMethod" value="POST">

          {{-- warehouse_id luôn là kho mặc định, ẩn không hiển thị --}}
          <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouse?->id }}">

          <div class="modal-header">
            <h5 class="modal-title" id="ruleModalLabel">Thêm quy tắc tái đặt hàng</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            <div class="mb-3">
              <label class="form-label fw-medium">Vật tư <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="rProductText"
                      placeholder="Nhập hoặc chọn"
                      list="productDatalist" autocomplete="off"
                      oninput="resolveProduct()" onblur="resolveProduct()">
                <datalist id="productDatalist">
                  @foreach ($products as $p)
                    <option value="{{ $p->code }} - {{ $p->name }}"></option>
                  @endforeach
                </datalist>
                <input type="hidden" id="rProduct" name="product_id"
                      value="{{ old('product_id') }}">
                <div class="invalid-feedback" id="rProductError"></div>
              @error('product_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-medium">Min <span class="text-danger">*</span></label>
                <input type="number" step="10" min="0"
                       class="form-control {{ $errors->has('min_qty') ? 'is-invalid' : '' }}"
                       id="rMinQty" name="min_qty"
                       value="{{ old('min_qty', 0) }}" required>
                @error('min_qty')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-6">
                <label class="form-label fw-medium">Max <span class="text-danger">*</span></label>
                <input type="number" step="10" min="0"
                       class="form-control {{ $errors->has('max_qty') ? 'is-invalid' : '' }}"
                       id="rMaxQty" name="max_qty"
                       value="{{ old('max_qty', 0) }}" required>
                @error('max_qty')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Người phụ trách <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="rEmployeeText"
                      placeholder="Nhập hoặc chọn"
                      list="employeeDatalist" autocomplete="off"
                      oninput="resolveEmployee()" onblur="resolveEmployee()">
                <datalist id="employeeDatalist">
                  @foreach ($employees as $emp)
                    <option value="{{ $emp->name }} ({{ $emp->code }})"></option>
                  @endforeach
                </datalist>
                <input type="hidden" id="rEmployee" name="employee_id"
                      value="{{ old('employee_id') }}">
                <div class="invalid-feedback" id="rEmployeeError"></div>
              @error('employee_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Ghi chú</label>
              <textarea class="form-control {{ $errors->has('note') ? 'is-invalid' : '' }}"
                        id="rNote" name="note"
                        rows="2" maxlength="500">{{ old('note') }}</textarea>
              @error('note')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div>
              <label class="form-label fw-medium">Trạng thái</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="rStatusActive" value="1" checked>
                  <label class="form-check-label text-success" for="rStatusActive">Hoạt động</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="rStatusInactive" value="0">
                  <label class="form-check-label text-secondary" for="rStatusInactive">Ngừng hoạt động</label>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" id="rSubmitBtn" class="btn btn-primary" {{ $defaultWarehouse ? '' : 'disabled' }}>
              <span id="rSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
              <svg id="rSubmitIcon" class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              <span id="rSubmitLabel">Lưu</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ===== MODAL XÁC NHẬN XÓA ===== --}}
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
            Bạn có chắc muốn xóa quy tắc của<br>
            <strong id="deleteRuleName" class="text-body"></strong>?
          </p>
          <p></p>
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
  const routeStore = '{{ route('master.reorder-rule.store') }}';
  const routeBase  = '{{ url('master/reorder-rule') }}';

  const products  = @json($products->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name]));
  const employees = @json($employees->map(fn($e) => ['id' => $e->id, 'code' => $e->code, 'name' => $e->name]));

  const hasServerErrors = {{ $errors->any() ? 'true' : 'false' }};

  function resolveProduct(strict = false) {
    const text  = document.getElementById('rProductText').value.trim();
    const el    = document.getElementById('rProductText');
    const hid   = document.getElementById('rProduct');
    const err   = document.getElementById('rProductError');
    const match = products.find(p => `${p.code} - ${p.name}` === text);

    if (match) {
      hid.value = match.id;
      if (strict) {
        el.classList.remove('is-invalid');
        err.textContent = '';
      }
    } else {
      hid.value = '';
      if (strict) {
        el.classList.add('is-invalid');
        err.textContent = text ? 'Vật tư không tồn tại trong hệ thống.' : 'Vui lòng chọn vật tư.';
      }
    }
  }

  function resolveEmployee(strict = false) {
    const text  = document.getElementById('rEmployeeText').value.trim();
    const el    = document.getElementById('rEmployeeText');
    const hid   = document.getElementById('rEmployee');
    const err   = document.getElementById('rEmployeeError');
    const match = employees.find(e => `${e.name} (${e.code})` === text);

    if (match) {
      hid.value = match.id;
      if (strict) {
        el.classList.remove('is-invalid');
        err.textContent = '';
      }
    } else {
      hid.value = '';
      if (strict) {
        el.classList.add('is-invalid');
        err.textContent = text ? 'Người phụ trách không tồn tại.' : 'Vui lòng chọn người phụ trách.';
      }
    }
  }

  function clearValidation() {
    document.querySelectorAll('#ruleForm .is-invalid').forEach(el => {
      el.classList.remove('is-invalid');
    });
    document.getElementById('rProductError').textContent  = '';
    document.getElementById('rEmployeeError').textContent = '';
  }

  function openModal(id = null, productId = null, employeeId = null,
                     minQty = 0, maxQty = 0, note = '', status = 1) {
    const modal  = new coreui.Modal(document.getElementById('ruleModal'));
    const form   = document.getElementById('ruleForm');
    const title  = document.getElementById('ruleModalLabel');
    const method = document.getElementById('formMethod');

    clearValidation();

    if (id) {
        title.textContent = 'Chỉnh sửa quy tắc';
        form.action       = `${routeBase}/${id}`;
        method.value      = 'PUT';
        document.getElementById('rProductText').setAttribute('disabled', true);
    } else {
        title.textContent = 'Thêm quy tắc';
        form.action       = routeStore;
        method.value      = 'POST';
        if (!hasServerErrors) form.reset();
        document.getElementById('rProductText').removeAttribute('disabled');
    }

    const prod = products.find(p => p.id == productId);
    document.getElementById('rProductText').value = prod ? `${prod.code} - ${prod.name}` : '';
    document.getElementById('rProduct').value     = productId ?? '';

    const emp = employees.find(e => e.id == employeeId);
    document.getElementById('rEmployeeText').value = emp ? `${emp.name} (${emp.code})` : '';
    document.getElementById('rEmployee').value     = employeeId ?? '';

    document.getElementById('rMinQty').value  = minQty;
    document.getElementById('rMaxQty').value  = maxQty;
    document.getElementById('rNote').value    = note;
    document.getElementById(status == 1 ? 'rStatusActive' : 'rStatusInactive').checked = true;

    modal.show();
    setTimeout(() => document.getElementById('rProductText').focus(), 300);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteRuleName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  @if ($errors->any())
    openModal(
      null,
      {{ old('product_id') ?? 'null' }},
      {{ old('employee_id') ?? 'null' }},
      {{ old('min_qty', 0) }},
      {{ old('max_qty', 0) }},
      '{{ addslashes(old('note')) }}',
      {{ old('status', 1) }}
    );

    @foreach ($errors->keys() as $field)
      document.getElementById(
        @switch($field)
          @case('min_qty')     'rMinQty'      @break
          @case('max_qty')     'rMaxQty'      @break
          @case('product_id')  'rProductText' @break
          @case('employee_id') 'rEmployeeText' @break
          @default             ''
        @endswitch
      )?.classList.add('is-invalid');
    @endforeach
  @endif

  document.getElementById('ruleForm').addEventListener('submit', function (e) {
    resolveProduct();
    resolveEmployee();

    if (!document.getElementById('rProduct').value) {
      document.getElementById('rProductText').classList.add('is-invalid');
      document.getElementById('rProductError').textContent = 'Vui lòng chọn vật tư.';
      e.preventDefault();
      return;
    }

    if (!document.getElementById('rEmployee').value) {
      document.getElementById('rEmployeeText').classList.add('is-invalid');
      document.getElementById('rEmployeeError').textContent = 'Vui lòng chọn người phụ trách.';
      e.preventDefault();
      return;
    }

    const btn     = document.getElementById('rSubmitBtn');
    const spinner = document.getElementById('rSubmitSpinner');
    const icon    = document.getElementById('rSubmitIcon');
    const label   = document.getElementById('rSubmitLabel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    label.textContent = 'Đang lưu...';
  });

  document.getElementById('ruleModal').addEventListener('hidden.coreui.modal', function () {
    const btn     = document.getElementById('rSubmitBtn');
    const spinner = document.getElementById('rSubmitSpinner');
    const icon    = document.getElementById('rSubmitIcon');
    const label   = document.getElementById('rSubmitLabel');

    btn.disabled = false;
    spinner.classList.add('d-none');
    icon.classList.remove('d-none');
    label.textContent = 'Lưu';
  });
</script>
@endpush