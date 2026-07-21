@extends('layouts.app')

@section('title', 'Danh mục vật tư')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item">Vật tư</li>
  <li class="breadcrumb-item active">Danh mục vật tư</li>
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
    <button class="btn btn-primary" onclick="clearValidationErrors('categoryForm'); openModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm mới
    </button>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="fw-semibold flex-shrink-0">Danh mục vật tư</span>
      <form method="GET" action="{{ route('master.category.index') }}"
            class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">
        <div class="input-group" style="min-width:260px;flex:2">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tìm theo mã hoặc tên danh mục">
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
          <a href="{{ route('master.category.index') }}" class="btn btn-outline-secondary">
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
                  Mã {!! $sortIcon('code') !!}
                </a>
              </th>
              <th>
                <a href="{{ $sortUrl('name') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Tên {!! $sortIcon('name') !!}
                </a>
              </th>
              <th style="width:16%">Vị trí gợi ý</th>
              <th style="width:24%">Ghi chú</th>
              <th class="text-center" style="width:8%">Trạng thái</th>
              <th class="text-center" style="width:8%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($categories as $index => $cat)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($categories->currentPage() - 1) * $categories->perPage() + $index + 1 }}
                </td>
                <td>
                  <code class="text-primary fw-medium">{{ $cat->code ?? '-'}}</code>
                </td>
                <td class="fw-medium">
                  @if ($cat->parent)
                    <span class="text-body-secondary me-1" style="font-size:11px">
                      <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-level-down') }}"></use></svg>
                    </span>
                  @endif
                  {{ $cat->name ?? '-' }}
                </td>
                <td class="small">
                  @if ($cat->putawayRule && $cat->putawayRule->destinationLocation)
                    <code class="text-body-secondary">[{{ $cat->putawayRule->destinationLocation->code }}]</code>
                    {{ $cat->putawayRule->destinationLocation->name }}
                  @else
                    <span class="text-body-secondary">-</span>
                  @endif
                </td>
                <td class="small" title="{{ $cat->note }}">
                 {{ truncate_text($cat->note) }}
                </td>
                <td class="text-center">
                  @if ($cat->status === \App\Enums\ActiveStatus::Active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                    onclick="clearValidationErrors('categoryForm'); openModal(
                      {{ $cat->id }},
                      '{{ addslashes($cat->code) }}',
                      '{{ addslashes($cat->name) }}',
                      '{{ addslashes($cat->note ?? '') }}',
                      {{ $cat->status->value }},
                      {{ $cat->putawayRule->location_id ?? 'null' }},
                      '{{ $cat->putawayRule && $cat->putawayRule->destinationLocation ? "[" . addslashes($cat->putawayRule->destinationLocation->code) . "] " . addslashes($cat->putawayRule->destinationLocation->name) : "" }}'
                    )"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                  </svg>
                  Chưa có danh mục nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

      <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-body-secondary">
          Hiển thị <strong>{{ $categories->firstItem() }}</strong>-<strong>{{ $categories->lastItem() }}</strong>
          trong tổng số <strong>{{ $categories->total() }}</strong> danh mục
        </small>
        {{ $categories->appends(request()->query())->links('pagination::bootstrap-5') }}
        <style>.card-footer .pagination { margin-bottom: 0; }</style>
      </div>
  </div>

  {{-- ===== MODAL TẠO / SỬA ===== --}}
  <div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="categoryForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="formMethod" value="POST">
          <input type="hidden" name="id" id="catFormId" value="{{ old('id') }}">

          <div class="modal-header">
            <h5 class="modal-title" id="categoryModalLabel">Thêm danh mục</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-medium">
                Mã
              </label>
              <input type="text"
                    class="form-control text-uppercase {{ $errors->has('code') ? 'is-invalid' : '' }}"
                    id="catCode" name="code"
                    value="{{ old('code') }}"
                    placeholder="Tự động" maxlength="20" style="letter-spacing:1px">
              @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">
                Tên <span class="text-danger">*</span>
              </label>
              <input type="text"
                    class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    id="catName" name="name"
                    value="{{ old('name') }}"
                    placeholder="Nhập tên" required maxlength="200"
                    oninput="this.classList.remove('is-invalid')">
              <div class="invalid-feedback" id="catNameError">@error('name'){{ $message }}@enderror</div>
            </div>

            {{-- ===== Gợi ý vị trí (PutawayRule theo Danh mục) ===== --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Gợi ý vị trí</label>
              <input type="text"
                    class="form-control {{ $errors->has('location_id') ? 'is-invalid' : '' }}"
                    id="catLocationText"
                    placeholder="Nhập hoặc chọn"
                    list="catLocationDatalist" autocomplete="off"
                    oninput="resolveCategoryLocation()" onblur="resolveCategoryLocation()">
              <datalist id="catLocationDatalist">
                @foreach ($locations as $loc)
                  <option value="[{{ $loc->code }}] {{ $loc->name }}"></option>
                @endforeach
              </datalist>
              <input type="hidden" id="catLocation" name="location_id" value="{{ old('location_id') }}">
              <div class="invalid-feedback" id="catLocationError">@error('location_id'){{ $message }}@enderror</div>
              <div class="form-text">Vị trí đích gợi ý áp dụng cho toàn bộ vật tư thuộc danh mục này (nếu vật tư chưa gán riêng).</div>
            </div>

            <div class="mb-3 mt-3">
              <label class="form-label fw-medium">Ghi chú</label>
              <textarea class="form-control" id="catDesc" name="note"
                        rows="2" maxlength="500">{{ old('note') }}</textarea>
            </div>

            <div>
              <label class="form-label fw-medium">Trạng thái</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="catStatusActive" value="1" checked>
                  <label class="form-check-label text-success" for="catStatusActive">Hoạt động</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="catStatusInactive" value="0">
                  <label class="form-check-label text-secondary" for="catStatusInactive">Ngưng hoạt động</label>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" id="catSubmitBtn" class="btn btn-primary">
              <span id="catSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
              <svg id="catSubmitIcon" class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              <span id="catSubmitLabel">Lưu</span>
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
            Bạn có chắc muốn xóa danh mục<br>
            <strong id="deleteCatName" class="text-body"></strong>?
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
  const routeStore = '{{ route('master.category.store') }}';
  const routeBase  = '{{ url('master/category') }}';

  // Danh sách vị trí Internal active — dùng để đối chiếu text nhập ở ô "Gợi ý vị trí"
  const catLocations = @json($locations->map(fn($l) => ['id' => $l->id, 'code' => $l->code, 'name' => $l->name]));

  function openModal(id = null, code = '', name = '', desc = '', status = 1, locationId = null, locationText = '') {
      const modal   = new coreui.Modal(document.getElementById('categoryModal'));
      const form    = document.getElementById('categoryForm');
      const title   = document.getElementById('categoryModalLabel');
      const method  = document.getElementById('formMethod');
      const codeEl  = document.getElementById('catCode');

      setModalFormId('catFormId', id);
      clearValidationErrors('categoryForm');
      document.getElementById('catName').value = name;
      document.getElementById('catDesc').value = desc;
      document.getElementById(status == 1 ? 'catStatusActive' : 'catStatusInactive').checked = true;

      // Gợi ý vị trí (PutawayRule theo danh mục)
      document.getElementById('catLocationText').value = locationText ?? '';
      document.getElementById('catLocation').value      = locationId ?? '';

      if (id) {
          title.textContent    = 'Chỉnh sửa danh mục';
          form.action          = `${routeBase}/${id}`;
          method.value         = 'PUT';
          codeEl.value         = code;
          codeEl.readOnly      = true;
          codeEl.classList.add('bg-body-secondary');
      } else {
          title.textContent    = 'Thêm danh mục';
          form.action          = routeStore;
          method.value         = 'POST';
          form.reset();
          codeEl.value         = '';
          codeEl.readOnly      = false;
          codeEl.classList.remove('bg-body-secondary');
          document.getElementById('catStatusActive').checked = true;
          // form.reset() xoá luôn 2 ô vị trí — set lại rõ ràng để tránh sót giá trị cũ
          document.getElementById('catLocationText').value = '';
          document.getElementById('catLocation').value      = '';
      }

      modal.show();
      setTimeout(() => (id ? document.getElementById('catName') : codeEl).focus(), 300);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteCatName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // Đối chiếu text nhập ở ô "Gợi ý vị trí" với danh sách vị trí Internal active,
  // set catLocation (hidden) = id nếu khớp, báo lỗi is-invalid nếu gõ text không khớp
  // option nào trong datalist (giống hệt resolveLocation() ở form Sản phẩm).
  function resolveCategoryLocation() {
    const text  = document.getElementById('catLocationText').value.trim();
    const el    = document.getElementById('catLocationText');
    const hid   = document.getElementById('catLocation');
    const err   = document.getElementById('catLocationError');
    const match = catLocations.find(l => `[${l.code}] ${l.name}` === text);

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

  document.getElementById('catCode').addEventListener('input', function () {
    sanitizeCodeInput(this);
  });

  @if ($errors->any())
    // Bọc trong DOMContentLoaded vì openModal() gọi setModalFormId(), hàm này được
    // định nghĩa trong resources/js/crud-modal-helpers.js và build qua Vite dưới dạng
    // <script type="module">. Module luôn thực thi SAU khi parse xong toàn bộ HTML
    // (tương đương defer), nghĩa là nếu gọi openModal() ngay lập tức tại đây (script
    // thường, chạy đồng bộ ngay khi parse tới), window.setModalFormId có thể CHƯA tồn
    // tại → "setModalFormId is not defined". DOMContentLoaded đảm bảo module đã chạy xong.
    document.addEventListener('DOMContentLoaded', function () {
      const oldLocationId = @json(old('location_id', null));
      const oldLocationMatch = catLocations.find(l => l.id == oldLocationId);
      openModal(
        {{ old('id') ?: 'null' }},
        '{{ old("code") }}',
        '{{ addslashes(old("name")) }}',
        '{{ addslashes(old("note")) }}',
        {{ old("status", 1) }},
        oldLocationId,
        oldLocationMatch ? `[${oldLocationMatch.code}] ${oldLocationMatch.name}` : ''
      );
    });
  @endif

  document.getElementById('categoryForm').addEventListener('submit', function (e) {
    const nameEl = document.getElementById('catName');
    const name   = nameEl.value.trim();

    nameEl.classList.remove('is-invalid');

    if (!name) {
      nameEl.classList.add('is-invalid');
      document.getElementById('catNameError').textContent = 'Vui lòng nhập tên danh mục.';
      e.preventDefault();
      nameEl.focus();
      return;
    }

    // Nếu người dùng gõ text vào ô vị trí nhưng không khớp option nào (hidden id rỗng),
    // chặn submit — giống validate resolveLocation() ở form Sản phẩm.
    const locationText = document.getElementById('catLocationText').value.trim();
    const locationId    = document.getElementById('catLocation').value;
    if (locationText && !locationId) {
      document.getElementById('catLocationText').classList.add('is-invalid');
      document.getElementById('catLocationError').textContent = 'Vị trí không tồn tại trong hệ thống.';
      e.preventDefault();
      document.getElementById('catLocationText').focus();
      return;
    }

    const btn     = document.getElementById('catSubmitBtn');
    const spinner = document.getElementById('catSubmitSpinner');
    const icon    = document.getElementById('catSubmitIcon');
    const label   = document.getElementById('catSubmitLabel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    label.textContent = 'Đang lưu...';
  });

  document.getElementById('categoryModal').addEventListener('hidden.coreui.modal', function () {
    const btn     = document.getElementById('catSubmitBtn');
    const spinner = document.getElementById('catSubmitSpinner');
    const icon    = document.getElementById('catSubmitIcon');
    const label   = document.getElementById('catSubmitLabel');

    btn.disabled = false;
    spinner.classList.add('d-none');
    icon.classList.remove('d-none');
    label.textContent = 'Lưu';
  });
</script>
@endpush
