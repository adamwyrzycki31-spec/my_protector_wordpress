<?php
/**
 * Plugin Name: MPR Reviews
 * Plugin URI: https://myprotector.org/mpr-reviews
 * Description: A comprehensive review platform for businesses
 * Version: 1.0.0
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

// Register activation hook
register_activation_hook(__FILE__, 'mpr_activate');

function mpr_activate() {
    // Just set a flag option, nothing more
    update_option('mpr_activated', time());
    flush_rewrite_rules();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'mpr_deactivate');

function mpr_deactivate() {
    flush_rewrite_rules();
}

// Register custom post type on init
add_action('init', 'mpr_register_post_types', 0);

function mpr_register_post_types() {
    register_post_type('mpr_business', array(
        'labels' => array(
            'name' => __('Businesses', 'mpr-reviews'),
            'singular_name' => __('Business', 'mpr-reviews'),
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'business'),
    ));

    register_post_type('mpr_review', array(
        'labels' => array(
            'name' => __('Reviews', 'mpr-reviews'),
            'singular_name' => __('Review', 'mpr-reviews'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'review'),
    ));

    register_taxonomy('mpr_category', 'mpr_business', array(
        'labels' => array(
            'name' => __('Categories', 'mpr-reviews'),
            'singular_name' => __('Category', 'mpr-reviews'),
        ),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'business-category'),
    ));
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'mpr_enqueue_assets');

function mpr_enqueue_assets() {
    wp_enqueue_style(
        'mpr-styles',
        MPR_PLUGIN_URL . 'assets/css/styles.css',
        array(),
        MPR_PLUGIN_VERSION
    );
}