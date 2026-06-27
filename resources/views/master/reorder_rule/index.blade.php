@extends('layouts.app')

@section('title', 'Reorder Rules — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Quy tắc tái đặt hàng (Reorder Rules)</li>
@endsection

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Quy tắc tái đặt hàng (Reorder Rules)</h4>
      <small class="text-body-secondary">Cảnh báo khi tồn kho xuống dưới ngưỡng tối thiểu</small>
    </div>
    <button class="btn btn-primary" onclick="openCreate()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm Rule
    </button>
  </div>

  {{-- Banner gợi ý tạo reorder rule cho sản phẩm mới --}}
  @if (session('suggest_reorder-rule'))
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-3 mb-4" role="alert">
      <svg class="icon icon-xl flex-shrink-0 text-success">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
      </svg>
      <div class="flex-grow-1">
        <strong>Đã thêm "{{ session('new_product_name') }}" thành công!</strong><br>
        <span class="small">Bước tiếp theo: tạo Reorder Rule để hệ thống cảnh báo khi tồn kho xuống thấp.</span>
      </div>
      <button type="button" class="btn btn-success btn-sm flex-shrink-0" onclick="openCreateWithProduct()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
        Tạo ngay
      </button>
      <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
  @endif

  {{-- THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-bell') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng số rules</div>
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
            <div class="text-body-secondary small">Đang hoạt động</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-danger">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $belowCount }}</div>
            <div class="text-body-secondary small">Dưới ngưỡng tối thiểu</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách Reorder Rules</span>
      <form method="GET" action="{{ route('master.reorder-rule.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:240px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã, tên hàng hóa...">
        </div>
        <select class="form-select" name="status" style="width:130px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
        </select>
        <div class="form-check d-flex align-items-center ms-1 gap-2">
          <input class="form-check-input" type="checkbox" name="below_min" value="1"
                 id="belowMinChk" {{ request('below_min') ? 'checked' : '' }}>
          <label class="form-check-label small" for="belowMinChk">Chỉ dưới ngưỡng</label>
        </div>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || (request('status') !== null && request('status') !== '') || request('below_min'))
          <a href="{{ route('master.reorder-rule.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:50px" class="text-center">#</th>
              <th>Hàng hóa</th>
              <th>Kho</th>
              <th class="text-end" style="width:120px">Tồn hiện tại</th>
              <th class="text-end" style="width:110px">Min qty</th>
              <th class="text-end" style="width:110px">Max qty</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:100px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($rules as $index => $rule)
              @php
                $isBelowMin = (float)($rule->current_stock ?? 0) < (float)$rule->min_qty;
              @endphp
              <tr class="{{ $isBelowMin && $rule->status == 1 ? 'table-warning' : '' }}">
                <td class="text-center text-body-secondary">
                  {{ ($rules->currentPage() - 1) * $rules->perPage() + $index + 1 }}
                </td>
                <td>
                  <div class="fw-medium">{{ $rule->product->name ?? '—' }}</div>
                  <div class="text-body-secondary small font-monospace">{{ $rule->product->code ?? '' }}</div>
                </td>
                <td>
                  <div class="fw-medium">{{ $rule->warehouse->name ?? '—' }}</div>
                  <div class="text-body-secondary small font-monospace">{{ $rule->warehouse->code ?? '' }}</div>
                </td>
                <td class="text-end">
                  @php $currentStock = (float)($rule->current_stock ?? 0); @endphp
                  <span class="fw-semibold {{ $isBelowMin && $rule->status == 1 ? 'text-danger' : '' }}">
                    {{ number_format($currentStock, 3) }}
                  </span>
                  @if ($isBelowMin && $rule->status == 1)
                    <svg class="icon icon-sm text-danger ms-1" title="Dưới ngưỡng tối thiểu!">
                      <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                  @endif
                </td>
                <td class="text-end text-body-secondary">{{ number_format($rule->min_qty, 3) }}</td>
                <td class="text-end text-body-secondary">{{ number_format($rule->max_qty, 3) }}</td>
                <td class="text-center">
                  @if ($rule->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1" title="Chỉnh sửa"
                          onclick="openEdit({{ $rule }})">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" title="Xóa"
                          onclick="confirmDelete({{ $rule->id }}, '{{ addslashes($rule->product->name ?? '') }}')">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-bell') }}"></use>
                  </svg>
                  Chưa có reorder rule nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($rules->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $rules->firstItem() }}–{{ $rules->lastItem() }}
          trong tổng số {{ $rules->total() }} rules
        </small>
        {{ $rules->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  {{-- MODAL THÊM / SỬA --}}
  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  <div class="modal fade" id="ruleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-semibold" id="ruleModalLabel">Thêm Reorder Rule</h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <form id="ruleForm" method="POST">
          @csrf
          <span id="methodField"></span>
          <div class="modal-body">

            {{-- Hàng hóa --}}
            <div class="mb-3">
              <label class="form-label fw-medium" for="product_id">Hàng hóa <span class="text-danger">*</span></label>
              <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id">
                <option value="">— Chọn hàng hóa —</option>
                @foreach ($products as $p)
                  <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                    [{{ $p->code }}] {{ $p->name }}
                  </option>
                @endforeach
              </select>
              @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- kho --}}
            <div class="mb-3">
              <label>Kho <span class="text-danger">*</span></label>
              <select name="warehouse_id" id="warehouse_id">
                @foreach ($warehouses as $wh)
                  <option value="{{ $wh->id }}">{{ $wh->code }} — {{ $wh->name }}</option>
                @endforeach
              </select>
              @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3">
              {{-- Min Qty --}}
              <div class="col-6">
                <label class="form-label fw-medium" for="min_qty">Ngưỡng tối thiểu (Min) <span class="text-danger">*</span></label>
                <input type="number" step="0.001" min="0"
                       class="form-control @error('min_qty') is-invalid @enderror"
                       id="min_qty" name="min_qty" value="{{ old('min_qty', 0) }}"
                       oninput="validateMaxMin()">
                <div class="form-text">Cảnh báo khi tồn &lt; min</div>
                @error('min_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- Max Qty --}}
              <div class="col-6">
                <label class="form-label fw-medium" for="max_qty">Ngưỡng tối đa (Max) <span class="text-danger">*</span></label>
                <input type="number" step="0.001" min="0"
                       class="form-control @error('max_qty') is-invalid @enderror"
                       id="max_qty" name="max_qty" value="{{ old('max_qty', 0) }}"
                       oninput="validateMaxMin()">
                <div class="form-text">Số lượng mục tiêu khi đặt mua</div>
                @error('max_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            {{-- Cảnh báo max < min --}}
            <div id="minMaxWarning" class="alert alert-warning py-1 px-3 mt-2 d-none small">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
              Ngưỡng tối đa phải ≥ ngưỡng tối thiểu.
            </div>

            {{-- Email cảnh báo --}}
            <div class="mb-3 mt-3">
              <label class="form-label fw-medium" for="alert_email">Email cảnh báo</label>
              <input type="email" class="form-control @error('alert_email') is-invalid @enderror"
                     id="alert_email" name="alert_email"
                     value="{{ old('alert_email') }}"
                     placeholder="vd: warehouse@company.com (để trống nếu không dùng)">
              @error('alert_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Trạng thái --}}
            <div class="mb-3">
              <label class="form-label fw-medium" for="status_rule">Trạng thái <span class="text-danger">*</span></label>
              <select class="form-select @error('status') is-invalid @enderror" id="status_rule" name="status">
                <option value="1">Hoạt động</option>
                <option value="0">Ngừng</option>
              </select>
              @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Ghi chú --}}
            <div class="mb-0">
              <label class="form-label fw-medium" for="rule_note">Ghi chú</label>
              <textarea class="form-control" id="rule_note" name="note" rows="2" maxlength="500">{{ old('note') }}</textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- MODAL XÓA --}}
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
            Bạn có chắc muốn xóa reorder rule của<br>
            <strong id="deleteRuleName" class="text-body"></strong>?
          </p>
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
  function validateMaxMin() {
    const min = parseFloat(document.getElementById('min_qty').value) || 0;
    const max = parseFloat(document.getElementById('max_qty').value) || 0;
    document.getElementById('minMaxWarning').classList.toggle('d-none', max >= min);
  }

  function openCreate() {
    const form = document.getElementById('ruleForm');
    document.getElementById('ruleModalLabel').textContent = 'Thêm Reorder Rule';
    form.action = '{{ route("master.reorder-rule.store") }}';
    document.getElementById('methodField').innerHTML = '';
    form.reset();
    document.getElementById('status_rule').value = 1;
    document.getElementById('min_qty').value = 0;
    document.getElementById('max_qty').value = 0;
    document.getElementById('minMaxWarning').classList.add('d-none');
    new coreui.Modal(document.getElementById('ruleModal')).show();
  }

  function openEdit(rule) {
    const form = document.getElementById('ruleForm');
    document.getElementById('ruleModalLabel').textContent = 'Chỉnh sửa Reorder Rule';
    form.action = `{{ url('master/reorder-rule') }}/${rule.id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    document.getElementById('product_id').value  = rule.product_id;
    document.getElementById('warehouse_id').value = rule.warehouse_id;
    document.getElementById('min_qty').value      = rule.min_qty;
    document.getElementById('max_qty').value      = rule.max_qty;
    document.getElementById('status_rule').value  = rule.status;
    document.getElementById('rule_note').value    = rule.note || '';
    validateMaxMin();

    new coreui.Modal(document.getElementById('ruleModal')).show();
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteRuleName').textContent = name;
    document.getElementById('deleteForm').action = `{{ url('master/reorder-rule') }}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  function openCreateWithProduct() {
    openCreate();
    const newProductId = {{ request('new_product_id', 'null') }};
    if (newProductId) {
      document.getElementById('product_id').value = newProductId;
    }
  }

  @if ($errors->any())
    new coreui.Modal(document.getElementById('ruleModal')).show();
  @endif
</script>
@endpush