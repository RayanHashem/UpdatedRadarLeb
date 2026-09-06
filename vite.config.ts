import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import tailwindcss from "@tailwindcss/vite";
import { resolve } from 'node:path';
import { createLogger, defineConfig } from 'vite';

/*
 * Vite's CSS pipeline logs a noisy warning like
 *   "<path> referenced in <path> didn't resolve at build time, it will
 *    remain unchanged to be resolved at runtime"
 * for every absolute `url(/assets/...)` in our CSS. Those URLs are served
 * directly out of Laravel's `public/` folder, so the behaviour is correct
 * — the warning is purely noise, not a real problem. createLogger lets us
 * drop exactly those messages without hiding genuine Vite warnings.
 */
const logger = createLogger();
const originalWarn = logger.warn.bind(logger);
const originalWarnOnce = logger.warnOnce.bind(logger);
const shouldSilence = (msg: string) =>
    msg.includes("didn't resolve at build time") &&
    (msg.includes('/assets/imgs/') ||
        msg.includes('/assets/videos/') ||
        msg.includes('/assets/sounds/'));
logger.warn = (msg, options) => {
    if (typeof msg === 'string' && shouldSilence(msg)) return;
    originalWarn(msg, options);
};
logger.warnOnce = (msg, options) => {
    if (typeof msg === 'string' && shouldSilence(msg)) return;
    originalWarnOnce(msg, options);
};

export default defineConfig({
    customLogger: logger,
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    /*
     * Silence Vite's "didn't resolve at build time, it will remain unchanged
     * to be resolved at runtime" warnings for asset URLs that are already
     * served out of Laravel's `public/` folder (e.g. /assets/imgs/game-bg.jpg,
     * /assets/imgs/loading-bg.png, /assets/imgs/sign-in-bg.jpg). Those paths
     * are intentionally absolute — they're served by the web server, not
     * bundled by Vite — so the warning is noise, not an error. Rollup's
     * `onwarn` lets us drop exactly those messages without hiding real
     * problems (missing modules, circular deps, etc.).
     */
    build: {
        rollupOptions: {
            onwarn(warning, defaultHandler) {
                const msg = typeof warning === 'string' ? warning : warning.message ?? '';
                if (
                    msg.includes("didn't resolve at build time") &&
                    (msg.includes('/assets/imgs/') || msg.includes('/assets/videos/') || msg.includes('/assets/sounds/'))
                ) {
                    return;
                }
                defaultHandler(warning);
            },
        },
    },
});
