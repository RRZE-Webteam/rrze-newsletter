const autoprefixer = require("autoprefixer");
const MiniCSSExtractPlugin = require("mini-css-extract-plugin");
const CSSMinimizerPlugin = require("css-minimizer-webpack-plugin");
const TerserPlugin = require("terser-webpack-plugin");

const path = require("path");
const admin = path.join(__dirname, "src", "admin");
const editor = path.join(__dirname, "src", "editor");
const subscription = path.join(__dirname, "src", "subscription");

module.exports = (env, argv) => {
    function isDevelopment() {
        return argv.mode === "development";
    }
    var config = {
        entry: {
            admin,
            editor,
            subscription
        },
        output: {
            filename: "[name].js",
            clean: true
        },
        optimization: {
            minimizer: [
                new CSSMinimizerPlugin(),
                new TerserPlugin({ terserOptions: { sourceMap: true } })
            ]
        },
        plugins: [
            new MiniCSSExtractPlugin({
                chunkFilename: "[id].css",
                filename: chunkData => {
                    return "[name].css";
                }
            })
        ],
        devtool: isDevelopment() ? "cheap-module-source-map" : "source-map",
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: [
                        {
                            loader: "babel-loader",
                            options: {
                                plugins: [
                                    "@babel/plugin-proposal-class-properties"
                                ],
                                presets: [
                                    "@babel/preset-env",
                                    [
                                        "@babel/preset-react",
                                        {
                                            pragma: "wp.element.createElement",
                                            pragmaFrag: "wp.element.Fragment",
                                            development: isDevelopment()
                                        }
                                    ]
                                ]
                            }
                        }
                    ]
                },
                {
                    test: /\.(sa|sc|c)ss$/,
                    use: [
                        MiniCSSExtractPlugin.loader,
                        "css-loader",
                        {
                            loader: "postcss-loader",
                            options: {
                                postcssOptions: {
                                    plugins: [autoprefixer()]
                                }
                            }
                        },
                        "sass-loader"
                    ]
                }
            ]
        },
        externals: {
            jquery: "jQuery",
            lodash: "lodash",
            react: "React",
            "react-dom": "ReactDOM",
            "@wordpress/api-fetch": ["wp", "apiFetch"],
            "@wordpress/data": ["wp", "data"],
            "@wordpress/dom-ready": ["wp", "domReady"],
            "@wordpress/hooks": ["wp", "hooks"],
            "@wordpress/keycodes": ["wp", "keycodes"],
            "@wordpress/blocks": ["wp", "blocks"],
            "@wordpress/i18n": ["wp", "i18n"],
            "@wordpress/editor": ["wp", "editor"],
            "@wordpress/components": ["wp", "components"],
            "@wordpress/element": ["wp", "element"],
            "@wordpress/blob": ["wp", "blob"],
            "@wordpress/html-entities": ["wp", "htmlEntities"],
            "@wordpress/compose": ["wp", "compose"],
            "@wordpress/plugins": ["wp", "plugins"],
            "@wordpress/edit-post": ["wp", "editPost"],
            "@wordpress/block-editor": ["wp", "blockEditor"]
        }
    };
    return config;
};
