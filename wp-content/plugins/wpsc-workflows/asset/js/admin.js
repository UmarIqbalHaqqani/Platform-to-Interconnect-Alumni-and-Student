/**
 * Load workflows section
 */
function wpsc_get_wf_settings(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery('.wpsc-setting-nav, .wpsc-humbargar-menu-item').removeClass('active');
	jQuery('.wpsc-setting-nav.workflows, .wpsc-humbargar-menu-item.workflows').addClass('active');
	jQuery('.wpsc-humbargar-title').html(supportcandy.humbargar_titles.workflows);

	if (supportcandy.current_section !== 'workflows') {
		supportcandy.current_section = 'workflows';
		supportcandy.current_tab = 'automatic';
	}

	window.history.replaceState({}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab);
	jQuery('.wpsc-setting-body').html(supportcandy.loader_html);

	wpsc_scroll_top();

	var data = {
		action: 'wpsc_get_wf_settings',
		tab: supportcandy.current_tab
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery('.wpsc-setting-body').html(response);
			wpsc_reset_responsive_style();
			jQuery('.wpsc-setting-tab-container button.' + supportcandy.current_tab).trigger("click");
		}
	);
}

/**
 * Load automatic tab ui
 */
function wpsc_wf_get_automatic_settings() {

	supportcandy.current_tab = 'automatic';
	jQuery('.wpsc-setting-tab-container button').removeClass('active');
	jQuery('.wpsc-setting-tab-container button.' + supportcandy.current_tab).addClass('active');

	window.history.replaceState({}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab);
	jQuery('.wpsc-setting-section-body').html(supportcandy.loader_html);

	wpsc_scroll_top();

	var data = { action: 'wpsc_wf_get_automatic_settings' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery('.wpsc-setting-section-body').html(response);
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Load manual tab ui
 */
function wpsc_wf_get_manual_settings() {

	supportcandy.current_tab = 'manual';
	jQuery('.wpsc-setting-tab-container button').removeClass('active');
	jQuery('.wpsc-setting-tab-container button.' + supportcandy.current_tab).addClass('active');

	window.history.replaceState({}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab);
	jQuery('.wpsc-setting-section-body').html(supportcandy.loader_html);

	wpsc_scroll_top();

	var data = { action: 'wpsc_wf_get_manual_settings' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery('.wpsc-setting-section-body').html(response);
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Set add new automatic workflow
 */
function wpsc_wfa_set_add_workflow(el) {

	var buttonText = jQuery(el).text();
	var form = jQuery('.wpsc-wf-settings')[0];
	var dataform = new FormData(form);
	jQuery(el).text(supportcandy.translations.please_wait);
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
			wpsc_wfa_get_edit_workflow(res.index, res.nonce);
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			alert(res.data);
			jQuery(el).text(buttonText);
		}
	);
}

/**
 * Disable automatic workflow
 */
function wpsc_wfa_disable_workflow(id, nonce) {

	var data = { action: 'wpsc_wfa_disable_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_wf_get_automatic_settings();
		}
	);
}

/**
 * Disable automatic workflow
 */
function wpsc_wfa_enable_workflow(id, nonce) {

	var data = { action: 'wpsc_wfa_enable_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_wf_get_automatic_settings();
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			if (res.data[0]['code'] == 'incomplete') {
				wpsc_wfa_get_edit_workflow(res.data[0]['index'], res.data[0]['nonce']);
			} else {
				alert(res.data[0]['message']);
			}
		}
	);
}

/**
 * Get edit automatic workflow
 */
function wpsc_wfa_get_edit_workflow(id, nonce) {

	jQuery('.wpsc-setting-section-body').html(supportcandy.loader_html);
	var data = { action: 'wpsc_wfa_get_edit_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery('.wpsc-setting-section-body').html(response);
		}
	);
}

/**
 * Set edit automatic workflow
 * @param {*} el 
 */
