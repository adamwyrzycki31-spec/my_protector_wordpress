<?php
/**
 * Frontend functionality for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Frontend {
    
    /**
     * Initialize frontend hooks
     */
    public static function init() {
        // Template redirects
        add_action('template_redirect', array(__CLASS__, 'template_redirect'));
        
        // Single business template
        add_filter('single_template', array(__CLASS__, 'single_business_template'));
        
        // Content filters
        add_filter('the_content', array(__CLASS__, 'business_content'), 20);
        add_filter('the_excerpt', array(__CLASS__, 'business_excerpt'), 20);
        
        // Comments (for reviews)
        add_filter('comments_open', array(__CLASS__, 'disable_comments'), 10, 2);
    }
    
    /**
     * Template redirect handler
     */
    public static function template_redirect() {
        // Handle query vars for review submission
        if (isset($_GET['mpr_write_review']) && is_singular('mpr_business')) {
            // Store flag for template to use
            global $wp_query;
            $wp_query->set('mpr_write_review', '1');
        }
    }
    
    /**
     * Load single business template
     */
    public static function single_business_template($template) {
        if (is_singular('mpr_business')) {
            $custom_template = locate_template('single-mpr_business.php');
            
            if ($custom_template) {
                return $custom_template;
            }
            
            // Check plugin templates
            $plugin_template = MPR_PLUGIN_DIR . 'templates/single-mpr_business.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Filter business content
     */
    public static function business_content($content) {
        if (!is_singular('mpr_business')) {
            return $content;
        }
        
        global $post;
        
        // Check if we're showing review form (query var or GET param)
        $show_form = isset($wp_query->query_vars['mpr_write_review']) || 
                     isset($_GET['mpr_write_review']);
        
        if ($show_form) {
            return $content . do_shortcode('[mpr_review_form business_id="' . $post->ID . '"]');
        }
        
        // Show reviews list instead
        return $content . self::render_business_reviews($post->ID);
    }
    
    /**
     * Filter business excerpt
     */
    public static function business_excerpt($excerpt) {
        if (!is_singular('mpr_business')) {
            return $excerpt;
        }
        
        global $post;
        $meta = MPR_Meta_Fields::get_business_meta($post->ID);
        
        $excerpt = '<div class="mpr-excerpt-rating">';
        $excerpt .= MPR_Shortcodes::render_star_rating($meta['average_rating'], 'small');
        $excerpt .= ' <span class="mpr-excerpt-meta">(' . esc_html($meta['total_reviews']) . ' ' . __('reviews', 'mpr-reviews') . ')</span>';
        $excerpt .= '</div>';
        
        return $excerpt;
    }
    
    /**
     * Render business reviews section
     */
    public static function render_business_reviews($business_id) {
        $per_page = get_option('mpr_reviews_per_page', 10);
        $paged = isset($_GET['mpr_review_page']) ? absint($_GET['mpr_review_page']) : 1;
        
        $args = array(
            'post_type' => 'mpr_review',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'meta_key' => 'mpr_business_id',
            'meta_value' => $business_id,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $query = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="mpr-business-reviews" id="mpr-reviews">
            <h2><?php _e('Customer Reviews', 'mpr-reviews'); ?></h2>
            
            <div class="mpr-reviews-actions">
                <a href="<?php echo esc_url(add_query_arg('mpr_write_review', '1')); ?>" class="button button-primary">
                    <?php _e('Write a Review', 'mpr-reviews'); ?>
                </a>
                <?php
                $meta = MPR_Meta_Fields::get_business_meta($business_id);
                if ($meta['total_reviews'] > 0) :
                ?>
                <div class="mpr-reviews-summary">
                    <?php echo MPR_Shortcodes::render_star_rating($meta['average_rating'], 'medium'); ?>
                    <span class="mpr-summary-text">
                        <?php printf(__('%.1f out of 5'), esc_html($meta['average_rating'])); ?>
                        <?php printf(_n('(%d review)', '(%d reviews)', $meta['total_reviews'], 'mpr-reviews'), $meta['total_reviews']); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($query->have_posts()) : ?>
                <div class="mpr-reviews-list">
                    <?php while ($query->have_posts()) : $query->the_post();
                        $rating = get_post_meta(get_the_ID(), 'mpr_review_rating', true);
                        $reviewer_name = get_post_meta(get_the_ID(), 'mpr_reviewer_name', true);
                        $helpful_count = get_post_meta(get_the_ID(), 'mpr_helpful_count', true) ?: 0;
                        ?>
                        <div class="mpr-review-item" id="review-<?php the_ID(); ?>">
                            <div class="mpr-review-header">
                                <div class="mpr-review-author">
                                    <span class="mpr-author-avatar">
                                        <?php echo esc_html(substr($reviewer_name ?: 'A', 0, 1)); ?>
                                    </span>
                                    <div class="mpr-author-info">
                                        <strong class="mpr-author-name"><?php echo esc_html($reviewer_name ?: __('Anonymous', 'mpr-reviews')); ?></strong>
                                        <span class="mpr-review-date"><?php echo get_the_date('F j, Y'); ?></span>
                                    </div>
                                </div>
                                <div class="mpr-review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <span class="mpr-star <?php echo $i <= $rating ? 'filled' : 'empty'; ?>"><?php echo ($i <= $rating) ? '★' : '☆'; ?></span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <h3 class="mpr-review-title"><?php echo esc_html(get_the_title()); ?></h3>
                            
                            <div class="mpr-review-content">
                                <?php the_content(); ?>
                            </div>
                            
                            <div class="mpr-review-footer">
                                <span class="mpr-helpful-count">
                                    <?php printf(_n('%d person found this helpful', '%d people found this helpful', $helpful_count, 'mpr-reviews'), $helpful_count); ?>
                                </span>
                                <button class="mpr-helpful-btn" data-review-id="<?php the_ID(); ?>">
                                    <?php _e('Helpful', 'mpr-reviews'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if ($query->max_num_pages > 1) : ?>
                <div class="mpr-pagination">
                    <?php
                    echo paginate_links(array(
                        'current' => $paged,
                        'total' => $query->max_num_pages,
                        'prev_text' => __('« Previous', 'mpr-reviews'),
                        'next_text' => __('Next »', 'mpr-reviews'),
                        'add_args' => array('mpr_review_page' => ''),
                    ));
                    ?>
                </div>
                <?php endif; ?>
                
            <?php else : ?>
                <div class="mpr-no-reviews">
                    <p><?php _e('No reviews yet. Be the first to review this business!', 'mpr-reviews'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Disable comments for review post type
     */
    public static function disable_comments($open, $post_id) {
        if (get_post_type($post_id) === 'mpr_review') {
            return false;
        }
        return $open;
    }
}

// Initialize frontend
add_action('init', array('MPR_Frontend', 'init'));