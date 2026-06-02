<?php
/**
 * Register Custom Post Types for MPR Reviews
 */

if (!defined('ABSPATH')) {
    exit;
}

class MPR_Post_Types {
    
    /**
     * Register post types and taxonomies
     */
    public static function register() {
        self::register_business_post_type();
        self::register_review_post_type();
        self::register_business_category_taxonomy();
        self::register_review_tags_taxonomy();
        self::register_rewrite_rules();
    }
    
    /**
     * Register Business post type
     */
    private static function register_business_post_type() {
        $labels = array(
            'name'                  => _x('Businesses', 'Post Type General Name', 'mpr-reviews'),
            'singular_name'         => _x('Business', 'Post Type Singular Name', 'mpr-reviews'),
            'menu_name'             => __('MPR Reviews', 'mpr-reviews'),
            'name_admin_bar'        => __('Business', 'mpr-reviews'),
            'archives'              => __('Business Archives', 'mpr-reviews'),
            'attributes'            => __('Business Attributes', 'mpr-reviews'),
            'parent_item_colon'     => __('Parent Business:', 'mpr-reviews'),
            'all_items'             => __('All Businesses', 'mpr-reviews'),
            'add_new_item'          => __('Add New Business', 'mpr-reviews'),
            'add_new'               => __('Add New', 'mpr-reviews'),
            'new_item'              => __('New Business', 'mpr-reviews'),
            'edit_item'             => __('Edit Business', 'mpr-reviews'),
            'update_item'          => __('Update Business', 'mpr-reviews'),
            'view_item'             => __('View Business', 'mpr-reviews'),
            'view_items'            => __('View Businesses', 'mpr-reviews'),
            'search_items'          => __('Search Business', 'mpr-reviews'),
            'not_found'             => __('Not found', 'mpr-reviews'),
            'not_found_in_trash'    => __('Not found in Trash', 'mpr-reviews'),
            'featured_image'        => __('Logo', 'mpr-reviews'),
            'set_featured_image'    => __('Set logo', 'mpr-reviews'),
            'remove_featured_image' => __('Remove logo', 'mpr-reviews'),
            'use_featured_image'    => __('Use as logo', 'mpr-reviews'),
            'insert_into_item'      => __('Insert into business', 'mpr-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this business', 'mpr-reviews'),
            'items_list'            => __('Businesses list', 'mpr-reviews'),
            'items_list_navigation' => __('Businesses list navigation', 'mpr-reviews'),
            'filter_items_list'     => __('Filter businesses list', 'mpr-reviews'),
        );
        
        $args = array(
            'label'               => __('Business', 'mpr-reviews'),
            'description'         => __('Business listings for reviews', 'mpr-reviews'),
            'labels'              => $labels,
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-building',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => 'businesses',
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'rewrite'             => array(
                'slug'       => 'business',
                'with_front' => true,
                'pages'      => true,
                'feeds'      => true,
            ),
        );
        
        register_post_type('mpr_business', $args);
    }
    
    /**
     * Register Review post type
     */
    private static function register_review_post_type() {
        $labels = array(
            'name'                  => _x('Reviews', 'Post Type General Name', 'mpr-reviews'),
            'singular_name'         => _x('Review', 'Post Type Singular Name', 'mpr-reviews'),
            'menu_name'             => __('Reviews', 'mpr-reviews'),
            'name_admin_bar'        => __('Review', 'mpr-reviews'),
            'archives'              => __('Review Archives', 'mpr-reviews'),
            'attributes'            => __('Review Attributes', 'mpr-reviews'),
            'parent_item_colon'     => __('Parent Review:', 'mpr-reviews'),
            'all_items'             => __('All Reviews', 'mpr-reviews'),
            'add_new_item'          => __('Add New Review', 'mpr-reviews'),
            'add_new'               => __('Add New', 'mpr-reviews'),
            'new_item'              => __('New Review', 'mpr-reviews'),
            'edit_item'             => __('Edit Review', 'mpr-reviews'),
            'update_item'          => __('Update Review', 'mpr-reviews'),
            'view_item'             => __('View Review', 'mpr-reviews'),
            'view_items'            => __('View Reviews', 'mpr-reviews'),
            'search_items'          => __('Search Review', 'mpr-reviews'),
            'not_found'             => __('Not found', 'mpr-reviews'),
            'not_found_in_trash'    => __('Not found in Trash', 'mpr-reviews'),
            'featured_image'        => __('Reviewer Avatar', 'mpr-reviews'),
            'set_featured_image'    => __('Set avatar', 'mpr-reviews'),
            'remove_featured_image' => __('Remove avatar', 'mpr-reviews'),
            'use_featured_image'    => __('Use as avatar', 'mpr-reviews'),
            'insert_into_item'      => __('Insert into review', 'mpr-reviews'),
            'uploaded_to_this_item' => __('Uploaded to this review', 'mpr-reviews'),
            'items_list'            => __('Reviews list', 'mpr-reviews'),
            'items_list_navigation' => __('Reviews list navigation', 'mpr-reviews'),
            'filter_items_list'     => __('Filter reviews list', 'mpr-reviews'),
        );
        
        $args = array(
            'label'               => __('Review', 'mpr-reviews'),
            'description'         => __('Business reviews', 'mpr-reviews'),
            'labels'              => $labels,
            'supports'            => array('title', 'editor', 'author', 'custom-fields'),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-star-filled',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'rewrite'             => array(
                'slug'       => 'review',
                'with_front' => false,
                'pages'      => false,
            ),
        );
        
