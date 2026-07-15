/**
 * Modal xem ảnh + pan/zoom cho trang master/product/index.
 * Thay thế toàn bộ logic transform tay (translate/scale + pointer events viết thủ công)
 * bằng thư viện panzoom-core, tránh lỗi tích luỹ state ("liệt dần") sau nhiều lần
 * pan/zoom liên tục do:
 *   - pointer capture / isDragging không được giải phóng đúng trong mọi nhánh
 *   - không throttle applyTransform() theo animation frame
 *   - không giới hạn biên pan
 *
 * QUAN TRỌNG VỀ LIFECYCLE:
 * panzoom-core gắn listener (wheel, pointerdown/move/up) trực tiếp lên node được
 * truyền vào initializePanZoom(). Nếu không gọi destroy() trước khi khởi tạo lại,
 * mỗi lần mở modal sẽ cộng dồn thêm 1 bộ listener mới trên cùng node -> đúng nguyên
 * nhân gây "liệt dần" nếu ai đó gọi initializePanZoom() nhiều lần trên cùng phần tử.
 * Ở đây ta giải quyết bằng cách: khởi tạo panzoom CHỈ MỘT LẦN (singleton theo modal),
 * và chỉ dùng reset()/destroy() đúng chỗ theo vòng đời show/hide của modal.
 */

import initializePanZoom from 'panzoom-core';

export function initImageZoomModal({
  modalSelector = '#imageZoomModal',
  viewportSelector = '#imgZoomViewport',
  imgSelector = '#imgZoomModalImg',
  triggerSelector = '.img-zoomable',
  btnInSelector = '#imgZoomInBtn',
  btnOutSelector = '#imgZoomOutBtn',
  btnResetSelector = '#imgZoomResetBtn',
} = {}) {
  const modalEl = document.querySelector(modalSelector);
  const viewport = document.querySelector(viewportSelector);
  const zoomImg = document.querySelector(imgSelector);
  const btnIn = document.querySelector(btnInSelector);
  const btnOut = document.querySelector(btnOutSelector);
  const btnReset = document.querySelector(btnResetSelector);

  if (!modalEl || !viewport || !zoomImg) return null;

  const bsModal = new coreui.Modal(modalEl);

  const ZOOM_STEP = 0.25;
  const ZOOM_MIN = 0.5;
  const ZOOM_MAX = 8;

  // Khởi tạo panzoom MỘT LẦN duy nhất trên viewport, không phải trên <img>.
  // panzoom-core tự set transform lên node truyền vào, nên viewport đóng vai trò
  // "canvas" còn <img> là nội dung hiển thị bên trong.
  let panZoom = initializePanZoom(viewport, {
    zoomInitial: 1,
    zoomMin: ZOOM_MIN,
    zoomMax: ZOOM_MAX,
    zoomSpeed: 1,
    disabledElements: true, // không dùng tính năng kéo-thả nhiều element của lib
    onContainerZoomChange: updateResetLabel,
  });

  function updateResetLabel() {
    if (!btnReset) return;
    const pct = Math.round(panZoom.getZoom() * 100);
    btnReset.textContent = `${pct}%`;
  }

  function resetView() {
    panZoom.reset();
    updateResetLabel();
  }

  // Mở modal khi click vào ảnh có class img-zoomable (event delegation
  // để hoạt động cả với các dòng bảng được render lại/phân trang qua AJAX
  // mà không cần gắn lại listener cho từng thumbnail).
  document.addEventListener('click', function (e) {
    const thumb = e.target.closest(triggerSelector);
    if (!thumb) return;

    zoomImg.src = thumb.dataset.preview || thumb.src;
    resetView();
    bsModal.show();
  });

  btnIn?.addEventListener('click', () => {
    panZoom.zoomIn(ZOOM_STEP);
    updateResetLabel();
  });

  btnOut?.addEventListener('click', () => {
    panZoom.zoomOut(ZOOM_STEP);
    updateResetLabel();
  });

  btnReset?.addEventListener('click', resetView);

  // Reset mỗi khi đóng modal — đây là điểm mấu chốt để tránh lỗi "liệt dần":
  // panzoom-core giữ state pan/zoom nội bộ trên instance dùng lại (singleton),
  // nên phải chủ động đưa state về (0,0,1) khi đóng modal, thay vì destroy()
  // rồi initializePanZoom() lại (việc này mới thực sự gây rò rỉ listener nếu lặp lại).
  modalEl.addEventListener('hidden.coreui.modal', () => {
    resetView();
  });

  // API trả ra để nơi gọi có thể huỷ hoàn toàn khi rời khỏi trang
  // (ví dụ nếu index.blade từng bị load lại qua AJAX/SPA-partial trong tương lai).
  return {
    destroy() {
      panZoom.destroy();
      panZoom = null;
    },
  };
}
