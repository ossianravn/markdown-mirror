<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
error_log('Markdown Mirror: Main plugin file loaded at ' . time());

/**
 * Plugin Name: Markdown Mirror (llms.txt)
 * Plugin URI: https://example.com/markdown-mirror
 * Description: Generates a Markdown mirror of site content and an llms.txt file for AI consumption.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: markdown-mirror
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'MD_MIRROR_VERSION', '1.0.0' );
define( 'MD_MIRROR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MD_MIRROR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Error reporting for debugging
if (WP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Load the plugin autoloader first
error_log('Markdown Mirror: Loading autoloader');
require_once MD_MIRROR_PLUGIN_DIR . 'includes/class-autoloader.php';
\MarkdownMirror\Autoloader::register();

// Load Composer autoloader if it exists
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    error_log('Markdown Mirror: Loading Composer autoloader');
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * The main plugin class
 */
class Markdown_Mirror_Plugin {
    /**
     * @var \MarkdownMirror\MD_Converter
     */
    public static $converter = null;

    /**
     * Initialize the plugin
     */
    public static function init() {
        error_log('Markdown Mirror: Plugin init() called');
        try {
            // Initialize components immediately
            self::init_components();
            
            // Initialize admin components if in admin
            if (is_admin()) {
                error_log('Markdown Mirror: Initializing admin components');
                self::init_admin();
            }
            
        } catch (\Exception $e) {
            error_log('Markdown Mirror Error: ' . $e->getMessage());
            if (WP_DEBUG) {
                error_log('Markdown Mirror Error Stack Trace: ' . $e->getTraceAsString());
            }
            add_action('admin_notices', function() use ($e) {
                ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html('Markdown Mirror Plugin Error: ' . $e->getMessage()); ?></p>
                </div>
                <?php
            });
        }
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        error_log('Markdown Mirror: Plugin activation');
        
        // Initialize rewrite rules
        \MarkdownMirror\Rewrite::activate();
        
        // Set default options
        add_option('md_mirror_noindex_md', 'yes');
        add_option('md_mirror_post_types', ['post', 'page']);
        
        // Log activation for debugging
        if (WP_DEBUG) {
            error_log('Markdown Mirror: Plugin activated');
        }
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        error_log('Markdown Mirror: Plugin deactivation');
        if (class_exists('\MarkdownMirror\Cache')) {
            \MarkdownMirror\Cache::clear_all_cache();
        }
    }

    /**
     * Initialize admin components
     */
    private static function init_admin() {
        error_log('Markdown Mirror: Initializing admin components');
        if (!class_exists('\MarkdownMirror\Admin\Admin_Menu')) {
            throw new \Exception('Admin_Menu class not found. Please check your installation.');
        }
        
        // Initialize admin interface
        \MarkdownMirror\Admin\Admin_Menu::init();
        \MarkdownMirror\Admin\Meta_Box::init();
        
        // Initialize test page in admin
        if (WP_DEBUG) {
            \MarkdownMirror\Admin\Test_Page::init();
        }
    }

    /**
     * Initialize plugin components
     */
    public static function init_components() {
        error_log('Markdown Mirror: Initializing plugin components');
        // Initialize the converter first
        if (!class_exists('\MarkdownMirror\MD_Converter')) {
            throw new \Exception('MD_Converter class not found. Please check your installation.');
        }
        self::$converter = new \MarkdownMirror\MD_Converter();
        
        // Initialize rewrite rules
        error_log('Markdown Mirror: About to initialize Rewrite class');
        \MarkdownMirror\Rewrite::init();
        
        // Initialize caching system
        \MarkdownMirror\Cache::init();
        
        // Initialize SEO functionality
        \MarkdownMirror\SEO::init();
    }

    /**
     * Get the converter instance
     *
     * @return \MarkdownMirror\MD_Converter
     */
    public static function get_converter() {
        return self::$converter;
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    error_log('Markdown Mirror: Initializing plugin components');
    
    try {
        // Initialize the converter first
        if (!class_exists('\MarkdownMirror\MD_Converter')) {
            throw new \Exception('MD_Converter class not found. Please check your installation.');
        }
        Markdown_Mirror_Plugin::$converter = new \MarkdownMirror\MD_Converter();
        
        // Initialize rewrite functionality first
        \MarkdownMirror\Rewrite::init();
        
        // Initialize other components
        \MarkdownMirror\SEO::init();
        \MarkdownMirror\Cache::init();
        
        // Initialize admin components if in admin
        if (is_admin()) {
            \MarkdownMirror\Admin\Admin_Menu::init();
            \MarkdownMirror\Admin\Meta_Box::init();
        }
    } catch (\Exception $e) {
        error_log('Markdown Mirror Error: ' . $e->getMessage());
        if (WP_DEBUG) {
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
});

// Register activation/deactivation hooks
register_activation_hook(__FILE__, function() {
    error_log('Markdown Mirror: Plugin activation');
    \MarkdownMirror\Rewrite::activate();
    
    // Set default options
    add_option('md_mirror_noindex_md', 'yes');
    add_option('md_mirror_post_types', ['post', 'page']);
});

register_deactivation_hook(__FILE__, function() {
    error_log('Markdown Mirror: Plugin deactivation');
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
    return Markdown_Mirror_Plugin::get_converter();
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