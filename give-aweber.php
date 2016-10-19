<?php
/**
 * Plugin Name: Give - AWeber
 * Plugin URL: https://givewp.com/addons/aweber/
 * Description: Easily integrate AWeber opt-ins within your Give donation forms.
 * Version: 1.0
 * Author: WordImpress
 * Author URI: https://wordimpress.com
 * Text Domain: give-aweber
 */

//Constants
if ( ! defined( 'GIVE_AWEBER_VERSION' ) ) {
	define( 'GIVE_AWEBER_VERSION', '1.0' );
}
if ( ! defined( 'GIVE_AWEBER_STORE_API_URL' ) ) {
	define( 'GIVE_AWEBER_STORE_API_URL', 'https://givewp.com' );
}

if ( ! defined( 'GIVE_AWEBER_PRODUCT_NAME' ) ) {
	define( 'GIVE_AWEBER_PRODUCT_NAME', 'Aweber' );
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
 * Give - Aweber Add-on Licensing
 */
function give_add_aweber_licensing() {

	if ( class_exists( 'Give_License' ) ) {
		new Give_License( __FILE__, GIVE_AWEBER_PRODUCT_NAME, GIVE_AWEBER_VERSION, 'Devin Walker' );
	}

}

add_action( 'plugins_loaded', 'give_add_aweber_licensing' );


/**
 * Give Aweber Includes
 */
function give_aweber_includes() {

	include( GIVE_AWEBER_PATH . '/includes/class-give-aweber.php' );

	new Give_Aweber( 'aweber', 'AWeber' );

}

add_action( 'plugins_loaded', 'give_aweber_includes' );

