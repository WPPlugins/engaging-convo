<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://engagingconvo.com/
 * @since      1.0.0
 *
 * @package    Enco
 * @subpackage Enco/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Enco
 * @subpackage Enco/admin
 * @author     Lazhar Ichir <hello@lazharichir.com>
 */
class Enco_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

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
	 * @access  public
	 * @since   1.0.0
	 * @param   string    $plugin_name	The name of this plugin.
	 * @param   string    $version 		The version of this plugin.
	 * @return  void
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, ENCO_ASSETS_URL . 'css/enco-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, ENCO_ASSETS_URL . 'js/enco-public.js?ver=1.0.0', array( 'jquery' ), $this->version, false );
		
		$wp_localize_array = array( 
			'ajax_url' 			=> admin_url( 'admin-ajax.php' ),
			'subject_min_words' => enco_subject_min_words(),
			'subject_max_words' => enco_subject_max_words()
		);
		
		$wp_localize_array = apply_filters( 'enco_wp_localize_array', $wp_localize_array );

		wp_localize_script( $this->plugin_name, 'ajax_object', $wp_localize_array );

	}

	/**
	 * Load custom post types and past manager (meta boxes.)
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function custom_post_types() {

		$Enco_Thread_Cpt 	= new Enco_Thread_Cpt();
		$Enco_Post_Manager 	= new Enco_Post_Manager();
		$Enco_Promotional 	= new Enco_Promotional();
	}

	/**
	 * Creates the options page
	 *
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function enco_settings() {
		
		$settings = new Enco_Settings_Page();

	}

	/**
	 * Load our main JS file with the tinymce
	 * events and functions.
	 *
	 * @since    1.0.0
	 */
	public function enco_tinymce( $init ) {

	    $init['enco_tinymce'] = ENCO_ASSETS_URL . 'js/enco-admin.js';
	    return $init;
	}



}
