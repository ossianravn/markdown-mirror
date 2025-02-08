<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
error_log('Markdown Mirror: Main plugin file loaded at ' . time());

/**
 * Plugin Name: Markdown Mirror (llms.txt)
 * Plugin URI: https://github.com/ossianravn/markdown-mirror
 * Description: Dynamically converts your WordPress posts and pages into Markdown and generates a root-level llms.txt file.
 * Version: 0.1.0
 * Author: Ossian Ravn Engmark
 * Author URI: https://ossianravn.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: markdown-mirror
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MARKDOWN_MIRROR_VERSION', '0.1.0');
define('MARKDOWN_MIRROR_FILE', __FILE__);
define('MARKDOWN_MIRROR_PATH', plugin_dir_path(__FILE__));
define('MARKDOWN_MIRROR_URL', plugin_dir_url(__FILE__));

// Error reporting for debugging
if (WP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Load Composer autoloader if it exists
if (file_exists(MARKDOWN_MIRROR_PATH . 'vendor/autoload.php')) {
    require_once MARKDOWN_MIRROR_PATH . 'vendor/autoload.php';
}

// Load core plugin files
require_once MARKDOWN_MIRROR_PATH . 'includes/class-autoloader.php';
require_once MARKDOWN_MIRROR_PATH . 'includes/class-plugin.php';
require_once MARKDOWN_MIRROR_PATH . 'includes/class-md-converter.php';
require_once MARKDOWN_MIRROR_PATH . 'includes/class-rewrite.php';
require_once MARKDOWN_MIRROR_PATH . 'includes/class-cache.php';
require_once MARKDOWN_MIRROR_PATH . 'includes/class-seo.php';
require_once MARKDOWN_MIRROR_PATH . 'admin/class-settings.php';

// Initialize the plugin
add_action('plugins_loaded', function() {
    try {
        // Initialize plugin components
        \MarkdownMirror\Plugin::init();
        
    } catch (\Exception $e) {
        error_log('Markdown Mirror Error: ' . $e->getMessage());
        if (WP_DEBUG) {
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><?php echo esc_html('Markdown Mirror Plugin Error: ' . $e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
});

// Register activation/deactivation hooks
register_activation_hook(__FILE__, ['\MarkdownMirror\Plugin', 'activate']);
register_deactivation_hook(__FILE__, function() {
    if (class_exists('\MarkdownMirror\Cache')) {
        \MarkdownMirror\Cache::clear_all_cache();
    }
    flush_rewrite_rules();
});

/**
 * Helper function to get the converter instance.
 *
 * @return \MarkdownMirror\MD_Converter
 */
function md_mirror_get_converter() {
    return \MarkdownMirror\Plugin::get_converter();
}

/**
 * Helper function to check if a post should be included in Markdown mirror
 *
 * @param int|\WP_Post $post Post ID or object
 * @return bool Whether the post should be included
 */
function md_mirror_is_post_included($post) {
    return \MarkdownMirror\Admin\Meta_Box::is_post_included($post);
} 