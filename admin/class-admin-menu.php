<?php
namespace MarkdownMirror\Admin;

/**
 * Class Admin_Menu
 * Handles the WordPress admin interface for the plugin
 */
class Admin_Menu {
    /**
     * Initialize the admin menu
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_styles']);
    }

    /**
     * Add the settings page to the WordPress admin menu
     */
    public static function add_settings_page() {
        add_options_page(
            __('Markdown Mirror Settings', 'markdown-mirror'),
            __('Markdown Mirror', 'markdown-mirror'),
            'manage_options',
            'markdown-mirror-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public static function register_settings() {
        // Register settings group
        register_setting('md_mirror_settings_group', 'md_mirror_post_types');
        register_setting('md_mirror_settings_group', 'md_mirror_custom_summary');
        register_setting('md_mirror_settings_group', 'md_mirror_noindex_md');
        register_setting('md_mirror_settings_group', 'md_mirror_cache_duration');

        // Add settings sections
        add_settings_section(
            'md_mirror_main_section',
            __('General Settings', 'markdown-mirror'),
            [__CLASS__, 'render_section_description'],
            'markdown-mirror-settings'
        );

        add_settings_section(
            'md_mirror_seo_section',
            __('SEO Settings', 'markdown-mirror'),
            [__CLASS__, 'render_seo_section_description'],
            'markdown-mirror-settings'
        );

        add_settings_section(
            'md_mirror_cache_section',
            __('Cache Settings', 'markdown-mirror'),
            [__CLASS__, 'render_cache_section_description'],
            'markdown-mirror-settings'
        );
    }

    /**
     * Enqueue admin styles
     */
    public static function enqueue_admin_styles($hook) {
        if ('settings_page_markdown-mirror-settings' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'md-mirror-admin',
            MD_MIRROR_PLUGIN_URL . 'admin/css/admin-styles.css',
            [],
            MD_MIRROR_VERSION
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
        <div class="wrap markdown-mirror-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('md_mirror_settings_group');
                do_settings_sections('markdown-mirror-settings');
                submit_button();
                ?>
            </form>

            <div class="quick-links">
                <h2><?php _e('Quick Links', 'markdown-mirror'); ?></h2>
                <p>
                    <a href="<?php echo esc_url(home_url('llms.txt')); ?>" 
                       target="_blank" 
                       class="button">
                        <?php _e('View llms.txt', 'markdown-mirror'); ?>
                    </a>
                    <?php
                    // Get a sample post to show .md link
                    $sample_post = get_posts(['numberposts' => 1]);
                    if (!empty($sample_post)) {
                        $md_url = home_url(get_post_field('post_name', $sample_post[0]) . '.md');
                        ?>
                        <a href="<?php echo esc_url($md_url); ?>" 
                           target="_blank" 
                           class="button">
                            <?php _e('View Sample .md File', 'markdown-mirror'); ?>
                        </a>
                        <?php
                    }
                    ?>
                </p>
            </div>

            <div class="status-info">
                <h2><?php _e('Plugin Status', 'markdown-mirror'); ?></h2>
                <?php
                $rules = get_option('rewrite_rules');
                $has_rules = isset($rules['llms\.txt$']);
                ?>
                <p>
                    <strong><?php _e('Rewrite Rules:', 'markdown-mirror'); ?></strong>
                    <span class="status-indicator <?php echo $has_rules ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $has_rules ? __('Active', 'markdown-mirror') : __('Not Active', 'markdown-mirror'); ?>
                    </span>
                    <?php if (!$has_rules): ?>
                        <br>
                        <em><?php _e('Try saving your permalinks to refresh rewrite rules.', 'markdown-mirror'); ?></em>
                    <?php endif; ?>
                </p>
                <p>
                    <strong><?php _e('Cache Status:', 'markdown-mirror'); ?></strong>
                    <?php
                    $cache_enabled = class_exists('\MarkdownMirror\Cache');
                    ?>
                    <span class="status-indicator <?php echo $cache_enabled ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $cache_enabled ? __('Enabled', 'markdown-mirror') : __('Disabled', 'markdown-mirror'); ?>
                    </span>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the main section description
     */
    public static function render_section_description() {
        echo '<p>' . esc_html__('Configure which content types should be available in Markdown format.', 'markdown-mirror') . '</p>';
        
        // Post Types Field
        $all_post_types = get_post_types(['public' => true], 'objects');
        $selected_types = (array) get_option('md_mirror_post_types', ['post', 'page']);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Include Post Types', 'markdown-mirror'); ?></th>
                <td>
                    <?php foreach($all_post_types as $type): ?>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" 
                                   name="md_mirror_post_types[]" 
                                   value="<?php echo esc_attr($type->name); ?>"
                                   <?php checked(in_array($type->name, $selected_types)); ?>>
                            <?php echo esc_html($type->label); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Custom llms.txt Summary', 'markdown-mirror'); ?></th>
                <td>
                    <?php $summary = get_option('md_mirror_custom_summary', ''); ?>
                    <textarea name="md_mirror_custom_summary" 
                              rows="3" 
                              class="large-text"
                              placeholder="<?php esc_attr_e('Enter a custom summary for your llms.txt file. If left empty, your site tagline will be used.', 'markdown-mirror'); ?>"><?php echo esc_textarea($summary); ?></textarea>
                    <p class="description">
                        <?php _e('This text appears at the top of your llms.txt file. Markdown formatting is supported.', 'markdown-mirror'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the SEO section description
     */
    public static function render_seo_section_description() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('NoIndex Markdown URLs', 'markdown-mirror'); ?></th>
                <td>
                    <?php $noindex = get_option('md_mirror_noindex_md', 'yes'); ?>
                    <select name="md_mirror_noindex_md">
                        <option value="yes" <?php selected($noindex, 'yes'); ?>>
                            <?php _e('Yes - Prevent search engines from indexing .md URLs', 'markdown-mirror'); ?>
                        </option>
                        <option value="no" <?php selected($noindex, 'no'); ?>>
                            <?php _e('No - Allow search engines to index .md URLs', 'markdown-mirror'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Recommended: Yes - This prevents duplicate content issues in search engines.', 'markdown-mirror'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the cache section description
     */
    public static function render_cache_section_description() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Cache Duration', 'markdown-mirror'); ?></th>
                <td>
                    <?php $duration = get_option('md_mirror_cache_duration', DAY_IN_SECONDS); ?>
                    <select name="md_mirror_cache_duration">
                        <option value="3600" <?php selected($duration, 3600); ?>>
                            <?php _e('1 Hour', 'markdown-mirror'); ?>
                        </option>
                        <option value="86400" <?php selected($duration, 86400); ?>>
                            <?php _e('1 Day', 'markdown-mirror'); ?>
                        </option>
                        <option value="604800" <?php selected($duration, 604800); ?>>
                            <?php _e('1 Week', 'markdown-mirror'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('How long to cache the Markdown versions of your content.', 'markdown-mirror'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
} 