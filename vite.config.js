import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import fs from "fs";

const host = "lidiag.test";

export default defineConfig({
    define: {
        // Vue esm-bundler compile-time feature flags (mejor tree-shaking / elimina warning)
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },
    server: {
        host,
        hmr: { host },
        https: {
            key: fs.readFileSync("C:/laragon/etc/ssl/laragon.key"),
            cert: fs.readFileSync("C:/laragon/etc/ssl/laragon.crt"),
        },
    },
    plugins: [
        laravel({
            input: ["resources/sass/app.scss", "resources/js/app.js"],
            refresh: true,
        }),
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => ["md-linedivider"].includes(tag),
                },
            },
        }),
    ],
    optimizeDeps: {
        include: ["@ffmpeg/ffmpeg"],
    },
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
});
