<?php
/**
 * Plugin Name: MPR Reviews
 * Version: 1.0.0
 * Description: A comprehensive review platform for businesses
 * Author: MyProtector
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MPR_PLUGIN_VERSION', '1.0.0');
define('MPR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'mpr_activation');

function mpr_activation() {
    update_option('mpr_reviews_per_page', 10);
    update_option('mpr_notify_admin', true);
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'mpr_deactivation');

function mpr_deactivation() {
    flush_rewrite_rules();
}

// Load main class
require_once MPR_PLUGIN_DIR . 'includes/class-mpr-loader.php';

// Initialize
add_action('plugins_loaded', array('MPR_Loader', 'get_instance'), 1);