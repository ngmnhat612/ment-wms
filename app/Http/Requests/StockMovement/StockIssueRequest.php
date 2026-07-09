<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;
use App\Models\Inventory\Serial;
use App\Repositories\Contracts\Inventory\LotRepositoryInterface;
use App\Repositories\Contracts\Inventory\StockRepositoryInterface;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;

class StockIssueRequest extends FormRequest
{
    public function __construct(
        private LotRepositoryInterface $lotRepository,
        private ProductRepositoryInterface $productRepository,
        private StockRepositoryInterface $stockRepository,
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true; // Gate::authorize() đã xử lý ở Controller.
    }

    /**
     * Chuẩn hoá dữ liệu trước khi validate: nếu actual_qty (Thực xuất)
     * bị bỏ trống, tự động coi là 0 thay vì để null gây lỗi ở bước sau.
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('lines'))) {
            return;
        }

        $lines = collect($this->input('lines'))->map(function ($line) {
            if (! isset($line['actual_qty']) || $line['actual_qty'] === '' || $line['actual_qty'] === null) {
                $line['actual_qty'] = 0;
            }

            return $line;
        })->all();

        $this->merge(['lines' => $lines]);
    }

    public function rules(): array
    {
        $isUpdate = $this->route('issue') !== null;

        return [
            'warehouse_id'         => 'required|exists:warehouses,id',
            'stock_out_request_id' => 'nullable|exists:stock_out_request,id',
            'code' => $isUpdate
                ? [
                    'nullable', 'string', 'max:50',
                    Rule::unique('stock_issue', 'code')->ignore($this->route('issue')),
                ]
                : 'nullable|string|max:50|unique:stock_issue,code',
            'issue_date' => 'required|date',
            'note'       => 'nullable|string|max:500',

            // ── Mỗi line = 1 hàng UI (FLAT) ──
            // Sê-ri là 1 chuỗi text (mỗi mã cách nhau <space>), Service sẽ
            // tự tách thành nhiều dòng detail và resolve lot/serial ĐÃ CÓ
            // SẴN trong hệ thống (khác receipt: issue không tạo lô mới).
            'lines'                  => 'required|array|min:1',
            'lines.*.product_id'     => 'required|exists:products,id',
            'lines.*.uom_id'         => 'required|exists:uoms,id',
            'lines.*.expected_qty'   => 'required|numeric|min:0.001',
            'lines.*.actual_qty'     => 'required|numeric|min:0',
            'lines.*.location_id'    => 'required|exists:locations,id',
            'lines.*.receiver_id'    => 'required|exists:employees,id',
            'lines.*.sn_id'          => 'nullable|exists:sns,id',
            'lines.*.sub_warehouse'  => 'nullable|string|max:50',
            'lines.*.note'           => 'nullable|string|max:500',
            'lines.*.lot_number'     => 'required|string|max:50',
            'lines.*.serial_numbers' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required'         => 'Vui lòng chọn kho xuất.',
            'code.unique'                    => 'Mã phiếu đã tồn tại.',
            'issue_date.required'            => 'Vui lòng chọn ngày xuất.',
            'lines.required'                 => 'Phiếu xuất phải có ít nhất một vật tư.',
            'lines.*.product_id.required'    => 'Vui lòng chọn vật tư.',
            'lines.*.uom_id.required'        => 'Vui lòng chọn đơn vị tính.',
            'lines.*.expected_qty.required'  => 'Vui lòng nhập số lượng dự kiến.',
            'lines.*.expected_qty.min'       => 'Số lượng phải lớn hơn 0.',
            'lines.*.location_id.required'   => 'Vui lòng chọn vị trí lấy hàng.',
            'lines.*.receiver_id.required'   => 'Vui lòng chọn người nhận.',
            'lines.*.lot_number.required'    => 'Vui lòng chọn Lô sau khi chọn Vị trí.',
        ];
    }

    /**
     * Validate nghiệp vụ:
     *  - bắt buộc serial_numbers theo tracking_type của từng product
     *  - actual_qty PHẢI khớp đúng số serial đếm được (tracking = Lô+Sê-ri)
     *  - chặn serial trùng trong cùng phiếu
     *  - chặn 1 serial bị xuất 2 lần trong cùng phiếu
     *  - Lô/Sê-ri nhập vào PHẢI ĐÃ TỒN TẠI trong hệ thống (ngược với receipt,
     *    vì issue chỉ xuất hàng đã có tồn kho, không tạo lô/sê-ri mới)
     *  - Lô PHẢI đang có tồn ĐÚNG Vị trí đã chọn (Vị trí -> Lô)
     *  - Sê-ri PHẢI thuộc ĐÚNG Lô đã chọn VÀ đúng Vị trí đã chọn (Lô -> Sê-ri),
     *    khớp với chuỗi phụ thuộc Vật tư -> Vị trí -> Lô -> Sê-ri ở UI.
     *    Dùng chung StockRepository::availableForIssue() với AJAX gợi ý ở
     *    Controller để đảm bảo backend không "tin" dữ liệu chỉ vì UI đã lọc.
     *  - Vị trí đã chọn PHẢI có đủ tồn khả dụng (available_qty = quantity -
     *    reserved_qty) để đáp ứng số lượng Xuất (expected_qty) của dòng đó —
     *    đây là điều kiện BẮT BUỘC để phiếu Draft chuyển reserved_qty thành
     *    "Đang giữ" (StockIssueService::reserve()); nếu không đủ, chặn Lưu
     *    ngay ở bước validate, không để service phải tự raise DomainException
     *    ở giữa transaction.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $lines = $this->input('lines', []);
            $products = $this->productRepository->findManyByIds(
                collect($lines)->pluck('product_id')->filter()->all()
            );

            // Cache tồn kho khả dụng theo product_id — dùng LẠI đúng nguồn dữ
            // liệu với AJAX gợi ý Vị trí/Lô/Sê-ri (StockIssueController::stockLocations()),
            // để tránh 2 nơi có 2 "sự thật" khác nhau về tồn kho.
            $stockCache = [];
            $stocksFor = function (int $productId) use (&$stockCache) {
                return $stockCache[$productId] ??= $this->stockRepository->availableForIssue(
                    $productId,
                    $this->route('issue')?->id
                );
            };

            $serialSeen = []; // ['product_id|serial_value' => 'Dòng N']

            // Khi sửa phiếu (Draft), lượng đang giữ chỗ CỦA CHÍNH PHIẾU NÀY
            // (ở lot/location cũ) phải được CỘNG LẠI vào available_qty trước
            // khi so sánh với expected_qty mới — nếu không, sửa phiếu (không
            // đổi gì) vẫn báo thiếu tồn vì tồn kho đang coi lượng đã giữ chỗ
            // của chính phiếu là "không khả dụng".
            $issue = $this->route('issue');
            $ownReservedByKey = []; // 'productId|locationId|lotId|serialId' => qty
            if ($issue) {
                $issue->loadMissing('lines.details');
                foreach ($issue->lines as $existingLine) {
                    foreach ($existingLine->details as $existingDetail) {
                        if (empty($existingDetail->location_id) || empty($existingDetail->lot_id)) {
                            continue;
                        }
                        $qty = (float) ($existingDetail->actual_qty ?: $existingLine->expected_qty);
                        $key = $existingLine->product_id . '|' . $existingDetail->location_id . '|'
                            . $existingDetail->lot_id . '|' . ($existingDetail->serial_id ?? '0');
                        $ownReservedByKey[$key] = ($ownReservedByKey[$key] ?? 0) + $qty;
                    }
                }
            }

            foreach ($lines as $i => $row) {
                if (empty($row['product_id'])) continue;

                $product      = $products->get($row['product_id']);
                $tracking     = (int) ($product?->tracking_type?->value ?? 1);
                $line         = $i + 1;
                $locationId   = $row['location_id'] ?? null;
                $expectedQty  = (float) ($row['expected_qty'] ?? 0);

                $serialNumbers = collect(preg_split('/\s+/', trim((string) ($row['serial_numbers'] ?? ''))))
                    ->filter(fn ($s) => $s !== '')
                    ->values();

                // ── Số Lô nhập tay phải ĐÃ TỒN TẠI trong hệ thống VÀ đang nằm
                //    đúng ở Vị trí đã chọn (Vị trí -> Lô, khớp với luồng UI) ──
                $lotNumberInput = trim((string) ($row['lot_number'] ?? ''));
                $lotId = null;

                if ($lotNumberInput !== '') {
                    if (! ctype_digit($lotNumberInput)) {
                        $validator->errors()->add(
                            "lines.{$i}.lot_number",
                            "Dòng {$line}: Số Lô phải là số nguyên dương."
                        );
                    } else {
                        $existingLot = $this->lotRepository->findByLotNumber(
                            (int) $lotNumberInput,
                            (int) $row['product_id']
                        );

                        if (! $existingLot) {
                            $validator->errors()->add(
                                "lines.{$i}.lot_number",
                                "Dòng {$line}: Số Lô \"{$lotNumberInput}\" không tồn tại trong hệ thống."
                            );
                        } elseif (! $locationId) {
                            // Không có location_id thì không thể xác định Lô này có đang
                            // nằm đúng vị trí hay không — báo lỗi tường minh thay vì bỏ qua
                            // bước kiểm tra (rule 'lines.*.location_id' => 'required' đã có
                            // thể báo lỗi riêng, nhưng ở đây cần chặn luôn để không vô tình
                            // coi Lô là hợp lệ khi thiếu dữ liệu để xác minh).
                            $validator->errors()->add(
                                "lines.{$i}.lot_number",
                                "Dòng {$line}: Cần chọn Vị trí trước khi chọn Lô."
                            );
                        } else {
                            $lotId = $existingLot->id;
                            $stocksForProduct = $stocksFor((int) $row['product_id']);

                            $lotAtLocation = $stocksForProduct
                                ->contains(fn ($s) => (int) $s->lot_id === (int) $lotId
                                    && (int) $s->current_location_id === (int) $locationId);

                            if (! $lotAtLocation) {
                                $validator->errors()->add(
                                    "lines.{$i}.lot_number",
                                    "Dòng {$line}: Số Lô \"{$lotNumberInput}\" không có tồn kho tại Vị trí đã chọn."
                                );
                                $lotId = null; // Không dùng lô này để validate serial bên dưới nữa.
                            }
                        }
                    }
                }

                if ($tracking === 2) {
                    if ($serialNumbers->isEmpty()) {
                        $validator->errors()->add(
                            "lines.{$i}.serial_numbers",
                            "Dòng {$line}: Hàng quản lý theo Lô + Sê-ri — vui lòng nhập Mã Serial (cách nhau bằng dấu cách)."
                        );
                    } else {
                        $actualQty = $row['actual_qty'] ?? null;
                        $serialCount = $serialNumbers->count();

                        if ($actualQty === null || (int) $actualQty !== $serialCount) {
                            $validator->errors()->add(
                                "lines.{$i}.actual_qty",
                                "Dòng {$line}: Thực xuất ({$actualQty}) phải bằng số Sê-ri đã nhập ({$serialCount})."
                            );
                        }

                        $dupInRow = $serialNumbers->duplicates();
                        if ($dupInRow->isNotEmpty()) {
                            $validator->errors()->add(
                                "lines.{$i}.serial_numbers",
                                "Dòng {$line}: Mã Serial \"{$dupInRow->first()}\" bị lặp lại trong cùng dòng."
                            );
                        }

                        // Serial phải ĐÃ TỒN TẠI trong hệ thống để xuất được.
                        $existingSerials = Serial::whereIn('serial_number', $serialNumbers->all())
                            ->get(['id', 'serial_number', 'lot_id']);

                        $missing = $serialNumbers->diff($existingSerials->pluck('serial_number'));
                        foreach ($missing as $missingSerial) {
                            $validator->errors()->add(
                                "lines.{$i}.serial_numbers",
                                "Dòng {$line}: Số Serial \"{$missingSerial}\" không tồn tại trong hệ thống."
                            );
                        }

                        // ── Serial phải thuộc ĐÚNG Lô đã chọn (Lô -> Sê-ri) và
                        //    đúng Vị trí đã chọn — khớp với luồng UI mới.
                        //    Nếu thiếu location_id/lot_id (nghĩa là Lô chưa được
                        //    xác nhận hợp lệ ở bước trên), KHÔNG bỏ qua âm thầm:
                        //    báo lỗi rõ ràng để không vô tình chấp nhận Sê-ri mà
                        //    chưa xác minh được có đúng Lô/Vị trí hay không. ──
                        if (! $locationId) {
                            $validator->errors()->add(
                                "lines.{$i}.serial_numbers",
                                "Dòng {$line}: Cần chọn Vị trí trước khi chọn Sê-ri."
                            );
                        } elseif (! $lotId) {
                            $validator->errors()->add(
                                "lines.{$i}.serial_numbers",
                                "Dòng {$line}: Cần chọn Lô hợp lệ trước khi chọn Sê-ri."
                            );
                        } else {
                            $stocks = $stocksFor((int) $row['product_id']);

                            foreach ($existingSerials as $serialModel) {
                                if ((int) $serialModel->lot_id !== (int) $lotId) {
                                    $validator->errors()->add(
                                        "lines.{$i}.serial_numbers",
                                        "Dòng {$line}: Số Serial \"{$serialModel->serial_number}\" không thuộc Lô \"{$lotNumberInput}\" đã chọn."
                                    );
                                    continue;
                                }

                                $serialAtLocation = $stocks->contains(fn ($s) => (int) $s->serial_id === (int) $serialModel->id
                                    && (int) $s->current_location_id === (int) $locationId);

                                if (! $serialAtLocation) {
                                    $validator->errors()->add(
                                        "lines.{$i}.serial_numbers",
                                        "Dòng {$line}: Số Serial \"{$serialModel->serial_number}\" không có tồn kho tại Vị trí đã chọn."
                                    );
                                }
                            }
                        }
                    }
                }

                // ── Serial trùng với các DÒNG KHÁC trong phiếu (cùng product) ──
                foreach ($serialNumbers as $serialValue) {
                    $key = $row['product_id'] . '|' . $serialValue;
                    if (isset($serialSeen[$key])) {
                        $validator->errors()->add(
                            "lines.{$i}.serial_numbers",
                            "Dòng {$line}: Số Serial \"{$serialValue}\" đã xuất ở {$serialSeen[$key]} (cùng sản phẩm)."
                        );
                    } else {
                        $serialSeen[$key] = "Dòng {$line}";
                    }
                }

                // ── Lô đã chọn (tại Vị trí đã chọn) PHẢI đủ tồn khả dụng cho
                //    số lượng Xuất — validate theo LÔ, không còn theo tổng cả
                //    Vị trí, vì giữ chỗ (reserve()) giờ luôn cần location_id +
                //    lot_id (xem StockIssueService::reserveLines()). Chỉ chạy
                //    khi đã xác định được $lotId hợp lệ ở bước trên (nếu Lô
                //    không hợp lệ, lỗi đã được báo ở đó rồi, không cần thêm
                //    lỗi trùng lặp ở đây).
                // Với hàng theo Lô+Sê-ri: available_qty được coi là số serial
                // ĐANG CÒN TỒN thực sự nhập (đã validate ở trên rồi), nên bỏ
                // qua bước cộng dồn ở đây — chỉ áp dụng cho hàng KHÔNG serial
                // (tracking = Lô, actual_qty tự do) vì đây là trường hợp người
                // dùng có thể nhập expected_qty vượt quá tồn của chính Lô đó.
                if ($tracking !== 2 && $locationId && $lotId && $expectedQty > 0) {
                    $stocks = $stocksFor((int) $row['product_id']);

                    $stocksAtLotLocation = $stocks->filter(
                        fn ($s) => (int) $s->current_location_id === (int) $locationId
                            && (int) $s->lot_id === (int) $lotId
                    );

                    $availableAtLotLocation = (float) $stocksAtLotLocation->sum(
                        fn ($s) => (float) $s->quantity - (float) $s->reserved_qty
                    );

                    // Cộng lại lượng chính phiếu này đang giữ chỗ (khi sửa phiếu),
                    // để không tự chặn chính mình.
                    if ($issue) {
                        foreach ($stocksAtLotLocation as $s) {
                            $key = $row['product_id'] . '|' . $s->current_location_id . '|'
                                . $s->lot_id . '|' . ($s->serial_id ?? '0');
                            $availableAtLotLocation += $ownReservedByKey[$key] ?? 0;
                        }
                    }

                    if ($availableAtLotLocation < $expectedQty) {
                        $validator->errors()->add(
                            "lines.{$i}.expected_qty",
                            "Dòng {$line}: Lô \"{$lotNumberInput}\" tại Vị trí đã chọn chỉ còn "
                            . "{$availableAtLotLocation} khả dụng, không đủ để giữ chỗ {$expectedQty}."
                        );
                    }
                }
            }
        });
    }
}