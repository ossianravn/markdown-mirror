# Markdown Mirror (llms.txt) WordPress Plugin

**Version:** 1.0.0  
**Author:** Ossian Ravn Engmark (hello@ossianravn.dev)
**License:** GPL v2 or later  
**Requires WordPress:** 5.0+  
**Requires PHP:** 7.4+

---

## Table of Contents

1. [Overview](#overview)  
2. [Key Features](#key-features)  
3. [Installation & Requirements](#installation--requirements)  
4. [Basic Usage](#basic-usage)  
5. [Admin Settings](#admin-settings)  
   - [General Settings](#general-settings)  
   - [SEO Settings](#seo-settings)  
   - [Cache Settings](#cache-settings)  
6. [Per-Post Controls](#per-post-controls)  
7. [Markdown & llms.txt Generation](#markdown--llmstxt-generation)  
   - [How `.md` URLs Are Served](#how-md-urls-are-served)  
   - [How `llms.txt` Is Generated](#how-llmstxt-is-generated)  
8. [Caching System](#caching-system)  
9. [SEO Handling & Noindex](#seo-handling--noindex)  
10. [Testing & Debug Tools](#testing--debug-tools)  
11. [FAQ / Troubleshooting](#faq--troubleshooting)  
12. [Installation via Composer](#installation-via-composer)  
13. [Changelog](#changelog)  
14. [Contributing & Support](#contributing--support)  
15. [License](#license)

---

## 1. Overview

The **Markdown Mirror (llms.txt)** plugin dynamically converts your WordPress posts and pages into Markdown on the fly and generates a root-level `llms.txt` file. This file acts as a machine-friendly "AI sitemap," guiding advanced language models and developer tools to your site's key content while ensuring your SEO remains intact.

Key benefits include:
- **Dynamic Markdown Conversion:** Converts posts/pages to Markdown (using the [League HTML-to-Markdown](https://github.com/thephpleague/html-to-markdown) library) when requested.
- **AI-Friendly Index:** Creates an `llms.txt` file that aggregates your content in a clean, Markdown-formatted list.
- **SEO-Friendly:** Adds canonical links and SEO headers without disallowing `.md` URLs in `robots.txt`—ensuring both search engines and AI crawlers can access your content appropriately.
- **Efficient Caching:** Speeds up repeated requests by caching both individual Markdown conversions and the global `llms.txt` content.
- **Per-Post Control:** Allows you to include or exclude specific posts from the Markdown mirror via a simple meta box.
- **Robust Admin Interface:** Provides detailed settings and built-in tests to ensure proper functionality.

---

## 2. Key Features

- **Dynamic Conversion:** Serve your posts and pages as Markdown files via custom rewrite rules.
- **Automatic `llms.txt` Generation:** Generate an "AI sitemap" at `llms.txt` that lists your included content.
- **Admin Settings & Meta Boxes:** Easily select which post types to mirror and manage per-post inclusion.
- **Caching System:** Automatically caches converted Markdown and the `llms.txt` output to improve performance.
- **SEO Enhancements:** Adds canonical links, alternate links, and X-Robots-Tag headers for proper content discovery and SEO optimization.
- **Test Suite:** Run diagnostic tests (available in WP_DEBUG mode) to verify rewrite rules, conversion accuracy, caching, and SEO headers.
- **Custom Autoloader & Composer Support:** Leverages a custom PSR-4 autoloader along with Composer for dependency management.

---

## 3. Installation & Requirements

### User Installation

1. **Upload Plugin Files:**  
   Upload the entire `markdown-mirror` folder to your `/wp-content/plugins/` directory.

2. **Activate the Plugin:**  
   In the WordPress admin, navigate to **Plugins > Installed Plugins** and activate **Markdown Mirror (llms.txt)**.

3. **Configure Settings:**  
   Visit **Settings > Markdown Mirror** to choose which post types to mirror, set a custom summary for `llms.txt`, and adjust caching and SEO options.

4. **Permalinks:**  
   If your `.md` URLs or `llms.txt` file are not working as expected, go to **Settings > Permalinks** and click **Save Changes** to flush rewrite rules.

### Development Installation

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/[your-username]/markdown-mirror.git
   cd markdown-mirror
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   ```

3. **Build the Plugin:**
   ```bash
   ./build.sh
   ```

### Requirements
- WordPress 5.0+  
- PHP 7.4+  
- Pretty permalinks enabled
- Composer (for development)

---

## 4. Basic Usage

Once activated, the plugin automatically:

- **Serves Markdown Versions:**  
  Your posts and pages are available as Markdown files at URLs like:  
  `https://your-site.com/your-post.md`

- **Generates `llms.txt`:**  
  A Markdown-formatted `llms.txt` file is available at:  
  `https://your-site.com/llms.txt`

- **Admin Controls:**  
  - **Global Settings:** Configure the post types, custom summary, and SEO options.
  - **Per-Post Controls:** Use the meta box on the post edit screen to include or exclude specific posts.

- **Testing:**  
  (When WP_DEBUG is enabled) Run tests under **Tools > MD Mirror Tests** to verify rewrite rules, caching, conversion, and SEO header functionality.

---

## 5. Admin Settings

Access the **Markdown Mirror** settings under **Settings → Markdown Mirror** in your WordPress dashboard. The settings are divided into three sections:

### General Settings

- **Include Post Types:**  
  Select which post types (Posts, Pages, custom types, etc.) should be mirrored.
  
- **Custom llms.txt Summary:**  
  Enter a custom summary to appear at the top of the `llms.txt` file. If left blank, your site tagline is used.

### SEO Settings

- **NoIndex Markdown URLs:**  
  Choose whether to send a `noindex` header for `.md` pages to prevent them from competing with your main HTML pages.  
  *(Note: Although `.md` pages receive an X-Robots-Tag noindex header when enabled, the `robots.txt` is no longer configured to disallow `.md` URLs.)*

### Cache Settings

- **Cache Duration:**  
  Set how long to cache the converted Markdown content and `llms.txt` file (e.g., 1 hour, 1 day, or 1 week) to improve performance.

---

## 6. Per-Post Controls

To manage Markdown inclusion on individual posts:

1. **Edit a Post/Page:**  
   Open the post you want to control in the editor.
   
2. **Locate the Meta Box:**  
   Find the **Markdown Mirror** meta box on the right-hand side.
   
3. **Toggle Inclusion:**  
   Choose **"Include in Markdown Mirror"** or **"Exclude from Markdown Mirror"**.  
   - Excluded posts return a 404 on `.md` URLs and are omitted from `llms.txt`.

By default, all posts are included unless explicitly excluded.

---

## 7. Markdown & llms.txt Generation

### How `.md` URLs Are Served

- **Rewrite Rules:**  
  The plugin adds rewrite rules that capture requests ending in `.md`.
  
- **Dynamic Conversion:**  
  When a `.md` URL is requested, the plugin converts the corresponding post's HTML to Markdown using the League HTML-to-Markdown library.  
- **Caching:**  
  The converted Markdown is cached to speed up subsequent requests.

### How `llms.txt` Is Generated

- **Aggregation:**  
  When you visit `llms.txt`, the plugin:
  1. Retrieves the site name and a custom summary (or WP's tagline).
  2. Lists all included posts/pages with headings that link to their `.md` URLs.
  3. Optionally includes meta descriptions or excerpts.
  
- **Formatting:**  
  Each entry is separated by a horizontal rule (`---`) for clarity.
  
- **Caching:**  
  The generated `llms.txt` is cached and automatically updated when content or settings change.

---

## 8. Caching System

The plugin caches:
- **Per-Post Markdown Content:** To prevent repeated conversions.
- **Global `llms.txt` Content:** To minimize processing on each request.

**Cache Invalidation Triggers:**
- Creating, updating, or deleting a post.
- Changing a post's publish status.
- Updating relevant meta data (e.g., `_md_mirror_include`, SEO descriptions).
- Modifying plugin settings (post types, custom summary).

This ensures that content is current while reducing server load.

---

## 9. SEO Handling & Noindex

To safeguard your site's SEO, the plugin:

- **Noindex Markdown Pages:**  
  When enabled in the settings, `.md` pages receive an `X-Robots-Tag: noindex, nofollow` header to prevent duplicate content issues.
  
- **Canonical Links:**  
  Each `.md` page includes a canonical link header pointing back to the original HTML page.

- **Alternate Links:**  
  Original HTML pages include a `rel="alternate"` link to their Markdown versions, helping search engines and AI tools discover the Markdown content. The Markdown pages, in turn, include both a canonical link back to the HTML version and an alternate link to themselves.
  
- **Robots.txt Adjustments:**  
  The plugin adds references to `llms.txt` in `robots.txt` but no longer disallows `.md` files, ensuring that both search engines and AI crawlers can access them if needed.

---

## 10. Testing & Debug Tools

A built-in test suite is available under **Tools → MD Mirror Tests** (visible when WP_DEBUG is enabled). It checks:

- **Rewrite Rules:** Verifies that requests for `llms.txt` and `.md` URLs are processed correctly.
- **Conversion Accuracy:** Ensures HTML is correctly converted to Markdown.
- **Caching Behavior:** Confirms that content is being cached and cleared as expected.
- **SEO Headers:** Validates that the appropriate HTTP headers (e.g., Content-Type, X-Robots-Tag, canonical link) are set.
- **Settings & Meta Box Functionality:** Checks that the default options and per-post controls work correctly.

Each test reports a Pass/Fail status with details for troubleshooting.

---

## 11. FAQ / Troubleshooting

**Q1: I get a 404 on my `.md` or `llms.txt` URLs.**  
- **A:** Flush your permalinks by going to **Settings → Permalinks** and clicking **Save Changes**. Also, ensure your server supports URL rewriting.

**Q2: My `.md` pages are appearing in search results.**  
- **A:** Confirm that the **NoIndex Markdown URLs** option is enabled. Although the plugin no longer disallows `.md` files in `robots.txt`, the appropriate noindex headers should prevent them from being indexed.

**Q3: The conversion seems slow for very large posts.**  
- **A:** Enable caching (the default) to reduce processing time on subsequent requests.

**Q4: Are physical `.md` files created on my server?**  
- **A:** No. All Markdown is generated dynamically without storing physical files.

**Q5: How do I exclude a specific post?**  
- **A:** Use the meta box on the post editor screen to mark a post as excluded from the Markdown mirror.

---

## 12. Installation via Composer

If you prefer to manage dependencies with Composer, navigate to the plugin directory and run:

```bash
composer install
```

This will install the HTML-to-Markdown library and any other required packages. Ensure your environment meets the PHP 7.4+ requirement.

---

## 13. Changelog

### 1.0.0
- Initial release with dynamic Markdown conversion and `llms.txt` generation.
- Added per-post control via meta boxes.
- Implemented caching, SEO enhancements, and rewrite rules.
- Included a comprehensive test suite for verifying core functionalities.
- **Note:** The disallow rule for `.md` files in `robots.txt` has been removed.

---

## 14. Contributing & Support

### Reporting Issues
- Use the [GitHub issue tracker](https://github.com/[your-username]/markdown-mirror/issues) to report bugs
- Include as much detail as possible using the provided issue templates
- Follow the bug report template for bugs and feature request template for new features

### Development Process
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run the build script to test (`./build.sh`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Build & Release Process

#### Local Development
- `build.sh`: Creates a distributable zip file
  ```bash
  ./build.sh
  ```

#### Making Releases
- `release.sh`: Handles version bumping, changelog updates, and deployment
  ```bash
  ./release.sh <new-version>
  # Example: ./release.sh 1.1.0
  ```

Options:
- `--skip-git`: Skip Git operations
- `--skip-svn`: Skip WordPress.org SVN deployment

#### Automated Processes
The repository includes GitHub Actions workflows that:
- Build the plugin on tag pushes
- Create GitHub releases
- Attach distribution files
- Generate release notes

### Documentation
- For advanced customization, refer to inline code comments
- Check the [Wiki](https://github.com/[your-username]/markdown-mirror/wiki) for detailed documentation
- Review available hooks and filters in the source code

---

## 15. License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

*Happy Markdowning!*