        register_post_type('mpr_review', $args);
    }
    
    /**
     * Register Business Category taxonomy
     */
    private static function register_business_category_taxonomy() {
        $labels = array(
            'name'                       => _x('Business Categories', 'Taxonomy General Name', 'mpr-reviews'),
            'singular_name'              => _x('Business Category', 'Taxonomy Singular Name', 'mpr-reviews'),
            'menu_name'                  => __('Categories', 'mpr-reviews'),
            'all_items'                  => __('All Categories', 'mpr-reviews'),
            'parent_item'                => __('Parent Category', 'mpr-reviews'),
            'parent_item_colon'          => __('Parent Category:', 'mpr-reviews'),
            'new_item_name'              => __('New Category Name', 'mpr-reviews'),
            'add_new_item'               => __('Add New Category', 'mpr-reviews'),
            'edit_item'                  => __('Edit Category', 'mpr-reviews'),
            'update_item'                => __('Update Category', 'mpr-reviews'),
            'view_item'                  => __('View Category', 'mpr-reviews'),
            'separate_items_with_commas' => __('Separate categories with commas', 'mpr-reviews'),
            'add_or_remove_items'        => __('Add or remove categories', 'mpr-reviews'),
            'choose_from_most_used'      => __('Choose from the most used', 'mpr-reviews'),
            'popular_items'              => __('Popular Categories', 'mpr-reviews'),
            'search_items'               => __('Search Categories', 'mpr-reviews'),
            'not_found'                  => __('Not Found', 'mpr-reviews'),
            'no_terms'                   => __('No categories', 'mpr-reviews'),
            'items_list'                 => __('Categories list', 'mpr-reviews'),
            'items_list_navigation'       => __('Categories list navigation', 'mpr-reviews'),
        );
        
        $args = array(
            'labels'             => $labels,
            'hierarchical'       => true,
            'public'             => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_nav_menus'  => true,
            'show_tagcloud'      => true,
            'show_in_rest'       => true,
            'query_var'          => 'business_category',
            'rewrite'            => array(
                'slug'         => 'business-category',
                'with_front'   => true,
                'hierarchical' => true,
            ),
        );
        
        register_taxonomy('mpr_business_category', array('mpr_business'), $args);
        
        // Register for users as well (for filtering)
        register_taxonomy_for_object_type('mpr_business_category', 'mpr_business');
    }
    
    /**
     * Register Review Tags taxonomy
     */
    private static function register_review_tags_taxonomy() {
        $labels = array(
            'name'                       => _x('Review Tags', 'Taxonomy General Name', 'mpr-reviews'),
            'singular_name'              => _x('Review Tag', 'Taxonomy Singular Name', 'mpr-reviews'),
            'menu_name'                  => __('Review Tags', 'mpr-reviews'),
            'all_items'                  => __('All Tags', 'mpr-reviews'),
            'new_item_name'              => __('New Tag Name', 'mpr-reviews'),
            'add_new_item'               => __('Add New Tag', 'mpr-reviews'),
            'edit_item'                  => __('Edit Tag', 'mpr-reviews'),
            'update_item'                => __('Update Tag', 'mpr-reviews'),
            'view_item'                  => __('View Tag', 'mpr-reviews'),
            'separate_items_with_commas' => __('Separate tags with commas', 'mpr-reviews'),
            'add_or_remove_items'        => __('Add or remove tags', 'mpr-reviews'),
            'choose_from_most_used'      => __('Choose from the most used', 'mpr-reviews'),
            'popular_items'              => __('Popular Tags', 'mpr-reviews'),
            'search_items'               => __('Search Tags', 'mpr-reviews'),
            'not_found'                  => __('Not Found', 'mpr-reviews'),
            'no_terms'                   => __('No tags', 'mpr-reviews'),
            'items_list'                 => __('Tags list', 'mpr-reviews'),
            'items_list_navigation'      => __('Tags list navigation', 'mpr-reviews'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column'  => true,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => true,
            'show_in_rest'      => true,
            'query_var'         => 'review_tag',
            'rewrite'           => array('slug' => 'review-tag'),
        );
        
        register_taxonomy('mpr_review_tag', array('mpr_review'), $args);
        register_taxonomy_for_object_type('mpr_review_tag', 'mpr_review');
    }
    
    /**
     * Register custom rewrite rules
     */
    private static function register_rewrite_rules() {
        // Add custom rewrite rules for business reviews
        add_rewrite_rule(
            'business/([^/]+)/reviews/?$',
            'index.php?post_type=mpr_business&name=$matches[1]&reviews=1',
            'top'
        );
    }
}