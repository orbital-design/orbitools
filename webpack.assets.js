const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
    mode: 'production',
    entry: {
        // SCSS entries for CSS compilation
        // - SCSS ~ Frontend ~ Loaded on the frontend
        'frontend/css/base': path.resolve(process.cwd(), 'src', 'frontend', 'scss', 'base.scss'),
        // - SCSS ~ Editor ~ Loaded in the block editor
        'admin/css/editor': path.resolve(process.cwd(), 'src', 'admin', 'scss', 'editor.scss'),
        // - SCSS ~ Admin ~ Loaded in the admin area
        'admin/css/admin': path.resolve(process.cwd(), 'src', 'admin', 'scss', 'admin.scss'),
        // - SCSS ~ Module ~ Layout Guides
        'frontend/css/modules/layout-guides/base': path.resolve(process.cwd(), 'src', 'modules', 'layout-guides','frontend', 'scss','base.scss'),
        'admin/css/modules/layout-guides/admin': path.resolve(process.cwd(), 'src', 'modules', 'layout-guides','admin', 'scss','admin.scss'),
        // - SCSS ~ Module ~ Menu Dividers
        'admin/css/modules/menu-dividers/admin': path.resolve(process.cwd(), 'src', 'modules', 'menu-dividers','admin', 'scss','admin.scss'),
        // - SCSS ~ Module ~ Menu Groups
        'admin/css/modules/menu-groups/admin': path.resolve(process.cwd(), 'src', 'modules', 'menu-groups','admin', 'scss','admin.scss'),
        // - SCSS ~ Controls ~ Typography Presets
        'admin/css/controls/typography-presets/admin': path.resolve(process.cwd(), 'src', 'controls', 'typography-presets','admin', 'scss','admin.scss'),

        // JS entries for JavaScript compilation
        // - JS ~ Admin ~ Loaded in the admin area
        'admin/js/admin': path.resolve(process.cwd(), 'src', 'admin', 'js', 'admin.js'),
        // - JS ~ Module ~ Layout Guides
        'frontend/js/modules/layout-guides/base': path.resolve(process.cwd(), 'src', 'modules', 'layout-guides','frontend', 'js','base.js'),
        // - JS ~ Module ~ Menu Dividers
        'admin/js/modules/menu-dividers/admin': path.resolve(process.cwd(), 'src', 'modules', 'menu-dividers','admin', 'js','admin.js'),
        'admin/js/modules/menu-dividers/processor': path.resolve(process.cwd(), 'src', 'modules', 'menu-dividers','admin', 'js','processor.js'),
        // - JS ~ Module ~ Menu Groups
        'admin/js/modules/menu-groups/admin': path.resolve(process.cwd(), 'src', 'modules', 'menu-groups','admin', 'js','admin.js'),
        'admin/js/modules/menu-groups/processor': path.resolve(process.cwd(), 'src', 'modules', 'menu-groups','admin', 'js','processor.js'),
        // - JS ~ Controls ~ Typography Presets
        'admin/js/controls/typography-presets/admin-handle-module-dashboard': path.resolve(process.cwd(), 'src', 'controls', 'typography-presets','admin', 'js','admin-handle-module-dashboard.js'),
        'admin/js/controls/typography-presets/editor-disable-core-typography-controls': path.resolve(process.cwd(), 'src', 'controls', 'typography-presets','admin', 'js','editor-disable-core-typography-controls.js'),
        'admin/js/controls/typography-presets/editor-presets-attribute-registration': path.resolve(process.cwd(), 'src', 'controls', 'typography-presets','admin', 'js','editor-presets-attribute-registration.js'),
        'admin/js/controls/typography-presets/editor-presets-classname-application': path.resolve(process.cwd(), 'src', 'controls', 'typography-presets','admin', 'js','editor-presets-classname-application.js'),
        'admin/js/controls/typography-presets/editor-presets-register-controls': path.resolve(process.cwd(), 'src', 'controls', 'typography-presets','admin', 'js','editor-presets-register-controls.js'),
        // - JS ~ Controls ~ Dimensions
        'admin/js/controls/dimensions/editor-dimensions-attribute-registration': path.resolve(process.cwd(), 'src', 'controls', 'dimensions','admin', 'js','editor-dimensions-attribute-registration.js'),
        'admin/js/controls/dimensions/editor-dimensions-classname-application': path.resolve(process.cwd(), 'src', 'controls', 'dimensions','admin', 'js','editor-dimensions-classname-application.js'),
        'admin/js/controls/dimensions/editor-dimensions-register-controls': path.resolve(process.cwd(), 'src', 'controls', 'dimensions','admin', 'js','editor-dimensions-register-controls.js'),

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
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            {
                test: /\.(png|jpe?g|gif|svg|webp|ico)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'media/[name][ext]'
                }
            },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name].css',
        }),
        new CopyWebpackPlugin({
            patterns: [
                {
                    from: path.resolve(process.cwd(), 'src', 'shared', 'images'),
                    to: path.resolve(process.cwd(), 'build', 'media'),
                    noErrorOnMissing: true, // Don't fail if the images directory doesn't exist
                },
            ],
        }),
        // Remove empty JS files generated from SCSS-only entries
        {
            apply(compiler) {
                compiler.hooks.emit.tap('RemoveEmptyJSPlugin', (compilation) => {
                    Object.keys(compilation.assets).forEach(filename => {
                        // Only remove JS files from CSS entries (those ending with .css in the entry name)
                        const isFromCssEntry = Object.keys(compilation.options.entry).some(entryName =>
                            entryName.includes('/css/') && filename.includes(entryName.replace(/.*\/css\//, ''))
                        );

                        if (filename.endsWith('.js') && compilation.assets[filename].size() === 0 && isFromCssEntry) {
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
