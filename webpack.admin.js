/**
 * Webpack config for the v3 React admin bundle.
 *
 * Built on @wordpress/scripts' default config so we inherit:
 *   - TSX/JSX/Babel pipeline
 *   - DependencyExtractionWebpackPlugin (@wordpress/* externalised
 *     to wp-* script handles; an .asset.php is produced listing deps
 *     and a content-hash version)
 *   - SCSS pipeline via MiniCssExtractPlugin
 *
 * Single entry — the whole admin app is one bundle. Webpack-driven
 * code splitting for per-module UI chunks lands in Phase 4 when the
 * filesystem discovery scan is introduced.
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'admin/index': path.resolve(process.cwd(), 'src', 'admin', 'index.tsx'),
    },
    output: {
        ...(defaultConfig.output || {}),
        path: path.resolve(process.cwd(), 'build'),
        filename: '[name].js',
        clean: false, // Coexist with blocks + assets bundles in build/
    },
    resolve: {
        ...(defaultConfig.resolve || {}),
        alias: {
            ...((defaultConfig.resolve && defaultConfig.resolve.alias) || {}),
            '@admin': path.resolve(process.cwd(), 'src', 'admin'),
        },
    },
};
