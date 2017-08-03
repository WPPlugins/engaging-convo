<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://engagingconvo.com/
 * @since      1.0.0
 *
 * @package    Enco
 * @subpackage Enco/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Enco
 * @subpackage Enco/includes
 * @author     Lazhar Ichir <hello@lazharichir.com>
 */
class Enco {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Enco_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'enco';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Enco_Loader. Orchestrates the hooks of the plugin.
	 * - Enco_i18n. Defines internationalization functionality.
	 * - Enco_Admin. Defines all hooks for the admin area.
	 * - Enco_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The functions and helpers.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions.helpers.php';

		/**
		 * The licensing stuff.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-license-manager.php';

		/**
		 * The classes responsible for registering the plugin's custom post types.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thread-cpt.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-post-manager.php';

		/**
		 * Engaging Comments' core classes.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-collection.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-document.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thread.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thread-collection.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-comment.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-comment-collection.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-promotional.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-settings-api.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-settings-page.php';
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-enco-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-enco-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-enco-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-enco-public.php';

		$this->loader = new Enco_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Enco_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Enco_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Enco_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'init', $plugin_admin, 'custom_post_types' );
		$this->loader->add_action( 'init', $plugin_admin, 'enco_settings' );

		$this->loader->add_action( 'edit_post', new Enco_Document(), 'post_updated', 10, 2 );
		$this->loader->add_action( 'wp_trash_post', new Enco_Thread(), 'thread_being_deleted' );
		$this->loader->add_action( 'before_delete_post', new Enco_Thread(), 'thread_being_deleted' );
		
		//
		// AJAX HOOKS
		//

		$this->loader->add_action( 'wp_ajax_delete_comment', new Enco_Comment(), 'ajax_delete' );
		$this->loader->add_action( 'wp_ajax_thread_reply', new Enco_Comment(), 'ajax_thread_reply' );
		$this->loader->add_action( 'wp_ajax_set_thread', new Enco_Comment(), 'ajax_set_thread' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Enco_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		
		$this->loader->add_filter( 'wp_footer', new Enco_Document(), 'enco_footer' );
		$this->loader->add_filter( 'the_content', $plugin_public, 'enco_the_content', 99, 5 );
		$this->loader->add_action( 'comments_template', $plugin_public, 'comments_template' );

		//
		// AJAX HOOKS
		//

		$this->loader->add_action( 'wp_ajax_start_new_thread', new Enco_Thread(), 'ajax_new_thread' );
		$this->loader->add_action( 'wp_ajax_nopriv_start_new_thread', new Enco_Thread(), 'ajax_new_thread' );

		$this->loader->add_action( 'wp_ajax_thread_reply', new Enco_Comment(), 'ajax_thread_reply' );
		$this->loader->add_action( 'wp_ajax_nopriv_thread_reply', new Enco_Comment(), 'ajax_thread_reply' );
		
		$this->loader->add_action( 'wp_ajax_delete_comment', new Enco_Comment(), 'ajax_delete' );
		$this->loader->add_action( 'wp_ajax_nopriv_delete_comment', new Enco_Comment(), 'ajax_delete' );
		
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Enco_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
