/**
 * Trigger workflow from individual ticket
 * @param {*} id 
 * @param {*} ticket_id 
 * @param {*} nonce 
 * @returns 
 */
function wpsc_itw_trigger_workflow( id, ticket_id, nonce ) {

	var flag = confirm(supportcandy.translations.confirm);
	if (!flag) {
		return;
	}

	var data = { action: 'wpsc_itw_trigger_workflow', id, ticket_id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_get_individual_ticket( ticket_id );
			wpsc_run_ajax_background_process();
		}
	);
}