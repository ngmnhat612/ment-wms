@extends('layouts.app')

@section('title', 'Kiểm kê kho')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item active">Kiểm kê</li>
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

  {{-- HEADER --}}
  <div class="d-flex justify-content-end gap-2 mb-4">
    <a href="{{ Route::has('stocktakes.create') ? route('stocktakes.create') : '#' }}"
       class="btn btn-primary {{ Route::has('stocktakes.create') ? '' : 'disabled' }}">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Tạo phiếu kiểm kê
    </a>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header">
    <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-3 flex-wrap">

        {{-- CỘT 1: Tiêu đề --}}
        <div class="flex-shrink-0">
        <span class="fw-semibold text-nowrap">Kiểm kê kho</span>
        </div>

        {{-- CỘT 2 + CỘT 3: chiếm hết khoảng trống giữa, chia đều nhau --}}
        <div class="flex-grow-1" style="min-width:400px">
        <div class="row g-2">

            {{-- CỘT 2: Tìm kiếm --}}
            <div class="col-6 d-flex align-items-center">
            <div class="input-group">
                <span class="input-group-text">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
                </span>
                <input type="text" class="form-control" name="search"
                    value="{{ request('search') }}" placeholder="Mã phiếu, mục đích...">
            </div>
            </div>

            {{-- CỘT 3: Phạm vi + Trạng thái (dòng 1), Từ - Đến ngày (dòng 2) --}}
            <div class="col-6 d-flex flex-column gap-2">
            <div class="d-flex gap-2">
                <select class="form-select" name="check_scope" onchange="this.form.submit()">
                <option value="">Phạm vi</option>
                @foreach (\App\Enums\InventoryCheckScope::cases() as $case)
                  <option value="{{ $case->value }}" {{ request('check_scope') == $case->value ? 'selected' : '' }}>
                    {{ $case->label() }}
                  </option>
                @endforeach
                </select>
                <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="">Trạng thái</option>
                @foreach (\App\Enums\InventoryCheckStatus::cases() as $case)
                  <option value="{{ $case->value }}" {{ request('status') == $case->value ? 'selected' : '' }}>
                    {{ $case->label() }}
                  </option>
                @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <div class="input-group">
                <span class="input-group-text">Từ</span>
                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" title="Từ ngày">
                </div>
                <div class="input-group">
                <span class="input-group-text">Đến</span>
                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" title="Đến ngày">
                </div>
            </div>
            </div>

        </div>
        </div>

        {{-- CỘT 4: Nút Lọc --}}
        <div class="flex-shrink-0">
        @php
            $hasFilter = request('search') || request('check_scope') || request('status') || request('date_from') || request('date_to');
        @endphp
        @if ($hasFilter)
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter-x') }}"></use></svg>
            </a>
        @else
            <button type="submit" class="btn btn-primary">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
            </button>
        @endif
        </div>

    </form>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible mx-3 mt-3 mb-0" role="alert">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
        {{ session('success') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible mx-3 mt-3 mb-0" role="alert">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
        {{ session('error') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>
    @endif

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
                <th class="text-center" style="width:4%">#</th>
                <th style="width:10%">
                    <a href="{{ $sortUrl('code') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Mã phiếu {!! $sortIcon('code') !!}
                    </a>
                </th>
                <th style="width:10%">Phạm vi</th>
                <th style="width:12%">Loại kiểm kê</th>
                <th>
                    <a href="{{ $sortUrl('created_by') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Người tạo {!! $sortIcon('created_by') !!}
                    </a>
                </th>
                <th style="width:10%">
                    <a href="{{ $sortUrl('check_date') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Ngày kiểm {!! $sortIcon('check_date') !!}
                    </a>
                </th>
                <th style="width:14%">
                    <a href="{{ $sortUrl('purpose') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Mục đích {!! $sortIcon('purpose') !!}
                    </a>
                </th>
                <th class="text-center" style="width:8%">Trạng thái</th>
                <th class="text-center" style="width:10%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {{--
                TODO (Controller/Service): mỗi item $checks cần kèm thêm thuộc tính
                phái sinh sau (giống StockMovementController::index()):
                  - created_by / created_by_code : join accounts để hiển thị tên + mã NV
                Xem InventoryCheckController::index() để áp dụng tương tự StockMovementController.
            --}}
            @forelse ($checks as $index => $check)
              @php
                  $status     = $check->status;
                  $scope      = $check->check_scope;
                  $type       = $check->check_type;
              @endphp
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($checks->currentPage() - 1) * $checks->perPage() + $index + 1 }}
                </td>
                <td>
                  <a href="{{ Route::has('stocktakes.show') ? route('stocktakes.show', $check->id) : '#' }}"
                     class="fw-medium text-primary text-decoration-none">
                    <code>{{ $check->code }}</code>
                  </a>
                </td>
                <td class="small">
                  {{ $scope?->label() ?? '-' }}
                </td>
                <td class="small">
                  {{ $type?->label() ?? '-' }}
                </td>
                <td>
                    @if($check->created_by)
                    <div class="fw-medium">{{ $check->created_by }}</div>
                    <div class="small text-body-secondary font-monospace">{{ $check->created_by_code }}</div>
                    @else
                    -
                    @endif
                </td>
                <td class="small">
                  {{ $check->check_date ? \Carbon\Carbon::parse($check->check_date)->format('d/m/Y') : '-' }}
                </td>
                <td class="small text-body-secondary text-truncate" style="max-width:180px" title="{{ $check->purpose }}">
                  {{ $check->purpose ?? '-' }}
                </td>
                <td class="text-center">
                  <span class="{{ $status->badgeClass() }}" style="font-size:11px">
                    {{ $status->label() }}
                  </span>
                </td>
                <td class="text-center">
                  <a href="{{ Route::has('stocktakes.show') ? route('stocktakes.show', $check->id) : '#' }}"
                     class="btn btn-sm btn-outline-primary me-1" title="Xem chi tiết">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list-rich') }}"></use></svg>
                  </a>
                  @if ($status === \App\Enums\InventoryCheckStatus::Draft)
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete('/stocktakes/{{ $check->id }}', '{{ addslashes($check->code) }}')"
                            title="Xóa">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clipboard') }}"></use>
                  </svg>
                  Chưa có phiếu kiểm kê nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
        Hiển thị <strong>{{ $checks->firstItem() ?? 0 }}</strong>-<strong>{{ $checks->lastItem() ?? 0 }}</strong>
        trong tổng số <strong>{{ $checks->total() }}</strong> phiếu
      </small>
      {{ $checks->appends(request()->query())->links('pagination::bootstrap-5') }}
      <style>
        .card-footer .pagination { margin-bottom: 0; }
      </style>
    </div>
  </div>

  {{-- MODAL XÁC NHẬN XÓA --}}
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center px-4 pb-2">
          <svg class="icon icon-3xl text-danger mb-3">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
          </svg>
          <h6 class="fw-semibold mb-1">Xác nhận xóa</h6>
          <p class="text-body-secondary small mb-0">
            Bạn có chắc muốn xóa phiếu<br>
            <strong id="deleteCode" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Thao tác này không thể hoàn tác.</p>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  function confirmDelete(deleteUrl, code) {
    document.getElementById('deleteCode').textContent = code;
    document.getElementById('deleteForm').action = deleteUrl;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }
</script>
@endpush