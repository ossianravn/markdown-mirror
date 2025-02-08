<?php
namespace MarkdownMirror\Admin;

/**
 * Class Meta_Box
 * Handles the per-post meta box for controlling Markdown inclusion
 */
class Meta_Box {
    /**
     * Meta key for storing the inclusion setting
     */
    const META_KEY = '_md_mirror_include';

    /**
     * Initialize the meta box
     */
    public static function init() {
        // Add meta box to eligible post types
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_box']);
        
        // Save meta box data
        add_action('save_post', [__CLASS__, 'save_meta_box']);
    }

    /**
     * Add meta box to eligible post types
     */
    public static function add_meta_box() {
        // Get enabled post types from settings
        $post_types = get_option('md_mirror_post_types', ['post', 'page']);
        
        // Add meta box to each enabled post type
        foreach ($post_types as $post_type) {
            add_meta_box(
                'md_mirror_meta_box',
                __('Markdown Mirror', 'markdown-mirror'),
                [__CLASS__, 'render_meta_box'],
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render meta box content
     * 
     * @param \WP_Post $post The post object
     */
    public static function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('md_mirror_meta_box', 'md_mirror_meta_box_nonce');

        // Get current value (defaults to 'yes')
        $value = get_post_meta($post->ID, self::META_KEY, true);
        if (empty($value)) {
            $value = 'yes'; // Default to included
        }

        // Get the .md URL for this post
        $md_url = home_url(get_post_field('post_name', $post) . '.md');
        ?>
        <p>
            <label>
                <input type="radio" 
                       name="md_mirror_include" 
                       value="yes" 
                       <?php checked($value, 'yes'); ?>>
                <?php _e('Include in Markdown Mirror', 'markdown-mirror'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="radio" 
                       name="md_mirror_include" 
                       value="no" 
                       <?php checked($value, 'no'); ?>>
                <?php _e('Exclude from Markdown Mirror', 'markdown-mirror'); ?>
            </label>
        </p>

        <?php if ($value === 'yes' && get_post_status($post) === 'publish'): ?>
            <p class="description">
                <?php _e('Markdown URL:', 'markdown-mirror'); ?><br>
                <a href="<?php echo esc_url($md_url); ?>" 
                   target="_blank" 
                   class="md-mirror-preview">
                    <?php echo esc_html($md_url); ?>
                </a>
            </p>
        <?php endif; ?>

        <p class="description">
            <?php _e('Control whether this content should be available in Markdown format and included in llms.txt.', 'markdown-mirror'); ?>
        </p>
        <?php
    }

    /**
     * Save meta box data
     * 
     * @param int $post_id The post ID
     */
    public static function save_meta_box($post_id) {
        // Security checks
        if (!isset($_POST['md_mirror_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['md_mirror_meta_box_nonce'], 'md_mirror_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if ('page' === $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Save the data
        if (isset($_POST['md_mirror_include'])) {
            $value = sanitize_text_field($_POST['md_mirror_include']);
            if (in_array($value, ['yes', 'no'])) {
                update_post_meta($post_id, self::META_KEY, $value);
            }
        }
    }

    /**
     * Check if a post should be included in Markdown mirror
     * 
     * @param int|\WP_Post $post Post ID or object
     * @return bool Whether the post should be included
     */
    public static function is_post_included($post) {
        $post = get_post($post);
        if (!$post) {
            return false;
        }

        // Check if post type is enabled
        $enabled_types = get_option('md_mirror_post_types', ['post', 'page']);
        if (!in_array($post->post_type, $enabled_types)) {
            return false;
        }

        // Check individual post setting
        $include = get_post_meta($post->ID, self::META_KEY, true);
        return $include !== 'no'; // Default to yes if not set
    }
} 