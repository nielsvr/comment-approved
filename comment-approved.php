<?php
/*
Plugin Name: Comment Approved
Plugin URI: http://media-enzo.nl
Description: Notify a user when their comment is approved

Version: 1.0
Requires at least: 3.0

Author: Media-Enzo
Author URI: http://media-enzo.nl

Text Domain: ca
Domain Path: /languages/

*/

require_once 'classes/main.php';

function ca_init() {

	// Initialize
	global $wp_comment_approved;

	$wp_comment_approved = new Comment_Approved();
	
	register_activation_hook( __FILE__, array( 'Comment_Approved', 'install' ) );
	
}

add_action( 'ca_init', 'ca_init', 1 );

// Start it up
do_action( 'ca_init' );
