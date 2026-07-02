function blockInvalidNumberKeys(event) {
    const invalidKeys = ['e', 'E', '+', '-', '.', ','];
    if (invalidKeys.includes(event.key)) event.preventDefault();
}

function blockInvalidNumberPaste(event) {
    const pasted = (event.clipboardData || window.clipboardData).getData('text');
    if (!/^\d+$/.test(pasted)) event.preventDefault();
}

function sanitizeNumberInput(el) {
    const cleaned = el.value.replace(/[^\d]/g, '');
    if (cleaned !== el.value) el.value = cleaned;
}