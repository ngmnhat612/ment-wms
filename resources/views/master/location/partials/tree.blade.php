{{--
  resources/views/master/location/partials/tree.blade.php
  Tree view cho cấu trúc phân cấp vị trí kho.
  Sử dụng CoreUI v5 native collapse (data-coreui-*), không dùng vanilla JS toggle.
--}}

<div class="card" id="locationTreeCard">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">
      <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-sitemap') }}"></use></svg>
      Sơ đồ cây vị trí kho
    </span>
    <div class="d-flex gap-2 align-items-center">
      <button class="btn btn-sm btn-outline-secondary" id="btnExpandAll" onclick="treeExpandAll()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-bottom') }}"></use></svg>
        Mở rộng tất cả
      </button>
      <button class="btn btn-sm btn-outline-secondary" onclick="treeCollapseAll()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
        Thu gọn tất cả
      </button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="location-tree p-3" id="locationTreeRoot">
      @foreach ($treeRoots as $root)
        @include('master.location.partials.tree-node', ['node' => $root, 'depth' => 0])
      @endforeach
    </div>
  </div>
</div>

<style>
  .location-tree { font-size: 0.875rem; }

  .tree-node { position: relative; }

  .tree-toggle-row {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border-radius: var(--cui-border-radius);
    cursor: default;
    transition: background 0.1s;
    line-height: 1.4;
  }
  .tree-toggle-row:hover { background: var(--cui-secondary-bg); }

  .tree-toggle-btn {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    padding: 0;
    color: var(--cui-body-color);
    opacity: 0.45;
    border-radius: 3px;
    transition: opacity 0.15s, transform 0.2s;
  }
  .tree-toggle-btn:hover { opacity: 0.8; }
  .tree-toggle-btn.collapsed .icon { transform: rotate(-90deg); }
  .tree-toggle-btn .icon { transition: transform 0.2s; }

  .tree-leaf-spacer { width: 18px; flex-shrink: 0; }

  .tree-children {
    margin-left: 22px;
    padding-left: 12px;
    border-left: 1px dashed var(--cui-border-color);
  }

  .tree-code {
    font-family: var(--cui-font-monospace);
    font-size: 0.8rem;
    font-weight: 600;
    min-width: 80px;
  }
  .tree-name { flex: 1; min-width: 0; }
  .tree-badges { display: flex; gap: 4px; flex-wrap: nowrap; align-items: center; margin-left: auto; }

  .tree-action-btn {
    opacity: 0;
    transition: opacity 0.15s;
    flex-shrink: 0;
  }
  .tree-toggle-row:hover .tree-action-btn { opacity: 1; }

  /* depth indent colors on left border */
  .tree-depth-0 > .tree-children { border-left-color: rgba(var(--cui-primary-rgb), 0.3); }
  .tree-depth-1 > .tree-children { border-left-color: rgba(var(--cui-info-rgb), 0.3); }
  .tree-depth-2 > .tree-children { border-left-color: rgba(var(--cui-success-rgb), 0.25); }
</style>