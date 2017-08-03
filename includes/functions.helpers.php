<?php

//////
//
// HELPERS & FUNCTIONS
//
//////

/**
 * Check if we reached the maximum threads
 * allowed as per the plugin settings in a post.
 *
 * Return true if we don't want to accept further threads.
 *
 * @since  1.0.0
 * @return bool
 */
function max_threads( $post_id ) {

	$max = intval( get_enco_max_threads() );

	$args = array(
	   'posts_per_page' => -1,
	   'post_type' => 'enco_thread',
	   'meta_key'  => 'thread_post_id',
	   'meta_value' => $post_id
	);
	$query = new WP_Query($args);

	$count = $query->found_posts;

	if( $max == -1 ) {
		return false;
	} else if ( $max == 0 ) {
		return true;
	} else if ( $count < $max ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Display a post content. Optinally allows post ID to be passed
 * @uses get_the_content()
 *
 * @param int $id Optional. Post ID.
 * @param string $more_link_text Optional. Content for when there is more text.
 * @param bool $stripteaser Optional. Strip teaser content before the more text. Default is false.
 */
function enco_the_content( $post_id = 0, $more_link_text = null, $stripteaser = false ){
    
    global $post;
    $post = get_post($post_id);
    setup_postdata( $post, $more_link_text, $stripteaser );
    $content = get_the_content();
    wp_reset_postdata( $post );
    return apply_filters( 'enco_the_content', $post_id, $content );
}

/**
 * Returns the current user's IP address.
 * @return string
 */
function enco_current_user_ip() {

	$ip = '';

	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	    $ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
	    $ip = $_SERVER['REMOTE_ADDR'];
	}

	return apply_filters( 'enco_current_user_ip', $ip );
}

/**
 * Flatten the code so we can remove useless differences
 * from string before comparing. ie. line breaks and so on.
 * 
 * @return string
 */
function enco_flatten( $content, $nl2br = true ) {

	$original = $content;
	$flattened = $content;

	if( $nl2br )
		$flattened = nl2br( $content );

	$flattened = str_replace( array("\r","\n"), "", $flattened );
	$flattened = str_replace( "<br />", "<br>", $flattened );
	$flattened = htmlspecialchars_decode( $flattened );

	return apply_filters( 'enco_flatten', $flattened, $original );
}

/**
 * Find the position of the Xth occurrence of a substring in a string
 *
 * @param string 	$haystack 	The text to search.
 * @param string 	$needle 	The substring to find.
 * @param int 		$number 	The occurrence to find.
 * @return int
 */
function enco_strposX( $haystack, $needle, $number ){

    if($number == '0'){
        return strpos($haystack, $needle);
    }elseif($number > '0'){
        return strpos($haystack, $needle, enco_strposX($haystack, $needle, $number - 1) + strlen($needle));
    }else{
    	// Error: Value for parameter $number is out of range
        return null;
    }
}

/**
 * Searches and replaces the nth substring occurrence in a text.
 * Returns the subject.
 * 
 * @param  string 	$search 	Substring to find/match.
 * @param  string 	$replace 	String to replace it with
 * @param  string 	$subject 	Body of text to perform the search against.
 * @param  int 		$nth 		Occurrence index (starts at 0).
 * @return string 
 */
function enco_str_replace_nth( $search, $replace, $content, $nth = 0 ) {

    $found = preg_match_all( '#' . preg_quote( $search ) . '#', $content, $matches, PREG_OFFSET_CAPTURE );

    if (false !== $found && $found > $nth) {
        return substr_replace( $content, $replace, $matches[0][$nth][1], strlen( $search ) );
    }

    return $content;
}

/**
 * Returns the number of comments for a given thread.
 * @param  int 	$thread_id  ID of the thread
 * @return int 
 */
function enco_total_thread_comments( $thread_id ) {
	
	$args = array(
	   'meta_key' 	=> 'thread_id',
	   'meta_value' => $thread_id,
	   'count'		=> true
	);

	// The Query
	$query = new WP_Comment_Query;
	$comments = $query->query( $args );

	return intval( $comments );
}

//////
//
// SANITIZATION
//
//////

/**
 * Sanitizes a string key for Enco Settings
 *
 * Keys are used as internal identifiers.
 * Alphanumeric characters, dashes, underscores, stops, colons and slashes are allowed.
 *
 * @since  1.0.0
 * @param  string $key String key
 * @return string Sanitized key
 */
function enco_sanitize_key( $key ) {
	$raw_key = $key;
	$key = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

	/**
	 * Filter a sanitized key string.
	 *
	 * @since 1.0.0
	 * @param string $key     Sanitized key.
	 * @param string $raw_key The key prior to sanitization.
	 */
	return apply_filters( 'enco_sanitize_key', $key, $raw_key );
}

//////
//
// OPTIONS & SETTINGS
//
//////

/**
 * Returns the maximum thread count allowed for a post.
 * @return int
 */
function get_enco_max_threads() {
	
	$o = 2;
	$o = apply_filters( 'enco_max_threads', $o );
	return $o;
}

/**
 * Returns the maximum number of highlighted threads in a post content.
 * @return int
 */
function get_enco_max_highlighted_threads() {
	
	$o = 2;
	$o = apply_filters( 'enco_max_highlighted_threads', $o );
	return $o;
}

/**
 * Returns the minimum word count required in order to start a new thread.
 * @return int
 */
function enco_subject_min_words() {
	
	$o = 4;
	$o = apply_filters( 'enco_subject_min_words', $o );
	return $o;
}

/**
 * Returns the maximum word count required in order to start a new thread.
 * @return int
 */
function enco_subject_max_words() {
	
	$o = 12;
	$o = apply_filters( 'enco_subject_max_words', $o );
	return $o;
}

/**
 * Returns the registered WP post types minus useless ones.
 * @return array
 */
function enco_get_post_types() {

	$types = get_post_types();
	unset($types['nav_menu_item']);
	unset($types['attachment']);
	unset($types['revision']);
	unset($types['enco_thread']);
	$types = apply_filters( 'enco_get_post_types', $types );
	return $types;
}

/**
 * Returns the WP post types Engaging Convo should work with.
 * ie. 'post' only for now
 * @return array
 */
function enco_linked_post_types() {
	
	$allowed = array( 'post' );
	$allowed = apply_filters( 'enco_linked_post_types', $allowed );
	return $allowed;
}

/**
 * Option Wrapper -- Returns whether only logged in users can comment
 * @return int|boolean
 */
function enco_only_logged_in_can_comment() {
	
	$o = get_option('comment_registration');
	$o = apply_filters( 'enco_only_logged_in_can_comment', $o );
	return empty( $o ) ? 0 : 1;
}

/**
 * Option Wrapper -- Returns all of enco_options settings.
 * @param  string 	$section 	The section name to retrieve the options from.
 * @return array
 */
function enco_options( $section = 'enco_options' ) {

	$options = get_option( $section );
	return apply_filters( 'enco_get_options', $options, $section );
}

/**
 * Option Wrapper -- Returns one of enco_options options.
 *
 * @param string $key 		The option name.
 * @param string $section 	The section to get it from.
 * @return mixed
 */
function enco_option( $key, $section = 'enco_options' ) {
	
	$defaults = enco_get_default_options();

	$o = enco_options( $section );
	$o = array_merge( $defaults, $o );

	$value = $o[$key];
	$value = apply_filters( 'enco_get_option', $value, $section, $key );
	return $value;
}

function enco_get_default_options() {

	$defaults = array(
		'max_threads' 				=> '2',
		'max_highlighted_threads' 	=> '2',
		'subject_min_words' 		=> '4',
		'subject_max_words' 		=> '16',
		'highlight_empty_threads' 	=> 'no',
		'highlight_color' 			=> '#B4E7F8',
		'highlight_text_color'		=> '#000000',
		'overlay_bg_color' 			=> '#E0E0E0',
		'border_left_color'			=> '#F3F3F3',
		'allowed_posttypes' 		=> array('post'),
		'show_plugin_credits' 		=> '1'
	);

	return apply_filters( 'enco_get_default_options', $defaults );
}

//////
//
// DEBUGGING
//
//////

/**
 * Quick debug dump function.
 * @param  mixed 	$var 	The variable to dump.
 * @return void
 */
function _dump( $var ) {
	echo '<pre>';
	var_dump( $var );
	echo '</pre>';
}
