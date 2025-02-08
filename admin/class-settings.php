<?php
namespace MarkdownMirror\Admin;

/**
 * Class Settings
 * Handles plugin settings page and options
 */
class Settings {
    /**
     * Initialize the settings
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_options_page(
            'Markdown Mirror Settings',
            'Markdown Mirror',
            'manage_options',
            'markdown-mirror',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Register settings and fields
     */
    public static function register_settings() {
        // General Settings Section
        add_settings_section(
            'md_mirror_general',
            'General Settings',
            [__CLASS__, 'render_general_section'],
            'markdown-mirror'
        );

        // Post Types Setting
        register_setting('markdown-mirror', 'md_mirror_post_types');
        add_settings_field(
            'md_mirror_post_types',
            'Include Post Types',
            [__CLASS__, 'render_post_types_field'],
            'markdown-mirror',
            'md_mirror_general'
        );

        // Include Taxonomies Setting
        register_setting('markdown-mirror', 'md_mirror_include_taxonomies');
        add_settings_field(
            'md_mirror_include_taxonomies',
            'Include Categories & Tags',
            [__CLASS__, 'render_include_taxonomies_field'],
            'markdown-mirror',
            'md_mirror_general'
        );

        // Custom Summary Setting
        register_setting('markdown-mirror', 'md_mirror_custom_summary');
        add_settings_field(
            'md_mirror_custom_summary',
            'Custom llms.txt Summary',
            [__CLASS__, 'render_custom_summary_field'],
            'markdown-mirror',
            'md_mirror_general'
        );

        // Context Files Section
        add_settings_section(
            'md_mirror_context',
            'Context Files Settings',
            [__CLASS__, 'render_context_section'],
            'markdown-mirror'
        );

        // Basic Context Settings
        register_setting('markdown-mirror', 'md_mirror_ctx_include_meta');
        add_settings_field(
            'md_mirror_ctx_include_meta',
            'Include in Basic Context',
            [__CLASS__, 'render_ctx_include_meta_field'],
            'markdown-mirror',
            'md_mirror_context'
        );

        // Full Context Settings
        register_setting('markdown-mirror', 'md_mirror_ctx_full_depth');
        add_settings_field(
            'md_mirror_ctx_full_depth',
            'Full Context Link Depth',
            [__CLASS__, 'render_ctx_full_depth_field'],
            'markdown-mirror',
            'md_mirror_context'
        );

        // Cache Settings Section
        add_settings_section(
            'md_mirror_cache',
            'Cache Settings',
            [__CLASS__, 'render_cache_section'],
            'markdown-mirror'
        );

        // Cache Duration Setting
        register_setting('markdown-mirror', 'md_mirror_cache_duration');
        add_settings_field(
            'md_mirror_cache_duration',
            'Cache Duration',
            [__CLASS__, 'render_cache_duration_field'],
            'markdown-mirror',
            'md_mirror_cache'
        );
    }

    /**
     * Render the settings page
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('markdown-mirror');
                do_settings_sections('markdown-mirror');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the general section description
     */
    public static function render_general_section($args) {
        ?>
        <p>Configure which content types to include and how they should be presented.</p>
        <?php
    }

    /**
     * Render the context section description
     */
    public static function render_context_section($args) {
        ?>
        <p>Control what content is included in the expanded context files (llms-ctx.txt and llms-ctx-full.txt).</p>
        <?php
    }

    /**
     * Render the cache section description
     */
    public static function render_cache_section($args) {
        ?>
        <p>Configure caching behavior for improved performance.</p>
        <?php
    }

