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
    'blocks/query-loop/frontend': path.resolve(process.cwd(), 'src', 'blocks', 'query-loop', 'frontend.js'),
    'blocks/read-more/index': path.resolve(process.cwd(), 'src', 'blocks', 'read-more', 'index.tsx'),
    'blocks/read-more/editor': path.resolve(process.cwd(), 'src', 'blocks', 'read-more', 'editor.scss'),
    'blocks/read-more/frontend': path.resolve(process.cwd(), 'src', 'blocks', 'read-more', 'frontend.js'),
    'blocks/spacer/index': path.resolve(process.cwd(), 'src', 'blocks', 'spacer', 'index.tsx'),
    'blocks/spacer/editor': path.resolve(process.cwd(), 'src', 'blocks', 'spacer', 'editor.scss'),
    'blocks/marquee/index': path.resolve(process.cwd(), 'src', 'blocks', 'marquee', 'index.tsx'),
    'blocks/marquee/editor': path.resolve(process.cwd(), 'src', 'blocks', 'marquee', 'editor.scss'),
    'blocks/marquee/frontend': path.resolve(process.cwd(), 'src', 'blocks', 'marquee', 'frontend.js'),
    'blocks/group/index': path.resolve(process.cwd(), 'src', 'blocks', 'group', 'index.tsx'),
    'blocks/group/editor': path.resolve(process.cwd(), 'src', 'blocks', 'group', 'editor.scss'),
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
                    from: 'src/blocks/read-more/block.json',
                    to: 'blocks/read-more/block.json',
                },
                {
                    from: 'src/blocks/spacer/block.json',
                    to: 'blocks/spacer/block.json',
                },
                {
                    from: 'src/blocks/marquee/block.json',
                    to: 'blocks/marquee/block.json',
                },
                {
                    from: 'src/blocks/group/block.json',
                    to: 'blocks/group/block.json',
                },
            ],
        }),
    ],
};
