<?php
/**
 * MPR Reviews Uninstaller
 * 
 * This file runs when the plugin is deleted/uninstalled
 * Cleans up all plugin data from the database
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options = array(
    'mpr_reviews_per_page',
    'mpr_notify_admin',
    'mpr_admin_email',
    'mpr_require_registration',
    'mpr_db_version',
);

foreach ($options as $option) {
    delete_option($option);
}

// Drop custom tables
global $wpdb;
$table_name = $wpdb->prefix . 'mpr_rating_logs';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Remove all custom post types and their data
// This is optional - set to true if you want to remove all business and review posts
$remove_all_data = false;

if ($remove_all_data) {
    // Remove all businesses
    $businesses = get_posts(array(
        'post_type' => 'mpr_business',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    
    foreach ($businesses as $business_id) {
        wp_delete_post($business_id, true);
    }
    
    // Remove all reviews
    $reviews = get_posts(array(
        'post_type' => 'mpr_review',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    
    foreach ($reviews as $review_id) {
        wp_delete_post($review_id, true);
    }
    
    // Remove taxonomy terms
    $categories = get_terms(array(
        'taxonomy' => 'mpr_business_category',
        'hide_empty' => false,
    ));
    
    foreach ($categories as $category) {
        wp_delete_term($category->term_id, 'mpr_business_category');
    }
    
    $tags = get_terms(array(
        'taxonomy' => 'mpr_review_tag',
        'hide_empty' => false,
    ));
    
    foreach ($tags as $tag) {
        wp_delete_term($tag->term_id, 'mpr_review_tag');
    }
}

// Clear transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mpr_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mpr_%'");

// Flush rewrite rules
flush_rewrite_rules();