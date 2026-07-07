/* UPDATE: ;Tạo file mới */
// Dùng cho các ô "Mã..." (Mã danh mục, Mã phòng ban, Mã MenT, v.v.)
// Chuyển hoa, chỉ giữ A-Z và 0-9, giữ nguyên vị trí con trỏ.
function sanitizeCodeInput(el) {
    const pos = el.selectionStart;
    const cleaned = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (cleaned !== el.value) {
        el.value = cleaned;
        el.setSelectionRange(pos, pos);
    }
}

// UPDATE
// Ghi id bản ghi đang sửa vào hidden input của form CRUD (modal).
// Dùng trong openModal() của các module category/department/sn/brand/location/
// supplier/uom/warehouse, để khi validate lỗi lúc Sửa, script reopen (dựa vào
// old('id')) biết đúng id và mở lại đúng chế độ "Chỉnh sửa" thay vì "Thêm mới".
function setModalFormId(hiddenFieldId, id) {
    const el = document.getElementById(hiddenFieldId);
    if (el) el.value = id || '';
}

// Xoá toàn bộ trạng thái lỗi validate cũ (class is-invalid) còn sót lại trong form,
// do class này được server render sẵn lúc load trang và không tự mất khi mở lại
// modal thuần JS (không reload trang). Gọi ở đầu openModal() của mỗi module.
function clearValidationErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}