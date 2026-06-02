<?php
/**
 * User management for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_User {
    
    /**
     * Initialize user hooks
     */
    public static function init() {
        add_action('wp', array(__CLASS__, 'handle_user_forms'));
        add_shortcode('mpr_login_form', array(__CLASS__, 'render_login_form'));
        add_shortcode('mpr_register_form', array(__CLASS__, 'render_register_form'));
        add_shortcode('mpr_user_profile', array(__CLASS__, 'render_user_profile'));
        add_shortcode('mpr_my_reviews', array(__CLASS__, 'render_my_reviews'));
    }
    
    /**
     * Handle user form submissions
     */
    public static function handle_user_forms() {
        if (!isset($_POST['mpr_user_action'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['mpr_user_nonce'], 'mpr_user_action')) {
            wp_redirect(add_query_arg('mpr_error', 'security', wp_get_referer()));
            exit;
        }
        
        $action = sanitize_text_field($_POST['mpr_user_action']);
        
        switch ($action) {
            case 'login':
                self::handle_login();
                break;
            case 'register':
                self::handle_registration();
                break;
            case 'update_profile':
                self::handle_profile_update();
                break;
        }
    }
    
    /**
     * Handle user login
     */
    private static function handle_login() {
        $username = sanitize_user($_POST['mpr_username']);
        $password = $_POST['mpr_password'];
        $remember = isset($_POST['mpr_remember']) ? true : false;
        
        if (empty($username) || empty($password)) {
            wp_redirect(add_query_arg('mpr_error', 'empty_fields', wp_get_referer()));
            exit;
        }
        
        $credentials = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        );
        
        $user = wp_signon($credentials, is_ssl());
        
        if (is_wp_error($user)) {
            wp_redirect(add_query_arg('mpr_error', 'login_failed', wp_get_referer()));
            exit;
        }
        
        wp_redirect(add_query_arg('mpr_success', 'logged_in', get_permalink());
        exit;
    }
    
    /**
     * Handle user registration
     */
    private static function handle_registration() {
        $username = sanitize_user($_POST['mpr_username']);
        $email = sanitize_email($_POST['mpr_email']);
        $password = $_POST['mpr_password'];
        $confirm_password = $_POST['mpr_confirm_password'];
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            wp_redirect(add_query_arg('mpr_error', 'empty_fields', wp_get_referer()));
            exit;
        }
        
        if ($password !== $confirm_password) {
            wp_redirect(add_query_arg('mpr_error', 'password_mismatch', wp_get_referer()));
            exit;
        }
        
        if (username_exists($username)) {
            wp_redirect(add_query_arg('mpr_error', 'username_exists', wp_get_referer()));
            exit;
        }
        
        if (email_exists($email)) {
            wp_redirect(add_query_arg('mpr_error', 'email_exists', wp_get_referer()));
            exit;
        }
        
        if (!is_email($email)) {
            wp_redirect(add_query_arg('mpr_error', 'invalid_email', wp_get_referer()));
            exit;
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('mpr_error', 'registration_failed', wp_get_referer()));
            exit;
        }
        
        // Update user display name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['mpr_display_name'] ?: $username),
        ));
        
        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_redirect(add_query_arg('mpr_success', 'registered', get_permalink()));
        exit;
    }
    
    /**
     * Handle profile update
     */
    private static function handle_profile_update() {
        if (!is_user_logged_in()) {
            wp_redirect(add_query_arg('mpr_error', 'not_logged_in', wp_get_referer()));
            exit;
        }
        
        $user_id = get_current_user_id();
        $display_name = sanitize_text_field($_POST['mpr_display_name']);
        $description = sanitize_textarea_field($_POST['mpr_description']);
        
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name,
            'description' => $description,
        ));
        
        wp_redirect(add_query_arg('mpr_success', 'profile_updated', get_permalink()));
        exit;
    }
    
    /**
     * Render login form shortcode
     */
    public static function render_login_form($atts = array()) {
        $atts = shortcode_atts(array(
            'redirect' => home_url(),
        ), $atts);
        
        if (is_user_logged_in()) {
            return '<p class="mpr-already-logged-in">' . 
                   sprintf(__('You are logged in as %s. <a href="%s">Log out</a>', 'mpr-reviews'), 
                   wp_get_current_user()->display_name,
                   wp_logout_url()) . 
                   '</p>';
        }
        
        ob_start();
        ?>
        <div class="mpr-login-form-wrapper">
            <h2><?php _e('Login to Your Account', 'mpr-reviews'); ?></h2>
            
            <?php 
            if (isset($_GET['mpr_error']) && $_GET['mpr_error'] === 'login_failed') {
                echo '<div class="mpr-error-message">' . __('Invalid username or password.', 'mpr-reviews') . '</div>';
            }
            if (isset($_GET['mpr_error']) && $_GET['mpr_error'] === 'empty_fields') {
                echo '<div class="mpr-error-message">' . __('Please fill in all fields.', 'mpr-reviews') . '</div>';
            }
            ?>
            
            <form method="post" action="" class="mpr-form">
                <input type="hidden" name="mpr_user_action" value="login">
                <?php wp_nonce_field('mpr_user_action', 'mpr_user_nonce'); ?>
                
                <p>
                    <label for="mpr_username"><?php _e('Username or Email', 'mpr-reviews'); ?></label>
                    <input type="text" name="mpr_username" id="mpr_username" required class="widefat">
                </p>
                
                <p>
                    <label for="mpr_password"><?php _e('Password', 'mpr-reviews'); ?></label>
                    <input type="password" name="mpr_password" id="mpr_password" required class="widefat">
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="mpr_remember"> 
                        <?php _e('Remember me', 'mpr-reviews'); ?>
                    </label>
                </p>
                
                <p>
                    <input type="submit" value="<?php esc_attr_e('Login', 'mpr-reviews'); ?>" class="button button-primary">
                </p>
                
                <p class="mpr-form-footer">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php _e('Forgot password?', 'mpr-reviews'); ?></a>
                    <span class="mpr-separator">|</span>
                    <a href="<?php echo esc_url(home_url('/register/')); ?>"><?php _e('Create an account', 'mpr-reviews'); ?></a>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render registration form shortcode
     */
    public static function render_register_form($atts = array()) {
        $atts = shortcode_atts(array(
            'redirect' => home_url(),
        ), $atts);
        
        if (is_user_logged_in()) {
            return '<p class="mpr-already-logged-in">' . 
                   sprintf(__('You are logged in as %s. <a href="%s">View profile</a>', 'mpr-reviews'), 
                   wp_get_current_user()->display_name,
                   home_url('/profile/')) . 
                   '</p>';
        }
        
        ob_start();
        ?>
        <div class="mpr-register-form-wrapper">
            <h2><?php _e('Create an Account', 'mpr-reviews'); ?></h2>
            
            <?php 
            if (isset($_GET['mpr_error'])) {
                $errors = array(
                    'password_mismatch' => __('Passwords do not match.', 'mpr-reviews'),
                    'username_exists' => __('Username already exists.', 'mpr-reviews'),
                    'email_exists' => __('Email already registered.', 'mpr-reviews'),
                    'invalid_email' => __('Please enter a valid email address.', 'mpr-reviews'),
                    'empty_fields' => __('Please fill in all required fields.', 'mpr-reviews'),
                    'registration_failed' => __('Registration failed. Please try again.', 'mpr-reviews'),
                );
                
                $error_key = sanitize_text_field($_GET['mpr_error']);
                if (isset($errors[$error_key])) {
                    echo '<div class="mpr-error-message">' . esc_html($errors[$error_key]) . '</div>';
                }
            }
            ?>
            
            <form method="post" action="" class="mpr-form">
                <input type="hidden" name="mpr_user_action" value="register">
                <?php wp_nonce_field('mpr_user_action', 'mpr_user_nonce'); ?>
                
                <p>
                    <label for="mpr_username"><?php _e('Username', 'mpr-reviews'); ?> *</label>
                    <input type="text" name="mpr_username" id="mpr_username" required class="widefat" minlength="4">
                </p>
                
                <p>
                    <label for="mpr_email"><?php _e('Email', 'mpr-reviews'); ?> *</label>
                    <input type="email" name="mpr_email" id="mpr_email" required class="widefat">
                </p>
                
                <p>
                    <label for="mpr_display_name"><?php _e('Display Name', 'mpr-reviews'); ?></label>
                    <input type="text" name="mpr_display_name" id="mpr_display_name" class="widefat">
                </p>
                
                <p>
                    <label for="mpr_password"><?php _e('Password', 'mpr-reviews'); ?> *</label>
                    <input type="password" name="mpr_password" id="mpr_password" required class="widefat" minlength="8">
                </p>
                
                <p>
                    <label for="mpr_confirm_password"><?php _e('Confirm Password', 'mpr-reviews'); ?> *</label>
                    <input type="password" name="mpr_confirm_password" id="mpr_confirm_password" required class="widefat" minlength="8">
                </p>
                
                <p>
                    <input type="submit" value="<?php esc_attr_e('Create Account', 'mpr-reviews'); ?>" class="button button-primary">
                </p>
                
                <p class="mpr-form-footer">
                    <?php _e('Already have an account?', 'mpr-reviews'); ?>
                    <a href="<?php echo esc_url(home_url('/login/')); ?>"><?php _e('Login here', 'mpr-reviews'); ?></a>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render user profile shortcode
     */
    public static function render_user_profile($atts = array()) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your profile.', 'mpr-reviews') . 
                   ' <a href="' . esc_url(home_url('/login/')) . '">' . __('Login', 'mpr-reviews') . '</a></p>';
        }
        
        $user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="mpr-profile-wrapper">
            <h2><?php _e('Your Profile', 'mpr-reviews'); ?></h2>
            
            <?php 
            if (isset($_GET['mpr_success']) && $_GET['mpr_success'] === 'profile_updated') {
                echo '<div class="mpr-success-message">' . __('Profile updated successfully!', 'mpr-reviews') . '</div>';
            }
            ?>
            
            <div class="mpr-profile-info">
                <p><strong><?php _e('Username:', 'mpr-reviews'); ?></strong> <?php echo esc_html($user->user_login); ?></p>
                <p><strong><?php _e('Email:', 'mpr-reviews'); ?></strong> <?php echo esc_html($user->user_email); ?></p>
                <p><strong><?php _e('Member since:', 'mpr-reviews'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?></p>
                <p><a href="<?php echo esc_url(home_url('/my-reviews/')); ?>" class="button"><?php _e('View My Reviews', 'mpr-reviews'); ?></a></p>
            </div>
            
            <h3><?php _e('Update Profile', 'mpr-reviews'); ?></h3>
            <form method="post" action="" class="mpr-form">
                <input type="hidden" name="mpr_user_action" value="update_profile">
                <?php wp_nonce_field('mpr_user_action', 'mpr_user_nonce'); ?>
                
                <p>
                    <label for="mpr_display_name"><?php _e('Display Name', 'mpr-reviews'); ?></label>
                    <input type="text" name="mpr_display_name" id="mpr_display_name" 
                           value="<?php echo esc_attr($user->display_name); ?>" class="widefat">
                </p>
                
                <p>
                    <label for="mpr_description"><?php _e('Bio', 'mpr-reviews'); ?></label>
                    <textarea name="mpr_description" id="mpr_description" class="widefat" rows="4"><?php echo esc_textarea($user->description); ?></textarea>
                </p>
                
                <p>
                    <input type="submit" value="<?php esc_attr_e('Update Profile', 'mpr-reviews'); ?>" class="button button-primary">
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render my reviews shortcode
     */
    public static function render_my_reviews($atts = array()) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your reviews.', 'mpr-reviews') . 
                   ' <a href="' . esc_url(home_url('/login/')) . '">' . __('Login', 'mpr-reviews') . '</a></p>';
        }
        
        $user_id = get_current_user_id();
        $per_page = get_option('mpr_reviews_per_page', 10);
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        
        $args = array(
            'post_type' => 'mpr_review',
            'post_status' => 'any',
            'author' => $user_id,
            'posts_per_page' => $per_page,
            'paged' => $paged,
        );
        
        $query = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="mpr-my-reviews-wrapper">
            <h2><?php _e('My Reviews', 'mpr-reviews'); ?></h2>
            
            <?php if ($query->have_posts()) : ?>
                <div class="mpr-reviews-list">
                    <?php while ($query->have_posts()) : $query->the_post(); 
                        $business_id = get_post_meta(get_the_ID(), 'mpr_business_id', true);
                        $rating = get_post_meta(get_the_ID(), 'mpr_review_rating', true);
                        $status = get_post_meta(get_the_ID(), 'mpr_approval_status', true) ?: 'pending';
                        $business_title = $business_id ? get_the_title($business_id) : __('Unknown Business', 'mpr-reviews');
                        ?>
                        <div class="mpr-review-item">
                            <div class="mpr-review-header">
                                <span class="mpr-review-business">
                                    <a href="<?php echo esc_url(get_permalink($business_id)); ?>"><?php echo esc_html($business_title); ?></a>
                                </span>
                                <span class="mpr-review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <span class="mpr-star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </span>
                                <span class="mpr-review-status status-<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html(ucfirst($status)); ?>
                                </span>
                            </div>
                            <h4 class="mpr-review-title"><?php echo esc_html(get_the_title()); ?></h4>
                            <p class="mpr-review-content"><?php echo esc_html(wp_trim_words(get_the_content(), 30)); ?></p>
                            <p class="mpr-review-date">
                                <?php printf(__('Submitted on %s', 'mpr-reviews'), get_the_date()); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php
                // Pagination
                $total_pages = $query->max_num_pages;
                if ($total_pages > 1) :
                ?>
                    <div class="mpr-pagination">
                        <?php
                        echo paginate_links(array(
                            'current' => $paged,
                            'total' => $total_pages,
                            'prev_text' => __('« Previous', 'mpr-reviews'),
                            'next_text' => __('Next »', 'mpr-reviews'),
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
            <?php else : ?>
                <p class="mpr-no-reviews"><?php _e('You haven\'t submitted any reviews yet.', 'mpr-reviews'); ?></p>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize user management
add_action('init', array('MPR_User', 'init'));