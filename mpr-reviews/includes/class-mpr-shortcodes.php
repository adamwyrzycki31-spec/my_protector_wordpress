<?php
/**
 * Shortcodes for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public static function init() {
        // Directory shortcode
        add_shortcode('mpr_business_directory', array(__CLASS__, 'render_business_directory'));
        
        // Single business shortcode
        add_shortcode('mpr_business', array(__CLASS__, 'render_single_business'));
        
        // Review form shortcode
        add_shortcode('mpr_review_form', array(__CLASS__, 'render_review_form'));
        
        // Search shortcode
        add_shortcode('mpr_search', array(__CLASS__, 'render_search_box'));
        
        // Featured businesses
        add_shortcode('mpr_featured_businesses', array(__CLASS__, 'render_featured_businesses'));
    }
    
    /**
     * Render business directory
     */
    public static function render_business_directory($atts = array()) {
        $atts = shortcode_atts(array(
            'per_page' => 10,
            'columns' => 2,
            'show_search' => 'true',
            'show_filters' => 'true',
            'category' => '',
        ), $atts);
        
        $per_page = absint($atts['per_page']);
        $columns = absint($atts['columns']);
        $paged = isset($_GET['mpr_page']) ? absint($_GET['mpr_page']) : 1;
        $search = isset($_GET['mpr_search']) ? sanitize_text_field($_GET['mpr_search']) : '';
        $category = isset($_GET['mpr_category']) ? absint($_GET['mpr_category']) : 0;
        $min_rating = isset($_GET['mpr_rating']) ? absint($_GET['mpr_rating']) : 0;
        
        // Build query
        $args = array(
            'post_type' => 'mpr_business',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $paged,
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
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
        
        ob_start();
        ?>
        <div class="mpr-directory-wrapper">
            <?php if ($atts['show_search'] === 'true' || $atts['show_filters'] === 'true') : ?>
            <div class="mpr-directory-filters">
                <form method="get" action="" class="mpr-filter-form">
                    <?php if ($atts['show_search'] === 'true') : ?>
                    <div class="mpr-search-box">
                        <input type="text" name="mpr_search" placeholder="<?php esc_attr_e('Search businesses...', 'mpr-reviews'); ?>" 
                               value="<?php echo esc_attr($search); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_filters'] === 'true') : ?>
                    <div class="mpr-filter-options">
                        <?php
                        wp_dropdown_categories(array(
                            'taxonomy' => 'mpr_business_category',
                            'name' => 'mpr_category',
                            'show_option_all' => __('All Categories', 'mpr-reviews'),
                            'selected' => $category,
                            'hide_empty' => 0,
                        ));
                        ?>
                        
                        <select name="mpr_rating" onchange="this.form.submit()">
                            <option value=""><?php _e('All Ratings', 'mpr-reviews'); ?></option>
                            <option value="5" <?php selected($min_rating, 5); ?>><?php _e('5 Stars', 'mpr-reviews'); ?></option>
                            <option value="4" <?php selected($min_rating, 4); ?>><?php _e('4+ Stars', 'mpr-reviews'); ?></option>
                            <option value="3" <?php selected($min_rating, 3); ?>><?php _e('3+ Stars', 'mpr-reviews'); ?></option>
                            <option value="2" <?php selected($min_rating, 2); ?>><?php _e('2+ Stars', 'mpr-reviews'); ?></option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="button"><?php _e('Filter', 'mpr-reviews'); ?></button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="mpr-directory-header">
                <h2><?php _e('Business Directory', 'mpr-reviews'); ?></h2>
                <span class="mpr-results-count">
                    <?php printf(_n('%d business found', '%d businesses found', $query->found_posts, 'mpr-reviews'), $query->found_posts); ?>
                </span>
            </div>
            
            <?php if ($query->have_posts()) : ?>
                <div class="mpr-businesses-grid mpr-grid-<?php echo esc_attr($columns); ?>">
                    <?php while ($query->have_posts()) : $query->the_post();
                        echo self::render_business_card(get_the_ID());
                    endwhile; ?>
                </div>
                
                <?php if ($query->max_num_pages > 1) : ?>
                <div class="mpr-pagination">
                    <?php
                    echo paginate_links(array(
                        'current' => $paged,
                        'total' => $query->max_num_pages,
                        'prev_text' => __('« Previous', 'mpr-reviews'),
                        'next_text' => __('Next »', 'mpr-reviews'),
                        'add_args' => array(
                            'mpr_search' => $search,
                            'mpr_category' => $category,
                            'mpr_rating' => $min_rating,
                        ),
                    ));
                    ?>
                </div>
                <?php endif; ?>
                
            <?php else : ?>
                <div class="mpr-no-results">
                    <p><?php _e('No businesses found matching your criteria.', 'mpr-reviews'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('mpr_business')); ?>" class="button">
                        <?php _e('View All Businesses', 'mpr-reviews'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render single business card
     */
    public static function render_business_card($business_id) {
        $meta = MPR_Meta_Fields::get_business_meta($business_id);
        $logo = get_the_post_thumbnail_url($business_id, 'medium');
        $categories = get_the_terms($business_id, 'mpr_business_category');
        ?>
        <div class="mpr-business-card">
            <?php if ($logo) : ?>
            <div class="mpr-business-logo">
                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(get_the_title($business_id)); ?>">
            </div>
            <?php endif; ?>
            
            <div class="mpr-business-info">
                <h3 class="mpr-business-name">
                    <a href="<?php echo esc_url(get_permalink($business_id)); ?>"><?php echo esc_html(get_the_title($business_id)); ?></a>
                </h3>
                
                <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                <div class="mpr-business-categories">
                    <?php foreach (array_slice($categories, 0, 2) as $cat) : ?>
                        <span class="mpr-category-tag"><?php echo esc_html($cat->name); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="mpr-business-rating">
                    <?php echo self::render_star_rating($meta['average_rating']); ?>
                    <span class="mpr-rating-value"><?php echo esc_html($meta['average_rating']); ?></span>
                    <span class="mpr-review-count">(<?php echo esc_html($meta['total_reviews']); ?> <?php _e('reviews', 'mpr-reviews'); ?>)</span>
                </div>
                
                <div class="mpr-business-excerpt">
                    <?php echo esc_html(wp_trim_words(get_the_excerpt($business_id), 20)); ?>
                </div>
                
                <div class="mpr-business-actions">
                    <a href="<?php echo esc_url(get_permalink($business_id)); ?>" class="button button-small">
                        <?php _e('View Details', 'mpr-reviews'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('mpr_write_review', '1', get_permalink($business_id))); ?>" class="button button-small button-primary">
                        <?php _e('Write a Review', 'mpr-reviews'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render star rating display
     */
    public static function render_star_rating($rating, $size = 'medium') {
        $full_stars = floor($rating);
        $empty_stars = 5 - $full_stars;
        $has_half = ($rating - $full_stars) >= 0.5;
        
        $html = '<div class="mpr-star-rating mpr-stars-' . esc_attr($size) . '">';
        
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<span class="mpr-star mpr-star-full">★</span>';
        }
        
        if ($has_half) {
            $html .= '<span class="mpr-star mpr-star-half">★</span>';
        }
        
        for ($i = 0; $i < $empty_stars - ($has_half ? 1 : 0); $i++) {
            $html .= '<span class="mpr-star mpr-star-empty">☆</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render single business
     */
    public static function render_single_business($atts = array()) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        if ($atts['id'] && get_post_type($atts['id']) === 'mpr_business') {
            $business_id = $atts['id'];
        } elseif (is_singular('mpr_business')) {
            global $post;
            $business_id = $post->ID;
        } else {
            return '<p class="mpr-error">' . __('Business not found.', 'mpr-reviews') . '</p>';
        }
        
        $meta = MPR_Meta_Fields::get_business_meta($business_id);
        $logo = get_the_post_thumbnail_url($business_id, 'large');
        $categories = get_the_terms($business_id, 'mpr_business_category');
        
        ob_start();
        ?>
        <div class="mpr-single-business">
            <?php if ($logo) : ?>
            <div class="mpr-business-logo">
                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(get_the_title($business_id)); ?>">
            </div>
            <?php endif; ?>
            
            <h1 class="mpr-business-title"><?php echo esc_html(get_the_title($business_id)); ?></h1>
            
            <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
            <div class="mpr-business-categories">
                <?php foreach ($categories as $cat) : ?>
                    <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="mpr-category-link"><?php echo esc_html($cat->name); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="mpr-business-rating-display">
                <?php echo self::render_star_rating($meta['average_rating'], 'large'); ?>
                <div class="mpr-rating-stats">
                    <span class="mpr-rating-number"><?php echo esc_html($meta['average_rating']); ?></span>
                    <span class="mpr-rating-count"><?php printf(_n('%d review', '%d reviews', $meta['total_reviews'], 'mpr-reviews'), $meta['total_reviews']); ?></span>
                </div>
            </div>
            
            <div class="mpr-business-description">
                <?php echo wp_kses_post(get_post_field('post_content', $business_id)); ?>
            </div>
            
            <div class="mpr-business-contact">
                <h3><?php _e('Contact Information', 'mpr-reviews'); ?></h3>
                <?php if ($meta['website']) : ?>
                <p><strong><?php _e('Website:', 'mpr-reviews'); ?></strong> <a href="<?php echo esc_url($meta['website']); ?>" target="_blank" rel="noopener"><?php echo esc_html($meta['website']); ?></a></p>
                <?php endif; ?>
                <?php if ($meta['phone']) : ?>
                <p><strong><?php _e('Phone:', 'mpr-reviews'); ?></strong> <a href="tel:<?php echo esc_attr($meta['phone']); ?>"><?php echo esc_html($meta['phone']); ?></a></p>
                <?php endif; ?>
                <?php if ($meta['email']) : ?>
                <p><strong><?php _e('Email:', 'mpr-reviews'); ?></strong> <a href="mailto:<?php echo esc_attr($meta['email']); ?>"><?php echo esc_html($meta['email']); ?></a></p>
                <?php endif; ?>
                <?php if ($meta['address']) : ?>
                <p><strong><?php _e('Address:', 'mpr-reviews'); ?></strong><br><?php echo nl2br(esc_html($meta['address'])); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mpr-business-actions">
                <a href="<?php echo esc_url(add_query_arg('mpr_write_review', '1', get_permalink($business_id))); ?>" class="button button-primary button-large">
                    <?php _e('Write a Review', 'mpr-reviews'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render review form
     */
    public static function render_review_form($atts = array()) {
        $atts = shortcode_atts(array(
            'business_id' => 0,
        ), $atts);
        
        $business_id = $atts['business_id'] ?: (isset($_GET['mpr_business_id']) ? absint($_GET['mpr_business_id']) : 0);
        
        if (!$business_id) {
            return '<p class="mpr-error">' . __('No business specified.', 'mpr-reviews') . '</p>';
        }
        
        if (get_post_type($business_id) !== 'mpr_business') {
            return '<p class="mpr-error">' . __('Invalid business.', 'mpr-reviews') . '</p>';
        }
        
        $business_title = get_the_title($business_id);
        
        ob_start();
        ?>
        <div class="mpr-review-form-wrapper">
            <h2><?php printf(__('Write a Review for %s', 'mpr-reviews'), esc_html($business_title)); ?></h2>
            
            <?php
            // Show messages
            if (isset($_GET['mpr_success'])) {
                echo '<div class="mpr-success-message">' . __('Thank you! Your review has been submitted.', 'mpr-reviews') . '</div>';
            }
            if (isset($_GET['mpr_error'])) {
                $error_messages = array(
                    'security' => __('Security verification failed. Please refresh the page and try again.', 'mpr-reviews'),
                    'validation' => __('Please fill in all required fields correctly.', 'mpr-reviews'),
                    'spam' => __('Your review appears to contain spam content.', 'mpr-reviews'),
                    'login_required' => __('You must be logged in to submit a review.', 'mpr-reviews'),
                    'failed' => __('Failed to submit review. Please try again.', 'mpr-reviews'),
                );
                $error = sanitize_text_field($_GET['mpr_error']);
                if (isset($error_messages[$error])) {
                    echo '<div class="mpr-error-message">' . esc_html($error_messages[$error]) . '</div>';
                }
            }
            ?>
            
            <form method="post" action="" class="mpr-form mpr-review-form">
                <input type="hidden" name="mpr_review_submission" value="1">
                <input type="hidden" name="mpr_business_id" value="<?php echo esc_attr($business_id); ?>">
                <?php wp_nonce_field('mpr_review_action', 'mpr_review_nonce'); ?>
                
                <div class="mpr-form-row">
                    <p class="mpr-form-field">
                        <label for="mpr_review_rating"><?php _e('Your Rating *', 'mpr-reviews'); ?></label>
                        <div class="mpr-rating-selector">
                            <?php for ($i = 5; $i >= 1; $i--) : ?>
                            <label class="mpr-rating-option">
                                <input type="radio" name="mpr_review_rating" value="<?php echo esc_attr($i); ?>" required>
                                <span class="mpr-rating-star">★</span>
                                <span class="mpr-rating-label"><?php echo esc_html($i); ?></span>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </p>
                </div>
                
                <div class="mpr-form-row">
                    <p class="mpr-form-field">
                        <label for="mpr_review_title"><?php _e('Review Title *', 'mpr-reviews'); ?></label>
                        <input type="text" name="mpr_review_title" id="mpr_review_title" required 
                               minlength="3" maxlength="200" class="widefat" 
                               placeholder="<?php esc_attr_e('Summarize your experience', 'mpr-reviews'); ?>">
                    </p>
                </div>
                
                <div class="mpr-form-row">
                    <p class="mpr-form-field">
                        <label for="mpr_review_content"><?php _e('Your Review *', 'mpr-reviews'); ?></label>
                        <textarea name="mpr_review_content" id="mpr_review_content" required 
                                  minlength="10" rows="6" class="widefat"
                                  placeholder="<?php esc_attr_e('Share your detailed experience with this business...', 'mpr-reviews'); ?>"></textarea>
                    </p>
                </div>
                
                <div class="mpr-form-row mpr-form-row-half">
                    <p class="mpr-form-field">
                        <label for="mpr_reviewer_name"><?php _e('Your Name *', 'mpr-reviews'); ?></label>
                        <input type="text" name="mpr_reviewer_name" id="mpr_reviewer_name" required 
                               minlength="2" class="widefat">
                    </p>
                    
                    <p class="mpr-form-field">
                        <label for="mpr_reviewer_email"><?php _e('Your Email *', 'mpr-reviews'); ?></label>
                        <input type="email" name="mpr_reviewer_email" id="mpr_reviewer_email" required class="widefat">
                    </p>
                </div>
                
                <?php echo MPR_Security::honeypot_field(); ?>
                
                <p class="mpr-form-submit">
                    <input type="submit" value="<?php esc_attr_e('Submit Review', 'mpr-reviews'); ?>" class="button button-primary button-large">
                </p>
                
                <p class="mpr-form-note">
                    <?php _e('Your review will be visible after admin approval.', 'mpr-reviews'); ?>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render search box
     */
    public static function render_search_box($atts = array()) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Search businesses...', 'mpr-reviews'),
            'button_text' => __('Search', 'mpr-reviews'),
        ), $atts);
        
        $current_search = isset($_GET['mpr_search']) ? sanitize_text_field($_GET['mpr_search']) : '';
        
        ob_start();
        ?>
        <div class="mpr-search-wrapper">
            <form method="get" action="" class="mpr-search-form">
                <div class="mpr-search-input-wrapper">
                    <input type="text" name="mpr_search" value="<?php echo esc_attr($current_search); ?>" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>" class="mpr-search-input">
                    <button type="submit" class="mpr-search-button">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render featured businesses
     */
    public static function render_featured_businesses($atts = array()) {
        $atts = shortcode_atts(array(
            'count' => 4,
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'title' => __('Featured Businesses', 'mpr-reviews'),
        ), $atts);
        
        $args = array(
            'post_type' => 'mpr_business',
            'post_status' => 'publish',
            'posts_per_page' => absint($atts['count']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="mpr-featured-businesses">
            <h2 class="mpr-featured-title"><?php echo esc_html($atts['title']); ?></h2>
            <div class="mpr-featured-grid">
                <?php while ($query->have_posts()) : $query->the_post();
                    echo self::render_business_card(get_the_ID());
                endwhile; ?>
            </div>
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize shortcodes
add_action('init', array('MPR_Shortcodes', 'init'));