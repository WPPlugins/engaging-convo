<?php
/**
 * Class for Settings page.
 *
 * @package    Enco
 * @author     Lazhar Ichir
 */

/**
 * Settings page class.
 *
 * @since  1.0.0
 * @access public
 */

if ( !class_exists( 'Enco_Settings_Page' ) ):

class Enco_Settings_Page {

    private $settings_api;

    /**
     * Constructor
     * 
     * @return void
     */
    function __construct() {
                //must check that the user has the required capability 
        if ( is_admin() && current_user_can('manage_options')) { 
            //wp_die( __('You do not have sufficient permissions to access this page.') );
            $this->settings_api = new Enco_Settings_API();
            add_action( 'admin_init', array($this, 'admin_init') );
            add_action( 'admin_menu', array($this, 'admin_menu') );
        }
    }
    
    /**
     * Initialize our Settings API
     * 
     * @return void
     */
    function admin_init() {
        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );
        //initialize settings
        $this->settings_api->admin_init();
    }
    
    /**
     * Add our options page.
     * 
     * @return void 
     */
    function admin_menu() {
        add_options_page( 'Engaging Co. Settings', 'Engaging Conversation', 'delete_posts', 'enco_settings', array($this, 'plugin_page') );
    }
    
    /**
     * Returns the sections as an array.
     * 
     * @return array 
     */
    function get_settings_sections() {
       
        $sections = array(
            array(
                'id' => 'enco_options',
                'title' => __( 'General Settings', 'enco' )
            )
        );

        $sections = apply_filters( 'enco_settings_sections', $sections );

        $sections[] = array(
            'id' => 'enco_licenses',
            'title' => __( 'Licenses', 'enco' )
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {

        $licenses_fields = array();
        $licenses_fields = apply_filters( 'enco_settings_licenses_fields' , $licenses_fields );

        $settings_fields = array(
            'enco_licenses' => $licenses_fields,
            'enco_options' => array(
                array(
                    'name'    => 'show_plugin_credits',
                    'label'   => __( 'Show Credits', 'enco' ),
                    'desc'    => __( 'Help us grow but showing the credits in the overlay (it\'s very discreet).', 'enco' ),
                    'type'    => 'select',
                    'default' => 'yes',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No'
                    )
                ),
            )
        );
        return apply_filters( 'enco_settings_fields', $settings_fields );
    }

    /**
     * Outputs the plugin settings page.
     * 
     * @return void
     */
    function plugin_page() {
        echo '<h1>' . __( 'Engaging Conversation &mdash; Settings', 'enco' ) . '</h1>';
        echo '<div class="wrap">';
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }
        return $pages_options;
    }

}
endif;