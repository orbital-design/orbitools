const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

// Filter out the default CopyWebpackPlugin to avoid duplicates
const filteredPlugins = defaultConfig.plugins.filter(
    plugin => !(plugin instanceof CopyWebpackPlugin)
);

// Standard WordPress block entries
const blockEntries = {
    'blocks/collection/index': path.resolve(process.cwd(), 'src', 'blocks', 'collection', 'index.tsx'),
    'blocks/collection/editor': path.resolve(process.cwd(), 'src', 'blocks', 'collection', 'editor.scss'),
    'blocks/entry/index': path.resolve(process.cwd(), 'src', 'blocks', 'entry', 'index.tsx'),
    'blocks/entry/editor': path.resolve(process.cwd(), 'src', 'blocks', 'entry', 'editor.scss'),
    'blocks/query-loop/index': path.resolve(process.cwd(), 'src', 'blocks', 'query-loop', 'index.tsx'),
    'blocks/query-loop/editor': path.resolve(process.cwd(), 'src', 'blocks', 'query-loop', 'editor.scss'),
    'blocks/spacer/index': path.resolve(process.cwd(), 'src', 'blocks', 'spacer', 'index.tsx'),
    'blocks/spacer/editor': path.resolve(process.cwd(), 'src', 'blocks', 'spacer', 'editor.scss'),
};

module.exports = {
    ...defaultConfig,
    entry: blockEntries,
    plugins: [
        ...filteredPlugins,
        new CopyWebpackPlugin({
            patterns: [
                {
                    from: 'src/blocks/collection/block.json',
                    to: 'blocks/collection/block.json',
                },
                {
                    from: 'src/blocks/entry/block.json',
                    to: 'blocks/entry/block.json',
                },
                {
                    from: 'src/blocks/query-loop/block.json',
                    to: 'blocks/query-loop/block.json',
                },
                {
                    from: 'src/blocks/spacer/block.json',
                    to: 'blocks/spacer/block.json',
                },
            ],
        }),
    ],
};
