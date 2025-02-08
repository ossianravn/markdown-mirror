<?php
/**
 * Class Test_Context_Generator
 *
 * @package MarkdownMirror
 */

namespace MarkdownMirror\Tests;

use MarkdownMirror\Context_Generator;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use WP_UnitTest_Factory;

/**
 * Context Generator test case.
 */
class Test_Context_Generator extends TestCase {
    /**
     * Instance of Context_Generator
     *
     * @var Context_Generator
     */
    private $generator;

    /**
     * Test post IDs
     *
     * @var array
     */
    private $test_posts = [];

    /**
     * WordPress test factory
     *
     * @var WP_UnitTest_Factory
     */
    protected static $factory;

    /**
     * Set up before class
     */
    public static function set_up_before_class() {
        parent::set_up_before_class();
        self::$factory = new WP_UnitTest_Factory();
    }

    /**
     * Set up test environment
     */
    public function set_up() {
        parent::set_up();
        
        // Create test posts
        $this->test_posts[] = self::$factory->post->create([
            'post_title' => 'Test Post 1',
            'post_content' => 'This is test content 1',
            'post_status' => 'publish',
            'post_name' => 'test-post-1'
        ]);

        $this->test_posts[] = self::$factory->post->create([
            'post_title' => 'Test Post 2',
            'post_content' => 'This is test content 2',
            'post_status' => 'publish',
            'post_name' => 'test-post-2'
        ]);

        // Initialize the generator
        $this->generator = new Context_Generator();
    }

    /**
     * Clean up test environment
     */
    public function tear_down() {
        // Delete test posts
        foreach ($this->test_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
        
        parent::tear_down();
    }

    /**
     * Test basic context generation
     */
    public function test_generate_basic_context() {
        $context = $this->generator->generate_basic_context();
        
        // Should contain site title
        $this->assertStringContainsString(get_bloginfo('name'), $context);
        
        // Should contain post titles but not as links
        $this->assertStringContainsString('Test Post 1', $context);
        $this->assertStringContainsString('Test Post 2', $context);
        
        // Should NOT contain markdown links
        $this->assertDoesNotMatchRegularExpression('/\[([^\]]+)\]\([^\)]+\)/', $context);
        
        // Should NOT contain raw URLs
        $this->assertDoesNotMatchRegularExpression('/https?:\/\/\S+/', $context);
    }

    /**
     * Test full context generation
     */
    public function test_generate_full_context() {
        $context = $this->generator->generate_full_context();
        
        // Should contain site title
        $this->assertStringContainsString(get_bloginfo('name'), $context);
        
        // Should contain post titles
        $this->assertStringContainsString('Test Post 1', $context);
        $this->assertStringContainsString('Test Post 2', $context);
        
        // Should contain post content (expanded from links)
        $this->assertStringContainsString('This is test content 1', $context);
        $this->assertStringContainsString('This is test content 2', $context);
    }

    /**
     * Test URL processing in basic context
     */
    public function test_url_removal() {
        // Create a post with URLs
        $post_with_urls = self::$factory->post->create([
            'post_title' => 'URL Test Post',
            'post_content' => 'Check this [link](https://example.com) and https://raw-url.com',
            'post_status' => 'publish',
            'post_name' => 'url-test'
        ]);
        
        // Add to test posts so it gets cleaned up
        $this->test_posts[] = $post_with_urls;
        
        // Clear any existing cache
        $this->generator->clear_cache();
        
        $context = $this->generator->generate_basic_context();
        
        // Should contain the word "link" but not the URL
        $this->assertStringContainsString('Check this link and', $context);
        $this->assertStringNotContainsString('https://example.com', $context);
        $this->assertStringNotContainsString('https://raw-url.com', $context);
    }

    /**
     * Test caching functionality
     */
    public function test_caching() {
        // Generate context first time
        $first_context = $this->generator->generate_basic_context();
        
        // Add a new post - shouldn't appear in cached content
        $new_post = self::$factory->post->create([
            'post_title' => 'New Post',
            'post_content' => 'New content',
            'post_status' => 'publish'
        ]);
        
        $cached_context = $this->generator->generate_basic_context();
        $this->assertEquals($first_context, $cached_context);
        
        // Clear cache and regenerate
        $this->generator->clear_cache();
        $fresh_context = $this->generator->generate_basic_context();
        
        // New content should now appear
        $this->assertStringContainsString('New Post', $fresh_context);
        
        wp_delete_post($new_post, true);
    }
} 