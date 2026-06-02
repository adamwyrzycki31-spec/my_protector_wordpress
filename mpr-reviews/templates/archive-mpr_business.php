<?php
/**
 * Business Archive Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$per_page = get_option('mpr_reviews_per_page', 10);
$paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;
$search = isset($_GET['mpr_search']) ? sanitize_text_field($_GET['mpr_search']) : '';
$category = isset($_GET['mpr_category']) ? absint($_GET['mpr_category']) : 0;
$min_rating = isset($_GET['mpr_rating']) ? absint($_GET['mpr_rating']) : 0;

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
?>

<main id="main" class="site-main mpr-archive-page" role="main">
    
    <div class="mpr-directory-wrapper">
        
        <header class="mpr-archive-header">
            <h1 class="mpr-archive-title"><?php post_type_archive_title(); ?></h1>
            <p class="mpr-archive-description">
                <?php _e('Discover and review local businesses. Find the best services near you.', 'mpr-reviews'); ?>
            </p>
        </header>
        
        <div class="mpr-directory-filters">
            <form method="get" action="" class="mpr-filter-form">
                
                <div class="mpr-search-box">
                    <input type="text" name="mpr_search" 
                           placeholder="<?php esc_attr_e('Search businesses...', 'mpr-reviews'); ?>" 
                           value="<?php echo esc_attr($search); ?>">
                </div>
                
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
                    
                    <select name="mpr_rating">
                        <option value=""><?php _e('All Ratings', 'mpr-reviews'); ?></option>
                        <option value="5" <?php selected($min_rating, 5); ?>><?php _e('5 Stars', 'mpr-reviews'); ?></option>
                        <option value="4" <?php selected($min_rating, 4); ?>><?php _e('4+ Stars', 'mpr-reviews'); ?></option>
                        <option value="3" <?php selected($min_rating, 3); ?>><?php _e('3+ Stars', 'mpr-reviews'); ?></option>
                        <option value="2" <?php selected($min_rating, 2); ?>><?php _e('2+ Stars', 'mpr-reviews'); ?></option>
                    </select>
                </div>
                
                <button type="submit" class="button"><?php _e('Filter', 'mpr-reviews'); ?></button>
                
                <?php if (!empty($search) || $category > 0 || $min_rating > 0) : ?>
                <a href="<?php echo esc_url(get_post_type_archive_link('mpr_business')); ?>" class="button button-secondary">
                    <?php _e('Clear', 'mpr-reviews'); ?>
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="mpr-directory-header">
            <h2><?php _e('All Businesses', 'mpr-reviews'); ?></h2>
            <span class="mpr-results-count">
                <?php printf(_n('%d business found', '%d businesses found', $query->found_posts, 'mpr-reviews'), $query->found_posts); ?>
            </span>
        </div>
        
        <?php if ($query->have_posts()) : ?>
        
            <div class="mpr-businesses-grid mpr-grid-2">
                <?php while ($query->have_posts()) : $query->the_post();
                    echo MPR_Shortcodes::render_business_card(get_the_ID());
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
                <h3><?php _e('No businesses found', 'mpr-reviews'); ?></h3>
                <p><?php _e('Try adjusting your search or filter criteria.', 'mpr-reviews'); ?></p>
                <a href="<?php echo esc_url(get_post_type_archive_link('mpr_business')); ?>" class="button">
                    <?php _e('View all businesses', 'mpr-reviews'); ?>
                </a>
            </div>
            
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
        
    </div>
    
</main>

<?php get_footer(); ?>