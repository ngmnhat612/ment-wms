@extends('layouts.app')

@section('title', (isset($receipt) ? 'Chỉnh sửa phiếu nhập' : 'Thêm phiếu nhập'))

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('stock-movements.index') }}">Nhập/Xuất kho</a></li>
<li class="breadcrumb-item active">{{ isset($receipt) ? $receipt->code : 'Thêm phiếu nhập' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($receipt);
$action = $isEdit ? route('receipts.update', $receipt->id) : route('receipts.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Chỉnh sửa phiếu nhập' : 'Thêm phiếu nhập' }}</h4>
    </div>
    <a href="{{ $isEdit ? route('receipts.show', $receipt->id) : route('stock-movements.index') }}" class="btn btn-outline-secondary">
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="receiptForm">
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <strong>Vui lòng kiểm tra lại thông tin:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
    @endif
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── THÔNG TIN PHIẾU ── --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
            Thông tin phiếu
        </div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Loại nhập giữ mặc định = 1 (Từ nhà cung cấp), không hiển thị input --}}
                <input type="hidden" name="warehouse_id" value="{{ old('warehouse_id', $receipt->warehouse_id ?? optional($warehouses->first())->id) }}">

                <div class="col-md-3">
                    <label class="form-label mb-1">Mã phiếu</label>
                    <input type="text"
                        class="form-control text-uppercase @error('code') is-invalid @enderror"
                        name="code" value="{{ old('code', $receipt->code ?? '') }}" placeholder="Tự động"
                        maxlength="50" {{ $isEdit ? 'readonly' : '' }}>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Phiếu liên kết</label>
                    <input type="text"
                        class="form-control @error('stock_in_request_id') is-invalid @enderror"
                        id="stock_in_request_code"
                        list="stockInRequestList"
                        placeholder="Nhập hoặc chọn"
                        autocomplete="off"
                        value="{{ old('stock_in_request_code', ($receipt->stockInRequest->code ?? '')) }}"
                        {{ $isEdit && isset($receipt->stock_in_request_id) ? 'readonly' : '' }}>

                    <datalist id="stockInRequestList">
                        @foreach($stockInRequests ?? [] as $request)
                            <option data-id="{{ $request->id }}" value="{{ $request->code }}"></option>
                        @endforeach
                    </datalist>

                    <input type="hidden" name="stock_in_request_id" id="stock_in_request_id"
                        value="{{ old('stock_in_request_id', $receipt->stock_in_request_id ?? '') }}">

                    @error('stock_in_request_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Ngày nhập <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('receipt_date') is-invalid @enderror"
                        name="receipt_date"
                        value="{{ old('receipt_date', isset($receipt->receipt_date) ? \Carbon\Carbon::parse($receipt->receipt_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('receipt_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Ghi chú</label>
                    <input type="text" class="form-control" name="note"
                        value="{{ old('note', $receipt->note ?? '') }}" maxlength="500" placeholder="Ghi chú">
                </div>

            </div>
        </div>
    </div>

    {{-- Datalist dùng chung cho các ô gõ-để-tìm bên dưới --}}
    <datalist id="productDatalist">
        @foreach($products as $p)
        <option value="{{ $p->code }} - {{ $p->name }}"></option>
        @endforeach
    </datalist>
    <datalist id="locationDatalist">
        @foreach($locations as $loc)
        <option value="{{ $loc->code }}{{ $loc->name ? ' - '.$loc->name : '' }}"></option>
        @endforeach
    </datalist>
    <datalist id="employeeDatalist">
        @foreach($employees as $e)
        <option value="{{ $e->code }} - {{ $e->name }}"></option>
        @endforeach
    </datalist>

    {{-- ── CHI TIẾT PHIẾU ── --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" style="min-height:44px">
            <span class="fw-semibold">
                Chi tiết phiếu
            </span>
            <button type="button" class="btn btn-sm btn-primary" onclick="addRow()" title="Thêm dòng">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
            </button>
        </div>

        <div class="card-body p-0">
            <div id="lotSerialAlertContainer"></div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="detailTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:2%">#</th>
                            <th style="min-width:180px">Vật tư <span class="text-danger">*</span></th>
                            <th style="width:8%">TSKT</th>
                            <th style="width:4%">ĐVT</th>
                            <th style="width:6%">Nhập <span class="text-danger">*</span></th>
                            <th style="width:6%">Thực nhập</th>
                            <th style="width:10%">Vị trí <span class="text-danger">*</span></th>
                            <th style="width:10%">Người nhận <span class="text-danger">*</span></th>
                            <th style="width:6%">SN</th>
                            <th style="width:6%">Lô</th>
                            <th style="min-width:200px">Sê-ri <span class="lot-serial-hint text-body-secondary fw-normal small"></span></th>
                            <th style="width:6%">Kho phụ</th>
                            <th style="min-width:120px">Ghi chú</th>
                            <th style="width:2%"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">

                        @php
                        // Mỗi hàng UI giờ = 1 LINE (không còn detail phẳng cũ).
                        // Khi sửa phiếu: mỗi $receipt->lines là 1 hàng; serial_numbers
                        // được ghép lại từ tất cả $line->details->serial (join bằng space).
                        $linesOld = old('lines');
                        if ($linesOld) {
                            $rows = collect($linesOld);
                        } elseif ($isEdit) {
                            $rows = $receipt->lines;
                        } else {
                            $rows = collect();
                        }
                        @endphp

                        @foreach($rows as $i => $lineRow)
                        @php
                        if (is_array($lineRow)) {
                            // Dữ liệu cũ (old input) sau khi validate lỗi — đã là FLAT lines[i]
                            $productId = $lineRow['product_id'] ?? '';
                            $product = $products->firstWhere('id', (int) $productId);
                            $tracking = (int) ($product?->tracking_type?->value ?? 1);
                            $uomId = $lineRow['uom_id'] ?? ($product->uom_id ?? '');
                            $uomName = $product->uom?->name ?? '-';
                            $expectedQty = isset($lineRow['expected_qty']) ? $lineRow['expected_qty'] + 0 : '';
                            $actualQty = isset($lineRow['actual_qty']) ? $lineRow['actual_qty'] + 0 : '';
                            $locationId = $lineRow['location_id'] ?? '';
                            $receiverId = $lineRow['receiver_id'] ?? '';
                            $snId = $lineRow['sn_id'] ?? '';
                            $lotNumber = $lineRow['lot_number'] ?? '';
                            $serialNumbers = $lineRow['serial_numbers'] ?? '';
                            $subWarehouse = $lineRow['sub_warehouse'] ?? '';
                            $note = $lineRow['note'] ?? '';
                        } else {
                            // Dữ liệu từ phiếu đang sửa: $lineRow là 1 StockReceiptLine,
                            // đã eager-load 'details.location/lot/serial/receiver' ở Repository.
                            $productId = $lineRow->product_id;
                            $product = $lineRow->product;
                            $tracking = (int) ($product?->tracking_type?->value ?? 1);
                            $uomId = $lineRow->uom_id;
                            $uomName = $lineRow->uom?->name ?? '-';
                            $expectedQty = $lineRow->expected_qty + 0;
                            $firstDetail = $lineRow->details->first();
                            $actualQty = $tracking === 2
                                ? $lineRow->details->count()
                                : ($firstDetail->actual_qty ?? 0) + 0;
                            $locationId = $firstDetail->location_id ?? '';
                            $receiverId = $firstDetail->receiver_id ?? '';
                            $snId = $lineRow->sn_id;
                            $lotNumber = $firstDetail->lot?->lot_number ?? '';
                            // Ghép mọi serial của line này thành 1 chuỗi cách nhau space.
                            $serialNumbers = $lineRow->details->pluck('serial.serial_number')->filter()->implode(' ');
                            $subWarehouse = $firstDetail->sub_warehouse ?? '';
                            $note = $lineRow->note ?? '';
                        }
                        @endphp
                        <tr>
                            <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                            <td>
                                <input type="hidden" name="lines[{{ $i }}][product_id]"
                                    class="product-id-hidden" value="{{ $productId }}">
                                <input type="text" class="form-control product-input" list="productDatalist"
                                    value="{{ $product ? $product->code.' - '.$product->name : '' }}"
                                    placeholder="Nhập hoặc chọn" autocomplete="off"
                                    oninput="onProductInput(this)" required>
                            </td>
                            <td>
                                <input type="text" class="form-control tskt-label" value="{{ $product->specification ?? '' }}"
                                    placeholder="-" readonly tabindex="-1">
                            </td>
                            <td>
                                <input type="hidden" name="lines[{{ $i }}][uom_id]" class="uom-hidden"
                                    value="{{ $uomId }}">
                                <span class="uom-label text-body-secondary small">{{ $uomName }}</span>
                            </td>
                            <td>
                                <input type="number" class="form-control text-end"
                                    name="lines[{{ $i }}][expected_qty]" value="{{ $expectedQty }}" min="0"
                                    step="1" required oninput="updateTotals()">
                            </td>
                            <td>
                                <input type="number" class="form-control text-end actual-qty-input"
                                    name="lines[{{ $i }}][actual_qty]" value="{{ $actualQty }}" min="0" step="1"
                                    {{ $tracking === 2 ? 'readonly' : '' }}>
                            </td>
                            <td>
                                @php $selLoc = $locations->firstWhere('id', (int) $locationId); @endphp
                                <input type="hidden" name="lines[{{ $i }}][location_id]"
                                    class="location-id-hidden" value="{{ $locationId }}">
                                <input type="text" class="form-control location-input" list="locationDatalist"
                                    value="{{ $selLoc ? $selLoc->code.($selLoc->name ? ' - '.$selLoc->name : '') : '' }}"
                                    placeholder="Nhập hoặc chọn" autocomplete="off"
                                    oninput="onLocationInput(this)" required>
                            </td>
                            <td>
                                @php $selEmp = $employees->firstWhere('id', (int) $receiverId); @endphp
                                <input type="hidden" name="lines[{{ $i }}][receiver_id]"
                                    class="receiver-id-hidden" value="{{ $receiverId }}">
                                <input type="text" class="form-control receiver-input" list="employeeDatalist"
                                    value="{{ $selEmp ? $selEmp->code.' - '.$selEmp->name : '' }}"
                                    placeholder="Nhập hoặc chọn" autocomplete="off"
                                    oninput="onReceiverInput(this)" required>
                            </td>
                            <td>
                                <select class="form-select" name="lines[{{ $i }}][sn_id]">
                                    <option value="">- Chọn -</option>
                                    @foreach($sns as $s)
                                    <option value="{{ $s->id }}"
                                        {{ (string) $snId === (string) $s->id ? 'selected' : '' }}>
                                        {{ $s->code }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            {{-- Lot field: 1 lô dùng chung cho cả dòng (và mọi serial con).
                                 old_lot_id: lot_id ĐANG GÁN cho dòng này TRƯỚC KHI sửa (chỉ có
                                 giá trị ở chế độ Edit) — dùng để Service tái sử dụng lại đúng
                                 Lô cũ nếu người dùng XÓA TRẮNG ô Lô (không phải sinh Lô mới),
                                 tránh "nhảy cóc" số Lô khi không có gì thay đổi thực sự. --}}
                            <td>
                                <input type="hidden"
                                    name="lines[{{ $i }}][old_lot_id]"
                                    class="old-lot-id-hidden"
                                    value="{{ is_array($lineRow) ? ($lineRow['old_lot_id'] ?? '') : ($firstDetail->lot_id ?? '') }}">
                                <input type="number"
                                    class="form-control lot-input"
                                    name="lines[{{ $i }}][lot_number]" value="{{ $lotNumber }}"
                                    placeholder="Tự động" min="1" step="1"
                                    oninput="clearFieldError(this)">
                            </td>
                            {{-- Serial field: chuỗi text, mỗi mã cách nhau <space> --}}
                            <td>
                                <input type="text"
                                    class="form-control serial-input {{ $tracking === 1 ? 'bg-body-secondary' : '' }}"
                                    name="lines[{{ $i }}][serial_numbers]" value="{{ $serialNumbers }}"
                                    placeholder="{{ $tracking === 1 ? '-' : 'SN0001 SN0002 ...' }}"
                                    maxlength="5000" autocomplete="off"
                                    oninput="onSerialInput(this)"
                                    {{ $tracking === 1 ? 'readonly' : '' }}>
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="lines[{{ $i }}][sub_warehouse]" value="{{ $subWarehouse }}"
                                    maxlength="50" placeholder="Nhập">
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="lines[{{ $i }}][note]" value="{{ $note }}"
                                    placeholder="Ghi chú" maxlength="500">
                            </td>
                            <td class="text-end pe-3" style="align-items:center; justify-content:flex-end;">
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="removeRow(this)" title="Xóa dòng">
                                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>

            {{-- Empty state --}}
            <div id="emptyDetail" class="text-center text-body-secondary py-5" style="display:none">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list-rich') }}"></use>
                </svg>
                Chưa có vật tư nào.
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center py-2">
            <small class="text-body-secondary">
                Tổng dòng: <strong id="rowCount">{{ $isEdit ? $receipt->lines->count() : 0 }}</strong>
            </small>
        </div>
    </div>

    {{-- ── NÚT LƯU ── --}}
    <div class="d-flex gap-2 justify-content-end mt-3">
        @if(!$isEdit)
        <button type="submit" id="receiptSubmitBtnNew" class="btn btn-outline-primary" name="action" value="save_and_new">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <svg class="icon me-1 submit-icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
            <span class="submit-label">Lưu &amp; Thêm mới</span>
        </button>
        @endif
        <button type="submit" id="receiptSubmitBtnSave" class="btn btn-primary" name="action" value="save">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <svg class="icon me-1 submit-icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            <span class="submit-label">Lưu</span>
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const LOCATIONS = @json($locationsJson ?? []);
const EMPLOYEES = @json($employeesJson ?? []);
const SNS = @json($snsJson ?? []);

// TRACKING constants (mirrors PHP TrackingType enum)
const TRACKING_LOT = 1;
const TRACKING_LOT_AND_SERIAL = 2;

let rowIndex = <?php echo $rows->count(); ?>;
let submitting = false;

// ── Đếm số serial trong 1 chuỗi "SN0001 SN0002 ..." ────────────────
function countSerials(str) {
    return (str || '').trim().split(/\s+/).filter(Boolean).length;
}

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

    // Lô luôn mở, không bắt buộc — để trống sẽ tự sinh mã ở backend
    lotInput.placeholder = 'Tự động';

    switch (tracking) {
        case TRACKING_LOT:
            // Khóa serial; actual_qty nhập tay bình thường
            serialInput.readOnly = true;
            serialInput.value = '';
            serialInput.placeholder = '-';
            serialInput.classList.add('bg-body-secondary');
            if (actualInput) {
                actualInput.readOnly = false;
                actualInput.classList.remove('bg-body-secondary');
            }
            break;

        case TRACKING_LOT_AND_SERIAL:
            // Mở serial; actual_qty giờ luôn = số serial đếm được, khóa không cho tự gõ
            serialInput.placeholder = 'SN0001 SN0002 ...';
            if (actualInput) {
                actualInput.readOnly = true;
                actualInput.classList.add('bg-body-secondary');
                actualInput.value = countSerials(serialInput.value);
            }
            break;
    }
}

// ── Tìm dữ liệu theo nhãn hiển thị (dùng cho input + datalist) ─────
function findProductByLabel(label) {
    return PRODUCTS.find(p => `${p.code} - ${p.name}` === label);
}
function findLocationByLabel(label) {
    return LOCATIONS.find(l => l.code === label || `${l.code}${l.name ? ' - ' + l.name : ''}` === label);
}
function findEmployeeByLabel(label) {
    return EMPLOYEES.find(e => `${e.code} - ${e.name}` === label);
}

// ── Khi gõ/chọn Tên vật tư ─────────────────────────────────────────
function onProductInput(input) {
    const tr = input.closest('tr');
    const hidden = tr.querySelector('.product-id-hidden');
    const p = findProductByLabel(input.value.trim());

    if (p) {
        hidden.value = p.id;
        input.classList.remove('is-invalid');
        input.dataset.tracking = p.tracking;

        tr.querySelector('.uom-label').textContent = p.uom || '-';
        tr.querySelector('.uom-hidden').value = p.uom_id || '';
        tr.querySelector('.tskt-label').value = p.specification ?? '';

        applyTracking(tr, parseInt(p.tracking) || TRACKING_LOT);
    } else {
        hidden.value = '';
        delete input.dataset.tracking;
        input.classList.toggle('is-invalid', input.value.trim() !== '');
        tr.querySelector('.uom-label').textContent = '-';
        tr.querySelector('.uom-hidden').value = '';
        tr.querySelector('.tskt-label').value = '';
    }
}

// ── Khi gõ/chọn Vị trí ───────────────────────────────────────────
function onLocationInput(input) {
    const tr = input.closest('tr');
    const hidden = tr.querySelector('.location-id-hidden');
    const l = findLocationByLabel(input.value.trim());

    if (l) {
        hidden.value = l.id;
        input.classList.remove('is-invalid');
    } else {
        hidden.value = '';
        input.classList.toggle('is-invalid', input.value.trim() !== '');
    }
}

// ── Khi gõ/chọn Người nhận ─────────────────────────────────────────
function onReceiverInput(input) {
    const tr = input.closest('tr');
    const hidden = tr.querySelector('.receiver-id-hidden');
    const e = findEmployeeByLabel(input.value.trim());

    if (e) {
        hidden.value = e.id;
        input.classList.remove('is-invalid');
    } else {
        hidden.value = '';
        input.classList.toggle('is-invalid', input.value.trim() !== '');
    }
}

// ── Khi gõ chuỗi Sê-ri: tự cập nhật Thực nhập = số serial đếm được ──
function onSerialInput(input) {
    clearFieldError(input);
    const tr = input.closest('tr');
    const actualInput = tr.querySelector('.actual-qty-input');
    const productInput = tr.querySelector('.product-input');
    const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_LOT;

    if (tracking === TRACKING_LOT_AND_SERIAL && actualInput) {
        actualInput.value = countSerials(input.value);
    }
}

// ── Thêm dòng mới ──────────────────────────────────────────────────
function rowTemplate(i) {
    const snOptions = SNS.map(s =>
        `<option value="${s.id}">${s.code}</option>`
    ).join('');

    return `
<tr>
  <td class="text-center text-body-secondary small">${i + 1}</td>
  <td>
    <input type="hidden" name="lines[${i}][product_id]" class="product-id-hidden" value="">
    <input type="text" class="form-control product-input" list="productDatalist"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onProductInput(this)" required>
  </td>
  <td>
    <input type="text" class="form-control tskt-label" placeholder="-" readonly tabindex="-1">
  </td>
  <td>
    <input type="hidden" name="lines[${i}][uom_id]" class="uom-hidden" value="">
    <span class="uom-label text-body-secondary small">-</span>
  </td>
  <td>
    <input type="number" class="form-control text-end" name="lines[${i}][expected_qty]"
           min="0" step="1" required placeholder="0" oninput="updateTotals()">
  </td>
  <td>
    <input type="number" class="form-control text-end actual-qty-input" name="lines[${i}][actual_qty]"
           min="0" step="1" placeholder="0">
  </td>
  <td>
    <input type="hidden" name="lines[${i}][location_id]" class="location-id-hidden" value="">
    <input type="text" class="form-control location-input" list="locationDatalist"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onLocationInput(this)" required>
  </td>
  <td>
    <input type="hidden" name="lines[${i}][receiver_id]" class="receiver-id-hidden" value="">
    <input type="text" class="form-control receiver-input" list="employeeDatalist"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onReceiverInput(this)" required>
  </td>
  <td>
    <select class="form-select" name="lines[${i}][sn_id]">
      <option value="">- Chọn -</option>
      ${snOptions}
    </select>
  </td>
  <td>
    <input type="hidden" name="lines[${i}][old_lot_id]" class="old-lot-id-hidden" value="">
    <input type="number" class="form-control lot-input"
        name="lines[${i}][lot_number]" placeholder="Tự động" min="1" step="1"
        oninput="clearFieldError(this)">
  </td>
  <td>
    <input type="text" class="form-control serial-input bg-body-secondary"
           name="lines[${i}][serial_numbers]" placeholder="-" maxlength="5000" readonly
           oninput="onSerialInput(this)">
  </td>
  <td>
    <input type="text" class="form-control" name="lines[${i}][sub_warehouse]" maxlength="50" placeholder="Nhập">
  </td>
  <td>
    <input type="text" class="form-control" name="lines[${i}][note]" placeholder="Ghi chú" maxlength="500">
  </td>
  <td class="text-end pe-3" style="align-items:center; justify-content:flex-end;">
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" title="Xóa dòng">
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

// ── Client-side validate Lot/Serial (gương với StockReceiptRequest) ─
function validateLotSerial() {
    const errors = [];

    document.querySelectorAll('#detailBody tr').forEach(tr => {
        const lotInput = tr.querySelector('.lot-input');
        const serialInput = tr.querySelector('.serial-input');
        [lotInput, serialInput].forEach(el => el?.classList.remove('is-invalid'));
    });

    // ── Bước 1: Serial bắt buộc + actual_qty phải khớp số serial ────
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const productInput = tr.querySelector('.product-input');
        const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_LOT;
        if (tracking !== TRACKING_LOT_AND_SERIAL) return;

        const serialInput = tr.querySelector('.serial-input');
        const serials = (serialInput.value || '').trim().split(/\s+/).filter(Boolean);

        if (serials.length === 0) {
            serialInput.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô+Sê-ri</strong> — chưa nhập Mã Serial (cách nhau bằng dấu cách).`);
            return;
        }

        // Serial trùng NGAY TRONG chuỗi của chính dòng này
        const seenInRow = new Set();
        for (const s of serials) {
            if (seenInRow.has(s)) {
                serialInput.classList.add('is-invalid');
                errors.push(`Dòng ${i+1}: Mã Serial <strong>"${s}"</strong> bị lặp lại trong cùng dòng.`);
                break;
            }
            seenInRow.add(s);
        }
    });

    // ── Bước 2: Serial trùng giữa các dòng khác nhau (cùng product) ──
    const serialMap = {}; // { product_id: { serial_value: rowIndex } }
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const productId = tr.querySelector('.product-id-hidden')?.value;
        const serialInput = tr.querySelector('.serial-input');
        const serials = (serialInput?.value || '').trim().split(/\s+/).filter(Boolean);
        if (!productId) return;

        serials.forEach(serialVal => {
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
    });

    // ── Bước 3: Số Lô trùng — chỉ trùng trong CÙNG 1 sản phẩm mới là lỗi ──
    const lotNumberMap = {}; // { productId: { lotNumber: rowIndex } }
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const lotInput = tr.querySelector('.lot-input');
        const lotVal = lotInput?.value.trim();
        const productId = tr.querySelector('.product-id-hidden')?.value;
        if (!lotVal || !productId) return; // để trống -> backend tự sinh, bỏ qua

        if (!lotNumberMap[productId]) lotNumberMap[productId] = {};

        if (lotNumberMap[productId][lotVal] !== undefined) {
            lotInput.classList.add('is-invalid');
            const firstRow = lotNumberMap[productId][lotVal] + 1;
            errors.push(`Dòng ${i+1}: Số Lô <strong>"${lotVal}"</strong> đã dùng ở dòng ${firstRow} (cùng vật tư).`);
        } else {
            lotNumberMap[productId][lotVal] = i;
        }
    });

    return errors;
}

