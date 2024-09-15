/**
 * Load email piping section
 */
function wpsc_get_print_settings(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.print_settings, .wpsc-humbargar-menu-item.print_settings' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.print_settings );

	if (supportcandy.current_section !== 'print_settings') {
		supportcandy.current_section = 'print_settings';
		supportcandy.current_tab     = 'general';
	}

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = {
		action: 'wpsc_get_print_settings',
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
function wpsc_pt_get_general_settings() {

	supportcandy.current_tab = 'general';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_pt_get_general_settings' };
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
function wpsc_pt_set_general_settings(el) {

	var form     = jQuery( '.wpsc-pt-general-settings' )[0];
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
			wpsc_pt_get_general_settings();
		}
	);
}

/**
 * Set general settings
 */
function wpsc_pt_reset_general_settings(el, nonce) {

	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = { action: 'wpsc_pt_reset_general_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_pt_get_general_settings();
		}
	);
}

/**
 * Load template tab ui
 */
function wpsc_pt_get_template_settings() {

	supportcandy.current_tab = 'template';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_pt_get_template_settings' };
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
 * Set template settings
 */
function wpsc_pt_set_template_settings(el) {

	var form     = jQuery( '.wpsc-pt-template-settings' )[0];
	var dataform = new FormData( form );

	var is_tinymce = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();
	var header     = is_tinymce && tinymce.get( 'wpsc-print-header' ) ? tinyMCE.get( 'wpsc-print-header' ).getContent() : jQuery( '#wpsc-print-header' ).val().trim();

	var body   = is_tinymce && tinymce.get( 'wpsc-print-body' ) ? tinyMCE.get( 'wpsc-print-body' ).getContent() : jQuery( '#wpsc-print-body' ).val().trim();
	var footer = is_tinymce && tinymce.get( 'wpsc-print-footer' ) ? tinyMCE.get( 'wpsc-print-footer' ).getContent() : jQuery( '#wpsc-print-footer' ).val().trim();

	if ( ! body ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}

	dataform.append( 'header', header );
	dataform.append( 'body', body );
	dataform.append( 'footer', footer );

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
			wpsc_pt_get_template_settings();
		}
	);
}

/**
 * Set general settings
 */
function wpsc_pt_reset_template_settings(el, nonce) {

	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = { action: 'wpsc_pt_reset_template_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_pt_get_template_settings();
		}
	);
}
