{{--
  resources/views/master/location/partials/tree-node.blade.php
  Đệ quy mỗi node trong cây vị trí.
  $node: Location model (with children eager-loaded)
  $depth: int (0 = gốc)
--}}
@php
  $hasChildren = $node->children->isNotEmpty();
  $collapseId  = 'tc-' . $node->id;
  $isVirtual   = $node->isVirtual();
  $typeColor   = $node->type_color;   // primary | info | warning | danger | secondary
  $typeLabel   = $node->type_label;
  $isRoot      = $depth === 0;
@endphp

<div class="tree-node tree-depth-{{ $depth }}">

  {{-- ROW --}}
  <div class="tree-toggle-row">

    {{-- Toggle collapse button (nếu có con) --}}
    @if ($hasChildren)
      <button
        class="tree-toggle-btn{{ $isRoot ? '' : ' collapsed' }}"
        type="button"
        data-coreui-toggle="collapse"
        data-coreui-target="#{{ $collapseId }}"
        aria-expanded="{{ $isRoot ? 'true' : 'false' }}"
        aria-controls="{{ $collapseId }}"
        title="Mở/Thu gọn"
      >
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-chevron-bottom') }}"></use></svg>
      </button>
    @else
      <span class="tree-leaf-spacer" aria-hidden="true"></span>
    @endif

    {{-- Type icon --}}
    @if ($isVirtual)
      <svg class="icon text-body-secondary" style="flex-shrink:0" aria-hidden="true">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-cloud') }}"></use>
      </svg>
    @elseif ($node->parent_id === null)
      <svg class="icon text-primary" style="flex-shrink:0" aria-hidden="true">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-factory') }}"></use>
      </svg>
    @else
      <svg class="icon text-{{ $typeColor }}" style="flex-shrink:0" aria-hidden="true">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
      </svg>
    @endif

    {{-- Code --}}
    <code class="tree-code text-{{ $typeColor }}">{{ $node->code }}</code>

    {{-- Name --}}
    <span class="tree-name text-truncate">
      {{ $node->name }}
    </span>

    {{-- Badges --}}
    <span class="tree-badges">
      {{-- Type badge --}}
      <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }} border border-{{ $typeColor }}-subtle" style="font-size:10px">
        {{ $typeLabel }}
      </span>

      {{-- Status --}}
      @if ($node->status == 0)
        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:10px">Ngừng</span>
      @endif

      {{-- Capacity --}}
      @if ($node->capacity_limit)
        <span class="badge bg-body-secondary text-body border" style="font-size:10px">
          <svg class="icon icon-sm me-1" aria-hidden="true"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use></svg>
          {{ number_format($node->capacity_limit, 0) }}
        </span>
      @endif

      {{-- Barcode print --}}
      @if (!$isVirtual && $node->barcode)
        <a href="{{ route('master.location.barcode', $node->id) }}"
           target="_blank"
           class="btn btn-xs btn-outline-secondary tree-action-btn"
           style="font-size:11px;padding:1px 6px"
           title="In nhãn barcode">
          <svg class="icon icon-sm me-1" aria-hidden="true"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use></svg>
          In
        </a>
      @endif

      {{-- Edit --}}
      @if (!$isVirtual || !in_array($node->code, ['WH']))
        <button class="btn btn-xs btn-outline-primary tree-action-btn"
                style="font-size:11px;padding:1px 6px"
                onclick="openForm({{ $node->id }})"
                title="Chỉnh sửa">
          <svg class="icon icon-sm" aria-hidden="true"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
        </button>
      @endif
    </span>
  </div>

  {{-- CHILDREN --}}
  @if ($hasChildren)
    <div class="tree-children collapse{{ $isRoot ? ' show' : '' }}" id="{{ $collapseId }}">
      @foreach ($node->children->sortBy('code') as $child)
        @include('master.location.partials.tree-node', [
          'node'  => $child,
          'depth' => $depth + 1,
        ])
      @endforeach
    </div>
  @endif

</div>