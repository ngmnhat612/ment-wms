{{--
  Params:
    $details        — Collection<InventoryCheckDetail>
    $canEdit        — bool, hiện input nhập actual_qty (chỉ khi phiếu đang InProgress)
    $highlightDiff  — bool (optional, default false), tô màu dòng có chênh lệch + hiện tổng
    $checkType      — App\Enums\InventoryCheckType (optional), quyết định cột nào được mở nhập:
                        Quantity → chỉ mở "Thực tế"; Location → chỉ mở "Vị trí thực tế"; Both → mở cả hai
--}}
@php
  $highlightDiff = $highlightDiff ?? false;
  $checkType     = $checkType ?? \App\Enums\InventoryCheckType::Both;
  $canEditQty    = $canEdit && in_array($checkType, [\App\Enums\InventoryCheckType::Quantity, \App\Enums\InventoryCheckType::Both], true);
  $canEditLoc    = $canEdit && in_array($checkType, [\App\Enums\InventoryCheckType::Location, \App\Enums\InventoryCheckType::Both], true);
@endphp

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th class="text-center" style="width:4%">#</th>
        <th>Vật tư</th>
        <th style="width:8%">ĐVT</th>
        <th style="width:4%">Lô</th>
        <th style="width:10%">Vị trí hệ thống</th>
        <th style="width:10%">Vị trí thực tế</th>
        <th class="text-end" style="width:10%">Tồn hệ thống</th>
        <th class="text-end" style="width:10%">Tồn thực tế</th>
        <th class="text-end" style="width:10%">Chênh lệch</th>
        <th style="width:10%">Người kiểm</th>
        <th class="text-center" style="width:10%">Trạng thái</th>
      </tr>
    </thead>
    <tbody>
      @forelse($details as $i => $detail)
      @php
        $diff      = (float) $detail->diff_qty;
        $isCounted = $detail->actual_qty !== null;
        $hasDiff   = $isCounted && $diff != 0;
        $rowClass  = $highlightDiff && $hasDiff
          ? ($diff > 0 ? 'table-success' : 'table-danger')
          : '';
      @endphp
      <tr class="{{ $rowClass }}">
        <td class="text-body-secondary small">{{ $loop->iteration }}</td>

        {{-- Vật tư --}}
        <td>
          <div class="fw-semibold small">{{ $detail->product->name ?? '—' }}</div>
          <div class="text-body-secondary" style="font-size:11px">{{ $detail->product->code ?? '' }}</div>
        </td>

        {{-- ĐVT --}}
        <td class="small text-center text-body-secondary">{{ $detail->uom->name ?? '—' }}</td>

        {{-- Lô --}}
        <td class="small text-body-secondary">{{ $detail->lot->lot_code ?? '—' }}</td>

        {{-- Vị trí hệ thống --}}
        <td class="small text-body-secondary">{{ $detail->systemLocation->code ?? '—' }}</td>

        {{-- Vị trí thực tế --}}
        <td class="small">
          @if($canEditLoc)
            <select name="details[{{ $loop->index }}][actual_location_id]"
                    class="form-select form-select-sm actual-location-select"
                    data-detail-id="{{ $detail->id }}">
              <option value="">— Giữ nguyên —</option>
              @foreach($locationOptions ?? [] as $loc)
                <option value="{{ $loc->id }}"
                  {{ old("details.{$loop->index}.actual_location_id", $detail->actual_location_id) == $loc->id ? 'selected' : '' }}>
                  {{ $loc->code }}
                </option>
              @endforeach
            </select>
          @else
            <span class="text-body-secondary">{{ $detail->actualLocation->code ?? '—' }}</span>
          @endif
        </td>

        {{-- Tồn hệ thống --}}
        <td class="text-end fw-semibold">{{ number_format($detail->system_qty, 0) }}</td>

        {{-- Thực tế — editable nếu loại kiểm kê có kiểm số lượng --}}
        <td class="text-end">
          @if($canEditQty || $canEditLoc)
            <input type="hidden" name="details[{{ $loop->index }}][id]" value="{{ $detail->id }}">
          @endif
          @if($canEditQty)
            <input type="number"
                   name="details[{{ $loop->index }}][actual_qty]"
                   class="form-control form-control-sm text-end actual-qty-input"
                   style="width:90px; margin-left:auto"
                   min="0" step="1"
                   value="{{ old("details.{$loop->index}.actual_qty", $detail->actual_qty) }}"
                   placeholder="Nhập..."
                   data-detail-id="{{ $detail->id }}"
                   data-system="{{ $detail->system_qty }}">
          @else
            <span class="{{ $isCounted ? 'fw-semibold' : 'text-body-secondary' }}">
              {{ $isCounted ? number_format($detail->actual_qty, 0) : '—' }}
            </span>
          @endif
        </td>

        {{-- Chênh lệch --}}
        <td class="text-end">
          @if($isCounted)
            @if($diff > 0)
              <span class="fw-bold text-success">+{{ number_format($diff, 0) }}</span>
            @elseif($diff < 0)
              <span class="fw-bold text-danger">{{ number_format($diff, 0) }}</span>
            @else
              <span class="text-body-secondary">0</span>
            @endif
          @else
            <span class="text-body-secondary">—</span>
          @endif
        </td>

        {{-- Người kiểm (nhân viên được phân công) --}}
        <td class="small text-body-secondary">
          {{ $detail->assignment->name ?? '—' }}
        </td>

        {{-- Trạng thái dòng --}}
        <td class="text-center">
          @if(!$isCounted)
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                  style="font-size:10px">Chưa kiểm</span>
          @elseif($hasDiff)
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"
                  style="font-size:10px">Lệch</span>
          @else
            <span class="badge bg-success-subtle text-success border border-success-subtle"
                  style="font-size:10px">Khớp</span>
          @endif
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="11" class="text-center text-body-secondary py-5">
          <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-inbox') }}"></use>
          </svg>
          Không có dữ liệu.
        </td>
      </tr>
      @endforelse
    </tbody>

    @if($details->count() > 0 && $highlightDiff)
    <tfoot class="table-light fw-semibold">
      <tr>
        <td colspan="6" class="text-end">Tổng:</td>
        <td class="text-end">{{ number_format($details->sum('system_qty'), 0) }}</td>
        <td class="text-end">
          @php $totalActual = $details->whereNotNull('actual_qty')->sum('actual_qty'); @endphp
          {{ $totalActual > 0 ? number_format($totalActual, 0) : '—' }}
        </td>
        <td class="text-end">
          @php
            $totalDiff = $details->whereNotNull('actual_qty')->sum('diff_qty');
          @endphp
          @if($totalDiff > 0)
            <span class="text-success">+{{ number_format($totalDiff, 0) }}</span>
          @elseif($totalDiff < 0)
            <span class="text-danger">{{ number_format($totalDiff, 0) }}</span>
          @else
            <span class="text-body-secondary">0</span>
          @endif
        </td>
        <td colspan="2"></td>
      </tr>
    </tfoot>
    @endif
  </table>
</div>