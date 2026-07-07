@extends('layouts.app')

@section('title', (isset($issue) ? 'Chỉnh sửa phiếu xuất' : 'Thêm phiếu xuất'))

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('stock-movements.index') }}">Nhập/Xuất kho</a></li>
<li class="breadcrumb-item active">{{ isset($issue) ? $issue->code : 'Thêm phiếu xuất' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($issue);
$action = $isEdit ? route('issues.update', $issue->id) : route('issues.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Chỉnh sửa phiếu xuất' : 'Thêm phiếu xuất' }}</h4>
    </div>
    <a href="{{ route('stock-movements.index') }}" class="btn btn-secondary">
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="issueForm">
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

                {{-- Kho xuất giữ mặc định là kho đầu tiên, không hiển thị input --}}
                <input type="hidden" name="warehouse_id" value="{{ old('warehouse_id', $issue->warehouse_id ?? optional($warehouses->first())->id) }}">

                <div class="col-md-3">
                    <label class="form-label mb-1">Mã phiếu</label>
                    <input type="text"
                        class="form-control text-uppercase @error('code') is-invalid @enderror"
                        name="code" value="{{ old('code', $issue->code ?? '') }}" placeholder="Tự động"
                        maxlength="50" {{ $isEdit ? 'readonly' : '' }}>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Ngày xuất <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                        name="issue_date"
                        value="{{ old('issue_date', isset($issue->issue_date) ? \Carbon\Carbon::parse($issue->issue_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label mb-1">Ghi chú</label>
                    <input type="text" class="form-control" name="note"
                        value="{{ old('note', $issue->note ?? '') }}" maxlength="500" placeholder="Ghi chú">
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
            <button type="button" class="btn btn-sm btn-primary" onclick="addRow()">
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
                            <th style="width:6%">Xuất <span class="text-danger">*</span></th>
                            <th style="width:6%">Thực xuất</th>
                            <th style="width:10%">Vị trí <span class="text-danger">*</span></th>
                            <th style="width:10%">Người nhận <span class="text-danger">*</span></th>
                            <th style="width:6%">SN</th>
                            <th style="width:6%">Lô</th>
                            <th style="width:6%">Sê-ri</th>
                            <th style="width:6%">Kho phụ</th>
                            <th style="min-width:120px">Ghi chú</th>
                            <th style="width:2%"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">

                        @php
                        $detailsOld = old('details');
                        if ($detailsOld) {
                        $rows = collect($detailsOld);
                        } elseif ($isEdit) {
                        $rows = $issue->details;
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
                        $tracking = (int) ($product?->tracking_type?->value ?? 1);
                        $uomId = $detail['uom_id'] ?? ($product->uom_id ?? '');
                        $uomName = $product->uom?->name ?? '-';
                        $expectedQty = isset($detail['expected_qty']) ? $detail['expected_qty'] + 0 : '';
                        $actualQty = isset($detail['actual_qty']) ? $detail['actual_qty'] + 0 : '';
                        $locationId = $detail['location_id'] ?? '';
                        $receiverId = $detail['receiver_id'] ?? '';
                        $snId = $detail['sn_id'] ?? '';
                        $lotNumber = $detail['lot_number'] ?? '';
                        $serialNumber = $detail['serial_number'] ?? '';
                        $subWarehouse = $detail['sub_warehouse'] ?? '';
                        $note = $detail['note'] ?? '';
                        } else {
                        // Dữ liệu từ phiếu đang sửa
                        $productId = $detail->product_id;
                        $product = $detail->product;
                        $tracking = (int) ($product?->tracking_type?->value ?? 1);
                        $uomId = $detail->uom_id;
                        $uomName = $detail->uom?->name ?? '-';
                        $expectedQty = $detail->expected_qty + 0;
                        $actualQty = $detail->actual_qty + 0;
                        $locationId = $detail->location_id;
                        $receiverId = $detail->receiver_id;
                        $snId = $detail->sn_id;
                        $lotNumber = $detail->lot?->lot_number ?? '';
                        $serialNumber = $detail->serial?->serial_number ?? '';
                        $subWarehouse = $detail->sub_warehouse ?? '';
                        $note = $detail->note ?? '';
                        }
                        @endphp
                        <tr>
                            <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][product_id]"
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
                                <input type="hidden" name="details[{{ $i }}][uom_id]" class="uom-hidden"
                                    value="{{ $uomId }}">
                                <span class="uom-label text-body-secondary small">{{ $uomName }}</span>
                            </td>
                            <td>
                                <input type="number" class="form-control text-end"
                                    name="details[{{ $i }}][expected_qty]" value="{{ $expectedQty }}" min="0"
                                    step="1" required oninput="updateTotals()" onchange="onExpectedQtyChange(this)">
                            </td>
                            <td>
                                <input type="number" class="form-control text-end actual-qty-input"
                                    name="details[{{ $i }}][actual_qty]" value="{{ $actualQty }}" min="0" step="1"
                                    {{ in_array($tracking, [3,4]) ? 'readonly' : '' }}>
                            </td>
                            <td>
                                @php $selLoc = $locations->firstWhere('id', (int) $locationId); @endphp
                                <input type="hidden" name="details[{{ $i }}][location_id]"
                                    class="location-id-hidden" value="{{ $locationId }}">
                                <input type="text" class="form-control location-input" list="locationDatalist"
                                    value="{{ $selLoc ? $selLoc->code.($selLoc->name ? ' - '.$selLoc->name : '') : '' }}"
                                    placeholder="Nhập hoặc chọn" autocomplete="off"
                                    oninput="onLocationInput(this)" required>
                            </td>
                            <td>
                                @php $selEmp = $employees->firstWhere('id', (int) $receiverId); @endphp
                                <input type="hidden" name="details[{{ $i }}][receiver_id]"
                                    class="receiver-id-hidden" value="{{ $receiverId }}">
                                <input type="text" class="form-control receiver-input" list="employeeDatalist"
                                    value="{{ $selEmp ? $selEmp->code.' - '.$selEmp->name : '' }}"
                                    placeholder="Nhập hoặc chọn" autocomplete="off"
                                    oninput="onReceiverInput(this)" required>
                            </td>
                            <td>
                                <select class="form-select" name="details[{{ $i }}][sn_id]">
                                    <option value="">- Chọn -</option>
                                    @foreach($sns as $s)
                                    <option value="{{ $s->id }}"
                                        {{ (string) $snId === (string) $s->id ? 'selected' : '' }}>
                                        {{ $s->code }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            {{-- Lot field --}}
                            <td>
                                <input type="text"
                                    class="form-control lot-input {{ in_array($tracking, [1,3]) ? 'bg-body-secondary' : '' }}"
                                    name="details[{{ $i }}][lot_number]" value="{{ $lotNumber }}"
                                    placeholder="{{ in_array($tracking, [1,3]) ? '-' : 'Số lot' }}" maxlength="100"
                                    {{ in_array($tracking, [1,3]) ? 'readonly' : '' }}
                                    {{ $tracking === 4 ? 'onchange="autoFillLot(this)"' : '' }}>
                            </td>
                            {{-- Serial field --}}
                            <td>
                                <input type="text"
                                    class="form-control serial-input {{ in_array($tracking, [1,2]) ? 'bg-body-secondary' : '' }}"
                                    name="details[{{ $i }}][serial_number]" value="{{ $serialNumber }}"
                                    placeholder="{{ in_array($tracking, [1,2]) ? '-' : 'Mã serial' }}" maxlength="100"
                                    {{ in_array($tracking, [1,2]) ? 'readonly' : '' }}>
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][sub_warehouse]" value="{{ $subWarehouse }}"
                                    maxlength="50">
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][note]" value="{{ $note }}"
                                    placeholder="Ghi chú" maxlength="500">
                            </td>
                            <td class="text-end pe-3">
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
                Tổng dòng: <strong id="rowCount">{{ $isEdit ? $issue->details->count() : 0 }}</strong>
            </small>
        </div>
    </div>

    {{-- ── NÚT LƯU ── --}}
    <div class="d-flex gap-2 justify-content-end mt-3">
        <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary">Hủy</a>
        @if(!$isEdit)
        <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
            Lưu & Thêm mới
        </button>
        @endif
        <button type="submit" class="btn btn-primary" name="action" value="save">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
            </svg>
            Lưu
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
            lotInput.placeholder = '-';
            serialInput.readOnly = true;
            serialInput.value = '';
            serialInput.placeholder = '-';
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
            serialInput.placeholder = '-';
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
            lotInput.placeholder = '-';
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
        input.dataset.tracking = p.tracking_type;

        tr.querySelector('.uom-label').textContent = p.uom || '-';
        tr.querySelector('.uom-hidden').value = p.uom_id || '';
        tr.querySelector('.tskt-label').value = p.specification ?? '';

        applyTracking(tr, parseInt(p.tracking_type) || TRACKING_NONE);
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

