/*!
 * Chip / Chip Input bridge (v2 — an toàn, không xung đột)
 *
 * Nạp SAU coreui.bundle.min.js (bản chính đang dùng cho layout — Sidebar,
 * Dropdown, Alert, Navigation, ...) và SAU coreui-chip-only.min.js
 * (bundle RIÊNG chỉ chứa Chip/ChipInput/ChipSet, build từ CoreUI 5.8.0
 * bằng esbuild, KHÔNG kèm Dropdown/Sidebar/Alert nên không tự động
 * bind lại Data API lên các phần tử đó — không còn xung đột như bản
 * coreui-chip-bundle.min.js (full bundle) trước đây.
 *
 * Thứ tự nạp bắt buộc:
 *   1) coreui.bundle.min.js        (namespace: window.coreui)
 *   2) coreui-chip-only.min.js     (namespace: window.coreuiChipOnly)
 *   3) chip-input-bridge.js        (file này)
 */
(function () {
  if (typeof window.coreuiChipOnly === 'undefined') {
    console.error('[chip-input-bridge] coreui-chip-only.min.js chưa được nạp trước file này.');
    return;
  }
  if (typeof window.coreui === 'undefined') {
    console.error('[chip-input-bridge] coreui.bundle.min.js (bản chính) chưa được nạp trước file này.');
    return;
  }

  window.coreui.Chip = window.coreuiChipOnly.Chip;
  window.coreui.ChipInput = window.coreuiChipOnly.ChipInput;
  window.coreui.ChipSet = window.coreuiChipOnly.ChipSet;

  delete window.coreuiChipOnly;
})();

// ── PATCH: ChipInput._syncHiddenInput/_createHiddenInput hard-code "," ──
// coreui-chip-only (CoreUI 5.8.0) luôn nối giá trị hidden input bằng dấu
// phẩy, bỏ qua config.separator. Patch lại 1 lần cho toàn app, áp dụng
// đồng nhất cho MỌI ChipInput (kể cả tự động init qua Data API lúc
// DOMContentLoaded), tránh phải tự fix tay ở từng form (receipt/issue...).
(function () {
  const proto = window.coreui.ChipInput.prototype;

  proto._syncHiddenInput = function () {
    if (this._hiddenInput) {
      const sep = this._config?.separator || ' ';
      this._hiddenInput.value = this.getValues().join(sep);
    }
  };

  const originalCreateHiddenInput = proto._createHiddenInput;
  proto._createHiddenInput = function () {
    originalCreateHiddenInput.call(this);
    this._syncHiddenInput(); // ghi đè lại value vừa bị join(",") sai
  };
})();