<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu nhập kho — {{ $receipt->code }}</title>
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 12px;
        color: #1a1a1a;
        background: #fff;
        padding: 24px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        border-bottom: 2px solid #333;
        padding-bottom: 12px;
    }

    .company-name {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .doc-title {
        text-align: right;
    }

    .doc-title h2 {
        font-size: 18px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .doc-title .code {
        font-size: 13px;
        color: #555;
        margin-top: 2px;
    }

    .meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 20px;
    }

    .meta-block {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px 14px;
    }

    .meta-block h4 {
        font-size: 11px;
        text-transform: uppercase;
        color: #777;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        border-bottom: 1px solid #eee;
        padding-bottom: 4px;
    }

    .meta-row {
        display: flex;
        gap: 8px;
        margin-bottom: 4px;
    }

    .meta-label {
        color: #555;
        min-width: 110px;
        flex-shrink: 0;
    }

    .meta-value {
        font-weight: 600;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    thead tr {
        background: #f0f0f0;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 6px 8px;
        text-align: left;
        vertical-align: middle;
    }

    th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #444;
    }

    td.num {
        text-align: right;
    }

    td.center {
        text-align: center;
        color: #666;
        font-size: 11px;
    }

    tfoot td {
        background: #f8f8f8;
        font-weight: 600;
    }

    .badge-success {
        display: inline-block;
        padding: 2px 8px;
        background: #d1fae5;
        color: #065f46;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 600;
    }

    .signatures {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 24px;
        margin-top: 32px;
    }

    .sig-block {
        text-align: center;
    }

    .sig-block .sig-title {
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }

    .sig-block .sig-note {
        font-size: 10px;
        color: #888;
        margin-bottom: 48px;
    }

    .sig-block .sig-line {
        border-top: 1px solid #999;
        padding-top: 4px;
        font-size: 11px;
        color: #555;
    }

    .footer-note {
        font-size: 10px;
        color: #aaa;
        text-align: center;
        border-top: 1px solid #eee;
        padding-top: 8px;
    }

    @media print {
        body {
            padding: 10px;
        }

        .no-print {
            display: none !important;
        }
    }
    </style>
</head>

<body>

    @php
    $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
    $typeLabels = [1 => 'Từ nhà cung cấp', 2 => 'Trả hàng SX', 3 => 'Khác'];
    @endphp

    <div class="header">
        <div>
            <div class="company-name">Ment Automation</div>
            <div style="font-size:11px; color:#666; margin-top:3px;">Hệ thống quản lý kho</div>
        </div>
        <div class="doc-title">
            <h2>Phiếu nhập kho</h2>
            <div class="code">{{ $receipt->code }}</div>
            <div style="margin-top:4px;">
                <span class="badge-success">Hoàn thành</span>
            </div>
        </div>
    </div>

    <div class="meta">
        <div class="meta-block">
            <h4>Thông tin phiếu</h4>
            <div class="meta-row">
                <span class="meta-label">Mã phiếu:</span>
                <span class="meta-value">{{ $receipt->code }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Loại nhập:</span>
                <span class="meta-value">{{ $typeLabels[$receipt->receipt_type] ?? '—' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Số tham chiếu:</span>
                <span class="meta-value">{{ $receipt->reference_no ?? '—' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Ngày nhập:</span>
                <span class="meta-value">
                    {{ $receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') : '—' }}
                </span>
            </div>
            @if($receipt->note)
            <div class="meta-row">
                <span class="meta-label">Ghi chú:</span>
                <span class="meta-value">{{ $receipt->note }}</span>
            </div>
            @endif
        </div>
        <div class="meta-block">
            <h4>Nhà cung cấp & người thực hiện</h4>
            <div class="meta-row">
                <span class="meta-label">Nhà cung cấp:</span>
                <span class="meta-value">{{ $receipt->supplier?->name ?? '—' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Người tạo:</span>
                <span class="meta-value">{{ $receipt->createdBy?->name ?? '—' }}</span>
            </div>
            @if($receipt->confirmedBy)
            <div class="meta-row">
                <span class="meta-label">Người duyệt:</span>
                <span class="meta-value">{{ $receipt->confirmedBy->name }}</span>
            </div>
            @endif
            <div class="meta-row">
                <span class="meta-label">Ngày in:</span>
                <span class="meta-value">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:32px">#</th>
                <th>Mã hàng</th>
                <th>Tên hàng hóa</th>
                <th>ĐVT</th>
                <th class="num">SL dự kiến</th>
                <th class="num">SL thực nhận</th>
                <th>Vị trí kho</th>
                <th>Lot / Batch</th>
                <th>Hạn dùng</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receipt->details as $i => $detail)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td style="font-weight:600;">{{ $detail->product?->code ?? '—' }}</td>
                <td>{{ $detail->product?->name ?? '—' }}</td>
                <td>{{ $detail->uom?->name ?? $detail->product?->uom?->name ?? '—' }}</td>
                <td class="num">{{ $fmt($detail->expected_qty) }}</td>
                <td class="num" style="font-weight:600;">
                    {{ $detail->actual_qty !== null ? $fmt($detail->actual_qty) : $fmt($detail->expected_qty) }}
                </td>
                <td>{{ $detail->location?->code ?? '—' }}</td>
                <td>{{ $detail->lot?->lot_number ?? '—' }}</td>
                <td>
                    {{ $detail->expiry_date ? \Carbon\Carbon::parse($detail->expiry_date)->format('d/m/Y') : '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="center" style="padding:16px;">Không có dòng chi tiết.</td>
            </tr>
            @endforelse
        </tbody>
        @if($receipt->details->count())
        <tfoot>
            <tr>
                <td colspan="8" style="text-align:right; font-size:11px; color:#555;">
                    Tổng cộng:
                </td>
                <td style="text-align:right;">
                    {{ $receipt->details->count() }} mặt hàng
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-title">Người lập phiếu</div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
            <div class="sig-line">{{ $receipt->createdBy?->name ?? '' }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Người duyệt</div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
            <div class="sig-line">{{ $receipt->confirmedBy?->name ?? '' }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Thủ kho</div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
            <div class="sig-line">&nbsp;</div>
        </div>
    </div>

    <div class="footer-note" style="margin-top:24px;">
        In lúc {{ now()->format('d/m/Y H:i:s') }} — Ment Automation Warehouse System
    </div>

    <script>
    window.addEventListener('load', function() {
        window.print();
    });
    </script>
</body>

</html>
