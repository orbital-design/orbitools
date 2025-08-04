const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    mode: 'production',
    entry: {
        'frontend/css/base': path.resolve(process.cwd(), 'src', 'frontend', 'scss', 'base.scss'),
        'admin/css/editor': path.resolve(process.cwd(), 'src', 'admin', 'scss', 'editor.scss'),
        'admin/css/admin': path.resolve(process.cwd(), 'src', 'admin', 'scss', 'admin.scss'),
    },
    output: {
        path: path.resolve(process.cwd(), 'build'),
        clean: false, // Don't clean - blocks might have already built
    },
    module: {
        rules: [
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'postcss-loader', 
                    'sass-loader',
                ],
            },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name].css',
        }),
        // Remove empty JS files
        {
            apply(compiler) {
                compiler.hooks.emit.tap('RemoveEmptyJSPlugin', (compilation) => {
                    Object.keys(compilation.assets).forEach(filename => {
                        if (filename.endsWith('.js') && compilation.assets[filename].size() === 0) {
                            delete compilation.assets[filename];
                        }
                    });
                });
            },
        },
    ],
    optimization: {
        splitChunks: false, // Don't split CSS-only chunks
    },
};