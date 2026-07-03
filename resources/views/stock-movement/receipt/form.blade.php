@extends('layouts.app')

@section('title', (isset($receipt) ? 'Sửa phiếu nhập' : 'Tạo phiếu nhập') . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('receipts.index') }}">Nhập kho</a></li>
<li class="breadcrumb-item active">{{ isset($receipt) ? $receipt->code : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($receipt);
$action = $isEdit ? route('receipts.update', $receipt->id) : route('receipts.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu nhập' : 'Tạo phiếu nhập mới' }}</h4>
        <small
            class="text-body-secondary">{{ $isEdit ? $receipt->code : 'Điền thông tin và thêm hàng hóa cần nhập' }}</small>
    </div>
    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary btn-sm">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="receiptForm">
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
                        name="code" value="{{ old('code', $receipt->code ?? '') }}" placeholder="Tự sinh nếu trống"
                        maxlength="50" {{ $isEdit ? 'readonly' : '' }}>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Loại nhập <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('receipt_type') is-invalid @enderror"
                        name="receipt_type" id="receiptType" required>
                        <option value="1" {{ old('receipt_type', $receipt->receipt_type ?? 1) == 1 ? 'selected' : '' }}>
                            Từ nhà cung cấp</option>
                        <option value="2" {{ old('receipt_type', $receipt->receipt_type ?? 1) == 2 ? 'selected' : '' }}>
                            Trả hàng SX / BT</option>
                        <option value="3" {{ old('receipt_type', $receipt->receipt_type ?? 1) == 3 ? 'selected' : '' }}>
                            Khác</option>
                    </select>
                    @error('receipt_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2" id="supplierGroup">
                    <label class="form-label form-label-sm mb-1">Nhà cung cấp</label>
                    <select class="form-select form-select-sm @error('supplier_id') is-invalid @enderror"
                        name="supplier_id">
                        <option value="">— Chọn NCC —</option>
                        @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}"
                            {{ old('supplier_id', $receipt->supplier_id ?? '') == $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Số tham chiếu</label>
                    <input type="text" class="form-control form-control-sm @error('reference_no') is-invalid @enderror"
                        name="reference_no" value="{{ old('reference_no', $receipt->reference_no ?? '') }}"
                        placeholder="Số PO / chứng từ" maxlength="100">
                    @error('reference_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Ngày nhập <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('receipt_date') is-invalid @enderror"
                        name="receipt_date"
                        value="{{ old('receipt_date', isset($receipt->receipt_date) ? \Carbon\Carbon::parse($receipt->receipt_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('receipt_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Ghi chú</label>
                    <input type="text" class="form-control form-control-sm" name="note"
                        value="{{ old('note', $receipt->note ?? '') }}" placeholder="Ghi chú nếu có..." maxlength="500">
                </div>

            </div>
        </div>
    </div>

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
            <div id="lotSerialAlertContainer"></div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="detailTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px" class="text-center">#</th>
                            <th style="min-width:200px">Hàng hóa <span class="text-danger">*</span></th>
                            <th style="width:80px">ĐVT</th>
                            <th style="width:120px">SL dự kiến <span class="text-danger">*</span></th>
                            <th style="width:120px">SL thực nhận</th>
                            <th style="width:140px">Vị trí kho</th>
                            <th style="width:120px">
                                Số Lot
                                <svg class="icon icon-sm text-body-secondary"
                                    title="Bắt buộc với hàng theo Lô hoặc Lô+Serial">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}">
                                    </use>
                                </svg>
                            </th>
                            <th style="width:120px">
                                Số Serial
                                <svg class="icon icon-sm text-body-secondary"
                                    title="Bắt buộc với hàng theo Serial hoặc Lô+Serial">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}">
                                    </use>
                                </svg>
                            </th>
                            <th style="width:120px">Hạn dùng</th>
                            <th style="width:36px"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">

                        @php
                        $detailsOld = old('details');
                        if ($detailsOld) {
                        $rows = collect($detailsOld);
                        } elseif ($isEdit) {
                        $rows = $receipt->details;
                        } else {
                        $rows = collect();
                        }
                        @endphp

                        @foreach($rows as $i => $detail)
                        @php
                        if (is_array($detail)) {
                        // Dữ liệu cũ (old input) sau khi validate lỗi
                        $productId = $detail['product_id'] ?? '';
                        $product = $products->firstWhere('id', (int) $productId);
                        $tracking = (int) ($product->tracking_type ?? 1);
                        $uomId = $detail['uom_id'] ?? ($product->uom_id ?? '');
                        $uomName = $product->uom?->name ?? '—';
                        $expectedQty = isset($detail['expected_qty']) ? $detail['expected_qty'] + 0 : '';
                        $actualQty = isset($detail['actual_qty']) ? $detail['actual_qty'] + 0 : '';
                        $locationId = $detail['location_id'] ?? '';
                        $lotNumber = $detail['lot_number'] ?? '';
                        $serialNumber = $detail['serial_number'] ?? '';
                        $expiryDate = $detail['expiry_date'] ?? '';
                        } else {
                        // Dữ liệu từ phiếu đang sửa
                        $productId = $detail->product_id;
                        $product = $detail->product;
                        $tracking = (int) ($product->tracking_type ?? 1);
                        $uomId = $detail->uom_id;
                        $uomName = $detail->uom?->name ?? '—';
                        $expectedQty = $detail->expected_qty + 0;
                        $actualQty = $detail->actual_qty + 0;
                        $locationId = $detail->location_id;
                        $lotNumber = $detail->lot?->lot_number ?? '';
                        $serialNumber = $detail->serial?->serial_number ?? '';
                        $expiryDate = $detail->expiry_date
                        ? \Carbon\Carbon::parse($detail->expiry_date)->format('Y-m-d')
                        : '';
                        }
                        @endphp
                        <tr>
                            <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                            <td>
                                <select class="form-select form-select-sm product-select"
                                    name="details[{{ $i }}][product_id]" required onchange="onProductChange(this)">
                                    <option value="">— Chọn hàng hóa —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-uom="{{ $p->uom?->name }}"
                                        data-uom-id="{{ $p->uom_id }}"
                                        data-tracking="{{ (int) ($p->tracking_type ?? 1) }}"
                                        {{ (string) $productId === (string) $p->id ? 'selected' : '' }}>
                                        {{ $p->code }} — {{ $p->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][uom_id]" class="uom-hidden"
                                    value="{{ $uomId }}">
                                <span class="uom-label text-body-secondary small">{{ $uomName }}</span>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                    name="details[{{ $i }}][expected_qty]" value="{{ $expectedQty }}" min="0.001"
                                    step="0.001" required oninput="updateTotals()" onchange="onExpectedQtyChange(this)">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end actual-qty-input"
                                    name="details[{{ $i }}][actual_qty]" value="{{ $actualQty }}" min="0" step="0.001"
                                    {{ in_array($tracking, [3,4]) ? 'readonly' : '' }}>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="details[{{ $i }}][location_id]">
                                    <option value="">— Chọn —</option>
                                    @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}"
                                        {{ (string) $locationId === (string) $loc->id ? 'selected' : '' }}>
                                        {{ $loc->code }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            {{-- Lot field --}}
                            <td>
                                <input type="text"
                                    class="form-control form-control-sm lot-input {{ in_array($tracking, [1,3]) ? 'bg-body-secondary' : '' }}"
                                    name="details[{{ $i }}][lot_number]" value="{{ $lotNumber }}"
                                    placeholder="{{ in_array($tracking, [1,3]) ? '—' : 'Số lot' }}" maxlength="100"
                                    {{ in_array($tracking, [1,3]) ? 'readonly' : '' }}
                                    {{ $tracking === 4 ? 'onchange="autoFillLot(this)"' : '' }}>
                            </td>
                            {{-- Serial field --}}
                            <td>
                                <input type="text"
                                    class="form-control form-control-sm serial-input {{ in_array($tracking, [1,2]) ? 'bg-body-secondary' : '' }}"
                                    name="details[{{ $i }}][serial_number]" value="{{ $serialNumber }}"
                                    placeholder="{{ in_array($tracking, [1,2]) ? '—' : 'Mã serial' }}" maxlength="100"
                                    {{ in_array($tracking, [1,2]) ? 'readonly' : '' }}>
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm"
                                    name="details[{{ $i }}][expiry_date]" value="{{ $expiryDate }}">
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

                    </tbody>
                </table>
            </div>

            {{-- Empty state --}}
            <div id="emptyDetail" class="text-center text-body-secondary py-4" style="display:none">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Chưa có hàng hóa nào.
                <br>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow()">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                    </svg>
                    Thêm dòng đầu tiên
                </button>
            </div>
        </div>

        <div class="card-footer py-2 text-body-secondary small">
            <span>Tổng dòng: <strong id="rowCount">{{ $isEdit ? $receipt->details->count() : 0 }}</strong></span>
        </div>
    </div>

    {{-- ── NÚT LƯU ── --}}
    <div class="d-flex gap-2 justify-content-end mt-3">
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Hủy</a>
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
            {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu nhập' }}
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const LOCATIONS = @json($locationsJson);

// TRACKING constants (mirrors PHP)
const TRACKING_NONE = 1;
const TRACKING_LOT = 2;
const TRACKING_SERIAL = 3;
const TRACKING_LOT_AND_SERIAL = 4;

let rowIndex = <?php echo $rows->count(); ?>;

// ── Áp tracking lên một <tr> ──────────────────────────────────────
function applyTracking(tr, tracking) {
    const lotInput = tr.querySelector('.lot-input');
    const serialInput = tr.querySelector('.serial-input');
    const actualInput = tr.querySelector('.actual-qty-input');
    if (!lotInput || !serialInput) return;

    // Reset
    [lotInput, serialInput].forEach(el => {
        el.readOnly = false;
        el.classList.remove('bg-body-secondary', 'is-invalid');
    });

    switch (tracking) {
        case TRACKING_NONE:
            // Khóa cả 2
            lotInput.readOnly = true;
            lotInput.value = '';
            lotInput.placeholder = '—';
            serialInput.readOnly = true;
            serialInput.value = '';
            serialInput.placeholder = '—';
            lotInput.classList.add('bg-body-secondary');
            serialInput.classList.add('bg-body-secondary');
            if (actualInput) {
                actualInput.readOnly = false;
                actualInput.classList.remove('bg-body-secondary');
            }
            break;

        case TRACKING_LOT:
            // Mở lot, khóa serial
            lotInput.placeholder = 'Số lot / batch';
            serialInput.readOnly = true;
            serialInput.value = '';
            serialInput.placeholder = '—';
            serialInput.classList.add('bg-body-secondary');
            if (actualInput) {
                actualInput.readOnly = false;
                actualInput.classList.remove('bg-body-secondary');
            }
            break;

        case TRACKING_SERIAL:
            // Khóa lot, mở serial; actual_qty mặc định = 1, cho phép sửa
            lotInput.readOnly = true;
            lotInput.value = '';
            lotInput.placeholder = '—';
            lotInput.classList.add('bg-body-secondary');
            serialInput.placeholder = 'Mã serial';
            if (actualInput && !actualInput.value) {
                actualInput.value = 1;
            }
            if (actualInput) {
                actualInput.readOnly = false;
                actualInput.classList.remove('bg-body-secondary');
            }
            break;

        case TRACKING_LOT_AND_SERIAL:
            // Mở cả 2; actual_qty mặc định = 1, cho phép sửa
            lotInput.placeholder = 'Số lot';
            serialInput.placeholder = 'Mã serial';
            if (actualInput && !actualInput.value) {
                actualInput.value = 1;
            }
            if (actualInput) {
                actualInput.readOnly = false;
                actualInput.classList.remove('bg-body-secondary');
            }
            break;
    }
}

// ── Khi chọn sản phẩm ─────────────────────────────────────────────
function onProductChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const tr = sel.closest('tr');
    const tracking = parseInt(opt.dataset.tracking) || TRACKING_NONE;

    tr.querySelector('.uom-label').textContent = opt.dataset.uom || '—';
    tr.querySelector('.uom-hidden').value = opt.dataset.uomId || '';

    applyTracking(tr, tracking);
}

// ── Auto-fill lot cho các dòng cùng sản phẩm (tracking=4) ─────────
function autoFillLot(lotInput) {
    const tr = lotInput.closest('tr');
    const sel = tr.querySelector('.product-select');
    const prodId = sel.value;
    const lotValue = lotInput.value.trim();
    if (!prodId || !lotValue) return;

    // Điền lot_number vào tất cả dòng cùng product_id, cùng tracking=4
    document.querySelectorAll('#detailBody tr').forEach(row => {
        const rowSel = row.querySelector('.product-select');
        if (!rowSel || rowSel.value !== prodId || row === tr) return;
        const opt = rowSel.options[rowSel.selectedIndex];
        if (parseInt(opt.dataset.tracking) !== TRACKING_LOT_AND_SERIAL) return;
        const rowLot = row.querySelector('.lot-input');
        if (rowLot && !rowLot.readOnly && !rowLot.value.trim()) {
            rowLot.value = lotValue;
        }
    });
}

// ── Khi nhập SL dự kiến (tự nhân dòng cho Serial) ─────────────────
function onExpectedQtyChange(input) {
    updateTotals();
    const tr = input.closest('tr');
    const sel = tr.querySelector('.product-select');
    const opt = sel.options[sel.selectedIndex];
    const tracking = parseInt(opt.dataset.tracking) || TRACKING_NONE;
    const qty = parseInt(input.value) || 1;

    if (!([TRACKING_SERIAL, TRACKING_LOT_AND_SERIAL].includes(tracking)) || qty <= 1) return;

    const locVal = tr.querySelector('select[name$="[location_id]"]').value;
    const expiryVal = tr.querySelector('input[name$="[expiry_date]"]').value;
    const lotVal = tr.querySelector('.lot-input')?.value ?? '';
    const prodVal = sel.value;

    input.value = 1;

    for (let n = 1; n < qty; n++) {
        document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
        const newTr = document.getElementById('detailBody').lastElementChild;
        rowIndex++;

        const newSel = newTr.querySelector('.product-select');
        newSel.value = prodVal;
        // Sync selected option rồi mới gọi onProductChange
        const targetOpt = Array.from(newSel.options).find(o => o.value === prodVal);
        if (targetOpt) targetOpt.selected = true;
        onProductChange(newSel);

        newTr.querySelector('select[name$="[location_id]"]').value = locVal;
        newTr.querySelector('input[name$="[expiry_date]"]').value = expiryVal;
        newTr.querySelector('input[name$="[expected_qty]"]').value = 1;

        // Điền sẵn lot nếu tracking=4
        if (tracking === TRACKING_LOT_AND_SERIAL && lotVal) {
            const newLot = newTr.querySelector('.lot-input');
            if (newLot) newLot.value = lotVal;
        }
    }

    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

// ── Template dòng mới ─────────────────────────────────────────────
function rowTemplate(i) {
    const productOptions = PRODUCTS.map(p =>
        `<option value="${p.id}" data-uom="${p.uom}" data-uom-id="${p.uom_id}" data-tracking="${p.tracking}">${p.code} — ${p.name}</option>`
    ).join('');

    const locationOptions = LOCATIONS.map(l =>
        `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''}</option>`
    ).join('');

    return `
<tr>
  <td class="text-center text-body-secondary small">${i + 1}</td>
  <td>
    <select class="form-select form-select-sm product-select" name="details[${i}][product_id]" required onchange="onProductChange(this)">
      <option value="">— Chọn hàng hóa —</option>
      ${productOptions}
    </select>
  </td>
  <td>
    <input type="hidden" name="details[${i}][uom_id]" class="uom-hidden" value="">
    <span class="uom-label text-body-secondary small">—</span>
  </td>
  <td>
    <input type="number" class="form-control form-control-sm text-end" name="details[${i}][expected_qty]"
           min="0.001" step="0.001" required placeholder="0"
           oninput="updateTotals()" onchange="onExpectedQtyChange(this)">
  </td>
  <td>
    <input type="number" class="form-control form-control-sm text-end actual-qty-input" name="details[${i}][actual_qty]"
           min="0" step="0.001" placeholder="0">
  </td>
  <td>
    <select class="form-select form-select-sm" name="details[${i}][location_id]">
      <option value="">— Chọn —</option>
      ${locationOptions}
    </select>
  </td>
  <td>
    <input type="text" class="form-control form-control-sm lot-input bg-body-secondary"
           name="details[${i}][lot_number]" placeholder="—" maxlength="100" readonly
           oninput="clearFieldError(this)" onchange="autoFillLot(this)">
  </td>
  <td>
    <input type="text" class="form-control form-control-sm serial-input bg-body-secondary"
           name="details[${i}][serial_number]" placeholder="—" maxlength="100" readonly
           oninput="clearFieldError(this)">
  </td>
  <td>
    <input type="date" class="form-control form-control-sm" name="details[${i}][expiry_date]">
  </td>
  <td>
    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)" title="Xóa dòng">
      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
    </button>
  </td>
</tr>`;
}

function addRow() {
    document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex++));
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
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
    const el = document.getElementById('totalExpected');
    if (!el) return;
    let total = 0;
    document.querySelectorAll('input[name$="[expected_qty]"]').forEach(inp => total += parseFloat(inp.value) || 0);
    el.textContent = total.toLocaleString('vi-VN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
    });
}

function clearFieldError(input) {
    input.classList.remove('is-invalid');
    if (!document.querySelector('.lot-input.is-invalid, .serial-input.is-invalid')) {
        document.getElementById('lotSerialAlertContainer').innerHTML = '';
    }
}

// ── Hiện/ẩn NCC ───────────────────────────────────────────────────
document.getElementById('receiptType').addEventListener('change', function() {
    document.getElementById('supplierGroup').style.display = this.value == '1' ? '' : 'none';
});

// ── Client-side validate Lot/Serial ───────────────────────────────
function validateLotSerial() {
    const errors = [];

    // ── Bước 1: validate từng dòng bắt buộc nhập lot/serial ──────────
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const sel = tr.querySelector('.product-select');
        const opt = sel?.options[sel.selectedIndex];
        const tracking = parseInt(opt?.dataset?.tracking) || TRACKING_NONE;
        const lotInput = tr.querySelector('.lot-input');
        const serialInput = tr.querySelector('.serial-input');
        [lotInput, serialInput].forEach(el => el?.classList.remove('is-invalid'));

        if (tracking === TRACKING_LOT && !lotInput.value.trim()) {
            lotInput.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô</strong> — chưa nhập Số Lot.`);
        } else if (tracking === TRACKING_SERIAL && !serialInput.value.trim()) {
            serialInput.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo <strong>Serial</strong> — chưa nhập Mã Serial.`);
        } else if (tracking === TRACKING_LOT_AND_SERIAL) {
            if (!lotInput.value.trim()) {
                lotInput.classList.add('is-invalid');
                errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô+Serial</strong> — chưa nhập Số Lot.`);
            }
            if (!serialInput.value.trim()) {
                serialInput.classList.add('is-invalid');
                errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô+Serial</strong> — chưa nhập Mã Serial.`);
            }
        }
    });

    // ── Bước 2: kiểm tra serial trùng trong cùng phiếu (theo product_id) ──
    const serialMap = {}; // { product_id: { serial_value: rowIndex } }
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const sel = tr.querySelector('.product-select');
        const productId = sel?.value;
        const serialInput = tr.querySelector('.serial-input');
        const serialVal = serialInput?.value.trim();
        if (!productId || !serialVal) return;

        if (!serialMap[productId]) serialMap[productId] = {};
        if (serialMap[productId][serialVal] !== undefined) {
            serialInput.classList.add('is-invalid');
            const firstRow = serialMap[productId][serialVal] + 1;
            errors.push(
                `Dòng ${i+1}: Số Serial <strong>"${serialVal}"</strong> đã nhập ở dòng ${firstRow} (cùng sản phẩm).`
            );
        } else {
            serialMap[productId][serialVal] = i;
        }
    });

    // ── Bước 3: LotAndSerial — các dòng cùng product phải dùng cùng 1 lot ──
    const lotMap = {}; // { product_id: { lot_value: rowIndex } }
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const sel = tr.querySelector('.product-select');
        const opt = sel?.options[sel.selectedIndex];
        const tracking = parseInt(opt?.dataset?.tracking) || TRACKING_NONE;
        if (tracking !== TRACKING_LOT_AND_SERIAL) return;

        const productId = sel?.value;
        const lotInput = tr.querySelector('.lot-input');
        const lotVal = lotInput?.value.trim();
        if (!productId || !lotVal) return;

        if (!lotMap[productId]) lotMap[productId] = null;

        if (lotMap[productId] === null) {
            lotMap[productId] = {
                value: lotVal,
                row: i
            };
        } else if (lotMap[productId].value !== lotVal) {
            lotInput.classList.add('is-invalid');
            const firstRow = lotMap[productId].row + 1;
            errors.push(`Nhiều serial trong cùng 1 lô thì nhập cùng mã lot.`);
        }
    });

    return errors;
}

document.getElementById('receiptForm').addEventListener('submit', function(e) {
    const errors = validateLotSerial();
    if (!errors.length) return;

    e.preventDefault();
    const container = document.getElementById('lotSerialAlertContainer');
    const ul = errors.map(msg => `<li>${msg}</li>`).join('');
    container.innerHTML = `
        <div class="alert alert-danger alert-dismissible mx-3 mt-3 mb-0" role="alert">
            <strong>Vui lòng kiểm tra lại thông tin Lot / Serial:</strong>
            <ul class="mb-0 mt-1">${ul}</ul>
            <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
        </div>`;
    container.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
    document.querySelector('.lot-input.is-invalid, .serial-input.is-invalid')?.focus();
});

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('receiptType').value;
    if (type != '1') document.getElementById('supplierGroup').style.display = 'none';

    document.querySelectorAll('#detailBody tr').forEach(tr => {
        const sel = tr.querySelector('.product-select');
        if (sel?.value) onProductChange(sel);
    });

    toggleEmptyState();
    updateTotals();

    <?php if ($rows->count() === 0): ?>
    addRow();
    <?php endif; ?>
});
</script>
@endpush
