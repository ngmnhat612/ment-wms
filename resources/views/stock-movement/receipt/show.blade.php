@extends('layouts.app')

@section('title', 'Phiếu nhập ' . $receipt->code . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('receipts.index') }}">Nhập kho</a></li>
<li class="breadcrumb-item active">{{ $receipt->code }}</li>
@endsection

@section('content')

@php
$fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
$statusMap = [
1 => ['Nháp', 'secondary', 'cil-pencil'],
2 => ['Chờ duyệt', 'warning', 'cil-clock'],
3 => ['Đã duyệt', 'info', 'cil-check'],
4 => ['Hoàn thành', 'success', 'cil-check-circle'],
5 => ['Đã hủy', 'danger', 'cil-x-circle'],
];
[$statusText, $statusColor, $statusIcon] = $statusMap[$receipt->status] ?? ['?', 'secondary', 'cil-info'];
$typeLabels = [1 => 'Từ nhà cung cấp', 2 => 'Trả hàng SX', 3 => 'Khác'];

// Kiểm tra xem có dòng nào dùng serial không (để quyết định có hiện cột Serial)
$hasSerial = $receipt->details->contains(fn($d) =>
in_array((int)($d->product?->tracking_type ?? 1), [3, 4])
);
$hasLot = $receipt->details->contains(fn($d) =>
in_array((int)($d->product?->tracking_type ?? 1), [2, 4])
);
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
            {{ $receipt->code }}
            <span
                class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill fs-6">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $statusIcon) }}"></use>
                </svg>
                {{ $statusText }}
            </span>
        </h4>
        <small class="text-body-secondary">
            Tạo lúc {{ $receipt->created_at?->format('d/m/Y H:i') }}
            @if($receipt->createdBy) bởi <strong>{{ $receipt->createdBy->name }}</strong> @endif
        </small>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        {{-- DRAFT --}}
        @if((int)$receipt->status === 1)
        <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-outline-secondary btn-sm">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use>
            </svg>Chỉnh sửa
        </a>
        <form method="POST" action="{{ route('receipts.submit', $receipt) }}">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use>
                </svg>Gửi duyệt
            </button>
        </form>
        <form method="POST" action="{{ route('receipts.destroy', $receipt) }}"
            onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $receipt->code }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                </svg>Xóa
            </button>
        </form>
        @endif

        {{-- PENDING --}}
        @if((int)$receipt->status === 2 && auth()->user()->can('receipt.approve'))
        <form method="POST" action="{{ route('receipts.approve', $receipt) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                </svg>Duyệt phiếu
            </button>
        </form>
        @endif

        {{-- APPROVED --}}
        @if((int)$receipt->status === 3)
        <button type="button" class="btn btn-success btn-sm" data-coreui-toggle="modal"
            data-coreui-target="#confirmModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
            </svg>Xác nhận nhận hàng
        </button>
        @endif

        {{-- HỦY (trừ completed/cancelled) --}}
        @if(!in_array((int)$receipt->status, [4, 5]))
        <form method="POST" action="{{ route('receipts.cancel', $receipt) }}"
            onsubmit="return confirm('Hủy phiếu {{ $receipt->code }}?\nThao tác này không thể khôi phục.')">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use>
                </svg>Hủy phiếu
            </button>
        </form>
        @endif

        @if((int) $receipt->status === 4)
        <a href="{{ route('receipts.print', $receipt) }}" target="_blank" class="btn btn-outline-primary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use>
            </svg>
            Xuất PDF
        </a>
        @endif

        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary btn-sm">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
            </svg>Quay lại
        </a>
    </div>
</div>

