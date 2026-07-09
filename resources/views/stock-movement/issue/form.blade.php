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
    <a href="{{ $isEdit ? route('issues.show', $issue->id) : route('stock-movements.index') }}" class="btn btn-outline-secondary">
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
                    <label class="form-label mb-1">Phiếu liên kết</label>
                    <input type="text"
                        class="form-control @error('stock_out_request_id') is-invalid @enderror"
                        id="stock_out_request_code"
                        list="stockOutRequestList"
                        placeholder="Nhập hoặc chọn"
                        autocomplete="off"
                        value="{{ old('stock_out_request_code', ($issue->stockOutRequest->code ?? '')) }}"
                        {{ $isEdit && isset($issue->stock_out_request_id) ? 'readonly' : '' }}>

                    <datalist id="stockOutRequestList">
                        @foreach($stockOutRequests ?? [] as $request)
                            <option data-id="{{ $request->id }}" value="{{ $request->code }}"></option>
                        @endforeach
                    </datalist>

                    <input type="hidden" name="stock_out_request_id" id="stock_out_request_id"
                        value="{{ old('stock_out_request_id', $issue->stock_out_request_id ?? '') }}">

                    @error('stock_out_request_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Ngày xuất <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                        name="issue_date"
                        value="{{ old('issue_date', isset($issue->issue_date) ? \Carbon\Carbon::parse($issue->issue_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
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
    {{--
        Lưu ý: KHÔNG còn datalist "locationDatalist" dùng chung nữa.
        Vị trí giờ phụ thuộc vào vật tư đã chọn ở từng dòng (chỉ hiển thị
        vị trí mà vật tư đó đang có tồn kho khả dụng), nên mỗi dòng có
        datalist riêng (class="location-datalist"), được nạp bằng AJAX
        khi người dùng chọn vật tư — xem hàm loadLocationsForRow() bên dưới.

        Luồng phụ thuộc đầy đủ: Vật tư -> Vị trí -> Lô (bắt buộc) -> Sê-ri
        (nếu có). Chỉ khi đã chọn ĐẾN Lô thì dòng mới được "giữ chỗ"
        (reserved_qty) khi Lưu — xem StockIssueService::reserveLines() và
        StockIssueRequest::withValidator() ở backend.
    --}}
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
                            <th style="width:6%">Xuất <span class="text-danger">*</span></th>
                            <th style="width:6%">Thực xuất</th>
                            <th style="width:10%">Vị trí <span class="text-danger">*</span></th>
                            <th style="width:10%">Người nhận <span class="text-danger">*</span></th>
                            <th style="width:6%">SN</th>
                            <th style="width:6%">Lô <span class="text-danger">*</span></th>
                            <th style="min-width:200px">Sê-ri <span class="lot-serial-hint text-body-secondary fw-normal small"></span></th>
                            <th style="width:6%">Kho phụ</th>
                            <th style="min-width:120px">Ghi chú</th>
                            <th style="width:2%"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">

                        @php
                        // Mỗi hàng UI giờ = 1 LINE (không còn detail phẳng cũ).
                        // Khi sửa phiếu: mỗi $issue->lines là 1 hàng; serial_numbers
                        // được ghép lại từ tất cả $line->details->serial (join bằng space).
                        $linesOld = old('lines');
                        if ($linesOld) {
                            $rows = collect($linesOld);
                        } elseif ($isEdit) {
                            $rows = $issue->lines;
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
                            $lotId = $lineRow['lot_id'] ?? '';
                            $lotNumber = $lineRow['lot_number'] ?? '';
                            $serialNumbers = $lineRow['serial_numbers'] ?? '';
                            $subWarehouse = $lineRow['sub_warehouse'] ?? '';
                            $note = $lineRow['note'] ?? '';
                        } else {
                            // Dữ liệu từ phiếu đang sửa: $lineRow là 1 StockIssueLine,
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
                            $lotId = $firstDetail->lot_id ?? '';
                            $lotNumber = $firstDetail->lot?->lot_number ?? '';
                            // Ghép mọi serial của line này thành 1 chuỗi cách nhau space.
                            $serialNumbers = $lineRow->details->pluck('serial.serial_number')->filter()->implode(' ');
                            $subWarehouse = $firstDetail->sub_warehouse ?? '';
                            $note = $lineRow->note ?? '';
                        }
                        // Nhãn vị trí hiện tại (dùng để hiển thị sẵn trong ô input,
                        // kể cả trước khi datalist theo vật tư được nạp xong qua AJAX).
                        $selLoc = ($locations ?? collect())->firstWhere('id', (int) $locationId);
                        $selLocLabel = $selLoc ? $selLoc->code.($selLoc->name ? ' - '.$selLoc->name : '') : '';
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
                                <div class="available-qty-warning text-danger small d-none mt-1"></div>
                            </td>
                            <td>
                                <input type="number" class="form-control text-end actual-qty-input"
                                    name="lines[{{ $i }}][actual_qty]" value="{{ $actualQty }}" min="0" step="1"
                                    {{ $tracking === 2 ? 'readonly' : '' }}>
                            </td>
                            <td>
                                <input type="hidden" name="lines[{{ $i }}][location_id]"
                                    class="location-id-hidden" value="{{ $locationId }}">
                                <input type="text" class="form-control location-input"
                                    list="locationDatalist-{{ $i }}"
                                    value="{{ $selLocLabel }}"
                                    data-current-label="{{ $selLocLabel }}"
                                    placeholder="Nhập hoặc chọn" autocomplete="off"
                                    oninput="onLocationInput(this)" required>
                                <datalist id="locationDatalist-{{ $i }}" class="location-datalist"></datalist>
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
                            {{-- Lot field: datalist phụ thuộc Vị trí đã chọn (Vị trí -> Lô) --}}
                            <td>
                                <input type="hidden" name="lines[{{ $i }}][lot_id]"
                                    class="lot-id-hidden" value="{{ $lotId }}">
                                <input type="text"
                                    class="form-control lot-input"
                                    name="lines[{{ $i }}][lot_number]" value="{{ $lotNumber }}"
                                    list="lotDatalist-{{ $i }}"
                                    data-current-label="{{ $lotNumber }}"
                                    placeholder="Chọn vị trí trước" autocomplete="off"
                                    oninput="onLotInput(this)" required>
                                <datalist id="lotDatalist-{{ $i }}" class="lot-datalist"></datalist>
                            </td>
                            {{-- Serial field: multi-select kiểu badge, giá trị thật vẫn là
                                 chuỗi "SN0001 SN0002 ..." lưu trong input hidden .serial-input
                                 (đồng bộ format với receipt/form.blade.php). Danh sách gợi ý
                                 phụ thuộc Lô đã chọn (Lô -> Sê-ri), chỉ được chọn mã có thật. --}}
                            <td>
                                <div class="serial-tag-box {{ $tracking === 1 ? 'disabled' : '' }}"
                                    onclick="focusSerialTyper(this)">
                                    <input type="hidden" class="serial-input"
                                        name="lines[{{ $i }}][serial_numbers]" value="{{ $serialNumbers }}">
                                    <input type="text" class="serial-tag-typer"
                                        list="serialDatalist-{{ $i }}"
                                        placeholder="{{ $tracking === 1 ? '-' : 'Chọn lô trước' }}"
                                        autocomplete="off"
                                        {{ $tracking === 1 ? 'disabled' : '' }}
                                        onkeydown="onSerialTyperKeydown(event, this)"
                                        onblur="commitSerialTyper(this)">
                                    <datalist id="serialDatalist-{{ $i }}" class="serial-datalist"></datalist>
                                </div>
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
                Tổng dòng: <strong id="rowCount">{{ $isEdit ? $issue->lines->count() : 0 }}</strong>
            </small>
        </div>
    </div>

    {{-- ── NÚT LƯU ── --}}
    <div class="d-flex gap-2 justify-content-end mt-3">
        @if(!$isEdit)
        <button type="submit" id="issueSubmitBtnNew" class="btn btn-outline-primary" name="action" value="save_and_new">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <svg class="icon me-1 submit-icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
            <span class="submit-label">Lưu &amp; Thêm mới</span>
        </button>
        @endif
        <button type="submit" id="issueSubmitBtnSave" class="btn btn-primary" name="action" value="save">
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
const EMPLOYEES = @json($employeesJson ?? []);
const SNS = @json($snsJson ?? []);

// URL AJAX lấy danh sách Vị trí đang có tồn khả dụng cho 1 vật tư.
// Ví dụ route: Route::get('issues/stock-locations/{product}', [StockIssueController::class, 'stockLocations'])->name('issues.stock-locations');
const STOCK_LOCATIONS_URL_BASE = "{{ route('issues.stock-locations', ['product' => '__PRODUCT_ID__']) }}{{ $isEdit ? '?issue_id=' . $issue->id : '' }}";

// TRACKING constants (mirrors PHP TrackingType enum: chỉ còn 2 giá trị)
const TRACKING_LOT = 1;
const TRACKING_LOT_AND_SERIAL = 2;

let rowIndex = <?php echo $rows->count(); ?>;
let submitting = false;

// ── Đếm số serial trong 1 chuỗi "SN0001 SN0002 ..." ────────────────
function countSerials(str) {
    return (str || '').trim().split(/\s+/).filter(Boolean).length;
}

// ── Lấy/ghi danh sách serial của 1 ô .serial-tag-box ───────────────
function getSerialTags(box) {
    return (box.querySelector('.serial-input').value || '').trim().split(/\s+/).filter(Boolean);
}

function setSerialTags(box, tags) {
    box.querySelector('.serial-input').value = tags.join(' ');
    renderSerialTags(box);
    syncActualQtyFromSerial(box);
}

// ── Vẽ lại các badge bên trong .serial-tag-box (giữ nguyên ô gõ) ───
function renderSerialTags(box) {
    const tags = getSerialTags(box);
    box.querySelectorAll('.serial-tag').forEach(el => el.remove());
    const typer = box.querySelector('.serial-tag-typer');

    tags.forEach(tag => {
        const span = document.createElement('span');
        span.className = 'serial-tag';
        span.textContent = tag;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'tag-remove';
        removeBtn.textContent = '×';
        removeBtn.title = 'Xóa mã này';
        removeBtn.onclick = (e) => {
            e.stopPropagation();
            setSerialTags(box, getSerialTags(box).filter(t => t !== tag));
            clearFieldError(box);

            // Trả mã vừa xóa lại vào danh sách gợi ý (nếu chưa có).
            const dl = box.querySelector('.serial-datalist');
            if (dl && ![...dl.options].some(o => o.value === tag)) {
                const opt = document.createElement('option');
                opt.value = tag;
                dl.appendChild(opt);
            }
        };

        span.appendChild(removeBtn);
        box.insertBefore(span, typer);
    });
}

// ── Thêm 1 mã serial (từ ô gõ) vào box, có kiểm tra trùng ngay tại chỗ ──
function addSerialTag(box, rawValue) {
    const value = (rawValue || '').trim();
    if (!value) return;

    // Bắt buộc mã phải nằm trong danh sách sê-ri đang có tồn của Lô đã chọn
    // (Lô -> Sê-ri) — không cho gõ tự do mã không tồn tại.
    const dl = box.querySelector('.serial-datalist');
    const isValidOption = dl && [...dl.options].some(o => o.value === value);
    if (!isValidOption) {
        box.classList.add('is-invalid');
        return;
    }

    const tags = getSerialTags(box);
    if (tags.includes(value)) {
        box.classList.add('is-invalid');
        return;
    }
    tags.push(value);
    setSerialTags(box, tags);
    box.classList.remove('is-invalid');

    // Mã đã dùng rồi -> loại khỏi gợi ý để không chọn trùng lần nữa.
    const usedOpt = [...dl.options].find(o => o.value === value);
    if (usedOpt) usedOpt.remove();
}

// ── Enter / Space / dấu phẩy chốt mã hiện tại thành badge ──────────
function onSerialTyperKeydown(event, typer) {
    if (['Enter', ' ', ','].includes(event.key)) {
        event.preventDefault();
        const box = typer.closest('.serial-tag-box');
        addSerialTag(box, typer.value);
        typer.value = '';
        return;
    }
    // Backspace trên ô rỗng -> xóa badge cuối cùng
    if (event.key === 'Backspace' && !typer.value) {
        const box = typer.closest('.serial-tag-box');
        const tags = getSerialTags(box);
        if (tags.length) {
            setSerialTags(box, tags.slice(0, -1));
        }
    }
}

// ── Khi rời khỏi ô gõ mà còn ký tự chưa chốt -> tự chốt luôn ────────
function commitSerialTyper(typer) {
    if (typer.value.trim()) {
        const box = typer.closest('.serial-tag-box');
        addSerialTag(box, typer.value);
        typer.value = '';
    }
}

// ── Click vào box (ngoài badge) -> focus vào ô gõ ──────────────────
function focusSerialTyper(box) {
    const typer = box.querySelector('.serial-tag-typer');
    if (typer && !typer.disabled) typer.focus();
}

// ── Đồng bộ Thực xuất = số serial hiện có trong box ────────────────
function syncActualQtyFromSerial(box) {
    const tr = box.closest('tr');
    const actualInput = tr?.querySelector('.actual-qty-input');
    const productInput = tr?.querySelector('.product-input');
    const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_LOT;
    if (tracking === TRACKING_LOT_AND_SERIAL && actualInput) {
        actualInput.value = getSerialTags(box).length;
    }
}

// ── Áp tracking lên một <tr> ──────────────────────────────────────
function applyTracking(tr, tracking) {
    const lotInput = tr.querySelector('.lot-input');
    const serialBox = tr.querySelector('.serial-tag-box');
    const serialTyper = tr.querySelector('.serial-tag-typer');
    const actualInput = tr.querySelector('.actual-qty-input');
    if (!lotInput || !serialBox || !serialTyper) return;

    // Reset
    lotInput.classList.remove('is-invalid');
    serialBox.classList.remove('is-invalid');

    const hasLocation = !!tr.querySelector('.location-id-hidden')?.value;
    const hasLot = !!tr.querySelector('.lot-id-hidden')?.value;

    switch (tracking) {
        case TRACKING_LOT:
            // Khóa serial; actual_qty nhập tay bình thường. Lô vẫn theo Vị trí như bình thường.
            setSerialTags(serialBox, []);
            serialTyper.disabled = true;
            serialTyper.placeholder = '-';
            serialBox.classList.add('disabled');
            lotInput.placeholder = hasLocation ? 'Nhập hoặc chọn' : 'Chọn vị trí trước';
            if (actualInput) {
                actualInput.readOnly = false;
                actualInput.classList.remove('bg-body-secondary');
            }
            break;

        case TRACKING_LOT_AND_SERIAL:
            // Serial chỉ mở khi đã có Lô (Lô -> Sê-ri); nếu chưa có Lô, giữ khóa với gợi ý phù hợp.
            serialBox.classList.remove('disabled');
            lotInput.placeholder = hasLocation ? 'Nhập hoặc chọn' : 'Chọn vị trí trước';
            serialTyper.disabled = !hasLot;
            serialTyper.placeholder = hasLot ? 'Chọn mã, Enter để thêm' : 'Chọn lô trước';
            if (actualInput) {
                actualInput.readOnly = true;
                actualInput.classList.add('bg-body-secondary');
                actualInput.value = getSerialTags(serialBox).length;
            }
            break;
    }
}

// ── Tìm dữ liệu theo nhãn hiển thị (dùng cho input + datalist) ─────
function findProductByLabel(label) {
    return PRODUCTS.find(p => `${p.code} - ${p.name}` === label);
}
function findEmployeeByLabel(label) {
    return EMPLOYEES.find(e => `${e.code} - ${e.name}` === label);
}

// ── Nạp danh sách Vị trí có tồn khả dụng cho 1 dòng, theo vật tư ───
// Chỉ hiển thị các vị trí mà vật tư đó đang có tồn kho (available_qty > 0),
// lấy từ API GET issues/stock-locations/{productId}.
// rawStockCache[productId] = TOÀN BỘ flat rows (location + lot + serial),
// dùng chung cho cả 3 tầng Vị trí -> Lô -> Sê-ri (chỉ gọi API 1 lần / vật tư).
const rawStockCache = {};

async function fetchStockRows(productId) {
    if (!productId) return [];
    if (rawStockCache[productId]) return rawStockCache[productId];

    try {
        const url = STOCK_LOCATIONS_URL_BASE.replace('__PRODUCT_ID__', productId);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        rawStockCache[productId] = res.ok ? await res.json() : [];
    } catch (e) {
        rawStockCache[productId] = [];
    }
    return rawStockCache[productId];
}

async function loadLocationsForRow(tr, productId) {
    const dl = tr.querySelector('.location-datalist');
    const locInput = tr.querySelector('.location-input');
    if (!dl || !locInput) return;
 
    const previousLabel = locInput.value.trim();
    dl.innerHTML = '';
 
    if (!productId) {
        locInput.classList.toggle('is-invalid', previousLabel !== '');
        checkAvailableQtyForRow(tr);
        return;
    }
 
    const rows = await fetchStockRows(productId);
 
    // Cộng dồn available_qty theo location_id (1 vị trí có thể có nhiều
    // dòng lô/serial trong rows, mỗi dòng đóng góp 1 phần tồn khả dụng).
    const availableByLocation = {};
    rows.forEach(row => {
        if (!row.location_id) return;
        const qty = Number(row.available_qty) || 0;
        availableByLocation[row.location_id] = (availableByLocation[row.location_id] || 0) + qty;
    });
 
    const seen = new Set();
    const uniqueLocations = rows.filter(row => {
        if (!row.location_id || seen.has(row.location_id)) return false;
        seen.add(row.location_id);
        return true;
    });
 
    uniqueLocations.forEach(row => {
        const avail = availableByLocation[row.location_id] || 0;
        const label = `${row.location_code}${row.location_name ? ' - ' + row.location_name : ''}`;
        const opt = document.createElement('option');
        // Hiện sẵn tồn khả dụng trong gợi ý, giúp người dùng chọn đúng Vị
        // trí có đủ hàng ngay từ đầu, không phải thử-sai.
        opt.value = `${label} (còn ${avail.toLocaleString('vi-VN', { maximumFractionDigits: 3 })})`;
        opt.dataset.id = row.location_id;
        opt.dataset.label = label;
        opt.dataset.available = avail;
        dl.appendChild(opt);
    });
 
    // Lưu bảng tồn khả dụng theo vị trí ngay trên <tr> để tái dùng khi
    // validate mà không cần gọi lại AJAX.
    tr.dataset.availableByLocation = JSON.stringify(availableByLocation);
 
    if (previousLabel) {
        const stillValid = uniqueLocations.some(row =>
            `${row.location_code}${row.location_name ? ' - ' + row.location_name : ''}` === previousLabel
        );
        locInput.classList.toggle('is-invalid', !stillValid);
        if (!stillValid) {
            tr.querySelector('.location-id-hidden').value = '';
        }
    }
 
    checkAvailableQtyForRow(tr);
}

function checkAvailableQtyForRow(tr) {
    const expectedInput = tr.querySelector('input[name$="[expected_qty]"]');
    const locationHidden = tr.querySelector('.location-id-hidden');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    const warningEl = tr.querySelector('.available-qty-warning');

    if (!expectedInput || !locationHidden) return true;

    const expectedQty = parseFloat(expectedInput.value) || 0;
    const locationId = locationHidden.value;
    const lotId = lotHidden?.value;

    // Chỉ giữ chỗ được khi đã xác định đủ Vị trí + Lô (khớp với
    // StockIssueService::reserveLines() ở backend — chỉ reserve() được
    // dòng có đủ location_id + lot_id). Thiếu Lô -> chưa thể kiểm tra
    // tồn khả dụng theo Lô, bỏ qua cảnh báo (lỗi "required" của input
    // Lô sẽ tự chặn submit).
    if (!locationId || !lotId || expectedQty <= 0) {
        warningEl?.classList.add('d-none');
        expectedInput.classList.remove('is-invalid');
        return true;
    }

    // Tồn khả dụng theo TỪNG LÔ tại Vị trí đã chọn, lấy từ bảng đã cộng
    // dồn sẵn khi loadLotsForRow() nạp datalist Lô (không còn cộng dồn cả
    // Vị trí), vì reserve() giữ chỗ đúng theo lot_id.
    let availableByLot = {};
    try {
        availableByLot = JSON.parse(tr.dataset.availableByLot || '{}');
    } catch (e) { /* ignore */ }

    const available = availableByLot[lotId];

    // Chưa có dữ liệu tồn cho lô này (chưa load xong AJAX) -> bỏ qua,
    // không báo lỗi giả.
    if (available === undefined) {
        warningEl?.classList.add('d-none');
        expectedInput.classList.remove('is-invalid');
        return true;
    }

    const insufficient = expectedQty > available;
    expectedInput.classList.toggle('is-invalid', insufficient);

    if (warningEl) {
        warningEl.classList.toggle('d-none', !insufficient);
        if (insufficient) {
            warningEl.textContent = `Lô đã chọn chỉ còn ${available.toLocaleString('vi-VN', { maximumFractionDigits: 3 })} khả dụng.`;
        }
    }

    return !insufficient;
}

function bindAvailableQtyWatchers(tr) {
    const expectedInput = tr.querySelector('input[name$="[expected_qty]"]');
    const locInput = tr.querySelector('.location-input');
    const lotInput = tr.querySelector('.lot-input');
 
    expectedInput?.addEventListener('input', () => checkAvailableQtyForRow(tr));
    // onLocationInput()/onLotInput() (đã có sẵn trong file gốc) tự cập nhật
    // location-id-hidden/lot-id-hidden; ta chỉ cần theo dõi thêm sự kiện
    // 'input' trên các ô hiển thị để bắt đúng thời điểm người dùng chọn xong
    // (datalist chọn -> input event). Giữ chỗ giờ phụ thuộc CẢ Vị trí LẪN Lô,
    // nên phải theo dõi cả hai.
    locInput?.addEventListener('input', () => checkAvailableQtyForRow(tr));
    locInput?.addEventListener('change', () => checkAvailableQtyForRow(tr));
    lotInput?.addEventListener('input', () => checkAvailableQtyForRow(tr));
    lotInput?.addEventListener('change', () => checkAvailableQtyForRow(tr));
}

// ── Nạp danh sách Lô có tồn tại 1 Vị trí cụ thể (Vị trí -> Lô) ─────
async function loadLotsForRow(tr, productId, locationId) {
    const dl = tr.querySelector('.lot-datalist');
    const lotInput = tr.querySelector('.lot-input');
    if (!dl || !lotInput) return;

    const previousLabel = lotInput.value.trim();
    dl.innerHTML = '';

    if (!productId || !locationId) {
        lotInput.disabled = true;
        lotInput.placeholder = 'Chọn vị trí trước';
        return;
    }

    lotInput.disabled = false;
    lotInput.placeholder = 'Nhập hoặc chọn';

    const rows = await fetchStockRows(productId);

    // Cộng dồn available_qty theo lot_id trong ĐÚNG Vị trí đã chọn (1 lô có
    // thể có nhiều dòng serial trong rows, mỗi dòng đóng góp 1 phần tồn khả dụng).
    const availableByLot = {};
    rows.forEach(row => {
        if (String(row.location_id) !== String(locationId) || !row.lot_id) return;
        const qty = Number(row.available_qty) || 0;
        availableByLot[row.lot_id] = (availableByLot[row.lot_id] || 0) + qty;
    });

    const seen = new Set();
    const lotsInLocation = rows.filter(row => {
        if (String(row.location_id) !== String(locationId) || !row.lot_id || seen.has(row.lot_id)) return false;
        seen.add(row.lot_id);
        return true;
    });

    lotsInLocation.forEach(row => {
        const avail = availableByLot[row.lot_id] || 0;
        const opt = document.createElement('option');
        // Hiện sẵn tồn khả dụng trong gợi ý, giúp người dùng chọn đúng Lô
        // có đủ hàng ngay từ đầu, không phải thử-sai (giống cách Vị trí
        // đang làm). Value gộp chung "(còn X)" chỉ tồn tại lúc chọn từ
        // dropdown — onLotInput() sẽ tách lại về số Lô sạch (dataset.label)
        // trước khi ghi vào input, nên không submit nhầm chuỗi có hậu tố.
        opt.value = `${row.lot_number} (còn ${avail.toLocaleString('vi-VN', { maximumFractionDigits: 3 })})`;
        opt.dataset.id = row.lot_id;
        opt.dataset.label = row.lot_number;
        opt.dataset.available = avail;
        dl.appendChild(opt);
    });

    // Lưu bảng tồn khả dụng theo lô ngay trên <tr> để tái dùng khi validate
    // mà không cần gọi lại AJAX.
    tr.dataset.availableByLot = JSON.stringify(availableByLot);

    if (previousLabel) {
        const stillValid = lotsInLocation.some(row => String(row.lot_number) === previousLabel);
        lotInput.classList.toggle('is-invalid', !stillValid);
        if (!stillValid) {
            tr.querySelector('.lot-id-hidden').value = '';
        }
    }
}

// ── Nạp danh sách Sê-ri có tồn trong 1 Lô cụ thể (Lô -> Sê-ri) ─────
async function loadSerialsForRow(tr, productId, locationId, lotId) {
    const dl = tr.querySelector('.serial-datalist');
    const typer = tr.querySelector('.serial-tag-typer');
    if (!dl || !typer) return;

    dl.innerHTML = '';

    const tracking = parseInt(tr.querySelector('.product-input')?.dataset?.tracking) || TRACKING_LOT;
    if (tracking !== TRACKING_LOT_AND_SERIAL) return;

    if (!productId || !locationId || !lotId) {
        typer.disabled = true;
        typer.placeholder = 'Chọn lô trước';
        return;
    }

    typer.disabled = false;
    typer.placeholder = 'Chọn mã, Enter để thêm';

    const rows = await fetchStockRows(productId);
    rows
        .filter(row => String(row.location_id) === String(locationId)
            && String(row.lot_id) === String(lotId)
            && row.serial_number)
        .forEach(row => {
            const opt = document.createElement('option');
            opt.value = row.serial_number;
            dl.appendChild(opt);
        });
}

// ── Khi gõ/chọn Tên vật tư ─────────────────────────────────────────
async function onProductInput(input, preserveSelection = false) {
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

        applyTracking(tr, parseInt(p.tracking_type) || TRACKING_LOT);
        await loadLocationsForRow(tr, p.id);
    } else {
        hidden.value = '';
        delete input.dataset.tracking;
        input.classList.toggle('is-invalid', input.value.trim() !== '');
        tr.querySelector('.uom-label').textContent = '-';
        tr.querySelector('.uom-hidden').value = '';
        tr.querySelector('.tskt-label').value = '';
        await loadLocationsForRow(tr, null);
    }

    // Đổi vật tư -> Vị trí/Lô/Sê-ri đã chọn trước đó không còn ý nghĩa, xóa hết.
    // Bỏ qua khi đang khởi tạo trang (dữ liệu cũ từ server cần được giữ nguyên).
    if (!preserveSelection) {
        clearLocationSelection(tr);
    }
}

