@extends('layouts.app')

@section('title', 'Phiếu yêu cầu nhập ' . $stockInRequest->code)

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Yêu cầu Nhập/Xuất kho</a></li>
<li class="breadcrumb-item active">{{ $stockInRequest->code }}</li>
@endsection

@section('content')

@php
$fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold d-flex align-items-center gap-2">
            {{ $stockInRequest->code }}
            <span class="{{ $stockInRequest->status->badgeClass() }}" style="font-size: 0.8rem;">
                {{ $stockInRequest->status->label() }}
            </span>
        </h4>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        {{-- DRAFT --}}
        @if($stockInRequest->status === \App\Enums\DocumentStatus::Draft)
        <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            Hoàn thành
        </button>

        <a href="{{ route('stock-in-requests.edit', $stockInRequest) }}" class="btn btn-primary">
            Chỉnh sửa
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
                <label class="form-label mb-1">Mã phiếu</label>
                <div class="fw-semibold">{{ $stockInRequest->code }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Người tạo</label>
                <div>{{ $stockInRequest->createdBy?->display_name ?? '-' }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Ngày tạo</label>
                <div>{{ $stockInRequest->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
            </div>
            <div class="col-md-12">
                <label class="form-label mb-1">Ghi chú</label>
                <div>{{ $stockInRequest->note ?? '-' }}</div>
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
                        <th style="width:8%">Số PO/CU</th>
                        <th style="width:8%">Ngày nhận</th>
                        <th style="min-width:160px">Vật tư</th>
                        <th style="min-width:140px">TSKT</th>
                        <th style="width:8%">Thương hiệu</th>
                        <th style="width:6%" class="text-end">SL</th>
                        <th style="width:6%">ĐVT</th>
                        <th style="width:6%">Số Lô</th>
                        <th style="min-width:120px">Người yêu cầu</th>
                        <th style="min-width:120px">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($stockInRequest->details as $di => $detail)
                    <tr>
                        <td class="text-center text-body-secondary">{{ $di + 1 }}</td>
                        <td>{{ $detail->reference_no ?? '-' }}</td>
                        <td>{{ $detail->received_date ? \Carbon\Carbon::parse($detail->received_date)->format('d/m/Y') : '-' }}</td>
                        <td>
                            <div class="fw-medium">{{ $detail->product_name ?? '-' }}</div>
                            <div class="small text-body-secondary font-monospace">{{ $detail->product_code ?? '' }}</div>
                        </td>
                        <td>{{ $detail->product_specification ?? '-' }}</td>
                        <td>{{ $detail->brand_name ?? '-' }}</td>
                        <td class="text-end">{{ $fmt($detail->quantity) }}</td>
                        <td>{{ $detail->uom_name ?? '-' }}</td>
                        <td>{{ $detail->lot_number ?? '-' }}</td>
                        <td>
                            @if ($detail->requester)
                                <div class="fw-medium">{{ $detail->requester->name }}</div>
                                <div class="small text-body-secondary font-monospace">{{ $detail->requester->code }}</div>
                            @else
                                <span class="text-body-secondary small">-</span>
                            @endif
                        </td>
                        <td>{{ $detail->note ?? '-' }}</td>
                    </tr>
                @empty
                <tr><td colspan="11" class="text-center text-body-secondary py-5">Không có dòng chi tiết.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-body-secondary">
            Tổng dòng: <strong>{{ $stockInRequest->details->count() }}</strong>
        </small>
    </div>
</div>

{{-- ── NÚT HỦY ── --}}
@if($stockInRequest->status === \App\Enums\DocumentStatus::Draft)
<div class="d-flex gap-2 justify-content-end mt-3">
    <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal"
        data-coreui-target="#cancelModal">
        Hủy
    </button>
</div>
@endif

{{-- MODAL XÁC NHẬN HOÀN THÀNH --}}
@if($stockInRequest->status === \App\Enums\DocumentStatus::Draft)
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
                    Xác nhận hoàn thành phiếu yêu cầu nhập kho<br>
                    <strong class="text-body">{{ $stockInRequest->code }}</strong>?
                </p>
                <p class="text-success small mt-1">Thao tác này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
                <form method="POST" action="{{ route('stock-in-requests.complete', $stockInRequest) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Hoàn thành</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL XÁC NHẬN HỦY PHIẾU --}}
@if($stockInRequest->status === \App\Enums\DocumentStatus::Draft)
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
                    <strong class="text-body">{{ $stockInRequest->code }}</strong>?
                </p>
                <p class="text-danger small mt-1">Thao tác này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Đóng</button>
                <form method="POST" action="{{ route('stock-in-requests.cancel', $stockInRequest) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Hủy</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection