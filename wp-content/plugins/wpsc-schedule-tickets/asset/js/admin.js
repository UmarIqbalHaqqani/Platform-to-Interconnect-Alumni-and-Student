/**
 * Load schedule ticket settings
 */
function wpsc_get_st_settings(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.st, .wpsc-humbargar-menu-item.st' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.st );

	if (supportcandy.current_section !== 'st') {
		supportcandy.current_section = 'st';
		supportcandy.current_tab     = 'general';
	}

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = {
		action: 'wpsc_get_st_settings',
		tab: supportcandy.current_tab
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-body' ).html( response );
			wpsc_reset_responsive_style();
			jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).trigger( "click" );
		}
	);
}

/**
 * Load general tab ui
 */
function wpsc_st_get_general_settings() {

	supportcandy.current_tab = 'general';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_st_get_general_settings' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Set general settings
 */
function wpsc_st_set_general_settings(el) {

	var form     = jQuery( '.wpsc-st-general-settings' )[0];
	var dataform = new FormData( form );
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
		function (res) {
			wpsc_st_get_general_settings();
		}
	);
}

/**
 * Set general settings
 */
function wpsc_st_reset_general_settings(el, nonce) {

	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = { action: 'wpsc_st_reset_general_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_st_get_general_settings();
		}
	);
}

/**
 * Get CRUD listing
 */
function wpsc_st_get_crud_settings() {

	supportcandy.current_tab = 'crud-settings';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_st_get_crud_settings' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Set add new st rule
 */
function wpsc_st_set_add_rule(el) {

	var form     = jQuery( '.wpsc-st-add-rule' )[0];
	var dataform = new FormData( form );

	var title = dataform.get( 'title' );
	if ( ! title ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}

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
		function (res) {
			wpsc_close_modal();
			wpsc_st_get_edit_rule( res.index, res.nonce );
		}
	);
}

/**
 * Get edit st rule
 */
function wpsc_st_get_edit_rule(id, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_st_get_edit_rule', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
		}
	);
}

/**
 * Set edit st rule
 */
function wpsc_st_set_edit_rule(el) {

	var form     = jQuery( '.frm-add-new-st' )[0];
	var dataform = new FormData( form );

	if ( ! (dataform.get( 'title' ) && dataform.get( 'customer' ) && dataform.get( 'subject' )) ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}

	var is_tinymce  = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();
	var description = is_tinymce && tinymce.get( 'description' ) ? tinyMCE.get( 'description' ).getContent() : jQuery( '#description' ).val().trim();
	if ( ! description ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}

	dataform.append( 'description', description );
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
		function (res) {
			wpsc_st_get_crud_settings();
		}
	);
}

/**
 * Delete rule
 */
function wpsc_st_delete_rule(id, nonce) {

	var flag = confirm( supportcandy.translations.confirm );
	if ( ! flag) {
		return;
	}

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_st_delete_rule', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_st_get_crud_settings();
		}
	);
}
