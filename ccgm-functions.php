<?php
/**
 * CC Group Meta
 *
 * @package   CC Group Meta
 * @author    David Cavins
 * @license   GPL-2.0+
 * @copyright 2014 Community Commons
 */

function cc_get_groupmeta( $meta_key = '', $group_id = 0 ) {
	// Group ID may or may not be provided
	$group_id = $group_id ? $group_id : bp_get_current_group_id();
	
	return groups_get_groupmeta( $group_id, $meta_key) ;
}