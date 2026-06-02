<?php
/**
 * Admin functionality for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Admin {
    
    /**
     * Initialize admin hooks
     */
    public static function init() {
        // Custom columns for business post type
        add_filter('manage_mpr_business_posts_columns', array(__CLASS__, 'business_columns'));
        add_action('manage_mpr_business_posts_custom_column', array(__CLASS__, 'business_column_content'), 10, 2);
        
        // Custom columns for review post type
        add_filter('manage_mpr_review_posts_columns', array(__CLASS__, 'review_columns'));
        add_action('manage_mpr_review_posts_custom_column', array(__CLASS__, 'review_column_content'), 10, 2);
        
        // Quick edit
        add_action('quick_edit_custom_box', array(__CLASS__, 'quick_edit_fields'), 10, 2);
        
        // Admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        
        // Admin styles and scripts
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        
        // Bulk actions
        add_filter('bulk_actions-edit-mpr_review', array(__CLASS__, 'register_bulk_actions'));
        add_filter('handle_bulk_actions-edit-mpr_review', array(__CLASS__, 'handle_bulk_actions'), 10, 3);
        
        // Status transitions
        add_action('transition_post_status', array(__CLASS__, 'handle_status_transition'), 10, 3);
        
        // Admin notices
        add_action('admin_notices', array(__CLASS__, 'admin_notices'));
        
        // Settings
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
    
    /**
     * Business list columns
     */
    public static function business_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['mpr_business_rating'] = __('Rating', 'mpr-reviews');
                $new_columns['mpr_business_reviews'] = __('Reviews', 'mpr-reviews');
                $new_columns['mpr_business_category'] = __('Category', 'mpr-reviews');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Business column content
     */
    public static function business_column_content($column, $post_id) {
        switch ($column) {
            case 'mpr_business_rating':
                $rating = MPR_Meta_Fields::calculate_average_rating($post_id);
                echo '<span class="mpr-admin-rating">' . esc_html($rating) . ' ★</span>';
                break;
                
            case 'mpr_business_reviews':
                $count = MPR_Meta_Fields::get_total_reviews($post_id);
                $edit_link = admin_url('edit.php?post_type=mpr_review&mpr_business_id=' . $post_id);
                echo '<a href="' . esc_url($edit_link) . '">' . esc_html($count) . ' ' . __('reviews', 'mpr-reviews') . '</a>';
                break;
                
            case 'mpr_business_category':
                $terms = get_the_terms($post_id, 'mpr_business_category');
                if ($terms && !is_wp_error($terms)) {
                    $names = wp_list_pluck($terms, 'name');
                    echo esc_html(implode(', ', $names));
                }
                break;
        }
    }
    
    /**
     * Review list columns
     */
    public static function review_columns($columns) {
        $new_columns = array();
        
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Review Title', 'mpr-reviews');
        $new_columns['mpr_reviewer_info'] = __('Reviewer', 'mpr-reviews');
        $new_columns['mpr_business'] = __('Business', 'mpr-reviews');
        $new_columns['mpr_rating'] = __('Rating', 'mpr-reviews');
        $new_columns['mpr_status'] = __('Status', 'mpr-reviews');
        $new_columns['mpr_date'] = __('Date', 'mpr-reviews');
        
        return $new_columns;
    }
    
    /**
     * Review column content
     */
    public static function review_column_content($column, $post_id) {
        switch ($column) {
            case 'mpr_reviewer_info':
                $name = get_post_meta($post_id, 'mpr_reviewer_name', true);
                $email = get_post_meta($post_id, 'mpr_reviewer_email', true);
                if ($name) {
                    echo '<strong>' . esc_html($name) . '</strong>';
                    if ($email) {
                        echo '<br><small>' . esc_html($email) . '</small>';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'mpr_business':
                $business_id = get_post_meta($post_id, 'mpr_business_id', true);
                if ($business_id && get_post($business_id)) {
                    echo '<a href="' . esc_url(get_edit_post_link($business_id)) . '">' . 
                         esc_html(get_the_title($business_id)) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'mpr_rating':
                $rating = get_post_meta($post_id, 'mpr_review_rating', true);
                if ($rating) {
                    echo '<span class="mpr-admin-rating">';
                    for ($i = 1; $i <= 5; $i++) {
                        echo ($i <= $rating) ? '★' : '☆';
                    }
                    echo '</span>';
                }
                break;
                
            case 'mpr_status':
                $status = get_post_meta($post_id, 'mpr_approval_status', true) ?: 'pending';
                $statuses = array(
                    'pending' => '<span class="mpr-status pending">' . __('Pending', 'mpr-reviews') . '</span>',
                    'approved' => '<span class="mpr-status approved">' . __('Approved', 'mpr-reviews') . '</span>',
                    'rejected' => '<span class="mpr-status rejected">' . __('Rejected', 'mpr-reviews') . '</span>',
                );
                echo isset($statuses[$status]) ? $statuses[$status] : $statuses['pending'];
                break;
                
            case 'mpr_date':
                echo get_the_date('Y-m-d', $post_id);
                break;
        }
    }
    
    /**
     * Quick edit fields
     */
    public static function quick_edit_fields($column_name, $post_type) {
        if ($post_type !== 'mpr_review') {
            return;
        }
        
        if ($column_name === 'mpr_rating') {
            ?>
            <fieldset class="inline-edit-col-center">
                <div class="inline-edit-group">
                    <label>
                        <span class="title"><?php _e('Rating', 'mpr-reviews'); ?></span>
                        <select name="mpr_review_rating">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <?php
        }
    }
    
    /**
     * Add admin menu items
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('MPR Reviews', 'mpr-reviews'),
            __('MPR Reviews', 'mpr-reviews'),
            'manage_options',
            'mpr-reviews',
            array(__CLASS__, 'render_dashboard_page'),
            'dashicons-star-filled',
            30
        );
        
        add_submenu_page(
            'mpr-reviews',
            __('Dashboard', 'mpr-reviews'),
            __('Dashboard', 'mpr-reviews'),
            'manage_options',
            'mpr-reviews',
            array(__CLASS__, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'mpr-reviews',
            __('All Businesses', 'mpr-reviews'),
            __('Businesses', 'mpr-reviews'),
            'manage_options',
            'edit.php?post_type=mpr_business'
        );
        
        add_submenu_page(
            'mpr-reviews',
            __('All Reviews', 'mpr-reviews'),
            __('Reviews', 'mpr-reviews'),
            'manage_options',
            'edit.php?post_type=mpr_review'
        );
        
        add_submenu_page(
            'mpr-reviews',
            __('Categories', 'mpr-reviews'),
            __('Categories', 'mpr-reviews'),
            'manage_options',
            'edit-tags.php?taxonomy=mpr_business_category&post_type=mpr_business'
        );
        
        add_submenu_page(
            'mpr-reviews',
            __('Settings', 'mpr-reviews'),
            __('Settings', 'mpr-reviews'),
            'manage_options',
            'mpr-reviews-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }
    
    /**
     * Render dashboard page
     */
    public static function render_dashboard_page() {
        // Get stats
        $total_businesses = wp_count_posts('mpr_business')->publish;
        $total_reviews = wp_count_posts('mpr_review')->publish;
        $pending_reviews = wp_count_posts('mpr_review')->pending;
        
        // Get recent reviews
        $recent_reviews = get_posts(array(
            'post_type' => 'mpr_review',
            'posts_per_page' => 5,
            'post_status' => 'pending',
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        // Get top businesses
        $top_businesses = get_posts(array(
            'post_type' => 'mpr_business',
            'posts_per_page' => 5,
            'post_status' => 'publish',
        ));
        
        ?>
        <div class="wrap mpr-admin-dashboard">
            <h1><?php _e('MPR Reviews Dashboard', 'mpr-reviews'); ?></h1>
            
            <div class="mpr-dashboard-stats">
                <div class="mpr-stat-box">
                    <h3><?php _e('Total Businesses', 'mpr-reviews'); ?></h3>
                    <p class="mpr-stat-number"><?php echo esc_html($total_businesses); ?></p>
                    <p class="mpr-stat-link">
                        <a href="<?php echo esc_url(admin_url('edit.php?post_type=mpr_business')); ?>">
                            <?php _e('View all', 'mpr-reviews'); ?>
                        </a>
                    </p>
                </div>
                
                <div class="mpr-stat-box">
                    <h3><?php _e('Total Reviews', 'mpr-reviews'); ?></h3>
                    <p class="mpr-stat-number"><?php echo esc_html($total_reviews); ?></p>
                    <p class="mpr-stat-link">
                        <a href="<?php echo esc_url(admin_url('edit.php?post_type=mpr_review')); ?>">
                            <?php _e('View all', 'mpr-reviews'); ?>
                        </a>
                    </p>
                </div>
                
                <div class="mpr-stat-box mpr-stat-warning">
                    <h3><?php _e('Pending Reviews', 'mpr-reviews'); ?></h3>
                    <p class="mpr-stat-number"><?php echo esc_html($pending_reviews); ?></p>
                    <?php if ($pending_reviews > 0) : ?>
                    <p class="mpr-stat-link">
                        <a href="<?php echo esc_url(admin_url('edit.php?post_type=mpr_review&post_status=pending')); ?>">
                            <?php _e('Review now', 'mpr-reviews'); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mpr-dashboard-sections">
                <div class="mpr-dashboard-section">
                    <h2><?php _e('Pending Reviews', 'mpr-reviews'); ?></h2>
                    
                    <?php if (empty($recent_reviews)) : ?>
                        <p><?php _e('No pending reviews.', 'mpr-reviews'); ?></p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Review', 'mpr-reviews'); ?></th>
                                    <th><?php _e('Business', 'mpr-reviews'); ?></th>
                                    <th><?php _e('Reviewer', 'mpr-reviews'); ?></th>
                                    <th><?php _e('Actions', 'mpr-reviews'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_reviews as $review) : 
                                    $business_id = get_post_meta($review->ID, 'mpr_business_id', true);
                                    $reviewer_name = get_post_meta($review->ID, 'mpr_reviewer_name', true);
                                    $reviewer_email = get_post_meta($review->ID, 'mpr_reviewer_email', true);
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($review->post_title); ?></strong>
                                            <br><small><?php echo esc_html(wp_trim_words($review->post_content, 10)); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($business_id) {
                                                echo '<a href="' . esc_url(get_edit_post_link($business_id)) . '">' . 
                                                     esc_html(get_the_title($business_id)) . '</a>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo esc_html($reviewer_name ?: '—'); ?>
                                            <br><small><?php echo esc_html($reviewer_email ?: ''); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($review->ID)); ?>" class="button">
                                                <?php _e('Review', 'mpr-reviews'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="mpr-dashboard-section">
                    <h2><?php _e('Quick Links', 'mpr-reviews'); ?></h2>
                    <ul class="mpr-quick-links">
                        <li><a href="<?php echo esc_url(admin_url('post-new.php?post_type=mpr_business')); ?>">
                            <span class="dashicons dashicons-plus"></span> <?php _e('Add New Business', 'mpr-reviews'); ?>
                        </a></li>
                        <li><a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=mpr_business_category&post_type=mpr_business')); ?>">
                            <span class="dashicons dashicons-category"></span> <?php _e('Manage Categories', 'mpr-reviews'); ?>
                        </a></li>
                        <li><a href="<?php echo esc_url(admin_url('admin.php?page=mpr-reviews-settings')); ?>">
                            <span class="dashicons dashicons-admin-settings"></span> <?php _e('Plugin Settings', 'mpr-reviews'); ?>
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap mpr-admin-settings">
            <h1><?php _e('MPR Reviews Settings', 'mpr-reviews'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('mpr_reviews_settings'); ?>
                
                <div class="mpr-settings-section">
                    <h2><?php _e('General Settings', 'mpr-reviews'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="mpr_reviews_per_page"><?php _e('Reviews Per Page', 'mpr-reviews'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="mpr_reviews_per_page" name="mpr_reviews_per_page" 
                                       value="<?php echo esc_attr(get_option('mpr_reviews_per_page', 10)); ?>" 
                                       min="1" max="100" class="small-text">
                                <p class="description"><?php _e('Number of reviews to display per page.', 'mpr-reviews'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="mpr_require_registration"><?php _e('Require Registration', 'mpr-reviews'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="mpr_require_registration" name="mpr_require_registration" 
                                       value="1" <?php checked(get_option('mpr_require_registration'), 1); ?>>
                                <label for="mpr_require_registration"><?php _e('Users must be logged in to submit reviews', 'mpr-reviews'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="mpr-settings-section">
                    <h2><?php _e('Email Notifications', 'mpr-reviews'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="mpr_notify_admin"><?php _e('Admin Notifications', 'mpr-reviews'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="mpr_notify_admin" name="mpr_notify_admin" 
                                       value="1" <?php checked(get_option('mpr_notify_admin', true), 1); ?>>
                                <label for="mpr_notify_admin"><?php _e('Send email when a new review is submitted', 'mpr-reviews'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="mpr_admin_email"><?php _e('Admin Email', 'mpr-reviews'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="mpr_admin_email" name="mpr_admin_email" 
                                       value="<?php echo esc_attr(get_option('mpr_admin_email', get_option('admin_email'))); ?>" 
                                       class="regular-text">
                                <p class="description"><?php _e('Email address to receive review notifications.', 'mpr-reviews'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('mpr_reviews_settings', 'mpr_reviews_per_page', 'absint');
        register_setting('mpr_reviews_settings', 'mpr_require_registration', 'absint');
        register_setting('mpr_reviews_settings', 'mpr_notify_admin', 'absint');
        register_setting('mpr_reviews_settings', 'mpr_admin_email', 'sanitize_email');
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'mpr-reviews') === false && 
            !in_array($hook, array('edit.php', 'post.php', 'post-new.php'))) {
            return;
        }
        
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
        
        if (!in_array($post_type, array('mpr_business', 'mpr_review')) && 
            !in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_style(
            'mpr-admin-styles',
            MPR_PLUGIN_URL . 'assets/css/mpr-admin.css',
            array(),
            MPR_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'mpr-admin-scripts',
            MPR_PLUGIN_URL . 'assets/js/mpr-admin.js',
            array('jquery'),
            MPR_PLUGIN_VERSION,
            true
        );
    }
    
    /**
     * Register bulk actions
     */
    public static function register_bulk_actions($bulk_actions) {
        $bulk_actions['mpr_approve'] = __('Approve', 'mpr-reviews');
        $bulk_actions['mpr_reject'] = __('Reject', 'mpr-reviews');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk actions
     */
    public static function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if (!in_array($action, array('mpr_approve', 'mpr_reject'))) {
            return $redirect_to;
        }
        
        $status = ($action === 'mpr_approve') ? 'approved' : 'rejected';
        
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, 'mpr_approval_status', $status);
            
            // Update post status if needed
            if ($status === 'approved') {
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_status' => 'publish',
                ));
            }
        }
        
        return add_query_arg('mpr_bulk_action', $action, $redirect_to);
    }
    
    /**
     * Handle status transitions
     */
    public static function handle_status_transition($new_status, $old_status, $post) {
        if ($post->post_type !== 'mpr_review') {
            return;
        }
        
        // Update approval status meta
        if ($new_status === 'publish') {
            update_post_meta($post->ID, 'mpr_approval_status', 'approved');
        } elseif ($new_status === 'trash') {
            // Keep status as is
        }
    }
    
    /**
     * Admin notices
     */
    public static function admin_notices() {
        if (isset($_GET['mpr_bulk_action'])) {
            $action = sanitize_text_field($_GET['mpr_bulk_action']);
            $message = ($action === 'mpr_approve') ? 
                __('Reviews approved successfully.', 'mpr-reviews') : 
                __('Reviews rejected successfully.', 'mpr-reviews');
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }
}

// Initialize admin
add_action('init', array('MPR_Admin', 'init'));