<?php
/**
 * @package Ajax_Dropdowns
 * @version 0.9.9
 */
/*
Plugin Name: Ajax Dropdowns
Plugin URI: http://wordpress.org/plugins/ajax-dropdowns/
Description: Display a group of posts that can be switched using dropdowns.
Author: Brian Miyaji
Version: 0.9.9
Author URI: http://themeboy.com/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Plugin setup
 *
 * @since 0.9
*/
class Ajax_Dropdowns {

	/**
	 * Ajax Dropdowns Constructor.
	 * @access public
	 */
	public function __construct() {

		// Define constants
		$this->define_constants();

		// Hooks
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_head', array( $this, 'menu_highlight' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10 );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'wp_ajax_ajax_dropdown', array( $this, 'ajax_callback' ) );
		add_action( 'wp_ajax_nopriv_ajax_dropdown', array( $this, 'ajax_callback' ) );
		add_filter( 'widget_text', array( $this, 'widget_text' ) );
	}

	/**
	 * Define constants
	*/
	private function define_constants() {
		if ( !defined( 'AJAX_DROPDOWNS_VERSION' ) )
			define( 'AJAX_DROPDOWNS_VERSION', '0.9.8' );

		if ( !defined( 'AJAX_DROPDOWNS_URL' ) )
			define( 'AJAX_DROPDOWNS_URL', plugin_dir_url( __FILE__ ) );

		if ( !defined( 'AJAX_DROPDOWNS_DIR' ) )
			define( 'AJAX_DROPDOWNS_DIR', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Init plugin when WordPress Initialises.
	 */
	public function init() {
		// Set up localisation
		$this->load_plugin_textdomain();
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public static function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'ajax-dropdowns' );

		// Global + Frontend Locale
		load_plugin_textdomain( 'ajax-dropdowns', false, plugin_basename( dirname( __FILE__ ) . "/languages" ) );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links
	 * @return array
	 */
	public function action_links( $links ) {
		return array_merge( array(
			'<a href="' . admin_url( 'edit.php?post_type=ajax_dropdown' ) . '">' . __( 'Manage', 'ajax-dropdowns' ) . '</a>',
		), $links );
	}

	/**
	 * Add menu item
	 */
	public static function register_post_type() {
		register_post_type( 'ajax_dropdown',
			array(
				'labels' => array(
					'name' 					=> __( 'Dropdowns', 'ajax-dropdowns' ),
					'singular_name' 		=> __( 'Dropdown', 'ajax-dropdowns' ),
					'add_new_item' 			=> __( 'Add New Dropdown', 'ajax-dropdowns' ),
					'edit_item' 			=> __( 'Edit Dropdown', 'ajax-dropdowns' ),
					'new_item' 				=> __( 'New Dropdown', 'ajax-dropdowns' ),
					'view_item' 			=> __( 'View Dropdown', 'ajax-dropdowns' ),
					'search_items' 			=> __( 'Search Dropdowns', 'ajax-dropdowns' ),
					'not_found' 			=> __( 'No dropdowns found.', 'ajax-dropdowns' ),
					'not_found_in_trash' 	=> __( 'No dropdowns found in trash.', 'ajax-dropdowns' ),
				),
				'public' 				=> false,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'map_meta_cap' 			=> true,
				'publicly_queryable' 	=> false,
				'exclude_from_search' 	=> true,
				'hierarchical' 			=> false,
				'rewrite' 				=> array( 'slug' => 'group' ),
				'supports' 				=> array( 'title' ),
				'has_archive' 			=> false,
				'show_in_nav_menus' 	=> false,
				'show_in_admin_bar' 	=> false,
				'menu_icon' 			=> 'dashicons-randomize',
			)
		);
	}

	/**
	 * Add menu item
	 */
	public function register_shortcode() {
		add_shortcode( 'ajax_dropdown', array( $this, 'shortcode' ) );
	}

	/**
	 * Enqueue scripts
	 */
	public static function scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'ajaxd-styles', AJAX_DROPDOWNS_URL . '/assets/css/ajaxd.css', array(), AJAX_DROPDOWNS_VERSION );
	}

	/**
	 * Highlights the correct top level admin menu item for the post type add screen.
	 *
	 * @access public
	 * @return void
	 */
	public static function menu_highlight() {
		global $typenow, $submenu;
		if ( 'ajax_dropdown' == $typenow ):
			global $submenu_file;
			$submenu_file = 'edit.php?post_type=ajax_dropdown';
		endif;
	}

