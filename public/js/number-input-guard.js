function blockInvalidNumberKeys(event) {
    const invalidKeys = ['e', 'E', '+', '-', '.', ','];
    if (invalidKeys.includes(event.key)) event.preventDefault();
}

function blockInvalidNumberPaste(event) {
    const pasted = (event.clipboardData || window.clipboardData).getData('text');
    if (!/^\d+$/.test(pasted)) event.preventDefault();
}

function sanitizeNumberInput(el) {
    let cleaned = el.value.replace(/[^\d]/g, '');

    // Xoá số 0 thừa ở đầu (nhưng giữ lại 1 số 0 nếu người dùng chỉ gõ "0")
    cleaned = cleaned.replace(/^0+(?=\d)/, '');

    if (cleaned !== el.value) el.value = cleaned;
}