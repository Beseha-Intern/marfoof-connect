<?php
// File: categories-export.php

/**
 * Export categories with pagination/chunking support
 */
function marfoof_connect_export_categories(WP_REST_Request $request) {
    $params = $request->get_params();
    $action = isset($params['action']) ? sanitize_text_field($params['action']) : 'preview';
    
    switch ($action) {
        case 'preview':
            return marfoof_connect_preview_categories();
            
        case 'start_migration':
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            $force_restart = isset($params['force_restart']) ? (bool)$params['force_restart'] : false;
            return marfoof_connect_start_migration('categories', $batch_size, $force_restart);
            
        case 'migrate_batch':
            $batch_number = isset($params['batch']) ? (int)$params['batch'] : 1;
            $batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : 500;
            return marfoof_connect_migrate_batch('categories', $batch_number, $batch_size);
            
        case 'migration_status':
            return marfoof_connect_get_migration_status('categories');
            
        case 'reset_migration':
            return marfoof_connect_reset_migration('categories');
            
        case 'get_all':
            return marfoof_connect_get_all_categories();
            
        default:
            return new WP_Error('invalid_action', 'Invalid action specified', array('status' => 400));
    }
}

/**
 * Preview categories before migration
 */
function marfoof_connect_preview_categories() {
    $stats = marfoof_connect_get_migration_stats('categories');
    return rest_ensure_response($stats);
}

/**
 * Get all categories (original function)
 */
function marfoof_connect_get_all_categories() {
    global $wpdb;
    
    $categories = $wpdb->get_results(
        "SELECT t.*, tt.description, tt.parent 
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = 'product_cat'
        ORDER BY t.term_id ASC"
    );
    
    $categories_data = array();
    
    foreach ($categories as $category) {
        $category_data = marfoof_connect_prepare_category_data($category);
        if ($category_data) {
            $categories_data[] = $category_data;
        }
    }
    
    return rest_ensure_response($categories_data);
}