{{-- Modal xem ảnh + zoom in/out. Markup thuần, logic pan/zoom nằm ở
     resources/js/product/image-zoom-modal.js (dùng panzoom-core). --}}
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-dark border-0">
      <div class="modal-header border-0 py-2">
        <div class="btn-group">
          <button type="button" class="btn btn-sm btn-outline-light" id="imgZoomOutBtn" title="Thu nhỏ">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-minus') }}"></use></svg>
          </button>
          <button type="button" class="btn btn-sm btn-outline-light" id="imgZoomResetBtn" title="Đặt lại">100%</button>
          <button type="button" class="btn btn-sm btn-outline-light" id="imgZoomInBtn" title="Phóng to">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
          </button>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-coreui-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex align-items-center justify-content-center overflow-hidden p-0"
           id="imgZoomViewport" style="height:75vh; cursor:grab;">
        <img id="imgZoomModalImg" src="" alt=""
             style="max-width:none; max-height:none; user-select:none; pointer-events:none;">
      </div>
    </div>
  </div>
</div>
