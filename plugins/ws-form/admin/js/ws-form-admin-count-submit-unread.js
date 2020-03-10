(function($) {

	'use strict';

	window.wsf_admin_wp_count_submit_unread_ajax = function() {

		// Initial render
		$.getJSON(ws_form_admin_count_submit_read_settings.count_submit_unread_ajax_url, function(data) {

			if(data) {

				var count_submit_unread_total = (typeof(data.count_submit_unread_total) !== 'undefined') ? data.count_submit_unread_total : 0;

			} else {

				var count_submit_unread_total = 0;
			}

			wsf_admin_wp_count_submit_unread_render(count_submit_unread_total);
		});
	}

	window.wsf_admin_wp_count_submit_unread_render = function(count_submit_unread_total) {

		if(typeof(count_submit_unread_total) === 'undefined') { var count_submit_unread_total = 0; } else { count_submit_unread_total = parseInt(count_submit_unread_total); }

		var count_submit_unread_total_html = (count_submit_unread_total > 0) ? ' <span class="count-' + count_submit_unread_total + '" title="' + count_submit_unread_total + ' new submission' + ((count_submit_unread_total != 1) ? 's' : '') + '"><span class="update-count">' + count_submit_unread_total + '</span></span>' : '';

		$('.wsf-submit-unread-total').html(count_submit_unread_total_html);
	}

	// Initial wsf_admin_wp_count_submit_unread_render
	$(function() {

		window.wsf_admin_wp_count_submit_unread_render(ws_form_admin_count_submit_read_settings.count_submit_unread_total);
	});

})(jQuery);
