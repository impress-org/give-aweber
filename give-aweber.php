<?php
/**
 * Plugin Name: Give - AWeber
 * Plugin URL: https://givewp.com/addons/aweber/
 * Description: Easily integrate AWeber opt-ins within your Give donation forms.
 * Version: 1.0.1
 * Author: WordImpress
 * Author URI: https://wordimpress.com
 * Text Domain: give-aweber
 */

//Define constants.
if ( ! defined( 'GIVE_AWEBER_VERSION' ) ) {
	define( 'GIVE_AWEBER_VERSION', '1.0.1' );
}

if ( ! defined( 'GIVE_AWEBER_PATH' ) ) {
	define( 'GIVE_AWEBER_PATH', dirname( __FILE__ ) );
}

if ( ! defined( 'GIVE_AWEBER_URL' ) ) {
	define( 'GIVE_AWEBER_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'GIVE_AWEBER_DIR' ) ) {
	define( 'GIVE_AWEBER_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'GIVE_AWEBER_BASENAME' ) ) {
	define( 'GIVE_AWEBER_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Give - Aweber Add-on licensing.
 */
function give_add_aweber_licensing() {

	if ( class_exists( 'Give_License' ) ) {
		new Give_License( __FILE__, 'AWeber', GIVE_AWEBER_VERSION, 'WordImpress' );
	}

}

add_action( 'plugins_loaded', 'give_add_aweber_licensing' );


/**
 * Give Aweber Includes.
 */
function give_aweber_includes() {

	include( GIVE_AWEBER_PATH . '/includes/give-aweber-activation.php' );
	include( GIVE_AWEBER_PATH . '/includes/class-give-aweber.php' );

	new Give_Aweber( 'aweber', 'AWeber' );

}

add_action( 'plugins_loaded', 'give_aweber_includes' );