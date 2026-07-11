@extends('layouts.app')

@section('title', 'Nhập/Xuất')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item active">Nhập/Xuất</li>
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
    <a href="{{ Route::has('receipts.create') ? route('receipts.create') : '#' }}"
       class="btn btn-primary {{ Route::has('receipts.create') ? '' : 'disabled' }}">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Nhập
    </a>
    <a href="{{ Route::has('issues.create') ? route('issues.create') : '#' }}"
       class="btn btn-primary {{ Route::has('issues.create') ? '' : 'disabled' }}">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Xuất
    </a>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header">
    <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-3 flex-wrap">

        {{-- CỘT 1: Tiêu đề --}}
        <div class="flex-shrink-0">
        <span class="fw-semibold text-nowrap">Nhập/Xuất</span>
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
                    value="{{ request('search') }}" placeholder="Tìm kiếm theo Mã phiếu">
            </div>
            </div>

            {{-- CỘT 3: Loại + Trạng thái (dòng 1), Từ - Đến ngày (dòng 2) --}}
            <div class="col-6 d-flex flex-column gap-2">
            <div class="d-flex gap-2">
                <select class="form-select" name="movement_type" onchange="this.form.submit()">
                <option value="">Nhập/Xuất</option>
                <option value="receipt" {{ request('movement_type') == 'receipt' ? 'selected' : '' }}>Nhập</option>
                <option value="issue" {{ request('movement_type') == 'issue' ? 'selected' : '' }}>Xuất</option>
                </select>
                <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="">Trạng thái</option>
                @foreach (\App\Enums\DocumentStatus::cases() as $case)
                  <option value="{{ $case->value }}" {{ request('status') == $case->value ? 'selected' : '' }}>
                    {{ $case->label('movement') }}
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
            $hasFilter = request('search') || request('movement_type') || request('status') || request('date_from') || request('date_to');
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

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
                <th class="text-center" style="width:4%">#</th>
                <th style="width:6%">Loại</th>
                <th style="width:10%">
                    <a href="{{ $sortUrl('code') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Mã phiếu {!! $sortIcon('code') !!}
                    </a>
                </th>
                <th style="width:10%">
                  <a href="{{ $sortUrl('linked_code') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                      Phiếu liên kết {!! $sortIcon('linked_code') !!}
                  </a>
              </th>
                <th>
                    <a href="{{ $sortUrl('created_by') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Người tạo {!! $sortIcon('created_by') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ $sortUrl('approved_by') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Người duyệt {!! $sortIcon('approved_by') !!}
                    </a>
                </th>
                <th style="width:10%">
                    <a href="{{ $sortUrl('doc_date') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Ngày {!! $sortIcon('doc_date') !!}
                    </a>
                </th>
                <th style="width:16%">
                    <a href="{{ $sortUrl('note') }}" class="text-decoration-none text-reset d-inline-flex align-items-center">
                        Ghi chú {!! $sortIcon('note') !!}
                    </a>
                </th>
                <th class="text-center" style="width:8%">Trạng thái</th>
                <th class="text-center" style="width:10%">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {{--
                TODO (Controller/Service): gộp $receipts và $issues thành 1 collection
                chung tên $movements, mỗi item cần có thêm 1 thuộc tính phân biệt,
                ví dụ 'movement_type' = 'receipt' | 'issue', để Blade biết render badge
                và route nào (receipts.show/edit hay issues.show/edit).
                Bổ sung approved_by, note vào mỗi item — xem ghi chú cuối cuộc trò chuyện.
                Xem gợi ý chi tiết ở StockMovementController::index().
            --}}
            @forelse ($movements as $index => $movement)
              @php
                  $status = $movement->status;
                  $isReceipt = $movement->movement_type === 'receipt';
                  $showRoute = $isReceipt ? 'receipts.show' : 'issues.show';
                  $deleteUrl = $isReceipt ? "/receipts/{$movement->id}" : "/issues/{$movement->id}";
                  $linkedRoute = $isReceipt ? 'stock-in-requests.show' : 'stock-out-requests.show';
              @endphp
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($movements->currentPage() - 1) * $movements->perPage() + $index + 1 }}
                </td>
                <td class="fw-medium">
                  @if ($isReceipt)
                      Nhập
                  @else
                      Xuất
                  @endif
                </td>
                <td>
                  <a href="{{ Route::has($showRoute) ? route($showRoute, $movement->id) : '#' }}"
                     class="fw-medium text-primary text-decoration-none">
                    <code>{{ $movement->code }}</code>
                  </a>
                </td>
                <td class="small">
                  @if ($movement->linked_code)
                    <a href="{{ Route::has($linkedRoute) ? route($linkedRoute, $movement->linked_id) : '#' }}"
                      class="fw-medium text-primary text-decoration-none">
                      <code>{{ $movement->linked_code }}</code>
                    </a>
                  @else
                    -
                  @endif
                </td>
                <td>
                    @if($movement->created_by)
                    <div class="fw-medium">{{ $movement->created_by }}</div>
                    <div class="small text-body-secondary font-monospace">{{ $movement->created_by_code }}</div>
                    @else
                    -
                    @endif
                </td>
                <td>
                    @if($movement->approved_by)
                    <div class="fw-medium">{{ $movement->approved_by }}</div>
                    <div class="small text-body-secondary font-monospace">{{ $movement->approved_by_code }}</div>
                    @else
                    -
                    @endif
                </td>
                <td class="small">
                  {{ $movement->doc_date ? \Carbon\Carbon::parse($movement->doc_date)->format('d/m/Y') : '-' }}
                </td>
                <td class="small text-body-secondary text-truncate" style="max-width:200px" title="{{ $movement->note }}">
                  {{ $movement->note ?? '-' }}
                </td>
                <td class="text-center">
                  <span class="{{ $status->badgeClass() }}" style="font-size:11px">
                    {{ $status->label('movement') }}
                  </span>
                </td>
                <td class="text-center">
                  <a href="{{ Route::has($showRoute) ? route($showRoute, $movement->id) : '#' }}"
                     class="btn btn-sm btn-outline-primary me-1" title="Xem chi tiết">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list-rich') }}"></use></svg>
                  </a>
                  @if ($status === \App\Enums\DocumentStatus::Draft)
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete('{{ $deleteUrl }}', '{{ addslashes($movement->code) }}')"
                            title="Xóa">
                      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-truck') }}"></use>
                  </svg>
                  Chưa có phiếu nhập/xuất nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
      <small class="text-body-secondary">
        Hiển thị <strong>{{ $movements->firstItem() ?? 0 }}</strong>-<strong>{{ $movements->lastItem() ?? 0 }}</strong>
        trong tổng số <strong>{{ $movements->total() }}</strong> phiếu
      </small>
      {{ $movements->appends(request()->query())->links('pagination::bootstrap-5') }}
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