@extends('layouts.app')

@section('title', 'Đơn vị tính')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Đơn vị tính</li>
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
      <span class="fw-semibold flex-shrink-0">Đơn vị tính</span>
      <form method="GET" action="{{ route('master.uom.index') }}"
            class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">
        <div class="input-group" style="min-width:260px;flex:2">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tìm theo mã hoặc tên đơn vị tính">
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
          <a href="{{ route('master.uom.index') }}" class="btn btn-outline-secondary">
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
              <th style="width:10%">
                <a href="{{ $sortUrl('code') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Mã {!! $sortIcon('code') !!}
                </a>
              </th>
              <th>
                <a href="{{ $sortUrl('name') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Tên {!! $sortIcon('name') !!}
                </a>
              </th>
              <th class="text-center" style="width:10%">Trạng thái</th>
              <th class="text-center" style="width:8%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($uoms as $index => $uom)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($uoms->currentPage() - 1) * $uoms->perPage() + $index + 1 }}
                </td>
                <td>
                  <code class="text-primary fw-medium">{{ $uom->code ?? '-' }}</code>
                </td>
                <td class="fw-medium">{{ $uom->name ?? '-' }}</td>
                <td class="text-center">
                  @if ($uom->status === \App\Enums\ActiveStatus::Active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openModal(
                            {{ $uom->id }},
                            '{{ addslashes($uom->code) }}',
                            '{{ addslashes($uom->name) }}',
                            {{ $uom->status->value }}
                          )"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $uom->id }}, '{{ addslashes($uom->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                  </svg>
                  Chưa có đơn vị tính nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
        Hiển thị <strong>{{ $uoms->firstItem() }}</strong>-<strong>{{ $uoms->lastItem() }}</strong>
        trong tổng số <strong>{{ $uoms->total() }}</strong> đơn vị tính
      </small>
      {{ $uoms->appends(request()->query())->links('pagination::bootstrap-5') }}
      <style>.card-footer .pagination { margin-bottom: 0; }</style>
    </div>
  </div>

  {{-- ===== MODAL TẠO / SỬA ===== --}}
  <div class="modal fade" id="uomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="uomForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="formMethod" value="POST">

          <div class="modal-header">
            <h5 class="modal-title" id="uomModalLabel">Thêm đơn vị tính</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-medium">Mã</label>
              <input type="text"
                     class="form-control text-uppercase {{ $errors->has('code') ? 'is-invalid' : '' }}"
                     id="uomCode" name="code"
                     value="{{ old('code') }}"
                     placeholder="Tự động" maxlength="50" style="letter-spacing:1px">
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
                     id="uomName" name="name"
                     value="{{ old('name') }}"
                     placeholder="Tên đầy đủ" required maxlength="200">
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div>
              <label class="form-label fw-medium">Trạng thái</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="uomStatusActive" value="1" checked>
                  <label class="form-check-label text-success" for="uomStatusActive">Hoạt động</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="uomStatusInactive" value="0">
                  <label class="form-check-label text-secondary" for="uomStatusInactive">Ngừng hoạt động</label>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" id="uomSubmitBtn" class="btn btn-primary">
              <span id="uomSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
              <svg id="uomSubmitIcon" class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              <span id="uomSubmitLabel">Lưu</span>
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
            Bạn có chắc muốn xóa đơn vị tính<br>
            <strong id="deleteUomName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Không thể xóa nếu đã gán cho vật tư.</p>
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
  const routeStore = '{{ route('master.uom.store') }}';
  const routeBase  = '{{ url('master/uom') }}';

  function openModal(id = null, code = '', name = '', status = 1) {
    const modal   = new coreui.Modal(document.getElementById('uomModal'));
    const form    = document.getElementById('uomForm');
    const title   = document.getElementById('uomModalLabel');
    const method  = document.getElementById('formMethod');
    const codeEl  = document.getElementById('uomCode');

    document.getElementById('uomName').value = name;
    document.getElementById(status == 1 ? 'uomStatusActive' : 'uomStatusInactive').checked = true;

    if (id) {
      title.textContent = 'Chỉnh sửa đơn vị tính';
      form.action       = `${routeBase}/${id}`;
      method.value      = 'PUT';
      codeEl.value      = code;
      codeEl.readOnly   = true;
      codeEl.classList.add('bg-body-secondary');
    } else {
      title.textContent = 'Thêm đơn vị tính';
      form.action       = routeStore;
      method.value      = 'POST';
      form.reset();
      codeEl.value      = '';
      codeEl.readOnly   = false;
      codeEl.classList.remove('bg-body-secondary');
      document.getElementById('uomStatusActive').checked = true;
    }

    modal.show();
    setTimeout(() => (id ? document.getElementById('uomName') : codeEl).focus(), 300);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteUomName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  document.getElementById('uomCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  @if ($errors->any())
    openModal(
      null,
      '{{ old("code") }}',
      '{{ addslashes(old("name")) }}',
      {{ old("status", 1) }}
    );
  @endif

  // ===== CHẶN SUBMIT LIÊN TỤC =====
  document.getElementById('uomForm').addEventListener('submit', function () {
    const btn     = document.getElementById('uomSubmitBtn');
    const spinner = document.getElementById('uomSubmitSpinner');
    const icon    = document.getElementById('uomSubmitIcon');
    const label   = document.getElementById('uomSubmitLabel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    label.textContent = 'Đang lưu...';
  });

  // Reset khi đóng modal (để lần mở sau vẫn hoạt động)
  document.getElementById('uomModal').addEventListener('hidden.coreui.modal', function () {
    const btn     = document.getElementById('uomSubmitBtn');
    const spinner = document.getElementById('uomSubmitSpinner');
    const icon    = document.getElementById('uomSubmitIcon');
    const label   = document.getElementById('uomSubmitLabel');

    btn.disabled = false;
    spinner.classList.add('d-none');
    icon.classList.remove('d-none');
    label.textContent = 'Lưu';
  });
</script>
@endpush