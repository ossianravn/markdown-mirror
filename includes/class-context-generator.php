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
     * Generate the basic context file (llms-ctx.txt)
     * This version contains core content without optional URLs
     *
     * @return string The generated context content
     */
    public function generate_basic_context() {
        $cache_key = 'md_mirror_basic_context';
        $cached_content = wp_cache_get($cache_key, Cache::CACHE_GROUP);

        if ($cached_content !== false) {
            return $cached_content;
        }

        // Get the llms.txt content first
        $llms_content = $this->get_llms_content();
        
        // Process the content to remove optional URLs
        $context_content = $this->process_content_without_urls($llms_content);

        // Cache the result
        wp_cache_set($cache_key, $context_content, Cache::CACHE_GROUP, Cache::CACHE_EXPIRATION);

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
        $cached_content = wp_cache_get($cache_key, Cache::CACHE_GROUP);

        if ($cached_content !== false) {
            return $cached_content;
        }

        // Override content settings for full context
        add_filter('pre_option_md_mirror_ctx_include_meta', function() {
            return [
                'title' => true,
                'excerpt' => true,
                'meta_desc' => true,
                'content' => true // Force content inclusion for full context
            ];
        });

        // Get the llms.txt content with full content
        $llms_content = $this->get_llms_content();
        
        // Remove the filter
        remove_all_filters('pre_option_md_mirror_ctx_include_meta');
        
        // Process the content and include referenced URLs
        $context_content = $this->process_content_with_urls($llms_content);

        // Cache the result
        wp_cache_set($cache_key, $context_content, Cache::CACHE_GROUP, Cache::CACHE_EXPIRATION);

        return $context_content;
    }

    /**
     * Get the base llms.txt content
     *
     * @return string The llms.txt content
     */
    private function get_llms_content() {
        // Get site info
        $site_title = get_bloginfo('name');
        $site_description = get_option('md_mirror_custom_summary', get_bloginfo('description'));
        
        // Start building llms.txt content
        $content = "# {$site_title}\n\n";
        $content .= "> {$site_description}\n\n";
        
        // Add categories section if enabled
        if (get_option('md_mirror_include_taxonomies', true)) {
            $content .= "## Categories\n\n";
            $categories = get_categories(['hide_empty' => true]);
            
            foreach ($categories as $category) {
                $content .= "### {$category->name}\n\n";
                if (!empty($category->description)) {
                    $content .= "_" . $category->description . "_\n\n";
                }
                
                // Get posts in this category
                $cat_posts = get_posts([
                    'post_type' => get_option('md_mirror_post_types', ['post', 'page']),
                    'posts_per_page' => -1,
                    'category' => $category->term_id,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                foreach ($cat_posts as $post) {
                    if (md_mirror_is_post_included($post)) {
                        $md_url = home_url(get_post_field('post_name', $post) . '.md');
                        $content .= "- [{$post->post_title}]({$md_url})\n";
                    }
                }
                
                $content .= "\n---\n\n";
            }
            
            // Add tags section
            $content .= "## Tags\n\n";
            $tags = get_tags(['hide_empty' => true]);
            
            foreach ($tags as $tag) {
                $content .= "### {$tag->name}\n\n";
                if (!empty($tag->description)) {
                    $content .= "_" . $tag->description . "_\n\n";
                }
                
                // Get posts with this tag
                $tag_posts = get_posts([
                    'post_type' => get_option('md_mirror_post_types', ['post', 'page']),
                    'posts_per_page' => -1,
                    'tag_id' => $tag->term_id,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                foreach ($tag_posts as $post) {
                    if (md_mirror_is_post_included($post)) {
                        $md_url = home_url(get_post_field('post_name', $post) . '.md');
                        $content .= "- [{$post->post_title}]({$md_url})\n";
                    }
                }
                
                $content .= "\n---\n\n";
            }
        }
        
        // Add chronological content section
        $content .= "## Available Content\n\n";
        
        // Get published posts and pages
        $args = array(
            'post_type' => get_option('md_mirror_post_types', ['post', 'page']),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $posts = get_posts($args);
        $include_meta = get_option('md_mirror_ctx_include_meta', [
            'title' => true,
            'excerpt' => true,
            'meta_desc' => true,
            'content' => false
        ]);
        
        foreach ($posts as $post) {
            // Skip if post is excluded
            if (!md_mirror_is_post_included($post)) {
                continue;
            }
            
            $md_url = home_url(get_post_field('post_name', $post) . '.md');
            
            // Add title if enabled
            if (!empty($include_meta['title'])) {
                $content .= "### [{$post->post_title}]({$md_url})\n\n";
            }
            
            // Add meta description if enabled
            if (!empty($include_meta['meta_desc'])) {
                $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
                if (!empty($meta_desc)) {
                    $content .= "_Meta: {$meta_desc}_\n\n";
                }
            }
            
            // Add excerpt if enabled
            if (!empty($include_meta['excerpt'])) {
                $excerpt = get_the_excerpt($post);
                if (!empty($excerpt) && $excerpt !== $meta_desc) { // Only add if different from meta
                    $content .= "{$excerpt}\n\n";
                }
            }
            
            // Add the post content if enabled
            if (!empty($include_meta['content'])) {
                // Get the markdown content
                $markdown = Cache::get_post_markdown($post->ID);
                if ($markdown === false) {
                    $converter = md_mirror_get_converter();
                    $markdown = $converter->convert_post($post->ID);
                    Cache::set_post_markdown($post->ID, $markdown);
                }
                $content .= $markdown . "\n\n";
            }
            
            // Add post categories and tags if enabled
            if (get_option('md_mirror_include_taxonomies', true)) {
                // Add categories
                $categories = get_the_category($post->ID);
                if (!empty($categories)) {
                    $content .= "**Categories:** ";
                    $cat_names = array_map(function($cat) {
                        return $cat->name;
                    }, $categories);
                    $content .= implode(', ', $cat_names) . "\n\n";
                }
                
                // Add tags
                $tags = get_the_tags($post->ID);
                if (!empty($tags)) {
                    $content .= "**Tags:** ";
                    $tag_names = array_map(function($tag) {
                        return $tag->name;
                    }, $tags);
                    $content .= implode(', ', $tag_names) . "\n\n";
                }
            }
            
            // Add a separator between entries
            $content .= "---\n\n";
        }
        
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
     * @param int $depth Current recursion depth
     * @return string The processed content
     */
    private function process_content_with_urls($content, $depth = 1) {
        $max_depth = get_option('md_mirror_ctx_full_depth', 1);
        if ($max_depth !== -1 && $depth > $max_depth) {
            return $content;
        }

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
                    
                    // Process nested links if not at max depth
                    if ($max_depth === -1 || $depth < $max_depth) {
                        $markdown = $this->process_content_with_urls($markdown, $depth + 1);
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
        wp_cache_delete('md_mirror_basic_context', Cache::CACHE_GROUP);
        wp_cache_delete('md_mirror_full_context', Cache::CACHE_GROUP);
    }
} 