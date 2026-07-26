import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5174',
        cors: {
            origin: 'http://localhost:8082',
        },
        hmr: {
            host: 'localhost',
            port: 5173,
            clientPort: 5174,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
