@extends('layouts.app')

@section('title', 'Phiếu kiểm kê ' . $inventoryCheck->code)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê kho</a></li>
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
  $canFreeze = $status === \App\Enums\InventoryCheckStatus::InProgress && ! optional($activeFreeze)->isActive();
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h4 class="mb-0 fw-semibold d-flex align-items-center gap-2">
      {{ $inventoryCheck->code }}
      <span class="{{ $status->badgeClass() }}" style="font-size: 0.8rem;">
        {{ $status->label() }}
      </span>
      @if(optional($activeFreeze)->isActive())
        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill" style="font-size:0.75rem">
          <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
          Đang đóng băng
        </span>
      @endif
    </h4>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    {{-- DRAFT --}}
    @if($canEdit)
      <a href="{{ route('stocktakes.edit', $inventoryCheck) }}" class="btn btn-primary">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
        Chỉnh sửa
      </a>
      <form method="POST" action="{{ route('stocktakes.start', $inventoryCheck) }}"
            onsubmit="return confirm('Bắt đầu kiểm kê? Trạng thái phiếu sẽ chuyển sang Đang kiểm kê.')">
        @csrf
        <button class="btn btn-success">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-media-play') }}"></use></svg>
          Bắt đầu kiểm kê
        </button>
      </form>
    @endif

    {{-- IN_PROGRESS --}}
    @if($status === \App\Enums\InventoryCheckStatus::InProgress)
      @if($canFreeze)
        <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal" data-coreui-target="#freezeModal">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
          Đóng băng kho
        </button>
      @elseif(optional($activeFreeze)->isActive())
        <form method="POST" action="{{ route('stocktakes.unfreeze', [$inventoryCheck, $activeFreeze]) }}"
              onsubmit="return confirm('Mở đóng băng? Kho sẽ cho phép giao dịch trở lại.')">
          @csrf
          <button class="btn btn-outline-secondary">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-unlocked') }}"></use></svg>
            Mở đóng băng
          </button>
        </form>
      @endif

      <form method="POST" action="{{ route('stocktakes.complete', $inventoryCheck) }}"
            onsubmit="return confirm('Hoàn thành kiểm kê? Số liệu tồn thực tế sẽ được chốt lại.')">
        @csrf
        <button class="btn btn-success">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          Hoàn thành kiểm kê
        </button>
      </form>
    @endif

    {{-- COMPLETED + có chênh lệch chưa xử lý --}}
    @if($status === \App\Enums\InventoryCheckStatus::Completed && $hasDiff)
      <a href="{{ route('stocktakes.adjustment.create', $inventoryCheck) }}" class="btn btn-danger">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-balance-scale') }}"></use></svg>
        Tạo phiếu điều chỉnh
      </a>
    @endif

    @if(in_array($status, [\App\Enums\InventoryCheckStatus::Draft, \App\Enums\InventoryCheckStatus::InProgress]))
      <form method="POST" action="{{ route('stocktakes.cancel', $inventoryCheck) }}"
            onsubmit="return confirm('Hủy phiếu kiểm kê này?')">
        @csrf
        <button class="btn btn-outline-danger">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
          Hủy phiếu
        </button>
      </form>
    @endif

    <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary">
      Quay lại
    </a>
  </div>
</div>

{{-- ALERTS --}}
@if(session('success'))
  <div class="alert alert-success alert-dismissible mb-4">
    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
  </div>
@endif
@if(session('error'))
  <div class="alert alert-danger alert-dismissible mb-4">
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
  </div>
@endif