	/**
	 * Enqueue admin scripts
	 */
	public static function admin_scripts() {
		$screen = get_current_screen();

		if ( 'ajax_dropdown' == $screen->id ):
			wp_enqueue_style( 'jquery-chosen', AJAX_DROPDOWNS_URL . '/assets/css/chosen.css', array(), '1.1.0' );
			wp_enqueue_style( 'ajaxd-admin', AJAX_DROPDOWNS_URL . '/assets/css/admin.css', array(), AJAX_DROPDOWNS_VERSION );
	    	wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'chosen', AJAX_DROPDOWNS_URL . '/assets/js/chosen.jquery.min.js', array( 'jquery' ), '1.1.0', true );
	    	wp_enqueue_script( 'ajaxd-admin', AJAX_DROPDOWNS_URL . '/assets/js/ajaxd-admin.js', array( 'jquery', 'jquery-ui-sortable', 'chosen' ), AJAX_DROPDOWNS_VERSION, true );
		endif;
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box( 'ajaxd_postsdiv', __( 'Posts', 'ajax-dropdowns' ), array( $this, 'posts_meta_box' ), 'ajax_dropdown', 'advanced', 'high' );
		add_meta_box( 'ajaxd_methoddiv', __( 'Method', 'ajax-dropdowns' ), array( $this, 'method_meta_box' ), 'ajax_dropdown', 'side', 'default' );
		add_meta_box( 'ajaxd_shortcodediv', __( 'Shortcode', 'ajax-dropdowns' ), array( $this, 'shortcode_meta_box' ), 'ajax_dropdown', 'side', 'default' );
	}

	/**
	 * Posts meta box
	 */
	public static function posts_meta_box( $post, $args ) {
		$post_types = get_post_types( array( 'public' => true ) );
		$ajax_posts = get_post_meta( $post->ID, 'ajax_post' );
		?>
		<p>
			<select name="add_ajax_post" id="add_ajax_post" class="postform ajaxd-posts chosen-select<?php if ( is_rtl() ): ?> chosen-rtl<?php endif; ?>" data-placeholder="<?php _e( 'Add a post to this dropdown', 'ajax-dropdowns' ); ?>">
				<option value=""></option>
				<?php
				foreach ( $post_types as $post_type ):
					if ( 'attachment' == $post_type ) continue;
					$object = get_post_type_object( $post_type );
					$posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
					if ( $posts && is_array( $posts ) ):
						?>
						<optgroup label="<?php echo $object->labels->name; ?>">
							<?php
							printf( '<option value="%s" data-post-type="%s">%s</option>', 0, $object->labels->singular_name, sprintf( __( '&mdash; Add All %1$s (%2$s) &mdash;', 'ajax-dropdowns' ), $object->labels->name, sizeof( $posts ) ) );
							foreach ( $posts as $post ):
								printf( '<option value="%s" data-post-type="%s">%s</option>', $post->ID, $object->labels->singular_name, $post->post_title );
							endforeach;
							?>
						</optgroup>
						<?php
					endif;
				endforeach;
				?>
			</select>
		</p>
		<table class="widefat ajaxd-posts-table">
			<thead>
				<tr>
					<th style="width:1px;">&nbsp;</th>
					<th><?php _e( 'Title', 'ajax-dropdowns' ); ?></th>
					<th style="width:20%;"><?php _e( 'Post Type', 'ajax-dropdowns' ); ?></th>
					<th style="width:1px;">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<tr class="ajaxd-placeholder"<?php if ( sizeof( $ajax_posts ) ): ?> style="display:none;"<?php endif; ?>>
					<td colspan="4">
						<?php _e( 'No posts found.', 'ajax-dropdowns' ); ?>
						<?php _e( 'Use the menu above to add a post to this dropdown.', 'ajax-dropdowns' ); ?>
					</td>
				</tr>
				<?php if ( sizeof( $ajax_posts ) ): foreach ( $ajax_posts as $post_id ): ?>
					<tr>
						<td class="icon"><span class="dashicons dashicons-menu post-state-format"></span></td>
						<td><input type="hidden" name="ajax_post[]" value="<?php echo $post_id; ?>"><?php echo get_the_title( $post_id ); ?></td>
						<td><?php $post_type = get_post_type( $post_id ); $object = get_post_type_object( $post_type ); echo $object->labels->singular_name; ?></td>
						<td><a href="#" class="dashicons dashicons-no-alt ajaxd-delete"></a></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
		<p class="howto">
			<?php _e( 'Drag and drop to reorder posts.', 'ajax-dropdowns' ); ?>
		</p>
		<p>
			<a href="http://wordpress.org/support/view/plugin-reviews/ajax-dropdowns?rate=5#postform">
				<?php _e( 'Love Ajax Dropdowns? Help spread the word by rating us 5★ on WordPress.org', 'ajax-dropdowns' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Shortcode meta box
	 */
	public static function shortcode_meta_box( $post ) {
		?>
		<p class="howto">
			<?php _e( 'Copy this code and paste it into your post, page or text widget content.', 'ajax-dropdowns' ); ?>
		</p>
		<p><input type="text" value="[ajax_dropdown <?php echo $post->ID; ?>]" readonly="readonly" class="code"></p>
		<?php
	}

	/**
	 * Method meta box
	 */
	public static function method_meta_box( $post, $args ) {
		wp_nonce_field( 'ajaxd_save_data', 'ajaxd_meta_nonce' );
		$method = get_post_meta( $post->ID, 'ajaxd_method', true );
		$no_content = get_post_meta( $post->ID, 'ajaxd_no_content', true ) == 'yes' ? true : false;
		$method_options = array( 'ajax' => __( 'Ajax', 'ajax-dropdowns' ), 'inline' => __( 'Inline', 'ajax-dropdowns' ), 'parameter' => __( 'URL Parameter', 'ajax-dropdowns' ), 'redirect' => __( 'Redirect', 'ajax-dropdowns' ) );
		?>
		<p class="howto">
			<?php _e( 'Select the method to query posts.', 'ajax-dropdowns' ); ?>
		</p>
		<p>
			<select name="ajaxd_method" id="ajaxd_method" class="postform ajaxd-method widefat">
				<?php
				foreach ( $method_options as $key => $label ):
					printf( '<option value="%s" %s>%s</option>', $key, ( $key == $method ) ? 'selected' : '', $label );
				endforeach;
				?>
			</select>
		</p>
		<p class="ajaxd-no-content-option">
			<label>
				<input type="hidden" name="ajaxd_no_content" value="no">
				<input type="checkbox" name="ajaxd_no_content" class="ajaxd-no-content" value="yes" <?php checked( $no_content ); ?>>
				<?php _e( 'Dropdown only (no content)', 'ajax-dropdowns' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Check if we're saving, then trigger an action based on the post type
	 *
	 * @param  int $post_id
	 * @param  object $post
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( is_int( wp_is_post_revision( $post ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post ) ) ) return;
		if ( empty( $_POST['ajaxd_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ajaxd_meta_nonce'], 'ajaxd_save_data' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id  ) ) return;
		if ( 'ajax_dropdown' != $post->post_type ) return;

		// Save
		delete_post_meta( $post_id, 'ajax_post' );
		if ( isset( $_POST['ajax_post'] ) && is_array ( $_POST['ajax_post'] ) ):
			foreach ( $_POST['ajax_post'] as $value ):
				add_post_meta( $post_id, 'ajax_post', $value );
			endforeach;
		endif;
		update_post_meta( $post_id, 'ajaxd_layout', 'dropdown' );
		if ( isset( $_POST['ajaxd_method'] ) )
			update_post_meta( $post_id, 'ajaxd_method', $_POST['ajaxd_method'] );
		if ( isset( $_POST['ajaxd_method'] ) && 'redirect' == $_POST['ajaxd_method'] && isset( $_POST['ajaxd_no_content'] ) )
			update_post_meta( $post_id, 'ajaxd_no_content', $_POST['ajaxd_no_content'] );
		else
			update_post_meta( $post_id, 'ajaxd_no_content', 'no' );
	}

	/**
	 * Remove link from post updated messages
	 */
	public static function post_updated_messages( $messages ) {
		global $typenow, $post;

		if ( 'ajax_dropdown' == $typenow ):
			for ( $i = 0; $i <= 10; $i++ ):
				$messages['post'][ $i ] = '<strong>' . __( 'Settings saved.', 'ajax-dropdowns' ) . '</strong>';
			endfor;
		endif;

		return $messages;
	}

	/**
	 * Shortcode content
	 */
	public static function shortcode( $atts ) {
		// Get shortcode attributes
		if ( ! is_array( $atts ) || ! sizeof( $atts ) ) return;

		// Get group ID
		$id = array_shift( $atts );

		// Get group post ids
		$include = get_post_meta( $id, 'ajax_post' );
		if ( ! $include || ! is_array( $include ) || ! sizeof( $include ) ) return;

		// Get current group post
		if ( isset( $_REQUEST['ajax_post'] ) && in_array( $_REQUEST['ajax_post'], $include ) )
			$current = $_REQUEST['ajax_post'];
		else
			$current = reset( $include );

		// Get method and no content option
		$method = get_post_meta( $id, 'ajaxd_method', true );
		$no_content = get_post_meta( $id, 'ajaxd_no_content', true );

		/**
		 * Select options
		 */
		$select = '<select class="ajaxd-select" name="ajax_post" id="ajaxd-select-' . $id . '">';
		foreach ( $include as $post_id ): if ( get_post_status( $post_id ) == 'publish' ||  current_user_can( 'edit_post', $post_id ) ):
			$select .= '<option value="' . $post_id . '" data-permalink="' . get_permalink( $post_id ) . '" ' . selected( $current, $post_id, false ) . '>' . get_the_title( $post_id ) . '</option>';
		endif; endforeach;
		$select .= '</select>';
		
		/**
		 * Select script (defaults to ajax)
		 */
		if ( 'inline' == $method ):
			$script = '$("#ajaxd-select-' . $id . '").change(function(){$("#ajaxd-posts-' . $id . ' #ajaxd-post-"+$(this).val()).show().siblings().hide();});';
		elseif ( 'parameter' == $method ):
			$script = '$("#ajaxd-select-' . $id . '").change(function(){window.location="' . add_query_arg( 'ajax_post', '', remove_query_arg( 'ajax_post', get_permalink() ) ) . '="+$(this).val();});';
		elseif ( 'redirect' == $method ):
			$script = '$("#ajaxd-select-' . $id . '").change(function(){window.location=$(this).find("option:selected").data("permalink");});';
		else:
			$script = '$("#ajaxd-select-' . $id . '").change(function(){$.post("' . admin_url('admin-ajax.php') . '",{"action":"ajax_dropdown","post_id":$(this).val()},function(response){if(response!=0){$("#ajaxd-posts-' . $id . '").html(response)};});}).trigger("change");';
		endif;

		// Skip if no content
		if ( 'yes' == $no_content ):
			$content = '';
		else:
			// Get global $wp_query and hold onto original queried object
			global $wp_query;
			$queried_object = $wp_query->queried_object;

			// Limit posts to current if not inline
			if ( 'inline' != $method ):
				$include = array( $current );
			endif;

			// Loop through posts
			$content = '<div class="ajaxd-posts" id="ajaxd-posts-' . $id . '">';
			foreach ( $include as $post_id ):
				$query = new WP_Query( array( 'p' => $post_id, 'post_type' => 'any' ) );
				if ( ! $query->have_posts() ) continue;
				while ( $query->have_posts() ): $query->the_post();
					global $post;
					$wp_query->queried_object = $post;
					$content .= '<div class="ajaxd-post" id="ajaxd-post-' . $post_id . '"' . ( $current == $post_id ? '' : ' style="display:none;"' ) . '>' . apply_filters( 'the_content', get_the_content() ) . '</div>';
				endwhile;
			endforeach;
			wp_reset_postdata();
			$content .= '</div><!-- .ajaxd-posts -->';

			// Restore original queried object
			$wp_query->queried_object = $queried_object;
		endif;

		// Return output
		return $select . $content . '<script type="text/javascript">(function($) {' . $script . '})(jQuery);</script>';
	}

	/**
	 * Ajax Callback
	 */
	public static function ajax_callback() {

		// Return if no id is given
		if ( ! isset( $_POST['post_id'] ) ) return false;

		// Query post
		$post_id = intval( $_POST['post_id'] );
		$query = new WP_Query( array( 'p' => $post_id, 'post_type' => 'any' ) );

		// Return if post does not exist
		if ( ! $query->have_posts() ) return false;

		// Post exists. Output it.
		while ( $query->have_posts() ): $query->the_post();

			global $post, $wp_query;

			// Set queried object
			$wp_query->queried_object = $post;

			// Tell WP that we are in a singular post loop
			$wp_query->is_singular = $wp_query->in_the_loop = true;

			// Fix thumbnail error
			$wp_query->posts = array();

			echo '<div class="ajaxd-post" id="ajaxd-post-' . $post_id . '">' . apply_filters( 'the_content', get_the_content() ) . '</div>';

		endwhile;

		die();
	}

	/**
	 * Do shortcode in widgets
	 */
	public static function widget_text( $content ) {
		if ( ! preg_match( '/\[[\r\n\t ]*(ajax_dropdown)?[\r\n\t ].*?\]/', $content ) )
			return $content;

		$content = do_shortcode( $content );

		return $content;
	}

}

new Ajax_Dropdowns();
