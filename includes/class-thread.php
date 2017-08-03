<?php
/**
 * Class for Thread objects.
 *
 * @package    Enco
 * @author     Lazhar Ichir
 */

/**
 * Thread class.
 *
 * @since  1.0.0
 * @access public
 */
class Enco_Thread {

	/**
	 * Document Object.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    Enco_Document
	 */
	public $document;

	/**
	 * Post.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    WP_Post
	 */
	public $post;

	/**
	 * Thread ID.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    Enco_Document
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
	 * Bit that started the thread.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $subject = '';

	/**
	 * Occurence of the subject in the post.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    int
	 */
	public $occurrence = 0;

	/**
	 * Collection of the Comments.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    Enco_Comment_Collection
	 */
	public $comments;

	/**
	 * Collection of the Comments.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    Enco_Comment_Collection
	 */
	public $orphan_thread = false;

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function __construct( $p = null ) {

		$this->comments = new Enco_Comment_Collection();

		if( $p ) {
			$this->load_from_post( $p );
			$this->setup();
		}
	}

	/**
	 * Loads the thread from a post id or WP_Post object.
	 * 
	 * @param  	int|WP_Post		$p 		The Post id or object.
	 * @return 	void
	 */
	public function load_from_post( $p ) {

		if( $p instanceof WP_Post ) {
			$this->post = $p;
			$this->ID = $p->ID;
		} elseif ( is_int( $p ) ) {
			$this->post = get_post( $p );
			$this->ID = $p;
		} else {
			$get_post = get_post();
			if( $get_post ) {
				$this->post = $get_post;
				$this->ID = $get_post->ID;
			} else {
				$this->post = null;
				$this->ID = null;
			}
		}
	}

	/**
	 * Sets the class/thread up.
	 * 
	 * @return void
	 */
	public function setup() {

		$this->orphan_thread 	= false;
		$this->post_id  		= $this->get_post_id();
		$this->subject  		= $this->get_subject();
		$this->occurrence 		= $this->get_occurrence();

		$this->fetch_comments();
	}

	/**
	 * Sets this Thread as the Orphan Thread -- with no subject,
	 * mainly for old comments prior to plugin installation.
	 * 
	 * @param  int 			$post_id 	The post this thread belongs to.
	 * @return Enco_Thread 	Returns this Enco_Thread object.
	 */
	public function orphanage( $post_id = null ) {

		if( !empty( $post_id ) ) {
			$this->post_id = $post_id;
		}

		$this->orphan_thread 	= true;
		$this->ID 				= 0;

		$this->fetch_orphans();

		return $this;
	}

	//////
	//
	// THREAD MANAGEMENT
	//
	//////