function wpsc_wfa_set_edit_workflow(el) {

	var buttonText = jQuery(el).text();
	var form = jQuery('.wpsc-wf-settings')[0];
	var dataform = new FormData(form);
	dataform.append('conditions', JSON.stringify(wpsc_get_condition_json('conditions')));
	jQuery(el).text(supportcandy.translations.please_wait);
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
			wpsc_wf_get_automatic_settings();
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			alert(res.data);
			jQuery(el).text(buttonText);
		}
	);
}

/**
 * Get clone workflow
 * @param {*} id 
 * @param {*} nonce 
 */
function wpsc_wfa_get_clone_workflow(id, nonce) {

	wpsc_show_modal();
	var data = { action: 'wpsc_wfa_get_clone_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			// Set to modal.
			jQuery('.wpsc-modal-header').text(response.title);
			jQuery('.wpsc-modal-body').html(response.body);
			jQuery('.wpsc-modal-footer').html(response.footer);
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set clone workflow
 * @param {*} el 
 */
function wpsc_wfa_set_clone_workflow(el) {

	var buttonText = jQuery(el).text();
	var form = jQuery('.wpsc-wf-settings')[0];
	var dataform = new FormData(form);
	jQuery(el).text(supportcandy.translations.please_wait);
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
			wpsc_wfa_get_edit_workflow(res.index, res.nonce);
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			alert(res.data);
			jQuery(el).text(buttonText);
		}
	);
}

/**
 * Delete workflow
 * @param {*} id 
 * @param {*} nonce 
 */
function wpsc_wfa_delete_workflow(id, nonce) {

	var flag = confirm(supportcandy.translations.confirm);
	if (!flag) {
		return;
	}

	var data = { action: 'wpsc_wfa_delete_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_wf_get_automatic_settings();
		}
	);
}

/**
 * Set add new automatic workflow
 */
function wpsc_wfm_set_add_workflow(el) {

	var buttonText = jQuery(el).text();
	var form = jQuery('.wpsc-wf-settings')[0];
	var dataform = new FormData(form);
	jQuery(el).text(supportcandy.translations.please_wait);
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
			wpsc_wfm_get_edit_workflow(res.index, res.nonce);
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			alert(res.data);
			jQuery(el).text(buttonText);
		}
	);
}

/**
 * Get edit automatic workflow
 */
function wpsc_wfm_get_edit_workflow(id, nonce) {

	jQuery('.wpsc-setting-section-body').html(supportcandy.loader_html);
	var data = { action: 'wpsc_wfm_get_edit_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery('.wpsc-setting-section-body').html(response);
		}
	);
}

/**
 * Set edit automatic workflow
 * @param {*} el 
 */
function wpsc_wfm_set_edit_workflow(el) {

	var buttonText = jQuery(el).text();
	var form = jQuery('.wpsc-wf-settings')[0];
	var dataform = new FormData(form);
	dataform.append('conditions', JSON.stringify(wpsc_get_condition_json('conditions')));
	jQuery(el).text(supportcandy.translations.please_wait);
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
			wpsc_wf_get_manual_settings();
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			alert(res.data);
			jQuery(el).text(buttonText);
		}
	);
}

/**
 * Disable automatic workflow
 */
function wpsc_wfm_disable_workflow(id, nonce) {

	var data = { action: 'wpsc_wfm_disable_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_wf_get_manual_settings();
		}
	);
}

/**
 * Disable automatic workflow
 */
function wpsc_wfm_enable_workflow(id, nonce) {

	var data = { action: 'wpsc_wfm_enable_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_wf_get_manual_settings();
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			if (res.data[0]['code'] == 'incomplete') {
				wpsc_wfm_get_edit_workflow(res.data[0]['index'], res.data[0]['nonce']);
			} else {
				alert(res.data[0]['message']);
			}
		}
	);
}

/**
 * Get clone workflow
 * @param {*} id 
 * @param {*} nonce 
 */
