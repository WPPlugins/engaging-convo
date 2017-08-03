<?php
/**
 * Class used to incentivize users of the free plugin to upgrade.
 *
 * @package    enco
 * @subpackage includes
 * @author     Lazhar Ichir
 */

/**
 * Enco_Promotional class.
 *
 * @since  1.0.0
 * @access public
 */
class Enco_Promotional {

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function __construct() {

		add_action( 'enco_form_below_enco_options', array( $this, 'show_as_screenshot' ) );
		
	}

	//////
	//
	// LET'S DO THIS!
	//
	//////

	function show_as_screenshot() {

		if( ! class_exists('Enco_Advanced_Settings') ) {

			?>
			
			<div class="enco-settings-promo-1">
				<h3>Upgrade for more customizations and advanced settings!</h3>
				<ul>
					<li>Advanced settings</li>
					<li>Change colours and backgrounds</li>
					<li>Create your own reactions, like and dislike buttons</li>
					<li>Allow comment authors to receive email notifications on replies</li>
					<li>...and more!</li>
				</ul>
				<p>
					<a href="http://engagingconvo.com/" target="_blank" class="promo-btn-1">Upgrade Now</a>
				</p>
				<a href="http://engagingconvo.com/" target="_blank" style="display: block;">
					<img src="<?php echo ENCO_ASSETS_URL; ?>images/promotional-as.png" alt="Upgrade for more goodness!" style="max-width: 900px; padding: 0 10px; opacity: .3;">
				</a>
			</div>

			<?php

		}
	}

}