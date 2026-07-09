@extends('layouts.app')

@section('title', 'Tồn kho hiện tại')

@section('breadcrumb')
  <li class="breadcrumb-item">Tồn kho</li>
  <li class="breadcrumb-item active">Tồn kho hiện tại</li>
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

  {{-- BẢNG TỒN KHO --}}
  <div class="card">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="fw-semibold flex-shrink-0">Tồn kho hiện tại</span>
      <form method="GET" action="{{ route('inventory.index') }}"
            class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">
        <div class="input-group" style="min-width:260px;flex:2">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tìm theo mã, tên vật tư, Lô hoặc Sê-ri">
        </div>
        <select class="form-select" name="category_id" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Danh mục</option>
          @foreach ($categories ?? [] as $cat)
            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
              {{ $cat->name }}
            </option>
          @endforeach
        </select>
        <select class="form-select" name="location_id" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Vị trí</option>
          @foreach ($locations ?? [] as $loc)
            <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
              {{ $loc->code }} - {{ $loc->name }}
            </option>
          @endforeach
        </select>
        <select class="form-select" name="status" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Trạng thái</option>
          @foreach (\App\Enums\LotSerialStatus::options() as $val => $label)
            <option value="{{ $val }}" {{ request('status') == (string) $val ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>

        @php
          $hasFilter = request()->hasAny(['search','category_id','location_id','status']);
        @endphp
        @if ($hasFilter)
          <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
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
              <th class="text-center align-middle" style="width:4%">#</th>
              <th class="align-middle" style="width:8%">
                <a href="{{ $sortUrl('product_code') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Mã {!! $sortIcon('product_code') !!}
                </a>
              </th>
              <th class="align-middle" style="min-width:200px">
                <a href="{{ $sortUrl('product_name') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Tên {!! $sortIcon('product_name') !!}
                </a>
              </th>
              <th class="align-middle" style="width:8%">Danh mục</th>
              <th class="align-middle" style="width:8%">ĐVT</th>
              <th class="text-center" style="width:16%">
                Vị trí
                <div class="d-flex mt-1">
                  <span class="flex-fill">Trước</span>
                  <span class="flex-fill">Hiện tại</span>
                </div>
              </th>
              <th class="text-center align-middle" style="width:4%">Lô</th>
              <th class="text-end align-middle" style="width:8%">
                <a href="{{ $sortUrl('expiry_date') }}" class="text-decoration-none text-body d-inline-flex align-items-center justify-content-end w-100">
                  HSD {!! $sortIcon('expiry_date') !!}
                </a>
              </th>
              <th class="text-end align-middle" style="width:8%">
                <a href="{{ $sortUrl('quantity') }}" class="text-decoration-none text-body d-inline-flex align-items-center justify-content-end w-100">
                  Tồn kho {!! $sortIcon('quantity') !!}
                </a>
              </th>
              <th class="text-end align-middle" style="width:8%">Đang giữ</th>
              <th class="text-end align-middle" style="width:8%">
                <a href="{{ $sortUrl('available_qty') }}" class="text-decoration-none text-body d-inline-flex align-items-center justify-content-end w-100">
                  Khả dụng {!! $sortIcon('available_qty') !!}
                </a>
              </th>
              <th class="text-center align-middle" style="width:8%">Trạng thái</th>
              <th class="text-center align-middle" style="width:8%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($stocks ?? [] as $row)
            @php
              $statusEnum  = $row->status instanceof \App\Enums\LotSerialStatus
                  ? $row->status
                  : \App\Enums\LotSerialStatus::tryFrom((int) $row->status);
              $statusText  = $statusEnum?->label() ?? '?';
              $statusBadge = $statusEnum?->badgeClass() ?? 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';

              $available = (float)$row->available_qty;
              $qty       = (float)$row->quantity;
              $minStock  = (float)($row->min_stock ?? 0);
              $hasSerials = (int)($row->serial_count ?? 0) > 0;

              // Cảnh báo tồn kho
              $stockAlert = '';
              if ($qty == 0)         $stockAlert = 'zero';
              elseif ($minStock > 0 && $qty <= $minStock) $stockAlert = 'low';

              // HSD
              $expiryDays  = null;
              $expiryClass = '';
              if ($row->expiry_date) {
                  $expiryDays  = now()->diffInDays(\Carbon\Carbon::parse($row->expiry_date), false);
                  $expiryClass = $expiryDays < 0 ? 'danger' : ($expiryDays <= 7 ? 'danger' : ($expiryDays <= 30 ? 'warning' : 'success'));
              }
            @endphp
            <tr class="{{ $stockAlert === 'zero' ? 'table-secondary opacity-75' : ($stockAlert === 'low' ? 'table-warning' : '') }}">
              <td class="text-center text-body-secondary">
                {{ method_exists($stocks, 'currentPage') ? ($stocks->currentPage() - 1) * $stocks->perPage() + $loop->iteration : $loop->iteration }}
              </td>
              <td>
                <code class="text-primary fw-medium">{{ $row->product_code }}</code>
              </td>
              <td>
                <div class="fw-medium">{{ $row->product_name }}</div>
                @if($stockAlert === 'low')
                  <div style="font-size:11px" class="text-danger">
                    <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
                    Dưới ngưỡng tối thiểu (min: {{ number_format($minStock, 0) }})
                  </div>
                @endif
              </td>
              <td class="small">{{ $row->category_code ?? '-' }}</td>
              <td class="small">{{ $row->uom_name }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="text-center" style="width:50%">
                    @if($row->prev_location_code || $row->prev_location_name)
                      <div class="fw-medium">{{ $row->prev_location_name }}</div>
                      <div class="small text-body-secondary font-monospace">{{ $row->prev_location_code }}</div>
                    @else
                      <span class="text-body-secondary small">-</span>
                    @endif
                  </div>
                  <div class="text-center" style="width:50%">
                    @if($row->location_code || $row->location_name)
                      <div class="fw-medium">{{ $row->location_name }}</div>
                      <div class="small text-body-secondary font-monospace">{{ $row->location_code }}</div>
                    @else
                      <span class="text-body-secondary small">-</span>
                    @endif
                  </div>
                </div>
              </td>
              <td class="text-center">
                @if($row->lot_id)
                  <span class="fw-medium">{{ $row->lot_number }}</span>
                @else
                  <span class="text-body-secondary">-</span>
                @endif
              </td>
              <td class="text-end">
                @if($row->expiry_date)
                  <span class="badge bg-{{ $expiryClass }}-subtle text-{{ $expiryClass }} border border-{{ $expiryClass }}-subtle" style="font-size:11px">
                    {{ \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') }}
                  </span>
                @else
                  <span class="text-body-secondary small">-</span>
                @endif
              </td>
              <td class="text-end fw-semibold {{ $qty == 0 ? 'text-body-secondary' : '' }}">
                {{ number_format($qty, 0) }}
              </td>
              <td class="text-end">
                @if((float)$row->reserved_qty > 0)
                  <span class="text-warning fw-medium">{{ number_format($row->reserved_qty, 0) }}</span>
                @else
                  <span class="text-body-secondary">-</span>
                @endif
              </td>
              <td class="text-end fw-bold {{ $available <= 0 ? 'text-danger' : 'text-primary' }}">
                {{ number_format($available, 0) }}
              </td>
              <td class="text-center">
                <span class="{{ $statusBadge }}">
                  {{ $statusText }}
                </span>
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                  @if($hasSerials)
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            title="Xem chi tiết Sê-ri"
                            data-product-id="{{ $row->product_id }}"
                            data-location-id="{{ $row->current_location_id }}"
                            data-lot-id="{{ $row->lot_id }}"
                            data-product-name="{{ $row->product_name }}"
                            data-product-code="{{ $row->product_code }}"
                            data-lot-number="{{ $row->lot_number }}"
                            onclick="openSerialDetailModal(this)">
                      <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
                    </button>
                  @endif
                  <button type="button" class="btn btn-sm btn-outline-primary"
                          title="Chỉnh sửa vị trí"
                          data-product-id="{{ $row->product_id }}"
                          data-location-id="{{ $row->current_location_id }}"
                          data-lot-id="{{ $row->lot_id }}"
                          data-product-name="{{ $row->product_name }}"
                          data-product-code="{{ $row->product_code }}"
                          data-lot-number="{{ $row->lot_number }}"
                          data-location-name="{{ $row->location_name }}"
                          data-location-code="{{ $row->location_code }}"
                          onclick="openEditLocationModal(this)">
                    <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="13" class="text-center text-body-secondary py-5">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                </svg>
                Không tìm thấy dữ liệu tồn kho.
              </td>
            </tr>
            @endforelse
          </tbody>
          @if(isset($stocks) && $stocks->count() > 0)
          <tfoot class="table-light fw-semibold">
            <tr>
              <td colspan="8" class="text-end">Tổng số lượng:</td>
              <td class="text-end">{{ number_format($filteredTotalQuantity ?? 0, 0) }}</td>
              <td class="text-end text-warning">{{ number_format($filteredTotalReservedQty ?? 0, 0) }}</td>
              <td class="text-end text-primary">{{ number_format($filteredTotalAvailableQty ?? 0, 0) }}</td>
              <td colspan="3"></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>

    @if(isset($stocks) && method_exists($stocks, 'hasPages'))
    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
        Hiển thị <strong>{{ $stocks->firstItem() ?? 0 }}</strong>-<strong>{{ $stocks->lastItem() ?? 0 }}</strong>
        trong tổng số <strong>{{ $stocks->total() }}</strong> dòng
      </small>
      {{ $stocks->appends(request()->query())->links('pagination::bootstrap-5') }}
      <style>
        .card-footer .pagination { margin-bottom: 0; }
      </style>
    </div>
    @endif
  </div>

  {{-- MODAL: XEM CHI TIẾT Sê-ri --}}
  <div class="modal fade" id="serialDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title fw-semibold">Chi tiết Sê-ri</h6>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info py-2 mb-3">
            <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
            <strong id="serialDetailInfo"></strong>
          </div>
          <div id="serialDetailLoading" class="text-center py-4 text-body-secondary">
            <div class="spinner-border spinner-border-sm me-2"></div> Đang tải...
          </div>
          <div id="serialDetailError" class="alert alert-danger small d-none mb-0"></div>
          <div id="serialDetailList" class="d-none">
            <div class="table-responsive border rounded">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width:10%">#</th>
                    <th>Sê-ri</th>
                    <th class="text-end" style="width:20%">SL</th>
                    <th class="text-center" style="width:20%">Trạng thái</th>
                  </tr>
                </thead>
                <tbody id="serialDetailTbody"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL: CHỈNH SỬA VỊ TRÍ NHANH --}}
  <div class="modal fade" id="editLocationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="editLocationForm" method="POST" action="{{ route('inventory.updateLocation') }}">
          @csrf
          @foreach(request()->query() as $qKey => $qVal)
            <input type="hidden" name="{{ $qKey }}" value="{{ $qVal }}">
          @endforeach
          <input type="hidden" name="product_id" id="editProductId">
          <input type="hidden" name="lot_id" id="editLotId">
          <input type="hidden" name="from_location_id" id="editFromLocationId">

          <div class="modal-header">
            <h5 class="modal-title" id="editLocationModalLabel">Chỉnh sửa vị trí</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            <div class="mb-3">
              <label class="form-label fw-medium">Sản phẩm</label>
              <input type="text" class="form-control" id="editProductLabel" value="" disabled>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Lô</label>
              <input type="text" class="form-control" id="editLotLabel" value="" disabled>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Vị trí hiện tại</label>
              <input type="text" class="form-control" id="editCurrentLocationLabel" value="" disabled>
            </div>

            <div>
              <label class="form-label fw-medium" for="editToLocationId">
                Vị trí mới <span class="text-danger">*</span>
              </label>
              <select class="form-select {{ $errors->has('to_location_id') ? 'is-invalid' : '' }}"
                      name="to_location_id" id="editToLocationId" required>
                <option value="">- Chọn vị trí -</option>
                @foreach ($locations ?? [] as $loc)
                  <option value="{{ $loc->id }}">{{ $loc->code }} - {{ $loc->name }}</option>
                @endforeach
              </select>
              @error('to_location_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" id="editLocationSubmitBtn" class="btn btn-primary">
              <span id="editLocationSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
              <svg id="editLocationSubmitIcon" class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              <span id="editLocationSubmitLabel">Lưu</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  function openSerialDetailModal(btn) {
    const productId    = btn.dataset.productId;
    const locationId    = btn.dataset.locationId;
    const lotId         = btn.dataset.lotId;
    const productName   = btn.dataset.productName;
    const productCode   = btn.dataset.productCode;
    const lotNumber     = btn.dataset.lotNumber;

    const lotPart = lotNumber ? 'Lô ' + lotNumber + ' - ' : '';
    document.getElementById('serialDetailInfo').textContent =
      lotPart + productName + (productCode ? ' (' + productCode + ')' : '');

    const loadingEl = document.getElementById('serialDetailLoading');
    const errorEl   = document.getElementById('serialDetailError');
    const listEl    = document.getElementById('serialDetailList');
    const tbody     = document.getElementById('serialDetailTbody');

    loadingEl.classList.remove('d-none');
    errorEl.classList.add('d-none');
    listEl.classList.add('d-none');
    tbody.innerHTML = '';

    const modalEl = document.getElementById('serialDetailModal');
    const modal = coreui.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const params = new URLSearchParams({
      product_id: productId,
      location_id: locationId,
    });
    if (lotId) params.append('lot_id', lotId);

    fetch("{{ route('inventory.lotSerials') }}?" + params.toString(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(res => {
        if (!res.ok) throw new Error('Không tải được dữ liệu.');
        return res.json();
      })
      .then(data => {
        loadingEl.classList.add('d-none');
        if (!data.serials || data.serials.length === 0) {
          errorEl.textContent = 'Không có Sê-ri nào.';
          errorEl.classList.remove('d-none');
          return;
        }
        data.serials.forEach((s, index) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="text-center text-body-secondary">${index + 1}</td>
            <td><code class="text-primary fw-medium">${s.serial_number}</code></td>
            <td class="text-end">${s.quantity}</td>
            <td class="text-center"><span class="${s.status_badge}">${s.status_label}</span></td>
          `;
          tbody.appendChild(tr);
        });
        listEl.classList.remove('d-none');
      })
      .catch(() => {
        loadingEl.classList.add('d-none');
        errorEl.textContent = 'Có lỗi xảy ra khi tải dữ liệu Sê-ri.';
        errorEl.classList.remove('d-none');
      });
  }

  function openEditLocationModal(btn) {
    document.getElementById('editProductId').value = btn.dataset.productId;
    document.getElementById('editLotId').value = btn.dataset.lotId || '';
    document.getElementById('editFromLocationId').value = btn.dataset.locationId;

    document.getElementById('editProductLabel').value =
      (btn.dataset.productCode ? btn.dataset.productCode + ' - ' : '') + (btn.dataset.productName || '-');

    document.getElementById('editLotLabel').value = btn.dataset.lotNumber || '-';

    document.getElementById('editCurrentLocationLabel').value =
      (btn.dataset.locationCode ? btn.dataset.locationCode + ' - ' : '') + (btn.dataset.locationName || '-');

    const select = document.getElementById('editToLocationId');
    select.value = '';
    Array.from(select.options).forEach(opt => {
      opt.disabled = opt.value === btn.dataset.locationId;
    });

    const modalEl = document.getElementById('editLocationModal');
    const modal = coreui.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }

  document.getElementById('editLocationForm').addEventListener('submit', function () {
    const btn = document.getElementById('editLocationSubmitBtn');
    document.getElementById('editLocationSubmitSpinner').classList.remove('d-none');
    document.getElementById('editLocationSubmitIcon').classList.add('d-none');
    document.getElementById('editLocationSubmitLabel').textContent = 'Đang lưu...';
    btn.disabled = true;
  });
</script>
@endpush