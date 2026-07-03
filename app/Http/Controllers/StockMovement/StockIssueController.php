<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockIssue;
use App\Models\StockIssueDetail;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StockIssueController extends Controller
{
    public function __construct(private StockService $stockService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // DANH SÁCH
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = StockIssue::with(['requester', 'createdBy'])->withCount('details');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }
        if ($request->issue_type)    $query->where('issue_type', $request->issue_type);
        if ($request->status !== null && $request->status !== '') $query->where('status', $request->status);
        if ($request->date_from)     $query->where('issue_date', '>=', $request->date_from);
        if ($request->date_to)       $query->where('issue_date', '<=', $request->date_to);

        $issues         = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount     = StockIssue::count();
        $pendingCount   = StockIssue::where('status', 2)->count();
        $completedCount = StockIssue::where('status', 4)->count();
        $cancelledCount = StockIssue::where('status', 5)->count();

        return view('issues.index', compact(
            'issues', 'totalCount', 'pendingCount', 'completedCount', 'cancelledCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM TẠO MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $locations = Location::where('type', 1)->orderBy('code')->get();
        $users     = User::orderBy('name')->get();

        $lots = Lot::where('status', Lot::STATUS_ACTIVE)
            ->select('id', 'product_id', 'lot_number', 'expiry_date')
            ->orderBy('lot_number')
            ->get()
            ->groupBy('product_id');

        $productsJson  = $products->map(fn($p) => [
            'id'            => $p->id,
            'code'          => $p->code,
            'name'          => $p->name,
            'uom'           => $p->uom?->name ?? '—',
            'uom_id'        => $p->uom_id,
            'stock'         => (float) ($p->total_stock ?? 0),
            'tracking_type' => (int) $p->tracking_type,   // ← THÊM
        ])->values();

        $locationsJson = $locations->map(fn($l) => [
            'id'   => $l->id,
            'code' => $l->code,
            'name' => $l->name ?? '',
        ])->values();

        return view('issues.form', compact(
            'products', 'productsJson', 'locations', 'locationsJson', 'users', 'lots'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI (→ DRAFT)
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateIssue($request);

        DB::transaction(function () use ($request) {
            $code = $request->code
                ? strtoupper(trim($request->code))
                : $this->generateCode();

            $issue = StockIssue::create([
                'code'                 => $code,
                'issue_type'           => $request->issue_type,
                'requester_id'         => $request->requester_id ?: null,
                'reference_no'         => $request->reference_no ?: null,
                'issue_date'           => $request->issue_date,
                'expected_return_date' => $request->expected_return_date ?: null,
                'status'               => 1, // DRAFT
                'note'                 => $request->note ?: null,
                'created_by'           => Auth::id(),
            ]);

            $this->saveDetails($issue, $request->details ?? []);
        });

        $action = $request->input('action');
        return $action === 'save_and_new'
            ? redirect()->route('issues.create')->with('success', 'Đã tạo phiếu xuất thành công.')
            : redirect()->route('issues.index')->with('success', 'Đã tạo phiếu xuất thành công.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM CHI TIẾT
    // ──────────────────────────────────────────────────────────────────────────

    public function show(StockIssue $issue)
    {
        $issue->load([
            'requester', 'createdBy', 'confirmer',
            'details.product.uom',
            'details.location',
            'details.lot',
            'details.serial',
            'details.uom',
        ]);

        // Gợi ý Lot/Serial theo FIFO/FEFO để hiện trong modal (chỉ khi chưa hoàn thành)
        $suggestions = collect();
        if (in_array($issue->status, [1, 2, 3])) {
            foreach ($issue->details as $detail) {
                try {
                    $suggest = $this->stockService->suggestStockForIssue(
                        $detail->product_id,
                        $detail->quantity,
                        $detail->location_id
                    );
                    $suggestions[$detail->id] = $suggest;
                } catch (\Exception $e) {
                    $suggestions[$detail->id] = collect();
                }
            }
        }

        return view('issues.show', compact('issue', 'suggestions'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // AJAX: Vị trí & Lot có tồn kho khả dụng theo sản phẩm
    // ──────────────────────────────────────────────────────────────────────────
    public function stockLocations(int $productId)
    {
        $product  = Product::find($productId);
        $tracking = (int) ($product?->tracking_type ?? 1);

        $stocks = Stock::with(['location', 'lot', 'serial'])
            ->where('product_id', $productId)
            ->whereHas('location', function ($q) {
                $q->where('type', Location::TYPE_INTERNAL);
            })
            ->where(function ($q) {
                $q->where('available_qty', '>', 0)
                ->orWhereRaw('(quantity - reserved_qty) > 0');
            })
            ->get();

        $result = collect();

        foreach ($stocks as $s) {
            $baseRow = [
                'location_id'   => $s->location_id,
                'location_code' => $s->location?->code ?? '?',
                'location_name' => $s->location?->name ?? '',
                'lot_id'        => $s->lot_id,
                'lot_number'    => $s->lot?->lot_number,
                'expiry_date'   => $s->lot?->expiry_date?->format('Y-m-d'),
                'serial_id'     => $s->serial_id,
                'serial_number' => $s->serial?->serial_number,
                'available_qty' => (float) $s->available_qty,
            ];

            // Tracking=4 (LotAndSerial): nếu stock lưu theo lot-level (serial_id = null)
            // → mở rộng thành từng serial riêng từ bảng serials
            if ($tracking === Product::TRACKING_LOT_AND_SERIAL
                && $s->lot_id
                && ! $s->serial_id
            ) {
                $serials = \App\Models\Serial::where('product_id', $productId)
                    ->where('lot_id', $s->lot_id)
                    ->where('status', \App\Models\Serial::STATUS_INSTOCK)
                    ->get();

                if ($serials->isNotEmpty()) {
                    foreach ($serials as $serial) {
                        $result->push(array_merge($baseRow, [
                            'serial_id'     => $serial->id,
                            'serial_number' => $serial->serial_number,
                        ]));
                    }
                    continue; // bỏ qua dòng lot-level gốc
                }
            }

            $result->push($baseRow);
        }

        return response()->json($result->values());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM CHỈNH SỬA
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(StockIssue $issue)
    {
        if ((int) $issue->status !== StockIssue::STATUS_DRAFT) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $issue->load(['details.product', 'details.location', 'details.lot', 'details.uom']);
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $locations = Location::where('type', 1)->orderBy('code')->get();
        $users     = User::orderBy('name')->get();
        $lots      = Lot::where('status', Lot::STATUS_ACTIVE)
            ->select('id', 'product_id', 'lot_number', 'expiry_date')
            ->orderBy('lot_number')->get()->groupBy('product_id');

        $productsJson  = $products->map(fn($p) => [
            'id'            => $p->id,
            'code'          => $p->code,
            'name'          => $p->name,
            'uom'           => $p->uom?->name ?? '—',
            'uom_id'        => $p->uom_id,
            'stock'         => (float) ($p->total_stock ?? 0),
            'tracking_type' => (int) $p->tracking_type,   // ← THÊM
        ])->values();

        $locationsJson = $locations->map(fn($l) => [
            'id'   => $l->id,
            'code' => $l->code,
            'name' => $l->name ?? '',
        ])->values();

        return view('issues.form', compact(
            'issue', 'products', 'productsJson', 'locations', 'locationsJson', 'users', 'lots'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, StockIssue $issue)
    {
        if ((int) $issue->status !== StockIssue::STATUS_DRAFT) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $this->validateIssue($request, isUpdate: true);

        DB::transaction(function () use ($request, $issue) {
            $issue->update([
                'issue_type'           => $request->issue_type,
                'requester_id'         => $request->requester_id ?: null,
                'reference_no'         => $request->reference_no ?: null,
                'issue_date'           => $request->issue_date,
                'expected_return_date' => $request->expected_return_date ?: null,
                'note'                 => $request->note ?: null,
            ]);

            $issue->details()->delete();
            $this->saveDetails($issue, $request->details ?? []);
        });

        return redirect()->route('issues.show', $issue)
            ->with('success', "Đã cập nhật phiếu {$issue->code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XÓA PHIẾU (chỉ Draft)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(StockIssue $issue)
    {
        if ((int) $issue->status !== 1) {
            return redirect()->route('issues.index')
                ->with('error', "Không thể xóa phiếu {$issue->code} vì không ở trạng thái Draft.");
        }

        $code = $issue->code;
        DB::transaction(function () use ($issue) {
            $issue->details()->delete();
            $issue->delete();
        });

        return redirect()->route('issues.index')
            ->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHUYỂN TRẠNG THÁI: DRAFT → PENDING (Gửi duyệt)
    // ──────────────────────────────────────────────────────────────────────────

    public function submit(StockIssue $issue)
    {
        if ((int) $issue->status !== 1) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể gửi duyệt phiếu đang ở trạng thái Draft.');
        }

        $issue->update(['status' => 2]); // PENDING

        return redirect()->route('issues.show', $issue)
            ->with('success', "Phiếu {$issue->code} đã được gửi duyệt.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHUYỂN TRẠNG THÁI: PENDING → APPROVED (Duyệt & giữ hàng)
    // Gọi StockService::reserve() để khóa available_qty
    // ──────────────────────────────────────────────────────────────────────────

    public function approve(StockIssue $issue)
    {
        Gate::authorize('issue.approve');

        if ((int) $issue->status !== StockIssue::STATUS_PENDING) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể duyệt phiếu đang ở trạng thái Chờ duyệt.');
        }

        $issue->load('details.product');

        try {
            DB::transaction(function () use ($issue) {
                foreach ($issue->details as $detail) {
                    if ($detail->quantity <= 0) continue;

                    $strategy = $detail->product?->stock_rotation === 2 ? 'FEFO' : 'FIFO';

                    // Gợi ý và lấy danh sách stock lines để reserve
                    $suggestions = $this->stockService->suggestStockForIssue(
                        $detail->product_id,
                        $detail->quantity,
                        $detail->location_id
                    );

                    foreach ($suggestions as $s) {
                        $this->stockService->reserve([
                            'product_id'       => $detail->product_id,
                            'location_id'      => $s['location_id'],
                            'quantity'         => $s['qty_suggest'],
                            'lot_id'           => $s['lot_id'],
                            'serial_id'        => $s['serial_id'],
                            'transaction_type' => StockService::TYPE_ISSUE,
                            'reference_id'     => $issue->id,
                            'reference_type'   => 'stock_issue',
                            'reference_code'   => $issue->code,
                        ]);
                    }
                }

                $issue->update([
                    'status'       => 3, // APPROVED
                    'confirmed_by' => Auth::id(),
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Không thể duyệt: ' . $e->getMessage());
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Phiếu {$issue->code} đã được duyệt. Hàng đã được giữ chỗ.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHUYỂN TRẠNG THÁI: APPROVED → COMPLETED (Xuất hàng thực tế)
    // Gọi StockService::release() rồi decrease() cho từng dòng
    // ──────────────────────────────────────────────────────────────────────────

    public function confirm(StockIssue $issue)
    {
        if ((int) $issue->status !== StockIssue::STATUS_APPROVED) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể hoàn tất phiếu đã ở trạng thái Đã duyệt.');
        }

        $issue->load('details.product');

        try {
            DB::transaction(function () use ($issue) {
                foreach ($issue->details as $detail) {
                    if ($detail->quantity <= 0) continue;

                    // Thay vì dùng $detail->lot_id (có thể null dù stock có lot),
                    // truy vấn trực tiếp các dòng stock đang bị giữ chỗ cho product + location này.
                    // Thứ tự FEFO/FIFO giữ nhất quán với lúc approve.
                    $reservedRows = Stock::where('product_id', $detail->product_id)
                        ->where('location_id', $detail->location_id)
                        ->where('reserved_qty', '>', 0)
                        ->orderByRaw('
                            CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC,
                            expiry_date ASC,
                            received_date ASC,
                            id ASC
                        ')
                        ->get();

                    $remaining = (float) $detail->quantity;

                    foreach ($reservedRows as $rStock) {
                        if ($remaining <= 0.0001) break;

                        $qty = min((float) $rStock->reserved_qty, $remaining);

                        $rowParams = [
                            'product_id'       => $rStock->product_id,
                            'location_id'      => $rStock->location_id,
                            'quantity'         => $qty,
                            'lot_id'           => $rStock->lot_id,
                            'serial_id'        => $rStock->serial_id,
                            'transaction_type' => StockService::TYPE_ISSUE,
                            'reference_id'     => $issue->id,
                            'reference_type'   => 'stock_issue',
                            'reference_code'   => $issue->code,
                            'note'             => "Xuất kho từ phiếu {$issue->code}",
                            'created_by'       => Auth::id(),
                        ];

                        $this->stockService->release($rowParams);
                        $this->stockService->decrease($rowParams);
                        $remaining -= $qty;
                    }

                    if ($remaining > 0.001) {
                        throw new \Exception(
                            "Không đủ hàng đã giữ chỗ để xuất dòng product_id={$detail->product_id}. Còn thiếu: {$remaining}"
                        );
                    }
                }

                $issue->update(['status' => 4]);
            });
        } catch (\Exception $e) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Lỗi khi hoàn tất xuất kho: ' . $e->getMessage());
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Phiếu {$issue->code} hoàn tất. Tồn kho đã được trừ.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HỦY PHIẾU — Nếu đã APPROVED phải giải phóng reserved_qty
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(StockIssue $issue)
    {
        if ((int) $issue->status === StockIssue::STATUS_COMPLETED) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ((int) $issue->status === StockIssue::STATUS_CANCELLED) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Phiếu đã được hủy trước đó.');
        }

        try {
            DB::transaction(function () use ($issue) {
                // Nếu đã APPROVED → phải release reserved_qty đã giữ
                if ((int) $issue->status === StockIssue::STATUS_APPROVED) {
                    $issue->load('details');

                    foreach ($issue->details as $detail) {
                        if ($detail->quantity <= 0) continue;

                    // Giải phóng theo từng dòng stock thực tế đang được giữ chỗ
                    $reservedRows = Stock::where('product_id', $detail->product_id)
                        ->where('location_id', $detail->location_id)
                        ->where('reserved_qty', '>', 0)
                        ->orderByRaw('
                            CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC,
                            expiry_date ASC,
                            received_date ASC,
                            id ASC
                        ')
                        ->get();

                    $remaining = (float) $detail->quantity;

                    foreach ($reservedRows as $rStock) {
                        if ($remaining <= 0.0001) break;

                        $qty = min((float) $rStock->reserved_qty, $remaining);

                        try {
                            $this->stockService->release([
                                'product_id'       => $rStock->product_id,
                                'location_id'      => $rStock->location_id,
                                'quantity'         => $qty,
                                'lot_id'           => $rStock->lot_id,
                                'serial_id'        => $rStock->serial_id,
                                'transaction_type' => StockService::TYPE_ISSUE,
                                'reference_id'     => $issue->id,
                                'reference_type'   => 'stock_issue',
                                'reference_code'   => $issue->code,
                            ]);
                        } catch (\Exception $e) {
                            // Bỏ qua nếu dòng stock không còn
                        }

                        $remaining -= $qty;
                    }
                }
            }

                $issue->update(['status' => 5]); // CANCELLED
            });
        } catch (\Exception $e) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Lỗi khi hủy phiếu: ' . $e->getMessage());
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Đã hủy phiếu {$issue->code}. Hàng đã được trả về trạng thái sẵn sàng.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // IN PHIẾU XUẤT KHO (PDF / Print)
    // ──────────────────────────────────────────────────────────────────────────

    public function printPdf(StockIssue $issue)
    {
        $issue->load([
            'details.product.uom',
            'details.uom',
            'details.location',
            'details.lot',
            'details.serial',
            'createdBy',
            'confirmedBy',
            'requester',
        ]);

        return view('issues.print', compact('issue'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function validateIssue(Request $request, bool $isUpdate = false): void
    {
        $codeRule = $isUpdate
            ? 'nullable|string|max:50'
            : 'nullable|string|max:50|unique:stock_issues,code';

        $request->validate([
            'code'                            => $codeRule,
            'issue_type'                      => 'required|in:1,2,3,4',
            'requester_id'                    => 'nullable|exists:users,id',
            'reference_no'                    => 'nullable|string|max:100',
            'issue_date'                      => 'required|date',
            'expected_return_date'            => 'nullable|date|after_or_equal:issue_date',
            'note'                            => 'nullable|string|max:1000',
            'details'                         => 'required|array|min:1',
            'details.*.product_id'            => 'required|exists:products,id',
            'details.*.uom_id'                => 'required|exists:uoms,id',
            'details.*.quantity'              => 'required|numeric|min:0.001',
            'details.*.location_id'           => 'required|exists:locations,id',
            'details.*.lot_id'                => 'nullable|exists:lots,id',
            'details.*.serial_id'             => 'nullable|exists:serials,id',
            'details.*.note'                  => 'nullable|string|max:200',
        ], [
            'code.unique'                     => 'Mã phiếu đã tồn tại.',
            'issue_type.required'             => 'Vui lòng chọn loại xuất.',
            'issue_date.required'             => 'Vui lòng chọn ngày xuất.',
            'details.required'                => 'Phiếu xuất phải có ít nhất một hàng hóa.',
            'details.*.product_id.required'   => 'Vui lòng chọn hàng hóa.',
            'details.*.uom_id.required'       => 'Vui lòng chọn đơn vị tính.',
            'details.*.quantity.required'     => 'Vui lòng nhập số lượng.',
            'details.*.quantity.min'          => 'Số lượng phải lớn hơn 0.',
            'details.*.location_id.required'  => 'Vui lòng chọn vị trí kho.',
        ]);

        // Kiểm tra trùng serial_id trong cùng một phiếu
        $serials = collect($request->details ?? [])
            ->pluck('serial_id')
            ->filter(); // bỏ null/empty

        if ($serials->count() !== $serials->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'details' => ['Mỗi số Serial chỉ được xuất một lần trong cùng phiếu.'],
            ]);
        }
    }

    private function saveDetails(StockIssue $issue, array $details): void
    {
        foreach ($details as $row) {
            if (empty($row['product_id']) || empty($row['quantity'])) continue;

            StockIssueDetail::create([
                'stock_issue_id' => $issue->id,
                'product_id'     => $row['product_id'],
                'uom_id'         => $row['uom_id'],
                'lot_id'         => ($row['lot_id'] ?? null) ?: null,
                'serial_id'      => ($row['serial_id'] ?? null) ?: null,
                'location_id'    => $row['location_id'],
                'quantity'       => $row['quantity'],
                'note'           => $row['note'] ?: null,
            ]);
        }
    }

    private function generateCode(): string
    {
        $prefix = 'XK-' . now()->format('Ym') . '-';
        $last   = StockIssue::where('code', 'like', $prefix . '%')
                      ->orderByDesc('code')
                      ->value('code');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}