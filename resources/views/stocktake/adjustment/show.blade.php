@extends('layouts.app')

@section('title', 'Phiếu điều chỉnh ' . $adjustment->code)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê kho</a></li>
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.show', $inventoryCheck) }}">{{ $inventoryCheck->code }}</a></li>
  <li class="breadcrumb-item active">{{ $adjustment->code }}</li>
@endsection

@section('content')

@php
  $status      = $adjustment->status;
  $details     = $adjustment->details;
  $plusLines   = $details->filter(fn($d) => (float) $d->diff_qty > 0);
  $minusLines  = $details->filter(fn($d) => (float) $d->diff_qty < 0);
  $totalPlus   = $plusLines->sum(fn($d) => (float) $d->diff_qty);
  $totalMinus  = $minusLines->sum(fn($d) => (float) $d->diff_qty);
  $netTotal    = $totalPlus + $totalMinus;

  $canComplete = $status === \App\Enums\DocumentStatus::Draft;
  $canCancel   = $status === \App\Enums\DocumentStatus::Draft;
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h4 class="mb-0 fw-semibold">{{ $adjustment->code }}</h4>
      <span class="{{ $status->badgeClass() }}" style="font-size: 0.8rem;">
        {{ $status->label() }}
      </span>
    </div>
    <small class="text-body-secondary">
      Từ phiếu kiểm kê:
      <a href="{{ route('stocktakes.show', $inventoryCheck) }}" class="text-decoration-none">{{ $inventoryCheck->code }}</a>
      &nbsp;·&nbsp; Ngày: {{ $adjustment->adjustment_date ? \Carbon\Carbon::parse($adjustment->adjustment_date)->format('d/m/Y') : '—' }}
      &nbsp;·&nbsp; Người tạo: {{ $adjustment->createdBy->employee->name ?? '—' }}
    </small>
  </div>

  <div class="d-flex gap-2">
    <a href="{{ route('stocktakes.show', $inventoryCheck) }}" class="btn btn-outline-secondary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
      Quay lại
    </a>

    @if($canCancel)
      <form method="POST" action="{{ route('stocktakes.adjustment.cancel', [$inventoryCheck, $adjustment]) }}"
            onsubmit="return confirm('Hủy phiếu điều chỉnh này?')">
        @csrf
        <button class="btn btn-outline-danger">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
          Hủy phiếu
        </button>
      </form>
    @endif

    @if($canComplete)
      <form method="POST" action="{{ route('stocktakes.adjustment.complete', [$inventoryCheck, $adjustment]) }}"
            onsubmit="return confirm('Xác nhận điều chỉnh? Tồn kho sẽ được cập nhật ngay lập tức và không thể hoàn tác.')">
        @csrf
        <button class="btn btn-danger">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          Xác nhận điều chỉnh
        </button>
      </form>
    @endif
  </div>
</div>

{{-- THÔNG TIN PHIẾU --}}
<div class="card mb-4">
  <div class="card-header fw-semibold">Thông tin phiếu điều chỉnh</div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-sm-6 col-lg-3">
        <div class="text-body-secondary small mb-1">Mã phiếu</div>
        <div class="fw-semibold">{{ $adjustment->code }}</div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="text-body-secondary small mb-1">Phiếu kiểm kê</div>
        <a href="{{ route('stocktakes.show', $inventoryCheck) }}" class="fw-semibold text-decoration-none">
          {{ $inventoryCheck->code }}
        </a>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="text-body-secondary small mb-1">Ngày điều chỉnh</div>
        <div>{{ $adjustment->adjustment_date ? \Carbon\Carbon::parse($adjustment->adjustment_date)->format('d/m/Y') : '—' }}</div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="text-body-secondary small mb-1">Trạng thái</div>
        <span class="{{ $status->badgeClass() }}">
          {{ $status->label() }}
        </span>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="text-body-secondary small mb-1">Người tạo</div>
        <div>{{ $adjustment->createdBy->employee->name ?? '—' }}</div>
      </div>
      @if($adjustment->approvedBy)
      <div class="col-sm-6 col-lg-3">
        <div class="text-body-secondary small mb-1">Người duyệt</div>
        <div>{{ $adjustment->approvedBy->employee->name ?? '—' }}</div>
      </div>
      @endif
      @if($adjustment->note)
      <div class="col-12">
        <div class="text-body-secondary small mb-1">Ghi chú</div>
        <div>{{ $adjustment->note }}</div>
      </div>
      @endif
    </div>
  </div>
</div>

{{-- BẢNG CHÊNH LỆCH --}}
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Chi tiết điều chỉnh tồn kho</span>
    <small class="text-body-secondary">{{ $details->count() }} dòng</small>
  </div>

  @if($canComplete)
  <div class="alert alert-warning mx-3 mt-3 mb-0 d-flex align-items-start gap-2">
    <svg class="icon flex-shrink-0 mt-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
    <div>
      <strong>Lưu ý trước khi xác nhận:</strong> Thao tác "Xác nhận điều chỉnh" sẽ cập nhật tồn kho thực tế và ghi vào
      sổ cái kho. Hành động này <strong>không thể hoàn tác</strong>.
    </div>
  </div>
  @endif

  <div class="card-body p-0 mt-3">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:36px">#</th>
            <th>Mặt hàng</th>
            <th>Vị trí HT</th>
            <th>Vị trí thực tế</th>
            <th>Lô</th>
            <th style="width:50px">ĐVT</th>
            <th class="text-end" style="width:100px">Tồn HT</th>
            <th class="text-end" style="width:100px">Thực tế</th>
            <th class="text-end" style="width:100px">Chênh lệch</th>
            <th class="text-center" style="width:90px">Loại</th>
          </tr>
        </thead>
        <tbody>
          @forelse($details as $d)
          @php
            $diff     = (float) $d->diff_qty;
            $isPlus   = $diff > 0;
            $rowClass = $isPlus ? 'table-success' : 'table-danger';
          @endphp
          <tr class="{{ $rowClass }}">
            <td class="text-body-secondary small">{{ $loop->iteration }}</td>
            <td>
              <div class="fw-semibold small">{{ $d->product->name ?? '—' }}</div>
              <div class="text-body-secondary" style="font-size:11px">{{ $d->product->code ?? '' }}</div>
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
            <td class="text-center">
              @if($isPlus)
                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill" style="font-size:10px">
                  <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
                  Tăng tồn
                </span>
              @else
                <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill" style="font-size:10px">
                  <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-bottom') }}"></use></svg>
                  Giảm tồn
                </span>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="text-center text-body-secondary py-5">Không có dữ liệu.</td>
          </tr>
          @endforelse
        </tbody>
        @if($details->count() > 0)
        <tfoot class="table-light fw-semibold">
          <tr>
            <td colspan="6" class="text-end">Tổng:</td>
            <td class="text-end">{{ number_format($details->sum('system_qty'), 0) }}</td>
            <td class="text-end">{{ number_format($details->sum('actual_qty'), 0) }}</td>
            <td class="text-end">
              <span class="{{ $netTotal >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $netTotal >= 0 ? '+' : '' }}{{ number_format($netTotal, 0) }}
              </span>
            </td>
            <td></td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>

@endsection