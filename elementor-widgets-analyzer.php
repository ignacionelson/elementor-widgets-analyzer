<?php
/**
 * Plugin Name: Elementor Widgets Analyzer
 * Plugin URI: https://github.com/ignacionelson/elementor-widgets-analyzer
 * Description: Analyze and track Elementor widgets usage across all content types in your WordPress site. Built with Cursor.
 * Version: 1.0.0
 * Author: Ignacio Nelson
 * Author URI: https://www.subwaydesign.com.ar
 * License: GPL v2 or later
 * Text Domain: elementor-widgets-analyzer
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EWA_PLUGIN_FILE', __FILE__);
define('EWA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EWA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EWA_PLUGIN_VERSION', '1.0.0');

// Include required files
require_once EWA_PLUGIN_DIR . 'includes/class-elementor-widgets-analyzer.php';
require_once EWA_PLUGIN_DIR . 'includes/class-ewa-database.php';
require_once EWA_PLUGIN_DIR . 'includes/class-ewa-analyzer.php';
require_once EWA_PLUGIN_DIR . 'includes/class-ewa-admin.php';

// Initialize the plugin
function ewa_init() {
    // Check if Elementor is active
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . 
                 __('Elementor Widgets Analyzer requires Elementor to be installed and activated.', 'elementor-widgets-analyzer') . 
                 '</p></div>';
        });
        return;
    }
    
    // Initialize the main plugin class
    new Elementor_Widgets_Analyzer();
}
add_action('plugins_loaded', 'ewa_init');

// Activation hook
register_activation_hook(__FILE__, 'ewa_activate');
function ewa_activate() {
    require_once EWA_PLUGIN_DIR . 'includes/class-ewa-database.php';
    $database = new EWA_Database();
    $database->create_tables();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ewa_deactivate');
function ewa_deactivate() {
    // Cleanup if needed
} 