<?php
namespace MarkdownMirror;

/**
 * Class Rewrite
 * Handles URL rewriting for .md and llms.txt
 */
class Rewrite {
    /**
     * Initialize the rewrite rules
     */
    public static function init() {
        error_log('Markdown Mirror: Rewrite class init() called at ' . current_filter());
        
        // Add query vars first (high priority)
        add_filter('query_vars', [__CLASS__, 'add_query_vars'], 1);
        
        // Add rewrite rules after query vars
        add_action('init', [__CLASS__, 'add_rewrite_rules'], 10);
        
        // Handle requests
        add_action('template_redirect', [__CLASS__, 'handle_request']);
    }

    /**
     * Add our custom query vars
     */
    public static function add_query_vars($vars) {
        error_log('Markdown Mirror: Adding query vars');
        $vars[] = 'md_mirror_llms';
        $vars[] = 'md_mirror_markdown';
        $vars[] = 'md_mirror_path';
        $vars[] = 'md_mirror_ctx';
        $vars[] = 'md_mirror_ctx_full';
        $vars[] = 'md_mirror_taxonomy';
        $vars[] = 'md_mirror_term';
        return $vars;
    }

    /**
     * Add rewrite rules
     */
    public static function add_rewrite_rules() {
        error_log('Markdown Mirror: Adding rewrite rules');
        add_rewrite_rule('^llms\.txt$', 'index.php?md_mirror_llms=1', 'top');
        add_rewrite_rule('^llms-ctx\.txt$', 'index.php?md_mirror_ctx=1', 'top');
        add_rewrite_rule('^llms-ctx-full\.txt$', 'index.php?md_mirror_ctx_full=1', 'top');
        
        // Post/page URLs
        add_rewrite_rule('^([^/]+)\.md$', 'index.php?md_mirror_markdown=1&md_mirror_path=$matches[1]', 'top');
        
        // Taxonomy archive URLs
        $taxonomies = get_taxonomies(['public' => true], 'names');
        foreach ($taxonomies as $taxonomy) {
            // Single term archives
            add_rewrite_rule(
                "^{$taxonomy}/([^/]+)\.md$",
                "index.php?md_mirror_markdown=1&md_mirror_taxonomy={$taxonomy}&md_mirror_term=\$matches[1]",
                'top'
            );
        }
        
        // Prevent trailing slash redirects for our endpoints
        add_filter('redirect_canonical', function($redirect_url, $requested_url) {
            if (preg_match('/\.(md|txt)$/', $requested_url)) {
                return false; // Prevent redirect for .md and .txt files
            }
            return $redirect_url;
        }, 10, 2);
    }

