@extends('layouts.app')

@section('title', 'Phiếu xuất ' . $issue->code . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('issues.index') }}">Xuất kho</a></li>
<li class="breadcrumb-item active">{{ $issue->code }}</li>
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
$issueStatus = (int) $issue->status;
[$statusText, $statusColor, $statusIcon] = $statusMap[$issueStatus] ?? ['?', 'secondary', 'cil-info'];
$typeLabels = [1 => 'Sản xuất', 2 => 'Bảo trì', 3 => 'Mượn', 4 => 'Khác'];
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
            {{ $issue->code }}
            <span
                class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill fs-6">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $statusIcon) }}"></use>
                </svg>
                {{ $statusText }}
            </span>
        </h4>
        <small class="text-body-secondary">
            Tạo lúc {{ $issue->created_at?->format('d/m/Y H:i') }}
            @if($issue->creator) bởi {{ $issue->creator->name }} @endif
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">

        {{-- DRAFT: Sửa / Gửi duyệt / Xóa --}}
        @if($issueStatus === 1)
        <a href="{{ route('issues.edit', $issue) }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use>
            </svg>
            Chỉnh sửa
        </a>
        <form method="POST" action="{{ route('issues.submit', $issue) }}">
            @csrf
            <button type="submit" class="btn btn-warning">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use>
                </svg>
                Gửi duyệt
            </button>
        </form>
        <form method="POST" action="{{ route('issues.destroy', $issue) }}"
            onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $issue->code }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                </svg>
                Xóa
            </button>
        </form>
        @endif

        {{-- PENDING: Duyệt & giữ hàng --}}
        @if($issueStatus === 2 && auth()->user()->can('issue.approve'))
        <button type="button" class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#approveModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
            </svg>
            Duyệt & Giữ hàng
        </button>
        @endif

        {{-- APPROVED: Xuất kho thực tế --}}
        @if($issueStatus === 3)
        <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
            </svg>
            Xuất kho thực tế
        </button>
        @endif

        {{-- COMPLETED: In phiếu xuất kho --}}
        @if($issueStatus === 4)
        <a href="{{ route('issues.print', $issue) }}" target="_blank" class="btn btn-outline-primary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use>
            </svg>
            Xuất PDF
        </a>
        @endif

        {{-- HỦY: hiện khi chưa Hoàn thành / Đã hủy --}}
        @if(!in_array($issueStatus, [4, 5]))
        @php $cancelExtra = ($issueStatus === 3) ? '\nHàng đang giữ sẽ được giải phóng.' : ''; @endphp
        <form method="POST" action="{{ route('issues.cancel', $issue) }}"
            onsubmit="return confirm('Hủy phiếu {{ $issue->code }}?{{ $cancelExtra }}')">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use>
                </svg>
                Hủy phiếu
            </button>
        </form>
        @endif

        <a href="{{ route('issues.index') }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
            </svg>
            Quay lại
        </a>
    </div>
</div>

