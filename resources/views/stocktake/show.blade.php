@extends('layouts.app')

@section('title', 'Phiếu kiểm kê ' . $inventoryCheck->code)

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê</a></li>
  <li class="breadcrumb-item active">{{ $inventoryCheck->code }}</li>
@endsection

@section('content')

@php
  $status       = $inventoryCheck->status;
  $scope        = $inventoryCheck->check_scope;
  $type         = $inventoryCheck->check_type;
  $activeFreeze = $inventoryCheck->activeFreeze;
  $details      = $inventoryCheck->details;
  $diffDetails  = $details->filter(fn($d) => (float) $d->diff_qty !== 0.0);
  $hasDiff      = $diffDetails->isNotEmpty();

  $canEdit   = $status === \App\Enums\InventoryCheckStatus::Draft;
  $canStart  = $status === \App\Enums\InventoryCheckStatus::Draft;
  $canCancel = in_array($status, [\App\Enums\InventoryCheckStatus::Draft, \App\Enums\InventoryCheckStatus::InProgress]);
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h4 class="mb-0 fw-semibold d-flex align-items-center gap-2">
      {{ $inventoryCheck->code }}
      <span class="{{ $status->badgeClass() }}" style="font-size: 0.8rem;">
        {{ $status->label() }}
      </span>
    </h4>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    {{-- DRAFT --}}
    @if($canEdit)
      <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#startModal">
        Bắt đầu
      </button>
      <a href="{{ route('stocktakes.edit', $inventoryCheck) }}" class="btn btn-primary">
        Chỉnh sửa
      </a>
    @endif

    {{-- IN_PROGRESS --}}
    @if($status === \App\Enums\InventoryCheckStatus::InProgress)
      <form method="POST" action="{{ route('stocktakes.complete', $inventoryCheck) }}"
            onsubmit="return confirm('Hoàn thành kiểm kê? Số liệu tồn thực tế sẽ được chốt lại.')">
        @csrf
        <button class="btn btn-success">
          Hoàn thành
        </button>
      </form>
    @endif

    @if($status === \App\Enums\InventoryCheckStatus::Completed && $hasDiff)
      <a href="{{ route('stocktakes.adjustment.create', $inventoryCheck) }}" class="btn btn-warning">
        Điều chỉnh
      </a>
    @endif

    <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary">
      Quay lại
    </a>
  </div>
</div>

{{-- ── THÔNG TIN PHIẾU ── --}}
<div class="card mb-3">
  <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
    Thông tin phiếu
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label mb-1 fw-semibold">Phạm vi</label>
        <div>{{ $scope?->label() ?? '-' }}</div>
      </div>
      <div class="col-md-4">
        <label class="form-label mb-1 fw-semibold">Loại kiểm kê</label>
        <div>{{ $type?->label() ?? '-' }}</div>
      </div>
      <div class="col-md-4">
        <label class="form-label mb-1 fw-semibold">Ngày kiểm kê</label>
        <div>{{ $inventoryCheck->check_date ? \Carbon\Carbon::parse($inventoryCheck->check_date)->format('d/m/Y') : '-' }}</div>
      </div>
      <div class="col-md-4">
        <label class="form-label mb-1 fw-semibold">Mục đích</label>
        <div>{{ $inventoryCheck->purpose ?? '-' }}</div>
      </div>
      <div class="col-md-8">
        <label class="form-label mb-1 fw-semibold">Ghi chú</label>
        <div>{{ $inventoryCheck->note ?? '-' }}</div>
      </div>
    </div>
  </div>
</div>

{{-- ── ĐÓNG BĂNG ── --}}
@if($inventoryCheck->freezes->isNotEmpty())
<div class="card mb-3">
  <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
    Lịch sử đóng băng
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width:10%">Phạm vi</th>
            <th>Người đóng băng</th>
            <th style="width:14%">Thời điểm đóng băng</th>
            <th style="width:14%">Kết thúc</th>
            <th class="text-center" style="width:10%">Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          @foreach($inventoryCheck->freezes as $freeze)
          <tr>
            <td class="small text-center">{{ $freeze->check_type?->label() ?? '-' }}</td>
            <td class="small">{{ $freeze->frozenBy->employee->name ?? '-' }}</td>
            <td class="small">{{ $freeze->frozen_at?->format('H:i d/m/Y') ?? '-' }}</td>
            <td class="small">{{ $freeze->unfrozen_at?->format('H:i d/m/Y') ?? '-' }}</td>
            <td class="text-center">
              @if($freeze->isActive())
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Đang đóng băng</span>
              @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Kết thúc</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif

