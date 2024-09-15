/**
 * Get change status modal UI
 */
function wpsc_add_new_pc(ticket_id, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_add_new_pc',
		ticket_id,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set edit PC widget
 */
function wpsc_set_new_pc(el, ticket_id) {

	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );

	var form     = jQuery( '.wpsc-frm-add-new-pc' )[0];
	var dataform = new FormData( form );
	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			wpsc_close_modal();
			wpsc_get_individual_ticket( ticket_id );
		}
	);
}

/**
 * Add form textfield item
 */
function wpsc_add_textfield() {

	jQuery( '.wpsc-form-filter-container' ).append( jQuery( '.wpsc-form-textfield-snippet' ).html() );
	jQuery( '.wpsc-form-filter-container' ).last().find( '.cf' ).selectWoo();
}

/**
 * Add form textarea item
 */
function wpsc_add_textarea() {

	jQuery( '.wpsc-form-filter-container' ).append( jQuery( '.wpsc-form-textarea-snippet' ).html() );
	jQuery( '.wpsc-form-filter-container' ).last().find( '.cf' ).selectWoo();
}

/**
 * View Private Credential
 */
function wpsc_view_pc_data(ticket_id, key, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_view_pc_data',
		ticket_id,
		key,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Edit Private Credential
 */
function wpsc_edit_pc_data(ticket_id, key, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_edit_pc_data',
		ticket_id,
		key,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set edit PC data
 */
function wpsc_set_edit_pc_data(el, ticket_id) {

	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );

	var form     = jQuery( '.wpsc-frm-add-edited-pc' )[0];
	var dataform = new FormData( form );
	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			wpsc_close_modal();
			wpsc_get_individual_ticket( ticket_id );
		}
	);
}

/**
 * Delete Private Credential
 */
function wpsc_delete_pc_data(ticket_id, key, nonce) {

	var flag = confirm( supportcandy.translations.confirm );
	if ( ! flag) {
		return;
	}

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_delete_pc_data', ticket_id, key, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_close_modal();
			wpsc_get_individual_ticket( ticket_id );
		}
	);
}
