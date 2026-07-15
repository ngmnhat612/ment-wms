import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/breadcrumb.css',
                'resources/js/app.js',
                'resources/js/crud-modal-helpers.js',
                'resources/js/number-input-guard.js',
                'resources/js/product/product-index.js'
            ],
            refresh: true,
        }),
    ],
});
