<?php
/**
 * Class for Comment objects.
 *
 * @package    Enco
 * @author     Lazhar Ichir
 */

/**
 * Comment class.
 *
 * @since  1.0.0
 * @access public
 */
class Enco_Comment {

	/**
	 * Comment ID.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    int
	 */
	public $ID;

	/**
	 * Post ID.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    int
	 */
	public $post_id;

	/**
	 * Parent Comment ID.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    int
	 */
	public $parent_id;

	/**
	 * WP_Comment object itself.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    WP_Comment
	 */
	public $comment;

	/**
	 * Author of the comment.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    WP_User
	 */
	public $author;

	/**
	 * Thread.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    Enco_Thread
	 */
	public $thread;

	/**
	 * The comment's replies.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    Enco_Comment_Collection
	 */
	public $children;

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function __construct( $c = null ) {

		$this->children = new Enco_Comment_Collection();

		if( $c ) {
			$this->loadComment( $c );
			$this->loadAuthor();
			$this->fetch_children();
		}
	}
	
	/**
	 * Load the comment's data.
	 *
	 * @since   1.0.0
	 * @param 	int 	The comment ID
	 * @return  void
	 */
	public function loadComment( $c ) {

		$comment = get_comment( $c );

		if( $comment ) {

			$this->comment 			= $comment;
			
			$this->ID 				= $comment->comment_ID;
			$this->post_id 			= $comment->comment_post_ID;
			$this->parent_id 		= $comment->comment_parent;
		}
	}

	/**
	 * Load the comment's author information.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function loadAuthor() {

		$this->author = get_userdata( $this->comment->user_id );
	}

	//////
	//
	// COMMENT MANAGEMENT
	//
	//////

	/**
	 * Creates a comment.
	 * 
	 * @param  int 		$post_id 			Post the comment is linked to.
	 * @param  int 		$thread_id 			Thread the comment is linked to.
	 * @param  string 	$comment_content	Comment text.
	 * @param  int 		$parent 			Parent comment ID, or 0 if root.
	 * @param  array 	$author 			Array with the author's information.
	 * @param  array 	$meta 				Array with data to add as comment meta.
	 * @return array 	Array with basic information about the comment created.
	 */
	public function create( $post_id, $thread_id, $comment_content, $parent, $author = array(), $meta = array() ) {
		
		$author_defaults = array(
			'id' 		=> '',
			'name' 		=> 'Anonymous Lee',
			'email' 	=> '',
			'url' 		=> ''
		);

		$author = array_merge( $author_defaults, $author );
		$author = apply_filters( 'enco_comment_create_author', $author );

		$comment_data = array(
			'comment_post_ID' 		=> $post_id,
			'comment_author' 		=> $author['name'],
			'comment_author_email' 	=> $author['email'],
			'comment_author_url'	=> $author['url'],
			'comment_content' 		=> $comment_content,
			'comment_parent' 		=> $parent,
			'comment_type' 			=> '',
			'comment_author_IP'		=> enco_current_user_ip(),
			'comment_date_gmt'		=> gmdate( "M d Y H:i:s" ),
			'user_id' 				=> empty($author['id']) ? null : $author['id']
		);
		$comment_data = apply_filters( 'enco_comment_create_comment_data', $comment_data );

		$status 		= $this->check_comment( $comment_data );
		$new_comment_id = $this->new_comment( $comment_data );
	
		update_comment_meta( $new_comment_id, 'thread_id', $thread_id );

		if( !empty( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				update_comment_meta( $new_comment_id, $key, $value );
			}
		}

		$this->loadComment( $new_comment_id );
		$this->loadAuthor();

		$result = array_merge( array(
			'comment_id' 	=> $new_comment_id,
			'thread_id' 	=> $thread_id,
			'status' 		=> $status,
			'output'		=> $this->get_output()
		), $comment_data );

		do_action( 'enco_comment_created', $result );

		return apply_filters( 'enco_comment_create_result', $result );
	}

	/**
	 * Insert the new comment in the DB.
	 *
	 * @since  	1.0.0
	 * @param 	array 	$comment Array containing the comment data.
	 * @return 	int 	Returns the new comment ID.
	 */
	public function new_comment( $comment ) {

		$new_comment_id = wp_new_comment( $comment );
		return apply_filters( 'enco_comment_new_comment_id', $new_comment_id );
	}

	/**
	 * Check if we're allowed to add the comment.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return int|string 1=allowed, 0=moderation, or 'spam'
	 */
	public function check_comment( $comment ) {
		
		$status = wp_allow_comment( $comment );
		return apply_filters( 'enco_comment_check_comment', $status );
	}

