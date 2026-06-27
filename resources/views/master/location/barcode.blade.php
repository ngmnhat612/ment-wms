{{--
  resources/views/master/location/barcode.blade.php
  In nhãn barcode vị trí kho.
  Route: GET /master/location/{location}/barcode  → name: master.location.barcode
  Dùng JsBarcode (CDN). Tự động trigger print() sau khi load.
--}}

@php
  $locData = [
    'code'        => $location->code,
    'name'        => $location->name,
    'barcode'     => $location->barcode,
    'type'        => $location->type,
    'type_label'  => $location->type_label,
    'parent_code' => optional($location->parent)->code,
  ];
@endphp

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nhãn barcode — {{ $location->code }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: #f4f4f4;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 24px 16px 16px;
      min-height: 100vh;
      gap: 20px;
    }

    /* ── Toolbar (screen only) ────────────────────────────────── */
    .toolbar {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border: 1px solid #dde0e5;
      border-radius: 8px;
      padding: 10px 16px;
      width: 100%;
      max-width: 500px;
    }
    .toolbar-title { flex: 1; font-size: 14px; font-weight: 600; color: #1a1d23; }
    .toolbar-title small { font-weight: 400; color: #6b7280; margin-left: 6px; }

    .btn-print {
      display: inline-flex; align-items: center; gap: 6px;
      background: #0d6efd; color: #fff;
      border: none; border-radius: 6px;
      padding: 7px 16px; font-size: 13px; font-weight: 500;
      cursor: pointer; text-decoration: none;
      transition: background 0.15s;
    }
    .btn-print:hover { background: #0b5ed7; }
    .btn-print svg { width: 16px; height: 16px; fill: currentColor; }

    .btn-back {
      display: inline-flex; align-items: center; gap: 5px;
      color: #6b7280; text-decoration: none; font-size: 13px;
      border: 1px solid #dde0e5; border-radius: 6px;
      padding: 6px 12px;
      transition: background 0.1s;
    }
    .btn-back:hover { background: #f9fafb; color: #374151; }

    /* ── Quantity selector ────────────────────────────────────── */
    .qty-row {
      display: flex; align-items: center; gap: 10px;
      font-size: 13px; color: #374151;
    }
    .qty-row input[type=number] {
      width: 60px; padding: 5px 8px;
      border: 1px solid #dde0e5; border-radius: 6px;
      font-size: 13px; text-align: center;
    }

    /* ── Label(s) container ───────────────────────────────────── */
    .labels-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
      width: 100%;
      max-width: 700px;
    }

    /* ── Single label card ────────────────────────────────────── */
    .label-card {
      width: 220px;
      background: #fff;
      border: 1.5px solid #c8cdd6;
      border-radius: 8px;
      padding: 14px 12px 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.06);
      page-break-inside: avoid;
    }

    .label-header {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 6px;
      margin-bottom: 2px;
    }
    .label-type-badge {
      font-size: 9px; font-weight: 700; letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 2px 7px; border-radius: 4px;
    }
    .label-wh-name {
      font-size: 10px; color: #6b7280; font-weight: 500;
    }

    .label-code {
      font-size: 18px; font-weight: 700;
      letter-spacing: 0.02em; color: #111827;
      font-family: 'Courier New', monospace;
    }
    .label-name {
      font-size: 11px; color: #374151;
      text-align: center; line-height: 1.35;
    }

    .label-barcode-wrap {
      margin: 4px 0 2px;
      width: 100%;
      display: flex;
      justify-content: center;
    }
    .label-barcode-wrap svg {
      max-width: 196px;
    }

    .label-parent {
      font-size: 9.5px; color: #9ca3af;
    }

    /* type badge colors */
    .badge-internal  { background: #dbeafe; color: #1d4ed8; }
    .badge-virtual   { background: #fee2e2; color: #991b1b; }


    /* ── Print overrides ──────────────────────────────────────── */
    @media print {
      body { background: #fff; padding: 0; }
      .toolbar, .qty-row, .no-print { display: none !important; }
      .labels-wrap { gap: 6mm; padding: 4mm; }
      .label-card {
        width: 55mm;
        border: 1pt solid #999;
        box-shadow: none;
        page-break-inside: avoid;
        border-radius: 3mm;
        padding: 3mm 2.5mm 2.5mm;
      }
      .label-code { font-size: 14pt; }
      .label-name { font-size: 8pt; }
    }
  </style>
</head>
<body>

  {{-- ── Toolbar ── --}}
  <div class="toolbar no-print">
    <span class="toolbar-title">
      In nhãn barcode
      <small>{{ $location->code }}</small>
    </span>
    <span class="qty-row">
      <label for="qtyInput">Số nhãn:</label>
      <input type="number" id="qtyInput" value="1" min="1" max="50" onchange="renderLabels(this.value)">
    </span>
    <button class="btn-print" onclick="window.print()">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M6 9V4h12v5M6 18H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v7a2 2 0 01-2 2h-2M6 14h12v6H6z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      In ngay
    </button>
    <a href="{{ route('master.location.index') }}" class="btn-back">Quay lại</a>
  </div>

  {{-- ── Labels container ── --}}
  <div class="labels-wrap" id="labelsWrap"></div>

  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  <script>
    const LOC = @json($locData);

    const TYPE_BADGE_CLASS = {
      1: 'badge-internal',
      2: 'badge-virtual',
    };

    function buildLabel() {
      const card = document.createElement('div');
      card.className = 'label-card';

      const badgeClass = TYPE_BADGE_CLASS[LOC.type] || 'badge-internal';

      card.innerHTML = `
        <div class="label-header">
          <span class="label-type-badge ${badgeClass}">${LOC.type_label}</span>
          <span class="label-wh-name">Kho hàng</span>
        </div>
        <div class="label-code">${LOC.code}</div>
        <div class="label-name">${LOC.name}</div>
        <div class="label-barcode-wrap">
          <svg class="barcode-svg"></svg>
        </div>
        ${LOC.parent_code ? `<div class="label-parent">Cha: ${LOC.parent_code}</div>` : ''}
      `;

      return card;
    }

    function renderLabels(qty) {
      qty = Math.max(1, Math.min(50, parseInt(qty) || 1));
      const wrap = document.getElementById('labelsWrap');
      wrap.innerHTML = '';

      for (let i = 0; i < qty; i++) {
        const card = buildLabel();
        wrap.appendChild(card);
        const svg = card.querySelector('.barcode-svg');
        JsBarcode(svg, LOC.code, {
          format:      'CODE128',
          width:       1.6,
          height:      48,
          displayValue: true,
          fontSize:    10,
          textMargin:  3,
          margin:      0,
          lineColor:   '#111827',
          background:  'transparent',
        });
      }
    }

    renderLabels(1);

    // Auto-print nếu URL có ?print=1
    if (new URLSearchParams(location.search).get('print') === '1') {
      window.addEventListener('load', () => setTimeout(() => window.print(), 600));
    }
  </script>
</body>
</html>