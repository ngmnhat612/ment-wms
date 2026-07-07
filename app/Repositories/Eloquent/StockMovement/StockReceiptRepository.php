<?php

namespace App\Repositories\Eloquent\StockMovement;

use App\Models\StockMovement\StockReceipt;
use App\Models\StockMovement\StockReceiptLine;
use App\Models\StockMovement\StockReceiptDetail;
use App\Repositories\Contracts\StockMovement\StockReceiptRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockReceiptRepository implements StockReceiptRepositoryInterface
{

    public function findWithDetails(int $id): ?StockReceipt
    {
        return StockReceipt::with([
            'createdBy', 'approvedBy', 'stockInRequest',
            'lines.product.uom', 'lines.product.category',
            'lines.uom', 'lines.sn',
            'lines.details.location', 'lines.details.lot',
            'lines.details.serial', 'lines.details.receiver',
        ])->find($id);
    }

    public function create(array $headerData): StockReceipt
    {
        return StockReceipt::create($headerData);
    }

    public function update(StockReceipt $receipt, array $headerData): StockReceipt
    {
        $receipt->update($headerData);
        return $receipt->fresh();
    }

    public function delete(StockReceipt $receipt): bool
    {
        return (bool) $receipt->delete();
    }

    public function replaceDetails(StockReceipt $receipt, array $lineRows): void
    {
        $receipt->lines()->delete(); // cascade FK sẽ tự xóa detail con (kiểm tra migration 000026 có onDelete cascade chưa)

        foreach ($lineRows as $line) {
            $detailRows = $line['details'] ?? [];
            unset($line['details']);

            $line['stock_receipt_id'] = $receipt->id;
            $createdLine = StockReceiptLine::create($line);

            foreach ($detailRows as $detail) {
                $detail['stock_receipt_line_id'] = $createdLine->id;
                StockReceiptDetail::create($detail);
            }
        }
    }

    public function countByStatus(int $status): int
    {
        return StockReceipt::where('status', $status)->count();
    }

    public function generateCode(): string
    {
        $prefix = 'NK-' . now()->format('Ym') . '-';
        $last = StockReceipt::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function allForMovementList(array $filters): Collection
    {
        $query = StockReceipt::query()
            ->with(['createdBy.employee', 'approvedBy.employee'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->get();
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('details', function ($d) use ($search) {
                      $d->where('reference_no', 'like', "%{$search}%");
                  });
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('receipt_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('receipt_date', '<=', $filters['date_to']);
        }
    }
}