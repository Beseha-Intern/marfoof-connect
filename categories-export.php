<?php
/**
 * Categories export related REST API for Marfoof Connect
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all product categories with complete details
 */
function marfoof_connect_export_categories(WP_REST_Request $request) {
    $categories_data = array();
    
    // Get all product categories
    $args = array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false, // Get all categories even empty ones
        'orderby' => 'name',
        'order' => 'ASC'
    );
    
    $categories = get_terms($args);
    
    foreach ($categories as $category) {
        $category_data = array(
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'count' => $category->count,
            'parent_id' => $category->parent,
            'term_group' => $category->term_group,
            'term_taxonomy_id' => $category->term_taxonomy_id
        );
        
        // Get category image
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        if ($thumbnail_id) {
            $category_data['image'] = wp_get_attachment_url($thumbnail_id);
        } else {
            $category_data['image'] = null;
        }
        
        // Get category display type
        $display_type = get_term_meta($category->term_id, 'display_type', true);
        $category_data['display_type'] = $display_type ? $display_type : 'default';
        
        // Get custom meta data
        $category_meta = get_term_meta($category->term_id);
        $category_data['meta_data'] = $category_meta;
        
        $categories_data[] = $category_data;
    }
    
    return new WP_REST_Response($categories_data, 200);
}

/**
 * Register categories export REST API endpoint
 */
function marfoof_connect_register_categories_api_route() {
    register_rest_route('marfoof-connect/v1', '/export/categories', array(
        'methods' => 'GET',
        'callback' => 'marfoof_connect_export_categories',
        'permission_callback' => 'marfoof_connect_check_api_key',
    ));
}
add_action('rest_api_init', 'marfoof_connect_register_categories_api_route');