	/**
	 * Check the thread before creating it by checking if
	 * - subject already part of an existing thread,
	 * - occurence is > 0 (subject exists in content) 
	 * 
	 * @param  int  	$post_id 		ID of the post.
	 * @param  string 	$subject 		Thread subjcet.
	 * @param  int 		$occurrence 	Occurrence in the content.
	 * @return bool
	 */
	public function check_thread( $post_id, $subject, $occurrence ) {

		$content 	= apply_filters( 'the_content', enco_the_content( $post_id ) );
		$how_many 	= substr_count( $content, $subject );

		if( ( $occurrence > -1 ) && ( $occurrence <= $how_many ) ) {
			if( !$this->in_subject_span( $subject, $content ) ) {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Checks the thread and goes ahead with creating the starting comment,
	 * along with the trhead (enco_thread post) itself.
	 * 
	 * @param  int 		$post_id 		Post commented/threaded on.
	 * @param  string 	$subject 		Subject picked for the thread.
	 * @param  int 		$occurrence 	Occurrence of the subject (if appears several times.)
	 * @param  string 	$comment_text 	Text of the starter comment.
	 * @param  array 	$author  		Array with the author's information.
	 * @param  array 	$meta  			Array to add comment meta fields.
	 * @return array  	$results 		Response array with basic info.
	 */
	public function start_thread( $post_id, $subject, $occurrence, $comment_text, $author = array(), $meta = array() ) {

		$thread_allowed = $this->check_thread( $post_id, $subject, $occurrence );

		if( $thread_allowed ) {

			$result = array();

			if( !max_threads( $post_id ) ) {

				// Set author's default values
				$author_defaults = array(
					'id' 		=> '',
					'name' 		=> 'Anonymous Lee',
					'email' 	=> '',
					'url' 		=> ''
				);

				$author = array_merge( $author_defaults, $author );
				$author = apply_filters( 'enco_thread_author', $author );

				// Put comment's data in an array
				$comment_data = array(
					'comment_post_ID' 		=> $post_id,
					'comment_author' 		=> $author['name'],
					'comment_author_email' 	=> $author['email'],
					'comment_author_url'	=> $author['url'],
					'comment_content' 		=> $comment_text,
					'comment_type' 			=> '',
					'comment_parent' 		=> '0',
					'comment_author_IP'		=> enco_current_user_ip(),
					'comment_date_gmt'		=> gmdate( "M d Y H:i:s" ),
					'user_id' 				=> empty($author['id']) ? null : $author['id']
				);
				
				$comment_data = apply_filters( 'enco_thread_comment_data', $comment_data );

				// Check comment and get new comment's ID
				$new_comment 	= new Enco_Comment();
				$status 		= $new_comment->check_comment( $comment_data );
				$new_comment_id = $new_comment->new_comment( $comment_data );

				// Create Thread
				$new_thread = $this->create( $post_id, $subject, $occurrence );

				// Update comment's thread_id meta
				if( !$this->orphan_thread ) {
					update_comment_meta( $new_comment_id, 'thread_id', $new_thread );
					update_comment_meta( $new_comment_id, 'thread_subject', $subject );
				}

				if( !empty( $meta ) ) {
					foreach ( $meta as $key => $value ) {
						update_comment_meta( $new_comment_id, $key, $value );
					}
				}

				// Do the setup
				$this->load_from_post( $new_thread );
				$this->setup();

				// Return
				$result = array_merge( array(
					'thread_id' 	=> $this->ID,
					'comment_id' 	=> $new_comment_id,
					'status' 		=> $status,
					'output'		=> $this->get_output()
				), $comment_data );

				do_action( 'enco_thread_started', $result );

			} else {

				// Return
				$result = array(
					'status' 		=> 'too_many'
				);
			}

			return apply_filters( 'enco_thread_started_result', $result );
		}
	}

	/**
	 * Inserts the new thread in the database and adds the meta fields:
	 * thread_post_id, thread_subject and thread_occurrence. 
	 * 
	 * @param  int 		$post_id 			Post the thread belongs to.
	 * @param  string 	$subject 			Subject of this thread.
	 * @param  int 		$occurence 			Occurrence.
	 * @return int 		$new_thread_id 		Newly created thread ID.
	 */
	public function create( $post_id, $subject, $occurrence ) {

		// Create post object
		$post_data = array(
			'post_type'		=> 'enco_thread',
			'post_title'    => '',
			'post_content'  => '',
			'post_status'   => 'publish',
			'post_author'   => 1
		);
		 
		// Insert the post into the database
		$new_thread_id = wp_insert_post( $post_data );

		// Update the post metas
		update_post_meta( $new_thread_id, 'thread_post_id', $post_id );
		update_post_meta( $new_thread_id, 'thread_subject', $subject );
		update_post_meta( $new_thread_id, 'thread_occurrence', $occurrence );

		return $new_thread_id;
	}

	/**
	 * Fires when a enco_thread post is being deleted from admin panel.
	 * We need to remove the thread_id for the comment's meta.
	 *
	 * @param int $post_id ID of the enco_thread being deleted.
	 */
	public function thread_being_deleted( $post_id ) {

		$post_type = get_post_type( $post_id );   
    	
    	if ( $post_type != 'enco_thread' )
    		return;

		$this->load_from_post( $post_id );
		$this->setup();

		$ids = $this->get_comment_ids();
		foreach ( $ids as $id ) {
		
			delete_comment_meta( $id, 'thread_id' );
		}
	}

	/**
	 * Change this thread's comments to belong to $new_thread_id,
	 * or make them orphans if not specified.
	 *
	 * Delete the thread afterwards.
	 * 
	 * @param  int 		$new_thread_id		Thread ID for substitution
	 * @return void
	 */
	public function suicide( $new_thread_id = null ) {

		$ids = $this->get_comment_ids();

		foreach ( $ids as $id ) {
			
			if( $new_thread_id ) {
				update_comment_meta( $id, 'thread_id', $new_thread_id );
			}
			else {
				delete_comment_meta( $id, 'thread_id' );
			}
		}

		$this->delete();
	}

	/**
	 * Deletes the thread.
	 * 
	 * @param  boolean $force Set to true if you want to delete definitively.
	 * @return boolean 
	 */
	public function delete( $force = false ) {
		
		if( $force )
			return wp_delete_post( $this->ID );
		else 
			return wp_trash_post( $this->ID );
	}

	//////
	//
	// FETCH COMMENTS
	//
	//////

	/**
	 * Base function to fetch the comments for this thread.
	 * 
	 * @param  array 	$args 		Arguements that will be passed to the WP_Comment_Query.
	 * @return array 	$returning 	Returns the fetched comments.
	 */
	public function fetch_comments( array $args = [] ) {

		$defaults = array(
			'post_id'		=> $this->post_id,
			'status' 		=> 'approve',
            'orderby'       => 'date',
            'order'         => 'ASC',
            'parent'		=> 0,
            'meta_query' 	=> array(
				array(
					'key' 		=> 'thread_id',
					'value' 	=> $this->ID
				)
			)
		);

		$args = array_merge( $defaults, $args );

		$comment_query = new WP_Comment_Query( $args );
		$comment_items = $comment_query->get_comments();

        $returning = array();

		foreach( $comment_items as $comment_item ) {

            $comment = new Enco_Comment( $comment_item );
		    $this->comments->add( $comment_item->comment_ID, $comment );
            $returning[ $comment_item->comment_ID ] = $comment;
		}

        return $returning;
	}

	/**
	 * Fetches the orphan comments for this post/document.
	 * (ie. without a thread_id meta field clearly defined)
	 * 
	 * @return array 	$returning 	Returns the fetched comments.
	 */
	public function fetch_orphans() {

		$args = array(
            'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' 		=> 'thread_id',
					'compare' 	=> 'NOT EXISTS'
				),
				array(
					'key' 		=> 'thread_id',
					'value' 	=> '0'
				),
				array(
					'key' 		=> 'thread_id',
					'value' 	=> ''
				)
			)
		);

		return $this->fetch_comments( $args );
	}

	/**
	 * Fetches the parent comments for this thread.
	 * 
	 * Children comments will be loaded recursviely
	 * by each Enco_Comment object.
	 * 
	 * @return array 	$returning 	Returns the fetched comments.
	 */
	public function fetch_parents() {

        return $this->fetch_comments( array( 'parent' => 0 ) );
	}

	//////
	//
	// AJAX
	//
	//////

	/**
	 * AJAX Receiver - Receives the new thread's data and prepares it.
	 * 
	 * @return void
	 */
	public function ajax_new_thread() {

		parse_str( $_POST['body'], $body );

		$user_id = 0;

		$meta = array();
		$meta = apply_filters( 'enco_new_thread_meta', $meta, $body );

		if( is_user_logged_in() && get_current_user_id() > 0 )
			$user_id = get_current_user_id();

		$result = $this->start_thread( 
			$body['thread_post_id'],
			$body['thread_subject'],
			$body['thread_occurrence'],
			$body['thread_comment'],
			$author = array(
				'name' 	=> $body['thread_author_name'],
				'email' => $body['thread_author_email'],
				'url' 	=> $body['thread_author_url'],
				'id' 	=> $user_id
			),
			$meta
		);

	    echo json_encode($result);
		wp_die();
	}

	//////
	//
	// HELPERS
	//
	//////

	/**
	 * Returns the IDS of all the comments
	 * for this thread.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_comment_ids() {

		$ids = array();

		$args = array(
            'meta_query' 	=> array(
				array(
					'key' 		=> 'thread_id',
					'value' 	=> $this->ID
				)
			)
		);

		$cq = new WP_Comment_Query( $args );
		$ci = $cq->get_comments();

		foreach( $ci as $c ) {
			$ids[] = $c->comment_ID;
		}

		return $ids;
	}

	/**
	 * Checks if the subject is already
	 * part of an existing thread's subject.
	 *
	 * If so, return true and don't accept it.
	 * 
	 * @param  string $subject 
	 * @param  string $content 
	 * @return bool
	 */
	public function in_subject_span( $subject, $content ) {

		$result = false;

		$regex = '/enco-subject(.*?)<\/span>/';

		preg_match_all( $regex, $content, $matches);

		foreach ( $matches as $match ) {
			
			if( !empty($match) ) {
			
				if( strpos( $match[0], $subject ) > -1 ) {
					$result = true;
				}	
			}
		}

		return $result;
	}

	/**
	 * Counts all comments belonging to this thread.
	 * 
	 * @return int Total of comments belonging to this Thread.
	 */
	public function total() {

		if( !$this->orphan_thread ) {

			$args = array(
				'post_id' 	 => $this->post_id,
	            'meta_query' => array(
	            	array(
						'key' 		=> 'thread_id',
						'value' 	=> $this->ID
					)
	            )
			);

		} else {
			
			$args = array(
				'post_id' 	 => $this->post_id,
	            'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' 		=> 'thread_id',
						'compare' 	=> 'NOT EXISTS'
					),
					array(
						'key' 		=> 'thread_id',
						'value' 	=> '0'
					),
					array(
						'key' 		=> 'thread_id',
						'value' 	=> ''
					)
				)
			);
		}

