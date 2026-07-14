@extends('layouts.app')

@section('title', 'Phiếu xuất ' . $issue->code)

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('stock-movements.index') }}">Nhập/Xuất kho</a></li>
<li class="breadcrumb-item active">{{ $issue->code }}</li>
@endsection

@section('content')

@php
$fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
$typeLabels = [1 => 'Sản xuất', 2 => 'Bảo trì', 3 => 'Mượn', 4 => 'Trả NCC'];
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            {{ $issue->code }}
            <span class="{{ $issue->status->badgeClass() }}" style="font-size: 0.8rem;">
                {{ $issue->status->label() }}
            </span>
        </h4>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        {{-- DRAFT --}}
        @if($issue->status === \App\Enums\DocumentStatus::Draft)
        <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            Duyệt
        </button>

        <a href="{{ route('issues.edit', $issue) }}" class="btn btn-primary">
            Chỉnh sửa
        </a>
        @endif

        {{-- COMPLETED
        @if($issue->status === \App\Enums\DocumentStatus::Completed)
        <a href="{{ route('issues.print', $issue) }}" target="_blank" class="btn btn-outline-primary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use>
            </svg>
            Xuất PDF
        </a>
        @endif --}}

        <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary">
            Quay lại
        </a>
    </div>
</div>

{{-- ── THÔNG TIN PHIẾU (khớp đúng field của form tạo phiếu) ── --}}
<div class="card mb-3">
    <div class="card-header fw-semibold d-flex align-items-center" style="min-height:44px">
        Thông tin phiếu
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label mb-1">Mã phiếu</label>
                <div class="fw-semibold">{{ $issue->code }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Phiếu liên kết</label>
                <div>
                    @if($issue->stockOutRequest)
                        <a href="{{ route('stock-out-requests.show', $issue->stockOutRequest) }}">
                            {{ $issue->stockOutRequest->code }}
                        </a>
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Ngày xuất</label>
                <div>{{ $issue->issue_date ? \Carbon\Carbon::parse($issue->issue_date)->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Ghi chú</label>
                <div>{{ $issue->note ?? '-' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── CHI TIẾT PHIẾU (đúng cột như form tạo phiếu) ── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center" style="min-height:44px">
        <span class="fw-semibold">
            Chi tiết phiếu
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:2%">#</th>
                        <th style="min-width:180px">Vật tư</th>
                        <th style="width:8%">TSKT</th>
                        <th style="width:4%">ĐVT</th>
                        <th style="width:6%" class="text-end">Xuất</th>
                        <th style="width:6%" class="text-end">Thực xuất</th>
                        <th style="width:10%">Vị trí</th>
                        <th style="width:10%">Người nhận</th>
                        <th style="width:6%">SN</th>
                        <th style="width:6%">Lô</th>
                        <th style="min-width:200px">Sê-ri</th>
                        <th style="width:6%">Kho phụ</th>
                        <th style="min-width:120px">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issue->lines as $i => $line)
                        @php
                            $tracking = (int)($line->product?->tracking_type?->value ?? 1);
                            $firstDetail = $line->details->first();
                            $actualQty = $tracking === 2
                                ? $line->details->count()
                                : ($firstDetail->actual_qty ?? 0);
                            $serialNumbers = $line->details->pluck('serial.serial_number')->filter()->implode(' ');
                            $lotNumber = $firstDetail->lot?->lot_number ?? '-';
                        @endphp
                        <tr>
                            <td class="text-center text-body-secondary">{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-medium">{{ $line->product->name ?? '-' }}</div>
                                <div class="small text-body-secondary font-monospace">{{ $line->product->code ?? '' }}</div>
                            </td>
                            <td>{{ $line->product?->specification ?? '-' }}</td>
                            <td>{{ $line->uom?->name ?? '-' }}</td>
                            <td class="text-end">{{ $fmt($line->expected_qty) }}</td>
                            <td class="text-end">
                                <span class="{{ $actualQty < $line->expected_qty ? 'text-warning' : 'text-success' }}">
                                    {{ $fmt($actualQty) }}
                                </span>
                            </td>
                            <td>
                                @if ($firstDetail?->location)
                                    <div class="fw-medium">{{ $firstDetail->location->name }}</div>
                                    <div class="small text-body-secondary font-monospace">{{ $firstDetail->location->code }}</div>
                                @else
                                    <span class="text-body-secondary small">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($firstDetail?->receiver)
                                    <div class="fw-medium">{{ $firstDetail->receiver->name }}</div>
                                    <div class="small text-body-secondary font-monospace">{{ $firstDetail->receiver->code }}</div>
                                @else
                                    <span class="text-body-secondary small">-</span>
                                @endif
                            </td>
                            <td>{{ $line->sn?->code ?? '-' }}</td>
                            <td>
                                @if($tracking === 2)
                                    <span class="text-danger">{{ $lotNumber === '-' ? 'Chưa có lot' : $lotNumber }}</span>
                                @else
                                    {{ $lotNumber }}
                                @endif
                            </td>
                            <td class="small">
                                @if($tracking === 2 && $serialNumbers !== '')
                                    <span class="font-monospace">{{ $serialNumbers }}</span>
                                @elseif($tracking === 2)
                                    <span class="text-danger">Chưa có serial</span>
                                @else -
                                @endif
                            </td>
                            <td>{{ $firstDetail->sub_warehouse ?? '-' }}</td>
                            <td>{{ $line->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="14" class="text-center text-body-secondary py-5">Không có dòng chi tiết.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-body-secondary">
            Tổng dòng: <strong>{{ $issue->lines->count() }}</strong>
        </small>
    </div>
</div>

{{-- ── NÚT HỦY ── --}}
@if($issue->status === \App\Enums\DocumentStatus::Draft)
<div class="d-flex gap-2 justify-content-end mt-3">
    <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal"
        data-coreui-target="#cancelModal">
        Hủy
    </button>
</div>
@endif

{{-- MODAL XÁC NHẬN DUYỆT --}}
@if($issue->status === \App\Enums\DocumentStatus::Draft)
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center px-4 pb-2">
                <svg class="icon icon-3xl text-success mb-3">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                </svg>
                <h6 class="fw-semibold mb-1">Xác nhận hoàn tất</h6>
                <p class="text-body-secondary small mb-0">
                    Xác nhận đã xuất đủ hàng theo phiếu<br>
                    <strong class="text-body">{{ $issue->code }}</strong>?
                </p>
                <p class="text-danger small mt-1">
                    Tồn kho sẽ bị trừ thực tế và không thể hoàn tác.
                    Nếu số lượng thực xuất khác dự kiến, vui lòng chỉnh sửa phiếu trước.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
                <form method="POST" action="{{ route('issues.complete', $issue) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Duyệt</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL XÁC NHẬN HỦY PHIẾU --}}
@if($issue->status === \App\Enums\DocumentStatus::Draft)
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
                    <strong class="text-body">{{ $issue->code }}</strong>?
                </p>
                <p class="text-danger small mt-1">Thao tác này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
                <form method="POST" action="{{ route('issues.cancel', $issue) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Hủy</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection