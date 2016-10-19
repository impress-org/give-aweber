/**
 * Give Aweber Admin Forms JS
 *
 * @package:     Give
 * @subpackage:  Assets/JS
 * @copyright:   Copyright (c) 2016, WordImpress
 * @license:     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

jQuery.noConflict();
(function ($) {

	/**
	 * Toggle Conditional Form Fields
	 *
	 *  @since: 1.0
	 */
	var toggle_aweber_fields = function () {

		var aweber_option = $('input[name="_give_aweber_override_option"]');

		aweber_option.on('change', function () {

			var aweber_option_val = $(this).filter(':checked').val();

			if(typeof aweber_option_val == 'undefined') {
				return;
			}
			
			console.log(aweber_option_val);

			if (aweber_option_val === 'disable' || aweber_option_val == 'default') {
				$('.give-aweber-field-wrap').hide();
			} else {
				$('.give-aweber-field-wrap').show();
			}

		}).change();

	};


	//On DOM Ready
	$(function () {

		toggle_aweber_fields();

	});


})(jQuery);