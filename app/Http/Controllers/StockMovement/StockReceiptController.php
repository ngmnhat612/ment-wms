<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Serial;
use App\Models\StockReceipt;
use App\Models\StockReceiptDetail;
use App\Models\Supplier;
use App\Models\Uom;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StockReceiptController extends Controller
{
    public function __construct(private StockService $stockService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // DANH SÁCH
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        //UPDATE
        $query = StockReceipt::with(['supplier', 'createdBy'])->withCount('details');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }
        if ($request->receipt_type)   $query->where('receipt_type', $request->receipt_type);
        if ($request->status !== null && $request->status !== '') $query->where('status', $request->status);
        if ($request->date_from)      $query->where('receipt_date', '>=', $request->date_from);
        if ($request->date_to)        $query->where('receipt_date', '<=', $request->date_to);

        $receipts       = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount     = StockReceipt::count();
        $pendingCount   = StockReceipt::where('status', 2)->count();
        $completedCount = StockReceipt::where('status', 4)->count();
        $cancelledCount = StockReceipt::where('status', 5)->count();

        return view('receipts.index', compact(
            'receipts', 'totalCount', 'pendingCount', 'completedCount', 'cancelledCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM TẠO MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        $products      = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $productsJson  = $products->map(fn($p) => [
            'id'       => $p->id,
            'code'     => $p->code,
            'name'     => $p->name,
            'uom'      => $p->uom?->name ?? '—',
            'uom_id'   => $p->uom_id,
            'stock'    => (float) ($p->total_stock ?? 0),
            'tracking' => (int) ($p->tracking_type ?? 1), // 1=none, 2=lot, 3=serial
        ])->values();
                $suppliers     = Supplier::orderBy('name')->get();
                $locations     = Location::where('type', 1)->orderBy('code')->get();
                $locationsJson = $locations->map(fn($l) => [
                    'id'   => $l->id,
                    'code' => $l->code,
                    'name' => $l->name ?? '',
                ])->values();
                $uoms          = Uom::orderBy('name')->get();
                $putawayRules = DB::table('putaway_rules')->where('status', 1)->get(['product_id', 'category_id', 'location_dest_id']);
        return view('receipts.form', compact('productsJson', 'products', 'suppliers', 'locations', 'locationsJson', 'uoms', 'putawayRules'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI (→ DRAFT)
    // ──────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->validateReceipt($request);
        $this->validateLotSerialRequired($request->details ?? []);

        DB::transaction(function () use ($request) {
            $code = $request->code
                ? strtoupper(trim($request->code))
                : $this->generateCode();

            $receipt = StockReceipt::create([
                'code'         => $code,
                'receipt_type' => $request->receipt_type,
                'supplier_id'  => $request->supplier_id ?: null,
                'reference_no' => $request->reference_no ?: null,
                'receipt_date' => $request->receipt_date,
                'status'       => 1,
                'note'         => $request->note ?: null,
                'created_by'   => Auth::id(),
            ]);

            $this->saveDetails($receipt, $request->details ?? []);
        });

        $action = $request->input('action');
        return $action === 'save_and_new'
            ? redirect()->route('receipts.create')->with('success', 'Đã tạo phiếu nhập thành công.')
            : redirect()->route('receipts.index')->with('success', 'Đã tạo phiếu nhập thành công.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM CHI TIẾT
    // ──────────────────────────────────────────────────────────────────────────

    public function show(StockReceipt $receipt)
    {
        $receipt->load([
            'supplier', 'createdBy', 'confirmedBy',
            'details.product.uom',
            'details.product.category',
            'details.location',
            'details.lot',
            'details.serial',
            'details.uom',
        ]);

        // Tính gợi ý putaway cho các dòng chưa có vị trí (chỉ cần khi status = APPROVED)
        $putawaySuggestions = collect();
        if ((int) $receipt->status === StockReceipt::STATUS_APPROVED) {
            foreach ($receipt->details as $detail) {
                if (! $detail->location_id && $detail->product) {
                    $locationId = $this->stockService->suggestPutawayLocation(
                        $detail->product_id,
                        $detail->product->category_id ?? 0
                    );
                    if ($locationId) {
                        $putawaySuggestions[$detail->id] = Location::find($locationId);
                    }
                }
            }
        }

        return view('receipts.show', compact('receipt', 'putawaySuggestions'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM CHỈNH SỬA
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(StockReceipt $receipt)
    {
        if ((int) $receipt->status !== StockReceipt::STATUS_DRAFT) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $receipt->load(['details.product', 'details.location', 'details.lot', 'details.serial', 'details.uom']);

        $products      = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $productsJson  = $products->map(fn($p) => [
            'id'       => $p->id,
            'code'     => $p->code,
            'name'     => $p->name,
            'uom'      => $p->uom?->name ?? '—',
            'uom_id'   => $p->uom_id,
            'stock'    => (float) ($p->total_stock ?? 0),
            'tracking' => (int) ($p->tracking_type ?? 1), // 1=none, 2=lot, 3=serial
        ])->values();
        $suppliers     = Supplier::orderBy('name')->get();
        $locations     = Location::where('type', 1)->orderBy('code')->get();
        $locationsJson = $locations->map(fn($l) => [
            'id'   => $l->id,
            'code' => $l->code,
            'name' => $l->name ?? '',
        ])->values();
        $uoms         = Uom::orderBy('name')->get();
        $putawayRules = DB::table('putaway_rules')->where('status', 1)->get(['product_id', 'category_id', 'location_dest_id']);

        return view('receipts.form', compact(
            'receipt', 'products', 'productsJson', 'suppliers',
            'locations', 'locationsJson', 'uoms', 'putawayRules'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, StockReceipt $receipt)
    {
        if ((int) $receipt->status !== StockReceipt::STATUS_DRAFT) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $this->validateReceipt($request, isUpdate: true);
        $this->validateLotSerialRequired($request->details ?? []);

        DB::transaction(function () use ($request, $receipt) {
            $receipt->update([
                'receipt_type' => $request->receipt_type,
                'supplier_id'  => $request->supplier_id ?: null,
                'reference_no' => $request->reference_no ?: null,
                'receipt_date' => $request->receipt_date,
                'note'         => $request->note ?: null,
            ]);

            $receipt->details()->delete();
            $this->saveDetails($receipt, $request->details ?? []);
        });

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Đã cập nhật phiếu {$receipt->code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XÓA PHIẾU (chỉ Draft)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(StockReceipt $receipt)
    {
        if ((int) $receipt->status !== StockReceipt::STATUS_DRAFT) {
            return redirect()->route('receipts.index')
                ->with('error', "Không thể xóa phiếu {$receipt->code} vì không ở trạng thái Draft.");
        }

        $code = $receipt->code;
        DB::transaction(function () use ($receipt) {
            $receipt->details()->delete();
            $receipt->delete();
        });

        return redirect()->route('receipts.index')
            ->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHUYỂN TRẠNG THÁI: DRAFT → PENDING (Gửi duyệt)
    // ──────────────────────────────────────────────────────────────────────────

    public function submit(StockReceipt $receipt)
    {
        if ((int) $receipt->status !== StockReceipt::STATUS_DRAFT) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể gửi duyệt phiếu đang ở trạng thái Draft.');
        }

        if ($receipt->details()->count() === 0) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Phiếu chưa có hàng hóa. Vui lòng thêm ít nhất một dòng.');
        }

        $receipt->update(['status' => 2]); // PENDING

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Phiếu {$receipt->code} đã được gửi duyệt.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHUYỂN TRẠNG THÁI: PENDING → APPROVED (Duyệt phiếu)
    // ──────────────────────────────────────────────────────────────────────────

    public function approve(StockReceipt $receipt)
    {
        Gate::authorize('receipt.approve');

        if ((int) $receipt->status !== StockReceipt::STATUS_PENDING) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể duyệt phiếu đang ở trạng thái Chờ duyệt.');
        }

        $receipt->update([
            'status'       => 3, // APPROVED
            'confirmed_by' => Auth::id(),
        ]);

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Phiếu {$receipt->code} đã được duyệt. Tiến hành nhận hàng để hoàn tất.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CHUYỂN TRẠNG THÁI: APPROVED → COMPLETED (Nhận hàng & cập nhật tồn kho)
    // ──────────────────────────────────────────────────────────────────────────

    public function confirm(StockReceipt $receipt)
    {
        if ((int) $receipt->status !== StockReceipt::STATUS_APPROVED) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể hoàn tất phiếu đã ở trạng thái Đã duyệt.');
        }

        try {
            DB::transaction(function () use ($receipt) {
                $receipt->load('details.product');

                foreach ($receipt->details as $detail) {
                    $qty = $detail->actual_qty ?? $detail->expected_qty;

                    if ($qty <= 0) continue;

                    // Xử lý Lot / Serial theo tracking_type của sản phẩm
                    $lotId    = null;
                    $serialId = null;
                    $product  = $detail->product;
                    $tracking = (int) ($product?->tracking_type ?? Product::TRACKING_NONE);

                    // Tạo/tìm Lot cho type 2 và 4
                    if (in_array($tracking, [Product::TRACKING_LOT, Product::TRACKING_LOT_AND_SERIAL])) {
                        if ($detail->lot_id) {
                            $lotId = $detail->lot_id;
                        } elseif ($detail->lot_number ?? null) {
                            $lot = Lot::firstOrCreate(
                                ['product_id' => $detail->product_id, 'lot_number' => $detail->lot_number],
                                [
                                    'supplier_id'   => $receipt->supplier_id,
                                    'received_date' => $receipt->receipt_date,
                                    'expiry_date'   => $detail->expiry_date,
                                    'status'        => Lot::STATUS_ACTIVE,
                                ]
                            );
                            $lotId = $lot->id;
                        }
                    }

                    // Tạo/tìm Serial cho type 3 và 4
                    if (in_array($tracking, [Product::TRACKING_SERIAL, Product::TRACKING_LOT_AND_SERIAL])) {
                        if ($detail->serial_id) {
                            $serialId = $detail->serial_id;
                        } elseif ($detail->serial_number ?? null) {
                            $serial = Serial::firstOrCreate(
                                ['product_id' => $detail->product_id, 'serial_number' => $detail->serial_number],
                                [
                                    'lot_id'        => $lotId,
                                    'supplier_id'   => $receipt->supplier_id,
                                    'received_date' => $receipt->receipt_date,
                                    'status'        => Serial::STATUS_INSTOCK,
                                ]
                            );
                            $serialId = $serial->id;
                        }
                    }

                    // Gọi StockService::increase() — đây là điểm duy nhất cập nhật tồn kho
                    $this->stockService->increase([
                        'product_id'       => $detail->product_id,
                        'location_id'      => $detail->location_id ?? $this->defaultLocationId(),
                        'quantity'         => $qty,
                        'lot_id'           => $lotId,
                        'serial_id'        => $serialId,
                        'supplier_id'      => $receipt->supplier_id,
                        'received_date'    => $receipt->receipt_date,
                        'expiry_date'      => $detail->expiry_date,
                        'transaction_type' => StockService::TYPE_RECEIPT,
                        'reference_id'     => $receipt->id,
                        'reference_type'   => 'stock_receipt',
                        'reference_code'   => $receipt->code,
                        'note'             => "Nhập kho từ phiếu {$receipt->code}",
                        'created_by'       => Auth::id(),
                    ]);

                    // Cập nhật actual_qty vào detail
                    $detail->update(['actual_qty' => $qty]);
                }

                $receipt->update(['status' => 4]); // COMPLETED
            });
        } catch (\Exception $e) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Lỗi khi hoàn tất phiếu: ' . $e->getMessage());
        }

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Phiếu {$receipt->code} đã hoàn tất. Tồn kho đã được cập nhật.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HỦY PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(StockReceipt $receipt)
    {
        if ((int) $receipt->status === StockReceipt::STATUS_COMPLETED) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ((int) $receipt->status === StockReceipt::STATUS_CANCELLED) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Phiếu đã được hủy trước đó.');
        }

        $receipt->update(['status' => 5]);

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Đã hủy phiếu {$receipt->code}.");
    }

     // ──────────────────────────────────────────────────────────────────────────
    // XUẤT PDF
    // ──────────────────────────────────────────────────────────────────────────

    public function printPdf(StockReceipt $receipt)
    {
        $receipt->load([
            'supplier', 'createdBy', 'confirmedBy',
            'details.product.uom',
            'details.location',
            'details.lot',
            'details.uom',
        ]);

        return view('receipts.print', compact('receipt'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // AJAX: Gợi ý vị trí lưu kho (Putaway)
    // ──────────────────────────────────────────────────────────────────────────

    public function suggestPutaway(Request $request)
    {
        $productId  = (int) $request->product_id;
        $product    = Product::find($productId);
        $locationId = $this->stockService->suggestPutawayLocation($productId, $product?->category_id ?? 0);

        return response()->json(['location_id' => $locationId]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function validateReceipt(Request $request, bool $isUpdate = false): void
    {
        $codeRule = $isUpdate
            ? 'nullable|string|max:50'
            : 'nullable|string|max:50|unique:stock_receipts,code';

        $request->validate([
            'code'                           => $codeRule,
            'receipt_type'                   => 'required|in:1,2,3',
            'supplier_id'                    => 'nullable|exists:suppliers,id',
            'reference_no'                   => 'nullable|string|max:100',
            'receipt_date'                   => 'required|date',
            'note'                           => 'nullable|string|max:1000',
            'details'                        => 'required|array|min:1',
            'details.*.product_id'           => 'required|exists:products,id',
            'details.*.uom_id'               => 'required|exists:uoms,id',
            'details.*.expected_qty'         => 'required|numeric|min:0.001',
            'details.*.actual_qty'           => 'nullable|numeric|min:0',
            'details.*.location_id'          => 'nullable|exists:locations,id',
            'details.*.lot_number'           => 'nullable|string|max:50',
            'details.*.expiry_date'          => 'nullable|date',
            'details.*.serial_number'        => 'nullable|string|max:100',
        ], [
            'code.unique'                    => 'Mã phiếu đã tồn tại.',
            'receipt_type.required'          => 'Vui lòng chọn loại nhập.',
            'receipt_date.required'          => 'Vui lòng chọn ngày nhập.',
            'details.required'               => 'Phiếu nhập phải có ít nhất một hàng hóa.',
            'details.*.product_id.required'  => 'Vui lòng chọn hàng hóa.',
            'details.*.uom_id.required'      => 'Vui lòng chọn đơn vị tính.',
            'details.*.expected_qty.required'=> 'Vui lòng nhập số lượng dự kiến.',
            'details.*.expected_qty.min'     => 'Số lượng phải lớn hơn 0.',
        ]);
    }

    /**
     * Lưu chi tiết phiếu nhập.
     * Tự động tạo/tìm Lot nếu tracking_type = Lot.
     */
    private function saveDetails(StockReceipt $receipt, array $details): void
    {
        foreach ($details as $row) {
            if (empty($row['product_id']) || empty($row['expected_qty'])) continue;

            $product     = Product::find($row['product_id']);
            $tracking    = (int) ($product?->tracking_type ?? Product::TRACKING_NONE);
            $lotId       = null;
            $serialId    = null;
            $lotValue    = trim($row['lot_number'] ?? '');
            $serialValue = trim($row['serial_number'] ?? '');

            // ── Tạo/tìm Lot (tracking 2 và 4) ────────────────────────────────
            if ($lotValue && in_array($tracking, [
                Product::TRACKING_LOT,
                Product::TRACKING_LOT_AND_SERIAL,
            ])) {
                $lot = Lot::firstOrCreate(
                    ['product_id' => $row['product_id'], 'lot_number' => $lotValue],
                    [
                        'supplier_id'   => $receipt->supplier_id,
                        'received_date' => $receipt->receipt_date,
                        'expiry_date'   => $row['expiry_date'] ?? null,
                        'status'        => Lot::STATUS_ACTIVE,
                    ]
                );
                $lotId = $lot->id;
            }

            // ── Tạo/tìm Serial (tracking 3 và 4) ─────────────────────────────
            if ($serialValue && in_array($tracking, [
                Product::TRACKING_SERIAL,
                Product::TRACKING_LOT_AND_SERIAL,
            ])) {
                $serial = Serial::firstOrCreate(
                    ['product_id' => $row['product_id'], 'serial_number' => $serialValue],
                    [
                        'lot_id'        => $lotId,
                        'supplier_id'   => $receipt->supplier_id,
                        'received_date' => $receipt->receipt_date,
                        'expiry_date'   => $row['expiry_date'] ?? null,
                        'status'        => Serial::STATUS_INSTOCK,
                    ]
                );
                $serialId = $serial->id;
            }

            // ── Putaway ───────────────────────────────────────────────────────
            $locationId = $row['location_id'] ?: null;
            if (!$locationId && $product) {
                $locationId = $this->stockService->suggestPutawayLocation(
                    $product->id,
                    $product->category_id
                );
            }

            StockReceiptDetail::create([
                'stock_receipt_id' => $receipt->id,
                'product_id'       => $row['product_id'],
                'uom_id'           => $row['uom_id'],
                'lot_id'           => $lotId,
                'serial_id'        => $serialId,
                'serial_number'    => $serialValue ?: null,
                'location_id'      => $locationId,
                'expected_qty'     => $row['expected_qty'],
                'actual_qty'       => $row['actual_qty'] ?: null,
                'expiry_date'      => $row['expiry_date'] ?: null,
                'qc_status'        => 0,
                'supplier_id'      => $receipt->supplier_id,
            ]);
        }
    }

    private function generateCode(): string
    {
        $prefix = 'NK-' . now()->format('Ym') . '-';
        $last   = StockReceipt::where('code', 'like', $prefix . '%')
                      ->orderByDesc('code')
                      ->value('code');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function defaultLocationId(): int
    {
        return Location::where('type', 1)->orderBy('id')->value('id') ?? 1;
    }

    /**
     * Bắt buộc nhập Lot/Serial theo tracking_type của từng sản phẩm.
     */
    private function validateLotSerialRequired(array $details): void
    {
        $errors = [];

        foreach ($details as $i => $row) {
            if (empty($row['product_id'])) continue;

            $product  = Product::find($row['product_id']);
            $tracking = (int) ($product?->tracking_type ?? Product::TRACKING_NONE);
            $line     = $i + 1;

            $lotValue    = trim($row['lot_number'] ?? '');
            $serialValue = trim($row['serial_number'] ?? '');

            if ($tracking === Product::TRACKING_LOT && $lotValue === '') {
                $errors["details.{$i}.lot_number"] =
                    "Dòng {$line}: Hàng quản lý theo Lô — vui lòng nhập Số Lot/Batch.";
            }

            if ($tracking === Product::TRACKING_SERIAL && $serialValue === '') {
                $errors["details.{$i}.lot_number"] =
                    "Dòng {$line}: Hàng quản lý theo Serial — vui lòng nhập Mã Serial.";
            }

            if ($tracking === Product::TRACKING_LOT_AND_SERIAL) {
                if ($lotValue === '') {
                    $errors["details.{$i}.lot_number"] =
                        "Dòng {$line}: Hàng quản lý theo Lô+Serial — vui lòng nhập Số Lot.";
                }
                if (trim($row['serial_number'] ?? '') === '') {
                    $errors["details.{$i}.serial_number"] =
                        "Dòng {$line}: Hàng quản lý theo Lô+Serial — vui lòng nhập Mã Serial.";
                }
            }
        }

        // Kiểm tra serial trùng trong cùng phiếu (theo product_id)
        $serialSeen = []; // [ product_id => [ serial_number => line ] ]
        foreach ($details as $i => $row) {
            if (empty($row['product_id'])) continue;
            $serialValue = trim($row['serial_number'] ?? '');
            if ($serialValue === '') continue;

            $productId = $row['product_id'];
            $line      = $i + 1;

            if (isset($serialSeen[$productId][$serialValue])) {
                $firstLine = $serialSeen[$productId][$serialValue];
                $errors["details.{$i}.serial_number"] =
                    "Dòng {$line}: Số Serial \"{$serialValue}\" đã nhập ở dòng {$firstLine} (cùng sản phẩm).";
            } else {
                $serialSeen[$productId][$serialValue] = $line;
            }
        }

        // Kiểm tra LotAndSerial — cùng product phải dùng cùng 1 lot
        $lotSeen = []; // [ product_id => [ 'value' => lot_number, 'line' => line ] ]
        foreach ($details as $i => $row) {
            if (empty($row['product_id'])) continue;
            $product  = Product::find($row['product_id']);
            $tracking = (int) ($product?->tracking_type ?? Product::TRACKING_NONE);
            if ($tracking !== Product::TRACKING_LOT_AND_SERIAL) continue;

            $lotValue = trim($row['lot_number'] ?? '');
            if ($lotValue === '') continue;

            $productId = $row['product_id'];
            $line      = $i + 1;

            if (!isset($lotSeen[$productId])) {
                $lotSeen[$productId] = ['value' => $lotValue, 'line' => $line];
            } elseif ($lotSeen[$productId]['value'] !== $lotValue) {
                $firstLine = $lotSeen[$productId]['line'];
                $firstLot  = $lotSeen[$productId]['value'];
                $errors["details.{$i}.lot_number"] =
                    "Dòng {$line}: Số Lot \"{$lotValue}\" khác với dòng {$firstLine} (\"{$firstLot}\"). Các serial trong cùng lô phải dùng cùng mã lot.";
            }
        }

        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }
}