// ── Khi gõ/chọn Vị trí ───────────────────────────────────────────
function onLocationInput(input) {
    const tr = input.closest('tr');
    const hidden = tr.querySelector('.location-id-hidden');
    const dl = tr.querySelector('.location-datalist');
    const label = input.value.trim();
    const opt = dl
        ? [...dl.options].find(o => o.value === label || o.dataset.label === label)
        : null;
    const productId = tr.querySelector('.product-id-hidden')?.value;

    if (opt) {
        hidden.value = opt.dataset.id;
        input.classList.remove('is-invalid');
        input.value = opt.dataset.label;
    } else {
        hidden.value = '';
        input.classList.toggle('is-invalid', label !== '');
    }

    clearLotSelection(tr);
    loadLotsForRow(tr, productId, hidden.value || null);
}

// ── Khi gõ/chọn Lô ─────────────────────────────────────────────────
function onLotInput(input) {
    clearFieldError(input);
    const tr = input.closest('tr');
    const hidden = tr.querySelector('.lot-id-hidden');
    const dl = tr.querySelector('.lot-datalist');
    const label = input.value.trim();
    const opt = dl
        ? [...dl.options].find(o => o.value === label || o.dataset.label === label)
        : null;
    const productId = tr.querySelector('.product-id-hidden')?.value;
    const locationId = tr.querySelector('.location-id-hidden')?.value;

    if (opt) {
        hidden.value = opt.dataset.id;
        input.classList.remove('is-invalid');
        // opt.value gộp chung "(còn X)" để hiện trong gợi ý dropdown, nhưng
        // giá trị THẬT submit làm lot_number phải sạch — ghi lại đúng số Lô
        // (dataset.label) vào ô input ngay khi người dùng vừa chọn xong.
        input.value = opt.dataset.label;
    } else {
        hidden.value = '';
        input.classList.toggle('is-invalid', label !== '');
    }

    // Đổi Lô -> Sê-ri đã chọn trước đó không còn hợp lệ, xóa và nạp lại Sê-ri.
    const serialBox = tr.querySelector('.serial-tag-box');
    if (serialBox) setSerialTags(serialBox, []);
    loadSerialsForRow(tr, productId, locationId, hidden.value || null);
    checkAvailableQtyForRow(tr);
}

