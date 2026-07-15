@extends('layouts.app')

@section('title', 'Phiếu yêu cầu xuất ' . $stockOutRequest->code)

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Yêu cầu Nhập/Xuất</a></li>
<li class="breadcrumb-item active">{{ $stockOutRequest->code }}</li>
@endsection

@section('content')

@php
$fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            {{ $stockOutRequest->code }}
            <span class="{{ $stockOutRequest->status->badgeClass() }}" style="font-size: 0.8rem;">
                {{ $stockOutRequest->status->label() }}
            </span>
        </h4>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        {{-- DRAFT --}}
        @if($stockOutRequest->status === \App\Enums\DocumentStatus::Draft)
        <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            Hoàn thành
        </button>

        <a href="{{ route('stock-out-requests.edit', $stockOutRequest) }}" class="btn btn-primary">
            Chỉnh sửa
        </a>
        @endif

        {{-- COMPLETED --}}
        @if($stockOutRequest->status === \App\Enums\DocumentStatus::Completed)
        <a href="{{ route('issues.create') }}" class="btn btn-primary">
            Xuất
        </a>
        @endif

        <a href="{{ route('stock-requests.index') }}" class="btn btn-outline-secondary">
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
            <div class="col-md-3">
                <label class="form-label mb-1 fw-semibold">Mã phiếu</label>
                <div class="fw-medium">
                    <code class="text-primary">{{ $stockOutRequest->code }}</code>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 fw-semibold">Ngày yêu cầu</label>
                <div>{{ $stockOutRequest->request_date ? \Carbon\Carbon::parse($stockOutRequest->request_date)->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1 fw-semibold">Ghi chú</label>
                <div>{{ $stockOutRequest->note ?? '-' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── CHI TIẾT PHIẾU ── --}}
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
                        <th style="width:8%">Ngày xuất</th>
                        <th style="min-width:100px">Mã vật tư</th>
                        <th style="min-width:160px">Vật tư</th>
                        <th style="width:6%">ĐVT</th>
                        <th style="width:4%" class="text-end">SL</th>
                        <th style="width:6%" class="text-end">Thực xuất</th>
                        <th style="min-width:120px">Người nhận</th>
                        <th style="width:8%">Mã dự án</th>
                        <th style="width:6%">Lô</th>
                        <th style="min-width:150px">Sê-ri</th>
                        <th style="min-width:120px">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($stockOutRequest->details as $di => $detail)
                    <tr>
                        <td class="text-center text-body-secondary">{{ $di + 1 }}</td>
                        <td class="small">{{ $detail->issue_date ? \Carbon\Carbon::parse($detail->issue_date)->format('d/m/Y') : '-' }}</td>
                        <td><code class="text-primary fw-medium">{{ $detail->product_code ?? '-' }}</code></td>
                        <td class="small">{{ $detail->product_name ?? '-' }}</td>
                        <td class="small">{{ $detail->uom_name ?? '-' }}</td>
                        <td class="text-end">{{ $fmt($detail->quantity) }}</td>
                        <td class="text-end">
                            @if($detail->actual_qty !== null)
                                <span class="{{ (float)$detail->actual_qty < (float)$detail->quantity ? 'text-warning' : 'text-success' }}">
                                    {{ $fmt($detail->actual_qty) }}
                                </span>
                            @else
                                <span class="text-body-secondary">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($detail->receiver)
                                <div class="fw-medium">{{ $detail->receiver->name }}</div>
                                <div class="small text-body-secondary font-monospace">{{ $detail->receiver->code }}</div>
                            @else
                                <span class="text-body-secondary small">-</span>
                            @endif
                        </td>
                        <td class="small">{{ $detail->sn_code ?? '-' }}</td>
                        <td>{{ $detail->lot_number ?? '-' }}</td>
                        <td class="small">
                            @if($detail->serial_number)
                                <span class="font-monospace">{{ $detail->serial_number }}</span>
                            @else -
                            @endif
                        </td>
                        <td class="small">{{ $detail->note ?? '-' }}</td>
                    </tr>
                @empty
                <tr><td colspan="12" class="text-center text-body-secondary py-5">Không có dòng chi tiết.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-body-secondary">
            Tổng dòng: <strong>{{ $stockOutRequest->details->count() }}</strong>
        </small>
    </div>
</div>

{{-- ── NÚT HỦY ── --}}
@if($stockOutRequest->status === \App\Enums\DocumentStatus::Draft)
<div class="d-flex gap-2 justify-content-end mt-3">
    <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal"
        data-coreui-target="#cancelModal">
        Hủy
    </button>
</div>
@endif

{{-- MODAL XÁC NHẬN HOÀN THÀNH --}}
@if($stockOutRequest->status === \App\Enums\DocumentStatus::Draft)
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
                <h6 class="fw-semibold mb-1">Xác nhận hoàn thành</h6>
                <p class="text-body-secondary small mb-0">
                    Xác nhận hoàn thành phiếu yêu cầu xuất kho<br>
                    <strong class="text-body">{{ $stockOutRequest->code }}</strong>?
                </p>
                <p class="text-success small mt-1">Thao tác này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
                <form method="POST" action="{{ route('stock-out-requests.complete', $stockOutRequest) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Hoàn thành</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL XÁC NHẬN HỦY PHIẾU --}}
@if($stockOutRequest->status === \App\Enums\DocumentStatus::Draft)
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
                    <strong class="text-body">{{ $stockOutRequest->code }}</strong>?
                </p>
                <p class="text-danger small mt-1">Thao tác này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
                <form method="POST" action="{{ route('stock-out-requests.cancel', $stockOutRequest) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Hủy</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection