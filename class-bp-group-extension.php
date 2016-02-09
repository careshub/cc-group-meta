<?php
/**
 * CC Group Meta
 *
 * @package   CC Group Meta
 * @author    David Cavins
 * @license   GPL-2.0+
 * @copyright 2014 Community Commons
 */

/**
 * The class_exists() check is recommended, to prevent problems during upgrade
 * or when the Groups component is disabled
 */
if ( class_exists( 'BP_Group_Extension' ) ) :

// We're going to use BP_Group_Extension to add an admin metabox on the wp-admin>group edit screen
class CC_Custom_Meta_Group_Extension extends BP_Group_Extension {
    /**
     * Your __construct() method will contain configuration options for
     * your extension, and will pass them to parent::init()
     */
    function __construct() {
        $args = array(
            'slug' => 'cc-group-meta',
            'name' => 'CC Group Meta',
            'enable_nav_item' => false, // We don't need a display tab
            'screens' => array(
                'edit' => array(
                	'enabled' => false, // We do not need an edit tab, we're putting these options on the settings pane
                ),
                'create' => array(
                    'enabled' => false, // We do not need a create tab, we're putting these options on the settings pane
                ),
                'admin' => array(
                	'metabox_context' => 'side',
                    // 'name' => 'Community Commons Group Meta',
                ),
            ),
        );
        parent::init( $args );
    }

    /**
     * admin_screen() is the method for displaying the content
     * of the Dashboard admin panels
     */
    public function admin_screen( $group_id = null ) {
    	// Use our vanilla meta form markup
        $cc_group_meta = new CC_Group_Meta();
    	return $cc_group_meta->meta_form_markup( $group_id );
    }

    /**
     * settings_screen_save() contains the logic for saving
     * settings from the Dashboard admin panels
     */
    public function admin_screen_save( $group_id = null ) {
       	// Use our all-purpose saving function
    	return CC_Group_Meta::meta_form_save( $group_id );
    }

}
bp_register_group_extension( 'CC_Custom_Meta_Group_Extension' );

endif; // if ( class_exists( 'BP_Group_Extension' ) )