document.getElementById('receiptForm').addEventListener('submit', function(e) {
    const errors = validateLotSerial();
    if (errors.length) {
        e.preventDefault();
        const container = document.getElementById('lotSerialAlertContainer');
        const ul = errors.map(msg => `<li>${msg}</li>`).join('');
        container.innerHTML = `
            <div class="alert alert-danger alert-dismissible mx-3 mt-3 mb-3" role="alert">
                <strong>Vui lòng kiểm tra lại thông tin:</strong>
                <ul class="mb-0 mt-1">${ul}</ul>
                <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
            </div>`;
        container.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
        document.querySelector('.lot-input.is-invalid, .serial-input.is-invalid')?.focus();
        return; // Có lỗi → dừng, KHÔNG disable nút
    }

    // Không có lỗi Lot/Serial → mới cho phép disable nút và hiện "Đang lưu..."
    if (submitting) {
        e.preventDefault();
        return;
    }
    submitting = true;

    this.querySelectorAll('button[type="submit"]').forEach(function (btn) {
        btn.disabled = true;
        const spinner = btn.querySelector('.spinner-border');
        const icon    = btn.querySelector('.submit-icon');
        const label   = btn.querySelector('.submit-label');
        if (spinner) spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        if (label) label.textContent = 'Đang lưu...';
    });
});

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#detailBody tr').forEach(tr => {
        const productInput = tr.querySelector('.product-input');
        if (productInput?.value) onProductInput(productInput);
    });

    toggleEmptyState();
    updateTotals();

    <?php if ($rows->count() === 0): ?>
    addRow();
    <?php endif; ?>
});

(function () {
    const input  = document.getElementById('stock_in_request_code');
    const hidden = document.getElementById('stock_in_request_id');
    const list   = document.getElementById('stockInRequestList');

    function syncHiddenId() {
        const val = input.value.trim();
        const match = [...list.options].find(o => o.value === val);
        hidden.value = match ? match.dataset.id : '';
    }

    input.addEventListener('input', syncHiddenId);
    // Đồng bộ ngay khi load (trường hợp edit / old() đã có sẵn giá trị)
    syncHiddenId();
})();
</script>
@endpush