		$query = new WP_Comment_Query( $args );
		$total = $query->get_comments();
		$total = apply_filters( 'enco_thread_total', $total, $this->ID, $this->post_id );
		return count($total);
	}

	//////
	//
	// OUTPUT
	//
	//////

	/**
	 * The HTML output of the new thread <FORM>.
	 *
	 * @since  1.0.0
	 * @return array $ids
	 */
	public function output_reply_form() {

		?>

		<div id="enco-reply-<?php echo $this->ID; ?>" class="enco-reply">

			<form action="<?php echo admin_url('admin-ajax.php') ?>" method="post" id="enco-reply-form-<?php echo $this->ID; ?>" class="enco-reply-form" data-thread-id="<?php echo $this->ID; ?>">
				
				<header class="enco-reply-header">
					<h1><?php echo __( 'Engage In This Thread', 'enco' ) ?></h1>
				</header>

		<?php

		if( 
			( enco_only_logged_in_can_comment() && is_user_logged_in() ) || 
			( !enco_only_logged_in_can_comment() ) 
		  ) {

		?>

			<p class="enco-p-name">
				<label><input type="text" id="enco_comment_name" name="comment_name" placeholder="Name" required></label>
			</p>

			<p class="enco-p-email">
				<label><input type="email" id="enco_comment_email" name="comment_email" placeholder="Email" required></label>
			</p>

			<p class="enco-p-url">
				<label><input type="text" id="enco_comment_url" name="comment_url" placeholder="Website"></label>
			</p>

			<p class="enco-p-comment">
				<label><textarea id="enco_comment_content" name="comment_content" placeholder="Your Comment" required></textarea></label>
			</p>

			<p class="enco-p-submit">

				<input type="hidden" name="comment_thread_id" id="comment_thread_id" value="<?php echo $this->ID ?>"></input>

				<input type="hidden" name="comment_post_id" id="comment_post_id" value="<?php echo $this->post_id ?>"></input>
				
				<input type="hidden" name="comment_parent" id="comment_parent" value="0"></input>

				<input type="submit" name="enco-reply-submit" id="enco-reply-submit" class="enco-reply-submit" value="Post Comment"> 

				<input type="button" name="enco-reply-close" id="enco-reply-close" class="enco-reply-close" value="X" data-thread-id="<?php echo $this->ID ?>"> 

				<?php 
					// Executes the action hook named 'i_am_hook'
					do_action( 'enco_reply_form_near_submit' );
				?>

			</p>

		<?php

		} else {

		?>

			<p>
				<?php echo __( 'You must be <a href="<?php echo wp_login_url(); ?>" title="Login">registered and logged in</a> to comment.', 'enco' );
				?>
			</p>

		<?php
		}

		?>
		
			</form>
		</div>

		<?php
	}

	/**
	 * Outputs the HTML code for the orphan section,
	 * usually below the post/page content.
	 *
	 * @return void
	 */
	public function output_orphans() {

		if( comments_open() ) {

			echo '<h1 class="enco-orphans-title"><span>' . sprintf( _n( 'Join the conversation!', '%s General Comments', $this->total(), 'enco' ), $this->total() ) . '</span></h1>';
			
			$this->output_html();
		}
	}

	/**
	 * Outputs the HTMl code for the thread itself,
	 * including its own comments.
	 * 
	 * @return void
	 */
	public function output_html() {

		$orphan_class = $this->orphan_thread ? ' enco-orphan-thread' : '';

		echo '<div id="enco-thread-' . $this->ID . '" class="enco-thread enco-thread-post-' . $this->post_id  . $orphan_class . '" data-id="' . $this->ID . '">';

			if( !$this->orphan_thread ) { // Subject only for threads
				echo '<header class="enco-thread-header"><h1 class="enco-thread-subject">' . $this->subject . '</h1></header>';
			}

			// Comment List
			$this->output_comments();

			// Reply Form
			if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			} else {
				$this->output_reply_form();
			}

		echo '</div>';
	}

	/**
	 * Buffers the HTML to return it as a string, instead of echoing it.
	 * 
	 * @return string
	 */
	public function get_output() {

		ob_start();
		$this->output_html();
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Outputs the list of parent comments belonging to this thread.
	 * 
	 * @return void
	 */
	public function output_comments() {

		echo '<ul id="enco-children-of-' . $this->ID . '-0" class="enco-comment-list">';
		foreach ( $this->comments as $comment ) {
			$comment->output();
		}
		echo '</ul>';
	}

	/**
	 * Returns a parsed string of the thread's subject
	 * to use in the document content (highlighted).
	 *
	 * ie. adding the <span> and the comment count.
	 * 
	 * @return string
	 */
	public function parse_subject() {

		$parsed = '<span id="subject-' . $this->ID . '" class="enco-subject enco-subject-post-' . $this->post_id . '" data-id="' . $this->ID . '">' . apply_filters( 'enco_thread_subject', $this->subject ) . '<span class="enco-comment-count">' . apply_filters( 'enco_thread_subject_total', $this->total() ) . '</span></span> ';

		return apply_filters( 'enco_thread_parse_subject', $parsed );
	}

	/**
	 * Outputs the thread HTML code to display in the admin meta box.
	 * 
	 * @return void
	 */
	public function output_admin_mb() {

		?>

		<div id="thread-<?php echo $this->ID ?>" class="enco-admin-mb-thread clearfix">
			<h1 class="enco-admin-mb-thread-title" data-id="<?php echo $this->ID ?>">
				<div id="status-<?php echo $this->ID ?>" class="enco-status"></div>
				<?php echo $this->subject ?>
				<span class="enco-com-count"><?php echo $this->total() ?></span>
			</h1>
			<div id="enco-admin-mb-comments-<?php echo $this->ID ?>" class="enco-admin-mb-comments">
				<?php $this->output_comments(); ?>
			</div>
		</div>

		<?php
	}

	//////
	//
	// WRAPPERS
	//
	//////

	/**
	 * Returns the thread's subject.
	 * 
	 * @return string
	 */
	public function get_subject() {

		$subject = enco_flatten( get_post_meta( $this->ID, 'thread_subject', true ), false );
		return apply_filters( 'enco_thread_get_subject', $subject, $this );
	}

	/**
	 * Returns the thread's post id of the post it is linked to.
	 * 
	 * @return int
	 */
	public function get_post_id() {

		$post_id = intval( get_post_meta( $this->ID, 'thread_post_id', true ) );
		return apply_filters( 'enco_thread_get_post_id', $post_id, $this );
	}

	/**
	 * Returns the thread's subject occurrence.
	 * 
	 * @return string
	 */
	public function get_occurrence() {

		$occurrence = intval( get_post_meta( $this->ID, 'thread_occurrence', true ) );
		$occurrence = empty( $occurrence ) ? 0 : $occurrence;
		$occurrence = apply_filters( 'enco_thread_get_occurrence', $occurrence, $this );
		return $occurrence;
	}

}