/** @type {import("eslint").Linter.Config} */
module.exports = {
    root: true,
    env: {
        browser: true,
        es2021: true,
        node: true,
    },
    globals: {
        // Usado ampliamente en el repo (bootstrap + jQuery plugins legacy)
        axios: "readonly",
        $: "readonly",
        jQuery: "readonly",
    },
    extends: ["eslint:recommended", "plugin:vue/vue3-recommended", "prettier"],
    parserOptions: {
        ecmaVersion: "latest",
        sourceType: "module",
    },
    rules: {
        // Aterrizado al repo (mucho código legacy)
        "vue/multi-word-component-names": "off",
        // Ruido alto en legacy: preferimos habilitar gradualmente
        "vue/attributes-order": "off",
        "vue/first-attribute-linebreak": "off",
        "vue/order-in-components": "off",
        "vue/require-prop-types": "off",
        "vue/require-explicit-emits": "off",
        "vue/no-deprecated-destroyed-lifecycle": "off",
        "no-unused-vars": [
            "warn",
            {
                argsIgnorePattern: "^_",
                varsIgnorePattern: "^_",
                caughtErrorsIgnorePattern: "^_",
            },
        ],
        // En este repo hay globals legacy (c3, swal, etc.) y módulos sin import explícito
        "no-undef": "warn",
        // Legacy: permitir pero dejar visible
        "no-empty": "warn",
        "no-dupe-keys": "warn",
        "no-async-promise-executor": "warn",

        // Vue plugin: no bloquear CI por legado
        "vue/no-dupe-keys": "warn",
        "vue/no-reserved-keys": "warn",
        "vue/return-in-computed-property": "warn",
    },
    ignorePatterns: ["public/build/**", "public/admin-wrap/**", "resources/js/admin-wrap/**"],
};