	/**
	 * Deletes the current comment but also:
	 * - Changes the parent of each child comment
	 * - Deletes the thread if this was the last comment
	 * 
	 * @return bool 	$return 	True on success. False, otherwise.
	 */
	public function delete() {


		// Change parents of each reply
		$this->fetch_children();
		$children = $this->children;
		$children = apply_filters( 'enco_comment_delete_children', $children );

		foreach ( $children as $comment ) {
			$comment->update_parent( $this->parent_id );
		}

		// Delete the thread if it was the last comment
		$thread_id = $this->get_the_thread_id();
		
		if( !empty( $thread_id ) ) {
			
			$thread = new Enco_Thread( $thread_id );
			$total = $thread->total();
			if( $total == 1 ) {
				do_action( 'enco_delete_thread_with_last_comment', $thread );
				$thread->delete();
			}
		}

		do_action( 'enco_comment_delete_before', $this );
		$return = wp_delete_comment( $this->ID );
		do_action( 'enco_comment_delete_after', $this );

		return $return;
	}

	/**
	 * Fetches comments children to the current one.
	 * 
	 * @param  array $args Optional arguements for the WP_Comment_Query.
	 * @return array $returning An array with the fetched children (comments.)
	 */
	public function fetch_children( array $args = [] ) {

		$defaults = array(
			'status' 		=> 'approve',
            'orderby'       => 'date',
            'order'         => 'ASC',
            'parent'		=> $this->ID
		);

		$args = array_merge( $defaults, $args );

		$comment_query = new WP_Comment_Query( $args );
		$comment_items = $comment_query->get_comments();

        $returning = array();

		foreach( $comment_items as $comment_item ) {

            $comment = new Enco_Comment( $comment_item );

		    $this->children->add( $comment_item->comment_ID, $comment );
            $returning[ $comment_item->comment_ID ] = $comment;

		}

        return $returning;
	}

	//////
	//
	// OUTPUTS
	//
	//////

	/**
	 * Outputs this comment's HTML code.
	 * 
	 * @return void
	 */
	public function output() {

		?>
		<li id="enco-comment-<?php echo $this->ID ?>" class="enco-comment enco-comment-id-<?php echo $this->ID ?>" data-id="<?php echo $this->ID ?>">

			<div class="enco-comment-itself" id="comment-<?php echo $this->ID ?>">
		
				<header>
					<div class="enco-comment-gravatar"><?php $this->the_gravatar(); ?></div>
					<div class="enco-comment-author"><?php $this->the_author_username(); ?></div>
					<div class="enco-comment-date"><?php $this->the_date(); ?></div>
				</header>

				<div class="enco-comment-content">
					<div class="enco-comment-content-inner">
						<?php $this->the_content(); ?>
					</div>
				</div>

				<?php 
				
				if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) { ?>

				<footer class="clear">
					
					<?php 
						do_action( 'enco_comment_form_footer', $this );
						do_action( 'enco_comment_form_footer_admin', $this );
					?>

					<div class="enco-comment-actions">						
						<a href="<?php echo get_edit_post_link( $this->get_the_thread_id() ) ?>" class="enco-comment-thread-link"><?php echo __( 'Edit Thread', 'enco') ?></a>

						<a href="<?php echo get_edit_comment_link( $this->ID ) ?>" class="enco-comment-edit-link"><?php echo __( 'Edit Comment', 'enco') ?></a>
						
						<a href="<?php echo get_comment_link( $this->ID ) ?>" class="enco-comment-reply-link" target="_blank"><?php echo __( 'Reply', 'enco') ?></a>

						<?php if( current_user_can('moderate_comments') ) : ?><span class="enco-comment-delete-link" data-id="<?php echo $this->ID ?>" data-id="<?php echo $this->ID ?>">Delete</span><?php endif; ?>
					</div>
				</footer>

				<?php } else { ?>

				<footer class="clear">

					<?php do_action( 'enco_comment_form_footer', $this ); ?>
					
					<div class="enco-comment-actions">
						
						<span class="enco-comment-reply-link" data-id="<?php echo $this->ID ?>" data-thread-id="<?php $this->the_thread_id() ?>"><?php echo __( 'Reply', 'enco') ?></span>
						
						<?php if( current_user_can('moderate_comments') ) : ?><span class="enco-comment-delete-link" data-id="<?php echo $this->ID ?>" data-id="<?php echo $this->ID ?>"><?php echo __( 'Delete', 'enco') ?></span><?php endif; ?>

					</div>

				</footer>

				<div id="enco-reply-container-id-<?php echo $this->ID ?>" class="enco-reply-container"></div>

				<?php } ?>

