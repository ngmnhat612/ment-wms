function blockInvalidNumberKeys(event) {
    const invalidKeys = ['e', 'E', '+', '-', '.', ','];
    if (invalidKeys.includes(event.key)) event.preventDefault();
}

function blockInvalidNumberPaste(event) {
    const pasted = (event.clipboardData || window.clipboardData).getData('text');
    if (!/^\d+$/.test(pasted)) event.preventDefault();
}

/**
 * Dùng cho input số cần chặn TUYỆT ĐỐI ký tự chữ bằng JS (không chỉ dựa vào HTML5).
 * LƯU Ý: input type="number" có giới hạn của trình duyệt khiến JS không đọc được
 * giá trị "dirty" khi người dùng gõ chữ cái (el.value trả về rỗng thay vì chuỗi lỗi).
 * => Nếu cần chặn chữ cái chắc chắn 100%, PHẢI dùng type="text" + inputmode="numeric"
 *    + data-max="..." thay vì type="number" + max="...".
 */
function sanitizeNumberInput(el) {
    let cleaned = el.value.replace(/[^\d]/g, '');

    // Xoá số 0 thừa ở đầu (nhưng giữ lại 1 số 0 nếu người dùng chỉ gõ "0")
    cleaned = cleaned.replace(/^0+(?=\d)/, '');

    // Đọc giới hạn max từ thuộc tính max (type=number) HOẶC data-max (type=text)
    // để hàm này dùng chung được cho cả 2 kiểu input.
    const maxAttr = el.max !== '' ? el.max : el.dataset.max;
    if (maxAttr && cleaned !== '') {
        const maxVal = Number(maxAttr);
        if (Number(cleaned) > maxVal) {
            cleaned = String(maxVal);
        }
    }

    if (cleaned !== el.value) el.value = cleaned;
}

// Dùng cho các ô chỉ chấp nhận chữ số (SĐT, mã bưu điện...) - KHÔNG xoá số 0 ở đầu,
// vì các giá trị này (vd: 0901234567) số 0 đầu vẫn có ý nghĩa, khác với ô số lượng/đơn giá.
function sanitizeDigitsOnly(el) {
    const cleaned = el.value.replace(/[^\d]/g, '');
    if (cleaned !== el.value) el.value = cleaned;
}

// Vite build file này dưới dạng ES module (scope riêng), trong khi nhiều Blade view
// vẫn gọi các hàm qua thuộc tính inline (vd: onkeydown="blockInvalidNumberKeys(event)",
// onpaste="blockInvalidNumberPaste(event)", oninput="sanitizeNumberInput(this)").
// Gán ra window để giữ tương thích ngược, tránh phải sửa lại toàn bộ các view.
window.blockInvalidNumberKeys = blockInvalidNumberKeys;
window.blockInvalidNumberPaste = blockInvalidNumberPaste;
window.sanitizeNumberInput = sanitizeNumberInput;
window.sanitizeDigitsOnly = sanitizeDigitsOnly;
