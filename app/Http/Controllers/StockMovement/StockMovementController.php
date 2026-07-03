<?php

namespace App\Http\Controllers\StockMovement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        // TODO: khi StockReceiptController/StockIssueController có Service thật,
        // thay thế đoạn này bằng logic gộp $receipts + $issues như gợi ý TODO trong view.
        $movements = new LengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: 15,
            currentPage: 1,
            options: ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('stock-movement.index', [
            'movements'      => $movements,
            'totalCount'     => 0,
            'pendingCount'   => 0,
            'completedCount' => 0,
            'cancelledCount' => 0,
        ]);
    }
}