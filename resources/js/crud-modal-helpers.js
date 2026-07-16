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

// Dùng cho ô "Tên đăng nhập" (tài khoản đăng nhập của nhân viên).
// Chuyển thường, chỉ giữ a-z, 0-9 và 3 ký tự @ _ . (khớp rule
// 'regex:/^[a-z0-9@_.]+$/' trong StoreAccountRequest), giữ nguyên vị trí con trỏ.
function sanitizeUsernameInput(el) {
    const pos = el.selectionStart;
    const cleaned = el.value.toLowerCase().replace(/[^a-z0-9@_.]/g, '');
    if (cleaned !== el.value) {
        el.value = cleaned;
        el.setSelectionRange(pos, pos);
    }
}

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

// Vite build các file này dưới dạng ES module (scope riêng), trong khi nhiều Blade view
// vẫn gọi các hàm này qua thuộc tính inline (vd: onclick="sanitizeCodeInput(this)",
// oninput="clearValidationErrors('categoryForm')"). Gán ra window để giữ tương thích
// ngược, tránh phải sửa lại toàn bộ các view đang dùng inline handler.
window.sanitizeCodeInput = sanitizeCodeInput;
window.sanitizeUsernameInput = sanitizeUsernameInput;
window.setModalFormId = setModalFormId;
window.clearValidationErrors = clearValidationErrors;
