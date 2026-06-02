<?php
/**
 * Single Business Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="main" class="site-main mpr-single-business-page" role="main">
    
    <?php while (have_posts()) : the_post(); 
    
        $meta = MPR_Meta_Fields::get_business_meta(get_the_ID());
        $categories = get_the_terms(get_the_ID(), 'mpr_business_category');
        $logo = get_the_post_thumbnail_url(get_the_ID(), 'large');
        
        // Check if we're showing review form
        $show_form = isset($_GET['mpr_write_review']);
    ?>
    
        <article id="business-<?php the_ID(); ?>" <?php post_class(); ?>>
            
            <header class="mpr-business-header">
                <?php if ($logo) : ?>
                <div class="mpr-business-logo">
                    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
                <?php endif; ?>
                
                <h1 class="mpr-business-title"><?php the_title(); ?></h1>
                
                <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                <div class="mpr-business-categories">
                    <?php foreach ($categories as $cat) : ?>
                        <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="mpr-category-link">
                            <?php echo esc_html($cat->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($meta['total_reviews'] > 0) : ?>
                <div class="mpr-business-rating-display">
                    <?php echo MPR_Shortcodes::render_star_rating($meta['average_rating'], 'large'); ?>
                    <div class="mpr-rating-stats">
                        <span class="mpr-rating-number"><?php echo esc_html($meta['average_rating']); ?></span>
                        <span class="mpr-rating-count">
                            <?php printf(_n('(%d review)', '(%d reviews)', $meta['total_reviews'], 'mpr-reviews'), $meta['total_reviews']); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </header>
            
            <div class="mpr-business-content">
                <?php the_content(); ?>
            </div>
            
            <div class="mpr-business-contact">
                <h3><?php _e('Contact Information', 'mpr-reviews'); ?></h3>
                <ul class="mpr-contact-list">
                    <?php if ($meta['website']) : ?>
                    <li>
                        <strong><?php _e('Website:', 'mpr-reviews'); ?></strong>
                        <a href="<?php echo esc_url($meta['website']); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($meta['website']); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($meta['phone']) : ?>
                    <li>
                        <strong><?php _e('Phone:', 'mpr-reviews'); ?></strong>
                        <a href="tel:<?php echo esc_attr($meta['phone']); ?>"><?php echo esc_html($meta['phone']); ?></a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($meta['email']) : ?>
                    <li>
                        <strong><?php _e('Email:', 'mpr-reviews'); ?></strong>
                        <a href="mailto:<?php echo esc_attr($meta['email']); ?>"><?php echo esc_html($meta['email']); ?></a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($meta['address']) : ?>
                    <li>
                        <strong><?php _e('Address:', 'mpr-reviews'); ?></strong>
                        <address><?php echo nl2br(esc_html($meta['address'])); ?></address>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <?php if ($show_form) : ?>
            <div class="mpr-review-form-section">
                <?php echo do_shortcode('[mpr_review_form business_id="' . get_the_ID() . '"]'); ?>
            </div>
            <?php else : ?>
            <div class="mpr-reviews-cta">
                <a href="<?php echo esc_url(add_query_arg('mpr_write_review', '1')); ?>" class="button button-primary button-large">
                    <?php _e('Write a Review', 'mpr-reviews'); ?>
                </a>
            </div>
            <?php endif; ?>
            
        </article>
        
        <?php 
        // Show reviews section (unless showing form)
        if (!$show_form) {
            echo MPR_Frontend::render_business_reviews(get_the_ID());
        }
        ?>
        
    <?php endwhile; ?>
    
</main>

<?php get_footer(); ?>