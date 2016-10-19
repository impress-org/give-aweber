/*!
 * Give Aweber Admin Forms JS
 *
 * @description: The Give Admin Forms scripts. Only enqueued on the give_forms CPT; used to validate fields, show/hide, and other functions
 * @package:     Give
 * @subpackage:  Assets/JS
 * @copyright:   Copyright (c) 2016, WordImpress
 * @license:     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

jQuery.noConflict();
(function ( $ ) {

	/**
	 * Toggle Conditional Form Fields
	 *
	 *  @since: 1.0
	 */
	var toggle_aweber_fields = function () {


		var aweber_enable_option = $( '.give-aweber-enable' );
		var aweber_disable_option = $( '.give-aweber-disable' );

		aweber_enable_option.on( 'change', function () {

			var aweber_enable_option_val = $(this ).prop('checked');

			if ( aweber_enable_option_val === false ) {
				$( '.give-aweber-field-wrap' ).slideUp('fast');
			} else {
				$( '.give-aweber-field-wrap' ).slideDown('fast');
			}

		} ).change();

		aweber_disable_option.on( 'change', function () {

			var aweber_disable_option_val = $(this ).prop('checked');

			if ( aweber_disable_option_val === false ) {
				$( '.give-aweber-field-wrap' ).slideDown('fast');
			} else {
				$( '.give-aweber-field-wrap' ).slideUp('fast');
			}

		} ).change();

	};


	//On DOM Ready
	$( function () {

		toggle_aweber_fields();

	} );


})( jQuery );