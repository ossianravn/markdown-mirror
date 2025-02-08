<?php
namespace MarkdownMirror\Tests;

/**
 * Class Test
 * Provides testing functionality for the Markdown Mirror plugin
 */
class Test {
    /**
     * Run all tests
     */
    public static function run_tests() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $results = [];
        
        // Test basic functionality
        $results['rewrite_rules'] = self::test_rewrite_rules();
        $results['converter'] = self::test_converter();
        $results['cache'] = self::test_cache();
        $results['seo'] = self::test_seo();
        $results['settings'] = self::test_settings();
        
        return $results;
    }

    /**
     * Test rewrite rules and URL handling
     */
    private static function test_rewrite_rules() {
        $tests = [];

        // Test llms.txt URL
        $response = wp_remote_get(home_url('llms.txt'));
        $tests['llms_txt_status'] = wp_remote_retrieve_response_code($response) === 200;
        $tests['llms_txt_content_type'] = strpos(
            wp_remote_retrieve_header($response, 'content-type'),
            'text/markdown'
        ) !== false;

        // Test .md URL for a published post
        $post_id = self::create_test_post();
        if ($post_id) {
            $post = get_post($post_id);
            $md_url = home_url(get_post_field('post_name', $post) . '.md');
            $response = wp_remote_get($md_url);
            $tests['md_url_status'] = wp_remote_retrieve_response_code($response) === 200;
            $tests['md_content_type'] = strpos(
                wp_remote_retrieve_header($response, 'content-type'),
                'text/markdown'
            ) !== false;
            wp_delete_post($post_id, true);
        }

        return $tests;
    }

    /**
     * Test HTML to Markdown conversion
     */
    private static function test_converter() {
        $tests = [];
        $converter = md_mirror_get_converter();

        // Test basic HTML conversion
        $html = '<h1>Test</h1><p>This is a <strong>test</strong>.</p>';
        $markdown = $converter->convert($html);
        $tests['basic_conversion'] = strpos($markdown, '# Test') === 0 
            && strpos($markdown, '**test**') !== false;

        // Test post conversion
        $post_id = self::create_test_post();
        if ($post_id) {
            $markdown = $converter->convert_post($post_id);
            $tests['post_conversion'] = !empty($markdown) 
                && strpos($markdown, '# Test Post') === 0;
            wp_delete_post($post_id, true);
        }

        return $tests;
    }

    /**
     * Test caching functionality
     */
    private static function test_cache() {
        $tests = [];
        
        // Test post cache
        $post_id = self::create_test_post();
        if ($post_id) {
            $markdown = "# Test Content\n\nThis is cached content.";
            $tests['set_cache'] = \MarkdownMirror\Cache::set_post_markdown($post_id, $markdown);
            $tests['get_cache'] = \MarkdownMirror\Cache::get_post_markdown($post_id) === $markdown;
            
            // Test cache clearing
            \MarkdownMirror\Cache::clear_post_cache($post_id);
            $tests['clear_cache'] = \MarkdownMirror\Cache::get_post_markdown($post_id) === false;
            
            wp_delete_post($post_id, true);
        }

        // Test llms.txt cache
        $content = "# Test Site\n\n> Description\n\n## Content";
        $tests['set_llms_cache'] = \MarkdownMirror\Cache::set_llms_content($content);
        $tests['get_llms_cache'] = \MarkdownMirror\Cache::get_llms_content() === $content;
        
        return $tests;
    }

    /**
     * Test SEO functionality
     */
    private static function test_seo() {
        $tests = [];
        
        // Test robots.txt modifications
        $robots = apply_filters('robots_txt', '', true);
        $tests['robots_txt'] = strpos($robots, 'llms.txt') !== false 
            && strpos($robots, '.md') !== false;

        // Test headers for .md URLs
        $post_id = self::create_test_post();
        if ($post_id) {
            $headers = \MarkdownMirror\SEO::get_markdown_headers($post_id);
            $tests['md_headers'] = isset($headers['Content-Type']) 
                && isset($headers['X-Robots-Tag'])
                && isset($headers['Link']);
            wp_delete_post($post_id, true);
        }

        // Test llms.txt headers
        $headers = \MarkdownMirror\SEO::get_llms_headers();
        $tests['llms_headers'] = isset($headers['Content-Type']) 
            && isset($headers['X-Robots-Tag']);

        return $tests;
    }

    /**
     * Test plugin settings
     */
    private static function test_settings() {
        $tests = [];
        
        // Test default options
        $tests['noindex_default'] = get_option('md_mirror_noindex_md') === 'yes';
        $tests['post_types_default'] = in_array('post', get_option('md_mirror_post_types', [])) 
            && in_array('page', get_option('md_mirror_post_types', []));

        // Test post meta
        $post_id = self::create_test_post();
        if ($post_id) {
            $tests['post_meta_default'] = md_mirror_is_post_included($post_id) === true;
            update_post_meta($post_id, '_md_mirror_include', 'no');
            $tests['post_meta_excluded'] = md_mirror_is_post_included($post_id) === false;
            wp_delete_post($post_id, true);
        }

        return $tests;
    }

    /**
     * Create a test post
     *
     * @return int|false Post ID or false on failure
     */
    private static function create_test_post() {
        return wp_insert_post([
            'post_title' => 'Test Post',
            'post_content' => 'This is a <strong>test</strong> post content.',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
    }
} 