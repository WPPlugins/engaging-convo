<?php
/**
 * Class for the Thread Custom Post Type.
 * Mainly manages the metaboxes in all enabled post types..
 * 
 * @package    Enco
 * @author     Lazhar Ichir
 */

/**
 * Thread Custom Post Type.
 *
 * @since  1.0.0
 * @access public
 */
class Enco_Post_Manager {

	private $document;

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function __construct() {

	    global $pagenow;

	    if ( 'post.php' === $pagenow && isset($_GET['post']) && in_array( get_post_type( $_GET['post'] ), enco_linked_post_types() ) ) {
	
			$this->document = new Enco_Document();
	   		add_action( 'add_meta_boxes', array( $this, 'meta_box_add' ) );
			add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 3 );
	    }
	}
	
	/**
	 * Registers our meta boxes.
	 * 
	 * @return void
	 */
	public function meta_box_add() {

		add_meta_box(
			'enco-thread-list',
			__( 'List Of Threads', 'enco' ),
			array( $this, 'meta_box_html_thread_list' ),
			enco_linked_post_types(),
			'advanced',
			'default'
		);

		add_meta_box(
			'enco-orphans',
			__( 'Orphan Comments', 'enco' ),
			array( $this, 'meta_box_html_orphans' ),
			enco_linked_post_types(),
			'normal',
			'default'
		);
	}

	/**
	 * The metabox callback functions displaying orphan comments.
	 *
	 * @param  WP_Post $post The post being edited.
	 * @return void
	 */
	public function meta_box_html_orphans( $post ) {

		global $post;

		$args = array(
			'post_id' => $post->ID,
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
		
		$orphanage = get_comments($args);
		$threads   = '';

		foreach ($this->document->threads as $thread) {
			if( $thread->ID > 0 )
				$threads[$thread->ID] = $thread->subject;
		}

		foreach ($orphanage as $comment) {
			
			?>

			<div id="enco-orphan-<?php echo $comment->comment_ID; ?>" class="enco-orphan">
				<select id="enco-move-to-<?php echo $comment->comment_ID; ?>" class="enco-orphan-admin-move-to" data-comment-id="<?php echo $comment->comment_ID; ?>">
					<option value="">Move to a thread</option>
					<?php
					foreach ( $threads as $key => $value ) {
						echo '<option value="'.$key.'">'.$value.'</option>';
					}
					?>
				</select>
				<span><?php echo $comment->comment_content; ?></span>
			</div>

			<hr>

			<?php
		}
	}

	/**
	 * The metabox callback functions displaying
	 * the list of our threads.
	 * 
	 * Also outputs the Javascript work checking
	 * this post's threads during editing and at saving.
	 *
	 * @param  WP_Post $post The post being edited.
	 * @return void
	 */
	public function meta_box_html_thread_list( $post ) {

		global $post;
		
		wp_nonce_field( '_thread_list_nonce', 'thread_list_nonce' ); 
		$this->document->load_from_post( $_GET['post'] );
		$arrayadds = '';

		foreach ($this->document->threads as $thread) {

			if( $thread->ID > 0 ){
				$thread->output_admin_mb();
				$arrayadds .= 'enco_threads["'.$thread->ID.'"] = [ "'.$thread->occurrence.'", "'.str_replace('"', '\"', $thread->subject).'"]; ';
			}
		}

		?>

		<script>

			jQuery( window ).load(function() {

				var enco_threads = {};
				<?php echo $arrayadds; ?>

				enco_scanContent( enco_threads );
				setInterval( function() { enco_scanContent( enco_threads ); }, 3 * 1000 );

			    jQuery('#post').on('submit', function(e){
					
					if( !enco_finalScan( enco_threads ) ) {
					    e.preventDefault();
					    return false;
					}
			    });

			    jQuery('.enco-admin-mb-thread').on('click', '.enco-admin-mb-thread-title', function(e){
					
					jQuery( '#enco-admin-mb-comments-' + jQuery(this).data('id') ).toggle();
			    });

			    function enco_finalScan( enco_threads, e ) {

				    var content = tinymce.activeEditor.getContent()
				    	.replace( /<br \/> /g, '<br>' )
				    	.replace( /<br\/> /g, '<br>' )
				    	.replace( /<br \/>/g, '<br>' )
				    	.replace( /<br\/>/g, '<br>' );
					var alert 		= false;
					var brokencount = 0;

				    jQuery.each(enco_threads, function(k, v) {
						if( enco_getPosition( content, v[1].trim(), v[0] ) == -1 ) {
						    alert = true;
						    brokencount++;
						}
					});

				    if( alert ) {
						r = confirm( <?php echo __( '"You have broken " + brokencount + " thread(s) attached to this post. If you confirm, the thread(s) will be deleted and the linked comments will become orphans."', 'enco' ) ?> );

						if ( !r ) {
						    return false;
						} else {
							return true;
						}
					} else {
						return true;
					}
			    }

				function enco_scanContent( enco_threads ) {

				    var content = tinymce.activeEditor.getContent()
				    	.replace( /<br \/> /g, '<br>' )
				    	.replace( /<br\/> /g, '<br>' )
				    	.replace( /<br \/>/g, '<br>' )
				    	.replace( /<br\/>/g, '<br>' );

				    jQuery.each(enco_threads, function(k, v) {

				    	var sub = v[1].trim();
				    	var occ = v[0];
				    	var pos = enco_getPosition( content, sub, occ );
				    	//console.debug( sub + ' ('+occ+')\n\n\n' + content);

						if( pos == -1 ) {
						    jQuery('#thread-' + k).addClass('enco-status-error');
						    jQuery('#thread-' + k).removeClass('enco-status-found');
						} else {
						    jQuery('#thread-' + k).addClass('enco-status-found');
						    jQuery('#thread-' + k).removeClass('enco-status-error');
						}

					});	

					content = null;
				}

				function enco_getPosition( str, substring, n ) {
				    var times = -1, index = null;

				    while (times < n && index !== -1) {
				        index = str.indexOf(substring, index+substring.length);
				        times++;
				    }

				    return index;
				}

			});

		</script>

		<?php
	}

	/**
	 * Hook to add saving logic here. Empty for now.
	 *
	 * @param  WP_Post $post The post being edited.
	 * @return void
	 */
	public function meta_box_save( $post_id, $post, $update ) {
	}

	/**
	 * Gets the $key post meta from the current post (global).
	 * 
	 * @param  string 		$key 	Key of the custom post meta.
	 * @return bool|string
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

}