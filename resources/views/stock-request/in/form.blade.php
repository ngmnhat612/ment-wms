@extends('layouts.app')

@section('title', (isset($stockInRequest) ? 'Chỉnh sửa phiếu yêu cầu nhập' : 'Thêm phiếu yêu cầu nhập'))

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Yêu cầu Nhập/Xuất kho</a></li>
<li class="breadcrumb-item active">{{ isset($stockInRequest) ? $stockInRequest->code : 'Thêm phiếu yêu cầu nhập' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($stockInRequest);
$action = $isEdit ? route('stock-in-requests.update', $stockInRequest->id) : route('stock-in-requests.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Chỉnh sửa phiếu yêu cầu nhập' : 'Thêm phiếu yêu cầu nhập' }}</h4>
    </div>
    <a href="{{ $isEdit ? route('stock-in-requests.show', $stockInRequest->id) : route('stock-requests.index') }}" class="btn btn-outline-secondary">
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="stockInRequestForm">
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

                <div class="col-md-3">
                    <label class="form-label mb-1">Mã phiếu</label>
                    <input type="text"
                        class="form-control text-uppercase @error('code') is-invalid @enderror"
                        name="code" value="{{ old('code', $stockInRequest->code ?? '') }}" placeholder="Tự động"
                        maxlength="50" {{ $isEdit ? 'readonly' : '' }}>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-9">
                    <label class="form-label mb-1">Ghi chú</label>
                    <input type="text" class="form-control" name="note"
                        value="{{ old('note', $stockInRequest->note ?? '') }}" maxlength="500" placeholder="Ghi chú">
                </div>

            </div>
        </div>
    </div>

    {{-- Datalist dùng chung cho các ô gõ-để-tìm bên dưới --}}
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
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="detailTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:2%">#</th>
                            <th style="width:8%">Số PO/CU</th>
                            <th style="width:8%">Ngày nhận</th>
                            <th style="min-width:100px">Mã vật tư</th>
                            <th style="min-width:160px">Tên vật tư <span class="text-danger">*</span></th>
                            <th style="min-width:140px">TSKT</th>
                            <th style="width:8%">Thương hiệu</th>
                            <th style="width:6%">SL <span class="text-danger">*</span></th>
                            <th style="width:6%">ĐVT <span class="text-danger">*</span></th>
                            <th style="width:6%">Số Lô</th>
                            <th style="min-width:120px">Người yêu cầu</th>
                            <th style="min-width:120px">Ghi chú</th>
                            <th style="width:2%"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">

                        @php
                        $rowsOld = old('details');
                        if ($rowsOld) {
                            $rows = collect($rowsOld);
                        } elseif ($isEdit) {
                            $rows = $stockInRequest->details;
                        } else {
                            $rows = collect();
                        }
                        @endphp

                        @foreach($rows as $i => $row)
                        @php
                        if (is_array($row)) {
                            $referenceNo = $row['reference_no'] ?? '';
                            $receivedDate = $row['received_date'] ?? '';
                            $productCode = $row['product_code'] ?? '';
                            $productName = $row['product_name'] ?? '';
                            $productSpec = $row['product_specification'] ?? '';
                            $brandName = $row['brand_name'] ?? '';
                            $quantity = isset($row['quantity']) ? $row['quantity'] + 0 : '';
                            $uomName = $row['uom_name'] ?? '';
                            $lotNumber = $row['lot_number'] ?? '';
                            $requesterId = $row['requester_id'] ?? '';
                            $note = $row['note'] ?? '';
                        } else {
                            $referenceNo = $row->reference_no;
                            $receivedDate = $row->received_date ? \Carbon\Carbon::parse($row->received_date)->format('Y-m-d') : '';
                            $productCode = $row->product_code;
                            $productName = $row->product_name;
                            $productSpec = $row->product_specification;
                            $brandName = $row->brand_name;
                            $quantity = $row->quantity + 0;
                            $uomName = $row->uom_name;
                            $lotNumber = $row->lot_number;
                            $requesterId = $row->requester_id;
                            $note = $row->note;
                        }
                        $selRequester = $employees->firstWhere('id', (int) $requesterId);
                        @endphp
                        <tr>
                            <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][reference_no]" value="{{ $referenceNo }}"
                                    maxlength="100" placeholder="Số PO/CU">
                            </td>
                            <td>
                                <input type="date" class="form-control"
                                    name="details[{{ $i }}][received_date]" value="{{ $receivedDate }}">
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][product_code]" value="{{ $productCode }}"
                                    maxlength="50" placeholder="Mã vật tư">
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][product_name]" value="{{ $productName }}"
                                    maxlength="200" placeholder="Tên vật tư" required>
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][product_specification]" value="{{ $productSpec }}"
                                    maxlength="500" placeholder="Thông số kỹ thuật">
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][brand_name]" value="{{ $brandName }}"
                                    maxlength="200" placeholder="Thương hiệu">
                            </td>
                            <td>
                                <input type="number" class="form-control text-end"
                                    name="details[{{ $i }}][quantity]" value="{{ $quantity }}" min="0.001"
                                    step="0.001" required>
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][uom_name]" value="{{ $uomName }}"
                                    maxlength="50" placeholder="ĐVT" required>
                            </td>
                            <td>
                                <input type="number" class="form-control"
                                    name="details[{{ $i }}][lot_number]" value="{{ $lotNumber }}"
                                    placeholder="Tự động" min="1" step="1">
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][requester_id]"
                                    class="requester-id-hidden" value="{{ $requesterId }}">
                                <input type="text" class="form-control requester-input" list="employeeDatalist"
                                    value="{{ $selRequester ? $selRequester->code.' - '.$selRequester->name : '' }}"
                                    placeholder="Nhập hoặc chọn" autocomplete="off"
                                    oninput="onRequesterInput(this)">
                            </td>
                            <td>
                                <input type="text" class="form-control"
                                    name="details[{{ $i }}][note]" value="{{ $note }}"
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
                Tổng dòng: <strong id="rowCount">{{ $rows->count() }}</strong>
            </small>
        </div>
    </div>

    {{-- ── NÚT LƯU ── --}}
    <div class="d-flex gap-2 justify-content-end mt-3">
        @if(!$isEdit)
        <button type="submit" id="stockInRequestSubmitBtnNew" class="btn btn-outline-primary" name="action" value="save_and_new">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <svg class="icon me-1 submit-icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
            <span class="submit-label">Lưu &amp; Thêm mới</span>
        </button>
        @endif
        <button type="submit" id="stockInRequestSubmitBtnSave" class="btn btn-primary" name="action" value="save">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <svg class="icon me-1 submit-icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            <span class="submit-label">Lưu</span>
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
const EMPLOYEES = @json($employeesJson ?? $employees->map(fn($e) => ['id' => $e->id, 'code' => $e->code, 'name' => $e->name])->values());

