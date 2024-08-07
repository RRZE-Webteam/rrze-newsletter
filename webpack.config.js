const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const webpack = require("webpack");
const { basename, dirname, resolve } = require("path");
const srcDir = "src";
const isProduction = defaultConfig.isProduction;

const admin = resolve(process.cwd(), "src", "admin");
const editor = resolve(process.cwd(), "src", "editor");
const subscription = resolve(process.cwd(), "src/subscription");
const subscriptionemail = resolve(process.cwd(), "src/subscriptionemail");

module.exports = {
    ...defaultConfig,
    entry: {
        admin,
        editor,
        subscription,
        subscriptionemail,
    },
    output: {
        path: resolve(process.cwd(), "build"),
        filename: "[name].js",
        clean: true,
    },
    optimization: {
        ...defaultConfig.optimization,
        splitChunks: {
            cacheGroups: {
                style: {
                    type: "css/mini-extract",
                    test: /[\\/]style(\.module)?\.(pc|sc|sa|c)ss$/,
                    chunks: "all",
                    enforce: true,
                    name(_, chunks, cacheGroupKey) {
                        const chunkName = chunks[0].name;
                        return `${dirname(chunkName)}/${basename(
                            chunkName
                        )}.${cacheGroupKey}`;
                    },
                },
                default: false,
            },
        },
    },
    plugins: [
        ...defaultConfig.plugins,
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
        }),
    ],
};
