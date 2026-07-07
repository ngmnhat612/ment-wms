@extends('layouts.app')

@section('title', 'Tồn kho hiện tại')

@section('breadcrumb')
  <li class="breadcrumb-item">Tồn kho</li>
  <li class="breadcrumb-item active">Tồn kho hiện tại</li>
@endsection

@section('content')

  {{-- SORT HELPER --}}
  @php
    $sort = request('sort', '');
    $dir  = request('dir', '');
    $nextDir = function($col) use ($sort, $dir) {
      if ($sort !== $col) return 'asc';
      if ($dir === 'asc')  return 'desc';
      return '';
    };
    $sortUrl = function($col) use ($sort, $dir, $nextDir) {
      $nd = $nextDir($col);
      if ($nd === '') return request()->fullUrlWithQuery(['sort' => '', 'dir' => '', 'page' => 1]);
      return request()->fullUrlWithQuery(['sort' => $col, 'dir' => $nd, 'page' => 1]);
    };
    $sortIcon = function($col) use ($sort, $dir) {
      if ($sort !== $col || $dir === '') {
        $icon = 'cil-swap-vertical';
      } elseif ($dir === 'asc') {
        $icon = 'cil-sort-alpha-down';
      } else {
        $icon = 'cil-sort-alpha-up';
      }
      return "<svg class=\"icon icon-sm ms-1\"><use xlink:href=\"" . asset('vendor/coreui/icons/sprites/free.svg#' . $icon) . "\"></use></svg>";
    };
  @endphp

  {{-- BẢNG TỒN KHO --}}
  <div class="card">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="fw-semibold flex-shrink-0">Tồn kho hiện tại</span>
      <form method="GET" action="{{ route('inventory.index') }}"
            class="d-flex gap-2 flex-wrap align-items-center flex-grow-1 justify-content-end">
        <div class="input-group" style="min-width:260px;flex:2">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tìm theo mã, tên vật tư, Lô hoặc Sê-ri">
        </div>
        <select class="form-select" name="category_id" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Danh mục</option>
          @foreach ($categories ?? [] as $cat)
            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
              {{ $cat->name }}
            </option>
          @endforeach
        </select>
        <select class="form-select" name="location_id" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Vị trí</option>
          @foreach ($locations ?? [] as $loc)
            <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
              {{ $loc->code }} - {{ $loc->name }}
            </option>
          @endforeach
        </select>
        <select class="form-select" name="status" style="min-width:150px;flex:1" onchange="this.form.submit()">
          <option value="">Trạng thái</option>
          @foreach (\App\Enums\LotSerialStatus::options() as $val => $label)
            <option value="{{ $val }}" {{ request('status') == (string) $val ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>

        @php
          $hasFilter = request()->hasAny(['search','category_id','location_id','status']);
        @endphp
        @if ($hasFilter)
          <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter-x') }}"></use></svg>
          </a>
        @else
          <button type="submit" class="btn btn-primary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
          </button>
        @endif
      </form>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible mx-3 mt-3 mb-0" role="alert">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
        {{ session('success') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>
    @endif

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:4%">#</th>
              <th style="width:8%">
                <a href="{{ $sortUrl('product_code') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Mã {!! $sortIcon('product_code') !!}
                </a>
              </th>
              <th style="min-width:200px">
                <a href="{{ $sortUrl('product_name') }}" class="text-decoration-none text-body d-inline-flex align-items-center">
                  Tên {!! $sortIcon('product_name') !!}
                </a>
              </th>
              <th style="width:8%">Danh mục</th>
              <th style="width:8%">Vị trí</th>
              <th style="width:8%">Lô/Sê-ri</th>
              <th style="width:8%">ĐVT</th>
              <th class="text-end" style="width:8%">
                <a href="{{ $sortUrl('quantity') }}" class="text-decoration-none text-body d-inline-flex align-items-center justify-content-end w-100">
                  Tồn kho {!! $sortIcon('quantity') !!}
                </a>
              </th>
              <th class="text-end" style="width:8%">Đang giữ</th>
              <th class="text-end" style="width:8%">
                <a href="{{ $sortUrl('available_qty') }}" class="text-decoration-none text-body d-inline-flex align-items-center justify-content-end w-100">
                  Khả dụng {!! $sortIcon('available_qty') !!}
                </a>
              </th>
              <th class="text-center" style="width:8%">
                <a href="{{ $sortUrl('expiry_date') }}" class="text-decoration-none text-body d-inline-flex align-items-center justify-content-center w-100">
                  HSD {!! $sortIcon('expiry_date') !!}
                </a>
              </th>
              <th class="text-center" style="width:8%">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($stocks ?? [] as $row)
            @php
              $statusEnum  = $row->status instanceof \App\Enums\LotSerialStatus
                  ? $row->status
                  : \App\Enums\LotSerialStatus::tryFrom((int) $row->status);
              $statusText  = $statusEnum?->label() ?? '?';
              $statusBadge = $statusEnum?->badgeClass() ?? 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';

              $available = (float)$row->available_qty;
              $qty       = (float)$row->quantity;
              $minStock  = (float)($row->min_stock ?? 0);

              // Cảnh báo tồn kho
              $stockAlert = '';
              if ($qty == 0)         $stockAlert = 'zero';
              elseif ($minStock > 0 && $qty <= $minStock) $stockAlert = 'low';

              // HSD
              $expiryDays  = null;
              $expiryClass = '';
              if ($row->expiry_date) {
                  $expiryDays  = now()->diffInDays(\Carbon\Carbon::parse($row->expiry_date), false);
                  $expiryClass = $expiryDays < 0 ? 'danger' : ($expiryDays <= 7 ? 'danger' : ($expiryDays <= 30 ? 'warning' : 'success'));
              }
            @endphp
            <tr class="{{ $stockAlert === 'zero' ? 'table-secondary opacity-75' : ($stockAlert === 'low' ? 'table-warning' : '') }}">
              <td class="text-center text-body-secondary">
                {{ method_exists($stocks, 'currentPage') ? ($stocks->currentPage() - 1) * $stocks->perPage() + $loop->iteration : $loop->iteration }}
              </td>
              <td>
                <code class="text-primary fw-medium">{{ $row->product_code }}</code>
              </td>
              <td>
                <div class="fw-medium">{{ $row->product_name }}</div>
                @if($stockAlert === 'low')
                  <div style="font-size:11px" class="text-danger">
                    <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
                    Dưới ngưỡng tối thiểu (min: {{ number_format($minStock, 0) }})
                  </div>
                @endif
              </td>
              <td class="text-body-secondary small">{{ $row->category_name ?? '-' }}</td>
              <td>
                <span class="small">
                  <code class="text-secondary">{{ $row->location_code }}</code>
                  <span class="text-body-secondary">{{ $row->location_name }}</span>
                </span>
              </td>
              <td class="small">
                @if($row->lot_id && $row->serial_number)
                  <div class="text-body-secondary">Lot: {{ $row->lot_number }}</div>
                  <div><code>S/N: {{ $row->serial_number }}</code></div>
                @elseif($row->lot_id)
                  <code>{{ $row->lot_number }}</code>
                @elseif($row->serial_id)
                  <code>S/N: {{ $row->serial_number }}</code>
                @else
                  <span class="text-body-secondary">-</span>
                @endif
              </td>
              <td class="small text-body-secondary">{{ $row->uom_name }}</td>
              <td class="text-end fw-semibold {{ $qty == 0 ? 'text-body-secondary' : '' }}">
                {{ number_format($qty, 0) }}
              </td>
              <td class="text-end">
                @if((float)$row->reserved_qty > 0)
                  <span class="text-warning fw-medium">{{ number_format($row->reserved_qty, 0) }}</span>
                @else
                  <span class="text-body-secondary">-</span>
                @endif
              </td>
              <td class="text-end fw-bold {{ $available <= 0 ? 'text-danger' : 'text-primary' }}">
                {{ number_format($available, 0) }}
              </td>
              <td class="text-center">
                @if($row->expiry_date)
                  <span class="badge bg-{{ $expiryClass }}-subtle text-{{ $expiryClass }} border border-{{ $expiryClass }}-subtle" style="font-size:11px">
                    {{ \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') }}
                  </span>
                @else
                  <span class="text-body-secondary small">-</span>
                @endif
              </td>
              <td class="text-center">
                <span class="{{ $statusBadge }}">
                  {{ $statusText }}
                </span>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="12" class="text-center text-body-secondary py-5">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                </svg>
                Không tìm thấy dữ liệu tồn kho.
              </td>
            </tr>
            @endforelse
          </tbody>
          @if(isset($stocks) && $stocks->count() > 0)
          <tfoot class="table-light fw-semibold">
            <tr>
              <td colspan="7" class="text-end">Tổng trang này:</td>
              <td class="text-end">{{ number_format(collect($stocks->items())->sum('quantity'), 0) }}</td>
              <td class="text-end text-warning">{{ number_format(collect($stocks->items())->sum('reserved_qty'), 0) }}</td>
              <td class="text-end text-primary">{{ number_format(collect($stocks->items())->sum('available_qty'), 0) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>

    @if(isset($stocks) && method_exists($stocks, 'hasPages'))
    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
        Hiển thị <strong>{{ $stocks->firstItem() ?? 0 }}</strong>-<strong>{{ $stocks->lastItem() ?? 0 }}</strong>
        trong tổng số <strong>{{ $stocks->total() }}</strong> dòng
      </small>
      {{ $stocks->appends(request()->query())->links('pagination::bootstrap-5') }}
      <style>
        .card-footer .pagination { margin-bottom: 0; }
      </style>
    </div>
    @endif
  </div>

@endsection

@push('scripts')
<script>
</script>
@endpush