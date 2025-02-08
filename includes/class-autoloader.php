<?php
namespace MarkdownMirror;

/**
 * Class Autoloader
 * Handles autoloading of plugin classes
 */
class Autoloader {
    /**
     * Register the autoloader
     */
    public static function register() {
        error_log('Markdown Mirror: Registering autoloader');
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload callback
     *
     * @param string $class_name Full class name including namespace
     */
    public static function autoload($class_name) {
        error_log('Markdown Mirror: Attempting to autoload: ' . $class_name);
        
        // Only handle our namespace
        if (strpos($class_name, 'MarkdownMirror\\') !== 0) {
            error_log('Markdown Mirror: Skipping autoload for: ' . $class_name . ' (not in our namespace)');
            return;
        }

        // Remove namespace from class name
        $class_name = str_replace('MarkdownMirror\\', '', $class_name);
        error_log('Markdown Mirror: Class name without namespace: ' . $class_name);

        // Convert class name format to file name format
        $file_name = 'class-' . str_replace(['_', '\\'], ['-', '/'], strtolower($class_name)) . '.php';
        error_log('Markdown Mirror: Looking for file: ' . $file_name);

        // Handle subdirectories in namespace
        if (strpos($class_name, 'Admin\\') === 0) {
            $file_name = str_replace('admin/', '', $file_name);
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'admin/' . $file_name;
        } elseif (strpos($class_name, 'Tests\\') === 0) {
            $file_name = str_replace('tests/', '', $file_name);
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'tests/' . $file_name;
        } else {
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/' . $file_name;
        }

        error_log('Markdown Mirror: Full file path: ' . $file_path);

        // Include the file if it exists
        if (file_exists($file_path)) {
            error_log('Markdown Mirror: Loading file: ' . $file_path);
            require_once $file_path;
        } else {
            error_log('Markdown Mirror: File not found: ' . $file_path);
        }
    }
} 