// vite.config.js — Laravel + Tailwind CSS v4 (plugin oficial do Vite, sem PostCSS/autoprefixer)
// npm i -D tailwindcss @tailwindcss/vite
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
