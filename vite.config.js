import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import fs from "fs";

const host = "localhost";

export default defineConfig({
    define: {
        // Vue esm-bundler compile-time feature flags (mejor tree-shaking / elimina warning)
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },
    server: {
        host,
        port: 5179,
        strictPort: true,
        hmr: { host },
        https: false,
        watch: {
            ignored: ['**/src-tauri/**']
        },
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
                secure: false,
                ws: true,
            }
        }
    },
    plugins: [
        laravel({
            input: ["resources/js/app.js"],
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
            "@": "/resources/js",
        },
    },
    build: {
        chunkSizeWarningLimit: 600,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes("node_modules")) {
                        if (id.includes("vue") || id.includes("axios") || id.includes("dayjs")) {
                            return "vendor";
                        }
                    }
                    if (id.includes("modulos/help-desk/")) return "helpdesk";
                    if (id.includes("modulos/ecografias/lecturaComponent")) return "ecografiasLectura";
                    if (id.includes("modulos/ecografias/consentimiento")) return "ecografiasConsentimientos";
                    if (id.includes("modulos/ecografias/")) return "ecografias";
                    if (id.includes("modulos/tele-radiologia/")) return "teleRadiologia";
                    if (id.includes("modulos/estudios/")) return "estudios";
                    if (id.includes("modulos/seguridad/")) return "seguridad";
                },
            },
        },
    },
});