@extends('layouts.app')

@section('title', 'Quy đổi đơn vị — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Quy đổi đơn vị tính</li>
@endsection

@section('content')

  {{-- ===== HEADER ===== --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Quy đổi đơn vị tính</h4>
      <small class="text-body-secondary">Thiết lập hệ số quy đổi giữa các đơn vị tính</small>
    </div>
    <button class="btn btn-primary" onclick="openModal()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm mới
    </button>
  </div>

  {{-- ===== CARD THỐNG KÊ ===== --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-transfer') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng quy đổi</div>
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

  {{-- ===== BẢNG DANH SÁCH ===== --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách quy đổi</span>
      <form method="GET" action="{{ route('master.uom-conversion.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:240px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tìm đơn vị...">
        </div>
        <select class="form-select" name="status" style="width:140px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || request('status') !== null && request('status') !== '')
          <a href="{{ route('master.uom-conversion.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px" class="text-center">#</th>
              <th>Đơn vị nguồn</th>
              <th class="text-center" style="width:40px"></th>
              <th>Đơn vị đích</th>
              <th class="text-center" style="width:150px">Hệ số quy đổi</th>
              <th class="text-center" style="width:130px">Trạng thái</th>
              <th class="text-center" style="width:120px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($conversions as $index => $conv)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($conversions->currentPage() - 1) * $conversions->perPage() + $index + 1 }}
                </td>
                <td class="fw-medium">{{ $conv->fromUom->name }}</td>
                <td class="text-center text-body-secondary">
                  <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-right') }}"></use></svg>
                </td>
                <td class="fw-medium">{{ $conv->toUom->name }}</td>
                <td class="text-center font-monospace">
                  1 {{ $conv->fromUom->name }} = <strong>{{ rtrim(rtrim(number_format($conv->factor, 6), '0'), '.') }}</strong> {{ $conv->toUom->name }}
                </td>
                <td class="text-center">
                  @if ($conv->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
                      Hoạt động
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-ban') }}"></use></svg>
                      Ngừng
                    </span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openModal({{ $conv->id }}, {{ $conv->from_uom_id }}, {{ $conv->to_uom_id }}, '{{ $conv->factor }}', {{ $conv->status }})"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $conv->id }}, '{{ $conv->fromUom->name }} → {{ $conv->toUom->name }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-transfer') }}"></use>
                  </svg>
                  Chưa có quy đổi nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($conversions->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $conversions->firstItem() }}–{{ $conversions->lastItem() }}
          trong tổng số {{ $conversions->total() }} quy đổi
        </small>
        {{ $conversions->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- ===== MODAL TẠO / SỬA ===== --}}
  <div class="modal fade" id="convModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="convForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="formMethod" value="POST">

          <div class="modal-header">
            <h5 class="modal-title" id="convModalLabel">Thêm quy đổi đơn vị</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            {{-- Từ ĐVT --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Đơn vị nguồn <span class="text-danger">*</span></label>
              <select class="form-select" name="from_uom_id" id="fromUom" required>
                <option value="">— Chọn đơn vị —</option>
                @foreach ($uoms as $uom)
                  <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                @endforeach
              </select>
            </div>

            {{-- Hệ số --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Hệ số quy đổi <span class="text-danger">*</span></label>
              <input type="number" class="form-control font-monospace" name="factor" id="convFactor"
                     step="0.000001" min="0.000001" placeholder="VD: 1000, 0.001, 12...">
              <div class="form-text" id="factorHint">1 [nguồn] = <strong id="factorPreview">?</strong> [đích]</div>
            </div>

            {{-- Sang ĐVT --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Đơn vị đích <span class="text-danger">*</span></label>
              <select class="form-select" name="to_uom_id" id="toUom" required>
                <option value="">— Chọn đơn vị —</option>
                @foreach ($uoms as $uom)
                  <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                @endforeach
              </select>
            </div>

            {{-- Trạng thái --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Trạng thái</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status" id="statusActive" value="1" checked>
                  <label class="form-check-label text-success" for="statusActive">Hoạt động</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status" id="statusInactive" value="0">
                  <label class="form-check-label text-secondary" for="statusInactive">Ngừng hoạt động</label>
                </div>
              </div>
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
            Bạn có chắc muốn xóa quy đổi<br>
            <strong id="deleteConvName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Hành động này không thể hoàn tác.</p>
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
  const routeStore = '{{ route('master.uom-conversion.store') }}';
  const routeBase  = '{{ url('master/uom-conversion') }}';

  // Preview hệ số realtime
  function updatePreview() {
    const from   = document.getElementById('fromUom');
    const to     = document.getElementById('toUom');
    const factor = document.getElementById('convFactor').value;
    const fromName = from.options[from.selectedIndex]?.text || '[nguồn]';
    const toName   = to.options[to.selectedIndex]?.text   || '[đích]';
    document.getElementById('factorHint').innerHTML =
      `1 <strong>${fromName}</strong> = <strong>${factor || '?'}</strong> <strong>${toName}</strong>`;
  }

  document.getElementById('fromUom').addEventListener('change', updatePreview);
  document.getElementById('toUom').addEventListener('change', updatePreview);
  document.getElementById('convFactor').addEventListener('input', updatePreview);

  function openModal(id = null, fromId = null, toId = null, factor = '', status = 1) {
    const modal  = new coreui.Modal(document.getElementById('convModal'));
    const form   = document.getElementById('convForm');
    const title  = document.getElementById('convModalLabel');
    const method = document.getElementById('formMethod');

    if (id) {
      title.textContent = 'Chỉnh sửa quy đổi đơn vị';
      form.action       = `${routeBase}/${id}`;
      method.value      = 'PUT';
      document.getElementById('fromUom').value    = fromId;
      document.getElementById('toUom').value      = toId;
      document.getElementById('convFactor').value = factor;
      document.getElementById(status == 1 ? 'statusActive' : 'statusInactive').checked = true;
    } else {
      title.textContent = 'Thêm quy đổi đơn vị';
      form.action       = routeStore;
      method.value      = 'POST';
      form.reset();
      document.getElementById('statusActive').checked = true;
    }

    updatePreview();
    modal.show();
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteConvName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  @if ($errors->any())
    document.addEventListener('DOMContentLoaded', () => {
      openModal(
        {{ old('_id') ?: 'null' }},
        {{ old('from_uom_id') ?: 'null' }},
        {{ old('to_uom_id') ?: 'null' }},
        '{{ old('factor', '') }}',
        {{ old('status', 1) }}
      );
    });
  @endif
</script>
@endpush