// ── Xóa lựa chọn Vị trí + Lô + Sê-ri (khi đổi Vật tư) ──────────────
function clearLocationSelection(tr) {
    const locInput = tr.querySelector('.location-input');
    const locHidden = tr.querySelector('.location-id-hidden');
    if (locInput) {
        locInput.value = '';
        locInput.classList.remove('is-invalid');
    }
    if (locHidden) locHidden.value = '';
    clearLotSelection(tr);
}

// ── Xóa lựa chọn Lô + Sê-ri (khi đổi Vị trí) ───────────────────────
function clearLotSelection(tr) {
    const lotInput = tr.querySelector('.lot-input');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    if (lotInput) {
        lotInput.value = '';
        lotInput.classList.remove('is-invalid');
        lotInput.disabled = true;
        lotInput.placeholder = 'Chọn vị trí trước';
    }
    if (lotHidden) lotHidden.value = '';

    const serialBox = tr.querySelector('.serial-tag-box');
    if (serialBox) {
        setSerialTags(serialBox, []);
        const typer = serialBox.querySelector('.serial-tag-typer');
        if (typer) {
            typer.disabled = true;
            typer.placeholder = 'Chọn lô trước';
        }
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
    <div class="available-qty-warning text-danger small d-none mt-1"></div>
  </td>
  <td>
    <input type="number" class="form-control text-end actual-qty-input" name="lines[${i}][actual_qty]"
           min="0" step="1" placeholder="0">
  </td>
  <td>
    <input type="hidden" name="lines[${i}][location_id]" class="location-id-hidden" value="">
    <input type="text" class="form-control location-input" list="locationDatalist-${i}"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onLocationInput(this)" required>
    <datalist id="locationDatalist-${i}" class="location-datalist"></datalist>
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
    <input type="hidden" name="lines[${i}][lot_id]" class="lot-id-hidden" value="">
    <input type="text" class="form-control lot-input" name="lines[${i}][lot_number]"
        list="lotDatalist-${i}" placeholder="Chọn vị trí trước" autocomplete="off"
        oninput="onLotInput(this)" required>
    <datalist id="lotDatalist-${i}" class="lot-datalist"></datalist>
  </td>
  <td>
    <div class="serial-tag-box disabled" onclick="focusSerialTyper(this)">
      <input type="hidden" class="serial-input" name="lines[${i}][serial_numbers]" value="">
      <input type="text" class="serial-tag-typer" list="serialDatalist-${i}"
             placeholder="-" autocomplete="off" disabled
             onkeydown="onSerialTyperKeydown(event, this)" onblur="commitSerialTyper(this)">
      <datalist id="serialDatalist-${i}" class="serial-datalist"></datalist>
    </div>
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
    const newRow = document.getElementById('detailBody').lastElementChild;
    if (newRow) bindAvailableQtyWatchers(newRow);
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

function clearFieldError(el) {
    el.classList.remove('is-invalid');
    if (!document.querySelector('.lot-input.is-invalid, .serial-tag-box.is-invalid')) {
        document.getElementById('lotSerialAlertContainer').innerHTML = '';
    }
}

// ── Client-side validate Lot/Serial (gương với StockIssueRequest) ─
function validateLotSerial() {
    const errors = [];

    document.querySelectorAll('#detailBody tr').forEach(tr => {
        const lotInput = tr.querySelector('.lot-input');
        const serialBox = tr.querySelector('.serial-tag-box');
        [lotInput, serialBox].forEach(el => el?.classList.remove('is-invalid'));
    });

    // ── Bước 1: Serial bắt buộc + actual_qty phải khớp số serial ────
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const productInput = tr.querySelector('.product-input');
        const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_LOT;
        if (tracking !== TRACKING_LOT_AND_SERIAL) return;

        const serialBox = tr.querySelector('.serial-tag-box');
        const serialInput = tr.querySelector('.serial-input');
        const serials = (serialInput.value || '').trim().split(/\s+/).filter(Boolean);

        if (serials.length === 0) {
            serialBox.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo <strong>Lô+Sê-ri</strong> — chưa nhập Mã Serial (cách nhau bằng dấu cách).`);
            return;
        }

        // Serial trùng NGAY TRONG chuỗi của chính dòng này
        const seenInRow = new Set();
        for (const s of serials) {
            if (seenInRow.has(s)) {
                serialBox.classList.add('is-invalid');
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
        const serialBox = tr.querySelector('.serial-tag-box');
        const serialInput = tr.querySelector('.serial-input');
        const serials = (serialInput?.value || '').trim().split(/\s+/).filter(Boolean);
        if (!productId) return;

        serials.forEach(serialVal => {
            if (!serialMap[productId]) serialMap[productId] = {};
            if (serialMap[productId][serialVal] !== undefined) {
                serialBox.classList.add('is-invalid');
                const firstRow = serialMap[productId][serialVal] + 1;
                errors.push(
                    `Dòng ${i+1}: Số Serial <strong>"${serialVal}"</strong> đã nhập ở dòng ${firstRow} (cùng sản phẩm).`
                );
            } else {
                serialMap[productId][serialVal] = i;
            }
        });
    });

    // ── Bước 3: Lô bắt buộc phải được chọn (giữ chỗ theo Lô) ───────────
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const lotHidden = tr.querySelector('.lot-id-hidden');
        const lotInput = tr.querySelector('.lot-input');
        const productInput = tr.querySelector('.product-input');
        if (!productInput?.value.trim()) return; // dòng chưa nhập vật tư, bỏ qua ở bước này
        if (!lotHidden?.value) {
            lotInput?.classList.add('is-invalid');
            errors.push(`Dòng ${i + 1}: Vui lòng chọn <strong>Lô</strong> để giữ chỗ.`);
        }
    });

    // ── Bước 4: Số lượng Xuất không được vượt tồn khả dụng của Lô đã chọn ──
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        if (!checkAvailableQtyForRow(tr)) {
            errors.push(`Dòng ${i + 1}: Số lượng <strong>Xuất</strong> vượt quá tồn khả dụng của Lô đã chọn.`);
        }
    });

    return errors;
}

document.getElementById('issueForm').addEventListener('submit', function(e) {
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
        document.querySelector('.lot-input.is-invalid, .serial-tag-box.is-invalid .serial-tag-typer')?.focus();
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
    document.querySelectorAll('#detailBody tr').forEach(async (tr) => {
        bindAvailableQtyWatchers(tr);

        const productInput = tr.querySelector('.product-input');
        const productId = tr.querySelector('.product-id-hidden')?.value;
        const locationId = tr.querySelector('.location-id-hidden')?.value;
        const lotId = tr.querySelector('.lot-id-hidden')?.value;

        if (productInput?.value) {
            // preserveSelection=true: đây là dữ liệu đã lưu, không phải người dùng vừa gõ.
            await onProductInput(productInput, true);
        }

        // Vẽ badge cho các serial đã có sẵn (dữ liệu cũ khi sửa phiếu / old()).
        const serialBox = tr.querySelector('.serial-tag-box');
        if (serialBox) renderSerialTags(serialBox);

        // Nạp tiếp Lô (theo Vị trí đã lưu) rồi Sê-ri (theo Lô đã lưu), giữ đúng
        // chuỗi phụ thuộc Vật tư -> Vị trí -> Lô -> Sê-ri ngay từ lúc mở trang.
        if (productId && locationId) {
            await loadLotsForRow(tr, productId, locationId);
            const lotInput = tr.querySelector('.lot-input');
            if (lotInput) lotInput.disabled = false;

            // Kiểm tra ngay tồn khả dụng của Lô đã lưu, để cảnh báo hiện
            // ngay khi mở trang sửa phiếu (không cần đợi người dùng đụng
            // vào Vị trí/Lô/Xuất trước mới thấy cảnh báo).
            checkAvailableQtyForRow(tr);

            if (lotId) {
                await loadSerialsForRow(tr, productId, locationId, lotId);
                const typer = tr.querySelector('.serial-tag-typer');
                const tracking = parseInt(productInput?.dataset?.tracking) || TRACKING_LOT;
                if (typer && tracking === TRACKING_LOT_AND_SERIAL) typer.disabled = false;

                // Loại các mã đã chọn sẵn khỏi datalist gợi ý để tránh chọn trùng.
                if (serialBox) {
                    const dl = serialBox.querySelector('.serial-datalist');
                    const selected = getSerialTags(serialBox);
                    if (dl) {
                        [...dl.options].forEach(o => {
                            if (selected.includes(o.value)) o.remove();
                        });
                    }
                }
            }
        }
    });

    toggleEmptyState();
    updateTotals();

    <?php if ($rows->count() === 0): ?>
    addRow();
    <?php endif; ?>
});

(function () {
    const input  = document.getElementById('stock_out_request_code');
    const hidden = document.getElementById('stock_out_request_id');
    const list   = document.getElementById('stockOutRequestList');

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