// ── Auto-fill lot cho các dòng cùng sản phẩm (tracking=4) ─────────
function autoFillLot(lotInput) {
    const tr = lotInput.closest('tr');
    const prodId = tr.querySelector('.product-id-hidden')?.value;
    const lotValue = lotInput.value.trim();
    if (!prodId || !lotValue) return;

    // Điền lot_number vào tất cả dòng cùng product_id, cùng tracking=4
    document.querySelectorAll('#detailBody tr').forEach(row => {
        const rowProdId = row.querySelector('.product-id-hidden')?.value;
        if (!rowProdId || rowProdId !== prodId || row === tr) return;
        const rowProductInput = row.querySelector('.product-input');
        if (parseInt(rowProductInput?.dataset?.tracking) !== TRACKING_LOT_AND_SERIAL) return;
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
    const productInput = tr.querySelector('.product-input');
    const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_NONE;
    const qty = parseInt(input.value) || 1;

    if (!([TRACKING_SERIAL, TRACKING_LOT_AND_SERIAL].includes(tracking)) || qty <= 1) return;

    const locVal = tr.querySelector('.location-input').value;
    const locIdVal = tr.querySelector('.location-id-hidden').value;
    const receiverVal = tr.querySelector('.receiver-input').value;
    const receiverIdVal = tr.querySelector('.receiver-id-hidden').value;
    const snVal = tr.querySelector('select[name$="[sn_id]"]').value;
    const subWarehouseVal = tr.querySelector('input[name$="[sub_warehouse]"]').value;
    const noteVal = tr.querySelector('input[name$="[note]"]').value;
    const lotVal = tr.querySelector('.lot-input')?.value ?? '';
    const prodVal = productInput.value;

    input.value = 1;

    for (let n = 1; n < qty; n++) {
        document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
        const newTr = document.getElementById('detailBody').lastElementChild;
        rowIndex++;

        const newProductInput = newTr.querySelector('.product-input');
        newProductInput.value = prodVal;
        onProductInput(newProductInput);

        newTr.querySelector('.location-input').value = locVal;
        newTr.querySelector('.location-id-hidden').value = locIdVal;
        newTr.querySelector('.receiver-input').value = receiverVal;
        newTr.querySelector('.receiver-id-hidden').value = receiverIdVal;
        newTr.querySelector('select[name$="[sn_id]"]').value = snVal;
        newTr.querySelector('input[name$="[sub_warehouse]"]').value = subWarehouseVal;
        newTr.querySelector('input[name$="[note]"]').value = noteVal;
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
    const snOptions = SNS.map(s =>
        `<option value="${s.id}">${s.code}</option>`
    ).join('');

    return `
<tr>
  <td class="text-center text-body-secondary small">${i + 1}</td>
  <td>
    <input type="hidden" name="details[${i}][product_id]" class="product-id-hidden" value="">
    <input type="text" class="form-control product-input" list="productDatalist"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onProductInput(this)" required>
  </td>
  <td>
    <input type="text" class="form-control tskt-label" placeholder="-" readonly tabindex="-1">
  </td>
  <td>
    <input type="hidden" name="details[${i}][uom_id]" class="uom-hidden" value="">
    <span class="uom-label text-body-secondary small">-</span>
  </td>
  <td>
    <input type="number" class="form-control text-end" name="details[${i}][expected_qty]"
           min="0" step="1" required placeholder="0"
           oninput="updateTotals()" onchange="onExpectedQtyChange(this)">
  </td>
  <td>
    <input type="number" class="form-control text-end actual-qty-input" name="details[${i}][actual_qty]"
           min="0" step="1" placeholder="0">
  </td>
  <td>
    <input type="hidden" name="details[${i}][location_id]" class="location-id-hidden" value="">
    <input type="text" class="form-control location-input" list="locationDatalist"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onLocationInput(this)" required>
  </td>
  <td>
    <input type="hidden" name="details[${i}][receiver_id]" class="receiver-id-hidden" value="">
    <input type="text" class="form-control receiver-input" list="employeeDatalist"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onReceiverInput(this)" required>
  </td>
  <td>
    <select class="form-select" name="details[${i}][sn_id]">
      <option value="">- Chọn -</option>
      ${snOptions}
    </select>
  </td>
  <td>
    <input type="text" class="form-control lot-input bg-body-secondary"
           name="details[${i}][lot_number]" placeholder="-" maxlength="100" readonly
           oninput="clearFieldError(this)" onchange="autoFillLot(this)">
  </td>
  <td>
    <input type="text" class="form-control serial-input bg-body-secondary"
           name="details[${i}][serial_number]" placeholder="-" maxlength="100" readonly
           oninput="clearFieldError(this)">
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][sub_warehouse]" maxlength="50">
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][note]" placeholder="Ghi chú" maxlength="500">
  </td>
  <td class="text-end pe-3">
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

// ── Client-side validate Lot/Serial ───────────────────────────────
function validateLotSerial() {
    const errors = [];

    // ── Bước 1: validate từng dòng bắt buộc nhập lot/serial ──────────
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const productInput = tr.querySelector('.product-input');
        const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_NONE;
        const lotInput = tr.querySelector('.lot-input');
        const serialInput = tr.querySelector('.serial-input');
        [lotInput, serialInput].forEach(el => el?.classList.remove('is-invalid'));

        if (tracking === TRACKING_LOT && !lotInput.value.trim()) {
            lotInput.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô</strong> - chưa nhập Số Lot.`);
        } else if (tracking === TRACKING_SERIAL && !serialInput.value.trim()) {
            serialInput.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo <strong>Serial</strong> - chưa nhập Mã Serial.`);
        } else if (tracking === TRACKING_LOT_AND_SERIAL) {
            if (!lotInput.value.trim()) {
                lotInput.classList.add('is-invalid');
                errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô+Serial</strong> - chưa nhập Số Lot.`);
            }
            if (!serialInput.value.trim()) {
                serialInput.classList.add('is-invalid');
                errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô+Serial</strong> - chưa nhập Mã Serial.`);
            }
        }
    });

    // ── Bước 2: kiểm tra serial trùng trong cùng phiếu (theo product_id) ──
    const serialMap = {}; // { product_id: { serial_value: rowIndex } }
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const productId = tr.querySelector('.product-id-hidden')?.value;
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

    // ── Bước 3: LotAndSerial - các dòng cùng product phải dùng cùng 1 lot ──
    const lotMap = {}; // { product_id: { lot_value: rowIndex } }
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const productInput = tr.querySelector('.product-input');
        const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_NONE;
        if (tracking !== TRACKING_LOT_AND_SERIAL) return;

        const productId = tr.querySelector('.product-id-hidden')?.value;
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

document.getElementById('issueForm').addEventListener('submit', function(e) {
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
</script>
@endpush