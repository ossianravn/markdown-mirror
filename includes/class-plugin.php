<?php
namespace MarkdownMirror;

use MarkdownMirror\Admin\Settings;

/**
 * Main plugin class
 */
class Plugin {
    /**
     * @var MD_Converter
     */
    private static $converter = null;

    /**
     * Initialize the plugin
     */
    public static function init() {
        // Initialize autoloader
        Autoloader::register();

        // Initialize converter
        self::$converter = new MD_Converter();

        // Initialize components
        if (is_admin()) {
            Settings::init();
        }

        Rewrite::init();
        Cache::init();
        SEO::init();

        // Register activation hook
        register_activation_hook(MARKDOWN_MIRROR_FILE, [__CLASS__, 'activate']);
    }

    /**
     * Get the converter instance
     *
     * @return MD_Converter
     */
    public static function get_converter() {
        if (self::$converter === null) {
            self::$converter = new MD_Converter();
        }
        return self::$converter;
    }

    /**
     * Activation hook handler
     */
    public static function activate() {
        // Initialize rewrite rules
        Rewrite::init();
        Rewrite::activate();
        
        // Force flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        if (get_option('md_mirror_post_types') === false) {
            update_option('md_mirror_post_types', ['post', 'page']);
        }

        if (get_option('md_mirror_ctx_include_meta') === false) {
            update_option('md_mirror_ctx_include_meta', [
                'title' => true,
                'excerpt' => true,
                'meta_desc' => true,
                'content' => false
            ]);
        }

        if (get_option('md_mirror_ctx_full_depth') === false) {
            update_option('md_mirror_ctx_full_depth', 1);
        }

        if (get_option('md_mirror_cache_duration') === false) {
            update_option('md_mirror_cache_duration', DAY_IN_SECONDS);
        }
    }
} 