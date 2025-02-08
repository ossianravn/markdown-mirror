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
        // Try to get cached content first
        $content = Cache::get_llms_content();
        if ($content !== false) {
            return $content;
        }
        
        // Get site info
        $site_title = get_bloginfo('name');
        $site_description = get_option('md_mirror_custom_summary', get_bloginfo('description'));
        
        // Start building llms.txt content
        $content = "# {$site_title}\n\n";
        $content .= "> {$site_description}\n\n";
        $content .= "## Available Content\n\n";
        
        // Get published posts and pages
        $args = array(
            'post_type' => get_option('md_mirror_post_types', ['post', 'page']),
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        
        $posts = get_posts($args);
        
        foreach ($posts as $post) {
            // Skip if post is excluded
            if (!md_mirror_is_post_included($post)) {
                continue;
            }
            
            $md_url = home_url(get_post_field('post_name', $post) . '.md');
            
            // Start with the title and URL
            $content .= "### [{$post->post_title}]({$md_url})\n\n";
            
            // Add meta description if available (using Yoast SEO or similar)
            $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
            if (!empty($meta_desc)) {
                $content .= "_Meta: {$meta_desc}_\n\n";
            }
            
            // Add excerpt if available
            $excerpt = get_the_excerpt($post);
            if (!empty($excerpt) && $excerpt !== $meta_desc) { // Only add if different from meta
                $content .= "{$excerpt}\n\n";
            }
            
            // Add a separator between entries
            $content .= "---\n\n";
        }
        
        // Cache the content
        Cache::set_llms_content($content);
        
        return $content;
    }

    /**
     * Process content without including optional URLs
     * Removes markdown links but keeps the text content
     *
     * @param string $content The content to process
     * @return string The processed content
     */
    private function process_content_without_urls($content) {
        // Replace markdown links with just their text content
        // [link text](url) becomes just "link text"
        $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $content);
        
        // Remove any remaining URLs
        $content = preg_replace('/https?:\/\/\S+/', '', $content);
        
        // Clean up any double spaces or empty lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        $content = preg_replace('/[ ]{2,}/', ' ', $content);
        
        return trim($content);
    }

    /**
     * Process content and include referenced URLs
     * Expands markdown links by fetching and including their content
     *
     * @param string $content The content to process
     * @return string The processed content
     */
    private function process_content_with_urls($content) {
        // Find all markdown links
        preg_match_all('/\[([^\]]+)\]\(([^\)]+)\)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $link_text = $match[1];
            $url = $match[2];
            
            // Only process internal .md URLs
            if (strpos($url, home_url()) === 0 && substr($url, -3) === '.md') {
                // Get the post content
                $path = str_replace(home_url() . '/', '', $url);
                $path = str_replace('.md', '', $path);
                $post_id = url_to_postid(home_url($path));
                
                if ($post_id) {
                    // Get the markdown content
                    $markdown = Cache::get_post_markdown($post_id);
                    if ($markdown === false) {
                        $converter = md_mirror_get_converter();
                        $markdown = $converter->convert_post($post_id);
                        Cache::set_post_markdown($post_id, $markdown);
                    }
                    
                    // Replace the link with the full content
                    $replacement = "\n\n### {$link_text}\n\n{$markdown}\n\n---\n\n";
                    $content = str_replace($match[0], $replacement, $content);
                }
            }
        }
        
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