{{-- ── ĐIỀU CHỈNH LIÊN QUAN ── --}}
@if($inventoryCheck->adjustments->isNotEmpty())
<div class="card mb-3">
  <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
    Phiếu điều chỉnh liên kết
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width:4%">#</th>
            <th style="width:10%">Mã phiếu</th>
            <th>Người tạo</th>
            <th>Người duyệt</th>
            <th style="width:14%">Ngày điều chỉnh</th>
            <th class="text-center" style="width:10%">Trạng thái</th>
            <th class="text-center" style="width:10%">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          @foreach($inventoryCheck->adjustments as $index => $adj)
          <tr>
            <td class="text-center text-body-secondary">{{ $index + 1 }}</td>
            <td class="small"><code>{{ $adj->code }}</code></td>
            <td class="small">{{ $adj->createdBy->employee->name ?? '-' }}</td>
            <td class="small">{{ $adj->approvedBy->employee->name ?? '-' }}</td>
            <td class="small">{{ $adj->adjustment_date ? \Carbon\Carbon::parse($adj->adjustment_date)->format('d/m/Y') : '-' }}</td>
            <td class="text-center">
              <span class="{{ $adj->status->badgeClass() }}" style="font-size:10px">{{ $adj->status->label() }}</span>
            </td>
            <td class="text-center">
              <a href="{{ route('stocktakes.adjustment.show', [$inventoryCheck, $adj]) }}" class="btn btn-sm btn-outline-primary">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list-rich') }}"></use></svg>
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif

{{-- ── CHI TIẾT KIỂM KÊ ── --}}
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center" style="min-height:44px">
    <span class="fw-semibold">Chi tiết kiểm kê</span>
  </div>

  <div class="card-body p-0">
    <form method="POST" action="{{ route('stocktakes.details.update', $inventoryCheck) }}" id="detailsForm">
      @csrf
      @method('PUT')

      @include('stocktake.partials.lines-table', [
        'details'      => $details,
        'canEdit'      => $status === \App\Enums\InventoryCheckStatus::InProgress,
        'highlightDiff' => in_array($status, [\App\Enums\InventoryCheckStatus::Completed, \App\Enums\InventoryCheckStatus::InProgress]),
        'checkType'    => $type,
        'locationOptions' => $locationOptions ?? [],
      ])

      @if($status === \App\Enums\InventoryCheckStatus::InProgress)
      <div class="card-footer d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
          Lưu số liệu
        </button>
      </div>
      @endif
    </form>
  </div>
</div>

{{-- ── NÚT HỦY ── --}}
@if($canCancel)
<div class="d-flex gap-2 justify-content-end mt-3">
  <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal" data-coreui-target="#cancelModal">
    Hủy
  </button>
</div>
@endif

{{-- MODAL XÁC NHẬN BẮT ĐẦU --}}
@if($canStart)
<div class="modal fade" id="startModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center px-4 pb-2">
        <svg class="icon icon-3xl text-success mb-3">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
        </svg>
        <h6 class="fw-semibold mb-1">Xác nhận bắt đầu</h6>
        <p class="text-body-secondary small mb-0">
          Bắt đầu kiểm kê phiếu<br>
          <strong class="text-body">{{ $inventoryCheck->code }}</strong>?
        </p>
        <p class="text-warning small mt-1">Hệ thống sẽ đóng băng khu vực kiểm kê liên quan.</p>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
        <form method="POST" action="{{ route('stocktakes.start', $inventoryCheck) }}" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-success btn-sm">Bắt đầu</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endif

{{-- MODAL XÁC NHẬN HỦY PHIẾU --}}
@if($canCancel)
<div class="modal fade" id="cancelModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center px-4 pb-2">
        <svg class="icon icon-3xl text-danger mb-3">
          <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
        </svg>
        <h6 class="fw-semibold mb-1">Xác nhận hủy</h6>
        <p class="text-body-secondary small mb-0">
          Bạn có chắc muốn hủy phiếu<br>
          <strong class="text-body">{{ $inventoryCheck->code }}</strong>?
        </p>
        <p class="text-danger small mt-1">Thao tác này không thể hoàn tác.</p>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
        <form method="POST" action="{{ route('stocktakes.cancel', $inventoryCheck) }}" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-danger btn-sm">Hủy</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endif

@endsection