<?php
/**
 * SEO utilities for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_SEO {
    
    /**
     * Initialize SEO hooks
     */
    public static function init() {
        add_action('wp_head', array(__CLASS__, 'output_opengraph_tags'), 5);
        add_action('wp_head', array(__CLASS__, 'output_schema_markup'), 5);
        add_filter('pre_get_document_title', array(__CLASS__, 'filter_document_title'), 20);
        add_filter('wp_title', array(__CLASS__, 'filter_wp_title'), 20, 3);
    }
    
    /**
     * Output OpenGraph meta tags
     */
    public static function output_opengraph_tags() {
        if (!is_singular('mpr_business') && !is_singular('mpr_review')) {
            return;
        }
        
        global $post;
        
        if (is_singular('mpr_business')) {
            self::output_business_og_tags($post);
        } elseif (is_singular('mpr_review')) {
            self::output_review_og_tags($post);
        }
    }
    
    /**
     * Output OpenGraph tags for business
     */
    private static function output_business_og_tags($post) {
        $title = get_bloginfo('name') . ' - ' . get_the_title($post->ID);
        $description = get_the_excerpt($post->ID) ?: wp_trim_words($post->post_content, 30);
        $image = get_the_post_thumbnail_url($post->ID, 'large');
        $url = get_permalink($post->ID);
        $meta = MPR_Meta_Fields::get_business_meta($post->ID);
        
        ?>
        <meta property="og:type" content="website" />
        <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
        <meta property="og:description" content="<?php echo esc_attr($description); ?>" />
        <meta property="og:url" content="<?php echo esc_url($url); ?>" />
        <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>" />
        <?php if ($image) : ?>
        <meta property="og:image" content="<?php echo esc_url($image); ?>" />
        <?php endif; ?>
        <meta property="og:locale" content="<?php echo esc_attr(get_locale()); ?>" />
        
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
        <meta name="twitter:description" content="<?php echo esc_attr($description); ?>" />
        <?php if ($image) : ?>
        <meta name="twitter:image" content="<?php echo esc_url($image); ?>" />
        <?php endif; ?>
        <?php
    }
    
    /**
     * Output OpenGraph tags for review
     */
    private static function output_review_og_tags($post) {
        $business_id = get_post_meta($post->ID, 'mpr_business_id', true);
        $business_title = $business_id ? get_the_title($business_id) : '';
        $reviewer_name = get_post_meta($post->ID, 'mpr_reviewer_name', true);
        $rating = get_post_meta($post->ID, 'mpr_review_rating', true);
        
        $title = sprintf(__('Review of %s by %s - %d/5 stars', 'mpr-reviews'), 
            $business_title, 
            $reviewer_name,
            $rating
        );
        
        $description = wp_trim_words($post->post_content, 30);
        $url = get_permalink($post->ID);
        
        ?>
        <meta property="og:type" content="article" />
        <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
        <meta property="og:description" content="<?php echo esc_attr($description); ?>" />
        <meta property="og:url" content="<?php echo esc_url($url); ?>" />
        <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>" />
        <meta property="article:published_time" content="<?php echo esc_attr(get_the_date('c', $post)); ?>" />
        
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
        <meta name="twitter:description" content="<?php echo esc_attr($description); ?>" />
        <?php
    }
    
    /**
     * Output Schema.org markup
     */
    public static function output_schema_markup() {
        if (!is_singular('mpr_business')) {
            return;
        }
        
        global $post;
        $meta = MPR_Meta_Fields::get_business_meta($post->ID);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => get_the_title($post->ID),
            'description' => $post->post_content,
            'url' => $meta['website'] ?: get_permalink($post->ID),
            'telephone' => $meta['phone'],
            'email' => $meta['email'],
            'address' => array(
                '@type' => 'PostalAddress',
                'addressLocality' => self::extract_address_part($meta['address'], 'city'),
                'addressCountry' => self::extract_address_part($meta['address'], 'country'),
            ),
            'aggregateRating' => array(
                '@type' => 'AggregateRating',
                'ratingValue' => $meta['average_rating'],
                'reviewCount' => $meta['total_reviews'],
                'bestRating' => '5',
                'worstRating' => '1',
            ),
        );
        
        // Add logo if exists
        $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');
        if ($thumbnail) {
            $schema['image'] = $thumbnail;
        }
        
        // Get reviews
        $reviews = self::get_business_reviews($post->ID, 5);
        if (!empty($reviews)) {
            $schema['review'] = $reviews;
        }
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    /**
     * Get approved reviews for schema
     */
    private static function get_business_reviews($business_id, $limit = 5) {
        $args = array(
            'post_type' => 'mpr_review',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => 'mpr_business_id',
            'meta_value' => $business_id,
        );
        
        $query = new WP_Query($args);
        $reviews = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $reviewer_name = get_post_meta(get_the_ID(), 'mpr_reviewer_name', true);
                $rating = get_post_meta(get_the_ID(), 'mpr_review_rating', true);
                
                $reviews[] = array(
                    '@type' => 'Review',
                    'author' => array(
                        '@type' => 'Person',
                        'name' => $reviewer_name ?: 'Anonymous',
                    ),
                    'reviewRating' => array(
                        '@type' => 'Rating',
                        'ratingValue' => $rating,
                        'bestRating' => '5',
                        'worstRating' => '1',
                    ),
                    'reviewBody' => get_the_content(),
                    'datePublished' => get_the_date('Y-m-d'),
                );
            }
            
            wp_reset_postdata();
        }
        
        return $reviews;
    }
    
    /**
     * Extract address part from full address
     */
    private static function extract_address_part($address, $part) {
        if (empty($address)) {
            return '';
        }
        
        // Simple extraction - can be enhanced with geocoding
        $lines = explode("\n", $address);
        if ($part === 'city' && isset($lines[0])) {
            return trim($lines[0]);
        }
        if ($part === 'country' && isset($lines[1])) {
            return trim($lines[1]);
        }
        
        return '';
    }
    
    /**
     * Filter document title
     */
    public static function filter_document_title($title) {
        if (is_singular('mpr_business')) {
            global $post;
            $title = get_the_title($post->ID) . ' | ' . get_bloginfo('name');
        }
        
        return $title;
    }
    
    /**
     * Filter wp_title output
     */
    public static function filter_wp_title($title, $sep, $seplocation) {
        if (is_singular('mpr_business')) {
            global $post;
            if ($seplocation === 'right') {
                $title = get_the_title($post->ID) . " $sep " . get_bloginfo('name');
            } else {
                $title = get_bloginfo('name') . " $sep " . get_the_title($post->ID);
            }
        }
        
        return $title;
    }
    
    /**
     * Generate breadcrumb schema
     */
    public static function get_breadcrumb_schema($items) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(),
        );
        
        $position = 1;
        foreach ($items as $item) {
            $schema['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['name'],
                'item' => $item['url'],
            );
            $position++;
        }
        
        return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
}

// Initialize SEO
add_action('init', array('MPR_SEO', 'init'));