    /**
     * Render the post types field
     */
    public static function render_post_types_field() {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected = get_option('md_mirror_post_types', ['post', 'page']);
        
        // Add taxonomies as content types
        echo '<h4>Content Types</h4>';
        foreach ($post_types as $post_type) {
            ?>
            <label>
                <input type="checkbox" name="md_mirror_post_types[]" 
                       value="<?php echo esc_attr($post_type->name); ?>"
                       <?php checked(in_array($post_type->name, $selected)); ?>>
                <?php echo esc_html($post_type->label); ?>
            </label><br>
            <?php
        }

        echo '<h4>Taxonomy Archives</h4>';
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        foreach ($taxonomies as $taxonomy) {
            ?>
            <label>
                <input type="checkbox" name="md_mirror_post_types[]" 
                       value="tax_<?php echo esc_attr($taxonomy->name); ?>"
                       <?php checked(in_array('tax_' . $taxonomy->name, $selected)); ?>>
                <?php echo esc_html($taxonomy->label); ?> Archives
            </label><br>
            <?php
        }
        echo '<p class="description">Select which content types and taxonomy archives should be available as Markdown.</p>';
    }

    /**
     * Render the custom summary field
     */
    public static function render_custom_summary_field() {
        $value = get_option('md_mirror_custom_summary', '');
        ?>
        <textarea name="md_mirror_custom_summary" rows="3" class="large-text"><?php 
            echo esc_textarea($value); 
        ?></textarea>
        <p class="description">Custom summary to display at the top of llms.txt. If empty, your site's tagline will be used.</p>
        <?php
    }

    /**
     * Render the context include meta field
     */
    public static function render_ctx_include_meta_field() {
        $options = get_option('md_mirror_ctx_include_meta', [
            'title' => true,
            'excerpt' => true,
            'meta_desc' => true,
            'content' => false
        ]);
        ?>
        <label>
            <input type="checkbox" name="md_mirror_ctx_include_meta[title]" 
                   value="1" <?php checked(!empty($options['title'])); ?>>
            Post Titles
        </label><br>
        <label>
            <input type="checkbox" name="md_mirror_ctx_include_meta[excerpt]" 
                   value="1" <?php checked(!empty($options['excerpt'])); ?>>
            Post Excerpts
        </label><br>
        <label>
            <input type="checkbox" name="md_mirror_ctx_include_meta[meta_desc]" 
                   value="1" <?php checked(!empty($options['meta_desc'])); ?>>
            Meta Descriptions
        </label><br>
        <label>
            <input type="checkbox" name="md_mirror_ctx_include_meta[content]" 
                   value="1" <?php checked(!empty($options['content'])); ?>>
            Full Content
        </label>
        <p class="description">Select what content to include in the basic context file (llms-ctx.txt).</p>
        <?php
    }

    /**
     * Render the context full depth field
     */
    public static function render_ctx_full_depth_field() {
        $value = get_option('md_mirror_ctx_full_depth', 1);
        ?>
        <select name="md_mirror_ctx_full_depth">
            <option value="1" <?php selected($value, 1); ?>>1 Level Deep</option>
            <option value="2" <?php selected($value, 2); ?>>2 Levels Deep</option>
            <option value="3" <?php selected($value, 3); ?>>3 Levels Deep</option>
            <option value="-1" <?php selected($value, -1); ?>>Unlimited</option>
        </select>
        <p class="description">How many levels of linked content to include in the full context file (llms-ctx-full.txt).</p>
        <?php
    }

    /**
     * Render the cache duration field
     */
    public static function render_cache_duration_field() {
        $value = get_option('md_mirror_cache_duration', DAY_IN_SECONDS);
        ?>
        <select name="md_mirror_cache_duration">
            <option value="3600" <?php selected($value, HOUR_IN_SECONDS); ?>>1 Hour</option>
            <option value="86400" <?php selected($value, DAY_IN_SECONDS); ?>>1 Day</option>
            <option value="604800" <?php selected($value, WEEK_IN_SECONDS); ?>>1 Week</option>
        </select>
        <p class="description">How long to cache converted content.</p>
        <?php
    }

    /**
     * Render the include taxonomies field
     */
    public static function render_include_taxonomies_field() {
        $value = get_option('md_mirror_include_taxonomies', true);
        ?>
        <label>
            <input type="checkbox" name="md_mirror_include_taxonomies" 
                   value="1" <?php checked($value); ?>>
            Include category and tag pages in context files
        </label>
        <p class="description">When enabled, context files will include sections for categories and tags with their associated posts.</p>
        <?php
    }
} 