{{-- ALERTS --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
    </svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
    </svg>
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif

{{-- CẢNH BÁO KHI ĐÃ APPROVED --}}
@if($issueStatus === 3)
<div class="alert alert-info mb-4 d-flex align-items-center gap-2">
    <svg class="icon icon-xl flex-shrink-0">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
    </svg>
    <div>
        <strong>Hàng đang được giữ chỗ.</strong>
        Tồn kho khả dụng đã bị khóa cho phiếu này. Bấm <strong>"Xuất kho thực tế"</strong> để trừ kho hoặc
        <strong>"Hủy phiếu"</strong> để giải phóng.
    </div>
</div>
@endif

{{-- TIMELINE TRẠNG THÁI --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
            @php $steps = [1 => 'Nháp', 2 => 'Chờ duyệt', 3 => 'Đã duyệt', 4 => 'Hoàn thành']; @endphp
            @foreach($steps as $step => $label)
            @php
            $done = $issueStatus >= $step && $issueStatus !== 5;
            $current = $issueStatus === $step;
            $color = $done ? 'success' : 'secondary';
            $lineClass = $issueStatus > $step ? 'border-success' : 'border-secondary';
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

{{-- THÔNG TIN PHIẾU --}}
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
                <div class="fw-semibold">{{ $issue->code }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Loại xuất</div>
                <div>{{ $typeLabels[$issue->issue_type] ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Ngày xuất</div>
                <div>{{ $issue->issue_date ? \Carbon\Carbon::parse($issue->issue_date)->format('d/m/Y') : '—' }}</div>
            </div>
            @if($issue->expected_return_date)
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Hạn trả</div>
                <div class="text-warning fw-semibold">
                    {{ \Carbon\Carbon::parse($issue->expected_return_date)->format('d/m/Y') }}</div>
            </div>
            @endif
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Người y/c</div>
                <div>{{ $issue->requester?->name ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Số tham chiếu</div>
                <div>{{ $issue->reference_no ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Trạng thái</div>
                <span
                    class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                    {{ $statusText }}
                </span>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Người tạo</div>
                <div>{{ $issue->creator?->name ?? '—' }}</div>
            </div>
            @if($issue->confirmer)
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Người duyệt</div>
                <div>{{ $issue->confirmer->name }}</div>
            </div>
            @endif
            @if($issue->note)
            <div class="col-12">
                <div class="text-body-secondary mb-1">Ghi chú</div>
                <div>{{ $issue->note }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- CHI TIẾT HÀNG HÓA --}}
<div class="card mb-3">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
        <span>
            <svg class="icon me-1 text-primary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
            </svg>
            Chi tiết hàng hóa
        </span>
        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $issue->details->count() }} dòng</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                @php
                $hasLot = $issue->details->contains(fn($d) => in_array((int)($d->product?->tracking_type ??
                1),
                [2,4]));
                $hasSerial = $issue->details->contains(fn($d) => in_array((int)($d->product?->tracking_type
                ??
                1), [3,4]));
                @endphp
                <thead class="table-light">
                    <tr>
                        <th style="width:36px" class="text-center">#</th>
                        <th>Hàng hóa</th>
                        <th style="width:70px">ĐVT</th>
                        <th style="width:100px" class="text-end">Số lượng</th>
                        <th style="width:110px">Vị trí kho</th>
                        <th style="width:120px">Tracking</th>
                        @if($hasLot)
                        <th style="width:120px">Số Lot/Batch</th>
                        @endif
                        @if($hasSerial)
                        <th style="width:120px">Số Serial</th>
                        @endif
                        <th style="width:100px">Ghi chú</th>
                        @if(in_array($issueStatus, [1, 2, 3]))
                        <th style="width:90px">Gợi ý</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($issue->details as $i => $detail)
                    @php
                    $tracking = (int)($detail->product?->tracking_type ?? 1);
                    $trackingLabel = [1=>'—', 2=>'Lô', 3=>'Serial', 4=>'Lô+Serial'][$tracking] ?? '—';
                    $trackingColor = [1=>'secondary', 2=>'info', 3=>'warning', 4=>'primary'][$tracking] ??
                    'secondary';
                    @endphp
                    <tr>
                        <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $detail->product?->name ?? '—' }}</div>
                            <div class="text-body-secondary" style="font-size:11px">
                                {{ $detail->product?->code }}</div>
                        </td>
                        <td class="text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</td>
                        <td class="text-end fw-semibold small">{{ $fmt($detail->quantity) }}</td>
                        <td>
                            @if($detail->location)
                            <span
                                class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                {{ $detail->location->code }}
                            </span>
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
                        <td class="text-body-secondary small">{{ $detail->note ?? '—' }}</td>
                        @if(in_array($issueStatus, [1, 2, 3]))
                        <td>
                            @if($suggestions[$detail->id]?->isNotEmpty())
                            {{-- [SỬA 2] Dùng data-id thay vì {{ }} trong onclick --}}
                            <button type="button" class="btn btn-sm btn-outline-info" data-id="{{ $detail->id }}"
                                onclick="showSuggestion(this.dataset.id)" title="Xem gợi ý Lot/Serial">
                                <svg class="icon">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lightbulb') }}">
                                    </use>
                                </svg>
                            </button>
                            @else
                            <span class="text-danger small" title="Không đủ tồn kho">
                                <svg class="icon">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}">
                                    </use>
                                </svg>
                            </span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-body-secondary py-4">Không có dòng chi tiết.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Bảng gợi ý Lot/Serial (ẩn, hiện khi bấm nút) --}}
@if(in_array($issueStatus, [1, 2, 3]))
@foreach($issue->details as $detail)
@if($suggestions[$detail->id]?->isNotEmpty())
<div id="suggestion-{{ $detail->id }}" class="card mt-3 border-info d-none">
    <div class="card-header bg-info-subtle text-info-emphasis fw-semibold small d-flex justify-content-between">
        <span>
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lightbulb') }}"></use>
            </svg>
            Gợi ý FEFO/FIFO —
            {{ $detail->product?->name }}
        </span>
        {{-- [SỬA 3] Dùng data-id thay vì {{ }} trong onclick --}}
        <button type="button" class="btn-close btn-close-sm" data-id="{{ $detail->id }}"
            onclick="hideSuggestion(this.dataset.id)"></button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Vị trí</th>
                    <th>Lot/Serial</th>
                    <th class="text-end pe-4">SL gợi ý</th>
                    <th class="ps-3">Hạn dùng</th>
                </tr>
            </thead>
            <tbody>
                @foreach($suggestions[$detail->id] as $s)
                @php
                $trackingLabel = null;
                if (!empty($s['lot_id'])) {
                $trackingLabel = \App\Models\Lot::find($s['lot_id'])?->lot_number ?? '—';
                } elseif (!empty($s['serial_id'])) {
                $trackingLabel = \App\Models\Serial::find($s['serial_id'])?->serial_number ?? '—';
                }
                @endphp
                <tr>
                    <td class="small">
                        <span class="badge bg-secondary-subtle text-secondary-emphasis border">
                            {{ \App\Models\Location::find($s['location_id'])?->code ?? $s['location_id'] }}
                        </span>
                    </td>
                    <td class="small text-body-secondary">
                        {{ $trackingLabel ?? '—' }}
                    </td>
                    <td class="text-end fw-semibold pe-4">{{ number_format($s['qty_suggest'], 3) }}</td>
                    <td
                        class="small ps-3 {{ !empty($s['expiry_date']) && \Carbon\Carbon::parse($s['expiry_date'])->diffInDays(now(), false) > 0 ? 'text-danger' : '' }}">
                        {{ !empty($s['expiry_date']) ? \Carbon\Carbon::parse($s['expiry_date'])->format('d/m/Y') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach
@endif
</div>
</div>

{{-- MODAL DUYỆT PHIẾU (PENDING → APPROVED) --}}
@if($issueStatus === 2)
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                    </svg>
                    Duyệt phiếu xuất
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Duyệt phiếu <strong>{{ $issue->code }}</strong> và giữ chỗ hàng trong kho?</p>
                <div class="alert alert-warning small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
                    </svg>
                    Hệ thống sẽ tự động <strong>giữ chỗ</strong> (reserve) số lượng tương ứng theo chiến lược
                    FIFO/FEFO.
                    Nếu không đủ hàng, thao tác này sẽ thất bại.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('issues.approve', $issue) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                        </svg>
                        Duyệt & Giữ hàng
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL HOÀN TẤT XUẤT KHO (APPROVED → COMPLETED) --}}
@if($issueStatus === 3)
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}">
                        </use>
                    </svg>
                    Xác nhận xuất kho thực tế
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xác nhận đã lấy hàng theo phiếu <strong>{{ $issue->code }}</strong>?</p>
                <div class="alert alert-danger small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                    Sau khi xác nhận, tồn kho sẽ bị <strong>trừ thực tế</strong> và không thể hoàn tác.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('issues.confirm', $issue) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}">
                            </use>
                        </svg>
                        Xuất kho & trừ tồn
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function showSuggestion(id) {
    document.querySelectorAll('[id^="suggestion-"]').forEach(el => el.classList.add('d-none'));
    document.getElementById('suggestion-' + id)?.classList.remove('d-none');
}

function hideSuggestion(id) {
    document.getElementById('suggestion-' + id)?.classList.add('d-none');
}
</script>
@endpush