let rowIndex = <?php echo $rows->count(); ?>;
let submitting = false;

function findEmployeeByLabel(label) {
    return EMPLOYEES.find(e => `${e.code} - ${e.name}` === label);
}

function onRequesterInput(input) {
    const tr = input.closest('tr');
    const hidden = tr.querySelector('.requester-id-hidden');
    const e = findEmployeeByLabel(input.value.trim());

    if (e) {
        hidden.value = e.id;
        input.classList.remove('is-invalid');
    } else {
        hidden.value = '';
        input.classList.toggle('is-invalid', input.value.trim() !== '');
    }
}

function rowTemplate(i) {
    return `
<tr>
  <td class="text-center text-body-secondary small">${i + 1}</td>
  <td>
    <input type="text" class="form-control" name="details[${i}][reference_no]" maxlength="100" placeholder="Số PO/CU">
  </td>
  <td>
    <input type="date" class="form-control" name="details[${i}][received_date]">
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][product_code]" maxlength="50" placeholder="Mã vật tư">
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][product_name]" maxlength="200" placeholder="Tên vật tư" required>
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][product_specification]" maxlength="500" placeholder="Thông số kỹ thuật">
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][brand_name]" maxlength="200" placeholder="Thương hiệu">
  </td>
  <td>
    <input type="number" class="form-control text-end" name="details[${i}][quantity]" min="0.001" step="0.001" required placeholder="0">
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][uom_name]" maxlength="50" placeholder="ĐVT" required>
  </td>
  <td>
    <input type="number" class="form-control" name="details[${i}][lot_number]" placeholder="Tự động" min="1" step="1">
  </td>
  <td>
    <input type="hidden" name="details[${i}][requester_id]" class="requester-id-hidden" value="">
    <input type="text" class="form-control requester-input" list="employeeDatalist"
           placeholder="Nhập hoặc chọn" autocomplete="off" oninput="onRequesterInput(this)">
  </td>
  <td>
    <input type="text" class="form-control" name="details[${i}][note]" placeholder="Ghi chú" maxlength="500">
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
}

function removeRow(btn) {
    btn.closest('tr').remove();
    syncRowNumbers();
    toggleEmptyState();
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

document.getElementById('stockInRequestForm').addEventListener('submit', function(e) {
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

document.addEventListener('DOMContentLoaded', () => {
    toggleEmptyState();

    <?php if ($rows->count() === 0): ?>
    addRow();
    <?php endif; ?>
});
</script>
@endpush