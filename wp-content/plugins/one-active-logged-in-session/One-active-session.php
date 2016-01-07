<?php
/*
Plugin Name: One Active Session
Plugin URI: http://development.srapsware.com
Description: This Plugin uses function like WordPress 4.1, that have a button on the user profile screen which clears all other sessions, and on the user editing screen which clears all sessions. This plug-in can limit simultaneous login. This plugin use for every user automatically. 
Version: 1.0.0
Author: Shiv Singh (SrapsWare)
Author URI: http://shiv.srapsware.com
License: GPLv2 or later
 */
function wp_destroy_all_other_sessions() {
	$token = wp_get_session_token();
	if ( $token ) {
		$manager = WP_Session_Tokens::get_instance( get_current_user_id() );
		$manager->destroy_others( $token );
	}
}
add_action('init', 'wp_destroy_all_other_sessions');

?>