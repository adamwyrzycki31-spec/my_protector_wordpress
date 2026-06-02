<?php
/**
 * MPR Reviews - Main Loader Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Loader {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_textdomain();
        $this->register_post_types();
        $this->init_shortcodes();
        $this->enqueue_assets();
    }
    
    private function load_textdomain() {
        load_plugin_textdomain('mpr-reviews', false, basename(dirname(__FILE__)) . '/languages');
    }
    
    private function register_post_types() {
        // Register Business post type
        register_post_type('mpr_business', array(
            'labels' => array(
                'name' => __('Businesses', 'mpr-reviews'),
                'singular_name' => __('Business', 'mpr-reviews'),
                'add_new_item' => __('Add New Business', 'mpr-reviews'),
                'edit_item' => __('Edit Business', 'mpr-reviews'),
                'view_item' => __('View Business', 'mpr-reviews'),
                'search_items' => __('Search Businesses', 'mpr-reviews'),
                'not_found' => __('No businesses found', 'mpr-reviews'),
            ),
            'public' => true,
            'has_archive' => 'businesses',
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'business'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-building',
        ));
        
        // Register Review post type
        register_post_type('mpr_review', array(
            'labels' => array(
                'name' => __('Reviews', 'mpr-reviews'),
                'singular_name' => __('Review', 'mpr-reviews'),
                'add_new_item' => __('Add New Review', 'mpr-reviews'),
                'edit_item' => __('Edit Review', 'mpr-reviews'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'review'),
            'supports' => array('title', 'editor'),
        ));
        
        // Register Category taxonomy
        register_taxonomy('mpr_business_category', 'mpr_business', array(
            'labels' => array(
                'name' => __('Categories', 'mpr-reviews'),
                'singular_name' => __('Category', 'mpr-reviews'),
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'business-category'),
        ));
    }
    
    private function init_shortcodes() {
        // Review form shortcode
        add_shortcode('mpr_review_form', array($this, 'review_form_shortcode'));
        
        // Business directory shortcode
        add_shortcode('mpr_business_directory', array($this, 'business_directory_shortcode'));
        
        // Search shortcode
        add_shortcode('mpr_search', array($this, 'search_shortcode'));
    }
    
    private function enqueue_assets() {
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_style('mpr-styles', MPR_PLUGIN_URL . 'assets/css/mpr-styles.css', array(), MPR_PLUGIN_VERSION);
            wp_enqueue_script('mpr-scripts', MPR_PLUGIN_URL . 'assets/js/mpr-scripts.js', array('jquery'), MPR_PLUGIN_VERSION, true);
        });
    }
    
    public function review_form_shortcode($atts) {
        $atts = shortcode_atts(array('business_id' => 0), $atts);
        $business_id = absint($atts['business_id']);
        
        if (!$business_id) {
            return '<p class="mpr-error">No business specified.</p>';
        }
        
        ob_start();
        ?>
        <div class="mpr-review-form">
            <h3><?php _e('Write a Review', 'mpr-reviews'); ?></h3>
            <form method="post" action="">
                <input type="hidden" name="mpr_action" value="submit_review">
                <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">
                <?php wp_nonce_field('mpr_review_action', 'mpr_nonce'); ?>
                
                <p>
                    <label><?php _e('Rating', 'mpr-reviews'); ?></label>
                    <select name="rating" required>
                        <option value="5">5 - <?php _e('Excellent', 'mpr-reviews'); ?></option>
                        <option value="4">4 - <?php _e('Good', 'mpr-reviews'); ?></option>
                        <option value="3">3 - <?php _e('Average', 'mpr-reviews'); ?></option>
                        <option value="2">2 - <?php _e('Poor', 'mpr-reviews'); ?></option>
                        <option value="1">1 - <?php _e('Terrible', 'mpr-reviews'); ?></option>
                    </select>
                </p>
                <p>
                    <label><?php _e('Title', 'mpr-reviews'); ?></label>
                    <input type="text" name="title" required minlength="3">
                </p>
                <p>
                    <label><?php _e('Your Review', 'mpr-reviews'); ?></label>
                    <textarea name="content" rows="5" required minlength="10"></textarea>
                </p>
                <p>
                    <label><?php _e('Your Name', 'mpr-reviews'); ?></label>
                    <input type="text" name="reviewer_name" required>
                </p>
                <p>
                    <label><?php _e('Your Email', 'mpr-reviews'); ?></label>
                    <input type="email" name="reviewer_email" required>
                </p>
                <p><input type="submit" class="button" value="<?php esc_attr_e('Submit Review', 'mpr-reviews'); ?>"></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function business_directory_shortcode($atts) {
        $atts = shortcode_atts(array('per_page' => 10, 'columns' => 2), $atts);
        
        $args = array(
            'post_type' => 'mpr_business',
            'post_status' => 'publish',
            'posts_per_page' => absint($atts['per_page']),
        );
        
        $query = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="mpr-business-directory">
            <div class="mpr-grid mpr-grid-<?php echo esc_attr($atts['columns']); ?>">
                <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="mpr-business-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="mpr-business-logo">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <p><?php the_excerpt(); ?></p>
                        <a href="<?php the_permalink(); ?>" class="button"><?php _e('View Details', 'mpr-reviews'); ?></a>
                    </div>
                <?php endwhile; else : ?>
                    <p><?php _e('No businesses found.', 'mpr-reviews'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array('placeholder' => 'Search businesses...'), $atts);
        
        ob_start();
        ?>
        <div class="mpr-search">
            <form method="get" action="<?php echo esc_url(home_url('/businesses/')); ?>">
                <input type="text" name="s" placeholder="<?php echo esc_attr($atts['placeholder']); ?>">
                <button type="submit"><?php _e('Search', 'mpr-reviews'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Handle form submission
add_action('wp_loaded', function() {
    if (!isset($_POST['mpr_action']) || $_POST['mpr_action'] !== 'submit_review') {
        return;
    }
    
    if (!isset($_POST['mpr_nonce']) || !wp_verify_nonce($_POST['mpr_nonce'], 'mpr_review_action')) {
        return;
    }
    
    $business_id = absint($_POST['business_id']);
    $rating = absint($_POST['rating']);
    $title = sanitize_text_field($_POST['title']);
    $content = sanitize_textarea_field($_POST['content']);
    $reviewer_name = sanitize_text_field($_POST['reviewer_name']);
    $reviewer_email = sanitize_email($_POST['reviewer_email']);
    
    // Validate
    if (!$business_id || $rating < 1 || $rating > 5 || strlen($title) < 3 || strlen($content) < 10) {
        wp_redirect(add_query_arg('mpr_error', 'validation', wp_get_referer()));
        exit;
    }
    
    // Create review
    $review_id = wp_insert_post(array(
        'post_type' => 'mpr_review',
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'pending',
    ));
    
    if ($review_id && !is_wp_error($review_id)) {
        update_post_meta($review_id, 'mpr_business_id', $business_id);
        update_post_meta($review_id, 'mpr_rating', $rating);
        update_post_meta($review_id, 'mpr_reviewer_name', $reviewer_name);
        update_post_meta($review_id, 'mpr_reviewer_email', $reviewer_email);
        update_post_meta($review_id, 'mpr_status', 'pending');
    }
    
    wp_redirect(add_query_arg('mpr_success', '1', get_permalink($business_id)));
    exit;
});