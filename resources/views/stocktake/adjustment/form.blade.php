@extends('layouts.app')

@section('title', 'Tạo phiếu điều chỉnh')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê kho</a></li>
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.show', $inventoryCheck) }}">{{ $inventoryCheck->code }}</a></li>
  <li class="breadcrumb-item active">Tạo phiếu điều chỉnh</li>
@endsection

@section('content')

@php
  $diffDetails = $inventoryCheck->details->filter(fn($d) => (float) $d->diff_qty !== 0.0);
@endphp

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="{{ route('stocktakes.show', $inventoryCheck) }}" class="btn btn-outline-secondary btn-sm">
    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
  </a>
  <div>
    <h4 class="mb-0 fw-semibold">Tạo phiếu điều chỉnh</h4>
    <small class="text-body-secondary">
      Từ phiếu kiểm kê <a href="{{ route('stocktakes.show', $inventoryCheck) }}" class="text-decoration-none">{{ $inventoryCheck->code }}</a>
      — chọn các dòng chênh lệch cần điều chỉnh tồn kho
    </small>
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

<form method="POST" action="{{ route('stocktakes.adjustment.store', $inventoryCheck) }}" id="adjustmentForm">
  @csrf

  {{-- THÔNG TIN CHUNG --}}
  <div class="card mb-4">
    <div class="card-header fw-semibold">Thông tin phiếu điều chỉnh</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label" for="adjustment_date">
            Ngày điều chỉnh <span class="text-danger">*</span>
          </label>
          <input type="date" class="form-control @error('adjustment_date') is-invalid @enderror"
                 id="adjustment_date" name="adjustment_date"
                 value="{{ old('adjustment_date', now()->toDateString()) }}">
          @error('adjustment_date')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-sm-6">
          <label class="form-label mb-1 d-block">Từ phiếu kiểm kê</label>
          <div class="fw-semibold pt-2">{{ $inventoryCheck->code }}</div>
        </div>
        <div class="col-12">
          <label class="form-label" for="note">Ghi chú</label>
          <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note"
                    rows="2" maxlength="500" placeholder="Ghi chú thêm...">{{ old('note') }}</textarea>
          @error('note')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  {{-- CHỌN DÒNG CHÊNH LỆCH --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Chọn dòng cần điều chỉnh</span>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllDetails()">Chọn tất cả</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearDetails()">Bỏ chọn</button>
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
              <th style="width:36px">
                <input type="checkbox" class="form-check-input" id="checkAll">
              </th>
              <th>Mặt hàng</th>
              <th>Vị trí HT</th>
              <th>Vị trí thực tế</th>
              <th>Lô</th>
              <th style="width:50px">ĐVT</th>
              <th class="text-end" style="width:100px">Tồn HT</th>
              <th class="text-end" style="width:100px">Thực tế</th>
              <th class="text-end" style="width:100px">Chênh lệch</th>
            </tr>
          </thead>
          <tbody>
            @foreach($diffDetails as $d)
            @php
              $diff   = (float) $d->diff_qty;
              $isPlus = $diff > 0;
            @endphp
            <tr class="{{ $isPlus ? 'table-success' : 'table-danger' }}">
              <td>
                <input type="checkbox" class="form-check-input detail-cb" name="detail_ids[]"
                       value="{{ $d->id }}" id="detail_{{ $d->id }}"
                       {{ in_array($d->id, old('detail_ids', [])) ? 'checked' : '' }}>
              </td>
              <td>
                <label class="mb-0" for="detail_{{ $d->id }}">
                  <div class="fw-semibold small">{{ $d->product->name ?? '—' }}</div>
                  <div class="text-body-secondary" style="font-size:11px">{{ $d->product->code ?? '' }}</div>
                </label>
              </td>
              <td class="small text-body-secondary">{{ $d->systemLocation->code ?? '—' }}</td>
              <td class="small text-body-secondary">{{ $d->actualLocation->code ?? '—' }}</td>
              <td class="small text-body-secondary">{{ $d->lot->lot_code ?? '—' }}</td>
              <td class="small text-center text-body-secondary">{{ $d->uom->name ?? '—' }}</td>
              <td class="text-end">{{ number_format($d->system_qty, 0) }}</td>
              <td class="text-end fw-semibold">{{ number_format($d->actual_qty, 0) }}</td>
              <td class="text-end fw-bold {{ $isPlus ? 'text-success' : 'text-danger' }}">
                {{ $isPlus ? '+' : '' }}{{ number_format($diff, 0) }}
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ACTIONS --}}
  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('stocktakes.show', $inventoryCheck) }}" class="btn btn-outline-secondary">Hủy</a>
    <button type="submit" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
      Tạo phiếu điều chỉnh
    </button>
  </div>

</form>

@endif

@endsection

@push('scripts')
<script>
  function selectAllDetails() {
    document.querySelectorAll('.detail-cb').forEach(cb => cb.checked = true);
    document.getElementById('checkAll').checked = true;
  }
  function clearDetails() {
    document.querySelectorAll('.detail-cb').forEach(cb => cb.checked = false);
    document.getElementById('checkAll').checked = false;
  }
  document.getElementById('checkAll')?.addEventListener('change', function () {
    document.querySelectorAll('.detail-cb').forEach(cb => cb.checked = this.checked);
  });
</script>
@endpush