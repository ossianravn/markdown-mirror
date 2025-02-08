<?php
namespace MarkdownMirror\Admin;

use MarkdownMirror\Tests\Test;

/**
 * Class Test_Page
 * Handles the test page in WordPress admin
 */
class Test_Page {
    /**
     * Initialize the test page
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_test_page']);
    }

    /**
     * Add the test page to WordPress admin
     */
    public static function add_test_page() {
        add_submenu_page(
            'tools.php',
            __('Markdown Mirror Tests', 'markdown-mirror'),
            __('MD Mirror Tests', 'markdown-mirror'),
            'manage_options',
            'markdown-mirror-tests',
            [__CLASS__, 'render_test_page']
        );
    }

    /**
     * Render the test page
     */
    public static function render_test_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $test_results = null;
        if (isset($_POST['run_tests']) && check_admin_referer('md_mirror_run_tests')) {
            $test_results = Test::run_tests();
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Markdown Mirror Tests', 'markdown-mirror'); ?></h1>
            
            <p><?php _e('This page allows you to run tests to verify the plugin functionality.', 'markdown-mirror'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('md_mirror_run_tests'); ?>
                <p>
                    <input type="submit" 
                           name="run_tests" 
                           class="button button-primary" 
                           value="<?php esc_attr_e('Run Tests', 'markdown-mirror'); ?>">
                </p>
            </form>

            <?php if ($test_results): ?>
                <h2><?php _e('Test Results', 'markdown-mirror'); ?></h2>
                
                <?php foreach ($test_results as $section => $tests): ?>
                    <h3><?php echo esc_html(ucwords(str_replace('_', ' ', $section))); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Test', 'markdown-mirror'); ?></th>
                                <th><?php _e('Result', 'markdown-mirror'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tests as $test => $result): ?>
                                <tr>
                                    <td><?php echo esc_html(ucwords(str_replace('_', ' ', $test))); ?></td>
                                    <td>
                                        <?php if ($result): ?>
                                            <span class="dashicons dashicons-yes" style="color: green;"></span>
                                            <?php _e('Pass', 'markdown-mirror'); ?>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-no" style="color: red;"></span>
                                            <?php _e('Fail', 'markdown-mirror'); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>

                <p>
                    <em><?php _e('Tests completed. Please review the results above.', 'markdown-mirror'); ?></em>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
} 