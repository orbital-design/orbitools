const mix = require('laravel-mix');
require('laravel-mix-glob');
const fs = require('fs');
const path = require('path');

// Configure Mix for WordPress theme development
mix.setPublicPath('./');

// Dynamic SCSS compilation - finds all .scss files that don't start with _
function findScssFiles(dir, baseDir = dir, outputDir = 'css') {
    const files = [];

    if (!fs.existsSync(dir)) {
        return files;
    }

    const items = fs.readdirSync(dir, { withFileTypes: true });

    for (const item of items) {
        const fullPath = path.join(dir, item.name);

        if (item.isDirectory()) {
            // Recursively scan subdirectories
            files.push(...findScssFiles(fullPath, baseDir, outputDir));
        } else if (item.isFile() && item.name.endsWith('.scss') && !item.name.startsWith('_')) {
            // Calculate relative path from base directory
            const relativePath = path.relative(baseDir, fullPath);
            const relativeDir = path.dirname(relativePath);
            const fileName = path.basename(relativePath, '.scss');

            files.push({
                inputPath: path.relative(__dirname, fullPath),
                outputPath: path.join(outputDir, relativeDir, `${fileName}.css`).replace(/\\/g, '/')
            });
        }
    }

    return files;
}

// Find and compile all admin SCSS files (src/admin/scss -> admin/css)
const adminScssDir = path.join(__dirname, 'src/admin/scss');
const adminScssFiles = findScssFiles(adminScssDir, adminScssDir, 'admin/css');

// Find and compile all frontend SCSS files (src/frontend/scss -> assets/css)
const frontendScssDir = path.join(__dirname, 'src/frontend/scss');
const frontendScssFiles = findScssFiles(frontendScssDir, frontendScssDir, 'assets/css');

// Combine both arrays
const scssFiles = [...adminScssFiles, ...frontendScssFiles];

const sassOptions = {
    implementation: require('sass'),
    api: 'modern',
    sassOptions: {
        quietDeps: true,
        silenceDeprecations: ['import'],
        sourceMap: !mix.inProduction()
    }
};

// Compile each found SCSS file
scssFiles.forEach((file, index) => {
    if (index === 0) {
        mix.sass(file.inputPath, file.outputPath, sassOptions);
    } else {
        mix.sass(file.inputPath, file.outputPath, sassOptions);
    }
});

// PostCSS options (same as your gulp setup)
mix.options({
    processCssUrls: false, // Important for WordPress - prevents URL rewriting
    postCss: [
        require('autoprefixer')
        // Note: postcss-preset-env removed to eliminate TypeScript warnings
        // Autoprefixer handles most needed transformations for WordPress
    ],
    manifest: false,
    // Enable CSS source maps always
    cssSourceMap: true,
    // Disable minification for now
    cssMinification: false
});
// Watch additional paths (equivalent to your gulp watch task)
mix.when(!mix.inProduction(), () => {
    mix.options({
        hmrOptions: {
            host: 'localhost',
            port: 8080
        }
    });
});

// Disable Mix success notifications (optional)
mix.disableNotifications();

// Override webpack config for cleaner output
mix.webpackConfig({
    stats: {
        children: false,
        warnings: false,
        modules: false,
        chunks: false,
        chunkModules: false
    }
});
