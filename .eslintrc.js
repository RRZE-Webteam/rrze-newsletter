module.exports = {
    env: {
        browser: true,
        es6: true,
        node: true
    },
    extends: ["eslint:recommended", "prettier", "plugin:react/recommended"],
    globals: {
        Atomics: "readonly",
        SharedArrayBuffer: "readonly",
        wp: "readonly"
    },
    parser: "@babel/eslint-parser",
    parserOptions: {
        requireConfigFile: false,
        ecmaFeatures: {
            jsx: true
        },
        ecmaVersion: 2018,
        sourceType: "module"
    },
    plugins: ["react"],
    rules: {
        "no-console": "off",
        "react/react-in-jsx-scope": "off",
        "react/display-name": "off",
        "react/prop-types": "off",
        "react/jsx-uses-react": "error",
        "react/jsx-uses-vars": "error"
    },
    settings: {
        react: {
            version: "detect"
        }
    }
};
