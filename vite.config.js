import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/home.css',
            ],
            refresh: true,
        }),
    ],
    build: {
        cssCodeSplit: true,
        sourcemap: false,
    },
});
