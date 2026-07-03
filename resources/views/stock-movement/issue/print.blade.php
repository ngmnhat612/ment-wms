<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Phiếu Xuất Kho — {{ $issue->code }}</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #1e293b;
        padding: 24px;
    }

    .page-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #2563eb;
    }

    .page-header h1 {
        font-size: 16px;
        font-weight: bold;
        color: #1e3a8a;
        margin-bottom: 4px;
    }

    .page-header p {
        font-size: 10px;
        color: #64748b;
    }

    .info-grid {
        display: flex;
        gap: 16px;
        margin-bottom: 18px;
    }

    .info-box {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 12px;
    }

    .info-box .lbl {
        font-size: 9px;
        color: #64748b;
        margin-bottom: 2px;
    }

    .info-box .val {
        font-size: 11px;
        font-weight: bold;
    }

    .section-title {
        font-size: 12px;
        font-weight: bold;
        color: #1e3a8a;
        margin: 16px 0 8px;
        border-left: 3px solid #2563eb;
        padding-left: 8px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    thead th {
        background: #2563eb;
        color: #fff;
        font-size: 9px;
        font-weight: bold;
        padding: 5px 6px;
        text-align: left;
        border: 1px solid #1d4ed8;
    }

    thead th.r {
        text-align: right;
    }

    thead th.c {
        text-align: center;
    }

    tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    tbody td {
        padding: 4px 6px;
        border: 1px solid #e2e8f0;
        font-size: 9px;
        vertical-align: top;
    }

    tbody td.r {
        text-align: right;
    }

    tbody td.c {
        text-align: center;
    }

    tfoot td {
        background: #eff6ff;
        font-weight: bold;
        padding: 5px 6px;
        border: 1px solid #bfdbfe;
        font-size: 9px;
    }

    tfoot td.r {
        text-align: right;
    }

    .badge {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 3px;
        font-size: 8px;
        font-weight: bold;
    }

    .badge-draft {
        background: #f1f5f9;
        color: #475569;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-approved {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .badge-completed {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-cancelled {
        background: #fee2e2;
        color: #b91c1c;
    }

    .note-box {
        border: 1px dashed #cbd5e1;
        border-radius: 4px;
        padding: 8px 10px;
        font-size: 10px;
        color: #475569;
        margin-bottom: 16px;
    }

    .sign-row {
        display: flex;
        gap: 0;
        margin-top: 28px;
    }

    .sign-col {
        flex: 1;
        text-align: center;
        border-top: 1px solid #e2e8f0;
        padding-top: 6px;
        margin: 0 4px;
    }

    .sign-col .sign-title {
        font-size: 9px;
        font-weight: bold;
        color: #1e3a8a;
        margin-bottom: 2px;
    }

    .sign-col .sign-name {
        font-size: 9px;
        color: #64748b;
        margin-top: 36px;
    }

    .footer {
        margin-top: 20px;
        padding-top: 8px;
        border-top: 1px solid #e2e8f0;
        font-size: 9px;
        color: #94a3b8;
        text-align: center;
    }

    @media print {
        body {
            padding: 8px;
        }

        .no-print {
            display: none !important;
        }

        @page {
            margin: 12mm;
        }
    }
    </style>
</head>

<body>

    @php
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
    $typeLabels = [1 => 'Xuất sản xuất', 2 => 'Xuất bảo trì', 3 => 'Mượn hàng', 4 => 'Xuất khác'];
    $statusMap = [
    \App\Models\StockIssue::STATUS_DRAFT => ['Nháp', 'badge-draft'],
    \App\Models\StockIssue::STATUS_PENDING => ['Chờ duyệt', 'badge-pending'],
    \App\Models\StockIssue::STATUS_APPROVED => ['Đã duyệt', 'badge-approved'],
    \App\Models\StockIssue::STATUS_COMPLETED => ['Hoàn thành', 'badge-completed'],
    \App\Models\StockIssue::STATUS_CANCELLED => ['Đã hủy', 'badge-cancelled'],
    ];
    [$statusLabel, $statusClass] = $statusMap[$issue->status] ?? ['—', 'badge-draft'];
    @endphp

    {{-- NÚT IN (ẩn khi in) --}}
    <div class="no-print" style="margin-bottom:16px; text-align:right">
        <button onclick="window.print()"
            style="background:#2563eb;color:#fff;border:none;border-radius:4px;padding:6px 14px;font-size:11px;cursor:pointer">
            🖨 In phiếu
        </button>
        <a href="{{ route('issues.show', $issue) }}"
            style="margin-left:8px;background:#f1f5f9;color:#334155;border:none;border-radius:4px;padding:6px 14px;font-size:11px;cursor:pointer;text-decoration:none">
            ← Quay lại
        </a>
    </div>

    {{-- TIÊU ĐỀ --}}
    <div class="page-header">
        <h1>PHIẾU XUẤT KHO</h1>
        <p>Ment Automation — {{ $issue->code }}</p>
    </div>

    {{-- THÔNG TIN PHIẾU --}}
    <div class="info-grid">
        <div class="info-box">
            <div class="lbl">Mã phiếu</div>
            <div class="val">{{ $issue->code }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">Loại xuất</div>
            <div class="val">{{ $typeLabels[$issue->issue_type] ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">Trạng thái</div>
            <div class="val"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></div>
        </div>
        <div class="info-box">
            <div class="lbl">Ngày xuất</div>
            <div class="val">{{ $issue->issue_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">Số tham chiếu</div>
            <div class="val">{{ $issue->reference_no ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">Người yêu cầu</div>
            <div class="val">{{ $issue->requester?->name ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">Người tạo</div>
            <div class="val">{{ $issue->createdBy?->name ?? '—' }}</div>
        </div>
        <div class="info-box">
            <div class="lbl">Người duyệt</div>
            <div class="val">{{ $issue->confirmedBy?->name ?? '—' }}</div>
        </div>
    </div>

    @if($issue->expected_return_date)
    <div class="note-box">
        <strong>Ngày hoàn trả dự kiến:</strong> {{ $issue->expected_return_date->format('d/m/Y') }}
    </div>
    @endif

    @if($issue->note)
    <div class="note-box"><strong>Ghi chú:</strong> {{ $issue->note }}</div>
    @endif

    {{-- BẢNG CHI TIẾT --}}
    <div class="section-title">Chi tiết hàng hóa xuất kho</div>
    <table>
        <thead>
            <tr>
                <th style="width:24px" class="c">#</th>
                <th style="width:72px">Mã hàng</th>
                <th>Tên hàng hóa</th>
                <th style="width:36px" class="c">ĐVT</th>
                <th class="r" style="width:60px">Số lượng</th>
                <th style="width:80px">Vị trí kho</th>
                <th style="width:80px">Lot / Batch</th>
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($issue->details as $i => $row)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $row->product?->code ?? '—' }}</td>
                <td>{{ $row->product?->name ?? '—' }}</td>
                <td class="c">{{ $row->uom?->name ?? $row->product?->uom?->name ?? '—' }}</td>
                <td class="r" style="font-weight:bold">{{ $fmt($row->quantity) }}</td>
                <td>{{ $row->location?->code ?? '—' }}</td>
                <td>{{ $row->lot?->lot_number ?? '—' }}</td>
                <td style="color:#64748b">{{ $row->note ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:10px;color:#94a3b8">Không có dữ liệu</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right">Tổng số lượng:</td>
                <td class="r">{{ $fmt($issue->details->sum('quantity')) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    {{-- KÝ TÊN --}}
    <div class="sign-row">
        <div class="sign-col">
            <div class="sign-title">Người lập phiếu</div>
            <div class="sign-name">{{ $issue->createdBy?->name ?? '' }}</div>
        </div>
        <div class="sign-col">
            <div class="sign-title">Người duyệt</div>
            <div class="sign-name">{{ $issue->confirmedBy?->name ?? '' }}</div>
        </div>
        <div class="sign-col">
            <div class="sign-title">Thủ kho</div>
            <div class="sign-name">&nbsp;</div>
        </div>
        <div class="sign-col">
            <div class="sign-title">Người nhận hàng</div>
            <div class="sign-name">{{ $issue->requester?->name ?? '' }}</div>
        </div>
    </div>

    <div class="footer">
        Warehouse System — Phiếu xuất kho {{ $issue->code }} — In lúc {{ now()->format('H:i:s d/m/Y') }}
    </div>

</body>

</html>