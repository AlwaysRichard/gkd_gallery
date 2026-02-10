const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const glob = require('glob');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Find all block directories
const blocks = glob.sync('./blocks/*/', {
    ignore: ['**/build/**', '**/node_modules/**']
});

// Create entry points for each block
const entries = {};
blocks.forEach((blockDir) => {
    const blockName = path.basename(blockDir);

    // Editor entry (index.jsx)
    const indexFile = './' + path.join(blockDir, 'index.jsx');
    if (fs.existsSync(indexFile)) {
        entries[blockName] = indexFile;
    }

    // Frontend view entry (view.jsx)
    const viewFile = './' + path.join(blockDir, 'view.jsx');
    if (fs.existsSync(viewFile)) {
        entries[blockName + '-view'] = viewFile;
    }

    // Frontend style entry (style.scss)
    const styleFile = './' + path.join(blockDir, 'style.scss');
    if (fs.existsSync(styleFile)) {
        entries[blockName + '-style'] = styleFile;
    }
});

module.exports = {
    ...defaultConfig,
    entry: entries,
    output: {
        ...defaultConfig.output,
        path: path.resolve(process.cwd(), 'blocks'),
        filename: (pathData) => {
            const name = pathData.chunk.name;
            if (name.endsWith('-view')) {
                return name.replace('-view', '') + '/build/view.js';
            }
            if (name.endsWith('-style')) {
                return name.replace('-style', '') + '/build/style.js';
            }
            return name + '/build/index.js';
        },
        chunkFilename: '[name]/build/[id].js',
        // Don't clean the entire blocks directory!
        clean: false,
    },
    plugins: defaultConfig.plugins.filter(
        // Remove CleanWebpackPlugin to prevent deleting source files
        plugin => plugin.constructor.name !== 'CleanWebpackPlugin'
    ),
};