			</div>

			<?php $this->output_children(); ?>

		</li>
		<?
	}

	/**
	 * Outputs the children comments to the current one.
	 * @return void
	 */
	public function output_children() {

		?>
		<ul id="enco-children-of-<?php $this->the_thread_id() ?>-<?php echo $this->ID ?>" class="enco-comment-children enco-comment-children-of-<?php echo $this->ID ?>">
		<?php
			foreach ( $this->children as $comment ) {
				$comment->output();
			}
		?>
		</ul>
		<?php
	}

	//////
	//
	// HELPERS
	//
	//////

	/**
	 * Updates the parent of the current comment.
	 * 
	 * @param  int $new_parent_id 
	 * @return bool
	 */
	public function update_parent( $new_parent_id = 0 ) {

		$commentarr 					= array();
		$commentarr['comment_ID'] 		= $this->ID;
		$commentarr['comment_parent'] 	= $new_parent_id;
		return wp_update_comment( $commentarr );	
	}

	/**
	 * Sets this comment's thread.
	 * 
	 * @param 	int|string 	The thread_id to set, or empty string to remove. 
	 * @param 	int|null 	ID of the comment (or current one if null.)
	 * @return 	bool 		True on success.
	 */
	public function set_thread( $thread_id = '', $comment_id = null ) {

		if( !$comment_id )
			return $this->update_meta( 'thread_id', $thread_id );
		else
			return update_comment_meta( $comment_id, 'thread_id', $thread_id );
	}

	/**
	 * Buffers the HTML output and returns it -- instead of echoing it.
	 * @return string 	The html output source code.
	 */
	public function get_output() {

		ob_start();
		$this->output();
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Check whether or not the author of the comment 
	 * was logged in or anonymous.
	 * 
	 * @return boolean False if author signed up. True if anonymous.
	 */
	public function is_anonymous() {
		if( $this->comment->user_id > 0 )
			return false;
		else
			return true;
	}

	//////
	//
	// AJAX
	//
	//////

	/**
	 * AJAX Receiver - Receives the new reply's data and prepares it.
	 * 
	 * @return void
	 */
	public function ajax_thread_reply() {

		parse_str( $_POST['body'], $body );

		$user_id = 0;
		
		$meta = array();
		$meta = apply_filters( 'enco_thread_reply_meta', $meta, $body );

		if( is_user_logged_in() && get_current_user_id() > 0 )
			$user_id = get_current_user_id();

		$result = $this->create(
			$body['comment_post_id'],
			empty( $body['comment_thread_id'] ) ? 0 : $body['comment_thread_id'],
			$body['comment_content'],
			empty( $body['comment_parent'] ) ? 0 : $body['comment_parent'],
			$author = array(
				'name' 	=> $body['comment_name'],
				'email' => $body['comment_email'],
				'url' 	=> $body['comment_url'],
				'id' 	=> $user_id
			),
			$meta
		);

	    echo json_encode($result);
		wp_die();
	}

	/**
	 * AJAX Receiver - Sets or changes this comment's thread.
	 * 
	 * @return void
	 */
	public function ajax_set_thread() {

		$comment_id = intval($_POST['comment_id']);
		$thread_id = intval($_POST['thread_id']);

		$done = $this->set_thread( $thread_id, $comment_id );

		if( $done ) {
			
			$this->loadComment($comment_id);
			$this->loadAuthor();
			
			$result = array(
				'comment_id' 	=> $comment_id,
				'thread_id' 	=> $thread_id,
				'status' 		=> '1',
				'output'		=> $this->get_output()
			);
		} else {
			$result = array(
				'comment_id' 	=> $comment_id,
				'thread_id' 	=> $thread_id,
				'status' 		=> '0',
				'output'		=> __('This comment could not be moved.', 'enco')
			);
		}

	    echo json_encode($result);
		wp_die();
	}

	/**
	 * AJAX Receiver - Loads and prepares the data to delete a comment.
	 * 
	 * @return void
	 */
	public function ajax_delete() {

		if( !current_user_can('moderate_comments') ){
		    $result = array( 'comment_id' => $this->ID, 'deleted' => 0 );
		} else {
			$id = $_POST['comment_id'];
			$this->loadComment( $id );
			$this->loadAuthor();
			$this->fetch_children();

			$deleted = $this->delete();

			$result = array(
				'comment_id' 	=> $this->ID,
				'deleted' 		=> $deleted ? 1 : 0
			);
		}
			
	    echo json_encode($result);
		wp_die();
	}

	//////
	//
	// WRAPPERS
	//
	//////

	/**
	 * Returns the current comment's thread id.
	 * @return int 	Thread ID
	 */
	public function get_the_thread_id() {

		$thread_id = intval( $this->get_meta( 'thread_id' ) );
		$thread_id = empty( $thread_id ) ? 0 : $thread_id;
		return $thread_id;
	}

	/**
	 * Outputs the current comment's thread id.
	 */
	public function the_thread_id() {

		echo apply_filters( 'comment_the_thread_id', $this->get_the_thread_id() );
	}

	/**
	 * Returns the Gravatar image URL for the comment's author.
	 * @return string The URL of the image.
	 */
	public function get_the_gravatar() {
		
		if( $this->author )
			$url = get_avatar_url( $this->author->user_email );

		if( empty( $url ) )
			$url = ENCO_ASSETS_URL . 'images/anonymous.svg';

		return apply_filters( 'enco_get_the_gravatar', $url );
	}

	/**
	 * Outputs the Gravatr image for the user.
	 * @return void
	 */
	public function the_gravatar() {

		$output = '<img class="enco-gravatar-image" src="' . $this->get_the_gravatar() . '">';
		echo apply_filters( 'enco_the_gravatar', $output );
	}

	/**
	 * Returns the current comment's date.
	 * 
	 * @param  string Date format string.
	 * @return string The formatted comment's date.
	 */
	public function get_the_date( $f = 'l, F jS, Y' ) {
		
		$wpdformat = get_option('date_format');

		if( !empty( $wpdformat ) )
			$f =  $wpdformat;
		
		$date = date( $f, strtotime( $this->comment->comment_date ) );
		return apply_filters( 'enco_comment_get_the_date', $date );
	}

	/**
	 * Outputs the current comment's date.
	 * 
	 * @param  string Date format string.
	 * @return void
	 */
	public function the_date( $f = 'l, F jS, Y' ) {

		$output = $this->get_the_date( $f );
		echo apply_filters( 'enco_comment_the_date', $output );
	}

	/**
	 * Returns the current comment's content text.
	 * 
	 * @return string The comment's text.
	 */
	public function get_the_content() {

		$comment_text = nl2br( $this->comment->comment_content );
		return apply_filters( 'enco_comment_get_the_content', $comment_text );
	}

	/**
	 * Outputs the current comment's content text.
	 * 
	 * @return void
	 */
	public function the_content() {

		$comment_text = $this->get_the_content();
		echo apply_filters( 'enco_comment_the_content', $comment_text );
	}

	/**
	 * Returns the current comment's author's username.
	 * 
	 * @return string The comment's author's username.
	 */
	public function get_the_author_username() {

		$username = $this->comment->comment_author;

		if( !$this->is_anonymous() )
			$username = $this->author->user_login;
		
		return apply_filters( 'enco_comment_get_the_author_username', $username );
	}

	/**
	 * Outputs the current comment's author's username.
	 * 
	 * @return void
	 */
	public function the_author_username() {
		
		$username = $this->get_the_author_username();
		echo apply_filters( 'enco_comment_the_author_username', $username );
	}

	/**
	 * Returns the current comment's author's fullname.
	 * 
	 * @return string The comment's author's fullname.
	 */
	public function get_the_author_fullname() {

		$fullname = $this->author->first_name . ' ' . $this->author->last_name;

		if( $this->is_anonymous() )
			$fullname = $this->comment->comment_author;
		
		return apply_filters( 'enco_comment_get_the_author_fullname', $fullname );
	}

	/**
	 * Outputs the current comment's author's fullname.
	 * 
	 * @return void
	 */
	public function the_author_fullname() {
		$fullname = $this->get_the_author_fullname();
		echo apply_filters( 'enco_comment_the_author_fullname', $fullname );
	}

	/**
	 * Update the comment's meta (wrapper.)
	 * 
	 * @param  string $key 		The key of the meta to update.
	 * @param  string $value 	The new value.
	 * @return bool True on success. False if failed.
	 */
	public function update_meta( $key, $value ) {

		$value = apply_filters( 'enco_comment_update_meta', $value );
		return update_comment_meta( $this->ID, $key, $value );
	}

	/**
	 * @param  string $key The key of the meta to retrieve.
	 * @param  boolean Returns an array if false. A string of the first occurrence if true.
	 * @return mixed
	 */
	public function get_meta( $key, $single = true ) {

		return get_comment_meta( $this->ID, $key, $single );
	}

}