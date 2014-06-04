<?php
/*
Plugin Name: CC Group Meta
Description: Adds prime, featured, and categorization to BuddyPress groups.
Version: 0.1.0
Author: David Cavins
Licence: GPLv3
*/

define( 'CC_GROUP_META', '0.1.0' );

/**
 * Loads BP_Group_Extension class
 * Must load after our meta class, since we use use functions from CC_Group_Meta in the group extension
 *
 * @package CC Group Meta
 * @since 0.1.0
 */
function cc_group_meta_plugin_init() {

	require( dirname( __FILE__ ) . '/class-bp-group-extension.php' );
}
add_action( 'bp_include', 'cc_group_meta_plugin_init', 23 );

/**
 * Creates instance of CC_Group_Meta
 * This is where most of the running gears are.
 *
 * @package CC Group Meta
 * @since 0.1.0
 */

function cc_group_meta_class_init(){
	// Get the helper functions and template tags
	require( dirname( __FILE__ ) . '/ccgm-functions.php' );
	// Get the class fired up
	require( dirname( __FILE__ ) . '/class-cc-group-meta.php' );
	add_action( 'bp_include', array( 'CC_Group_Meta', 'get_instance' ), 21 );
}
add_action( 'bp_include', 'cc_group_meta_class_init' );