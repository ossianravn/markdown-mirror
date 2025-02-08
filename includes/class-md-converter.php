<?php
namespace MarkdownMirror;

use League\HTMLToMarkdown\HtmlConverter;

/**
 * Class MD_Converter
 * Handles conversion of HTML content to Markdown format.
 */
class MD_Converter {
    /**
     * @var HtmlConverter
     */
    private $converter;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
            'remove_nodes' => 'script style',
            'preserve_comments' => false,
        ]);
    }

    /**
     * Converts HTML content into Markdown.
     *
     * @param string $html The HTML content to convert.
     * @return string The converted Markdown content.
     */
    public function convert( $html ) {
        // Apply content filters to expand shortcodes etc.
        $html = apply_filters( 'md_mirror_pre_convert', $html );
        
        // Convert to Markdown
        $markdown = $this->converter->convert( $html );
        
        // Allow post-processing of Markdown
        $markdown = apply_filters( 'md_mirror_post_convert', $markdown, $html );
        
        return $markdown;
    }

    /**
     * Converts a WordPress post to Markdown.
     *
     * @param int|\WP_Post $post Post ID or post object.
     * @return string The post content as Markdown.
     */
    public function convert_post( $post ) {
        $post = get_post( $post );
        if ( ! $post ) {
            return '';
        }

        // Get the processed content
        $content = apply_filters( 'the_content', $post->post_content );
        
        // Convert to Markdown
        $markdown = $this->convert( $content );

        // Add title as H1
        $title = get_the_title( $post );
        if ( ! empty( $title ) ) {
            $markdown = "# {$title}\n\n" . $markdown;
        }

        return $markdown;
    }
} 