{{-- ALERTS --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible mb-3">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
    </svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-3">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
    </svg>
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif

{{-- TIMELINE --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
            @php $steps = [1 => 'Nháp', 2 => 'Chờ duyệt', 3 => 'Đã duyệt', 4 => 'Hoàn thành']; @endphp
            @foreach($steps as $step => $label)
            @php
            $done = $receipt->status >= $step && $receipt->status !== 5;
            $current = $receipt->status === $step;
            $color = $done ? 'success' : 'secondary';
            $lineClass = $receipt->status > $step ? 'border-success' : 'border-secondary';
            @endphp
            <div class="d-flex flex-column align-items-center flex-fill">
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 border border-2
                    bg-{{ $color }}{{ $current ? '' : '-subtle' }}
                    text-{{ $color }}{{ $current ? ' text-white' : '' }}
                    border-{{ $color }}" style="width:32px;height:32px;font-size:13px">
                    {{ $step }}
                </div>
                <small class="text-{{ $color }} {{ $current ? 'fw-semibold' : '' }}">{{ $label }}</small>
            </div>
            @if($step < 4) <div class="flex-fill border-top border-2 mt-2 mb-auto {{ $lineClass }}"
                style="max-width:60px">
        </div>
        @endif
        @endforeach
    </div>
</div>
</div>

{{-- THÔNG TIN PHIẾU (1 hàng ngang — giống form) --}}
<div class="card mb-3">
    <div class="card-header fw-semibold py-2">
        <svg class="icon me-1 text-primary">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
        </svg>
        Thông tin phiếu
    </div>
    <div class="card-body py-3">
        <div class="row g-3 small">
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Mã phiếu</div>
                <div class="fw-semibold">{{ $receipt->code }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Loại nhập</div>
                <div>{{ $typeLabels[$receipt->receipt_type] ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Nhà cung cấp</div>
                <div>{{ $receipt->supplier?->name ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Số tham chiếu</div>
                <div>{{ $receipt->reference_no ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Ngày nhập</div>
                <div>{{ $receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') : '—' }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Trạng thái</div>
                <span
                    class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                    {{ $statusText }}
                </span>
            </div>
            @if($receipt->note)
            <div class="col-12">
                <div class="text-body-secondary mb-1">Ghi chú</div>
                <div>{{ $receipt->note }}</div>
            </div>
            @endif
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Người tạo</div>
                <div>{{ $receipt->createdBy?->name ?? '—' }}</div>
            </div>
            @if($receipt->confirmedBy)
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Người duyệt</div>
                <div>{{ $receipt->confirmedBy->name }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- CHI TIẾT HÀNG HÓA --}}
<div class="card">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
        <span>
            <svg class="icon me-1 text-primary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
            </svg>
            Chi tiết hàng hóa
        </span>
        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $receipt->details->count() }} dòng</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px" class="text-center">#</th>
                        <th>Hàng hóa</th>
                        <th style="width:70px">ĐVT</th>
                        <th style="width:100px" class="text-end">SL dự kiến</th>
                        <th style="width:150px" class="text-end">SL thực nhận</th>
                        <th style="width:110px">Vị trí kho</th>
                        <th style="width:120px">Tracking</th>
                        @if($hasLot)
                        <th style="width:120px">Số Lot/Batch</th>
                        @endif
                        @if($hasSerial)
                        <th style="width:120px">Số Serial</th>
                        @endif
                        <th style="width:100px">Hạn dùng</th>
                        <th style="width:80px" class="text-center">QC</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipt->details as $i => $detail)
                    @php
                    $tracking = (int)($detail->product?->tracking_type ?? 1);
                    $trackingLabel = [1=>'—', 2=>'Lô', 3=>'Serial', 4=>'Lô+Serial'][$tracking] ?? '—';
                    $trackingColor = [1=>'secondary', 2=>'info', 3=>'warning', 4=>'primary'][$tracking] ?? 'secondary';
                    @endphp
                    <tr>
                        <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $detail->product?->name ?? '—' }}</div>
                            <div class="text-body-secondary" style="font-size:11px">{{ $detail->product?->code }}</div>
                        </td>
                        <td class="text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</td>
                        <td class="text-end fw-semibold small">{{ $fmt($detail->expected_qty) }}</td>
                        <td class="text-end small">
                            @if($detail->actual_qty !== null)
                            <span
                                class="fw-semibold {{ $detail->actual_qty < $detail->expected_qty ? 'text-warning' : 'text-success' }}">
                                {{ $fmt($detail->actual_qty) }}
                            </span>
                            @else
                            <span class="text-body-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @if($detail->location)
                            <span
                                class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                {{ $detail->location->code }}
                            </span>
                            @elseif(isset($putawaySuggestions[$detail->id]))
                            @php $suggested = $putawaySuggestions[$detail->id]; @endphp
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"
                                title="Gợi ý theo Putaway Rule">
                                <svg class="icon icon-sm me-1">
                                    <use
                                        xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-location-pin') }}">
                                    </use>
                                </svg>
                                {{ $suggested->code }}
                            </span>
                            <div style="font-size:10px" class="text-info">Putaway Rule</div>
                            @else
                            <span class="text-body-secondary small">—</span>
                            @endif
                        </td>
                        <td>
                            <span
                                class="badge bg-{{ $trackingColor }}-subtle text-{{ $trackingColor }}-emphasis border border-{{ $trackingColor }}-subtle">
                                {{ $trackingLabel }}
                            </span>
                        </td>
                        @if($hasLot)
                        <td class="small">
                            @if($detail->lot)
                            <span
                                class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace">
                                {{ $detail->lot->lot_number }}
                            </span>
                            @elseif(in_array($tracking, [2,4]))
                            <span class="text-danger small">Chưa có lot</span>
                            @else
                            <span class="text-body-secondary">—</span>
                            @endif
                        </td>
                        @endif
                        @if($hasSerial)
                        <td class="small">
                            @if($detail->serial)
                            <span
                                class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">
                                {{ $detail->serial->serial_number }}
                            </span>
                            @elseif(in_array($tracking, [3,4]))
                            <span class="text-danger small">Chưa có serial</span>
                            @else
                            <span class="text-body-secondary">—</span>
                            @endif
                        </td>
                        @endif
                        <td class="small">
                            @if($detail->expiry_date)
                            @php
                            $expiry = \Carbon\Carbon::parse($detail->expiry_date);
                            $daysLeft = now()->diffInDays($expiry, false);
                            @endphp
                            <span
                                class="{{ $daysLeft < 30 ? 'text-danger fw-semibold' : ($daysLeft < 90 ? 'text-warning' : '') }}">
                                {{ $expiry->format('d/m/Y') }}
                            </span>
                            @if($daysLeft < 30 && $daysLeft>= 0)
                                <div style="font-size:10px" class="text-danger">còn {{ $daysLeft }} ngày</div>
                                @elseif($daysLeft < 0) <div style="font-size:10px" class="text-danger">Đã hết hạn
        </div>
        @endif
        @else
        <span class="text-body-secondary">—</span>
        @endif
        </td>
        <td class="text-center">
            @php
            $qcMap = [0=>['—','secondary'], 1=>['Pass','success'], 2=>['Fail','danger'], 3=>['Pending','warning']];
            [$qcLabel, $qcColor] = $qcMap[$detail->qc_status] ?? ['—','secondary'];
            @endphp
            <span
                class="badge bg-{{ $qcColor }}-subtle text-{{ $qcColor }}-emphasis border border-{{ $qcColor }}-subtle">
                {{ $qcLabel }}
            </span>
        </td>
        </tr>
        @empty
        <tr>
            <td colspan="12" class="text-center text-body-secondary py-4">Không có dòng chi tiết.</td>
        </tr>
        @endforelse
        </tbody>
        </table>
    </div>
</div>

@if($receipt->status === 4)
<div class="card-footer border-success bg-success-subtle text-success d-flex align-items-center gap-2 py-2">
    <svg class="icon">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
    </svg>
    <span class="small fw-semibold">Tồn kho đã được cập nhật thành công.</span>
</div>
@endif
</div>

{{-- MODAL XÁC NHẬN NHẬN HÀNG --}}
@if((int)$receipt->status === 3)
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                    </svg>
                    Xác nhận nhận hàng
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xác nhận đã nhận đủ hàng theo phiếu <strong>{{ $receipt->code }}</strong>?</p>
                <div class="alert alert-info small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use>
                    </svg>
                    Sau khi xác nhận, tồn kho sẽ được cập nhật và không thể hoàn tác.
                    Nếu số lượng thực nhận khác dự kiến, vui lòng chỉnh sửa phiếu trước.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy
                    bỏ</button>
                <form method="POST" action="{{ route('receipts.confirm', $receipt) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}">
                            </use>
                        </svg>
                        Xác nhận & cập nhật tồn kho
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection