<?php
/**
 * Class Managing The 'enco_thread' Custom Post Type.
 *
 * @package    Enco
 * @author     Lazhar Ichir
 */

/**
 * Thread - Custom Post Type.
 *
 * @since  1.0.0
 * @access public
 */
class Enco_Thread_Cpt {

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function __construct() {

		// Only load this if in the admin panel.
		if( is_admin() ) {
			$this->custom_post_type();

			// Actions for columns and tables
			add_action( 'manage_enco_thread_posts_custom_column', array( $this, 'admin_columns_thread_display' ), 10, 2 );
			add_filter( 'manage_enco_thread_posts_columns', array( $this, 'admin_columns_thread' ) );
			add_filter( 'post_row_actions', array( $this, 'remove_row_actions'), 10, 2 );

			add_action( 'add_meta_boxes', array( $this, 'meta_box_add' ) );
			add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 3 );
		}
	}

	/**
	 * Populates each enco_thread post type admin column.
	 * 
	 * @param  string 	$column 	The name of the current column.
	 * @param  int 		$post_id 	The ID of the post in question.
	 * @return void
	 */
	public function admin_columns_thread_display( $column, $post_id ) {
		
		switch ( $column ) {

			case 'thread_subject':
				
				echo '<p>' . get_post_meta( $post_id, 'thread_subject', true ) . '</p>';

				break;

			case 'thread_post' :

				$thread_post_id = get_post_meta( $post_id, 'thread_post_id', true );
				echo '<a href="' . get_permalink($thread_post_id) . '">' . get_the_title($thread_post_id) . '</a>';

				break;

			case 'thread_comments' :

				echo enco_total_thread_comments( $post_id );

				break;
		}
	}

	/**
	 * Alter the enco_thread post type admin columns.
	 * 
	 * @param  array $columns Columns before filtering.
	 * @return array $columns Columns after filtering.
	 */
	public function admin_columns_thread( $columns ) {

		unset($columns['date']);
		unset($columns['title']);

		$columns['thread_subject']  = 'Thread';
		$columns['thread_post'] 	= 'Post';
		$columns['thread_comments'] = 'Comments';

		return $columns;
	}

	/**
	 * Filter the actions links.
	 * 
	 * @param  array 	$actions 	List of actions currently displayed
	 * @return array 	$action 	The filtered actions
	 */
	public function remove_row_actions( $actions, $post ) {

	  	global $current_screen;
		
		if( $current_screen->post_type != 'enco_thread' ) 
			return $actions;

		unset( $actions['view'] );
		unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	/**
	 * Registers the enco_thread custom post type.
	 * 
	 * @return void
	 */
	public function custom_post_type() {

		$labels = array(
			'name'                  => _x( 'Threads', 'Post Type General Name', 'enco' ),
			'singular_name'         => _x( 'Thread', 'Post Type Singular Name', 'enco' ),
			'menu_name'             => __( 'Threads', 'enco' ),
			'name_admin_bar'        => __( 'Thread', 'enco' ),
			'archives'              => __( 'Thread Archives', 'enco' ),
			'parent_item_colon'     => __( 'Parent Thread:', 'enco' ),
			'all_items'             => __( 'All Threads', 'enco' ),
			'add_new_item'          => __( 'Add New Thread', 'enco' ),
			'add_new'               => __( 'Add New', 'enco' ),
			'new_item'              => __( 'New Thread', 'enco' ),
			'edit_item'             => __( 'Edit Thread', 'enco' ),
			'update_item'           => __( 'Update Thread', 'enco' ),
			'view_item'             => __( 'View Thread', 'enco' ),
			'search_items'          => __( 'Search Thread', 'enco' ),
			'not_found'             => __( 'Not found', 'enco' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'enco' ),
			'featured_image'        => __( 'Featured Image', 'enco' ),
			'set_featured_image'    => __( 'Set featured image', 'enco' ),
			'remove_featured_image' => __( 'Remove featured image', 'enco' ),
			'use_featured_image'    => __( 'Use as featured image', 'enco' ),
			'insert_into_item'      => __( 'Insert into thread', 'enco' ),
			'uploaded_to_this_item' => __( 'Uploaded to this thread', 'enco' ),
			'items_list'            => __( 'Threads list', 'enco' ),
			'items_list_navigation' => __( 'Threads list navigation', 'enco' ),
			'filter_items_list'     => __( 'Filter threads list', 'enco' ),
		);

		$args = array(
			'label'                 => __( 'Thread', 'enco' ),
			'description'           => __( 'Engaging Comments - Thread', 'enco' ),
			'labels'                => $labels,
			'supports'              => array( '' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-format-chat',
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,		
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'rewrite'               => false,
			'capability_type'       => 'post',
		);

		register_post_type( 'enco_thread', $args );
	}

	/**
	 * Helper function to get the current post's given meta.
	 * 
	 * @param  string 	$key 	Meta key.
	 * @return mixed
	 */
	public function get_meta( $key ) {
		global $post;

		$field = get_post_meta( $post->ID, $key, true );
		if ( ! empty( $field ) ) {
			return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
		} else {
			return false;
		}
	}

	/**
	 * Add and register our enco_thread post type meta boxes.
	 * 
	 * @return void
	 */
	public function meta_box_add() {

		add_meta_box(
			'thread_details-thread',
			__( 'Thread Details', 'enco' ),
			array( $this, 'meta_box_html_details' ),
			'enco_thread',
			'side',
			'default'
		);

		add_meta_box(
			'thread_details-subject',
			__( 'Thread Subject', 'enco' ),
			array( $this, 'meta_box_html_subject' ),
			'enco_thread',
			'normal',
			'default'
		);

		add_meta_box(
			'thread_details-comments',
			__( 'Thread Comments', 'enco' ),
			array( $this, 'meta_box_html_comments' ),
			'enco_thread',
			'normal',
			'default'
		);
	}

	/**
	 * Output the comments belonging to this thread.
	 * 
	 * @param  WP_Post The post.
	 * @return void
	 */
	public function meta_box_html_comments( $post ) {

		$thread = new Enco_Thread( $post->ID );
		$comments = $thread->fetch_parents();
		$thread->output_comments();
	}

	/**
	 * Output the enco_thread's subject.
	 * 
	 * @param  WP_Post The post.
	 * @return void
	 */
	public function meta_box_html_subject( $post ) {

	?>
		<p><textarea name="thread_subject" id="thread_subject" class="widefat" rows="5" required><?php echo $this->get_meta( 'thread_subject' ); ?></textarea></p>
	<?php
	}

	/**
	 * The metabox callback functions displaying the thread details.
	 *
	 * @param  WP_Post $post The post being viewed and edited.
	 * @return void
	 */
	public function meta_box_html_details( $post ) {
		
		wp_nonce_field( '_thread_details_nonce', 'thread_details_nonce' ); ?>

		<p>Information directly related to this Thread.</p>

		<p>
			<label for="thread_post_id"><?php _e( 'Post ID', 'enco' ); ?></label><br>
			<select name="thread_post_id" id="thread_post_id" required>
				<option value="">-</option>
				<?php
					$args = array(
						'post_type' => 'post',
						'post_status' => 'publish',
						'posts_per_page' => -1,
						'numberposts' => -1,
						'order' => 'title',
						'orderby' => 'ASC'
					);

					$posts = get_posts( $args );
					$selected_id = (int)$this->get_meta( 'thread_post_id' );

					foreach ( $posts as $post ) {

					$occ = intval( $this->get_meta( 'thread_occurrence' ) );
					?>
					<option value="<?php echo $post->ID ?>" <?php selected( $selected_id, $post->ID ) ?>><?php echo $post->post_title ?></option>
				<? } ?>
			</select>
		</p>

		<p>
			<label for="thread_occurrence"><?php _e( 'Occurrence', 'enco' ); ?></label><br>
			<input type="number" name="thread_occurrence" id="thread_occurrence" value="<?php echo $occ; ?>" min="0" step="1" required>
		</p>

		<style>
			#misc-publishing-actions,
			#minor-publishing-actions {
				display: none;
			}
		</style>

		<?php
	}

	/**
	 * Saves the enco_thread post meta fields.
	 * 
	 * @param  int 		$post_id 	The updated post ID.
	 * @param  WP_Post 	$post 		The updated post object.
	 * @param  bool 	$update 	Update or new post.
	 * @return void
	 */
	public function meta_box_save( $post_id, $post, $update ) {
    	
		// Initial Checks
    	if ( $post->post_type != 'enco_thread' ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! isset( $_POST['thread_details_nonce'] ) || ! wp_verify_nonce( $_POST['thread_details_nonce'], '_thread_details_nonce' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		// Save Subject
		if ( isset( $_POST['thread_subject'] ) )
			update_post_meta( $post_id, 'thread_subject', esc_attr( $_POST['thread_subject'] ) );

		// Save Post ID
		if ( isset( $_POST['thread_post_id'] ) )
			update_post_meta( $post_id, 'thread_post_id', empty( $_POST['thread_post_id'] ) ? '0' : (int)$_POST['thread_post_id'] );

		// Save Occurrence
		if ( isset( $_POST['thread_occurrence'] ) )
			update_post_meta( $post_id, 'thread_occurrence', empty( $_POST['thread_occurrence'] ) ? '0' : (int)$_POST['thread_occurrence'] );

	}

}