{{-- ── THÔNG TIN PHIẾU ── --}}
<div class="card mb-3">
  <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
    Thông tin phiếu
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label mb-1">Kho</label>
        <div class="fw-semibold">{{ $inventoryCheck->warehouse->name ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Phạm vi</label>
        <div>{{ $scope?->label() ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Loại kiểm kê</label>
        <div>{{ $type?->label() ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Ngày kiểm kê</label>
        <div>{{ $inventoryCheck->check_date ? \Carbon\Carbon::parse($inventoryCheck->check_date)->format('d/m/Y') : '-' }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Người tạo</label>
        <div>{{ $inventoryCheck->createdBy->employee->name ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Hoàn thành lúc</label>
        <div>{{ $inventoryCheck->completed_at ? $inventoryCheck->completed_at->format('H:i d/m/Y') : '-' }}</div>
      </div>
      <div class="col-md-6">
        <label class="form-label mb-1">Mục đích</label>
        <div>{{ $inventoryCheck->purpose ?? '-' }}</div>
      </div>
      @if($inventoryCheck->note)
      <div class="col-12">
        <label class="form-label mb-1">Ghi chú</label>
        <div>{{ $inventoryCheck->note }}</div>
      </div>
      @endif
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
            <th>Phạm vi</th>
            <th>Người đóng băng</th>
            <th>Thời điểm đóng băng</th>
            <th>Thời điểm mở</th>
            <th class="text-center">Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          @foreach($inventoryCheck->freezes as $freeze)
          <tr>
            <td class="small">{{ $freeze->check_type?->label() ?? '-' }}</td>
            <td class="small">{{ $freeze->frozenBy->employee->name ?? '-' }}</td>
            <td class="small">{{ $freeze->frozen_at?->format('H:i d/m/Y') ?? '-' }}</td>
            <td class="small">{{ $freeze->unfrozen_at?->format('H:i d/m/Y') ?? '-' }}</td>
            <td class="text-center">
              @if($freeze->isActive())
                <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill" style="font-size:10px">Đang đóng băng</span>
              @else
                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle rounded-pill" style="font-size:10px">Đã mở</span>
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
    Phiếu điều chỉnh liên quan
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Mã phiếu</th>
            <th>Ngày điều chỉnh</th>
            <th>Người tạo</th>
            <th class="text-center">Trạng thái</th>
            <th class="text-end" style="width:60px"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($inventoryCheck->adjustments as $adj)
          <tr>
            <td class="small"><code>{{ $adj->code }}</code></td>
            <td class="small">{{ $adj->adjustment_date ? \Carbon\Carbon::parse($adj->adjustment_date)->format('d/m/Y') : '-' }}</td>
            <td class="small">{{ $adj->createdBy->employee->name ?? '-' }}</td>
            <td class="text-center">
              <span class="{{ $adj->status->badgeClass() }}" style="font-size:10px">{{ $adj->status->label() }}</span>
            </td>
            <td class="text-end">
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
    <small class="text-body-secondary">{{ $details->count() }} dòng</small>
  </div>

  <div class="card-body p-0">
    <form method="POST" action="{{ route('stocktakes.details.update', $inventoryCheck) }}" id="detailsForm">
      @csrf
      @method('PUT')

      @include('stocktake.partials.lines-table', [
        'details'      => $details,
        'canEdit'      => $status === \App\Enums\InventoryCheckStatus::InProgress,
        'highlightDiff' => in_array($status, [\App\Enums\InventoryCheckStatus::Completed, \App\Enums\InventoryCheckStatus::InProgress]),
      ])

      @if($status === \App\Enums\InventoryCheckStatus::InProgress)
      <div class="card-footer d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
          Lưu số liệu đếm
        </button>
      </div>
      @endif
    </form>
  </div>
</div>

{{-- MODAL ĐÓNG BĂNG --}}
@if($canFreeze)
<div class="modal fade" id="freezeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('stocktakes.freeze', $inventoryCheck) }}">
        @csrf
        <div class="modal-header">
          <h6 class="modal-title fw-semibold">Đóng băng kho để kiểm kê</h6>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-body-secondary small mb-3">
            Đóng băng sẽ tạm khóa giao dịch nhập/xuất trên phạm vi tương ứng cho đến khi mở lại.
          </p>
          <label class="form-label">Phạm vi đóng băng</label>
          <select class="form-select" name="freeze_scope" required>
            @foreach(\App\Enums\InventoryCheckScope::cases() as $case)
              <option value="{{ $case->value }}" {{ $scope === $case ? 'selected' : '' }}>{{ $case->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="modal-footer border-0 justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-danger btn-sm">Xác nhận đóng băng</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@endsection