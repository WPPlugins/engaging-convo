<?php
/**
 * License handler for Engaging Convo.
 *
 * This class should simplify the process of adding license information
 * to new Engaging Convo extensions.
 *
 * The core plugin is free and has no licensing itself. This class
 * is only for future extensions and add-ons.
 *
 * @version 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Enco_License' ) ) :

/**
 * Enco_License Class
 */
class Enco_License {

	private $file;
	private $license;
	private $item_name;
	private $item_id;
	private $item_shortname;
	private $version;
	private $author;
	private $option_name = 'enco_licenses';
	private $api_url = 'http://engagingconvo.com/';

	/**
	 * Class constructor
	 *
	 * @param string  $_file
	 * @param string  $_item_name
	 * @param string  $_version
	 * @param string  $_author
	 * @param string  $_optname
	 * @param string  $_api_url
	 */
	function __construct( $_file, $_item, $_version, $_author, $_optname = null, $_api_url = null ) {

		$this->file           = $_file;

		if( is_numeric( $_item ) ) {
			$this->item_id    = absint( $_item );
		} else {
			$this->item_name  = $_item;
		}

		$this->item_shortname = 'enco_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
		$this->version        = $_version;
		$this->license        = trim( enco_license_option( $this->item_shortname . '_license_key', '' ) );
		$this->author         = $_author;
		$this->api_url        = is_null( $_api_url ) ? $this->api_url : $_api_url;

		// Setup hooks
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include the updater class
	 *
	 * @access  private
	 * @return  void
	 */
	private function includes() {
		if ( ! class_exists( 'Enco_EDD_SL_Plugin_Updater' ) )  {
			require_once 'class-edd-sl-plugin-updater.php';
		}
	}

	/**
	 * Requests a check after form submitted
	 *
	 * @since	1.0
	 */

	public function pre_update_options() {

		add_filter( 'update_option_enco_licenses', array( $this, 'activate_license' ), 10, 2 );
		add_filter( 'update_option_enco_licenses', array( $this, 'deactivate_license' ), 10, 2 );
	}

	/**
	 * Setup hooks
	 *
	 * @access  private
	 * @return  void
	 */
	private function hooks() {

		// Register settings
		add_filter( 'enco_settings_fields', array( $this, 'settings' ) );

		// Display help text at the top of the Licenses tab
		//add_action( 'edd_settings_tab_top', array( $this, 'license_help_text' ) );

		// Licenses updated?
		add_action( 'updated_option', array( $this, 'activate_license' ), 10, 3 );
		//add_action( 'updated_option', array( $this, 'deactivate_license' ), 10, 3 );

		// Activate license key on settings save
		//add_action( 'admin_init', array( $this, 'activate_license' ) );

		// Deactivate license key
		//add_action( 'admin_init', array( $this, 'deactivate_license' ) );

		// Check that license is valid once per week
		add_action( 'edd_weekly_scheduled_events', array( $this, 'weekly_license_check' ) );

		// For testing license notices, uncomment this line to force checks on every page load
		//add_action( 'admin_init', array( $this, 'weekly_license_check' ) );

		// Updater
		add_action( 'admin_init', array( $this, 'auto_updater' ), 0 );

		// Display notices to admins
		add_action( 'admin_notices', array( $this, 'notices' ) );

		add_action( 'in_plugin_update_message-' . plugin_basename( $this->file ), array( $this, 'plugin_row_license_missing' ), 10, 2 );

	}

	/**
	 * Auto updater
	 *
	 * @access  private
	 * @return  void
	 */
	public function auto_updater() {

		$args = array(
			'version'   => $this->version,
			'license'   => $this->license,
			'author'    => $this->author
		);

		if( ! empty( $this->item_id ) ) {
			$args['item_id']   = $this->item_id;
		} else {
			$args['item_name'] = $this->item_name;
		}

		// Setup the updater
		$edd_updater = new Enco_EDD_SL_Plugin_Updater(
			$this->api_url,
			$this->file,
			$args
		);
	}


	/**
	 * Add license field to settings
	 *
	 * @access  public
	 * @param array   $settings
	 * @return  array
	 */
	public function settings( $settings_fields ) {
		
		$settings_fields['enco_licenses'][] =  array(
            'name'    => $this->item_shortname . '_license_key',
            'label'   => sprintf( __( '%1$s License Key', 'enco' ), $this->item_name ),
            'desc'    => '',
            'type'    => 'license',
            'options' => array( 'is_valid_license_option' => $this->item_shortname . '_license_active' )
		);

		return $settings_fields;
	}


	/**
	 * Display help text at the top of the Licenses tag
	 *
	 * @access  public
	 * @since   2.5
	 * @param   string   $active_tab
	 * @return  void
	 */
	public function license_help_text( $active_tab = '' ) {

		static $has_ran;

		if( 'licenses' !== $active_tab ) {
			return;
		}

		if( ! empty( $has_ran ) ) {
			return;
		}

		echo '<p>' . sprintf(
			__( 'Enter your extension license keys here to receive updates for purchased extensions. If your license key has expired, please <a href="%s" target="_blank" title="License renewal FAQ">renew your license</a>.', 'enco' ),
			'http://docs.easydigitaldownloads.com/article/1000-license-renewal'
		) . '</p>';

		$has_ran = true;

	}


	/**
	 * Activate the license key
	 *
	 * @access  public
	 * @return  void
	 */
	public function activate_license( $option_name, $old_value, $new_value ) {
	
		if( $option_name != 'enco_licenses' )
			return;

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $new_value[ $this->item_shortname . '_license_key'] ) ) {

			delete_option( $this->item_shortname . '_license_active' );
			return;
		}

