<?php
/**
 * Context Generator Class
 *
 * Handles the generation of llms-ctx.txt and llms-ctx-full.txt files
 *
 * @package MarkdownMirror
 */

namespace MarkdownMirror;

/**
 * Context Generator class
 */
class Context_Generator {

    /**
     * Instance of the Cache class
     *
     * @var Cache
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache = new Cache();
    }

    /**
     * Generate the basic context file (llms-ctx.txt)
     * This version contains core content without optional URLs
     *
     * @return string The generated context content
     */
    public function generate_basic_context() {
        $cache_key = 'md_mirror_basic_context';
        $cached_content = $this->cache->get($cache_key);

        if ($cached_content !== false) {
            return $cached_content;
        }

        // Get the llms.txt content first
        $llms_content = $this->get_llms_content();
        
        // Process the content to remove optional URLs
        $context_content = $this->process_content_without_urls($llms_content);

        // Cache the result
        $this->cache->set($cache_key, $context_content);

        return $context_content;
    }

    /**
     * Generate the full context file (llms-ctx-full.txt)
     * This version includes all referenced URLs
     *
     * @return string The generated context content
     */
    public function generate_full_context() {
        $cache_key = 'md_mirror_full_context';
        $cached_content = $this->cache->get($cache_key);

        if ($cached_content !== false) {
            return $cached_content;
        }

        // Get the llms.txt content first
        $llms_content = $this->get_llms_content();
        
        // Process the content and include referenced URLs
        $context_content = $this->process_content_with_urls($llms_content);

        // Cache the result
        $this->cache->set($cache_key, $context_content);

        return $context_content;
    }

    /**
     * Get the base llms.txt content
     *
     * @return string The llms.txt content
     */
    private function get_llms_content() {
        // TODO: Implement method to get llms.txt content
        return '';
    }

    /**
     * Process content without including optional URLs
     *
     * @param string $content The content to process
     * @return string The processed content
     */
    private function process_content_without_urls($content) {
        // TODO: Implement URL removal logic
        return $content;
    }

    /**
     * Process content and include referenced URLs
     *
     * @param string $content The content to process
     * @return string The processed content
     */
    private function process_content_with_urls($content) {
        // TODO: Implement URL inclusion logic
        return $content;
    }

    /**
     * Clear the context file caches
     */
    public function clear_cache() {
        $this->cache->delete('md_mirror_basic_context');
        $this->cache->delete('md_mirror_full_context');
    }
} 