#!/bin/bash

# Set variables
PLUGIN_SLUG="markdown-mirror"
CURRENT_VERSION=$(grep -m 1 "Version:" markdown-mirror.php | sed 's/.*Version: *\([0-9.]*\).*/\1/')
SVN_URL="https://plugins.svn.wordpress.org/$PLUGIN_SLUG"
SVN_DIR=".svn"
SKIP_GIT=false
SKIP_SVN=false
CHANGELOG_FILE="CHANGELOG.md"
RELEASE_NOTES_FILE="release-notes.md"

# Function to validate version number
validate_version() {
    if [[ ! $1 =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        echo "Error: Version must be in format x.y.z (e.g., 1.0.0)"
        exit 1
    fi
}

# Function to get commit messages since last tag
get_commit_messages() {
    local last_tag=$(git describe --tags --abbrev=0 2>/dev/null)
    if [ -n "$last_tag" ]; then
        git log --pretty=format:"- %s" $last_tag..HEAD
    else
        git log --pretty=format:"- %s"
    fi
}

# Function to update changelog
update_changelog() {
    local new_version=$1
    local date=$(date +%Y-%m-%d)
    local temp_file=$(mktemp)
    
    # Create changelog file if it doesn't exist
    if [ ! -f "$CHANGELOG_FILE" ]; then
        echo "# Changelog" > "$CHANGELOG_FILE"
        echo "" >> "$CHANGELOG_FILE"
    fi

    # Generate release notes from git commits
    echo "## [$new_version] - $date" > "$temp_file"
    echo "" >> "$temp_file"
    
    # Get commit messages and categorize them
    echo "### Added" >> "$temp_file"
    get_commit_messages | grep -i "add\|new\|feature" >> "$temp_file" || true
    echo "" >> "$temp_file"
    
    echo "### Changed" >> "$temp_file"
    get_commit_messages | grep -i "change\|update\|improve\|enhance" >> "$temp_file" || true
    echo "" >> "$temp_file"
    
    echo "### Fixed" >> "$temp_file"
    get_commit_messages | grep -i "fix\|bug\|issue\|resolve" >> "$temp_file" || true
    echo "" >> "$temp_file"
    
    # Add uncategorized changes
    echo "### Other" >> "$temp_file"
    get_commit_messages | grep -iv "add\|new\|feature\|change\|update\|improve\|enhance\|fix\|bug\|issue\|resolve" >> "$temp_file" || true
    echo "" >> "$temp_file"

    # Create release notes file
    cp "$temp_file" "$RELEASE_NOTES_FILE"
    
    # Prepend new changes to changelog
    echo "$(cat $temp_file)" > "$temp_file.2"
    echo "" >> "$temp_file.2"
    echo "$(cat $CHANGELOG_FILE)" >> "$temp_file.2"
    mv "$temp_file.2" "$CHANGELOG_FILE"
    
    rm "$temp_file"
    
    # Update README.md changelog section
    if grep -q "^## Changelog" README.md; then
        sed -i "/^## Changelog/a\\
\\
### $new_version - $date\\
$(get_commit_messages | sed 's/^/- /')" README.md
    fi
}

# Function to update version in files
update_version() {
    local new_version=$1
    local files=(
        "markdown-mirror.php"
        "README.md"
    )

    for file in "${files[@]}"; do
        if [ -f "$file" ]; then
            sed -i "s/Version: $CURRENT_VERSION/Version: $new_version/" "$file"
            sed -i "s/Stable tag: $CURRENT_VERSION/Stable tag: $new_version/" "$file"
        fi
    done
}

# Function to handle Git operations
handle_git() {
    local new_version=$1
    
    # Check if in git repository
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        echo "Error: Not in a git repository"
        exit 1
    fi

    # Check for uncommitted changes
    if ! git diff-index --quiet HEAD --; then
        echo "Error: You have uncommitted changes. Please commit or stash them first."
        exit 1
    fi

    # Commit version bump and changelog
    git add markdown-mirror.php README.md "$CHANGELOG_FILE" "$RELEASE_NOTES_FILE"
    git commit -m "Release version $new_version"

    # Create and push tag with release notes
    echo "Creating git tag v$new_version..."
    if [ -f "$RELEASE_NOTES_FILE" ]; then
        git tag -a "v$new_version" -F "$RELEASE_NOTES_FILE"
    else
        git tag -a "v$new_version" -m "Release version $new_version"
    fi
    
    # Push changes and tag
    git push origin HEAD
    git push origin "v$new_version"
}

# Function to handle SVN operations
handle_svn() {
    local new_version=$1
    
    # Check for SVN credentials
    if [ -z "$SVN_USERNAME" ] || [ -z "$SVN_PASSWORD" ]; then
        echo "Error: SVN_USERNAME and SVN_PASSWORD environment variables must be set"
        exit 1
    fi

    # Clean up SVN directory if it exists
    rm -rf "$SVN_DIR"

    # Checkout SVN repository
    echo "Checking out WordPress.org SVN repository..."
    svn checkout --quiet "$SVN_URL" "$SVN_DIR"

    # Build the plugin
    echo "Building plugin..."
    ./build.sh

    # Copy build files to SVN trunk
    echo "Copying files to SVN trunk..."
    rm -rf "$SVN_DIR/trunk"
    mkdir -p "$SVN_DIR/trunk"
    cp -R "dist/$PLUGIN_SLUG-$new_version"/* "$SVN_DIR/trunk/"

    # Create new tag
    echo "Creating new SVN tag..."
    rm -rf "$SVN_DIR/tags/$new_version"
    mkdir -p "$SVN_DIR/tags/$new_version"
    cp -R "dist/$PLUGIN_SLUG-$new_version"/* "$SVN_DIR/tags/$new_version/"

    # Add new files to SVN
    cd "$SVN_DIR"
    svn add --force trunk/* --quiet
    svn add --force tags/* --quiet

    # Remove deleted files
    svn status | grep '^!' | awk '{print $2}' | xargs -I% svn rm %@ --quiet

    # Commit to WordPress.org
    echo "Committing to WordPress.org..."
    svn commit -m "Release $new_version" --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --no-auth-cache --non-interactive
    cd ..

    # Clean up
    rm -rf "$SVN_DIR"
}

# Show usage if no arguments provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <new-version> [--skip-git] [--skip-svn]"
    echo "Example: $0 1.1.0"
    echo "Current version: $CURRENT_VERSION"
    exit 1
fi

# Parse arguments
NEW_VERSION=$1
shift

while [ "$1" != "" ]; do
    case $1 in
        --skip-git ) SKIP_GIT=true ;;
        --skip-svn ) SKIP_SVN=true ;;
    esac
    shift
done

# Validate new version
validate_version "$NEW_VERSION"

# Confirm with user
echo "Current version: $CURRENT_VERSION"
echo "New version: $NEW_VERSION"
echo "Git operations: $([ "$SKIP_GIT" = true ] && echo "SKIPPED" || echo "ENABLED")"
echo "SVN operations: $([ "$SKIP_SVN" = true ] && echo "SKIPPED" || echo "ENABLED")"
read -p "Continue? [y/N] " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Update version in files
echo "Updating version numbers..."
update_version "$NEW_VERSION"

# Update changelog and generate release notes
echo "Updating changelog and generating release notes..."
update_changelog "$NEW_VERSION"

# Show release notes and ask for confirmation
if [ -f "$RELEASE_NOTES_FILE" ]; then
    echo "Generated release notes:"
    echo "----------------------"
    cat "$RELEASE_NOTES_FILE"
    echo "----------------------"
    read -p "Would you like to edit the release notes? [y/N] " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        ${EDITOR:-vim} "$RELEASE_NOTES_FILE"
    fi
fi

# Handle Git operations
if [ "$SKIP_GIT" = false ]; then
    handle_git "$NEW_VERSION"
fi

# Handle SVN operations
if [ "$SKIP_SVN" = false ]; then
    handle_svn "$NEW_VERSION"
fi

echo "Release $NEW_VERSION completed successfully!" 