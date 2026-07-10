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

<div class="row justify-content-center">
  <div class="col-lg-8">

    <div class="d-flex align-items-center gap-3 mb-4">
      <a href="{{ $isEdit ? route('stocktakes.show', $inventoryCheck) : route('stocktakes.index') }}" class="btn btn-outline-secondary btn-sm">
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
      </a>
      <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu kiểm kê' : 'Tạo phiếu kiểm kê' }}</h4>
        <small class="text-body-secondary">Điền thông tin và chọn phạm vi kiểm kê</small>
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

    <form method="POST" action="{{ $isEdit ? route('stocktakes.update', $inventoryCheck) : route('stocktakes.store') }}" id="stocktakeForm">
      @csrf
      @if($isEdit) @method('PUT') @endif

      {{-- THÔNG TIN CHUNG --}}
      <div class="card mb-4">
        <div class="card-header fw-semibold">Thông tin chung</div>
        <div class="card-body">
          <div class="row g-3">

            {{-- Kho --}}
            <div class="col-sm-6">
              <label class="form-label" for="warehouse_id">
                Kho <span class="text-danger">*</span>
              </label>
              <select class="form-select @error('warehouse_id') is-invalid @enderror"
                      id="warehouse_id" name="warehouse_id" required>
                <option value="">— Chọn kho —</option>
                @foreach($warehouses as $wh)
                  <option value="{{ $wh->id }}" {{ old('warehouse_id', $inventoryCheck->warehouse_id ?? null) == $wh->id ? 'selected' : '' }}>
                    {{ $wh->code }} — {{ $wh->name }}
                  </option>
                @endforeach
              </select>
              @error('warehouse_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Ngày kiểm --}}
            <div class="col-sm-6">
              <label class="form-label" for="check_date">
                Ngày kiểm kê <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control @error('check_date') is-invalid @enderror"
                     id="check_date" name="check_date"
                     value="{{ old('check_date', isset($inventoryCheck) ? \Carbon\Carbon::parse($inventoryCheck->check_date)->toDateString() : now()->toDateString()) }}">
              @error('check_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Phạm vi kiểm kê --}}
            <div class="col-12">
              <label class="form-label fw-semibold">
                Phạm vi kiểm kê <span class="text-danger">*</span>
              </label>
              <div class="d-flex gap-3 flex-wrap">
                @foreach(\App\Enums\InventoryCheckScope::cases() as $case)
                @php
                  $icon = $case === \App\Enums\InventoryCheckScope::EntireWarehouse ? 'cil-storage' : 'cil-location-pin';
                @endphp
                <div class="flex-fill" style="min-width:160px">
                  <input type="radio" class="btn-check" name="check_scope" id="scope_{{ $case->value }}"
                         value="{{ $case->value }}"
                         {{ old('check_scope', $inventoryCheck->check_scope->value ?? \App\Enums\InventoryCheckScope::EntireWarehouse->value) == $case->value ? 'checked' : '' }}>
                  <label class="btn btn-outline-primary w-100 d-flex align-items-center gap-2 justify-content-center"
                         for="scope_{{ $case->value }}">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $icon) }}"></use></svg>
                    {{ $case->label() }}
                  </label>
                </div>
                @endforeach
              </div>
              @error('check_scope')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Loại kiểm kê --}}
            <div class="col-12">
              <label class="form-label fw-semibold">
                Loại kiểm kê <span class="text-danger">*</span>
              </label>
              <div class="d-flex gap-3 flex-wrap">
                @foreach(\App\Enums\InventoryCheckType::cases() as $case)
                @php
                  $icon = match($case) {
                    \App\Enums\InventoryCheckType::Quantity => 'cil-calculator',
                    \App\Enums\InventoryCheckType::Location => 'cil-location-pin',
                    \App\Enums\InventoryCheckType::Both     => 'cil-list-rich',
                  };
                @endphp
                <div class="flex-fill" style="min-width:160px">
                  <input type="radio" class="btn-check" name="check_type" id="type_{{ $case->value }}"
                         value="{{ $case->value }}"
                         {{ old('check_type', $inventoryCheck->check_type->value ?? \App\Enums\InventoryCheckType::Quantity->value) == $case->value ? 'checked' : '' }}>
                  <label class="btn btn-outline-secondary w-100 d-flex align-items-center gap-2 justify-content-center"
                         for="type_{{ $case->value }}">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $icon) }}"></use></svg>
                    {{ $case->label() }}
                  </label>
                </div>
                @endforeach
              </div>
              @error('check_type')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Mục đích --}}
            <div class="col-12">
              <label class="form-label" for="purpose">Mục đích</label>
              <input type="text" class="form-control @error('purpose') is-invalid @enderror"
                     id="purpose" name="purpose" maxlength="200"
                     value="{{ old('purpose', $inventoryCheck->purpose ?? '') }}" placeholder="VD: Kiểm kê định kỳ quý 3...">
              @error('purpose')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Ghi chú --}}
            <div class="col-12">
              <label class="form-label" for="note">Ghi chú</label>
              <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note"
                        rows="2" maxlength="500" placeholder="Ghi chú thêm...">{{ old('note', $inventoryCheck->note ?? '') }}</textarea>
              @error('note')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

          </div>
        </div>
      </div>

      {{-- PHẠM VI: THEO KHU VỰC --}}
      <div class="card mb-4 scope-card" id="scope-area" style="display:none">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
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
  </div>
</div>

@endsection

@push('scripts')
<script>
  // Hiện/ẩn khu vực card theo check_scope
  function updateScopeVisibility() {
    const val = document.querySelector('input[name="check_scope"]:checked')?.value;
    document.getElementById('scope-area').style.display = val == {{ \App\Enums\InventoryCheckScope::ByArea->value }} ? '' : 'none';
  }

  document.querySelectorAll('input[name="check_scope"]').forEach(el => {
    el.addEventListener('change', updateScopeVisibility);
  });
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