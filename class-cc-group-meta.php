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
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package CC Group Meta
 * @author  David Cavins
 */
class CC_Group_Meta {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1.0
	 *
	 * @var     string
	 */
	const VERSION = '0.1.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'cc-group-meta';

	/**
	 * Instance of this class.
	 *
	 * @since    0.1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Comma-separated string of category ids to exclude.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 * 1 is "uncategorized", 57528 is "guest voice"
	 */
	protected $exclude_cats = '1,57528';

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     0.1.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );


		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		// add_action( '@TODO', array( $this, 'action_method_name' ) );
		// add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		// Add a meta box to the group's "admin>settings" tab.
   		// We're also using BP_Group_Extension's admin_screen method to add this meta box to the WP-admin group edit
        add_filter( 'groups_custom_group_fields_editable', array( $this, 'meta_form_markup' ) );
        // Catch the saving of the meta form, fired when create>settings pane is saved or admin>settings is saved
        add_action( 'groups_group_details_edited', array( $this, 'meta_form_save') );
		add_action( 'groups_created_group', array( $this, 'meta_form_save' ) );

		// Add the filter dropdown to the Groups Directory
		add_action( 'bp_groups_directory_group_types', array( $this, 'output_channel_select' ) );

		// Add the "featured" option to the different "order by" select boxes
		// The groups directory (or tree, if Group Hierarchy is running)
		add_action( 'bp_groups_directory_order_options', array( $this, 'featured_option' ) );
		// and for the groups tab of the user's profile
		add_action( 'bp_member_group_order_options', array( $this, 'featured_option' ) );

		// Display featured groups at the top of the groups loop
		add_action( 'bp_before_groups_loop', array( $this, 'sticky_featured_groups') );

		// Catch the AJAX filter request and modify the query string
		add_filter( 'bp_ajax_querystring', array( $this, 'groups_querystring_filter' ), 37, 2 );
		// Handle JSON request for returning groups to which the user can upload data.
		add_action( 'wp_ajax_cc-json-groups-user-can-upload-data-to', array( $this, 'json_groups_user_can_upload_data_to' ) );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    0.1.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.1.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    0.1.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    0.1.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    0.1.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    0.1.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.1.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {
		// if ( function_exists( 'bp_is_groups_component' ) && ccgn_is_component() )
		// 	wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {
		// only fetch the js file if on the groups directory
		// bp_is_groups_directory() is available at 2.0.0.
		if ( bp_is_groups_component() && ! bp_current_action() && ! bp_current_item() )
			wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'channel-select.js', __FILE__ ), array( 'jquery' ), self::VERSION, TRUE );
	}

	/**
	 * We use a subset of the complete list of categories for groups
	 * This function excludes the unnecessary options and refers to the
	 * variable $exclude_cats
	 *
	 * @since    0.1.0
	 */
	public function get_group_categories() {
        $args = array(
			'hide_empty'	=> 0,
			'exclude'		=> $this->exclude_cats,
			);
		return get_categories( $args );
	}

	/**
	 *  Renders extra fields on form when creating a group and when editing group details
	 * 	Used by CC_Custom_Meta_Group_Extension::admin_screen()
  	 *  @param  	int $group_id
	 *  @return 	string html markup
	 *  @since    	0.1.0
	 */
	public function meta_form_markup( $group_id = 0 ) {
		if ( ! current_user_can( 'delete_others_pages' ) )
			return;

		$group_id = $group_id ? $group_id : bp_get_current_group_id();

		if ( ! is_admin() ) : ?>
			<div class="checkbox content-row">
				<hr />
				<h4>Commons-specific settings</h4>
		<?php endif; ?>

			<p><label for="cc_featured_group"><input type="checkbox" id="cc_featured_group" name="cc_featured_group" <?php checked( groups_get_groupmeta( $group_id, 'cc_group_is_featured' ), 1 ); ?> /> Highlight on the groups directory.</label></p>

		<fieldset>
			<legend>Mapping capabilities</legend>

			<p><label for="members_can_upload_data"><input type="checkbox" id="members_can_upload_data" name="members_can_upload_data" <?php checked( groups_get_groupmeta( $group_id, 'members_can_upload_data' ), 1 ); ?> /> Admins and mods can upload data; members can use advanced mapping tools.</label></p>

			<p><label for="members_can_edit_data">Which members of this hub can create and edit data using the YouAdd tool?</label>
				<?php
				$edit_data_level = groups_get_groupmeta( $group_id, 'members_can_edit_data' );
				?>
			<select id="members_can_edit_data" name="members_can_edit_data" >
				<option value="" <?php selected( $edit_data_level, '' ); ?>>No one</option>
				<option value="admins" <?php selected( $edit_data_level, 'admins' ); ?>>Admins</option>
				<option value="mods" <?php selected( $edit_data_level, 'mods' ); ?>>Moderators and Admins</option>
				<option value="members" <?php selected( $edit_data_level, 'members' ); ?>>Members</option>
			</select></p>
		</fieldset>

			<?php
			// Expose a hook for other plugins that we may write.
			// This is used by our activity aggregation plugin
			do_action( 'cc_group_meta_details_form_before_channels', $group_id );
			?>

			<h4>Associated Channels</h4>
			<?php
            if ( $selected_cats = groups_get_groupmeta( $group_id, 'cc_group_category', false ) ) {
            	$selected_cats = array_map( 'intval', $selected_cats );
            }
            $categories = self::get_group_categories();
            if ( ! empty( $categories ) ) :
            ?>
	            <ul class="no-bullets horizontal">
					<?php
					    foreach ( $categories as $category ) {
			            	$selected_cat = in_array( $category->term_id, $selected_cats) ? true : false;
						?>
						<li id="category-<?php echo $category->term_id; ?>"><label class="selectit"><input value="<?php echo $category->term_id; ?>" type="checkbox" name="post_category[]" id="in-category-<?php echo $category->term_id; ?>" <?php checked( $selected_cat ); ?>> <?php echo $category->name; ?></label></li>
						<?php
					}
					?>
	            </ul>
            <?php
            endif; //if ( ! empty( $categories ) )
		if ( ! is_admin() ) : ?>
			<hr />
			</div>
		<?php endif;
	}

	/**
	 *  Saves the input from our extra meta fields
	 * 	Used by CC_Custom_Meta_Group_Extension::admin_screen_save()
	 *  @param  	int $group_id
	 *  @return 	void
	 *  @since    	0.1.0
	 */
	public function meta_form_save( $group_id = 0 ) {
		// Don't update these settings unless the user can update them.
		if ( ! current_user_can( 'delete_others_pages' ) ) {
			return;
		}

		$group_id = $group_id ? $group_id : bp_get_current_group_id();

		$meta = array(
			// Checkboxes
			'cc_group_is_featured' => isset( $_POST['cc_featured_group'] ),
			'members_can_upload_data' => isset( $_POST['members_can_upload_data'] ), // formerly "group_is_prime_group"
			// Selects
			'members_can_edit_data' => isset( $_POST['members_can_edit_data'] ) ? $_POST['members_can_edit_data'] : '',
		);

		foreach ( $meta as $meta_key => $new_meta_value ) {

			/* Get the meta value of the custom field key. */
			$meta_value = groups_get_groupmeta( $group_id, $meta_key, true );

			/* If there is no new meta value but an old value exists, delete it. */
			if ( '' == $new_meta_value && $meta_value )
				groups_delete_groupmeta( $group_id, $meta_key, $meta_value );

			/* If a new meta value was added and there was no previous value, add it. */
			elseif ( $new_meta_value && '' == $meta_value )
				groups_add_groupmeta( $group_id, $meta_key, $new_meta_value, true );

			/* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				groups_update_groupmeta( $group_id, $meta_key, $new_meta_value );
		}

		// I don't think we'll want to store the category relationship as a serialized array, but as multiple meta items.
		// Not as efficient storage-wise, but it'll greatly simplify finding groups by category.
		$categories = (array) $_POST['post_category']; // This is OK if empty, an empty array works for us.
		// Fetch existing values
		$old_cats = groups_get_groupmeta( $group_id, 'cc_group_category', false );
		// Categories in the db but not in the POST should be removed
		$cats_to_delete = array_diff( $old_cats, $categories );
		// Categories not in the db but in the POST should be added
		$cats_to_add = array_diff( $categories, $old_cats );

		foreach ($cats_to_add as $add_id) {
			groups_add_groupmeta( $group_id, 'cc_group_category', $add_id, false );
		}

		foreach ($cats_to_delete as $delete_id) {
			groups_delete_groupmeta( $group_id, 'cc_group_category', $delete_id );
		}

		// Expose a hook for other plugins that we may write.
		// This is used by our activity aggregation plugin
		do_action( 'cc_group_meta_details_form_save', $group_id );

	}

	/**
	*  Adds channel filter dropdown list markup to the groups directory list
	*  @uses   	wp_dropdown_categories()
	*  @return 	string html markup
	*  @since 	0.1.0
	*/
	public function output_channel_select() {
		$args = array(
			'show_option_all' 	=> 'All Channels',
			'id' 				=> 'groups-filter-channel',
			'name' 				=> 'groups-filter-channel',
			'exclude'			=> $this->exclude_cats,
			'orderby'           => 'NAME',
			'hide_empty'        => false,
			);
		// The class "no-ajax" is used so that BP will ignore the input.
		?>
		<li class="no-ajax" id="groups-filter-by-channel">
			<label for="groups-filter-channel">Channel:</label>
			<?php wp_dropdown_categories( $args ); ?>
		</li>
		<?php
	}

	/**
	*  Adds "featured" option to the "Order by" dropdown list filter
	*  @return 	string html markup
	*  @since 	0.1.0
	*/
	public function featured_option() {
		?>
		<option value="featured"><?php _e( 'Featured' ); ?></option>
		<?php
	}

	/**
	*  Adds "featured" groups (maximum of 3) to the top of the groups list
	*  @return 	string html markup
	*  @since 	0.1.0
	*/
	public function sticky_featured_groups() {
		// If the user is interacting with the directory, don't show this at the top--that's annoying.
		// If the request is AJAX-based, don't show the featured groups.
		if ( ! ( defined('DOING_AJAX') && DOING_AJAX ) ) {

			$args = array(
				'per_page' => 2,
				'meta_query' => array(
					array(
		           		/* this is the meta_key you want to filter on */
		                'key'     => 'cc_group_is_featured',
		                /* You need to get all values that are = to the id selected */
		                'value'   => 1,
		                'type'    => 'numeric',
		                'compare' => '='
		                ),
		            ),
				);
			if ( bp_has_groups( $args ) ) :
				$shown_hubs = array();
				?>
		        <h5>Featured Hubs</h5>
				<ul class="item-list" id="groups-list-featured">
				<?php
				while ( bp_groups() ) : bp_the_group();
				?>
					<li id="featured-group-<?php
					echo bp_get_group_slug() . ' ' . bp_get_group_status();
					?>">
						<div class="item-avatar">
							<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar(); ?></a>
						</div>
						<div class="item">
							<div class="item-title"><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></div>
							<div class="item-desc"><?php bp_group_description_excerpt(); ?></div>
						</div>
						<div class="clear"></div>
					</li>
				<?php
				$shown_hubs[] = bp_get_group_id();
				endwhile;
				do_action( 'cc_add_to_featured_hubs', $shown_hubs );
				?>
				</ul>
			<?php endif; // bp_has_groups
		} // if ( ! ( defined('DOING_AJAX') && DOING_AJAX ) )
	}


	/**
	 * Builds a Group Meta Query to retrieve the favorited activities. Group meta_queries in bp_has_groups were introduced in 1.8
	 *
	 * @param  string 	$query_string the front end arguments for the Activity loop
	 * @param  string 	$object       the Component object
	 * @uses   wp_parse_args()
	 * @return array()|string $query_string 	new arguments or same if not needed
	 */
	public function groups_querystring_filter( $query_string = '', $object = '' ) {

		if ( ! in_array( $object, array('tree','groups') ) )
           	return $query_string;

        // You can easily manipulate the query string
        // by transforming it into an array and merging
        // arguments with these default ones
	    $defaults = array(
	        'type'            => 'active',
	        'action'          => 'active',
	        'scope'           => 'all',
	        'page'            => 1,
	        'search_terms'    => '',
	        'exclude'         => false,
	    );

        $args = wp_parse_args( $query_string, $defaults );

        // The channel filter data is stored as a cookie and passed along with the post request
        if ( ! empty( $_POST['cookie'] ) ) {
			$post_cookie = wp_parse_args( str_replace( '; ', '&', urldecode( $_POST['cookie'] ) ) );
		} else {
			$post_cookie = &$_COOKIE;
		}

        $channel_filter = '';
		if ( isset( $post_cookie['bp-groups-channel'] ) )
			$channel_filter = $post_cookie['bp-groups-channel'];

        // Add the channel filter meta query if needed
        if ( $channel_filter ) {

            $args['meta_query'][] = array(
           		/* this is the meta_key you want to filter on */
                'key'     => 'cc_group_category',
                /* You need to get all values that are = to the id selected */
                'value'   => $channel_filter,
                'type'    => 'numeric',
                'compare' => '='
            );

        }

        // Add the featured group filter
       	if ( $args['type'] == 'featured' ) {
            $args['meta_query'][] = array(
           		/* this is the meta_key you want to filter on */
                'key'     => 'cc_group_is_featured',
                /* You need to get all values that are = to the id selected */
                'value'   => 1,
                'type'    => 'numeric',
                'compare' => '='
            );
       	}

       	// Add the operator if both filters are enabled
       	// See http://codex.wordpress.org/Class_Reference/WP_User_Query#Custom_Field_Parameters for structure
       	if ( $channel_filter && $args['type'] == 'featured' ) {
       		$args['meta_query']['relation'] = 'AND';
       	}

        $query_string = empty( $args ) ? $query_string : $args;

        return apply_filters( 'bp_groups_channel_querystring_filter', $query_string, $object );
	function json_groups_user_can_upload_data_to() {
		$meta_key = 'members_can_upload_data';
		$user_id = bp_loggedin_user_id();

		$groups = groups_get_groups( array(
			'per_page' => false,
			'populate_extras' => false,
			'update_meta_cache' => false,
			'user_id' => bp_loggedin_user_id(),
			'meta_query' => array(
					array(
						'key'     => $meta_key,
						'compare' => 'EXISTS',
					),
				),
			)
		);
		$retval = array();
		foreach ( $groups['groups'] as $key => $group ) {
			if ( groups_is_user_admin( $user_id, $group->id ) || groups_is_user_mod( $user_id, $group->id ) ) {
				$retval[] = array(
					'id'	=> $group->id,
					'name'	=> $group->name,
					'link' 	=> bp_get_group_permalink( $group ),
				);
			}
		}

		// Send response
		header("content-type: text/javascript; charset=utf-8");
		header("Access-Control-Allow-Origin: *");
		echo htmlspecialchars($_GET['callback']) . '(' . json_encode( $retval ) . ')';

		exit;
	}

}
