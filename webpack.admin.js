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
 * Single entry — the whole admin app is one bundle. The
 * DiscoverAdminExtensionsPlugin runs the filesystem scan before each
 * compile and watch tick, refreshing src/admin/.generated/discovered.ts
 * so static imports of newly-added modules resolve correctly.
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const { run: discover } = require('./scripts/discover-admin-extensions');

// @wordpress/scripts ships a CleanWebpackPlugin in its default
// plugin list that wipes the entire output dir regardless of
// `output.clean` on the webpack config. Filter it out so
// build:admin coexists with build:blocks / build:assets siblings
// under build/.
const filteredPlugins = (defaultConfig.plugins || []).filter(
    (plugin) => !(plugin instanceof CleanWebpackPlugin),
);

class DiscoverAdminExtensionsPlugin {
    apply(compiler) {
        const handler = (_compilation, callback) => {
            try {
                discover();
            } catch (err) {
                console.error('[discover-admin-extensions] failed:', err);
            }
            if (typeof callback === 'function') {
                callback();
            }
        };
        compiler.hooks.beforeRun.tapAsync('DiscoverAdminExtensionsPlugin', handler);
        compiler.hooks.watchRun.tapAsync('DiscoverAdminExtensionsPlugin', handler);
    }
}

// Run once at config-load time so the manifest exists before the
// first compile pass (otherwise the static import in App.tsx fails).
discover();

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
    plugins: [
        ...filteredPlugins,
        new DiscoverAdminExtensionsPlugin(),
    ],
};
