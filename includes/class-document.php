<?php
/**
 * Class starting the process of parsing posts and threads.
 *
 * @package    enco
 * @subpackage includes
 * @author     Lazhar Ichir
 */

/**
 * Enco_Document class.
 *
 * @since  1.0.0
 * @access public
 */
class Enco_Document {

	/**
	 * Post Object.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    WP_Post
	 */
	public $post = null;

	/**
	 * Post ID.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    int
	 */
	public $post_id = null;

	/**
	 * Array of part objects.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    Enco_Thread_Collection
	 */
	public $threads = null;

	/**
	 * Array of comment objects.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    array
	 */
	public $orphans = null;

	/**
	 * IDs of the threads to show
	 * in the orphan section.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    array
	 */
	public $show_in_orphans = array();

	/**
	 * Document content.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $content = null;

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function __construct() {

		$this->threads = new Enco_Thread_Collection();
	}

	/**
	 * Load document from a WP_Post object or post id.
	 *
	 * @since   1.0.0
	 * @param 	int|WP_Post $p The post_id or WP_Post object to load the document from.
	 * @return  void
	 */
	public function load_from_post( $p = null ) {

		if( $p instanceof WP_Post ) {
			$this->post = $p;
			$this->post_id = $p->ID;
			$this->content = $p->post_content;
		} elseif ( is_int( $p ) ) {
			$this->post = get_post( $p );
			$this->post_id = $p;
			$this->content = $this->post->post_content;
		} else {
			$get_post = get_post();
			if( $get_post ) {
				$this->post = $get_post;
				$this->post_id = $get_post->ID;
				$this->content = $get_post->post_content;
			} else {
				$this->post = null;
				$this->post_id = null;
				$this->content = null;
			}
		}

		if( $this->post )
			$this->fetch_threads();

		return $this;
	}

	/**
	 * Retrieve the threads.
	 *
	 * @since  1.0.0
	 * @param array $args Arguements to use in the WP_Query.
	 * @return void
	 */
	public function fetch_threads( array $args = [] ) {

		$orphanage = new Enco_Thread();
		$orphanage = $orphanage->orphanage( $this->post->ID );
		$this->threads->add( '0', $orphanage );

		$items = array();

		$defaults = array(
			'post_type'		=> 'enco_thread',
			'status' 		=> 'publish',
            'meta_key'      => 'thread_post_id',
            'meta_value'    => $this->post->ID
		);

		$args = array_merge( $defaults, $args );

		$query = new WP_Query( $args );
		$items = $query->get_posts();

		foreach( $items as $thread_item ) {

			if( !$this->threads->has( $thread_item->ID ) ) {

	            $thread = new Enco_Thread( $thread_item );
			    $this->threads->add( $thread->ID, $thread );
	        }
		}

        return $this->threads;
	}

	/**
	 * Parses the content to use our own HTML span tags
	 * around the thread subjects.
	 *
	 * Also performs some checks and adds overflowing
	 * threads in the orphan section, below the post.
	 *
	 * @since  1.0.0
	 * @param  string 	$content Use current class' content on null.
	 * @return string 	$content
	 */
	public function contentify( $content = null ) {

		if( !$content )
			$content = $this->content;

		// Sorting threads by the most commented on top first
		$this->threads->sort_by_total();

		$content = $content;

		// Retrieving the maximum threads to highlight in post content
		$max = get_enco_max_highlighted_threads();
		$i = 0;

		foreach ( $this->threads as $thread ) {
			
			if( !$thread->orphan_thread && $thread->total() > 0 ) {
			
				if( $this->highlight_or_not() && (( $i < $max ) || ( $max == -1 ) ) ) {
		
					$content  = enco_flatten( $content, false );

					$content  = enco_str_replace_nth(
						$thread->subject,
						$thread->parse_subject(),
						$content,
						$thread->occurrence
					);

					$i++;

				} else {

					$this->show_in_orphans[] = $thread->ID;
				}
			}
		}

		return $this->wrap_in_cotton( $content );
	}

	/**
	 * Fires when a post is updated so we can fix
	 * broken threads and put the comments as orphans.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 * @return string $content
	 */
	public function post_updated( $post_id, $post ) {


		$post_type = get_post_type( $post_id );
		if ( !(is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )) ) { return; }
		if ( $post_type=='revision' ) { return; }
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
		if ( !in_array( $post_type, enco_linked_post_types() ) ) { return; }

		// Load post and threads
		$this->load_from_post( $post );

