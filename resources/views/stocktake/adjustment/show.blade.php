@extends('layouts.app')

@section('title', 'Phiếu điều chỉnh ' . $adjustment->code)

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê</a></li>
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
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h4 class="mb-0 fw-semibold">{{ $adjustment->code }}</h4>
      <span class="{{ $status->badgeClass() }}" style="font-size: 0.8rem;">
        {{ $status->label() }}
      </span>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    @if($canComplete)
      <form method="POST" action="{{ route('stocktakes.adjustment.complete', [$inventoryCheck, $adjustment]) }}"
            onsubmit="return confirm('Xác nhận điều chỉnh? Tồn kho sẽ được cập nhật ngay lập tức và không thể hoàn tác.')">
        @csrf
        <button class="btn btn-success">
          Duyệt
        </button>
      </form>
    @endif

    @if($canCancel)
      <a href="{{ route('stocktakes.adjustment.edit', [$inventoryCheck, $adjustment]) }}" class="btn btn-primary">
        Chỉnh sửa
      </a>
    @endif

    <a href="{{ route('stocktakes.show', $inventoryCheck) }}" class="btn btn-outline-secondary">
      Quay lại
    </a>
  </div>
</div>

{{-- THÔNG TIN PHIẾU --}}
<div class="card mb-3">
  <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
    Thông tin phiếu
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-sm-3">
        <label class="form-label mb-1 d-block fw-semibold">Mã phiếu</label>
        <input type="text" class="form-control" value="{{ $adjustment->code }}" disabled>
      </div>
      <div class="col-sm-3">
        <label class="form-label mb-1 d-block fw-semibold">Từ phiếu kiểm kê</label>
        <input type="text" class="form-control" value="{{ $inventoryCheck->code }}" disabled>
      </div>
      <div class="col-sm-3">
        <label class="form-label fw-semibold">Ngày điều chỉnh</label>
        <input type="text" class="form-control"
               value="{{ $adjustment->adjustment_date ? \Carbon\Carbon::parse($adjustment->adjustment_date)->format('d/m/Y') : '—' }}" disabled>
      </div>
      <div class="col-sm-3">
        <label class="form-label fw-semibold">Ghi chú</label>
        <textarea class="form-control" rows="1" disabled>{{ $adjustment->note }}</textarea>
      </div>
    </div>
  </div>
</div>

{{-- BẢNG CHÊNH LỆCH --}}
<div class="card mb-3">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center" style="min-height:44px">
    <span class="fw-semibold">Chi tiết điều chỉnh tồn kho</span>
  </div>

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
            <td class="text-center text-body-secondary">{{ $loop->iteration }}</td>
            <td>
              <div class="fw-semibold small">{{ $d->product->name ?? '—' }}</div>
              <div class="text-body-secondary" style="font-size:11px">{{ $d->product->code ?? '' }}</div>
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
          </tr>
          @empty
          <tr>
            <td colspan="9" class="text-center text-body-secondary py-5">Không có dữ liệu.</td>
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
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>

@if($canCancel)
<div class="d-flex gap-2 justify-content-end mt-3">
  <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal" data-coreui-target="#cancelModal">
    Hủy
  </button>
</div>
@endif

@endsection