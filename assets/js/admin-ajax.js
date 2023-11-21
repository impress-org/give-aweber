/**
 * Give AWeber Admin Settings JS
 *
 * @description: The Give Admin Settings scripts. Only enqueued on the give-settings page;
 * @package:     Give
 * @since:       1.0
 * @subpackage:  Assets/JS
 * @copyright:   Copyright (c) 2016, GiveWP
 * @license:     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

var give_vars;
jQuery.noConflict();
jQuery(document).ready(function ($) {

	/**
	 * Refresh Lists Button click.
	 */
	$('.give-reset-aweber-button').on('click', function (e) {
		e.preventDefault();

		var field_type = $(this).data('field_type');

		var data = {
				action: $(this).data('action'),
				field_type: field_type,
				post_id: give_vars.post_id
			},
			refresh_button = $(this),
			spinner = $(this).next();

		$.ajax({
			method: 'POST',
			url: ajaxurl,
			data: data,
			beforeSend: function () {
				spinner.addClass('is-active');
			},
			success: function (res) {
				if (true == res.success) {
					//Replace select options.
					if (field_type == 'select') {
						$('.give-aweber-list-select').empty().append(res.data.lists);
					} else {
						$('.give-aweber-list-wrap').empty().append(res.data.lists);
					}

					//refresh_button.hide();
					spinner.removeClass('is-active');
				}
			},
			error: function (res) {
				console.error({aweberListErrorResponse: res});
			}
		});
	});

});
