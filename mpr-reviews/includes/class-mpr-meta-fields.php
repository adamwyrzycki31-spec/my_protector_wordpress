<?php
/**
 * Custom Meta Fields for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Meta_Fields {
    
    /**
     * Business meta keys
     */
    const BUSINESS_META_PREFIX = 'mpr_business_';
    
    /**
     * Initialize meta fields
     */
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_meta_boxes'), 10, 2);
    }
    
    /**
     * Add meta boxes
     */
    public static function add_meta_boxes() {
        // Business meta boxes
        add_meta_box(
            'mpr_business_details',
            __('Business Details', 'mpr-reviews'),
            array(__CLASS__, 'render_business_meta_box'),
            'mpr_business',
            'normal',
            'high'
        );
        
        // Review meta box
        add_meta_box(
            'mpr_review_details',
            __('Review Details', 'mpr-reviews'),
            array(__CLASS__, 'render_review_meta_box'),
            'mpr_review',
            'side',
            'default'
        );
    }
    
    /**
     * Render business meta box
     */
    public static function render_business_meta_box($post) {
        wp_nonce_field('mpr_business_meta', 'mpr_business_nonce');
        
        $fields = self::get_business_fields();
        $values = array();
        
        foreach ($fields as $key => $field) {
            $values[$key] = get_post_meta($post->ID, $key, true);
        }
        
        // Calculate average rating and total reviews
        $values['mpr_business_average_rating'] = self::calculate_average_rating($post->ID);
        $values['mpr_business_total_reviews'] = self::get_total_reviews($post->ID);
        ?>
        <div class="mpr-meta-box">
            <p>
                <label for="mpr_business_website"><?php _e('Website URL', 'mpr-reviews'); ?></label>
                <input type="url" id="mpr_business_website" name="mpr_business_website" 
                       value="<?php echo esc_url($values['mpr_business_website'] ?? ''); ?>" 
                       class="widefat" placeholder="https://example.com">
            </p>
            
            <p>
                <label for="mpr_business_phone"><?php _e('Phone', 'mpr-reviews'); ?></label>
                <input type="tel" id="mpr_business_phone" name="mpr_business_phone" 
                       value="<?php echo esc_attr($values['mpr_business_phone'] ?? ''); ?>" 
                       class="widefat">
            </p>
            
            <p>
                <label for="mpr_business_email"><?php _e('Email', 'mpr-reviews'); ?></label>
                <input type="email" id="mpr_business_email" name="mpr_business_email" 
                       value="<?php echo esc_attr($values['mpr_business_email'] ?? ''); ?>" 
                       class="widefat">
            </p>
            
            <p>
                <label for="mpr_business_address"><?php _e('Address', 'mpr-reviews'); ?></label>
                <textarea id="mpr_business_address" name="mpr_business_address" 
                          class="widefat" rows="2"><?php echo esc_textarea($values['mpr_business_address'] ?? ''); ?></textarea>
            </p>
            
            <p>
                <label for="mpr_business_category_select"><?php _e('Primary Category', 'mpr-reviews'); ?></label>
                <?php
                wp_dropdown_categories(array(
                    'taxonomy' => 'mpr_business_category',
                    'name' => 'mpr_business_category_select',
                    'id' => 'mpr_business_category_select',
                    'show_option_all' => __('Select a category', 'mpr-reviews'),
                    'selected' => isset($values['mpr_business_category_select']) ? (int)$values['mpr_business_category_select'] : 0,
                    'hide_empty' => 0,
                ));
                ?>
            </p>
            
            <div class="mpr-rating-stats">
                <h4><?php _e('Rating Statistics', 'mpr-reviews'); ?></h4>
                <p>
                    <strong><?php _e('Average Rating:', 'mpr-reviews'); ?></strong>
                    <span class="mpr-average-rating">
                        <?php echo esc_html($values['mpr_business_average_rating']); ?> / 5.0
                    </span>
                </p>
                <p>
                    <strong><?php _e('Total Reviews:', 'mpr-reviews'); ?></strong>
                    <span class="mpr-total-reviews">
                        <?php echo esc_html($values['mpr_business_total_reviews']); ?>
                    </span>
                </p>
            </div>
        </div>
        
        <style>
        .mpr-meta-box p { margin-bottom: 15px; }
        .mpr-meta-box label { display: block; font-weight: 600; margin-bottom: 5px; }
        .mpr-meta-box input, .mpr-meta-box textarea, .mpr-meta-box select { width: 100%; }
        .mpr-rating-stats { background: #f0f0f0; padding: 10px; margin-top: 20px; }
        .mpr-rating-stats h4 { margin: 0 0 10px 0; }
        </style>
        <?php
    }
    
    /**
     * Render review meta box
     */
    public static function render_review_meta_box($post) {
        wp_nonce_field('mpr_review_meta', 'mpr_review_nonce');
        
        $rating = get_post_meta($post->ID, 'mpr_review_rating', true);
        $reviewer_email = get_post_meta($post->ID, 'mpr_reviewer_email', true);
        $reviewer_name = get_post_meta($post->ID, 'mpr_reviewer_name', true);
        $business_id = get_post_meta($post->ID, 'mpr_business_id', true);
        $helpful_count = get_post_meta($post->ID, 'mpr_helpful_count', true) ?: 0;
        $approval_status = get_post_meta($post->ID, 'mpr_approval_status', true) ?: 'pending';
        ?>
        <div class="mpr-review-meta">
            <p>
                <label for="mpr_review_rating"><?php _e('Rating (1-5)', 'mpr-reviews'); ?></label>
                <select id="mpr_review_rating" name="mpr_review_rating" class="widefat">
                    <option value="1" <?php selected($rating, '1'); ?>><?php _e('1 Star', 'mpr-reviews'); ?></option>
                    <option value="2" <?php selected($rating, '2'); ?>><?php _e('2 Stars', 'mpr-reviews'); ?></option>
                    <option value="3" <?php selected($rating, '3'); ?>><?php _e('3 Stars', 'mpr-reviews'); ?></option>
                    <option value="4" <?php selected($rating, '4'); ?>><?php _e('4 Stars', 'mpr-reviews'); ?></option>
                    <option value="5" <?php selected($rating, '5'); ?>><?php _e('5 Stars', 'mpr-reviews'); ?></option>
                </select>
            </p>
            
            <p>
                <label for="mpr_reviewer_name"><?php _e('Reviewer Name', 'mpr-reviews'); ?></label>
                <input type="text" id="mpr_reviewer_name" name="mpr_reviewer_name" 
                       value="<?php echo esc_attr($reviewer_name ?? ''); ?>" class="widefat">
            </p>
            
            <p>
                <label for="mpr_reviewer_email"><?php _e('Reviewer Email', 'mpr-reviews'); ?></label>
                <input type="email" id="mpr_reviewer_email" name="mpr_reviewer_email" 
                       value="<?php echo esc_attr($reviewer_email ?? ''); ?>" class="widefat">
            </p>
            
            <p>
                <label for="mpr_business_id"><?php _e('Business ID', 'mpr-reviews'); ?></label>
                <input type="number" id="mpr_business_id" name="mpr_business_id" 
                       value="<?php echo esc_attr($business_id ?? ''); ?>" class="widefat">
            </p>
            
            <p>
                <label for="mpr_approval_status"><?php _e('Approval Status', 'mpr-reviews'); ?></label>
                <select id="mpr_approval_status" name="mpr_approval_status" class="widefat">
                    <option value="pending" <?php selected($approval_status, 'pending'); ?>><?php _e('Pending', 'mpr-reviews'); ?></option>
                    <option value="approved" <?php selected($approval_status, 'approved'); ?>><?php _e('Approved', 'mpr-reviews'); ?></option>
                    <option value="rejected" <?php selected($approval_status, 'rejected'); ?>><?php _e('Rejected', 'mpr-reviews'); ?></option>
                </select>
            </p>
            
            <p>
                <strong><?php _e('Helpful Votes:', 'mpr-reviews'); ?></strong>
                <?php echo esc_html($helpful_count); ?>
            </p>
            
            <p>
                <strong><?php _e('Submission Date:', 'mpr-reviews'); ?></strong>
                <?php echo esc_html(get_the_date('Y-m-d H:i:s', $post)); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public static function save_meta_boxes($post_id, $post) {
        // Business meta
        if ($post->post_type === 'mpr_business') {
            if (!isset($_POST['mpr_business_nonce']) || 
                !wp_verify_nonce($_POST['mpr_business_nonce'], 'mpr_business_meta')) {
                return;
            }
            
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            
            $fields = array(
                'mpr_business_website',
                'mpr_business_phone',
                'mpr_business_email',
                'mpr_business_address',
                'mpr_business_category_select',
            );
            
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = $_POST[$field];
                    
                    // Sanitize based on field type
                    if ($field === 'mpr_business_website') {
                        $value = esc_url_raw($value);
                    } elseif ($field === 'mpr_business_email') {
                        $value = sanitize_email($value);
                    } elseif ($field === 'mpr_business_category_select') {
                        $value = absint($value);
                        // Update term relationship
                        wp_set_object_terms($post_id, $value, 'mpr_business_category');
                    } else {
                        $value = sanitize_textarea_field($value);
                    }
                    
                    update_post_meta($post_id, $field, $value);
                }
            }
        }
        
        // Review meta
        if ($post->post_type === 'mpr_review') {
            if (!isset($_POST['mpr_review_nonce']) || 
                !wp_verify_nonce($_POST['mpr_review_nonce'], 'mpr_review_meta')) {
                return;
            }
            
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            
            $fields = array(
                'mpr_review_rating' => 'absint',
                'mpr_reviewer_name' => 'sanitize_text_field',
                'mpr_reviewer_email' => 'sanitize_email',
                'mpr_business_id' => 'absint',
                'mpr_approval_status' => 'sanitize_text_field',
            );
            
            foreach ($fields as $field => $sanitizer) {
                if (isset($_POST[$field])) {
                    $value = $sanitizer($_POST[$field]);
                    update_post_meta($post_id, $field, $value);
                }
            }
        }
    }
    
    /**
     * Get business fields configuration
     */
    private static function get_business_fields() {
        return array(
            'mpr_business_website' => array(
                'type' => 'url',
                'label' => __('Website URL', 'mpr-reviews'),
            ),
            'mpr_business_phone' => array(
                'type' => 'text',
                'label' => __('Phone', 'mpr-reviews'),
            ),
            'mpr_business_email' => array(
                'type' => 'email',
                'label' => __('Email', 'mpr-reviews'),
            ),
            'mpr_business_address' => array(
                'type' => 'textarea',
                'label' => __('Address', 'mpr-reviews'),
            ),
        );
    }
    
    /**
     * Calculate average rating for a business
     */
    public static function calculate_average_rating($business_id) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT AVG(meta_value) as average
            FROM {$wpdb->postmeta}
            WHERE post_id IN (
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'mpr_review'
                AND post_status = 'publish'
            )
            AND meta_key = 'mpr_review_rating'
            AND post_id IN (
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = 'mpr_business_id'
                AND meta_value = %d
            )
        ", $business_id);
        
        $result = $wpdb->get_var($query);
        
        return $result ? round((float)$result, 1) : '0.0';
    }
    
    /**
     * Get total reviews for a business
     */
    public static function get_total_reviews($business_id) {
        $args = array(
            'post_type' => 'mpr_review',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'mpr_business_id',
                    'value' => $business_id,
                ),
            ),
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Get all meta for a business (for display)
     */
    public static function get_business_meta($business_id) {
        return array(
            'website' => get_post_meta($business_id, 'mpr_business_website', true),
            'phone' => get_post_meta($business_id, 'mpr_business_phone', true),
            'email' => get_post_meta($business_id, 'mpr_business_email', true),
            'address' => get_post_meta($business_id, 'mpr_business_address', true),
            'average_rating' => self::calculate_average_rating($business_id),
            'total_reviews' => self::get_total_reviews($business_id),
        );
    }
}

// Initialize meta fields
add_action('init', array('MPR_Meta_Fields', 'init'));