		// Foreach (non orphan, non empty) thread, 
		// - check if subject still there.
		// --- if not, fire the Thread's suicide() function
		foreach ( $this->threads as $thread ) {
			
			if( !$thread->orphan_thread && $thread->total() > 0 ) {
			
				$content = enco_flatten( $this->post->post_content, true );
				$found = enco_strposX( $content, $thread->subject, $thread->occurrence );

				if( !$found ) {
					$thread->suicide();
				}
			}
		}
	}

	//////
	//
	// HELPERS
	//
	//////

	/**
	 * Whether a document/post should be parsed or not.
	 * 
	 * Using the _enco_disable_in_content = true
	 * in the post meta cancels the parsing.
	 * 
	 * @return bool 
	 */
	public function highlight_or_not() {

		$doit = true;
		$opt  = intval( get_post_meta( get_the_ID(), '_enco_disable_in_content', true ) );
		
		if( $opt > 0 )
			$doit = false;
		
		return apply_filters( 'enco_document_highlight_or_not', $doit, $this );
	}

	/**
	 * Returns the IDS of all the comments
	 * for this document.
	 *
	 * @since  1.0.0
	 * @return array $ids
	 */
	public function get_comment_ids() {

		$ids = array();

		if( !$this->threads->isEmpty() ) {

			foreach ( $this->threads as $thread ) {

				if( !$thread->comments->isEmpty() ) {

					foreach( $thread->comments as $comment ) {

						$ids[] = $comment->ID;

					}
				}
			}
		}

		return $ids;
	}

	/**
	 * Check if we reached the maximum threads
	 * allowed as per the plugin settings.
	 *
	 * Return true if we don't want to accept further threads.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function max_thread() {

		$max = intval( get_enco_max_threads() );

		if( $max == -1 ) {
			return false;
		} else if ( $max == 0 ) {
			return true;
		} else if ( ($this->threads->count()-1) < $max ) {
			return false;
		} else {
			return true;
		}
	}

	//////
	//
	// OUTPUT
	//
	//////

	/**
	 * Outputs the HTML code for this document.
	 * Engaging Convo needs the blow to work:
	 * - enco-overlay <div> to host the highlighted threads (+ close button)
	 * - enco-orphans <div> to host the orphans (threads & comments)
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function output_html() {

		echo '<div id="enco-overlay" class="enco-overlay">';

			foreach ( $this->threads as $thread ) {
				if( !$thread->orphan_thread && !in_array( $thread->ID, $this->show_in_orphans ) )
					echo $thread->output_html();
			}

			if( !$this->max_thread() ) {
				$this->output_start_thread();
			}

			$this->output_credits();

		echo '<span class="enco-close-btn">X</span></div>';

		echo '<div id="enco-orphans" class="enco-orphans">';

			if( !empty( $this->show_in_orphans ) ) {

				$total_to_show = count($this->show_in_orphans);

				echo '<div id="enco-orphans-poured" class="enco-orphans-poured">';

				echo '<h1 class="enco-orphans-title"><span>' . sprintf( _n( '%s Other Thread', '%s Other Threads', $total_to_show, 'enco' ), $total_to_show ) . '</span></h1>';

				foreach ( $this->threads as $thread ) {
					if( !$thread->orphan_thread && in_array( $thread->ID, $this->show_in_orphans ) )
						echo $thread->output_html();
				}
				echo '</div>';

			}

			if( $this->threads->count() > 0 && $this->threads->get(0) ) {
				$orphan_thread = $this->threads->get(0);
				$orphan_thread->output_orphans();
			}				

		echo "</div>";
	}

	/**
	 * Outputs some CSS, HTML or any code in the footer.
	 * - enco-tooltip <div> is required for notices
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function enco_footer() {

		?>
		<div id="enco-tooltip" class="enco-tooltip"></div>
		<?php
		do_action( 'enco_document_footer', $this );
	}

	/**
	 * Outputs the plugin's credits at the bottom of the overlay.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function output_credits() {

		$show = enco_option('show_plugin_credits');
		if( !empty( $show ) )
			echo '<footer class="enco-overlay-credits"><p>Powered By <a href="http://engagingconvo.com/" target="_blank">Engaging Convo</a><p></footer>';
	}

	/**
	 * Outputs the HTML code of the <FORM> to start a new thread.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function output_start_thread() {

		?>

		<form action="<?php echo admin_url('admin-ajax.php') ?>" method="post" id="enco-start-thread" class="enco-start-thread">

			<header class="enco-start-thread-header">
				<textarea id="enco-thread-subject" name="thread_subject" readonly></textarea>
			</header>

			<p class="enco-p-name">
				<label><input type="text" id="enco-thread-name" name="thread_author_name" placeholder="Name" required></label>
			</p>

			<p class="enco-p-email">
				<label><input type="email" id="enco-thread-email" name="thread_author_email" placeholder="Email" required></label>
			</p>

			<p class="enco-p-url">
				<label><input type="text" id="enco-thread-url" name="thread_author_url" placeholder="Website"></label>
			</p>

			<p class="enco-p-comment">
				<label><textarea id="enco-thread-comment" name="thread_comment" placeholder="Your Comment" required></textarea></label>
			</p>

			<footer>

				<input type="hidden" name="thread_post_id" id="thread_post_id" value="<?php echo $this->post_id ?>"></input>
			
				<input type="hidden" name="thread_occurrence" id="thread_occurrence" value=""></input>
			
				<input type="submit" name="enco-start-thread-submit" id="enco-start-thread-submit" class="enco-reply-submit" value="Start Thread"> 

				<input type="button" name="enco-start-thread-close" id="enco-start-thread-close" class="enco-reply-close" value="X">
				 
				<?php 
					// Executes the action hook named 'enco_start_thread_form_near_submit'
					do_action( 'enco_start_thread_form_near_submit' );
				?>

			</footer>

		</form>

		<?php
	}

	/**
	 * Wrap content in our own DIV.
	 *
	 * @since  1.0.0
	 * @param  string $content The content to wrap.
	 * @return string $content
	 */
	public function wrap_in_cotton( $content = null ) {

		return '<div id="enco-content" class="enco-content">' . $content . '</div>';
	}

}