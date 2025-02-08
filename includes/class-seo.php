<?php
namespace MarkdownMirror;

/**
 * Class SEO
 * Handles SEO-related functionality for Markdown Mirror
 */
class SEO {
    /**
     * Initialize SEO functionality
     */
    public static function init() {
        // Add robots.txt modifications
        add_filter('robots_txt', [__CLASS__, 'modify_robots_txt'], 10, 2);
        
        // Add link rel canonical to .md pages if needed
        add_action('template_redirect', [__CLASS__, 'maybe_add_canonical_header']);
        
        // Add meta robots tag as backup for X-Robots-Tag header
        add_action('wp_head', [__CLASS__, 'maybe_add_noindex_meta']);
        
        // Add alternate link to head for Markdown version
        add_action('wp_head', [__CLASS__, 'add_markdown_alternate_link']);
        
        // Filter WordPress sitemaps to exclude .md URLs
        add_filter('wp_sitemaps_posts_query_args', [__CLASS__, 'exclude_md_from_sitemap']);
    }

    /**
     * Modify robots.txt content
     *
     * @param string $output Current robots.txt content
     * @param bool $public Whether the site is public or not
     * @return string Modified robots.txt content
     */
    public static function modify_robots_txt($output, $public) {
        if (!$public) {
            return $output;
        }

        $output .= "\n# Markdown Mirror (llms.txt) Rules\n";
        
        // Add llms.txt and .md references
        $output .= "Allow: /llms.txt\n";
        $output .= "Allow: /*.md$\n";
        
        // Add sitemap reference if WordPress generates one
        if (get_option('blog_public') && function_exists('get_sitemap_url')) {
            $output .= "\nSitemap: " . get_sitemap_url('index') . "\n";
        }

        return $output;
    }

    /**
     * Add alternate link for Markdown version in original content
     */
    public static function add_markdown_alternate_link() {
        // Don't add alternate link on .md pages
        if (get_query_var('md_mirror_markdown')) {
            return;
        }

        // Only add for single posts/pages that are included in Markdown mirror
        if (is_singular() && md_mirror_is_post_included(get_the_ID())) {
            $md_url = home_url(get_post_field('post_name', get_the_ID()) . '.md');
            echo '<link rel="alternate" type="text/markdown" href="' . esc_url($md_url) . '" />' . "\n";
        }
    }

    /**
     * Add canonical header for .md pages
     */
    public static function maybe_add_canonical_header() {
        global $wp_query;
        
        // Only proceed if this is a .md request
        if (!get_query_var('md_mirror_markdown')) {
            return;
        }
        
        $path = get_query_var('md_mirror_path');
        $url = home_url($path);
        $post_id = url_to_postid($url);
        
        if ($post_id) {
            // Get the original post URL
            $canonical_url = get_permalink($post_id);
            
            // Add both canonical and alternate headers
            header('Link: <' . esc_url($canonical_url) . '>; rel="canonical", <' . esc_url(home_url($path . '.md')) . '>; rel="alternate"; type="text/markdown"', false);
            
            // Add X-Robots-Tag if enabled
            if (get_option('md_mirror_noindex_md', 'yes') === 'yes') {
                header('X-Robots-Tag: noindex, follow', true);
            }
        }
    }

    /**
     * Add noindex meta tag as backup for X-Robots-Tag
     */
    public static function maybe_add_noindex_meta() {
        if (!get_query_var('md_mirror_markdown')) {
            return;
        }

        if (get_option('md_mirror_noindex_md', 'yes') === 'yes') {
            echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
        }
    }

    /**
     * Exclude .md URLs from WordPress sitemaps
     *
     * @param array $args Query arguments
     * @return array Modified query arguments
     */
    public static function exclude_md_from_sitemap($args) {
        // We don't need to modify anything as .md URLs are not regular posts
        // They're handled by our rewrite rules and won't appear in sitemaps
        return $args;
    }

    /**
     * Get SEO headers for Markdown pages
     */
    public static function get_markdown_headers($post_id) {
        $headers = [];
        
        // Set content type
        $headers['Content-Type'] = 'text/markdown; charset=utf-8';
        
        // Add noindex if enabled
        if (get_option('md_mirror_noindex_md', 'yes') === 'yes') {
            $headers['X-Robots-Tag'] = 'noindex, follow';
        }
        
        // Add canonical link to original post
        if ($post_id) {
            $canonical_url = get_permalink($post_id);
            $headers['Link'] = '<' . esc_url($canonical_url) . '>; rel="canonical"';
        }
        
        return $headers;
    }

    /**
     * Get SEO headers for llms.txt
     */
    public static function get_llms_headers() {
        return [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'X-Robots-Tag' => 'index, follow', // Always index llms.txt
        ];
    }

    /**
     * Get headers for taxonomy archive Markdown pages
     *
     * @param WP_Term $term The taxonomy term
     * @return array Headers to set
     */
    public static function get_taxonomy_markdown_headers($term) {
        $headers = [
            'Content-Type' => 'text/markdown; charset=' . get_option('blog_charset'),
            'X-Robots-Tag' => 'noindex, follow'
        ];
        
        // Get the HTML URL for this term
        $html_url = get_term_link($term);
        
        // Add canonical link to the HTML version
        $headers['Link'] = '<' . esc_url($html_url) . '>; rel="canonical"';
        
        // Add alternate link to self
        $md_url = home_url($term->taxonomy . '/' . $term->slug . '.md');
        $headers['Link'] .= ', <' . esc_url($md_url) . '>; rel="alternate"; type="text/markdown"';
        
        return $headers;
    }

    /**
     * Add alternate link to HTML pages
     */
    public static function add_alternate_link() {
        // Check if this is a taxonomy term archive
        if (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term && isset($term->taxonomy) && isset($term->slug)) {
                $enabled_types = get_option('md_mirror_post_types', ['post', 'page']);
                if (in_array('tax_' . $term->taxonomy, $enabled_types)) {
                    $md_url = home_url($term->taxonomy . '/' . $term->slug . '.md');
                    echo '<link rel="alternate" type="text/markdown" href="' . esc_url($md_url) . '" />' . "\n";
                }
            }
        }
        
        // Existing post/page handling
        global $post;
        if ($post && md_mirror_is_post_included($post)) {
            $md_url = home_url(get_post_field('post_name', $post) . '.md');
            echo '<link rel="alternate" type="text/markdown" href="' . esc_url($md_url) . '" />' . "\n";
        }
    }
} 