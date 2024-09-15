<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Submenu' ) ) :

	final class WPSC_SF_Submenu {

		/**
		 * Initialize the class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wpsc_before_setting_admin_menu', array( __CLASS__, 'load_admin_menu' ) );
			add_action( 'wp_ajax_wpsc_get_all_feedback', array( __CLASS__, 'get_all_feedback' ) );
		}

		/**
		 * Load admin submenu
		 *
		 * @return void
		 */
		public static function load_admin_menu() {

			add_submenu_page(
				'wpsc-tickets',
				esc_attr__( 'Customer Feedback', 'wpsc-sf' ),
				esc_attr__( 'Customer Feedback', 'wpsc-sf' ),
				'manage_options',
				'wpsc-customer-feedback',
				array( __CLASS__, 'layout' )
			);
		}

		/**
		 * Customer feedback admin submenu layout
		 *
		 * @return void
		 */
		public static function layout() {?>
			<div class="wrap">
				<hr class="wp-header-end">
				<div id="wpsc-container">
					<div class="wpsc-setting-header">
						<h2><?php esc_attr_e( 'Customer Feedback', 'wpsc-sf' ); ?></h2>
					</div>

					<div class="wpsc-setting-section-body">
						<div class="wpsc-filter-container">
							<div class="wpsc-filter-item">
								<select id="wpsc-input-sort-feedback" class="wpsc-input-sort-feedback" name="sort-feedback">
									<option value="0"><?php esc_attr_e( 'All ratings', 'wpsc-sf' ); ?></option>
									<?php
									$ratings = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
									foreach ( $ratings as $rating ) {
										?>
										<option value="<?php echo esc_attr( $rating->id ); ?>"><?php echo esc_attr( $rating->name ); ?></option>
										<?php
									}
									?>
								</select>
							</div>
						</div>

						<table class="wpsc-sf-feedback wpsc-setting-tbl">
							<thead>
								<tr>
									<th><?php esc_attr_e( 'Ticket', 'supportcandy' ); ?></th>
									<th><?php echo esc_attr( wpsc__( 'Customer', 'supportcandy' ) ); ?></th>
									<th><?php esc_attr_e( 'Rating', 'wpsc-sf' ); ?></th>
									<th><?php esc_attr_e( 'Feedback', 'wpsc-sf' ); ?></th>
									<th><?php echo esc_attr( wpsc__( 'Date' ) ); ?></th>
								</tr>
							</thead>
						</table>

					</div>

					<script>
						jQuery(document).ready(function() {

							jQuery('#wpsc-input-sort-feedback').on('change', function() {

								jQuery('.wpsc-sf-feedback').dataTable({
									processing: true,
									serverSide: true,
									serverMethod: 'post',
									ajax: { 
										url: supportcandy.ajax_url + '?action=wpsc_get_all_feedback',
										data: { 'sid': this.value, '_ajax_nonce': '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_all_feedback' ) ); ?>' }
									},
									'columns': [
										{ data: 'ticket' },
										{ data: 'customer' },
										{ data: 'rating' },
										{ data: 'feedback' },
										{ data: 'date' },
									],
									'bDestroy': true,
									'ordering': false,
									'searching': false,
									'bLengthChange': false,
									'pageLength': 20,
									columnDefs: [ 
										{ targets: '_all', className: 'dt-left' },
										{ targets: 3, width: 500 }
									],
									language: supportcandy.translations.datatables
								});
							});

							jQuery('#wpsc-input-sort-feedback').change();

						});
					</script>
				</div>
			</div>
			<?php
		}

		/**
		 * Undocumented function
		 *
		 * @return void
		 */
		public static function get_all_feedback() {

			if ( check_ajax_referer( 'wpsc_get_all_feedback', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$draw       = isset( $_POST['draw'] ) ? intval( $_POST['draw'] ) : 1;
			$start      = isset( $_POST['start'] ) ? intval( $_POST['start'] ) : 1;
			$rowperpage = isset( $_POST['length'] ) ? intval( $_POST['length'] ) : 20;
			$page_no    = ( $start / $rowperpage ) + 1;
			$sid        = isset( $_POST['sid'] ) ? intval( $_POST['sid'] ) : 0;

			$ratings    = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
			$rating_ids = array();
			if ( $sid ) {
				$rating_ids = array( $sid );
			} else {
				foreach ( $ratings as $rating ) {
					$rating_ids[] = $rating->id;
				}
			}

			// tickets.
			$response = WPSC_Ticket::find(
				array(
					'parent-filter'  => 'all',
					'orderby'        => 'sf_date',
					'order'          => 'DESC',
					'page_no'        => $page_no,
					'items_per_page' => $rowperpage,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'rating',
							'compare' => 'IN',
							'val'     => $rating_ids,
						),
					),
				)
			);

			$tickets = $response['results'];

			$data = array();
			foreach ( $tickets as $ticket ) {

				$rating = '<span class="wpsc-tag" style="color:' . $ticket->rating->color . ';background-color:' . $ticket->rating->bg_color . ';">' . $ticket->rating->name . '</span>';

				$date_str = '';
				if ( $ticket->sf_date && $ticket->sf_date != '0000-00-00 00:00:00' ) {
					$tz   = wp_timezone();
					$date = $ticket->sf_date;
					$date->setTimezone( $tz );
					$date_str = $date->format( 'Y-m-d H:i:s' );
				}

				$url       = admin_url( 'admin.php?page=wpsc-tickets&section=ticket-list&id=' . $ticket->id );
				$ticket_id = '<a class="wpsc-link" href="' . $url . '" target="__blank">#' . $ticket->id . '</a>';

				$data[] = array(
					'ticket'   => $ticket_id,
					'customer' => stripslashes( $ticket->customer->name ),
					'rating'   => $rating,
					'feedback' => nl2br( stripslashes( $ticket->sf_feedback ) ),
					'date'     => $date_str,
				);
			}

			$response = array(
				'draw'                 => intval( $draw ),
				'iTotalRecords'        => $response['total_items'],
				'iTotalDisplayRecords' => $response['total_items'],
				'data'                 => $data,
			);

			wp_send_json( $response );
		}
	}
endif;

WPSC_SF_Submenu::init();
