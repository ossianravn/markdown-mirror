#!/bin/bash

# Set variables
PLUGIN_SLUG="markdown-mirror"
VERSION=$(grep -m 1 "Version:" markdown-mirror.php | sed 's/.*Version: *\([0-9.]*\).*/\1/')
BUILD_DIR="./build"
DIST_DIR="./dist"
ZIP_FILE="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"

# Check for required commands
command -v zip >/dev/null 2>&1 || { echo "Error: zip command is required but not installed. Install with: sudo apt-get install zip"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "Error: composer is required but not installed."; exit 1; }

# Ensure we're in the plugin directory
if [ ! -f "markdown-mirror.php" ]; then
    echo "Error: Must be run from plugin root directory"
    exit 1
fi

# Verify version was extracted
if [ -z "$VERSION" ]; then
    echo "Error: Could not extract version from plugin file"
    exit 1
fi

# Create necessary directories
echo "Creating build directories..."
rm -rf "$BUILD_DIR" "$DIST_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"
mkdir -p "$DIST_DIR"

# Install composer dependencies in production mode
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Copy files to build directory
echo "Copying plugin files..."
if [ ! -f "LICENSE" ]; then
    echo "Warning: LICENSE file not found"
fi

# Create array of files/directories to copy
files=(
    "admin"
    "includes"
    "vendor"
    "markdown-mirror.php"
    "README.md"
    "LICENSE"
)

# Copy each file/directory if it exists
for file in "${files[@]}"; do
    if [ -e "$file" ]; then
        cp -R "$file" "$BUILD_DIR/$PLUGIN_SLUG/"
    else
        echo "Warning: $file not found"
    fi
done

# Remove development files and directories from build
echo "Removing development files..."
rm -rf "$BUILD_DIR/$PLUGIN_SLUG/vendor/composer/installed.php"
find "$BUILD_DIR/$PLUGIN_SLUG" -name ".git*" -exec rm -rf {} +
find "$BUILD_DIR/$PLUGIN_SLUG" -name "*.map" -delete
find "$BUILD_DIR/$PLUGIN_SLUG" -name "*.log" -delete
find "$BUILD_DIR/$PLUGIN_SLUG" -name "phpunit.*" -delete
find "$BUILD_DIR/$PLUGIN_SLUG" -name "*.test.php" -delete
find "$BUILD_DIR/$PLUGIN_SLUG" -name "*.spec.php" -delete

# Create zip file
echo "Creating zip file..."
cd "$BUILD_DIR"
zip -r "../$ZIP_FILE" "$PLUGIN_SLUG"
if [ $? -ne 0 ]; then
    echo "Error: Failed to create zip file"
    cd ..
    rm -rf "$BUILD_DIR"
    exit 1
fi
cd ..

# Cleanup
echo "Cleaning up..."
rm -rf "$BUILD_DIR"

# Output success message with full details
echo "Build complete!"
echo "Plugin: $PLUGIN_SLUG"
echo "Version: $VERSION"
echo "Zip file: $ZIP_FILE"
echo
echo "Note: Some PSR-4 autoloading warnings are expected and can be ignored." 