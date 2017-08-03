<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://engagingconvo.com/
 * @since      1.0.0
 *
 * @package    Enco
 * @subpackage Enco/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Enco
 * @subpackage Enco/public
 * @author     Lazhar Ichir <hello@lazharichir.com>
 */
class Enco_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The document instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Enco_Document    $document    The current document.
	 */
	public $document;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->document = new Enco_Document();

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, ENCO_ASSETS_URL . 'css/enco-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, ENCO_ASSETS_URL . 'js/enco-public.js', array( 'jquery' ), $this->version, true );

		$wp_localize_array = array( 
			'ajax_url' 			=> admin_url( 'admin-ajax.php' ),
			'subject_min_words' => enco_subject_min_words(),
			'subject_max_words' => enco_subject_max_words(),
			'too_many_threads'	=> max_threads( get_the_ID() )
		);
		$wp_localize_array = apply_filters( 'enco_wp_localize_array', $wp_localize_array );

		wp_localize_script( $this->plugin_name, 'ajax_object', $wp_localize_array );
	}

	/**
	 * Parses The Content
	 *
	 * @since    1.0.0
	 */
	public function enco_the_content( $content ) {
		
		$allowed_post_types = enco_linked_post_types();

		if( is_singular() && is_array( $allowed_post_types ) && in_array( get_post_type(), $allowed_post_types ) ) {
				$this->document->load_from_post( get_the_ID() );
				$content = $this->document->contentify( $content );
		}

		return apply_filters( 'enco_the_content', $content );
	}

	public function comments_template( $file ) {

		do_action( 'enco_before_comments_template' );

		$this->document->output_html();

		do_action( 'enco_after_comments_template' );

		return plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/enco-public-display.php';
	}

	public function enco_custom_css() {

		echo '';
	}

}