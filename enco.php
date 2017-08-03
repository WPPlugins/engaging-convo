<?php
/**
 * Engaging Convo -- BOOTSTRAP FILE
 *
 * @link              http://engagingconvo.com/
 * @since             1.0.0
 * @package           Enco
 *
 * @wordpress-plugin
 * Plugin Name:       Engaging Convo
 * Plugin URI:        http://engagingconvo.com/
 * Description:       The best WordPress Comments Plugin with reactions and inline-threads ala Medium.
 * Version:           1.0.1
 * Author:            Lazhar Limited
 * Author URI:        http://engagingconvo.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       enco
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if( ! defined( 'ENCO_FOLDER_URL' ) ) {
	define( 'ENCO_FOLDER_URL', plugin_dir_url( __FILE__ ) );
} // end if

if( ! defined( 'ENCO_ASSETS_URL' ) ) {
	define( 'ENCO_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/' );
} // end if

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-enco-activator.php
 */
function activate_enco() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-enco-activator.php';
	Enco_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-enco-deactivator.php
 */
function deactivate_enco() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-enco-deactivator.php';
	Enco_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_enco' );
register_deactivation_hook( __FILE__, 'deactivate_enco' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-enco.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_enco() {

	$plugin = new Enco();
	$plugin->run();

}
run_enco();
