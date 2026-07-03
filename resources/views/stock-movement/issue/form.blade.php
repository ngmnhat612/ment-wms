@extends('layouts.app')

@section('title', (isset($issue) ? 'Sửa phiếu xuất' : 'Tạo phiếu xuất') . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('issues.index') }}">Xuất kho</a></li>
<li class="breadcrumb-item active">{{ isset($issue) ? $issue->code : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($issue);
$action = $isEdit ? route('issues.update', $issue->id) : route('issues.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu xuất' : 'Tạo phiếu xuất mới' }}</h4>
        <small
            class="text-body-secondary">{{ $isEdit ? $issue->code : 'Điền thông tin và thêm hàng hóa cần xuất' }}</small>
    </div>
    <a href="{{ route('issues.index') }}" class="btn btn-outline-secondary btn-sm">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="issueForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── THÔNG TIN PHIẾU (1 hàng ngang) ── --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold py-2">
            <svg class="icon me-1 text-primary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
            </svg>
            Thông tin phiếu
        </div>
        <div class="card-body py-3">
            <div class="row g-3">

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Mã phiếu</label>
                    <input type="text"
                        class="form-control form-control-sm text-uppercase @error('code') is-invalid @enderror"
                        name="code" value="{{ old('code', $issue->code ?? '') }}" placeholder="Tự sinh nếu trống"
                        maxlength="50" {{ $isEdit ? 'readonly' : '' }}>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Loại xuất <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('issue_type') is-invalid @enderror"
                        name="issue_type" id="issueType" required>
                        <option value="1" {{ old('issue_type', $issue->issue_type ?? 1) == 1 ? 'selected' : '' }}>
                            Sản xuất</option>
                        <option value="2" {{ old('issue_type', $issue->issue_type ?? 1) == 2 ? 'selected' : '' }}>
                            Bảo trì</option>
                        <option value="3" {{ old('issue_type', $issue->issue_type ?? 1) == 3 ? 'selected' : '' }}>
                            Mượn</option>
                        <option value="4" {{ old('issue_type', $issue->issue_type ?? 1) == 4 ? 'selected' : '' }}>
                            Khác</option>
                    </select>
                    @error('issue_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Người yêu cầu</label>
                    <select class="form-select form-select-sm @error('requester_id') is-invalid @enderror"
                        name="requester_id">
                        <option value="">— Chọn —</option>
                        @foreach ($users as $user)
                        <option value="{{ $user->id }}"
                            {{ old('requester_id', $issue->requester_id ?? '') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('requester_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Số tham chiếu</label>
                    <input type="text" class="form-control form-control-sm @error('reference_no') is-invalid @enderror"
                        name="reference_no" value="{{ old('reference_no', $issue->reference_no ?? '') }}"
                        placeholder="Số lệnh SX / công việc" maxlength="100">
                    @error('reference_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Ngày xuất <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('issue_date') is-invalid @enderror"
                        name="issue_date"
                        value="{{ old('issue_date', isset($issue->issue_date) ? \Carbon\Carbon::parse($issue->issue_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Hạn trả — chỉ hiện khi Mượn --}}
                <div class="col-md-2" id="returnDateGroup"
                    style="{{ old('issue_type', $issue->issue_type ?? 1) == 3 ? '' : 'display:none' }}">
                    <label class="form-label form-label-sm mb-1">Hạn trả hàng</label>
                    <input type="date"
                        class="form-control form-control-sm @error('expected_return_date') is-invalid @enderror"
                        name="expected_return_date"
                        value="{{ old('expected_return_date', isset($issue->expected_return_date) ? \Carbon\Carbon::parse($issue->expected_return_date)->format('Y-m-d') : '') }}">
                    @error('expected_return_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Ghi chú</label>
                    <input type="text" class="form-control form-control-sm" name="note"
                        value="{{ old('note', $issue->note ?? '') }}" placeholder="Ghi chú nếu có..." maxlength="500">
                </div>

            </div>{{-- end row g-3 --}}
        </div>{{-- end card-body --}}
    </div>{{-- end card --}}

    {{-- ── CHI TIẾT HÀNG HÓA ── --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">
                <svg class="icon me-1 text-primary">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Chi tiết hàng hóa
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                </svg>
                Thêm dòng
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="detailTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px"></th>
                            <th>Hàng hóa <span class="text-danger">*</span></th>
                            <th style="width:100px">ĐVT</th>
                            <th style="width:110px">Số lượng <span class="text-danger">*</span></th>
                            <th style="width:130px">Vị trí kho <span class="text-danger">*</span></th>
                            <th style="width:110px">Số Lot</th>
                            <th style="width:120px">Số Serial</th>
                            <th style="width:90px">Tồn hiện</th>
                            <th style="width:200px">Ghi chú</th>
                            <th style="width:36px"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">

                        @if($isEdit && $issue->details->count())
                        @foreach($issue->details as $i => $detail)
                        <tr class="existing-row" data-current-location="{{ $detail->location_id }}"
                            data-current-lot-id="{{ $detail->lot_id ?? '' }}"
                            data-current-lot-number="{{ $detail->lot?->lot_number ?? '' }}"
                            data-current-serial-id="{{ $detail->serial_id ?? '' }}"
                            data-current-serial-number="{{ $detail->serial?->serial_number ?? '' }}">
                            <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                            <td>
                                <select class="form-select form-select-sm product-select"
                                    name="details[{{ $i }}][product_id]" required onchange="onProductChange(this)">
                                    <option value="">— Chọn hàng hóa —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-uom="{{ $p->uom?->name }}"
                                        data-uom-id="{{ $p->uom_id }}" data-stock="{{ $p->total_stock }}"
                                        data-tracking="{{ $p->tracking_type }}"
                                        {{ $detail->product_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->code }} — {{ $p->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][uom_id]" class="uom-hidden"
                                    value="{{ $detail->uom_id }}">
                                <span
                                    class="uom-label text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</span>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end qty-input"
                                    name="details[{{ $i }}][quantity]" value="{{ $detail->quantity }}" min="0.001"
                                    step="0.001" required oninput="handleQtyInput(this)">
                            </td>
                            <td>
                                <select class="form-select form-select-sm location-select"
                                    name="details[{{ $i }}][location_id]" required onchange="onLocationChange(this)"
                                    disabled>
                                    <option value="{{ $detail->location_id }}">⏳ Đang tải...</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][lot_id]" class="lot-id-hidden"
                                    value="{{ $detail->lot_id ?? '' }}">
                                <select class="form-select form-select-sm lot-select" disabled>
                                    <option value="">— Đang tải —</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][serial_id]" class="serial-id-hidden"
                                    value="{{ $detail->serial_id ?? '' }}">
                                <select class="form-select form-select-sm serial-select" disabled>
                                    <option value="">— Đang tải —</option>
                                </select>
                            </td>
                            <td class="text-end small stock-display text-body-secondary"
                                data-stock="{{ $detail->product?->total_stock ?? 0 }}">
                                {{ number_format($detail->product?->total_stock ?? 0, 0) }}
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="details[{{ $i }}][note]"
                                    value="{{ $detail->note ?? '' }}" placeholder="Ghi chú..." maxlength="200">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger p-1"
                                    onclick="removeRow(this)" title="Xóa dòng">
                                    <svg class="icon">
                                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}">
                                        </use>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        @endif

                    </tbody>
                </table>
            </div>

            <div id="emptyDetail" class="text-center text-body-secondary py-5"
                style="{{ ($isEdit && $issue->details->count()) ? 'display:none' : '' }}">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Chưa có hàng hóa nào.<br>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow()">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                    </svg>
                    Thêm dòng đầu tiên
                </button>
            </div>
        </div>

        <div class="card-footer text-body-secondary small d-flex justify-content-between">
            <span>Tổng số dòng: <strong id="rowCount">{{ $isEdit ? $issue->details->count() : 0 }}</strong></span>
            <span>Tổng SL xuất: <strong id="totalQty">0</strong></span>
        </div>
    </div>

    {{-- Cảnh báo tồn kho --}}
    <div class="alert alert-warning d-none mt-3" id="stockWarning">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
        </svg>
        <strong>Cảnh báo:</strong> Một số dòng có số lượng xuất vượt quá tồn kho hiện tại.
    </div>
    {{-- ── NÚT LƯU ── --}}
    <div class="d-flex gap-2 justify-content-end mt-3">
        <a href="{{ route('issues.index') }}" class="btn btn-outline-secondary">Hủy</a>
        @if(!$isEdit)
        <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
            </svg>
            Lưu & Tạo phiếu mới
        </button>
        @endif
        <button type="submit" class="btn btn-primary" name="action" value="save">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
            </svg>
            {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu xuất' }}
        </button>
    </div>
    </div>
    </div>

</form>

@endsection

@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const LOCATIONS = @json($locationsJson);

// Lots indexed by product_id
const LOTS = @json($lots ?? []);

let rowIndex = <?php echo $isEdit ? $issue->details->count() : 0; ?>;

// ── Template dòng chi tiết ─────────────────────────────────────────
function rowTemplate(i) {
    const productOptions = PRODUCTS.map(p =>
        `<option value="${p.id}" data-uom="${p.uom}" data-uom-id="${p.uom_id}" data-stock="${p.stock}" data-tracking="${p.tracking_type}">
      ${p.code} — ${p.name}
    </option>`
    ).join('');

    const locationOptions = LOCATIONS.map(l =>
        `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''}</option>`
    ).join('');

    return `
  <tr data-tracking="1">
    <td class="text-center text-body-secondary small">${i + 1}</td>
    <td>
      <select class="form-select form-select-sm product-select"
              name="details[${i}][product_id]" required
              onchange="onProductChange(this)">
        <option value="">— Chọn hàng hóa —</option>
        ${productOptions}
      </select>
    </td>
    <td>
      <input type="hidden" name="details[${i}][uom_id]" class="uom-hidden" value="">
      <span class="uom-label text-body-secondary small">—</span>
    </td>
    <td>
      <input type="number" class="form-control form-control-sm text-end qty-input"
             name="details[${i}][quantity]"
             min="0.001" step="0.001" required placeholder="0"
             oninput="handleQtyInput(this)">
    </td>
    <td>
      <select class="form-select form-select-sm location-select" name="details[${i}][location_id]"
              required onchange="onLocationChange(this)">
        <option value="">— Chọn sản phẩm trước —</option>
      </select>
    </td>
    <td>
      <input type="hidden" name="details[${i}][lot_id]" class="lot-id-hidden" value="">
      <select class="form-select form-select-sm lot-select">
        <option value="">— Chọn vị trí trước —</option>
      </select>
    </td>
    <td>
      <input type="hidden" name="details[${i}][serial_id]" class="serial-id-hidden" value="">
      <select class="form-select form-select-sm serial-select">
        <option value="">— Chọn vị trí trước —</option>
      </select>
    </td>
    <td class="text-end small stock-display text-body-secondary">—</td>
    <td>
      <input type="text" class="form-control form-control-sm"
             name="details[${i}][note]"
             placeholder="Ghi chú..." maxlength="200">
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-outline-danger p-1"
              onclick="removeRow(this)" title="Xóa dòng">
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
      </button>
    </td>
  </tr>`;
}

window.addRow = function() {
    document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
    rowIndex++;
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

window.removeRow = function(btn) {
    btn.closest('tr').remove();
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
    checkAllStock();
}

function syncRowNumbers() {
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        tr.querySelector('td:first-child').textContent = i + 1;
    });
}

function toggleEmptyState() {
    const rows = document.querySelectorAll('#detailBody tr').length;
    document.getElementById('emptyDetail').style.display = rows ? 'none' : '';
    document.getElementById('rowCount').textContent = rows;
}

function updateTotals() {
    let total = 0;
    document.querySelectorAll('input[name$="[quantity]"]').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById('totalQty').textContent =
        total.toLocaleString('vi-VN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
}

// ── Khi chọn hàng hóa → điền ĐVT, tồn kho tổng, fetch vị trí/lot ─
window.onProductChange = function(sel) {
    const opt = sel.options[sel.selectedIndex];
    const tr = sel.closest('tr');
    const productId = parseInt(opt.value);

    tr.dataset.tracking = opt.dataset.tracking || 1;

    // ĐVT
    tr.querySelector('.uom-label').textContent = opt.dataset.uom || '—';
    tr.querySelector('.uom-hidden').value = opt.dataset.uomId || '';

    // Tồn kho tổng (cột hiển thị)
    const stock = parseFloat(opt.dataset.stock) || 0;
    const stockEl = tr.querySelector('.stock-display');
    stockEl.textContent = stock.toLocaleString('vi-VN');
    stockEl.dataset.stock = stock;

    // Reset location & lot
    const locationSel = tr.querySelector('.location-select');
    const lotSel = tr.querySelector('.lot-select');
    locationSel.innerHTML = '<option value="">⏳ Đang tải vị trí...</option>';
    locationSel.disabled = true;
    lotSel.innerHTML = '<option value="">— Chọn vị trí trước —</option>';

    if (!productId) {
        locationSel.innerHTML = '<option value="">— Chọn sản phẩm trước —</option>';
        locationSel.disabled = false;
        return;
    }

    // Fetch vị trí có tồn kho khả dụng
    fetch(`/issues/stock-locations/${productId}`)
        .then(r => r.json())
        .then(stocks => {
            // Gom nhóm theo location_id
            const locMap = {};
            stocks.forEach(s => {
                if (!locMap[s.location_id]) {
                    locMap[s.location_id] = {
                        id: s.location_id,
                        code: s.location_code,
                        name: s.location_name,
                        available_qty: 0,
                        lots: [],
                        serials: [],
                    };
                }
                locMap[s.location_id].available_qty += s.available_qty;
                if (s.lot_id) {
                    locMap[s.location_id].lots.push({
                        id: s.lot_id,
                        lot_number: s.lot_number,
                        expiry_date: s.expiry_date,
                        available_qty: s.available_qty,
                    });
                }
                if (s.serial_id) {
                    locMap[s.location_id].serials.push({
                        id: s.serial_id,
                        serial_number: s.serial_number,
                        available_qty: s.available_qty,
                        lot_id: s.lot_id ? parseInt(s.lot_id) : null,
                    });
                }
            });

            // Lưu stockMap vào tr để dùng khi đổi location
            tr.dataset.stockMap = JSON.stringify(locMap);

            const locs = Object.values(locMap);
            if (locs.length === 0) {
                locationSel.innerHTML = '<option value="">⚠ Không có tồn kho khả dụng</option>';
            } else {
                locationSel.innerHTML =
                    '<option value="">— Chọn vị trí lấy hàng —</option>' +
                    locs.map(l =>
                        `<option value="${l.id}">` +
                        `${l.code}${l.name ? ' — ' + l.name : ''} ` +
                        `(Khả dụng: ${l.available_qty.toLocaleString('vi-VN')})` +
                        `</option>`
                    ).join('');

                // Tự động chọn vị trí đầu tiên (gợi ý FIFO/FEFO đã được sort ở service)
                if (locs.length === 1) {
                    locationSel.value = locs[0].id;
                    onLocationChange(locationSel);
                }
            }
            locationSel.disabled = false;
        })
        .catch(() => {
            locationSel.innerHTML = '<option value="">— Lỗi tải vị trí —</option>';
            locationSel.disabled = false;
        });

    checkStock(tr.querySelector('.qty-input'));
}


// ── Khi chọn vị trí → cập nhật lot/serial tương ứng ──────────────
window.onLocationChange = function(locationSel) {
    const tr = locationSel.closest('tr');
    const lotSel = tr.querySelector('.lot-select');
    const serialSel = tr.querySelector('.serial-select');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    const serialHidden = tr.querySelector('.serial-id-hidden');
    const locationId = parseInt(locationSel.value);
    const stockMap = tr.dataset.stockMap ? JSON.parse(tr.dataset.stockMap) : {};

    if (lotHidden) lotHidden.value = '';
    if (serialHidden) serialHidden.value = '';

    if (!locationId || !stockMap[locationId]) {
        lotSel.innerHTML = '<option value="">— Không chọn —</option>';
        serialSel.innerHTML = '<option value="">— Không chọn —</option>';
        return;
    }

    const lots = stockMap[locationId].lots || [];
    const serials = stockMap[locationId].serials || [];

    // Lot / Batch
    lotSel.innerHTML = '<option value="">— Không chọn —</option>' +
        lots.map(l =>
            `<option value="${l.id}">${l.lot_number}` +
            `${l.expiry_date ? ' · HSD: ' + l.expiry_date : ''}` +
            ` · Tồn: ${l.available_qty.toLocaleString('vi-VN')}</option>`
        ).join('');

    const tracking = parseInt(tr.dataset.tracking) || 1;

    if (tracking === 4) {
        // LotAndSerial: serial phụ thuộc vào lot → để trống, chờ chọn lot
        serialSel.innerHTML = '<option value="">— Chọn lot trước —</option>';
    } else {
        // Tracking khác: hiện tất cả serial
        serialSel.innerHTML = '<option value="">— Không chọn —</option>' +
            serials.map(s =>
                `<option value="${s.id}">${s.serial_number} · Tồn: ${s.available_qty.toLocaleString('vi-VN')}</option>`
            ).join('');
    }

    // Tự động chọn nếu chỉ có đúng 1 lot
    if (lots.length === 1) {
        lotSel.selectedIndex = 1;
        if (lotHidden) lotHidden.value = lots[0].id;
        lotSel.dispatchEvent(new Event('change', {
            bubbles: true
        })); // ← trigger filter serial cho tracking=4
    }
    // Tự động chọn nếu chỉ có đúng 1 serial (chỉ áp dụng tracking != 4)
    if (tracking !== 4 && serials.length === 1) {
        serialSel.selectedIndex = 1;
        if (serialHidden) serialHidden.value = serials[0].id;
        serialSel.dispatchEvent(new Event('change'));
    }
}

// ── Nạp dữ liệu vị trí/lot/serial cho các dòng đã có sẵn (Edit) ───
function initExistingRow(tr) {
    const productSel = tr.querySelector('.product-select');
    const productId = parseInt(productSel.value);
    if (!productId) return;

    const opt = productSel.options[productSel.selectedIndex];
    tr.dataset.tracking = opt.dataset.tracking || 1;

    const locationSel = tr.querySelector('.location-select');
    const lotSel = tr.querySelector('.lot-select');
    const serialSel = tr.querySelector('.serial-select');

    const currentLocation = tr.dataset.currentLocation;
    const currentLotId = tr.dataset.currentLotId;
    const currentSerialId = tr.dataset.currentSerialId;

    fetch(`/issues/stock-locations/${productId}`)
        .then(r => r.json())
        .then(stocks => {
            const locMap = {};
            stocks.forEach(s => {
                if (!locMap[s.location_id]) {
                    locMap[s.location_id] = {
                        id: s.location_id,
                        code: s.location_code,
                        name: s.location_name,
                        available_qty: 0,
                        lots: [],
                        serials: [],
                    };
                }
                locMap[s.location_id].available_qty += s.available_qty;
                if (s.lot_id) {
                    locMap[s.location_id].lots.push({
                        id: s.lot_id,
                        lot_number: s.lot_number,
                        expiry_date: s.expiry_date,
                        available_qty: s.available_qty,
                    });
                }
                if (s.serial_id) {
                    locMap[s.location_id].serials.push({
                        id: s.serial_id,
                        serial_number: s.serial_number,
                        available_qty: s.available_qty,
                        lot_id: s.lot_id ? parseInt(s.lot_id) : null,
                    });
                }
            });

            // Đảm bảo vị trí/lot/serial hiện tại của dòng luôn xuất hiện trong danh sách
            if (currentLocation && !locMap[currentLocation]) {
                locMap[currentLocation] = {
                    id: parseInt(currentLocation),
                    code: '(vị trí hiện tại)',
                    name: '',
                    available_qty: 0,
                    lots: [],
                    serials: [],
                };
            }
            if (currentLocation && currentLotId &&
                !locMap[currentLocation].lots.find(l => String(l.id) === String(currentLotId))) {
                locMap[currentLocation].lots.push({
                    id: parseInt(currentLotId),
                    lot_number: tr.dataset.currentLotNumber || `Lot #${currentLotId}`,
                    expiry_date: null,
                    available_qty: 0,
                });
            }
            if (currentLocation && currentSerialId &&
                !locMap[currentLocation].serials.find(s => String(s.id) === String(currentSerialId))) {
                locMap[currentLocation].serials.push({
                    id: parseInt(currentSerialId),
                    serial_number: tr.dataset.currentSerialNumber || `Serial #${currentSerialId}`,
                    available_qty: 0,
                });
            }

            tr.dataset.stockMap = JSON.stringify(locMap);

            const locs = Object.values(locMap);
            locationSel.innerHTML = locs.map(l =>
                `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''} ` +
                `(Khả dụng: ${l.available_qty.toLocaleString('vi-VN')})</option>`
            ).join('');
            locationSel.disabled = false;

            if (currentLocation) {
                locationSel.value = currentLocation;

                // Populate lot/serial options mà không reset hidden fields
                const stockMap = tr.dataset.stockMap ? JSON.parse(tr.dataset.stockMap) : {};
                const locationId = parseInt(currentLocation);
                const lots = stockMap[locationId]?.lots || [];
                const serials = stockMap[locationId]?.serials || [];

                const lotHidden = tr.querySelector('.lot-id-hidden');
                const serialHidden = tr.querySelector('.serial-id-hidden');

                lotSel.innerHTML = '<option value="">— Không chọn —</option>' +
                    lots.map(l =>
                        `<option value="${l.id}">${l.lot_number}` +
                        `${l.expiry_date ? ' · HSD: ' + l.expiry_date : ''}` +
                        ` · Tồn: ${l.available_qty.toLocaleString('vi-VN')}</option>`
                    ).join('');

                const tracking = parseInt(tr.dataset.tracking) || 1;

                if (tracking === 4) {
                    // tracking=4 (LotAndSerial): serial phụ thuộc lot → để trống, chờ dispatch lot change
                    serialSel.innerHTML = '<option value="">— Chọn lot trước —</option>';
                } else {
                    serialSel.innerHTML = '<option value="">— Không chọn —</option>' +
                        serials.map(s =>
                            `<option value="${s.id}">${s.serial_number} · Tồn: ${s.available_qty.toLocaleString('vi-VN')}</option>`
                        ).join('');
                }

                if (currentLotId) {
                    lotSel.value = currentLotId;
                    if (lotHidden) lotHidden.value = currentLotId;
                    // Dispatch change để trigger filter serial (phải bubble để delegated listener bắt được)
                    lotSel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                if (tracking !== 4 && currentSerialId) {
                    serialSel.value = currentSerialId;
                    if (serialHidden) serialHidden.value = currentSerialId;
                }
            }
            lotSel.disabled = false;
            serialSel.disabled = false;
        })
        .catch(() => {
            locationSel.innerHTML = '<option value="">— Lỗi tải vị trí —</option>';
            locationSel.disabled = false;
            lotSel.disabled = false;
            serialSel.disabled = false;
        });
}

// ── Khi chọn Lot/Batch hoặc Serial → cập nhật hidden fields ───────
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('lot-select')) {
        const tr = e.target.closest('tr');
        const lotHidden = tr.querySelector('.lot-id-hidden');
        const serialSel = tr.querySelector('.serial-select');
        const serialHidden = tr.querySelector('.serial-id-hidden');
        const selectedLotId = parseInt(e.target.value) || null;

        if (lotHidden) lotHidden.value = e.target.value;

        // Reset serial khi đổi lot
        if (serialHidden) serialHidden.value = '';

        // Lọc serial theo lot vừa chọn (tracking=4)
        const tracking = parseInt(tr.dataset.tracking) || 1;
        if (tracking === 4 && serialSel) {
            const locationSel = tr.querySelector('.location-select');
            const locationId = parseInt(locationSel?.value) || null;
            const stockMap = tr.dataset.stockMap ? JSON.parse(tr.dataset.stockMap) : {};
            const allSerials = stockMap[locationId]?.serials || [];

            // Mỗi serial trong stockMap cần có lot_id — xem phần AJAX endpoint bên dưới
            const filtered = selectedLotId ?
                allSerials.filter(s => s.lot_id === selectedLotId) :
                allSerials;

            serialSel.innerHTML = '<option value="">— Chọn serial —</option>' +
                filtered.map(s =>
                    `<option value="${s.id}">${s.serial_number} · Tồn: ${s.available_qty.toLocaleString('vi-VN')}</option>`
                ).join('');
            serialSel.disabled = false;

            // Tự động chọn nếu chỉ còn 1 serial
            if (filtered.length === 1) {
                serialSel.selectedIndex = 1;
                if (serialHidden) serialHidden.value = filtered[0].id;
            }
        }
    }
    if (e.target.classList.contains('serial-select')) {
        const tr = e.target.closest('tr');
        const serialHidden = tr.querySelector('.serial-id-hidden');
        if (serialHidden) serialHidden.value = e.target.value;
        checkDuplicateSerials();
    }
});

// ── SL hàng quản lý theo Serial → tự sinh thêm dòng (mỗi dòng = 1 serial) ──
window.handleQtyInput = function(qtyInput) {
    const tr = qtyInput.closest('tr');
    const tracking = parseInt(tr.dataset.tracking) || 1;
    const isSerialManaged = (tracking === 3 || tracking === 4); // SERIAL hoặc LOT+SERIAL

    if (isSerialManaged) {
        const qty = parseInt(qtyInput.value) || 0;

        if (qty > 1) {
            const productSel = tr.querySelector('.product-select');
            const productOpt = productSel.options[productSel.selectedIndex];
            const locationSel = tr.querySelector('.location-select');
            const locationVal = locationSel.value;
            const stockMapStr = tr.dataset.stockMap || '';

            qtyInput.value = 1;

            for (let n = 1; n < qty; n++) {
                addRow();
                const newTr = document.getElementById('detailBody').lastElementChild;

                // Copy hàng hóa, ĐVT, tồn, tracking
                const newProductSel = newTr.querySelector('.product-select');
                newProductSel.value = productSel.value;
                newTr.querySelector('.uom-label').textContent = productOpt.dataset.uom || '—';
                newTr.querySelector('.uom-hidden').value = productOpt.dataset.uomId || '';
                const stockEl = newTr.querySelector('.stock-display');
                stockEl.textContent = (parseFloat(productOpt.dataset.stock) || 0).toLocaleString('vi-VN');
                stockEl.dataset.stock = productOpt.dataset.stock || 0;
                newTr.dataset.tracking = tracking;
                newTr.querySelector('.qty-input').value = 1;

                // Copy danh sách vị trí/lot/serial đã fetch (không gọi lại API)
                if (stockMapStr) {
                    newTr.dataset.stockMap = stockMapStr;
                    const locMap = JSON.parse(stockMapStr);
                    const locs = Object.values(locMap);
                    const newLocationSel = newTr.querySelector('.location-select');
                    newLocationSel.innerHTML =
                        '<option value="">— Chọn vị trí lấy hàng —</option>' +
                        locs.map(l =>
                            `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''} ` +
                            `(Khả dụng: ${l.available_qty.toLocaleString('vi-VN')})</option>`
                        ).join('');
                    newLocationSel.disabled = false;

                    if (locationVal) {
                        newLocationSel.value = locationVal;
                        onLocationChange(newLocationSel);
                    }
                }
            }
        }
    }

    checkStock(qtyInput);
    updateTotals();
}

// ── Kiểm tra SL xuất so với tồn ───────────────────────────────────
window.checkStock = function(qtyInput) {
    if (!qtyInput) return;
    const tr = qtyInput.closest('tr');
    const stock = parseFloat(tr.querySelector('.stock-display')?.dataset.stock) || 0;
    const qty = parseFloat(qtyInput.value) || 0;

    if (qty > 0 && stock > 0 && qty > stock) {
        qtyInput.classList.add('is-invalid');
    } else {
        qtyInput.classList.remove('is-invalid');
    }
    checkAllStock();
    updateTotals();
}

function checkAllStock() {
    const hasWarning = document.querySelector('.qty-input.is-invalid') !== null;
    document.getElementById('stockWarning').classList.toggle('d-none', !hasWarning);
}

// ── Hiện/ẩn trường hạn trả theo loại xuất ────────────────────────
document.getElementById('issueType').addEventListener('change', function() {
    document.getElementById('returnDateGroup').style.display = this.value == '3' ? '' : 'none';
});

function checkDuplicateSerials() {
    const seen = {};
    let hasDup = false;

    document.querySelectorAll('#detailBody tr').forEach(tr => {
        const serialSel = tr.querySelector('.serial-select');
        const val = serialSel?.value;
        if (!val) return;

        if (seen[val]) {
            serialSel.classList.add('is-invalid');
            seen[val].classList.add('is-invalid');
            hasDup = true;
        } else {
            seen[val] = serialSel;
            serialSel.classList.remove('is-invalid');
        }
    });

    const btn = document.querySelector('button[name="action"]');
    if (btn) btn.disabled = hasDup;

    let warn = document.getElementById('serialDupWarning');
    if (hasDup) {
        if (!warn) {
            warn = document.createElement('div');
            warn.id = 'serialDupWarning';
            warn.className = 'alert alert-danger mt-3';
            warn.innerHTML = '<strong>Lỗi:</strong> Có số Serial bị chọn trùng nhau trong cùng phiếu.';
            document.getElementById('detailTable').closest('.card').after(warn);
        }
    } else {
        warn?.remove();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    toggleEmptyState();
    updateTotals();
    document.querySelectorAll('#detailBody tr.existing-row').forEach(initExistingRow);
    @if(!$isEdit) addRow();
    @endif
});
</script>
@endpush
