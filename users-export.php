<?php
/**
 * User export related REST API for Marfoof Connect
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * The callback function to get users and their metadata.
 */
function marfoof_connect_export_users( WP_REST_Request $request ) {
    $all_users = get_users();
    $users_data_with_meta = array();
    foreach ( $all_users as $user ) {
        $user_data = $user->to_array();
        $user_meta = get_user_meta( $user->ID );
        $combined_data = array_merge( $user_data,
            array( 'meta_data' => $user_meta ) ,
            array('roles' => $user_roles = $user->roles ),
            array('capabilities' => $user_roles = $user->allcaps )
        );
        $users_data_with_meta[] = $combined_data;
    }
    return new WP_REST_Response( $users_data_with_meta, 200 );
}
