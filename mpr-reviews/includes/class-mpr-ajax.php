<?php
/**
 * AJAX handlers for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Ajax {
    
    /**
     * Handle review submission
     */
    public static function handle_review_submission($is_ajax = false) {
        // Verify nonce
        if (!isset($_POST['mpr_review_nonce']) || 
            !wp_verify_nonce($_POST['mpr_review_nonce'], 'mpr_review_action')) {
            if ($is_ajax) {
                wp_send_json_error(array('message' => __('Security verification failed.', 'mpr-reviews')));
            }
            wp_redirect(add_query_arg('mpr_error', 'security', wp_get_referer()));
            exit;
        }
        
        // Check honeypot
        if (!empty($_POST['mpr_hp_field'])) {
            if ($is_ajax) {
                wp_send_json_success(array('message' => __('Thank you for your review!', 'mpr-reviews')));
            }
            wp_redirect(add_query_arg('mpr_success', '1', get_permalink($_POST['mpr_business_id'])));
            exit;
        }
        
        // Validate required fields
        $errors = self::validate_review_fields();
        
        if (!empty($errors)) {
            if ($is_ajax) {
                wp_send_json_error(array('message' => implode(', ', $errors)));
            }
            wp_redirect(add_query_arg('mpr_error', 'validation', wp_get_referer()));
            exit;
        }
        
        // Sanitize inputs
        $title = MPR_Security::sanitize_text($_POST['mpr_review_title']);
        $content = MPR_Security::sanitize_textarea($_POST['mpr_review_content']);
        $rating = MPR_Security::sanitize_rating($_POST['mpr_review_rating']);
        $reviewer_name = MPR_Security::sanitize_text($_POST['mpr_reviewer_name']);
        $reviewer_email = MPR_Security::sanitize_email_input($_POST['mpr_reviewer_email']);
        $business_id = absint($_POST['mpr_business_id']);
        
        // Check for spam
        if (MPR_Security::is_spam_content($title) || MPR_Security::is_spam_content($content)) {
            if ($is_ajax) {
                wp_send_json_error(array('message' => __('Your review appears to contain spam.', 'mpr-reviews')));
            }
            wp_redirect(add_query_arg('mpr_error', 'spam', wp_get_referer()));
            exit;
        }
        
        // Check if user is registered (if required)
        if (get_option('mpr_require_registration', false) && !is_user_logged_in()) {
            if ($is_ajax) {
                wp_send_json_error(array('message' => __('You must be logged in to submit a review.', 'mpr-reviews')));
            }
            wp_redirect(add_query_arg('mpr_error', 'login_required', wp_get_referer()));
            exit;
        }
        
        // Create the review post
        $review_data = array(
            'post_title' => sanitize_text_field($title),
            'post_content' => $content,
            'post_type' => 'mpr_review',
            'post_status' => 'pending',
            'post_author' => is_user_logged_in() ? get_current_user_id() : 0,
        );
        
        $review_id = wp_insert_post($review_data);
        
        if (is_wp_error($review_id)) {
            if ($is_ajax) {
                wp_send_json_error(array('message' => __('Failed to submit review. Please try again.', 'mpr-reviews')));
            }
            wp_redirect(add_query_arg('mpr_error', 'failed', wp_get_referer()));
            exit;
        }
        
        // Save meta fields
        update_post_meta($review_id, 'mpr_review_rating', $rating);
        update_post_meta($review_id, 'mpr_reviewer_name', $reviewer_name);
        update_post_meta($review_id, 'mpr_reviewer_email', $reviewer_email);
        update_post_meta($review_id, 'mpr_business_id', $business_id);
        update_post_meta($review_id, 'mpr_approval_status', 'pending');
        update_post_meta($review_id, 'mpr_helpful_count', 0);
        update_post_meta($review_id, 'mpr_submission_date', current_time('mysql'));
        
        // Send notification to admin
        self::send_admin_notification($review_id, $business_id, $reviewer_name, $reviewer_email, $rating);
        
        if ($is_ajax) {
            wp_send_json_success(array(
                'message' => __('Thank you! Your review has been submitted and is pending approval.', 'mpr-reviews'),
            ));
        }
        
        // Redirect with success message
        wp_redirect(add_query_arg('mpr_success', 'submitted', get_permalink($business_id)));
        exit;
    }
    
    /**
     * Validate review fields
     */
    private static function validate_review_fields() {
        $errors = array();
        
        // Title
        if (empty($_POST['mpr_review_title']) || strlen(trim($_POST['mpr_review_title'])) < 3) {
            $errors[] = __('Review title must be at least 3 characters.', 'mpr-reviews');
        }
        
        // Content
        if (empty($_POST['mpr_review_content']) || strlen(trim($_POST['mpr_review_content'])) < 10) {
            $errors[] = __('Review content must be at least 10 characters.', 'mpr-reviews');
        }
        
        // Rating
        if (!isset($_POST['mpr_review_rating']) || !in_array($_POST['mpr_review_rating'], array('1', '2', '3', '4', '5'))) {
            $errors[] = __('Please select a valid rating.', 'mpr-reviews');
        }
        
        // Reviewer name
        if (empty($_POST['mpr_reviewer_name']) || strlen(trim($_POST['mpr_reviewer_name'])) < 2) {
            $errors[] = __('Please enter your name.', 'mpr-reviews');
        }
        
        // Reviewer email
        if (empty($_POST['mpr_reviewer_email']) || !MPR_Security::is_valid_email($_POST['mpr_reviewer_email'])) {
            $errors[] = __('Please enter a valid email address.', 'mpr-reviews');
        }
        
        // Business ID
        if (empty($_POST['mpr_business_id']) || !get_post_type($_POST['mpr_business_id'])) {
            $errors[] = __('Invalid business.', 'mpr-reviews');
        }
        
        return $errors;
    }
    
    /**
     * Send admin notification email
     */
    private static function send_admin_notification($review_id, $business_id, $reviewer_name, $reviewer_email, $rating) {
        if (!get_option('mpr_notify_admin', true)) {
            return;
        }
        
        $admin_email = get_option('mpr_admin_email', get_option('admin_email'));
        $business_title = get_the_title($business_id);
        
        $subject = sprintf(__('[%s] New Review Submitted for %s', 'mpr-reviews'), 
            get_bloginfo('name'), 
            $business_title
        );
        
        $message = sprintf(
            __('A new review has been submitted and is pending approval.

Business: %s
Reviewer: %s
Email: %s
Rating: %d/5

Title: %s

You can approve or reject this review in the WordPress admin.

View all pending reviews: %s', 'mpr-reviews'),
            $business_title,
            $reviewer_name,
            $reviewer_email,
            $rating,
            sanitize_text_field($_POST['mpr_review_title']),
            admin_url('edit.php?post_type=mpr_review&post_status=pending')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Handle business search
     */
    public static function handle_business_search() {
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
        $min_rating = isset($_POST['min_rating']) ? absint($_POST['min_rating']) : 0;
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = get_option('mpr_reviews_per_page', 10);
        
        $args = array(
            'post_type' => 'mpr_business',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        );
        
        // Search query
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }
        
        // Category filter
        if ($category > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mpr_business_category',
                    'field' => 'term_id',
                    'terms' => $category,
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $business_id = get_the_ID();
                $meta = MPR_Meta_Fields::get_business_meta($business_id);
                
                $results[] = array(
                    'id' => $business_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'link' => get_permalink(),
                    'rating' => $meta['average_rating'],
                    'review_count' => $meta['total_reviews'],
                    'thumbnail' => get_the_post_thumbnail_url($business_id, 'thumbnail'),
                );
            }
            
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'results' => $results,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
        ));
    }
    
    /**
     * Handle review rating (helpful vote)
     */
    public static function handle_review_rating() {
        $review_id = isset($_POST['review_id']) ? absint($_POST['review_id']) : 0;
        
        if (!$review_id || get_post_type($review_id) !== 'mpr_review') {
            wp_send_json_error(array('message' => __('Invalid review.', 'mpr-reviews')));
        }
        
        // Simple check - could be enhanced with user tracking
        $current_count = get_post_meta($review_id, 'mpr_helpful_count', true) ?: 0;
        update_post_meta($review_id, 'mpr_helpful_count', absint($current_count) + 1);
        
        wp_send_json_success(array(
            'message' => __('Thank you for your feedback!', 'mpr-reviews'),
            'count' => absint($current_count) + 1,
        ));
    }
}