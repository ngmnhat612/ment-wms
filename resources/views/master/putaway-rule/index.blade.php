@extends('layouts.app')

@section('title', 'Gán gợi ý vị trí')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item">Vật tư</li>
  <li class="breadcrumb-item active">Gán gợi ý vị trí</li>
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
        $icon = 'cil-sort-alpha-down';
      } else {
        $icon = 'cil-sort-alpha-up';
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
      <span class="fw-semibold flex-shrink-0">Gán gợi ý vị trí</span>
      <form method="GET" action="{{ route('master.putaway-rule.index') }}"
            class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">

        <div class="input-group" style="min-width:260px;flex:2">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search" maxlength="200"
                 value="{{ request('search') }}" placeholder="Tìm theo vật tư hoặc danh mục">
        </div>

        <select class="form-select" name="apply_on" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Quy tắc áp dụng</option>
          <option value="product"  {{ request('apply_on') === 'product'  ? 'selected' : '' }}>Theo vật tư</option>
          <option value="category" {{ request('apply_on') === 'category' ? 'selected' : '' }}>Theo danh mục</option>
        </select>

        <select class="form-select" name="status" style="min-width:130px;flex:1" onchange="this.form.submit()">
          <option value="">Trạng thái</option>
          @foreach (\App\Enums\ActiveStatus::options() as $val => $label)
            <option value="{{ $val }}" {{ request('status') === (string) $val ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>

        @php
          $hasFilter = request('search') || request('apply_on') ||
                       (request('status') !== null && request('status') !== '');
        @endphp
        @if ($hasFilter)
          <a href="{{ route('master.putaway-rule.index') }}" class="btn btn-outline-secondary">
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
              <th style="width:12%">Quy tắc áp dụng</th>
              <th>
                <a href="{{ $sortUrl('applies_on') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Áp dụng cho {!! $sortIcon('applies_on') !!}
                </a>
              </th>
              <th>Vị trí gợi ý</th>
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
                  @if ($rule->product_id)
                    <span class="small">Theo vật tư</span>
                  @else
                    <span class="small">Theo danh mục</span>
                  @endif
                </td>
                <td>
                  @if ($rule->product_id)
                    <div class="fw-medium">{{ $rule->product->name ?? '-' }}</div>
                    <div class="small text-body-secondary font-monospace">{{ $rule->product->code ?? '' }}</div>
                  @else
                    <div class="fw-medium">{{ $rule->category->name ?? '-' }}</div>
                  @endif
                </td>
                <td>
                  @if ($rule->destinationLocation)
                    <div class="fw-medium">{{ $rule->destinationLocation->name }}</div>
                    <div class="small text-body-secondary font-monospace">{{ $rule->destinationLocation->code }}</div>
                  @else
                    <span class="text-body-secondary small">-</span>
                  @endif
                </td>
                <td class="small" title="{{ $rule->note }}">
                  {{ truncate_text($rule->note) }}
                </td>
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
                        '{{ $rule->product_id ? 'product' : 'category' }}',
                        {{ $rule->product_id ?? 'null' }},
                        {{ $rule->category_id ?? 'null' }},
                        {{ $rule->location_id ?? 'null' }},
                        {{ $rule->status->value }},
                        '{{ addslashes($rule->note ?? '') }}'
                    )" title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $rule->id }}, '{{ addslashes($rule->product->name ?? $rule->category->name ?? '') }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-body-secondary py-5">
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
          <input type="hidden" name="rule_id" id="rRuleId" value="">
          <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouse?->id }}">

          <div class="modal-header">
            <h5 class="modal-title" id="ruleModalLabel">Thêm quy tắc gán vị trí</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            {{-- Loại áp dụng --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Quy tắc áp dụng <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="applyProduct" name="applyOnToggle"
                        value="product" checked onchange="toggleApplyOn('product')">
                  <label class="form-check-label" for="applyProduct">Theo vật tư</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="applyCategory" name="applyOnToggle"
                        value="category" onchange="toggleApplyOn('category')">
                  <label class="form-check-label" for="applyCategory">Theo danh mục</label>
                </div>
                <input type="hidden" id="rApplyOn" name="apply_on" value="product">
              </div>
            </div>

            {{-- Vật tư --}}
            <div class="mb-3" id="productField">
              <label class="form-label fw-medium">Vật tư <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="rProductText"
                     placeholder="Nhập hoặc chọn"
                     list="productDatalist" autocomplete="off">
              <datalist id="productDatalist">
                @foreach ($products as $p)
                  <option value="{{ $p->code }} - {{ $p->name }}"></option>
                @endforeach
              </datalist>
              <input type="hidden" id="rProduct" name="product_id" value="{{ old('product_id') }}">
              <div class="invalid-feedback" id="rProductError"></div>
              @error('product_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            {{-- Nhóm hàng --}}
            <div class="mb-3 d-none" id="categoryField">
              <label class="form-label fw-medium">Danh mục <span class="text-danger">*</span></label>
                <select class="form-select {{ $errors->has('category_id') ? 'is-invalid' : '' }}" id="rCategory">
                  <option value="">- Chọn nhóm -</option>
                  @foreach ($categories as $c)
                    <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                  @endforeach
                  {{-- Các danh mục KHÔNG active: ẩn mặc định (d-none), chỉ hiện
                       khi JS cần gán cho rule đang chỉnh sửa có danh mục đã
                       ngưng hoạt động (tránh mất lựa chọn khi mở form edit).
                       Không hiện khi tạo mới. --}}
                  @foreach ($allCategories as $c)
                    @if ($c->status?->value !== \App\Enums\ActiveStatus::Active->value)
                      <option value="{{ $c->id }}" class="d-none" data-inactive="1">
                        {{ $c->name }} (Ngưng hoạt động)
                      </option>
                    @endif
                  @endforeach
                </select>
                <input type="hidden" id="rCategoryHidden" name="category_id" value="{{ old('category_id') }}">
                <div class="invalid-feedback" id="rCategoryError"></div>
              @error('category_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            {{-- Vị trí đích --}}
            <div class="mb-3" id="locationField">
              <label class="form-label fw-medium">Vị trí gợi ý <span class="text-danger">*</span></label>
              <select class="form-select {{ $errors->has('location_id') ? 'is-invalid' : '' }}"
                      id="rLocation" name="location_id">
                <option value="">- Chọn vị trí -</option>
                @foreach ($locations as $loc)
                  <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                    [{{ $loc->code }}] {{ $loc->name }}
                  </option>
                @endforeach
                {{-- Các vị trí KHÔNG active: ẩn mặc định, chỉ hiện khi JS cần
                     gán cho rule đang chỉnh sửa có vị trí đã ngưng hoạt động. --}}
                @foreach ($allLocations as $loc)
                  @if ($loc->status?->value !== \App\Enums\ActiveStatus::Active->value)
                    <option value="{{ $loc->id }}" class="d-none" data-inactive="1">
                      [{{ $loc->code }}] {{ $loc->name }} (Ngưng hoạt động)
                    </option>
                  @endif
                @endforeach
              </select>
              <div class="invalid-feedback" id="rLocationError"></div>
              @error('location_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            {{-- Ghi chú --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Ghi chú</label>
              <textarea class="form-control {{ $errors->has('note') ? 'is-invalid' : '' }}"
                        id="rNote" name="note"
                        rows="2" maxlength="500">{{ old('note') }}</textarea>
              @error('note')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Trạng thái --}}
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
                  <label class="form-check-label text-secondary" for="rStatusInactive">Ngưng hoạt động</label>
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

@php
  // Tính trước dữ liệu JS cho pool "Vật tư" khi Chỉnh sửa (active + đúng 1
  // item inactive hiện tại) ở đây, vì @json() trên 1 dòng dài với toán tử
  // ?-> lồng trong closure khiến Blade's directive parser (paren-counter,
  // không phải tokenizer PHP thật) bị lệch khi đếm ngoặc và báo lỗi
  // "Unclosed '[' does not match ')'".
  $allProductsJs = $allProducts->map(function ($p) {
      return [
          'id'       => $p->id,
          'code'     => $p->code,
          'name'     => $p->name,
          'inactive' => $p->status?->value !== \App\Enums\ActiveStatus::Active->value,
      ];
  });
@endphp

@push('scripts')
<script>
  const routeStore = '{{ route('master.putaway-rule.store') }}';
  const routeBase  = '{{ url('master/putaway-rule') }}';

  // Danh sách gốc — chỉ active. Đây là những gì được phép chọn khi TẠO MỚI,
  // và cũng là nền cho danh sách được phép chọn khi CHỈNH SỬA.
  const products = @json($products->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name]));

  // Danh sách đầy đủ (kể cả Ngưng hoạt động) — CHỈ dùng để tra cứu tên/mã của
  // 1 vật tư cụ thể (vật tư đang được gán sẵn cho rule khi mở form Chỉnh sửa).
  // Không dùng để hiển thị toàn bộ lựa chọn, để tránh cho phép gán MỚI một
  // vật tư khác đang Ngưng hoạt động.
  const allProducts = @json($allProductsJs);

  const hasServerErrors = {{ $errors->any() ? 'true' : 'false' }};

  // ID của vật tư hiện đang gán cho rule đang mở (nếu đang Ngưng hoạt động) —
  // item DUY NHẤT được phép giữ nguyên trong lựa chọn dù đã Ngưng hoạt động.
  // Reset về null mỗi khi mở modal.
  let currentInactiveProductId = null;

  function productLabel(p, inactive = false) {
    const base = `${p.code} - ${p.name}`;
    return inactive ? `${base} (Ngưng hoạt động)` : base;
  }

  // Pool được phép CHỌN cho vật tư: active-only, cộng thêm đúng 1 vật tư đang
  // Ngưng hoạt động nếu đó là vật tư hiện tại của rule (để không mất lựa chọn
  // đang có sẵn khi mở form Chỉnh sửa).
  function allowedProductPool() {
    if (currentInactiveProductId == null) return products;
    const extra = allProducts.find(p => p.id == currentInactiveProductId);
    return extra ? [...products, extra] : products;
  }

  // Cập nhật lại nội dung datalist Vật tư theo pool được phép chọn hiện tại —
  // chạy lại mỗi khi mở modal, vì "vật tư inactive được phép" thay đổi theo rule.
  function refreshProductDatalist() {
    const datalist = document.getElementById('productDatalist');
    datalist.innerHTML = allowedProductPool().map(p => {
      const inactive = p.id == currentInactiveProductId;
      const opt = document.createElement('option');
      opt.value = productLabel(p, inactive);
      return opt.outerHTML;
    }).join('');
  }

  // ─── Helper KHOÁ/MỞ cho <select> Danh mục & Vị trí ─────────────────────────
  // Dùng chung pattern với module Product: option Ngưng hoạt động được render
  // sẵn trong DOM với class d-none + data-inactive="1"; hàm này chỉ hiện tạm
  // đúng 1 option khi giá trị hiện tại của rule trỏ tới nó.
  function revealInactiveOptionIfNeeded(selectId, value) {
    const select = document.getElementById(selectId);
    if (value === null || value === undefined || value === '') return;
    const matched = select.querySelector(`option[value="${value}"]`);
    if (matched && matched.dataset.inactive === '1') {
      matched.classList.remove('d-none');
    }
  }

  // Ẩn lại toàn bộ option Ngưng hoạt động của 1 <select> — dùng khi chuyển
  // sang chế độ Tạo mới, đảm bảo Tạo mới chỉ có thể chọn giá trị active.
  function hideAllInactiveOptions(selectId) {
    document.getElementById(selectId)
      .querySelectorAll('option[data-inactive="1"]')
      .forEach(opt => opt.classList.add('d-none'));
  }

  // ─── Toggle product / category field ──────────────────────────────────────
  function toggleApplyOn(type) {
    document.getElementById('productField').classList.toggle('d-none',  type !== 'product');
    document.getElementById('categoryField').classList.toggle('d-none', type !== 'category');
    document.getElementById('rApplyOn').value = type;

    // Xóa dữ liệu của field không còn áp dụng, tránh gửi lẫn product_id/category_id
    if (type === 'product') {
      document.getElementById('rCategory').value = '';
      document.getElementById('rCategoryHidden').value = '';
      document.getElementById('rCategory').classList.remove('is-invalid');
      document.getElementById('rCategoryError').textContent = '';
    } else {
      document.getElementById('rProductText').value = '';
      document.getElementById('rProduct').value = '';
      document.getElementById('rProductText').classList.remove('is-invalid');
      document.getElementById('rProductError').textContent = '';
    }
  }

  // ─── Resolve product datalist ──────────────────────────────────────────────
  function resolveProduct(strict = false) {
    const text  = document.getElementById('rProductText').value.trim();
    const el    = document.getElementById('rProductText');
    const hid   = document.getElementById('rProduct');
    const err   = document.getElementById('rProductError');
    const pool  = allowedProductPool();
    const match = pool.find(p => {
      const inactive = p.id == currentInactiveProductId;
      return productLabel(p, inactive) === text;
    });

    if (match) {
      hid.value = match.id;
      if (strict) { el.classList.remove('is-invalid'); err.textContent = ''; }
    } else {
      hid.value = '';
      if (strict) {
        el.classList.add('is-invalid');
        err.textContent = text ? 'Vật tư không tồn tại trong hệ thống.' : 'Vui lòng chọn vật tư.';
      }
    }
  }

  // ─── Clear validation (chỉ dùng khi mở modal) ─────────────────────────────
  function clearValidation() {
    document.querySelectorAll('#ruleForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.getElementById('rProductError').textContent  = '';
    document.getElementById('rCategoryError').textContent = '';
    document.getElementById('rLocationError').textContent = '';
  }

  // ─── Xóa lỗi tương ứng khi người dùng tương tác ──────────────────────────
  document.getElementById('rProductText').addEventListener('input', function () {
    this.classList.remove('is-invalid');
    document.getElementById('rProductError').textContent = '';
    document.querySelectorAll('#productField .invalid-feedback.d-block').forEach(el => el.remove());
    resolveProduct(false);
  });

  document.getElementById('rCategory').addEventListener('change', function () {
    this.classList.remove('is-invalid');
    document.getElementById('rCategoryHidden').value = this.value;
    document.getElementById('rCategoryError').textContent = '';
    document.querySelectorAll('#categoryField .invalid-feedback.d-block').forEach(el => el.remove());
  });

  document.getElementById('rLocation').addEventListener('change', function () {
    this.classList.remove('is-invalid');
    document.getElementById('rLocationError').textContent = '';
    document.querySelectorAll('#locationField .invalid-feedback.d-block').forEach(el => el.remove());
  });

  // ─── Mở modal TẠO / SỬA ───────────────────────────────────────────────────
  function openModal(id = null, applyOn = 'product', productId = null, categoryId = null, locationId = null, status = 1, note = '') {
    const modal  = new coreui.Modal(document.getElementById('ruleModal'));
    const form   = document.getElementById('ruleForm');
    const title  = document.getElementById('ruleModalLabel');
    const method = document.getElementById('formMethod');

    clearValidation();

    // Xác định vật tư hiện tại của rule có đang Ngưng hoạt động không — nếu
    // có, đây là vật tư DUY NHẤT được "mở khoá" tạm thời để vẫn hiển thị
    // đúng, các vật tư inactive khác vẫn không được chọn.
    const isProductInactive = productId != null && !products.some(p => p.id == productId);
    currentInactiveProductId = isProductInactive ? productId : null;
    refreshProductDatalist();

  if (id) {
    title.textContent = 'Chỉnh sửa quy tắc';
    form.action       = `${routeBase}/${id}`;
    method.value      = 'PUT';
    document.getElementById('rRuleId').value = id;
    document.getElementById('applyProduct').disabled  = true;
    document.getElementById('applyCategory').disabled = true;
    document.getElementById('rProductText').setAttribute('disabled', true);
    document.getElementById('rCategory').setAttribute('disabled', true);
    document.querySelectorAll('#ruleForm .invalid-feedback.d-block').forEach(el => el.remove());

    // Chỉnh sửa: chỉ mở khoá đúng option Danh mục / Vị trí đang được rule
    // này gán (nếu đang Ngưng hoạt động), không hiện các item inactive khác.
    revealInactiveOptionIfNeeded('rCategory', categoryId);
    revealInactiveOptionIfNeeded('rLocation', locationId);
  } else {
    title.textContent = 'Thêm quy tắc';
    form.action       = routeStore;
    method.value      = 'POST';
    document.getElementById('rRuleId').value = '';
    if (!hasServerErrors) form.reset();
    document.getElementById('applyProduct').disabled  = false;
    document.getElementById('applyCategory').disabled = false;
    document.getElementById('rProductText').removeAttribute('disabled');
    document.getElementById('rCategory').removeAttribute('disabled');

    // Tạo mới: luôn chỉ cho phép chọn giá trị active.
    hideAllInactiveOptions('rCategory');
    hideAllInactiveOptions('rLocation');
  }

    // Apply on
    const type = applyOn || 'product';
    document.getElementById(type === 'product' ? 'applyProduct' : 'applyCategory').checked = true;
    toggleApplyOn(type);

    // Vật tư
    const prodPool = allowedProductPool();
    const prod = prodPool.find(p => p.id == productId);
    document.getElementById('rProductText').value = prod ? productLabel(prod, prod.id == currentInactiveProductId) : '';
    document.getElementById('rProduct').value     = productId ?? '';

    // Danh mục
    document.getElementById('rCategory').value = categoryId ?? '';
    document.getElementById('rCategoryHidden').value = categoryId ?? '';

    // Vị trí, ghi chú & trạng thái
    document.getElementById('rLocation').value = locationId ?? '';
    document.getElementById('rNote').value = note;
    document.getElementById(status == 1 ? 'rStatusActive' : 'rStatusInactive').checked = true;

    modal.show();
  }

  // ─── Xóa ──────────────────────────────────────────────────────────────────
  function confirmDelete(id, name) {
    document.getElementById('deleteRuleName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // ─── Submit validation ─────────────────────────────────────────────────────
  document.getElementById('ruleForm').addEventListener('submit', function (e) {
    const type = document.getElementById('rApplyOn').value;

    if (type === 'product') {
        resolveProduct(true);
        if (!document.getElementById('rProduct').value) {
            e.preventDefault();
            document.getElementById('rProductText').focus();
            return;
        }
    }

    if (type === 'category' && !document.getElementById('rCategory').value) {
      document.getElementById('rCategory').classList.add('is-invalid');
      document.getElementById('rCategoryError').textContent = 'Vui lòng chọn danh mục.';
      e.preventDefault();
      document.getElementById('rCategory').focus();
      return;
    }

    if (!document.getElementById('rLocation').value) {
      document.getElementById('rLocation').classList.add('is-invalid');
      document.getElementById('rLocationError').textContent = 'Vui lòng chọn vị trí gợi ý.';
      e.preventDefault();
      document.getElementById('rLocation').focus();
      return;
    }

    const btn     = document.getElementById('rSubmitBtn');
    const spinner = document.getElementById('rSubmitSpinner');
    const icon    = document.getElementById('rSubmitIcon');
    const label   = document.getElementById('rSubmitLabel');
    btn.disabled    = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    label.textContent = 'Đang lưu...';
});

  document.getElementById('ruleModal').addEventListener('shown.coreui.modal', function () {
    document.getElementById('rProductText').focus();
  });

  // ─── Mở lại modal nếu có lỗi server ──────────────────────────────────────
  @if ($errors->any())
    const applyOnOld = '{{ old('apply_on', 'product') }}';
    const ruleIdOld  = {{ old('rule_id') ?? 'null' }};
    openModal(
      ruleIdOld,
      applyOnOld,
      {{ old('product_id') ?? 'null' }},
      {{ old('category_id') ?? 'null' }},
      {{ old('location_id') ?? 'null' }},
      {{ old('status', 1) }},
      '{{ addslashes(old('note', '')) }}'
    );
    @foreach ($errors->keys() as $field)
      document.getElementById(
        @switch($field)
          @case('product_id')  'rProductText' @break
          @case('category_id') 'rCategory'    @break
          @case('location_id') 'rLocation'    @break
          @default             ''
        @endswitch
      )?.classList.add('is-invalid');
    @endforeach
  @endif
</script>
@endpush
