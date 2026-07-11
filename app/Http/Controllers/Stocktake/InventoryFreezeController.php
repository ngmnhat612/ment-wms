<?php

namespace App\Http\Controllers\Stocktake;

use App\Http\Controllers\Controller;
use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\InventoryFreeze;
use App\Services\Stocktake\InventoryFreezeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryFreezeController extends Controller
{
    public function __construct(
        private InventoryFreezeService $freezeService,
    ) {}

    public function store(Request $request, InventoryCheck $stocktake)
    {
        Gate::authorize('manage', $stocktake);

        $request->validate([
            'freeze_scope' => ['required', 'integer', \Illuminate\Validation\Rule::enum(\App\Enums\InventoryCheckScope::class)],
        ]);

        try {
            $this->freezeService->freeze($stocktake, (int) $request->input('freeze_scope'));
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.show', $stocktake)->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', 'Đã đóng băng kho để kiểm kê.');
    }

    public function unfreeze(InventoryCheck $stocktake, InventoryFreeze $freeze)
    {
        Gate::authorize('manage', $freeze);

        try {
            $this->freezeService->unfreeze($freeze);
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.show', $stocktake)->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', 'Đã mở đóng băng kho.');
    }
}
