#!/bin/bash
#
# Build script for Orbitools WordPress plugin
# Creates a production-ready zip file for deployment
#
# Usage: ./scripts/build-release.sh
#

set -e

# Configuration
PLUGIN_SLUG="orbitools"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
BUILD_DIR="$PLUGIN_DIR/dist"
RELEASE_DIR="$BUILD_DIR/$PLUGIN_SLUG"

# Get version from plugin header
VERSION=$(grep -o "Version:.*" "$PLUGIN_DIR/orbitools.php" | head -1 | sed 's/Version:[[:space:]]*//')
if [ -z "$VERSION" ]; then
    VERSION="1.0.0"
fi

ZIP_FILE="$BUILD_DIR/$PLUGIN_SLUG-$VERSION.zip"

echo "=========================================="
echo "Building $PLUGIN_SLUG v$VERSION"
echo "=========================================="

# Clean up previous builds
echo "Cleaning previous builds..."
rm -rf "$BUILD_DIR"
mkdir -p "$RELEASE_DIR"

# Install dependencies and build
echo "Installing dependencies..."
cd "$PLUGIN_DIR"
npm ci --silent 2>/dev/null || npm install --silent

echo "Running production build..."
npm run build

echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --quiet 2>/dev/null || true

# ==========================================
# Copy plugin files to release directory
# ==========================================

echo "Copying files..."

# Root PHP files
cp "$PLUGIN_DIR/orbitools.php" "$RELEASE_DIR/"
cp "$PLUGIN_DIR/uninstall.php" "$RELEASE_DIR/"

# PHP includes
cp -r "$PLUGIN_DIR/inc" "$RELEASE_DIR/inc"

# Config files
cp -r "$PLUGIN_DIR/config" "$RELEASE_DIR/config"

# Compiled build output (blocks + assets)
mkdir -p "$RELEASE_DIR/build"
cp -r "$PLUGIN_DIR/build/blocks" "$RELEASE_DIR/build/blocks"

# Copy asset build output if it exists (admin/frontend CSS/JS)
for asset_dir in admin frontend media; do
    if [ -d "$PLUGIN_DIR/build/$asset_dir" ]; then
        cp -r "$PLUGIN_DIR/build/$asset_dir" "$RELEASE_DIR/build/$asset_dir"
    fi
done

# Remove source maps and license files from build
find "$RELEASE_DIR/build" -name "*.map" -delete
find "$RELEASE_DIR/build" -name "*.LICENSE.txt" -delete

# Composer vendor directory (production dependencies only)
if [ -d "$PLUGIN_DIR/vendor" ]; then
    cp -r "$PLUGIN_DIR/vendor" "$RELEASE_DIR/vendor"
fi

# ==========================================
# Clean up dev-only files
# ==========================================

# Remove .DS_Store files
find "$RELEASE_DIR" -name ".DS_Store" -delete

# Remove CLAUDE.md (dev instructions)
rm -f "$RELEASE_DIR/CLAUDE.md"

# ==========================================
# Create the zip file
# ==========================================

echo "Creating zip archive..."
cd "$BUILD_DIR"
zip -r -q "$ZIP_FILE" "$PLUGIN_SLUG"

# Clean up the unzipped release directory
rm -rf "$RELEASE_DIR"

# Restore dev Composer dependencies
echo "Restoring dev dependencies..."
cd "$PLUGIN_DIR"
composer install --quiet 2>/dev/null || true

# Commit release-modified files
echo "Committing release changes..."
cd "$PLUGIN_DIR"
git add orbitools.php package.json vendor/composer/installed.php
git commit -m "Release v$VERSION" --quiet 2>/dev/null || true

# Output results
ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
FILE_COUNT=$(unzip -l "$ZIP_FILE" | tail -1 | awk '{print $2}')

echo ""
echo "=========================================="
echo "Build complete!"
echo "=========================================="
echo "Output: $ZIP_FILE"
echo "Size:   $ZIP_SIZE"
echo "Files:  $FILE_COUNT"
echo ""
