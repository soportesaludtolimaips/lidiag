const js = require('@eslint/js');
const pluginVue = require('eslint-plugin-vue');
const prettier = require('eslint-config-prettier');
const globals = require('globals');

module.exports = [
    {
        ignores: ['public/build/**', 'public/admin-wrap/**', 'resources/js/admin-wrap/**'],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    prettier,
    {
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2021,
                ...globals.node,
                axios: 'readonly',
                $: 'readonly',
                jQuery: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/attributes-order': 'off',
            'vue/first-attribute-linebreak': 'off',
            'vue/order-in-components': 'off',
            'vue/require-prop-types': 'off',
            'vue/require-explicit-emits': 'off',
            'vue/no-deprecated-destroyed-lifecycle': 'off',
            'no-unused-vars': [
                'warn',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                    caughtErrorsIgnorePattern: '^_',
                },
            ],
            'no-undef': 'warn',
            'no-empty': 'warn',
            'no-dupe-keys': 'warn',
            'no-async-promise-executor': 'warn',
            'vue/no-dupe-keys': 'warn',
            'vue/no-reserved-keys': 'warn',
            'vue/return-in-computed-property': 'warn',
        },
    },
];
