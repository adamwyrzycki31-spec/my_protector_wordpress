<?php
/**
 * Security utilities for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Security {
    
    /**
     * Initialize security hooks
     */
    public static function init() {
        // Rate limiting
        add_action('init', array(__CLASS__, 'check_rate_limit'));
        
        // Honeypot field check
        add_action('wp_loaded', array(__CLASS__, 'check_honeypot'));
    }
    
    /**
     * Verify nonce
     */
    public static function verify_nonce($action = 'mpr_nonce') {
        if (isset($_POST['mpr_review_nonce'])) {
            return wp_verify_nonce($_POST['mpr_review_nonce'], $action);
        }
        
        if (isset($_GET['_wpnonce'])) {
            return wp_verify_nonce($_GET['_wpnonce'], $action);
        }
        
        return false;
    }
    
    /**
     * Create nonce field
     */
    public static function nonce_field($action = 'mpr_review_action') {
        wp_nonce_field($action, 'mpr_review_nonce');
    }
    
    /**
     * Sanitize text input
     */
    public static function sanitize_text($text) {
        return sanitize_text_field(trim($text));
    }
    
    /**
     * Sanitize email
     */
    public static function sanitize_email_input($email) {
        return sanitize_email(trim($email));
    }
    
    /**
     * Sanitize URL
     */
    public static function sanitize_url_input($url) {
        return esc_url_raw(trim($url));
    }
    
    /**
     * Sanitize textarea content (allows some HTML)
     */
    public static function sanitize_textarea($content) {
        $allowed = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
        );
        
        return wp_kses(trim($content), $allowed);
    }
    
    /**
     * Sanitize rating (1-5)
     */
    public static function sanitize_rating($rating) {
        $rating = absint($rating);
        return min(5, max(1, $rating));
    }
    
    /**
     * Check rate limiting
     */
    public static function check_rate_limit() {
        $ip = self::get_client_ip();
        $transient_key = 'mpr_rate_limit_' . md5($ip);
        
        $count = get_transient($transient_key);
        
        if ($count === false) {
            set_transient($transient_key, 1, 60); // 1 per minute initially
        } elseif ($count >= 5) {
            // Too many requests
            if (wp_doing_ajax()) {
                wp_send_json_error(array(
                    'message' => __('Too many requests. Please wait a moment.', 'mpr-reviews'),
                ));
                wp_die();
            }
            wp_die(__('Rate limit exceeded. Please try again later.', 'mpr-reviews'));
        } else {
            set_transient($transient_key, $count + 1, 60);
        }
    }
    
    /**
     * Get client IP address
     */
    public static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        
        return $ip;
    }
    
    /**
     * Check honeypot field
     */
    public static function check_honeypot() {
        if (isset($_POST['mpr_hp_field']) && !empty($_POST['mpr_hp_field'])) {
            // Bot detected - silently fail
            wp_redirect(home_url());
            exit;
        }
    }
    
    /**
     * Check user capability
     */
    public static function check_capability($cap = 'edit_posts') {
        return current_user_can($cap);
    }
    
    /**
     * Validate email format
     */
    public static function is_valid_email($email) {
        return is_email($email);
    }
    
    /**
     * Validate URL format
     */
    public static function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Check for spam patterns
     */
    public static function is_spam_content($content) {
        $spam_patterns = array(
            '/\b(viagra|cialis|casino|lottery)\b/i',
            '/\[url=/i',
            '/\[link/i',
            '/http:\/\/[^\s]+{5,}/i',
        );
        
        foreach ($spam_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Escape HTML content
     */
    public static function escape_html($content) {
        return esc_html($content);
    }
    
    /**
     * Escape textarea content
     */
    public static function escape_textarea($content) {
        return esc_textarea($content);
    }
    
    /**
     * Escape URL
     */
    public static function escape_url($url) {
        return esc_url($url);
    }
    
    /**
     * Escape attribute
     */
    public static function escape_attr($attr) {
        return esc_attr($attr);
    }
    
    /**
     * Prepare SQL statement values
     */
    public static function prepare_string($string) {
        global $wpdb;
        return $wpdb->prepare('%s', $string);
    }
    
    /**
     * Generate honeypot field HTML
     */
    public static function honeypot_field() {
        return '<input type="text" name="mpr_hp_field" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">';
    }
}

// Initialize security
add_action('init', array('MPR_Security', 'init'));