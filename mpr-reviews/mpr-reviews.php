<?php
/**
 * Plugin Name: MPR Reviews
 * Plugin URI: https://myprotector.org/mpr-reviews
 * Description: A comprehensive review platform for businesses - Trustpilot style functionality for WordPress
 * Version: 1.0.0
 * Author: MyProtector
 * Author URI: https://myprotector.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mpr-reviews
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MPR_PLUGIN_VERSION', '1.0.0');
define('MPR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MPR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main MPR_Reviews class
 */
class MPR_Reviews {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'), 1);
        add_action('init', array($this, 'register_post_types'), 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_loaded', array($this, 'handle_form_submission'));
        
        // AJAX handlers
        add_action('wp_ajax_mpr_submit_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_nopriv_mpr_submit_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_mpr_search_businesses', array($this, 'ajax_search_businesses'));
        add_action('wp_ajax_nopriv_mpr_search_businesses', array($this, 'ajax_search_businesses'));
        add_action('wp_ajax_mpr_rate_review', array($this, 'ajax_rate_review'));
        add_action('wp_ajax_nopriv_mpr_rate_review', array($this, 'ajax_rate_review'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mpr-reviews',
            false,
            basename(dirname(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Load plugin dependencies
     */
    public function load_dependencies() {
        // Load database functions first
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-database.php';
        
        // Load post types
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-post-types.php';
        
        // Load meta fields
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-meta-fields.php';
        
        // Load shortcodes
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-shortcodes.php';
        
        // Load AJAX handlers
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-ajax.php';
        
        // Load security
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-security.php';
        
        // Load SEO
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-seo.php';
        
        // Load user management
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-user.php';
        
        // Load admin
        require_once MPR_PLUGIN_DIR . 'admin/class-mpr-admin.php';
        
        // Load frontend
        require_once MPR_PLUGIN_DIR . 'frontend/class-mpr-frontend.php';
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $this->load_dependencies();
        MPR_Post_Types::register();
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'mpr-reviews-styles',
            MPR_PLUGIN_URL . 'assets/css/mpr-styles.css',
            array(),
            MPR_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'mpr-reviews-scripts',
            MPR_PLUGIN_URL . 'assets/js/mpr-scripts.js',
            array('jquery'),
            MPR_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('mpr-reviews-scripts', 'mpr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mpr_nonce'),
            'loading_text' => __('Loading...', 'mpr-reviews'),
            'error_text' => __('An error occurred. Please try again.', 'mpr-reviews'),
        ));
    }
    
    /**
     * Handle review form submission
     */
    public function handle_form_submission() {
        if (!isset($_POST['mpr_review_submission']) || !wp_verify_nonce($_POST['mpr_review_nonce'], 'mpr_review_action')) {
            return;
        }
        
        $this->load_dependencies();
        MPR_Ajax::handle_review_submission();
    }
    
    /**
     * AJAX submit review
     */
    public function ajax_submit_review() {
        $this->load_dependencies();
        check_ajax_referer('mpr_nonce', 'nonce');
        MPR_Ajax::handle_review_submission(true);
    }
    
    /**
     * AJAX search businesses
     */
    public function ajax_search_businesses() {
        $this->load_dependencies();
        check_ajax_referer('mpr_nonce', 'nonce');
        MPR_Ajax::handle_business_search();
    }
    
    /**
     * AJAX rate review
     */
    public function ajax_rate_review() {
        $this->load_dependencies();
        check_ajax_referer('mpr_nonce', 'nonce');
        MPR_Ajax::handle_review_rating();
    }
    
    /**
     * Setup plugin on activation
     */
    public static function activate() {
        // Set default options
        $defaults = array(
            'mpr_reviews_per_page' => 10,
            'mpr_notify_admin' => true,
            'mpr_admin_email' => get_option('admin_email'),
            'mpr_require_registration' => false,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
        
        // Create database tables
        require_once MPR_PLUGIN_DIR . 'includes/class-mpr-database.php';
        mpr_create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Cleanup on deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize plugin
add_action('plugins_loaded', array('MPR_Reviews', 'get_instance'), 1);

// Register activation/deactivation hooks
register_activation_hook(__FILE__, array('MPR_Reviews', 'activate'));
register_deactivation_hook(__FILE__, array('MPR_Reviews', 'deactivate'));