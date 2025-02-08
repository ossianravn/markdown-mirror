<?php
namespace MarkdownMirror;

/**
 * Class Cache
 * Handles caching of Markdown content and llms.txt
 */
class Cache {
    /**
     * Cache group for Markdown content
     */
    const CACHE_GROUP = 'md_mirror';

    /**
     * Cache key for llms.txt content
     */
    const LLMS_CACHE_KEY = 'llms_txt_content';

    /**
     * Cache expiration time in seconds (24 hours)
     */
    const CACHE_EXPIRATION = DAY_IN_SECONDS;

    /**
     * Initialize the caching system
     */
    public static function init() {
        // Clear cache when a post is saved/updated/deleted
        add_action('save_post', [__CLASS__, 'clear_post_cache'], 10, 2);
        add_action('delete_post', [__CLASS__, 'clear_post_cache'], 10);
        add_action('trash_post', [__CLASS__, 'clear_post_cache'], 10);
        
        // Clear cache when post status changes
        add_action('transition_post_status', [__CLASS__, 'handle_status_transition'], 10, 3);
        
        // Clear cache when relevant plugin settings are updated
        add_action('update_option_md_mirror_post_types', [__CLASS__, 'clear_all_cache']);
        add_action('update_option_md_mirror_custom_summary', [__CLASS__, 'clear_llms_cache']);
        add_action('update_option_blogname', [__CLASS__, 'clear_llms_cache']);
        add_action('update_option_blogdescription', [__CLASS__, 'clear_llms_cache']);
        
        // Clear cache when post meta is updated
        add_action('updated_post_meta', [__CLASS__, 'handle_meta_update'], 10, 4);
        add_action('added_post_meta', [__CLASS__, 'handle_meta_update'], 10, 4);
        add_action('deleted_post_meta', [__CLASS__, 'handle_meta_update'], 10, 4);
        
        // Clear cache when a post's slug changes
        add_action('post_updated', [__CLASS__, 'handle_post_update'], 10, 3);
    }

    /**
     * Get cached Markdown content for a post
     *
     * @param int|\WP_Post $post Post ID or object
     * @return string|false Cached content or false if not cached
     */
    public static function get_post_markdown($post) {
        $post = get_post($post);
        if (!$post) {
            return false;
        }

        $cache_key = self::get_post_cache_key($post->ID);
        return wp_cache_get($cache_key, self::CACHE_GROUP);
    }

    /**
     * Cache Markdown content for a post
     *
     * @param int|\WP_Post $post Post ID or object
     * @param string $markdown The Markdown content to cache
     * @return bool True on success, false on failure
     */
    public static function set_post_markdown($post, $markdown) {
        $post = get_post($post);
        if (!$post) {
            return false;
        }

        $cache_key = self::get_post_cache_key($post->ID);
        return wp_cache_set($cache_key, $markdown, self::CACHE_GROUP, self::CACHE_EXPIRATION);
    }

    /**
     * Get cached llms.txt content
     *
     * @return string|false Cached content or false if not cached
     */
    public static function get_llms_content() {
        return wp_cache_get(self::LLMS_CACHE_KEY, self::CACHE_GROUP);
    }

    /**
     * Cache llms.txt content
     *
     * @param string $content The content to cache
     * @return bool True on success, false on failure
     */
    public static function set_llms_content($content) {
        return wp_cache_set(self::LLMS_CACHE_KEY, $content, self::CACHE_GROUP, self::CACHE_EXPIRATION);
    }

    /**
     * Handle post status transitions
     */
    public static function handle_status_transition($new_status, $old_status, $post) {
        if ($new_status === 'publish' || $old_status === 'publish') {
            self::clear_llms_cache(); // Clear llms.txt as available content changed
            if ($new_status === 'publish') {
                self::clear_post_cache($post->ID, $post); // Ensure new post is regenerated
            }
        }
    }

    /**
     * Handle post updates to catch slug changes
     */
    public static function handle_post_update($post_id, $post_after, $post_before) {
        if ($post_before->post_name !== $post_after->post_name) {
            self::clear_llms_cache(); // URLs in llms.txt need updating
        }
    }

    /**
     * Handle post meta updates
     */
    public static function handle_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
        // Clear cache for meta keys that affect our output
        $watched_meta_keys = [
            '_yoast_wpseo_metadesc',    // Yoast SEO description
            '_md_mirror_include',        // Our inclusion toggle
            '_excerpt',                  // Manual excerpt
            'rank_math_description',     // RankMath SEO description
            'aioseo_description',        // All in One SEO description
        ];
        
        if (in_array($meta_key, $watched_meta_keys)) {
            self::clear_post_cache($post_id);
            self::clear_llms_cache();
        }
    }

    /**
     * Clear cached Markdown content for a post
     */
    public static function clear_post_cache($post_id, $post = null) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Get post type
        $post_type = $post ? $post->post_type : get_post_type($post_id);
        
        // Only clear cache for relevant post types
        $allowed_types = get_option('md_mirror_post_types', ['post', 'page']);
        if (!in_array($post_type, $allowed_types)) {
            return;
        }

        // Clear this post's Markdown cache
        $cache_key = self::get_post_cache_key($post_id);
        wp_cache_delete($cache_key, self::CACHE_GROUP);

        // Also clear llms.txt cache as it contains post information
        self::clear_llms_cache();
        
        error_log("Markdown Mirror: Cleared cache for post {$post_id}");
    }

    /**
     * Clear the llms.txt cache
     */
    public static function clear_llms_cache() {
        wp_cache_delete(self::LLMS_CACHE_KEY, self::CACHE_GROUP);
    }

    /**
     * Clear all plugin caches
     */
    public static function clear_all_cache() {
        wp_cache_delete(self::LLMS_CACHE_KEY, self::CACHE_GROUP);
        
        // Note: In a real production environment, you might want to implement
        // a more sophisticated way to clear all post caches, possibly using
        // a cache prefix or storing a list of cached post IDs
        wp_cache_flush();
    }

    /**
     * Get cache key for a post
     *
     * @param int $post_id Post ID
     * @return string Cache key
     */
    private static function get_post_cache_key($post_id) {
        return 'post_' . $post_id . '_markdown';
    }
} 