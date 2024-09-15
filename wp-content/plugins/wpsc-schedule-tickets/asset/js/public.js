/**
 * Schedule current ticket
 */
function wpsc_it_get_schedule_ticket(ticket_id, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_it_get_schedule_ticket',
		ticket_id,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set schedule ticket from an individual ticket
 */
function wpsc_it_set_schedule_ticket(el) {

	if (wpsc_is_description_text()) {
		if ( ! confirm( supportcandy.translations.warning_message )) {
			return;
		}
	}

	var form     = jQuery( 'form.wpsc-it-schedule' )[0];
	var dataform = new FormData( form );
	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );

	var btnText = jQuery( el ).text().trim();
	jQuery( el ).text( supportcandy.translations.please_wait );

	jQuery.ajax(
		{

			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false

		}
	).done(
		function (response) {

			wpsc_close_modal();

		}
	).fail(
		function (xhr) {

			var response = JSON.parse( xhr.responseText );
			alert( response.data );
			jQuery( el ).text( btnText );
			jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', false );
		}
	);
}
