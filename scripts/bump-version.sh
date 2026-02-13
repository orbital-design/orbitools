#!/bin/bash
#
# Version bump script for Orbitools WordPress plugin
# Bumps the semantic version in orbitools.php and package.json
#
# Usage:
#   ./scripts/bump-version.sh patch    # 1.0.0 → 1.0.1
#   ./scripts/bump-version.sh minor    # 1.0.0 → 1.1.0
#   ./scripts/bump-version.sh major    # 1.0.0 → 2.0.0
#   ./scripts/bump-version.sh 2.3.1    # Set exact version
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
PLUGIN_FILE="$PLUGIN_DIR/orbitools.php"
PACKAGE_JSON="$PLUGIN_DIR/package.json"

# Get current version from plugin header
CURRENT=$(grep -o "Version:.*" "$PLUGIN_FILE" | head -1 | sed 's/Version:[[:space:]]*//')

if [ -z "$CURRENT" ]; then
    echo "Error: Could not read version from orbitools.php"
    exit 1
fi

# Parse current version
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT"

# Determine new version
BUMP_TYPE="${1:-patch}"

case "$BUMP_TYPE" in
    patch)
        PATCH=$((PATCH + 1))
        NEW_VERSION="$MAJOR.$MINOR.$PATCH"
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        NEW_VERSION="$MAJOR.$MINOR.$PATCH"
        ;;
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        NEW_VERSION="$MAJOR.$MINOR.$PATCH"
        ;;
    *)
        # Validate as explicit version (x.y.z)
        if [[ "$BUMP_TYPE" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            NEW_VERSION="$BUMP_TYPE"
        else
            echo "Error: Invalid argument '$BUMP_TYPE'"
            echo "Usage: $0 [patch|minor|major|x.y.z]"
            exit 1
        fi
        ;;
esac

# Update plugin header version
sed -i '' "s/Version:[[:space:]]*$CURRENT/Version:         $NEW_VERSION/" "$PLUGIN_FILE"

# Update ORBITOOLS_VERSION constant
sed -i '' "s/define('ORBITOOLS_VERSION', '$CURRENT')/define('ORBITOOLS_VERSION', '$NEW_VERSION')/" "$PLUGIN_FILE"

# Update package.json
sed -i '' "s/\"version\": \"$CURRENT\"/\"version\": \"$NEW_VERSION\"/" "$PACKAGE_JSON"

echo "$CURRENT → $NEW_VERSION"