function wpsc_wfm_get_clone_workflow(id, nonce) {

	wpsc_show_modal();
	var data = { action: 'wpsc_wfm_get_clone_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			// Set to modal.
			jQuery('.wpsc-modal-header').text(response.title);
			jQuery('.wpsc-modal-body').html(response.body);
			jQuery('.wpsc-modal-footer').html(response.footer);
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set clone workflow
 * @param {*} el 
 */
function wpsc_wfm_set_clone_workflow(el) {

	var buttonText = jQuery(el).text();
	var form = jQuery('.wpsc-wf-settings')[0];
	var dataform = new FormData(form);
	jQuery(el).text(supportcandy.translations.please_wait);
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
			wpsc_wfm_get_edit_workflow(res.index, res.nonce);
		}
	).fail(
		function (xhr) {
			let res = JSON.parse(xhr.responseText);
			alert(res.data);
			jQuery(el).text(buttonText);
		}
	);
}

/**
 * Delete workflow
 * @param {*} id 
 * @param {*} nonce 
 */
function wpsc_wfm_delete_workflow(id, nonce) {

	var flag = confirm(supportcandy.translations.confirm);
	if (!flag) {
		return;
	}

	var data = { action: 'wpsc_wfm_delete_workflow', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_wf_get_manual_settings();
		}
	);
}

/**
 * Remove action from settings
 * @param {*} el 
 */
function wpsc_wf_remove_action(el) {

	jQuery(el).closest('.wf-action-item').remove();
}

/**
 * Add new action
 */
function wpsc_wf_get_add_new_action(nonce) {

	let actions = jQuery.map(jQuery('.wf-action-item'), function (el) {
		return jQuery(el).data('slug');
	});

	wpsc_show_modal();
	var data = { action: 'wpsc_wf_get_add_new_action', _ajax_nonce: nonce, actions: actions.join(',') };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			// Set to modal.
			jQuery('.wpsc-modal-header').text(response.title);
			jQuery('.wpsc-modal-body').html(response.body);
			jQuery('.wpsc-modal-footer').html(response.footer);
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set add new action
 * @param {*} el 
 */
function wpsc_wf_set_add_new_action(el) {

	var form = jQuery('.wpsc-wf-add-action')[0];
	var dataform = new FormData(form);
	jQuery(el).text(supportcandy.translations.please_wait);
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
			jQuery('.wpsc-action-container .wf-actions').append(res);
		}
	);
}

/**
 * Add new action
 */
function wpsc_wf_cf_get_add_new_field(nonce, field) {

	let fields = jQuery.map(jQuery('.wpsc-input-group.ticket'), function (el) {
		return jQuery(el).data('slug');
	});

	wpsc_show_modal();
	var data = {
		action: 'wpsc_wf_cf_get_add_new_field',
		_ajax_nonce: nonce,
		field,
		fields: fields.join(',')
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			// Set to modal.
			jQuery('.wpsc-modal-header').text(response.title);
			jQuery('.wpsc-modal-body').html(response.body);
			jQuery('.wpsc-modal-footer').html(response.footer);
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set add new action
 * @param {*} el 
 */
function wpsc_wf_cf_set_add_new_field(el, field) {

	var form = jQuery('.wpsc-wf-add-field')[0];
	var dataform = new FormData(form);
	jQuery(el).text(supportcandy.translations.please_wait);
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
			jQuery( '.wf-cf-container.' + field ).append(res);
		}
	);
}

/**
 * Remove custom field
 * @param {*} el 
 */
function wpsc_wf_cf_remove_field(el) {

	jQuery(el).closest('.wpsc-input-group').remove();
}

/**
 * Ticket widget status
 */
function wpsc_get_tw_workflows() {

	wpsc_show_modal();
	var data = { action: 'wpsc_get_tw_workflows' };
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
 * Set add new action
 * @param {*} el 
 */
function wpsc_set_tw_workflows( el ) {

	var form = jQuery('.wpsc-frm-edit-widget-settings')[0];
	var dataform = new FormData(form);

	if ( dataform.get( 'label' ).trim() == '' ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}

	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery(el).text(supportcandy.translations.please_wait);
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
			wpsc_get_ticket_widget();
		}
	);
}
