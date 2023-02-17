import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    /* server: {
        host: '192.168.0.107',  // Add this to force IPv4 only
    }, */
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/appConfiguracion.js',
                'resources/js/appEstudios.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },

                compilerOptions: {
                    isCustomElement: (tag) => {
                        return tag.startsWith('ion-') // (return true)
                    }
                }

            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
