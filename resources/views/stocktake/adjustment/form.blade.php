@extends('layouts.app')

@php $isEdit = isset($adjustment); @endphp

@section('title', $isEdit ? 'Chỉnh sửa phiếu điều chỉnh' : 'Thêm phiếu điều chỉnh')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê</a></li>
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.show', $inventoryCheck) }}">{{ $inventoryCheck->code }}</a></li>
  @if($isEdit)
    <li class="breadcrumb-item"><a href="{{ route('stocktakes.adjustment.show', [$inventoryCheck, $adjustment]) }}">{{ $adjustment->code }}</a></li>
    <li class="breadcrumb-item active">Chỉnh sửa</li>
  @else
    <li class="breadcrumb-item active">Thêm phiếu điều chỉnh</li>
  @endif
@endsection

@section('content')

@php
  // Ở chế độ edit: nguồn dòng chênh lệch vẫn là InventoryCheckDetail của phiếu kiểm kê gốc
  // (giống create) để người dùng có thể đổi lại tập hợp dòng đã chọn.
  $diffDetails = $inventoryCheck->details->filter(fn($d) => (float) $d->diff_qty !== 0.0);

  // Danh sách check_detail_id đã được chọn trước đó (khi edit), dùng để tick sẵn checkbox.
  $selectedDetailIds = $isEdit
    ? $adjustment->details->pluck('check_detail_id')->filter()->all()
    : [];
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h4 class="mb-0 fw-semibold">
      {{ $isEdit ? 'Chỉnh sửa phiếu điều chỉnh' : 'Thêm phiếu điều chỉnh' }}
    </h4>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ $isEdit ? route('stocktakes.adjustment.show', [$inventoryCheck, $adjustment]) : route('stocktakes.show', $inventoryCheck) }}" class="btn btn-outline-secondary">
      Quay lại
    </a>
  </div>
</div>

@if($errors->any())
  <div class="alert alert-danger alert-dismissible mb-4">
    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
    <strong>Vui lòng kiểm tra lại:</strong>
    <ul class="mb-0 mt-1 ps-3">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
  </div>
@endif

@if($diffDetails->isEmpty())
  <div class="alert alert-info d-flex align-items-center gap-2">
    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
    Phiếu kiểm kê này không có dòng chênh lệch nào cần điều chỉnh.
  </div>
@else