    /**
     * Activation hook handler
     */
    public static function activate() {
        error_log('Markdown Mirror: Activating rewrite rules');
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Handle requests to our custom endpoints
     */
    public static function handle_request() {
        global $wp_query;
        
        error_log('Markdown Mirror: Handling request');
        error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
        error_log('Query vars: ' . print_r($wp_query->query_vars, true));

        // Check for llms.txt
        if (get_query_var('md_mirror_llms')) {
            error_log('Markdown Mirror: Serving llms.txt');
            self::serve_llms_txt();
            exit;
        }

        // Check for llms-ctx.txt
        if (get_query_var('md_mirror_ctx')) {
            error_log('Markdown Mirror: Serving llms-ctx.txt');
            self::serve_ctx_txt();
            exit;
        }

        // Check for llms-ctx-full.txt
        if (get_query_var('md_mirror_ctx_full')) {
            error_log('Markdown Mirror: Serving llms-ctx-full.txt');
            self::serve_ctx_full_txt();
            exit;
        }

        // Check for .md file
        if (get_query_var('md_mirror_markdown')) {
            error_log('Markdown Mirror: Serving markdown file');
            $path = get_query_var('md_mirror_path');
            self::serve_markdown($path);
            exit;
        }
    }

    /**
     * Serve the llms.txt content
     */
    private static function serve_llms_txt() {
        // Set SEO headers
        foreach (SEO::get_llms_headers() as $header => $value) {
            header("$header: $value");
        }
        
        // Try to get cached content
        $content = Cache::get_llms_content();
        if ($content !== false) {
            echo $content;
            return;
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
        
        echo $content;
    }

    /**
     * Serve the Markdown version of a post/page or taxonomy archive
     *
     * @param string $path The requested path
     */
    private static function serve_markdown($path) {
        global $wp_query;
        
        // Check if this is a taxonomy archive request
        $taxonomy = get_query_var('md_mirror_taxonomy');
        $term_slug = get_query_var('md_mirror_term');
        
        if ($taxonomy && $term_slug) {
            // Check if taxonomy archives are enabled
            $enabled_types = get_option('md_mirror_post_types', ['post', 'page']);
            if (!in_array('tax_' . $taxonomy, $enabled_types)) {
                status_header(404);
                echo "# 404 Not Found\n\nThis taxonomy archive is not available in Markdown format.";
                exit;
            }
            
            $term = get_term_by('slug', $term_slug, $taxonomy);
            if (!$term) {
                status_header(404);
                echo "# 404 Not Found\n\nThe requested taxonomy term could not be found.";
                exit;
            }
            
            // Generate Markdown for taxonomy archive
            $markdown = self::generate_taxonomy_archive_markdown($term);
            
            // Set SEO headers
            foreach (SEO::get_taxonomy_markdown_headers($term) as $header => $value) {
                header("$header: $value");
            }
            
            echo $markdown;
            return;
        }
        
        // Regular post/page handling
        $url = home_url($path);
        $post_id = url_to_postid($url);
        
        if (!$post_id) {
            status_header(404);
            echo "# 404 Not Found\n\nThe requested content could not be found.";
            exit;
        }
        
        // Check if post should be included
        if (!md_mirror_is_post_included($post_id)) {
            status_header(404);
            echo "# 404 Not Found\n\nThis content is not available in Markdown format.";
            exit;
        }
        
        // Try to get cached content
        $markdown = Cache::get_post_markdown($post_id);
        if ($markdown === false) {
            // Get the converter instance
            $converter = md_mirror_get_converter();
            
            // Convert and cache
            $markdown = $converter->convert_post($post_id);
            Cache::set_post_markdown($post_id, $markdown);
        }
        
        // Set SEO headers
        foreach (SEO::get_markdown_headers($post_id) as $header => $value) {
            header("$header: $value");
        }
        
        echo $markdown;
    }

    /**
     * Generate Markdown content for a taxonomy archive
     *
     * @param WP_Term $term The taxonomy term
     * @return string The generated Markdown
     */
    private static function generate_taxonomy_archive_markdown($term) {
        $cache_key = 'md_mirror_tax_' . $term->taxonomy . '_' . $term->term_id;
        $cached_content = wp_cache_get($cache_key, Cache::CACHE_GROUP);
        
        if ($cached_content !== false) {
            return $cached_content;
        }
        
        // Build the Markdown content
        $content = "# {$term->name}\n\n";
        
        if (!empty($term->description)) {
            $content .= "{$term->description}\n\n";
        }
        
        // Get posts in this term
        $posts = get_posts([
            'post_type' => 'any',
            'tax_query' => [
                [
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ]
            ],
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        foreach ($posts as $post) {
            if (md_mirror_is_post_included($post)) {
                $md_url = home_url(get_post_field('post_name', $post) . '.md');
                $content .= "## [{$post->post_title}]({$md_url})\n\n";
                
                $excerpt = get_the_excerpt($post);
                if (!empty($excerpt)) {
                    $content .= "{$excerpt}\n\n";
                }
                
                $content .= "---\n\n";
            }
        }
        
        // Cache the content
        wp_cache_set($cache_key, $content, Cache::CACHE_GROUP, Cache::CACHE_EXPIRATION);
        
        return $content;
    }

    /**
     * Serve the llms-ctx.txt content
     */
    private static function serve_ctx_txt() {
        // Set SEO headers
        foreach (SEO::get_llms_headers() as $header => $value) {
            header("$header: $value");
        }
        
        $context_generator = new Context_Generator();
        echo $context_generator->generate_basic_context();
    }

    /**
     * Serve the llms-ctx-full.txt content
     */
    private static function serve_ctx_full_txt() {
        // Set SEO headers
        foreach (SEO::get_llms_headers() as $header => $value) {
            header("$header: $value");
        }
        
        $context_generator = new Context_Generator();
        echo $context_generator->generate_full_context();
    }
} 