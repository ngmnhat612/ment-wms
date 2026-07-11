@extends('layouts.app')

@php $isEdit = isset($inventoryCheck); @endphp

@section('title', $isEdit ? 'Sửa phiếu kiểm kê' : 'Tạo phiếu kiểm kê')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê kho</a></li>
  @if($isEdit)
    <li class="breadcrumb-item"><a href="{{ route('stocktakes.show', $inventoryCheck) }}">{{ $inventoryCheck->code }}</a></li>
    <li class="breadcrumb-item active">Sửa phiếu kiểm kê</li>
  @else
    <li class="breadcrumb-item active">Tạo phiếu kiểm kê</li>
  @endif
@endsection

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h4 class="mb-0 fw-semibold">
      {{ $isEdit ? 'Sửa phiếu kiểm kê' : 'Tạo phiếu kiểm kê' }}
    </h4>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ $isEdit ? route('stocktakes.show', $inventoryCheck) : route('stocktakes.index') }}" class="btn btn-outline-secondary">
      Quay lại
    </a>
  </div>
</div>

@if($errors->any())
  <div class="alert alert-danger alert-dismissible mb-3">
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

<form method="POST" action="{{ $isEdit ? route('stocktakes.update', $inventoryCheck) : route('stocktakes.store') }}" id="stocktakeForm">
  @csrf
  @if($isEdit) @method('PUT') @endif

  {{-- ── THÔNG TIN PHIẾU ── --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
      Thông tin phiếu
    </div>
    <div class="card-body">
      <div class="row g-3">

        <input type="hidden" name="warehouse_id" value="{{ old('warehouse_id', $inventoryCheck->warehouse_id ?? $warehouses->first()?->id) }}">

        <div class="col-md-4">
          <label class="form-label mb-1" for="check_scope">
            Phạm vi <span class="text-danger">*</span>
          </label>
          <select class="form-select @error('check_scope') is-invalid @enderror" id="check_scope" name="check_scope" required>
            @foreach(\App\Enums\InventoryCheckScope::cases() as $case)
              <option value="{{ $case->value }}"
                {{ old('check_scope', $inventoryCheck->check_scope->value ?? \App\Enums\InventoryCheckScope::EntireWarehouse->value) == $case->value ? 'selected' : '' }}>
                {{ $case->label() }}
              </option>
            @endforeach
          </select>
          @error('check_scope')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1" for="check_type">
            Loại kiểm kê <span class="text-danger">*</span>
          </label>
          <select class="form-select @error('check_type') is-invalid @enderror" id="check_type" name="check_type" required>
            @foreach(\App\Enums\InventoryCheckType::cases() as $case)
              <option value="{{ $case->value }}"
                {{ old('check_type', $inventoryCheck->check_type->value ?? \App\Enums\InventoryCheckType::Quantity->value) == $case->value ? 'selected' : '' }}>
                {{ $case->label() }}
              </option>
            @endforeach
          </select>
          @error('check_type')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1" for="check_date">
            Ngày kiểm kê <span class="text-danger">*</span>
          </label>
          <input type="date" class="form-control @error('check_date') is-invalid @enderror"
                 id="check_date" name="check_date"
                 value="{{ old('check_date', isset($inventoryCheck) ? \Carbon\Carbon::parse($inventoryCheck->check_date)->toDateString() : now()->toDateString()) }}">
          @error('check_date')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-6">
          <label class="form-label mb-1" for="purpose">Mục đích</label>
          <input type="text" class="form-control @error('purpose') is-invalid @enderror"
                 id="purpose" name="purpose" maxlength="200"
                 value="{{ old('purpose', $inventoryCheck->purpose ?? '') }}" placeholder="VD: Kiểm kê định kỳ quý 3...">
          @error('purpose')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-12">
          <label class="form-label mb-1" for="note">Ghi chú</label>
          <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note"
                    rows="2" maxlength="500" placeholder="Ghi chú thêm...">{{ old('note', $inventoryCheck->note ?? '') }}</textarea>
          @error('note')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

      </div>
    </div>
  </div>

  {{-- ── PHẠM VI: THEO KHU VỰC ── --}}
  <div class="card mb-3" id="scope-area" style="display:none">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center" style="min-height:44px">
      <span>Chọn khu vực / vị trí kiểm kê</span>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllLocations()">Chọn tất cả</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearLocations()">Bỏ chọn</button>
      </div>
    </div>
    <div class="card-body">
      @error('location_ids')
        <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
      @enderror
      <div class="row g-2" style="max-height:300px; overflow-y:auto">
        @foreach($locations as $loc)
        <div class="col-sm-6 col-lg-4">
          <div class="form-check">
            <input class="form-check-input location-cb" type="checkbox"
                   name="location_ids[]" value="{{ $loc->id }}"
                   id="loc_{{ $loc->id }}"
                   {{ in_array($loc->id, old('location_ids', $selectedLocationIds ?? [])) ? 'checked' : '' }}>
            <label class="form-check-label small" for="loc_{{ $loc->id }}">
              <span class="fw-semibold">{{ $loc->code }}</span>
              @if($loc->name !== $loc->code)
                <span class="text-body-secondary">— {{ $loc->name }}</span>
              @endif
            </label>
          </div>
        </div>
        @endforeach
        @if($locations->isEmpty())
          <div class="col-12 text-body-secondary small">Không có vị trí nào.</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ACTIONS --}}
  <div class="d-flex justify-content-end gap-2">
    <a href="{{ $isEdit ? route('stocktakes.show', $inventoryCheck) : route('stocktakes.index') }}" class="btn btn-outline-secondary">Hủy</a>
    <button type="submit" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
      {{ $isEdit ? 'Lưu thay đổi' : 'Tạo phiếu kiểm kê' }}
    </button>
  </div>

</form>

@endsection

@push('scripts')
<script>
  // Hiện/ẩn khu vực card theo check_scope
  function updateScopeVisibility() {
    const val = document.getElementById('check_scope').value;
    document.getElementById('scope-area').style.display = val == {{ \App\Enums\InventoryCheckScope::ByArea->value }} ? '' : 'none';
  }

  document.getElementById('check_scope').addEventListener('change', updateScopeVisibility);
  updateScopeVisibility();

  // Chọn/bỏ chọn tất cả vị trí
  function selectAllLocations() {
    document.querySelectorAll('.location-cb').forEach(cb => cb.checked = true);
  }
  function clearLocations() {
    document.querySelectorAll('.location-cb').forEach(cb => cb.checked = false);
  }
</script>
@endpush