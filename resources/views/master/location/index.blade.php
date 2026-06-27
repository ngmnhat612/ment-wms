@extends('layouts.app')

@section('title', 'Vị trí kho — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Vị trí kho</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Vị trí kho</h4>
      <small class="text-body-secondary">Quản lý vị trí lưu trữ hàng hóa (phân cấp, hỗ trợ vị trí ảo)</small>
    </div>
    <button class="btn btn-primary" onclick="openForm()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm vị trí
    </button>
  </div>

  {{-- CARDS THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-map') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng vị trí</div>
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
      <div class="card border-start border-start-4 border-start-info">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-info"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $internalCount }}</div>
            <div class="text-body-secondary small">Vị trí thực (Internal)</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- VIEW TOGGLE --}}
  <ul class="nav nav-tabs mb-3" id="locationViewTabs" data-coreui-toggle="tabs">
    <li class="nav-item">
      <a class="nav-link active" href="#tabTable" data-coreui-toggle="tab">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
        Danh sách
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#tabTree" data-coreui-toggle="tab">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-sitemap') }}"></use></svg>
        Sơ đồ cây
      </a>
    </li>
  </ul>
 
  <div class="tab-content">
    <div class="tab-pane fade show active" id="tabTable">
      {{-- BẢNG DANH SÁCH --}}
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="fw-semibold">Danh sách vị trí kho</span>
          <form method="GET" action="{{ route('master.location.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="input-group" style="width:220px">
              <span class="input-group-text">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
              </span>
              <input type="text" class="form-control" name="search"
                    value="{{ request('search') }}" placeholder="Mã, tên,...">
            </div>
              <select class="form-select" name="type" style="width:160px">
                <option value="">Tất cả loại</option>
                @foreach (\App\Enums\LocationType::cases() as $type)
                  <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>
                    {{ $type->label() }}
                  </option>
                @endforeach
              </select>
            <select class="form-select" name="status" style="width:130px">
              <option value="">Tất cả</option>
              <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
              <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
            </select>
            <button type="submit" class="btn btn-outline-primary">Lọc</button>
            @if(request('search') || request('type') || (request('status') !== null && request('status') !== ''))
              <a href="{{ route('master.location.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
            @endif
          </form>
        </div>

        {{-- Ghi chú loại vị trí --}}
        <div class="card-header border-top-0 py-2 bg-body-tertiary">
          <div class="d-flex flex-wrap gap-2 small">
            <span class="text-body-secondary me-1">Loại vị trí:</span>
            @foreach (\App\Models\Location::typeLabels() as $val => $label)
              @php $color = \App\Models\Location::typeColors()[$val]; @endphp
              <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle">
                {{ $label }}
              </span>
            @endforeach
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width:55px">#</th>
                  <th style="width:130px">Mã vị trí</th>
                  <th>Tên vị trí</th>
                  <th style="width:150px">Vị trí cha</th>
                  <th class="text-center" style="width:140px">Loại</th>
                  <th class="text-center" style="width:120px">Trạng thái</th>
                  <th class="text-center" style="width:110px">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($locations as $index => $loc)
                  <tr class="{{ $loc->isVirtual() ? 'table-light' : '' }}">
                    <td class="text-center text-body-secondary">
                      {{ ($locations->currentPage() - 1) * $locations->perPage() + $index + 1 }}
                    </td>
                    <td>
                      <code class="fw-medium text-{{ $loc->type_color }}">{{ $loc->code }}</code>
                    </td>
                    <td>
                      @if ($loc->parent)
                        <span class="text-body-secondary me-1" style="font-size:11px">
                          <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-level-down') }}"></use></svg>
                        </span>
                      @endif
                      <span class="fw-medium">{{ $loc->name }}</span>
                      @if ($loc->hasChildren())
                        <span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:10px">Có vị trí con</span>
                      @endif
                      @if ($loc->isVirtual())
                        <span class="badge bg-body-secondary text-body-secondary border ms-1" style="font-size:10px">Hệ thống</span>
                      @endif
                    </td>
                    <td class="small">
                      @if ($loc->parent)
                        <span class="badge bg-body-secondary text-body border">{{ $loc->parent->code }}</span>
                      @else
                        <span class="text-body-secondary">— Gốc</span>
                      @endif
                    </td>
                    <td class="text-center">
                      <span class="badge bg-{{ $loc->type_color }}-subtle text-{{ $loc->type_color }} border border-{{ $loc->type_color }}-subtle">
                        {{ $loc->type_label }}
                      </span>
                    </td>
                    <td class="text-center">
                      @if ($loc->status == 1)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                      @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                      @endif
                    </td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-primary me-1"
                              onclick="openForm({{ $loc->id }})"
                              title="Chỉnh sửa">
                        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                      </button>
                      @if (!$loc->isVirtual() && !in_array($loc->code, ['WH']))
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete({{ $loc->id }}, '{{ addslashes($loc->name) }}')"
                                title="Xóa">
                          <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                        </button>
                      @else
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Vị trí hệ thống">
                          <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
                        </button>
                      @endif
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

        @if ($locations->hasPages())
          <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-body-secondary">
              Hiển thị {{ $locations->firstItem() }}–{{ $locations->lastItem() }}
              trong tổng số {{ $locations->total() }} vị trí
            </small>
            {{ $locations->appends(request()->query())->links('pagination::bootstrap-5') }}
          </div>
        @endif
      </div>
    </div>
    <div class="tab-pane fade" id="tabTree">
      @include('master.location.partials.tree')
    </div>
  </div>

  {{-- OFFCANVAS FORM --}}
  <div class="offcanvas offcanvas-end" style="width:460px" tabindex="-1" id="locationOffcanvas">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="locationOffcanvasTitle">Thêm vị trí kho</h5>
      <button type="button" class="btn-close" data-coreui-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="locationForm" method="POST">
        @csrf
        <input type="hidden" name="_method" id="formMethod" value="POST">

        <div class="row g-3 mb-3">
          <div class="col-5">
            <label class="form-label">Mã vị trí <span class="text-danger">*</span></label>
            <input type="text" class="form-control text-uppercase" id="lCode" name="code"
                   placeholder="VD: A-01-01" required maxlength="50">
          </div>
          <div class="col-7">
            <label class="form-label">Tên vị trí <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="lName" name="name"
                   placeholder="VD: Kệ A tầng 1 ô 1" required maxlength="100">
          </div>
        </div>

        {{-- Loại vị trí --}}
        <div class="mb-3">
          <label class="form-label">Loại vị trí <span class="text-danger">*</span></label>
          <select class="form-select" id="lType" name="type" required onchange="onTypeChange(this.value)">
            @foreach (\App\Models\LocationType::cases() as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
          </select>
          <div class="form-text" id="typeHint">Vị trí thực — có thể chứa hàng hóa thực tế.</div>
        </div>

        {{-- Vị trí cha — chỉ hiện khi type = Internal --}}
        <div class="mb-3" id="parentGroup">
          <label class="form-label">Vị trí cha</label>
          <select class="form-select" id="lParent" name="parent_id">
            <option value="">— Không có (vị trí gốc) —</option>
            @foreach ($parentOptions as $p)
              <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
            @endforeach
          </select>
          <div class="form-text">Chỉ áp dụng cho vị trí Internal</div>
        </div>

        {{-- Trạng thái --}}
        <div class="mb-4">
          <label class="form-label fw-medium">Trạng thái</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status" id="lStatusActive" value="1" checked>
              <label class="form-check-label text-success" for="lStatusActive">Hoạt động</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status" id="lStatusInactive" value="0">
              <label class="form-check-label text-secondary" for="lStatusInactive">Ngừng hoạt động</label>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            Lưu vị trí
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
            Bạn có chắc muốn xóa vị trí<br>
            <strong id="deleteLocationName" class="text-body"></strong>?
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

@endsection

@push('scripts')
<script>
  const routeStore = '{{ route('master.location.store') }}';
  const routeBase  = '{{ url('master/location') }}';
  const locations  = @json($locations->keyBy('id'));

  const typeHints = {
    1: 'Vị trí thực — có thể chứa hàng hóa thực tế.',
    2: 'Vị trí ảo — dùng làm điểm neo cho kho, không chứa hàng trực tiếp.',
  };

  function onTypeChange(val) {
    const parentGroup = document.getElementById('parentGroup');
    const hint        = document.getElementById('typeHint');
    parentGroup.style.display = (val == 1) ? 'block' : 'none';
    hint.textContent = typeHints[val] ?? '';
  }

  function openForm(id = null) {
    const offcanvas = new coreui.OffCanvas(document.getElementById('locationOffcanvas'));
    const form      = document.getElementById('locationForm');
    const title     = document.getElementById('locationOffcanvasTitle');
    const method    = document.getElementById('formMethod');

    form.reset();
    document.getElementById('lStatusActive').checked = true;
    onTypeChange(1);

    if (id && locations[id]) {
      const l = locations[id];
      title.textContent = 'Chỉnh sửa vị trí kho';
      form.action       = `${routeBase}/${id}`;
      method.value      = 'PUT';

      document.getElementById('lCode').value     = l.code ?? '';
      document.getElementById('lName').value     = l.name ?? '';
      document.getElementById('lType').value     = l.type ?? 1;
      document.getElementById('lParent').value   = l.parent_id ?? '';
      document.getElementById(l.status == 1 ? 'lStatusActive' : 'lStatusInactive').checked = true;
      onTypeChange(l.type);
    } else {
      title.textContent = 'Thêm vị trí kho';
      form.action       = routeStore;
      method.value      = 'POST';
    }

    offcanvas.show();
    setTimeout(() => document.getElementById('lCode').focus(), 400);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteLocationName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // Auto viết hoa mã
  document.getElementById('lCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

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