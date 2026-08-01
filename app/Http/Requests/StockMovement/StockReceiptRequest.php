<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;
use App\Models\Inventory\Serial;
use App\Repositories\Contracts\Inventory\SerialRepositoryInterface;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;

class StockReceiptRequest extends FormRequest
{
    public function __construct(
        private SerialRepositoryInterface $serialRepository,
        private ProductRepositoryInterface $productRepository,
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true; // Gate::authorize() đã xử lý ở Controller, không lặp lại ở đây.
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
                $line['actual_qty'] = is_numeric($line['expected_qty'] ?? null) ? $line['expected_qty'] : 0;
            }

            return $line;
        })->all();

        $this->merge(['lines' => $lines]);
    }

    public function rules(): array
    {
        $isUpdate = $this->route('receipt') !== null;

        return [
            'warehouse_id'        => 'required|exists:warehouses,id',
            'stock_in_request_id' => 'nullable|exists:stock_in_request,id',
            'code' => $isUpdate
                ? [
                    'nullable', 'string', 'max:50',
                    Rule::unique('stock_receipt', 'code')->ignore($this->route('receipt')),
                ]
                : 'nullable|string|max:50|unique:stock_receipt,code',
            'receipt_date'        => 'required|date',
            'note'                => 'nullable|string|max:500',

            // ── Mỗi line = 1 hàng UI (FLAT) ──
            // Không còn mảng details[] con nhập tay: Sê-ri giờ là 1 chuỗi
            // text (mỗi mã cách nhau <space>), Service sẽ tự tách thành
            // nhiều dòng detail. actual_qty vẫn nhập tay và phải khớp số
            // serial đếm được (validate ở withValidator() bên dưới).
            'lines'                    => 'required|array|min:1',
            'lines.*.product_id'       => 'required|exists:products,id',
            'lines.*.uom_id'           => 'required|exists:uoms,id',
            'lines.*.expected_qty'     => 'required|numeric|min:0.001',
            'lines.*.actual_qty'       => 'required|numeric|min:0',
            'lines.*.location_id'      => 'required|exists:locations,id',
            'lines.*.receiver_id'      => 'required|exists:employees,id',
            'lines.*.sn_id'            => 'nullable|exists:sns,id',
            'lines.*.supplier_id'      => 'nullable|exists:suppliers,id',
            'lines.*.brand_id'         => 'nullable|exists:brands,id',
            'lines.*.sub_warehouse'    => 'nullable|string|max:50',
            'lines.*.reference_no'     => 'nullable|string|max:100',
            'lines.*.note'             => 'nullable|string|max:500',
            'lines.*.lot_number'       => 'nullable|string|max:50',
            'lines.*.old_lot_id'       => 'nullable|integer',
            'lines.*.serial_numbers'   => 'nullable|string|max:5000',
            'lines.*.expiry_date'      => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required'         => 'Vui lòng chọn kho nhập.',
            'code.unique'                    => 'Mã phiếu đã tồn tại.',
            'receipt_date.required'          => 'Vui lòng chọn ngày nhập.',
            'lines.required'                 => 'Phiếu nhập phải có ít nhất một vật tư.',
            'lines.*.product_id.required'    => 'Vui lòng chọn vật tư.',
            'lines.*.uom_id.required'        => 'Vui lòng chọn đơn vị tính.',
            'lines.*.expected_qty.required'  => 'Vui lòng nhập số lượng dự kiến.',
            'lines.*.expected_qty.min'       => 'Số lượng phải lớn hơn 0.',
            'lines.*.location_id.required'   => 'Vui lòng chọn vị trí lưu kho.',
            'lines.*.receiver_id.required'   => 'Vui lòng chọn người nhận.',
        ];
    }

    /**
     * Validate nghiệp vụ không thể diễn đạt bằng rule tĩnh:
     *  - bắt buộc serial_numbers theo tracking_type của từng product
     *  - actual_qty PHẢI khớp đúng số serial đếm được trong chuỗi
     *    (khi tracking = Lô+Sê-ri) — theo yêu cầu nghiệp vụ mới nhất
     *  - chặn serial trùng trong cùng phiếu (kể cả trùng trong cùng 1 dòng)
     *  - chặn lot_number trùng NGAY TRONG CÙNG PHIẾU (không còn chặn trùng
     *    với Lô đã tồn tại trong hệ thống — cho phép nhập bổ sung vào Lô cũ)
     * Đây là validate INPUT (dữ liệu người dùng nhập) — khác với việc
     * resolve lot_id/serial_id thật, vốn thuộc về StockReceiptService.
     *
     * TrackingType hiện chỉ có 2 giá trị:
     *   1 = Lot            (Theo lô)            -> Serial không dùng
     *   2 = LotAndSerial   (Theo lô + sê-ri)     -> Serial bắt buộc
     * => Lô KHÔNG bắt buộc nhập: nếu để trống, StockReceiptService sẽ tự
     *    động sinh mã Lô (LO1, LO2, ...) qua CodeGeneratorService.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $lines = $this->input('lines', []);
            $products = $this->productRepository->findManyByIds(
                collect($lines)->pluck('product_id')->filter()->all()
            );

            $serialSeen = [];    // ['product_id|serial_value' => 'Dòng N']
            $lotNumberSeen = []; // ["{product_id}:{lotNumberInt}" => ['line' => n]]

            $currentReceipt = $this->route('receipt');
            $excludedLotIds = $currentReceipt
                ? $currentReceipt->details()->whereNotNull('lot_id')->pluck('lot_id')->all()
                : [];

            foreach ($lines as $i => $row) {
                if (empty($row['product_id'])) continue;

                $product  = $products->get($row['product_id']);
                $tracking = (int) ($product?->tracking_type?->value ?? 1);
                $line     = $i + 1;

                // ── old_lot_id (nếu có) PHẢI thuộc chính phiếu đang sửa ──
                // Đây là "chỗ dựa" để Service tái sử dụng lại đúng số Lô cũ
                // khi người dùng để trống ô Lô — không được tin mù giá trị
                // client gửi lên, vì có thể bị giả mạo để "cướp" số Lô của
                // phiếu/dòng khác không thuộc quyền của dòng này.
                $oldLotId = $row['old_lot_id'] ?? null;
                if ($oldLotId && ! in_array((int) $oldLotId, $excludedLotIds, true)) {
                    $validator->errors()->add(
                        "lines.{$i}.old_lot_id",
                        "Dòng {$line}: Dữ liệu Lô cũ không hợp lệ."
                    );
                }

                $serialNumbers = collect(preg_split('/\s+/', trim((string) ($row['serial_numbers'] ?? ''))))
                    ->filter(fn ($s) => $s !== '')
                    ->values();

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
                                "Dòng {$line}: Thực nhập ({$actualQty}) phải bằng số Sê-ri đã nhập ({$serialCount})."
                            );
                        }

                        $dupInRow = $serialNumbers->duplicates();
                        if ($dupInRow->isNotEmpty()) {
                            $validator->errors()->add(
                                "lines.{$i}.serial_numbers",
                                "Dòng {$line}: Mã Serial \"{$dupInRow->first()}\" bị lặp lại trong cùng dòng."
                            );
                        }
                    }
                }

                // ── Serial trùng với các DÒNG KHÁC trong phiếu (cùng product) ── (CHỈ 1 LẦN)
                foreach ($serialNumbers as $serialValue) {
                    $key = $row['product_id'] . '|' . $serialValue;
                    if (isset($serialSeen[$key])) {
                        $validator->errors()->add(
                            "lines.{$i}.serial_numbers",
                            "Dòng {$line}: Số Serial \"{$serialValue}\" đã nhập ở {$serialSeen[$key]} (cùng sản phẩm)."
                        );
                    } else {
                        $serialSeen[$key] = "Dòng {$line}";
                    }
                }

                // ── Serial trùng với Serial ĐÃ TỒN TẠI trong hệ thống (bảng serials) ──
                // CHỈ trong phạm vi CÙNG sản phẩm — 2 sản phẩm khác nhau được
                // phép trùng số Sê-ri (unique constraint là (product_id, serial_number)).
                // Trừ các serial đang gán cho chính phiếu này (khi update).
                if ($serialNumbers->isNotEmpty()) {
                    $existingSerials = $this->serialRepository
                        ->findExistingSerialNumbers((int) $row['product_id'], $serialNumbers->all(), $excludedLotIds);

                    foreach ($existingSerials as $existingSerial) {
                        $validator->errors()->add(
                            "lines.{$i}.serial_numbers",
                            "Dòng {$line}: Số Serial \"{$existingSerial}\" đã tồn tại trong hệ thống."
                        );
                    }
                }

                // ── Kiểm tra trùng Số Lô khi người dùng tự nhập tay ──
                $lotNumberInput = trim((string) ($row['lot_number'] ?? ''));

                if ($lotNumberInput !== '') {
                    if (! ctype_digit($lotNumberInput)) {
                        $validator->errors()->add(
                            "lines.{$i}.lot_number",
                            "Dòng {$line}: Số Lô phải là số nguyên dương."
                        );
                        continue;
                    }

                    $lotNumberInt = (int) $lotNumberInput;
                    $productId    = (int) $row['product_id'];
                    $lotSeenKey   = $productId . ':' . $lotNumberInt;

                    if (isset($lotNumberSeen[$lotSeenKey])) {
                        $prevRow = $lotNumberSeen[$lotSeenKey];
                        $validator->errors()->add(
                            "lines.{$i}.lot_number",
                            "Dòng {$line}: Số Lô \"{$lotNumberInt}\" đã dùng ở dòng {$prevRow['line']} (cùng vật tư)."
                        );
                    } else {
                        $lotNumberSeen[$lotSeenKey] = ['line' => $line];
                    }

                    // ── Cho phép nhập thêm vào Lô đã tồn tại (kể cả đã có tồn kho) ──
                    // Ràng buộc "1 Lô chỉ thuộc 1 Vật tư" đã đảm bảo ở tầng khác:
                    //   - DB: unique(product_id, lot_code) ở bảng lots
                    //   - Service: LotRepository::firstOrCreate() tìm theo đúng
                    //     (product_id, lot_code), tự trả về Lô cũ khi đã có.
                }
            }
        });
    }
}