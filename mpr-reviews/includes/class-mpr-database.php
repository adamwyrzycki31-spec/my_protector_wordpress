<?php
/**
 * Database functions for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create custom database tables
 */
function mpr_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for rating logs (for tracking helpful votes)
    $table_name = $wpdb->prefix . 'mpr_rating_logs';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        review_id bigint(20) NOT NULL,
        user_id bigint(20) DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        rating_type varchar(20) DEFAULT 'helpful',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY review_id (review_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Store db version
    update_option('mpr_db_version', MPR_PLUGIN_VERSION);
}

/**
 * Drop custom database tables (used on uninstall)
 */
function mpr_drop_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mpr_rating_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    delete_option('mpr_db_version');
}

/**
 * Get rating logs for a review
 */
function mpr_get_rating_logs($review_id, $limit = 10) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mpr_rating_logs';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE review_id = %d ORDER BY created_at DESC LIMIT %d",
        $review_id,
        $limit
    ));
}

/**
 * Check if user has already rated a review
 */
function mpr_has_user_rated($review_id, $user_id = null, $ip_address = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mpr_rating_logs';
    
    $where = array('review_id' => $review_id);
    $where_clause = 'review_id = %d';
    $values = array($review_id);
    
    if ($user_id) {
        $where_clause .= ' AND user_id = %d';
        $values[] = $user_id;
    }
    
    if ($ip_address) {
        $where_clause .= ' AND ip_address = %s';
        $values[] = $ip_address;
    }
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
        $values
    ));
    
    return $result > 0;
}

/**
 * Log a rating
 */
function mpr_log_rating($review_id, $user_id = null, $ip_address = null, $rating_type = 'helpful') {
    global $wpdb;
    
    // Check if already rated
    if (mpr_has_user_rated($review_id, $user_id, $ip_address)) {
        return false;
    }
    
    $table_name = $wpdb->prefix . 'mpr_rating_logs';
    
    $wpdb->insert(
        $table_name,
        array(
            'review_id' => $review_id,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'rating_type' => $rating_type,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s')
    );
    
    return true;
}

/**
 * Get businesses with minimum rating
 */
function mpr_get_businesses_by_rating($min_rating = 4, $limit = 10) {
    $args = array(
        'post_type' => 'mpr_business',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'mpr_business_average_rating',
                'value' => $min_rating,
                'compare' => '>=',
                'type' => 'DECIMAL',
            ),
        ),
        'orderby' => 'meta_value_num',
        'meta_key' => 'mpr_business_average_rating',
        'order' => 'DESC',
    );
    
    return new WP_Query($args);
}

/**
 * Get review statistics for admin
 */
function mpr_get_review_stats($days = 30) {
    global $wpdb;
    
    $start_date = date('Y-m-d', strtotime("-{$days} days"));
    
    $stats = array();
    
    // Total reviews
    $stats['total'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mpr_review' AND post_status = 'publish'"
    );
    
    // Pending reviews
    $stats['pending'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mpr_review' AND post_status = 'pending'"
    );
    
    // Reviews in last 30 days
    $stats['recent'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'mpr_review' 
         AND post_status IN ('publish', 'pending') 
         AND post_date >= %s",
        $start_date
    ));
    
    // Average rating
    $stats['average_rating'] = (float) $wpdb->get_var(
        "SELECT AVG(meta_value) FROM {$wpdb->postmeta} 
         WHERE meta_key = 'mpr_review_rating' 
         AND post_id IN (
             SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mpr_review' AND post_status = 'publish'
         )"
    );
    
    // Rating distribution
    for ($i = 1; $i <= 5; $i++) {
        $stats['rating_distribution'][$i] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = 'mpr_review_rating' 
             AND meta_value = %d
             AND post_id IN (
                 SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mpr_review' AND post_status = 'publish'
             )",
            $i
        ));
    }
    
    return $stats;
}

/**
 * Export reviews to CSV (admin function)
 */
function mpr_export_reviews_csv($args = array()) {
    $defaults = array(
        'status' => 'all',
        'business_id' => 0,
        'start_date' => '',
        'end_date' => '',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $query_args = array(
        'post_type' => 'mpr_review',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    
    if ($args['business_id'] > 0) {
        $query_args['meta_query'] = array(
            array(
                'key' => 'mpr_business_id',
                'value' => $args['business_id'],
            ),
        );
    }
    
    if (!empty($args['start_date'])) {
        $query_args['date_query'] = array(
            'after' => $args['start_date'],
        );
    }
    
    if (!empty($args['end_date'])) {
        $query_args['date_query'] = array(
            'before' => $args['end_date'],
        );
    }
    
    $reviews = new WP_Query($query_args);
    
    if (!$reviews->have_posts()) {
        return false;
    }
    
    // Create CSV
    $csv_output = "ID,Title,Content,Rating,Reviewer Name,Reviewer Email,Business,Status,Date\n";
    
    while ($reviews->have_posts()) {
        $reviews->the_post();
        
        $business_id = get_post_meta(get_the_ID(), 'mpr_business_id', true);
        $business_title = $business_id ? get_the_title($business_id) : '';
        
        $csv_output .= sprintf(
            '"%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
            get_the_ID(),
            str_replace('"', '""', get_the_title()),
            str_replace('"', '""', get_the_content()),
            get_post_meta(get_the_ID(), 'mpr_review_rating', true),
            str_replace('"', '""', get_post_meta(get_the_ID(), 'mpr_reviewer_name', true)),
            str_replace('"', '""', get_post_meta(get_the_ID(), 'mpr_reviewer_email', true)),
            str_replace('"', '""', $business_title),
            get_post_meta(get_the_ID(), 'mpr_approval_status', true),
            get_the_date('Y-m-d H:i:s')
        );
    }
    
    wp_reset_postdata();
    
    return $csv_output;
}