@extends('layouts.app')

@section('title', 'Danh sách vật tư')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item">Vật tư</li>
  <li class="breadcrumb-item active">Danh sách vật tư</li>
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
    <button class="btn btn-primary" onclick="openForm()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm mới
    </button>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header">
      <form method="GET" action="{{ route('master.product.index') }}" class="d-flex align-items-center gap-3 flex-wrap">

        {{-- CỘT 1: Tiêu đề --}}
        <div class="flex-shrink-0">
          <span class="fw-semibold text-nowrap">Danh sách vật tư</span>
        </div>

        {{-- CỘT 2: 2 thanh tìm kiếm + 3 filter --}}
        <div class="flex-grow-1" style="min-width:500px">
          <div class="row g-2">

            {{-- Dòng 1: 2 thanh tìm kiếm --}}
            <div class="col-6">
              <div class="input-group">
                <span class="input-group-text">
                  <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
                </span>
                <input type="text" class="form-control" name="search"
                      value="{{ request('search') }}" placeholder="Tìm theo mã hoặc tên vật tư">
              </div>
            </div>
            <div class="col-6">
              <div class="input-group">
                <span class="input-group-text">
                  <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
                </span>
                <input type="text" class="form-control" name="search_spec"
                      value="{{ request('search_spec') }}" placeholder="Tìm theo thông số kỹ thuật">
              </div>
            </div>

            {{-- Dòng 2: 3 filter --}}
            <div class="col-4">
              <select class="form-select" name="category_id" onchange="this.form.submit()">
                <option value="">Danh mục</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-4">
              <select class="form-select" name="tracking_type" onchange="this.form.submit()">
                <option value="">Theo dõi</option>
                @foreach (\App\Enums\TrackingType::options() as $val => $label)
                  <option value="{{ $val }}" {{ request('tracking_type') === (string) $val ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-4">
              <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="">Trạng thái</option>
                @foreach (\App\Enums\ActiveStatus::options() as $val => $label)
                  <option value="{{ $val }}" {{ request('status') === (string) $val ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

          </div>
        </div>

        {{-- CỘT 3: Nút Lọc --}}
        <div class="flex-shrink-0">
          @php
            $hasFilter = request('search') || request('search_spec') || request('category_id') || request('tracking_type') || (request('status') !== null && request('status') !== '');
          @endphp

          <button type="submit" class="btn btn-primary {{ $hasFilter ? 'd-none' : '' }}">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
          </button>

          @if ($hasFilter)
            <a href="{{ route('master.product.index') }}" class="btn btn-outline-secondary">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter-x') }}"></use></svg>
            </a>
          @endif
        </div>

      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:4%">#</th>
              <th style="width:6%">Ảnh</th>
              <th style="width:8%">
                <a href="{{ $sortUrl('code') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Mã MenT {!! $sortIcon('code') !!}
                </a>
              </th>
              <th>
                <a href="{{ $sortUrl('name') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Tên {!! $sortIcon('name') !!}
                </a>
              </th>
              <th style="width:14%">
                <a href="{{ $sortUrl('specification') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  TSKT {!! $sortIcon('specification') !!}
                </a>
              </th>
              <th style="width:8%">Danh mục</th>
              <th style="width:8%">ĐVT</th>
              <th style="width:8%">Theo dõi</th>
              <th class="text-center" style="width:8%">Trạng thái</th>
              <th class="text-center" style="width:8%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($products as $index => $product)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($products->currentPage() - 1) * $products->perPage() + $index + 1 }}
                </td>
                <td>
                  @if ($product->image_path)
                    <img src="{{ Storage::url($product->image_path) }}"
                        class="img-zoomable"
                        style="width:50px; height:50px; object-fit:contain; cursor:zoom-in;"
                        alt="{{ $product->name }}"
                        data-preview="{{ Storage::url($product->image_path) }}">
                  @else
                    <div class="rounded bg-body-secondary d-flex align-items-center justify-content-center"
                         style="width:50px; height:50px">
                      <svg class="icon icon-sm text-body-tertiary">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-image') }}"></use>
                      </svg>
                    </div>
                  @endif
                </td>
                <td><code class="text-primary fw-medium">{{ $product->code ?? '-' }}</code></td>
                <td class="fw-medium">{{ $product->name ?? '-' }}</td>
                <td class="small" title="{{ $product->specification }}">
                  {{ truncate_text($product->specification) }}
                </td>
                <td class="small">{{ $product->category?->code ?? '-' }}</td>
                <td class="small">{{ $product->uom?->name ?? '-' }}</td>
                <td class="small">{{ $product->tracking_type?->label() ?? '-' }}</td>
                <td class="text-center">
                  @if ($product->status === \App\Enums\ActiveStatus::Active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openForm({{ $product->id }})"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                  </svg>
                  Chưa có vật tư nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
          Hiển thị <strong>{{ $products->firstItem() }}</strong>-<strong>{{ $products->lastItem() }}</strong>
          trong tổng số <strong>{{ $products->total() }}</strong> vật tư
      </small>
      {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
      <style>
        .card-footer .pagination { margin-bottom: 0; }
      </style>
    </div>

  </div>

  {{-- OFFCANVAS FORM --}}
  <div class="offcanvas offcanvas-end" style="width:540px" tabindex="-1" id="productOffcanvas">
    <div class="offcanvas-header border-bottom">
      <div class="d-flex align-items-center gap-3">
        <h5 class="offcanvas-title mb-0" id="productOffcanvasTitle">Thêm vật tư</h5>
        <div class="form-check form-switch mb-0" id="variantToggleWrap">
          <input class="form-check-input" type="checkbox"
                id="pIsVariant" name="is_variant" value="1"
                onchange="toggleVariantMode(this.checked)">
          <label class="form-check-label small text-body-secondary" for="pIsVariant">
            Biến thể
          </label>
        </div>
      </div>
      <button type="button" class="btn-close" data-coreui-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="productForm" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        {{-- ===== THÔNG TIN CƠ BẢN ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1">Thông tin cơ bản</div>

        {{-- Chế độ thường: Mã MenT --}}
        <div class="mb-3 d-block" id="codeWrap">
          <label class="form-label">Mã MenT</label>
          <input type="text" class="form-control text-uppercase"
                id="pCode" name="code"
                placeholder="Tự động"
                oninput="sanitizeCodeInput(this)"
                onblur="checkProductCodeUnique(this, 'pCodeError')">
          <div class="invalid-feedback" id="pCodeError"></div>
        </div>

        {{-- Chế độ biến thể: Mã MenT gốc + Mã MenT biến thể (cùng hàng 50/50) --}}
        <div class="mb-3 d-none" id="variantCodeRow">
          <div class="row g-3">
            <div class="col-6" id="parentCodeWrap">
              <label class="form-label">Mã MenT gốc <span class="text-danger">*</span></label>
              <input type="text" class="form-control text-uppercase"
                    id="pParentCode" name="parent_code"
                    placeholder="Nhập hoặc chọn"
                    list="parentCodeList"
                    oninput="sanitizeCodeInput(this); fetchParentProduct()"
                    onblur="fetchParentProduct()">
              <div class="invalid-feedback" id="pParentCodeError"></div>
              <datalist id="parentCodeList">
                @foreach ($allProducts as $p)
                  <option value="{{ $p->code }}">{{ $p->name }}</option>
                @endforeach
              </datalist>
            </div>
            <div class="col-6" id="variantCodeWrap">
              <label class="form-label">Mã MenT biến thể</label>
              <input type="text" class="form-control text-uppercase"
                    id="pVariantCode" name="code"
                    placeholder="TỰ ĐỘNG"
                    oninput="sanitizeCodeInput(this)"
                    onblur="checkProductCodeUnique(this, 'pVariantCodeError')">
              <div class="invalid-feedback" id="pVariantCodeError"></div>
            </div>
          </div>
        </div>

        {{-- Chế độ thường: Tên --}}
        <div class="mb-3 d-block" id="nameNormalWrap">
          <label class="form-label">Tên <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="pName" name="name"
                placeholder="Nhập tên" maxlength="200">
          <div class="invalid-feedback" id="pNameError"></div>
        </div>

        {{-- Chế độ biến thể: Tên --}}
        <div class="mb-3 d-none" id="nameVariantWrap">
          <label class="form-label">Tên <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="pNameVariant" name="name"
                placeholder="Nhập tên" maxlength="200">
          <div class="invalid-feedback" id="pNameVariantError"></div>
        </div>

        {{-- Danh mục + ĐVT (khoá khi là biến thể) --}}
        <div class="mb-3">
          <label class="form-label">Danh mục <span class="text-danger" id="categoryRequired">*</span></label>
          <select class="form-select" id="pCategory" name="category_id">
            <option value="">- Chọn danh mục -</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
          <div class="invalid-feedback" id="pCategoryError"></div>
          <input type="hidden" id="pCategoryHidden">
        </div>
        <div class="mb-3">
          <label class="form-label">ĐVT <span class="text-danger" id="uomRequired">*</span></label>
          <select class="form-select" id="pUom" name="uom_id">
            <option value="">- Chọn ĐVT -</option>
            @foreach ($uoms as $uom)
              <option value="{{ $uom->id }}">{{ $uom->name }}</option>
            @endforeach
          </select>
          <div class="invalid-feedback" id="pUomError"></div>
        </div>

        {{-- Ảnh --}}
        <div class="mb-3">
          <label class="form-label">Ảnh</label>
          <div class="d-flex gap-3 align-items-start">
            <div id="imagePreviewWrap"
                class="rounded border bg-body-secondary d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0"
                style="width:80px;height:80px">
              <img id="imagePreview" src="" alt="" class="d-none"
                  style="width:80px;height:80px;object-fit:cover;">
              <svg id="imageIcon" class="icon icon-2xl text-body-tertiary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-image') }}"></use>
              </svg>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2">
                <input type="file" id="pImage" name="image" class="d-none"
                      accept="image/jpeg,image/png,image/webp,image/gif"
                      onchange="previewImage(this)">

                <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0"
                        onclick="document.getElementById('pImage').click()">
                  Chọn tệp
                </button>

                <span class="form-control form-control-sm text-truncate" id="fileNameDisplay"
                      title="Không tệp nào được chọn">
                  Không tệp nào được chọn
                </span>

                <button type="button" id="clearImageBtn" class="btn-close d-none flex-shrink-0"
                        style="font-size:0.6rem" onclick="clearImage()"></button>
              </div>
              @error('image')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
              <div class="form-text" id="imageHint">JPEG, PNG, WebP - tối đa 2 MB</div>
              <input type="hidden" name="remove_image" id="removeImage" value="0">
            </div>
          </div>
        </div>

        {{-- ===== THÔNG SỐ KỸ THUẬT ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Thông số kỹ thuật</div>

        <div class="mb-3">
          <label class="form-label">Thông số kỹ thuật</label>
          <textarea class="form-control" id="pSpec" name="specification" rows="3"
                    placeholder="Mô tả thông số kỹ thuật của vật tư..." maxlength="500"></textarea>
          <div class="form-text">Tối đa 500 ký tự</div>
        </div>

        {{-- ===== QUẢN LÝ TỒN KHO ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Quản lý tồn kho</div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label">Kiểu theo dõi</label>
            <select class="form-select" id="pTracking" name="tracking_type" required>
              @foreach (\App\Enums\TrackingType::options() as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Quy tắc xuất kho</label>
            <select class="form-select" id="pRotation" name="stock_rotation">
              @foreach (\App\Enums\StockRotation::options() as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3 d-none" id="alertExpiryWrap">
            <label class="form-label">
              Cảnh báo trước hết hạn (ngày) <span class="text-danger">*</span>
            </label>
            <input type="number" class="form-control" id="pAlertExpiry"
                  name="alert_before_expiry" min="1" placeholder="Ví dụ: 30">
            <div class="invalid-feedback" id="pAlertExpiryError"></div>
          </div>

          <div class="col-6">
            <label class="form-label">Ngưỡng tồn tối thiểu (Min)</label>
            <input type="text" inputmode="numeric" pattern="[0-9]*"
                    class="form-control" id="pMinQty" name="min_qty"
                    value="0"
                    onkeydown="blockInvalidNumberKeys(event)"
                    onpaste="blockInvalidNumberPaste(event)"
                    oninput="sanitizeNumberInput(this)"
                    onblur="validateMinMaxQty()"
                    data-max="99999999">
            <div class="invalid-feedback" id="pMinQtyError"></div>
            </div>
            <div class="col-6">
            <label class="form-label">Ngưỡng tồn tối đa (Max)</label>
            <input type="text" inputmode="numeric" pattern="[0-9]*"
                    class="form-control" id="pMaxQty" name="max_qty"
                    value="0"
                    onkeydown="blockInvalidNumberKeys(event)"
                    onpaste="blockInvalidNumberPaste(event)"
                    oninput="sanitizeNumberInput(this)"
                    onblur="validateMinMaxQty()"
                    data-max="99999999">
            <div class="invalid-feedback" id="pMaxQtyError"></div>
            </div>

          {{-- ===== Gợi ý vị trí ===== --}}
          <div class="col-12">
            <label class="form-label">Gợi ý vị trí</label>
            <input type="text" class="form-control" id="pLocationText"
                  placeholder="Nhập hoặc chọn"
                  list="locationDatalist" autocomplete="off"
                  oninput="resolveLocation()" onblur="resolveLocation()">
            <datalist id="locationDatalist">
              @foreach ($locations as $loc)
                <option value="[{{ $loc->code }}] {{ $loc->name }}"></option>
              @endforeach
            </datalist>
            <input type="hidden" id="pLocation" name="location_id" value="">
            <div class="invalid-feedback" id="pLocationError"></div>
          </div>
        </div>

        {{-- ===== THÔNG TIN THÊM ===== --}}
        <div class="mb-3 fw-semibold text-primary border-bottom pb-1 mt-4">Thông tin thêm</div>

        <div class="mb-4">
          <label class="form-label">Trạng thái</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status"
                     id="pStatusActive" value="1" checked>
              <label class="form-check-label text-success" for="pStatusActive">Hoạt động</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status"
                     id="pStatusInactive" value="0">
              <label class="form-check-label text-secondary" for="pStatusInactive">Ngưng hoạt động</label>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" id="productSubmitBtn" class="btn btn-primary flex-grow-1">
            <span id="productSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <svg id="productSubmitIcon" class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            <span id="productSubmitLabel">Lưu</span>
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
            Bạn có chắc muốn xóa vật tư<br>
            <strong id="deleteProductName" class="text-body"></strong>?
          </p>
          <p></p>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm"
                  data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div id="imgPreviewPopup" style="
    display:none;
    position:fixed;
    z-index:9999;
    background:#fff;
    border-radius:8px;
    box-shadow:0 4px 20px rgba(0,0,0,0.25);
    padding:6px;
    pointer-events:none;
  ">
    <img id="imgPreviewPopupImg" src="" style="width:500px;height:500px;object-fit:contain;">
  </div>

@include('master.product.partials.image-zoom-modal')

@endsection

@push('scripts')
<script>
  const routeStore        = '{{ route('master.product.store') }}';
  const routeStoreVariant = '{{ route('master.product.storeVariant') }}';
  const routeBase         = '{{ url('master/product') }}';

  // Map product data for edit mode
  const productsMap = {};
  const locations = @json($locations->map(fn($l) => ['id' => $l->id, 'code' => $l->code, 'name' => $l->name]));

  @foreach ($products as $p)
    productsMap[{{ $p->id }}] = {
      id:                  {{ $p->id }},
      code:                '{{ addslashes($p->code) }}',
      name:                '{{ addslashes($p->name) }}',
      category_id:         {{ $p->category_id ?? 'null' }},
      uom_id:              {{ $p->uom_id ?? 'null' }},
      specification:       '{{ addslashes($p->specification ?? '') }}',
      tracking_type:       {{ $p->tracking_type?->value ?? 1 }},
      stock_rotation:      {{ $p->stock_rotation?->value ?? 1 }},
      status:              {{ $p->status?->value ?? 1 }},
      image_path:          '{{ $p->image_path ?? '' }}',
      image_url:           '{{ $p->image_path ? Storage::url($p->image_path) : '' }}',
      image_name:          '{{ $p->image_path ? addslashes(basename($p->image_path)) : '' }}',
      min_qty: {{ $p->reorderRule->min_qty ?? 0 }},
      max_qty: {{ $p->reorderRule->max_qty ?? 0 }},
      location_id:   {{ $p->putawayRule->location_id ?? 'null' }},
      location_text: '{{ $p->putawayRule && $p->putawayRule->destinationLocation ? "[" . addslashes($p->putawayRule->destinationLocation->code) . "] " . addslashes($p->putawayRule->destinationLocation->name) : "" }}',
    };
  @endforeach

  // ===== HELPER: KHOÁ / MỞ DANH MỤC =====
  // Dùng chung cho cả openForm() và nhánh khôi phục lỗi validate (update:)
  // để tránh 2 nơi bị lệch logic như đã xảy ra trước đó.
  function lockCategoryForEdit(categoryId) {
    const catSelect = document.getElementById('pCategory');
    const catHidden = document.getElementById('pCategoryHidden');
    catSelect.value    = categoryId ?? '';
    catSelect.disabled = true;
    catSelect.classList.add('bg-body-secondary');
    catHidden.name  = 'category_id';
    catHidden.value = categoryId ?? '';
  }

  function unlockCategoryForCreate() {
    const catSelect = document.getElementById('pCategory');
    const catHidden = document.getElementById('pCategoryHidden');
    catSelect.disabled = false;
    catSelect.classList.remove('bg-body-secondary');
    catHidden.name  = '';
    catHidden.value = '';
  }

  // Set giá trị cho <select>, fallback về '' (option mặc định "- Chọn ... -")
  // nếu giá trị không khớp option nào tồn tại — tránh hiển thị rỗng "lạ".
  function setSelectValueSafe(selectId, value) {
    const select = document.getElementById(selectId);
    const exists = value !== '' && select.querySelector(`option[value="${value}"]`);
    select.value = exists ? value : '';
  }

  // ===== MỞ FORM =====
  function openForm(id = null) {
    const offcanvasEl = document.getElementById('productOffcanvas');
    const offcanvas   = new coreui.OffCanvas(offcanvasEl);
    const form        = document.getElementById('productForm');
    const title       = document.getElementById('productOffcanvasTitle');
    const method      = document.getElementById('formMethod');
    const codeInput   = document.getElementById('pCode');

    form.reset();
    document.getElementById('pStatusActive').checked = true;
    resetImagePreview();
    offcanvasEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    offcanvasEl.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

    // Xoá alert lỗi validate còn sót lại từ lần submit thất bại trước đó
    const oldAlert = form.querySelector('.alert-danger');
    if (oldAlert) oldAlert.remove();

    // Reset toggle biến thể về chế độ thường mỗi lần mở form
    document.getElementById('pIsVariant').checked = false;
    toggleVariantMode(false);

    // Ẩn toggle Biến thể khi chỉnh sửa (không cho phép chuyển loại)
    document.getElementById('variantToggleWrap').classList.toggle('d-none', !!id);

    codeInput.removeAttribute('readonly');
    codeInput.classList.remove('bg-body-secondary');

    if (id && productsMap[id]) {
      const p = productsMap[id];
      title.textContent  = 'Chỉnh sửa vật tư';
      form.action        = `${routeBase}/${id}`;
      method.value       = 'PUT';

      codeInput.value                             = p.code;
      document.getElementById('pName').value     = p.name;
      setSelectValueSafe('pUom', p.uom_id ?? '');
      document.getElementById('pSpec').value      = p.specification;
      document.getElementById('pTracking').value  = p.tracking_type;
      document.getElementById('pRotation').value  = p.stock_rotation;
      document.getElementById(p.status == 1 ? 'pStatusActive' : 'pStatusInactive').checked = true;
      document.getElementById('pMinQty').value = p.min_qty ?? 0;
      document.getElementById('pMaxQty').value = p.max_qty ?? 0;
      document.getElementById('pLocationText').value = p.location_text ?? '';
      document.getElementById('pLocation').value     = p.location_id ?? '';

      if (p.image_url) {
        showImagePreview(p.image_url, p.image_name);
      }

      // Lock mã khi edit
      codeInput.setAttribute('readonly', true);
      codeInput.classList.add('bg-body-secondary');

      // Lock danh mục khi edit
      lockCategoryForEdit(p.category_id);

    } else {
      title.textContent = 'Thêm vật tư';
      form.action       = routeStore;
      method.value      = 'POST';

      unlockCategoryForCreate();
      document.getElementById('pMinQty').value = 0;
      document.getElementById('pMaxQty').value = 0;
      document.getElementById('pLocationText').value = '';
      document.getElementById('pLocation').value     = '';
    }

    offcanvas.show();
    setTimeout(() => document.getElementById('pName').focus(), 400);
  }

  // ===== IMAGE PREVIEW =====
  function showImagePreview(src, fileName = '') {
      const img     = document.getElementById('imagePreview');
      const icon    = document.getElementById('imageIcon');
      const btn     = document.getElementById('clearImageBtn');
      const nameDsp = document.getElementById('fileNameDisplay');

      if (img)  { img.src = src; img.classList.remove('d-none'); }
      if (icon) { icon.classList.add('d-none'); }
      if (btn)  { btn.classList.remove('d-none'); }
      if (nameDsp) {
          const text = fileName || 'Không tệp nào được chọn';
          nameDsp.textContent = text;
          nameDsp.title = text;
      }
  }

  function resetImagePreview() {
      const img     = document.getElementById('imagePreview');
      const icon    = document.getElementById('imageIcon');
      const btn     = document.getElementById('clearImageBtn');
      const nameDsp = document.getElementById('fileNameDisplay');

      if (img)  { img.src = ''; img.classList.add('d-none'); }
      if (icon) { icon.classList.remove('d-none'); }
      if (btn)  { btn.classList.add('d-none'); }
      if (nameDsp) {
          nameDsp.textContent = 'Không tệp nào được chọn';
          nameDsp.title = 'Không tệp nào được chọn';
      }
  }

  function clearImage() {
    resetImagePreview();
    document.getElementById('pImage').value = '';
    document.getElementById('removeImage').value = '1';
  }

  function previewImage(input) {
      if (input.files && input.files[0]) {
          const reader = new FileReader();
          reader.onload = e => showImagePreview(e.target.result, input.files[0].name);
          reader.readAsDataURL(input.files[0]);
          document.getElementById('removeImage').value = '0';
      }
  }

  // ===== XÓA VẬT TƯ =====
  function confirmDelete(id, name) {
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // ===== AUTO UPPERCASE MÃ =====
  document.getElementById('pCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  // Ẩn/hiện theo FEFO (value = 2 nếu enum FEFO=2)
  document.getElementById('pRotation').addEventListener('change', function () {
    const wrap = document.getElementById('alertExpiryWrap');
    const input = document.getElementById('pAlertExpiry');
    const isFEFO = this.value == '2'; // FEFO=2 theo enum hiện tại
    wrap.classList.toggle('d-none', !isFEFO);
    input.required = isFEFO;
    if (!isFEFO) input.value = '';
  });

  // ===== VALIDATION ERROR - MỞ LẠI OFFCANVAS =====
  document.addEventListener('DOMContentLoaded', function () {
    const pfa = (document.body.dataset.pfa || '').trim();
    if (!pfa) return;

    const alertHtml = `
      <div class="alert alert-danger alert-dismissible mb-3" role="alert">
        <strong>Kiểm tra lại:</strong>
        <ul class="mb-0 mt-1 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>`;
    document.getElementById('productForm').insertAdjacentHTML('afterbegin', alertHtml);

    if (pfa === 'variant') {
      // Mở lại form ở chế độ biến thể
      document.getElementById('productOffcanvasTitle').textContent = 'Thêm biến thể';
      document.getElementById('productForm').action = routeStoreVariant;
      document.getElementById('formMethod').value   = 'POST';

      document.getElementById('pIsVariant').checked = true;
      toggleVariantMode(true);

      // Điền lại old input
      document.getElementById('pParentCode').value  = @json(old('parent_code', ''));
      document.getElementById('pVariantCode').value = @json(old('code', ''));
      document.getElementById('pNameVariant').value = @json(old('name', ''));
      document.getElementById('pSpec').value        = @json(old('specification', ''));
      document.getElementById(
        @json(old('status', '1')) == '1' ? 'pStatusActive' : 'pStatusInactive'
      ).checked = true;
      document.getElementById('pMinQty').value = @json(old('min_qty', 0));
      document.getElementById('pMaxQty').value = @json(old('max_qty', 0));
      document.getElementById('pLocation').value = @json(old('location_id', ''));
      const oldLoc1 = locations.find(l => l.id == @json(old('location_id', 'null')));
      document.getElementById('pLocationText').value = oldLoc1 ? `[${oldLoc1.code}] ${oldLoc1.name}` : '';

    } else if (pfa.startsWith('update:')) {
      const id = pfa.split(':')[1];
      const p  = productsMap[id] ?? null;

      document.getElementById('productOffcanvasTitle').textContent = 'Chỉnh sửa vật tư';

      // Gọi toggleVariantMode TRƯỚC — nó sẽ tạm set action = routeStore,
      // nhưng ta ghi đè lại đúng action/method NGAY SAU ĐÓ nên không ảnh hưởng.
      toggleVariantMode(false);
      document.getElementById('variantToggleWrap').classList.add('d-none');

      // Set action/method ĐÚNG — phải nằm sau toggleVariantMode()
      document.getElementById('productForm').action = `${routeBase}/${id}`;
      document.getElementById('formMethod').value   = 'PUT';

      const codeInput = document.getElementById('pCode');
      codeInput.setAttribute('readonly', true);
      codeInput.classList.add('bg-body-secondary');

      document.getElementById('pCode').value     = @json(old('code', ''));
      document.getElementById('pName').value     = @json(old('name', ''));
      document.getElementById('pSpec').value     = @json(old('specification', ''));
      document.getElementById('pTracking').value = @json(old('tracking_type', 1));
      document.getElementById('pRotation').value = @json(old('stock_rotation', 1));
      document.getElementById(
        @json(old('status', '1')) == '1' ? 'pStatusActive' : 'pStatusInactive'
      ).checked = true;
      document.getElementById('pMinQty').value = @json(old('min_qty', 0));
      document.getElementById('pMaxQty').value = @json(old('max_qty', 0));
      document.getElementById('pLocation').value = @json(old('location_id', ''));
      const oldLoc2 = locations.find(l => l.id == @json(old('location_id', 'null')));
      document.getElementById('pLocationText').value = oldLoc2 ? `[${oldLoc2.code}] ${oldLoc2.name}` : '';

      setSelectValueSafe('pUom', @json(old('uom_id', '')));

      const categoryId = p ? (p.category_id ?? '') : @json(old('category_id', ''));
      lockCategoryForEdit(categoryId);

      if (p && p.image_url) {
        showImagePreview(p.image_url, p.image_name);
      }

    } else {
      // store thường fail
      document.getElementById('productOffcanvasTitle').textContent = 'Thêm vật tư';
      document.getElementById('productForm').action = routeStore;
      document.getElementById('formMethod').value   = 'POST';

      toggleVariantMode(false);
      unlockCategoryForCreate();

      document.getElementById('pCode').value      = @json(old('code', ''));
      document.getElementById('pName').value      = @json(old('name', ''));
      setSelectValueSafe('pCategory', @json(old('category_id', '')));
      setSelectValueSafe('pUom', @json(old('uom_id', '')));
      document.getElementById('pSpec').value      = @json(old('specification', ''));
      document.getElementById('pTracking').value  = @json(old('tracking_type', 1));
      document.getElementById('pRotation').value  = @json(old('stock_rotation', 1));
      document.getElementById(
        @json(old('status', '1')) == '1' ? 'pStatusActive' : 'pStatusInactive'
      ).checked = true;
      document.getElementById('pMinQty').value = @json(old('min_qty', 0));
      document.getElementById('pMaxQty').value = @json(old('max_qty', 0));

      document.getElementById('pLocation').value = @json(old('location_id', ''));
      const oldLoc3 = locations.find(l => l.id == @json(old('location_id', 'null')));
      document.getElementById('pLocationText').value = oldLoc3 ? `[${oldLoc3.code}] ${oldLoc3.name}` : '';
    }

    document.body.dataset.pfa = '';
    new coreui.OffCanvas(document.getElementById('productOffcanvas')).show();
  });

  function toggleVariantMode(isVariant) {
    const form = document.getElementById('productForm');
    form.action = isVariant ? routeStoreVariant : routeStore;

    document.getElementById('codeWrap').classList.toggle('d-none', isVariant);
    document.getElementById('variantCodeRow').classList.toggle('d-none', !isVariant);

    document.getElementById('nameNormalWrap').classList.toggle('d-none', isVariant);
    document.getElementById('nameVariantWrap').classList.toggle('d-none', !isVariant);

    // Disable input ẩn để không bị submit (đặc biệt quan trọng vì
    // pName và pNameVariant dùng chung name="name")
    document.getElementById('pCode').disabled        =  isVariant;
    document.getElementById('pName').disabled        =  isVariant;
    document.getElementById('pVariantCode').disabled = !isVariant;
    document.getElementById('pNameVariant').disabled = !isVariant;
    document.getElementById('pParentCode').disabled  = !isVariant;

    // document.getElementById('pName').required        = !isVariant;
    // document.getElementById('pNameVariant').required =  isVariant;

    const lock = ['pCategory', 'pUom', 'pTracking', 'pRotation'];
    lock.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.disabled = isVariant;
    });

    if (!isVariant) {
      document.getElementById('pParentCode').value  = '';
      document.getElementById('pVariantCode').value = '';
    }
  }

  // Khi blur khỏi ô Mã MenT gốc → fetch thông tin cha để điền sẵn
  async function fetchParentProduct() {
    const parentCodeEl = document.getElementById('pParentCode');
    const parentCode    = parentCodeEl.value.trim();

    parentCodeEl.classList.remove('is-invalid');
    document.getElementById('pParentCodeError').textContent = '';
    if (!parentCode) return;

    try {
      const res  = await fetch(`{{ route('master.product.find') }}?code=${parentCode}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json();

      if (!res.ok) {
        // parent_code.exists: mã gốc không tồn tại trong hệ thống
        parentCodeEl.classList.add('is-invalid');
        document.getElementById('pParentCodeError').textContent =
          data.error || 'Mã MenT gốc không tồn tại trong hệ thống.';
        return;
      }

      setSelectValueSafe('pCategory', data.category_id ?? '');
      setSelectValueSafe('pUom', data.uom_id ?? '');
      document.getElementById('pTracking').value      = data.tracking_type;
      document.getElementById('pRotation').value      = data.stock_rotation;
      document.getElementById('pNameVariant').value    = document.getElementById('pNameVariant').value || data.name;
      document.getElementById('pSpec').value           = data.specification;

      // Preview ảnh từ cha nếu có
      if (data.image_url) {
        showImagePreview(data.image_url);
      } else {
        resetImagePreview();
      }

    } catch {}
  }

  // ===== code.unique (AJAX, dùng lúc blur ở Mã MenT thường & Mã MenT biến thể) =====
  // Không check khi field đang readonly (đang Sửa vật tư -> mã bị khoá, luôn là
  // mã hiện tại của chính nó nên không thể trùng) hoặc khi để trống (hệ thống
  // sẽ tự sinh mã, không cần kiểm tra).
  async function checkProductCodeUnique(inputEl, errorId) {
    const code = inputEl.value.trim();

    inputEl.classList.remove('is-invalid');
    document.getElementById(errorId).textContent = '';
    if (!code || inputEl.readOnly) return;

    try {
      const res = await fetch(
        `{{ route('master.product.checkCode') }}?code=${encodeURIComponent(code)}`,
        { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
      );
      if (!res.ok) return; // lỗi mạng/server: để backend chặn thật lúc submit
      const data = await res.json();

      if (data.exists) {
        inputEl.classList.add('is-invalid');
        document.getElementById(errorId).textContent = 'Mã MenT đã tồn tại.';
      }
    } catch {}
  }

  // ===== min_qty <= max_qty (kiểm tra được ngay phía client, không cần AJAX) =====
  function validateMinMaxQty() {
    const minEl = document.getElementById('pMinQty');
    const maxEl = document.getElementById('pMaxQty');
    const min   = parseInt(minEl.value || '0', 10);
    const max   = parseInt(maxEl.value || '0', 10);

    minEl.classList.remove('is-invalid');
    maxEl.classList.remove('is-invalid');
    document.getElementById('pMinQtyError').textContent = '';
    document.getElementById('pMaxQtyError').textContent = '';

    // KHÔNG được coi max == 0 là "không giới hạn" rồi bỏ qua check: 2 field này
    // luôn có giá trị (mặc định "0", không để trống được), nên backend
    // ('max_qty' => '...|gte:min_qty') LUÔN so sánh min-max kể cả khi max = 0.
    // Ví dụ Min=100, Max=0 (chưa sửa Max) vẫn phải bị chặn ở đây, nếu không sẽ
    // lọt xuống server và bị redirect back (reload).
    if (min > max) {
      maxEl.classList.add('is-invalid');
      document.getElementById('pMaxQtyError').textContent =
        'Ngưỡng tối thiểu không được vượt quá ngưỡng tối đa.';
      return false;
    }
    return true;
  }

  // ===== CHẶN SUBMIT LIÊN TỤC =====
  document.getElementById('productForm').addEventListener('submit', function (e) {
    resolveLocation();

    // ===== VALIDATE THỦ CÔNG (form dùng novalidate để đồng bộ 1 kiểu message
    // tiếng Việt cho mọi field, khớp với rule thật trong StoreProductRequest /
    // StoreProductVariantRequest) =====
    const isVariant = document.getElementById('pIsVariant').checked;
    let firstInvalid = null;

    const markInvalid = function (el, errorId, msg) {
      el.classList.add('is-invalid');
      document.getElementById(errorId).textContent = msg;
      firstInvalid = firstInvalid || el;
    };
    const clearInvalid = function (el, errorId) {
      el.classList.remove('is-invalid');
      document.getElementById(errorId).textContent = '';
    };

    if (isVariant) {
      const parentCodeEl = document.getElementById('pParentCode');
      const nameEl        = document.getElementById('pNameVariant');

      clearInvalid(parentCodeEl, 'pParentCodeError');
      clearInvalid(nameEl, 'pNameVariantError');

      if (!parentCodeEl.value.trim()) {
        markInvalid(parentCodeEl, 'pParentCodeError', 'Vui lòng nhập mã MenT gốc.');
      }
      if (!nameEl.value.trim()) {
        markInvalid(nameEl, 'pNameVariantError', 'Vui lòng nhập tên biến thể.');
      }
    } else {
      const nameEl = document.getElementById('pName');
      const catEl  = document.getElementById('pCategory');
      const uomEl  = document.getElementById('pUom');

      clearInvalid(nameEl, 'pNameError');
      clearInvalid(catEl, 'pCategoryError');
      clearInvalid(uomEl, 'pUomError');

      if (!nameEl.value.trim()) {
        markInvalid(nameEl, 'pNameError', 'Vui lòng nhập tên vật tư.');
      }
      if (!catEl.disabled && !catEl.value) {
        markInvalid(catEl, 'pCategoryError', 'Vui lòng chọn danh mục vật tư.');
      }
      if (!uomEl.disabled && !uomEl.value) {
        markInvalid(uomEl, 'pUomError', 'Vui lòng chọn đơn vị tính.');
      }

      // Cảnh báo trước hết hạn: chỉ bắt buộc khi chọn FEFO (đọc lại thuộc tính
      // .required do listener 'change' của pRotation đã tự tính sẵn ở trên)
      const alertEl = document.getElementById('pAlertExpiry');
      clearInvalid(alertEl, 'pAlertExpiryError');
      if (alertEl.required && !alertEl.value) {
        markInvalid(alertEl, 'pAlertExpiryError', 'Vui lòng nhập số ngày cảnh báo trước hết hạn khi dùng FEFO.');
      }
    }

    if (firstInvalid) {
      e.preventDefault();
      firstInvalid.focus();
      return;
    }

    // min_qty <= max_qty: áp dụng cho cả 2 chế độ (StoreProductRequest và
    // StoreProductVariantRequest đều có rule 'max_qty' => '...|gte:min_qty')
    if (!validateMinMaxQty()) {
      e.preventDefault();
      document.getElementById('pMaxQty').focus();
      return;
    }

    // Nếu mã MenT / mã MenT gốc đang bị đánh dấu lỗi từ lần kiểm tra AJAX lúc
    // blur trước đó (trùng mã / mã gốc không tồn tại) -> chặn, không cho submit
    // trong lúc chưa sửa lại. Đây chỉ là lớp UX phụ — FormRequest phía backend
    // vẫn là lớp chặn thật cuối cùng (vd: mã vừa bị người khác tạo trùng ngay
    // trước khi submit thì AJAX blur không kịp bắt, backend vẫn sẽ chặn).
    const codeEl        = isVariant ? document.getElementById('pVariantCode') : document.getElementById('pCode');
    const parentCodeEl2 = document.getElementById('pParentCode');
    if (codeEl.classList.contains('is-invalid') || (isVariant && parentCodeEl2.classList.contains('is-invalid'))) {
      e.preventDefault();
      (codeEl.classList.contains('is-invalid') ? codeEl : parentCodeEl2).focus();
      return;
    }

    // Nếu người dùng đã nhập text nhưng không khớp vị trí nào -> chặn submit
    const locationText = document.getElementById('pLocationText').value.trim();
    const locationId    = document.getElementById('pLocation').value;
    if (locationText && !locationId) {
      document.getElementById('pLocationText').classList.add('is-invalid');
      document.getElementById('pLocationError').textContent = 'Vị trí không tồn tại trong hệ thống.';
      document.getElementById('pLocationText').focus();
      e.preventDefault();
      return;
    }

    const btn     = document.getElementById('productSubmitBtn');
    const spinner = document.getElementById('productSubmitSpinner');
    const icon    = document.getElementById('productSubmitIcon');
    const label   = document.getElementById('productSubmitLabel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    label.textContent = 'Đang lưu...';
  });

  // Reset lại nút khi đóng offcanvas (để lần mở sau vẫn hoạt động bình thường)
  document.getElementById('productOffcanvas').addEventListener('hidden.coreui.offcanvas', function () {
    const btn     = document.getElementById('productSubmitBtn');
    const spinner = document.getElementById('productSubmitSpinner');
    const icon    = document.getElementById('productSubmitIcon');
    const label   = document.getElementById('productSubmitLabel');

    btn.disabled = false;
    spinner.classList.add('d-none');
    icon.classList.remove('d-none');
    label.textContent = 'Lưu';
  });

  document.querySelectorAll('[data-preview]').forEach(function(thumb) {
    thumb.addEventListener('mouseenter', function(e) {
      const popup = document.getElementById('imgPreviewPopup');
      document.getElementById('imgPreviewPopupImg').src = this.dataset.preview;
      popup.style.display = 'block';
    });
    thumb.addEventListener('mousemove', function(e) {
      const popup = document.getElementById('imgPreviewPopup');
      popup.style.left = (e.clientX + 16) + 'px';
      popup.style.top  = (e.clientY - 110) + 'px';
    });
    thumb.addEventListener('mouseleave', function() {
      document.getElementById('imgPreviewPopup').style.display = 'none';
    });
  });

  function resolveLocation() {
    const text  = document.getElementById('pLocationText').value.trim();
    const el    = document.getElementById('pLocationText');
    const hid   = document.getElementById('pLocation');
    const err   = document.getElementById('pLocationError');
    const match = locations.find(l => `[${l.code}] ${l.name}` === text);

    if (match) {
      hid.value = match.id;
      el.classList.remove('is-invalid');
      err.textContent = '';
    } else {
      hid.value = '';
      if (text) {
        el.classList.add('is-invalid');
        err.textContent = 'Vị trí không tồn tại trong hệ thống.';
      } else {
        el.classList.remove('is-invalid');
        err.textContent = '';
      }
    }
  }
</script>

  @vite('resources/js/product/product-index.js')
@endpush
