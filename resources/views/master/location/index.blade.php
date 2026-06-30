@extends('layouts.app')

@section('title', 'Vị trí kho')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Vị trí kho</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" onclick="openModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm vị trí
    </button>
  </div>

      <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
          <span class="fw-semibold flex-shrink-0">Vị trí kho</span>
          <form method="GET" action="{{ route('master.location.index') }}"
                class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">

            <div class="input-group" style="min-width:220px;flex:2">
              <span class="input-group-text">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
              </span>
              <input type="text" class="form-control" name="search"
                     value="{{ request('search') }}" placeholder="Tìm theo mã hoặc tên vị trí kho">
            </div>
            <select class="form-select" name="status" style="min-width:130px;flex:1" onchange="this.form.submit()">
              <option value="">Trạng thái</option>
              @foreach (\App\Enums\ActiveStatus::options() as $val => $label)
                <option value="{{ $val }}" {{ request('status') === (string) $val ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>

            @php
              $hasFilter = request('search')
                        || (request('status') !== null && request('status') !== '');
            @endphp
            @if ($hasFilter)
              <a href="{{ route('master.location.index') }}" class="btn btn-outline-secondary">
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
                  <th style="width:8%">Mã</th>
                  <th>Tên</th>
                  <th style="width:15%">Vị trí cha</th>
                  <th style="width:20%">Ghi chú</th>
                  <th class="text-center" style="width:8%">Trạng thái</th>
                  <th class="text-center" style="width:8%">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($locations as $index => $loc)
                  <tr>
                    <td class="text-center text-body-secondary">
                      {{ ($locations->currentPage() - 1) * $locations->perPage() + $index + 1 }}
                    </td>
                    <td>
                      <code class="text-primary fw-medium">{{ $loc->code }}</code>
                    </td>
                    <td class="fw-medium">{{ $loc->name }}</td>
                    <td>
                      @if ($loc->parent)
                        <div class="fw-medium">{{ $loc->parent->name }}</div>
                        <div class="small text-body-secondary font-monospace">{{ $loc->parent->code }}</div>
                      @else
                        <span class="text-body-secondary small">Gốc</span>
                      @endif
                    </td>
                    <td class="small">{{ $loc->note ?? '-' }}</td>
                    <td class="text-center">
                      @if ($loc->status === \App\Enums\ActiveStatus::Active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                      @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                      @endif
                    </td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-primary me-1"
                              onclick="openModal(
                                {{ $loc->id }},
                                {{ $loc->parent_id ?? 'null' }},
                                {{ $loc->warehouse_id ?? 'null' }},
                                '{{ addslashes($loc->code) }}',
                                '{{ addslashes($loc->name) }}',
                                {{ $loc->type->value }},
                                {{ $loc->status->value }},
                                '{{ addslashes($loc->note ?? '') }}'
                              )"
                              title="Chỉnh sửa">
                        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                      </button>
                      <button class="btn btn-sm btn-outline-danger"
                              onclick="confirmDelete({{ $loc->id }}, '{{ addslashes($loc->name) }}')"
                              title="Xóa">
                        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-body-secondary py-5">
                      <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-map') }}"></use>
                      </svg>
                      Chưa có vị trí nào
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center py-2">
          <small class="text-body-secondary">
            Hiển thị <strong>{{ $locations->firstItem() }}</strong>–<strong>{{ $locations->lastItem() }}</strong>
            trong tổng số <strong>{{ $locations->total() }}</strong> vị trí
          </small>
          {{ $locations->appends(request()->query())->links('pagination::bootstrap-5') }}
          <style>.card-footer .pagination { margin-bottom: 0; }</style>
        </div>
      </div>

  {{-- ===== MODAL TẠO / SỬA ===== --}}
  <div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="locationForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="formMethod" value="POST">

          <div class="modal-header">
            <h5 class="modal-title" id="locationModalLabel">Thêm vị trí kho</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            {{-- Vị trí cha --}}
          <div class="mb-3">
            <label class="form-label fw-medium">Vị trí cha <span class="text-danger">*</span></label>
            <select class="form-select" id="lParentId" name="parent_id">
              @foreach ($parentOptions as $p)
                <option value="{{ $p->id }}" {{ $p->id === $defaultParentId ? 'selected' : '' }}>
                  {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $p->depth ?? 0) !!}{{ ($p->depth ?? 0) > 0 ? '└─ ' : '' }}{{ $p->code }} - {{ $p->name }}
                </option>
              @endforeach
            </select>
          </div>

            {{-- Mã + Tên --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Mã</label>
              <input type="text"
                    class="form-control text-uppercase {{ $errors->has('code') ? 'is-invalid' : '' }}"
                    id="lCode" name="code"
                    value="{{ old('code') }}"
                    placeholder="Tự động" maxlength="50" style="letter-spacing:1px">
              @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Tên <span class="text-danger">*</span></label>
              <input type="text"
                    class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    id="lName" name="name"
                    value="{{ old('name') }}"
                    placeholder="Tên đầy đủ" required maxlength="100">
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Ghi chú --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Ghi chú</label>
              <textarea class="form-control" id="lNote" name="note"
                        rows="2" maxlength="500">{{ old('note') }}</textarea>
            </div>

            {{-- Trạng thái --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Trạng thái</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="lStatusActive" value="1" checked>
                  <label class="form-check-label text-success" for="lStatusActive">Hoạt động</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="lStatusInactive" value="0">
                  <label class="form-check-label text-secondary" for="lStatusInactive">Ngừng hoạt động</label>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" id="locSubmitBtn" class="btn btn-primary">
              <span id="locSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
              <svg id="locSubmitIcon" class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              <span id="locSubmitLabel">Lưu</span>
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
            Bạn có chắc muốn xóa vị trí<br>
            <strong id="deleteLocName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Không thể xóa nếu có vị trí con hoặc đang có tồn kho.</p>
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

  <input type="hidden" name="warehouse_id" id="lWarehouseId" value="{{ $warehouses->first()?->id }}">
  <input type="hidden" name="type" id="lType" value="1">

@endsection

@push('scripts')
<script>
  const routeStore = '{{ route('master.location.store') }}';
  const routeBase  = '{{ url('master/location') }}';

  const typeHints = {
    1: 'Vị trí thực - có thể chứa hàng hóa thực tế.',
    2: 'Vị trí ảo - dùng làm điểm neo cho kho, không chứa hàng trực tiếp.',
  };

  function openModal(id = null, parentId = null, warehouseId = null, code = '', name = '', type = 1, status = 1, note = '') {
    const modal  = new coreui.Modal(document.getElementById('locationModal'));
    const form   = document.getElementById('locationForm');
    const title  = document.getElementById('locationModalLabel');
    const method = document.getElementById('formMethod');
    const codeEl = document.getElementById('lCode');

    document.getElementById('lParentId').value    = parentId    ?? '';
    document.getElementById('lWarehouseId').value = warehouseId ?? '';
    document.getElementById('lName').value        = name;
    document.getElementById('lType').value        = type;
    document.getElementById('lNote').value        = note;
    document.getElementById(status == 1 ? 'lStatusActive' : 'lStatusInactive').checked = true;

    if (id) {
      title.textContent  = 'Chỉnh sửa vị trí kho';
      form.action        = `${routeBase}/${id}`;
      method.value       = 'PUT';
      codeEl.value       = code;
      codeEl.readOnly    = true;
      codeEl.classList.add('bg-body-secondary');
    } else {
      title.textContent  = 'Thêm vị trí kho';
      form.action        = routeStore;
      method.value       = 'POST';
      form.reset();
      codeEl.value       = '';
      codeEl.readOnly    = false;
      codeEl.classList.remove('bg-body-secondary');
      document.getElementById('lStatusActive').checked = true;
    }

    modal.show();
    setTimeout(() => (id ? document.getElementById('lName') : codeEl).focus(), 300);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteLocName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // Auto viết hoa mã
  document.getElementById('lCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  // ===== CHẶN SUBMIT LIÊN TỤC =====
  document.getElementById('locationForm').addEventListener('submit', function () {
    const btn     = document.getElementById('locSubmitBtn');
    const spinner = document.getElementById('locSubmitSpinner');
    const icon    = document.getElementById('locSubmitIcon');
    const label   = document.getElementById('locSubmitLabel');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    label.textContent = 'Đang lưu...';
  });

  document.getElementById('locationModal').addEventListener('hidden.coreui.modal', function () {
    const btn     = document.getElementById('locSubmitBtn');
    const spinner = document.getElementById('locSubmitSpinner');
    const icon    = document.getElementById('locSubmitIcon');
    const label   = document.getElementById('locSubmitLabel');

    btn.disabled = false;
    spinner.classList.add('d-none');
    icon.classList.remove('d-none');
    label.textContent = 'Lưu';
  });

  @if ($errors->any())
    openModal(
      null, null, null,
      '{{ old('code') }}',
      '{{ addslashes(old('name')) }}',
      {{ old('type', 1) }},
      {{ old('status', 1) }},
      '{{ addslashes(old('note')) }}'
    );
  @endif

  // ===== TREE HELPERS =====
  function treeExpandAll() {
    document.querySelectorAll('#locationTreeRoot .collapse').forEach(el => {
      coreui.Collapse.getOrCreateInstance(el, { toggle: false }).show();
    });
    document.querySelectorAll('#locationTreeRoot .tree-toggle-btn').forEach(btn => {
      btn.classList.remove('collapsed');
      btn.setAttribute('aria-expanded', 'true');
    });
  }

  function treeCollapseAll() {
    document.querySelectorAll('#locationTreeRoot .collapse').forEach(el => {
      coreui.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
    });
    document.querySelectorAll('#locationTreeRoot .tree-toggle-btn').forEach(btn => {
      btn.classList.add('collapsed');
      btn.setAttribute('aria-expanded', 'false');
    });
  }
</script>
@endpush