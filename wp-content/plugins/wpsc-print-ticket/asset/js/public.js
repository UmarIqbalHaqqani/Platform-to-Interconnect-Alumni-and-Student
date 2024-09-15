/**
 * Print ticket
 */
function wpsc_print_ticket(ticket_id, nonce) {

	window.open( supportcandy.home_url + '?action=wpsc_downlaod_pdf&ticket_id=' + ticket_id + '&_ajax_nonce=' + nonce );
}
