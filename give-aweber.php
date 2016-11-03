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
		new Give_License( __FILE__, 'AWeber', GIVE_AWEBER_VERSION, 'Devin Walker' );
	}

}

add_action( 'plugins_loaded', 'give_add_aweber_licensing' );


/**
 * Give Aweber Includes.
 */
function give_aweber_includes() {

	include( GIVE_AWEBER_PATH . '/includes/class-give-aweber.php' );

	new Give_Aweber( 'aweber', 'AWeber' );

}

add_action( 'plugins_loaded', 'give_aweber_includes' );


/**
 * Plugins row action links.
 *
 * @since 1.0
 *
 * @param array $actions An array of plugin action links.
 *
 * @return array An array of updated action links.
 */
function give_aweber_plugin_action_links( $actions ) {
	$new_actions = array(
		'settings' => sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=addons' ),
			esc_html__( 'Settings', 'give-aweber' )
		),
	);

	return array_merge( $new_actions, $actions );
}

add_filter( 'plugin_action_links_' . GIVE_AWEBER_BASENAME, 'give_aweber_plugin_action_links' );


/**
 * Plugin row meta links
 *
 * @since 1.0
 *
 * @param array $plugin_meta An array of the plugin's metadata.
 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
 *
 * @return array
 */
function give_aweber_plugin_row_meta( $plugin_meta, $plugin_file ) {
	if ( $plugin_file != GIVE_AWEBER_BASENAME ) {
		return $plugin_meta;
	}

	$new_meta_links = array(
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( add_query_arg( array(
					'utm_source'   => 'plugins-page',
					'utm_medium'   => 'plugin-row',
					'utm_campaign' => 'admin',
				), 'https://givewp.com/documentation/add-ons/aweber/' )
			),
			esc_html__( 'Documentation', 'give-aweber' )
		),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( add_query_arg( array(
					'utm_source'   => 'plugins-page',
					'utm_medium'   => 'plugin-row',
					'utm_campaign' => 'admin',
				), 'https://givewp.com/addons/' )
			),
			esc_html__( 'Add-ons', 'give-aweber' )
		),
	);

	return array_merge( $plugin_meta, $new_meta_links );
}

add_filter( 'plugin_row_meta', 'give_aweber_plugin_row_meta', 10, 2 );