import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 5 still ships deprecated Sass @import/global functions.
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function', 'mixed-decls'],
            },
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('bootstrap') || id.includes('@popperjs')) {
                            return 'vendor-bootstrap';
                        }
                        if (id.includes('sweetalert2')) {
                            return 'vendor-sweetalert';
                        }
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-vue';
                        }
                    }
                },
            },
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