<form method="POST"
      action="{{ $isEdit ? route('stocktakes.adjustment.update', [$inventoryCheck, $adjustment]) : route('stocktakes.adjustment.store', $inventoryCheck) }}"
      id="adjustmentForm">
  @csrf
  @if($isEdit) @method('PUT') @endif

  {{-- THÔNG TIN CHUNG --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
      Thông tin phiếu
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-3">
          <label class="form-label mb-1 d-block fw-semibold">Mã phiếu</label>
          <input type="text" class="form-control" value="{{ $isEdit ? $adjustment->code : 'TỰ ĐỘNG' }}" disabled>
        </div>
        <div class="col-sm-3">
          <label class="form-label mb-1 d-block fw-semibold">Từ phiếu kiểm kê</label>
          <input type="text" class="form-control" value="{{ $inventoryCheck->code }}" disabled>
        </div>
        <div class="col-sm-3">
          <label class="form-label fw-semibold" for="adjustment_date">
            Ngày điều chỉnh <span class="text-danger">*</span>
          </label>
          <input type="date" class="form-control @error('adjustment_date') is-invalid @enderror"
                 id="adjustment_date" name="adjustment_date"
                 value="{{ old('adjustment_date', $isEdit ? \Carbon\Carbon::parse($adjustment->adjustment_date)->toDateString() : now()->toDateString()) }}">
          @error('adjustment_date')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-sm-3">
          <label class="form-label fw-semibold" for="note">Ghi chú</label>
          <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note"
                    rows="1" maxlength="500" placeholder="Ghi chú">{{ old('note', $isEdit ? $adjustment->note : '') }}</textarea>
          @error('note')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  {{-- CHỌN DÒNG CHÊNH LỆCH --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center" style="min-height:44px">
      <span>Chọn dòng cần điều chỉnh</span>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-primary" id="toggleDetailsBtn" onclick="toggleAllDetails()" title="Chọn tất cả">
          <svg class="icon" id="toggleDetailsIcon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
        </button>
      </div>
    </div>

    @error('detail_ids')
      <div class="alert alert-danger py-2 mx-3 mt-3 mb-0">{{ $message }}</div>
    @enderror

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:4%">#</th>
              <th>Vật tư</th>
              <th style="width:8%">ĐVT</th>
              <th style="width:4%">Lô</th>
              <th style="width:10%">Vị trí hệ thống</th>
              <th style="width:10%">Vị trí thực tế</th>
              <th class="text-end" style="width:10%">Tồn hệ thống</th>
              <th class="text-end" style="width:10%">Tồn thực tế</th>
              <th class="text-end" style="width:10%">Chênh lệch</th>
              <th class="text-center" style="width:10%">Chọn</th>
            </tr>
          </thead>
          <tbody>
            @foreach($diffDetails as $index => $d)
            @php
              $diff    = (float) $d->diff_qty;
              $isPlus  = $diff > 0;
              $checked = $isEdit
                ? in_array($d->id, old('detail_ids', $selectedDetailIds))
                : in_array($d->id, old('detail_ids', []));
            @endphp
            <tr class="{{ $isPlus ? 'table-success' : 'table-danger' }}">
              <td class="text-center text-body-secondary">{{ $index + 1 }}</td>
              <td>
                <label class="mb-0" for="detail_{{ $d->id }}">
                  <div class="fw-semibold small">{{ $d->product->name ?? '—' }}</div>
                  <div class="text-body-secondary" style="font-size:11px">{{ $d->product->code ?? '' }}</div>
                </label>
              </td>
              <td class="small text-center text-body-secondary">{{ $d->uom->name ?? '—' }}</td>
              <td class="small text-body-secondary">{{ $d->lot->lot_code ?? '—' }}</td>
              <td class="small text-body-secondary">{{ $d->systemLocation->code ?? '—' }}</td>
              <td class="small text-body-secondary">{{ $d->actualLocation->code ?? '—' }}</td>
              <td class="text-end">{{ number_format($d->system_qty, 0) }}</td>
              <td class="text-end fw-semibold">{{ number_format($d->actual_qty, 0) }}</td>
              <td class="text-end fw-bold {{ $isPlus ? 'text-success' : 'text-danger' }}">
                {{ $isPlus ? '+' : '' }}{{ number_format($diff, 0) }}
              </td>
              <td class="text-center">
                <input type="checkbox" class="form-check-input detail-cb" name="detail_ids[]"
                       value="{{ $d->id }}" id="detail_{{ $d->id }}"
                       {{ $checked ? 'checked' : '' }}>
              </td>
            </tr>
            @endforeach
          </tbody>
          @if($diffDetails->count() > 0)
          <tfoot class="table-light fw-semibold">
            <tr>
              <td colspan="6" class="text-end">Tổng:</td>
              <td class="text-end">{{ number_format($diffDetails->sum('system_qty'), 0) }}</td>
              <td class="text-end">{{ number_format($diffDetails->sum('actual_qty'), 0) }}</td>
              <td class="text-end">
                @php $totalDiff = $diffDetails->sum('diff_qty'); @endphp
                @if($totalDiff > 0)
                  <span class="text-success">+{{ number_format($totalDiff, 0) }}</span>
                @elseif($totalDiff < 0)
                  <span class="text-danger">{{ number_format($totalDiff, 0) }}</span>
                @else
                  <span class="text-body-secondary">0</span>
                @endif
              </td>
              <td></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>

  {{-- ACTIONS --}}
  <div class="d-flex justify-content-end gap-2">
    <a href="{{ $isEdit ? route('stocktakes.adjustment.show', [$inventoryCheck, $adjustment]) : route('stocktakes.show', $inventoryCheck) }}" class="btn btn-outline-secondary">Hủy</a>
    <button type="submit" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
      Lưu
    </button>
  </div>

</form>

@endif

@endsection

@push('scripts')
<script>
  function toggleAllDetails() {
    const checkboxes = document.querySelectorAll('.detail-cb');
    const btn = document.getElementById('toggleDetailsBtn');
    const useEl = document.querySelector('#toggleDetailsIcon use');
    const isSelectAll = btn.dataset.state !== 'selected';

    checkboxes.forEach(cb => cb.checked = isSelectAll);

    if (isSelectAll) {
      useEl.setAttribute('xlink:href', '{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}');
      btn.title = 'Bỏ chọn';
      btn.dataset.state = 'selected';
    } else {
      useEl.setAttribute('xlink:href', '{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}');
      btn.title = 'Chọn tất cả';
      btn.dataset.state = '';
    }
  }
</script>
@endpush