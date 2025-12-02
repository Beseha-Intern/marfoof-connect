<?php
/**
 * Products export related REST API for Marfoof Connect
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all products with complete metadata and details (without categories full data)
 */
function marfoof_connect_export_products(WP_REST_Request $request) {
    // Get all products
    $args = array(
        'limit' => -1, // Get all products
        'status' => array('publish', 'draft', 'pending'), // Multiple statuses
        'return' => 'ids'
    );
    
    $product_ids = wc_get_products($args);
    $products_data = array();
    
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            continue;
        }
        
        // Basic product data
        $product_data = array(
            'ID' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'manage_stock' => $product->get_manage_stock(),
            'backorders' => $product->get_backorders(),
            'weight' => $product->get_weight(),
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height(),
            'featured' => $product->get_featured(),
            'virtual' => $product->get_virtual(),
            'downloadable' => $product->get_downloadable(),
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'date_created' => $product->get_date_created()->format('c'),
            'date_modified' => $product->get_date_modified()->format('c')
        );
        
        // Category IDs only (not full category data)
        $category_ids = array();
        $category_terms = wp_get_post_terms($product->get_id(), 'product_cat');
        foreach ($category_terms as $category) {
            $category_ids[] = $category->term_id;
        }
        $product_data['category_ids'] = $category_ids;
        
        // Tag IDs only
        $tag_ids = array();
        $tag_terms = wp_get_post_terms($product->get_id(), 'product_tag');
        foreach ($tag_terms as $tag) {
            $tag_ids[] = $tag->term_id;
        }
        $product_data['tag_ids'] = $tag_ids;
        
        // Images
        $images = array();
        $image_id = $product->get_image_id();
        if ($image_id) {
            $images['main'] = wp_get_attachment_url($image_id);
        }
        
        $gallery_ids = $product->get_gallery_image_ids();
        $images['gallery'] = array();
        foreach ($gallery_ids as $gallery_id) {
            $images['gallery'][] = wp_get_attachment_url($gallery_id);
        }
        $product_data['images'] = $images;
        
        // Attributes
        $attributes = array();
        $product_attributes = $product->get_attributes();
        foreach ($product_attributes as $attribute_name => $attribute) {
            $attributes[$attribute_name] = array(
                'name' => $attribute->get_name(),
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation()
            );
        }
        $product_data['attributes'] = $attributes;
        
        // Variations (for variable products)
        if ($product->is_type('variable')) {
            $variations = array();
            $variation_ids = $product->get_children();
            
            foreach ($variation_ids as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variations[] = array(
                        'id' => $variation->get_id(),
                        'sku' => $variation->get_sku(),
                        'price' => $variation->get_price(),
                        'attributes' => $variation->get_attributes(),
                        'stock_quantity' => $variation->get_stock_quantity()
                    );
                }
            }
            $product_data['variations'] = $variations;
        }
        
        // Meta data
        $meta_data = get_post_meta($product->get_id());
        $product_data['meta_data'] = $meta_data;
        
        $products_data[] = $product_data;
    }
    
    return new WP_REST_Response($products_data, 200);
}

/**
 * Register products export REST API endpoint
 */
function marfoof_connect_register_products_api_route() {
    register_rest_route('marfoof-connect/v1', '/export/products', array(
        'methods' => 'GET',
        'callback' => 'marfoof_connect_export_products',
        'permission_callback' => 'marfoof_connect_check_api_key',
    ));
}
add_action('rest_api_init', 'marfoof_connect_register_products_api_route');