		foreach ( $new_value as $key => $value ) {
			if( false !== strpos( $key, 'license_key_deactivate' ) ) {
				// Don't activate a key when deactivating a different key
				return;	
			}
		}

		$details = enco_license_option( $this->item_shortname . '_license_active' );

		if ( is_object( $details ) && 'valid' === $details->license ) {
			return;
		}

		$license = sanitize_text_field( $_POST['enco_licenses'][ $this->item_shortname . '_license_key'] );

		if( empty( $license ) ) {
			return;
		}

		// Data to send to the API
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( $this->item_name ),
			'url'        => home_url()
		);

		// Call the API
		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			)
		);

		// Make sure there are no errors
		if ( is_wp_error( $response ) ) {
			return;
		}

		// Tell WordPress to look for updates
		set_site_transient( 'update_plugins', null );

		// Decode license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		$new_value[$this->item_shortname . '_license_active'] = $license_data;

		remove_filter( 'updated_option', array( $this, 'activate_license' ) );
		update_option( 'enco_licenses', $new_value );
		add_filter( 'updated_option', array( $this, 'activate_license' ), 10, 3 );
	}


	/**
	 * Deactivate the license key
	 *
	 * @access  public
	 * @return  void
	 */
	public function deactivate_license( $option_name, $old_value, $new_value ) {

		if( ! isset( $_POST['option_page'] ) || $_POST['option_page'] != 'enco_licenses' )
			return;

		if ( ! isset( $_POST['enco_licenses'][ $this->item_shortname . '_license_key'] ) )
			return;

		if( ! wp_verify_nonce( $_REQUEST[ $this->item_shortname . '_license_key-nonce'], $this->item_shortname . '_license_key-nonce' ) ) {

			wp_die( __( 'Nonce verification failed', 'enco' ), __( 'Error', 'enco' ), array( 'response' => 403 ) );

		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Run on deactivate button press
		if ( isset( $_POST[ $this->item_shortname . '_license_key_deactivate' ] ) ) {

			// Data to send to the API
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $this->license,
				'item_name'  => urlencode( $this->item_name ),
				'url'        => home_url()
			);

			// Call the API
			$response = wp_remote_post(
				$this->api_url,
				array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $api_params
				)
			);

			// Make sure there are no errors
			if ( is_wp_error( $response ) ) {
				return;
			}

			// Decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			delete_option( $this->item_shortname . '_license_active' );

		}
	}


	/**
	 * Check if license key is valid once per week
	 *
	 * @access  public
	 * @since   2.5
	 * @return  void
	 */

	public function weekly_license_check() {

		if( ! empty( $_POST['option_page'] ) || $_POST['option_page'] != 'enco_licenses' ) {
			return; // Don't fire when saving settings
		}

		if( empty( $this->license ) ) {
			return;
		}

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'check_license',
			'license' 	=> $this->license,
			'item_name' => urlencode( $this->item_name ),
			'url'       => home_url()
		);

		// Call the API
		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		save_license_option( $this->item_shortname . '_license_active', $license_data );

	}


	/**
	 * Admin notices for errors
	 *
	 * @access  public
	 * @return  void
	 */
	public function notices() {

		static $showed_invalid_message;

		if( empty( $this->license ) ) {
			return;
		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$messages = array();

		$license = enco_license_option( $this->item_shortname . '_license_active' );

		if( is_object( $license ) && 'valid' !== $license->license && empty( $showed_invalid_message ) ) {

			$messages[] = sprintf(
				__( 'You have invalid or expired license keys for Engaging Convo. Please go to the <a href="%s" title="Go to Licenses page">Licenses page</a> to correct this issue.', 'enco' ),
				admin_url( 'options-general.php?page=enco_settings' )
			);

			$showed_invalid_message = true;

		}

		if( ! empty( $messages ) ) {

			foreach( $messages as $message ) {

				echo '<div class="error">';
					echo '<p>' . $message . '</p>';
				echo '</div>';

			}
		}
	}

	/**
	 * Displays message inline on plugin row that the license key is missing
	 *
	 * @access  public
	 * @since   2.5
	 * @return  void
	 */
	public function plugin_row_license_missing( $plugin_data, $version_info ) {

		static $showed_imissing_key_message;

		$license = enco_license_option( $this->item_shortname . '_license_active' );

		if( ( ! is_object( $license ) || 'valid' !== $license->license ) && empty( $showed_imissing_key_message[ $this->item_shortname ] ) ) {

			echo '&nbsp;<strong><a href="' . esc_url( admin_url( 'options-general.php?page=enco_settings' ) ) . '">' . __( 'Enter valid license key for automatic updates.', 'enco' ) . '</a></strong>';
			$showed_imissing_key_message[ $this->item_shortname ] = true;
		}

	}

}

endif; // end class_exists check

/**
 * Save license options.
 *
 * @since	1.0
 */

function save_license_option( $key, $value ) {

	$options = enco_license_options();
	$options[$key] = $value;

	remove_filter( 'updated_option', array( $this, 'activate_license' ) );

	$res = update_option( 'enco_licenses', $options );

	add_filter( 'updated_option', array( $this, 'activate_license' ), 10, 3 );

	return $res;
}	

/**
 * Retrieve all licenses options.
 *
 * @since	1.0
 */

function enco_license_options() {

	$options = get_option( 'enco_licenses' );

	if( empty( $options ) )
		$options = array();

	return $options;
}

/**
 * Retrieve one license option.
 *
 * @since	1.0
 */

function enco_license_option( $key ) {
	
	$o = enco_license_options();
	if( array_key_exists( $key, $o ) ) {
		return $o[$key];
	} else {
		return false;
	}	
}
