<?php

/**
 * Fired during plugin activation
 *
 * @link       http://engagingconvo.com/
 * @since      1.0.0
 *
 * @package    Enco
 * @subpackage Enco/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Enco
 * @subpackage Enco/includes
 * @author     Lazhar Ichir <hello@lazharichir.com>
 */
class Enco_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// Create the empty array for our plugin
		$defaults = enco_get_default_options();

		// Create our enco_option settings
		update_option( 'enco_options', $defaults );

	}

}
