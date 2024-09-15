<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ST_Settings_CRUD' ) ) :

	final class WPSC_ST_Settings_CRUD {

		/**
		 * Ignore custom field types
		 *
		 * @var array
		 */
		public static $ignore_cft = array();

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// ignore cft.
			add_action( 'init', array( __CLASS__, 'ignore_cft' ) );

			// list.
			add_action( 'wp_ajax_wpsc_st_get_crud_settings', array( __CLASS__, 'get_listing' ) );

			// add new rule.
			add_action( 'wp_ajax_wpsc_st_get_add_rule', array( __CLASS__, 'get_add_rule' ) );
			add_action( 'wp_ajax_wpsc_st_set_add_rule', array( __CLASS__, 'set_add_rule' ) );

			// edit rule.
			add_action( 'wp_ajax_wpsc_st_get_edit_rule', array( __CLASS__, 'get_edit_rule' ) );
			add_action( 'wp_ajax_wpsc_st_set_edit_rule', array( __CLASS__, 'set_edit_rule' ) );

			// delete rule.
			add_action( 'wp_ajax_wpsc_st_delete_rule', array( __CLASS__, 'delete_rule' ) );

			// Customer filter autocomplete.
			add_action( 'wp_ajax_wpsc_st_customer_autocomplete', array( __CLASS__, 'customer_autocomplete' ) );
		}

		/**
		 * Set ignore cft for schedule tickets
		 *
		 * @return void
		 */
		public static function ignore_cft() {

			self::$ignore_cft = apply_filters(
				'wpsc_st_ignore_cft',
				array(
					'df_id',
					'df_customer',
					'df_customer_name',
					'df_customer_email',
					'df_subject',
					'df_description',
					'df_assigned_agent',
					'df_date_created',
					'df_date_updated',
					'df_date_closed',
					'df_agent_created',
					'df_ip_address',
					'df_source',
					'df_browser',
					'df_os',
					'df_prev_assignee',
					'df_user_type',
					'cf_html',
					'cf_file_attachment_multiple',
					'cf_file_attachment_single',
					'cf_edd_order',
					'cf_woo_order',
					'df_time_spent',
					'df_sf_rating',
					'df_sf_feedback',
					'df_sf_date',
					'df_sla',
					'df_last_reply_on',
					'df_last_reply_by',
				)
			);
		}

		/**
		 * Load schedule ticket listing page
		 *
		 * @return void
		 */
		public static function get_listing() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$rules = get_option( 'wpsc-st-rules', array() );
			?>
			<table class="wpsc-st-table wpsc-setting-tbl">
				<thead>
					<tr>
						<th><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></th>
						<th><?php echo esc_attr( wpsc__( 'Actions', 'supportcandy' ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $rules as $id => $rule ) {
						?>
						<tr>
							<td><?php echo esc_attr( $rule['title'] ); ?></td>
							<td>
								<a href="javascript:wpsc_st_get_edit_rule('<?php echo esc_attr( $id ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_st_get_edit_rule' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></a> |
								<a href="javascript:wpsc_st_delete_rule('<?php echo esc_attr( $id ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_st_delete_rule' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?></a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<script>
				jQuery('table.wpsc-st-table').DataTable({
					ordering: false,
					pageLength: 20,
					bLengthChange: false,
					columnDefs: [ 
						{ targets: -1, searchable: false },
						{ targets: '_all', className: 'dt-left' }
					],
					dom: 'Bfrtip',
					buttons: [
						{
							text: '<?php echo esc_attr( wpsc__( 'Add new', 'supportcandy' ) ); ?>',
							className: 'wpsc-button small primary',
							action: function ( e, dt, node, config ) {

								wpsc_show_modal();
								var data = { action: 'wpsc_st_get_add_rule', _ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_st_get_add_rule' ) ); ?>' };
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
						}
					],
					language: supportcandy.translations.datatables
				});
			</script>
			<?php
			wp_die();
		}

		/**
		 * Get add new rule
		 *
		 * @return void
		 */
		public static function get_add_rule() {

			if ( check_ajax_referer( 'wpsc_st_get_add_rule', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$title = esc_attr( wpsc__( 'Add new', 'supportcandy' ) );

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-st-add-rule">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="title" autocomplete="off">
				</div>
				<input type="hidden" name="action" value="wpsc_st_set_add_rule">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_st_set_add_rule' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_st_set_add_rule(this);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);

			wp_send_json( $response, 200 );
		}

		/**
		 * Set add new rule
		 *
		 * @return void
		 */
		public static function set_add_rule() {

			if ( check_ajax_referer( 'wpsc_st_set_add_rule', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$index = 1;
			$rules = get_option( 'wpsc-st-rules', array() );
			if ( $rules ) {
				end( $rules );
				$last_index = key( $rules );
				reset( $rules );
				$index = intval( $last_index ) + 1;
			}

			$rules[ $index ] = array(

				'title'                          => $title,
				'recurrence-period'              => 'daily',

				'daily-recurrence-type'          => 'daily-every-day',
				'daily-x-days'                   => 1,
				'daily-x-work-days'              => 1,

				'weekly-x-weeks'                 => 1,
				'weekly-days'                    => array( 1, 2, 3, 4, 5 ),

				'monthly-recurrence-type'        => 'monthly-day-number',
				'monthly-day-number-x-months'    => 1,
				'monthly-day-number-day'         => 1,
				'monthly-week-number-x-months'   => 1,
				'monthly-week-number-occurrence' => 1,
				'monthly-week-number-day'        => 1,

				'yearly-recurrence-type'         => 'yearly-day-number',
				'yearly-day-number-x-years'      => 1,
				'yearly-day-number-day'          => 1,
				'yearly-day-number-month'        => 1,
				'yearly-week-number-x-years'     => 1,
				'yearly-week-number-occurrence'  => 1,
				'yearly-week-number-day'         => 1,
				'yearly-week-number-month'       => 1,

				'starts-on'                      => ( new DateTime() )->format( 'Y-m-d' ),

				'ends-on'                        => 'no-end-date',
				'ends-after-times'               => 10,
				'end-date'                       => '',

				'ticket-count'                   => 0,
			);

			update_option( 'wpsc-st-rules', $rules );
			$nonce = wp_create_nonce( 'wpsc_st_get_edit_rule' );
			wp_send_json(
				array(
					'index' => $index,
					'nonce' => $nonce,
				),
				200
			);
		}

		/**
		 * Get add new usergroup
		 *
		 * @return void
		 */
		public static function get_edit_rule() {

			if ( check_ajax_referer( 'wpsc_st_get_edit_rule', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$rules = get_option( 'wpsc-st-rules', array() );
			if ( ! isset( $rules[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$rule = $rules[ $id ];
			?>

			<form action="#" onsubmit="return false;" class="frm-add-new-st">

				<div class="wpsc-input-group">
					<div class="wpsc-tff-label">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="title" value="<?php echo esc_attr( $rule['title'] ); ?>" autocomplete="off">
				</div>

				<div class="wpsc-accordion">
					<h3><?php esc_attr_e( 'Recurrence Settings', 'wpsc-st' ); ?></h3>
					<div>
						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12">
							<div class="wpsc-tff-label">
								<span class="name"><?php esc_attr_e( 'Recurrence period', 'wpsc-st' ); ?></span>
							</div>
							<select class="recurrence-period" name="recurrence-period">
								<option <?php selected( $rule['recurrence-period'], 'daily' ); ?> value="daily"><?php esc_attr_e( 'Daily', 'wpsc-st' ); ?></option>
								<option <?php selected( $rule['recurrence-period'], 'weekly' ); ?> value="weekly"><?php esc_attr_e( 'Weekly', 'wpsc-st' ); ?></option>
								<option <?php selected( $rule['recurrence-period'], 'monthly' ); ?> value="monthly"><?php esc_attr_e( 'Monthly', 'wpsc-st' ); ?></option>
								<option <?php selected( $rule['recurrence-period'], 'yearly' ); ?> value="yearly"><?php esc_attr_e( 'Yearly', 'wpsc-st' ); ?></option>
							</select>
							<script>
								jQuery('select.recurrence-period').change(function(){
									let recurrence = jQuery(this).val();
									jQuery('.wpsc-tff.recurrence').hide();
									jQuery('.wpsc-tff.recurrence.'+recurrence).show();
								});
								jQuery('select.recurrence-period').trigger('change');
							</script>
						</div>

						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12 recurrence daily">
							<div class="wpsc-tff-label">
								<span class="name"><?php esc_attr_e( 'Settings for daily recurrence', 'wpsc-st' ); ?></span>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
							<?php $no_of_days = '<input type="number" name="daily-x-days" value="' . $rule['daily-x-days'] . '">'; ?>
								<input type="radio" <?php checked( $rule['daily-recurrence-type'], 'daily-every-day' ); ?> name="daily-recurrence-type" value="daily-every-day">
								<?php /* translators: %1$s: Every x day */ ?>
								<div><?php printf( esc_attr__( 'Every %1$s day(s)', 'wpsc-st' ), $no_of_days ); // phpcs:ignore?></div>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
							<?php $no_of_days = '<input type="number" name="daily-x-work-days" value="' . $rule['daily-x-work-days'] . '">'; ?>
								<input type="radio" <?php checked( $rule['daily-recurrence-type'], 'daily-work-day' ); ?> name="daily-recurrence-type" value="daily-work-day">
								<?php /* translators: %1$s: Every x work day */ ?>
								<div><?php printf( esc_attr__( 'Every %1$s work day(s)', 'wpsc-st' ), $no_of_days ); // phpcs:ignore?></div>
							</div>
						</div>

						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12 recurrence weekly">
							<div class="wpsc-tff-label">
								<span class="name"><?php esc_attr_e( 'Settings for weekly recurrence', 'wpsc-st' ); ?></span>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
							<?php
								$no_of_weeks = '<input type="number" name="weekly-x-weeks" value="' . $rule['weekly-x-weeks'] . '">';
							?>
								<?php /* translators: %1$s: Every x week(s) */ ?>
								<div><?php printf( esc_attr__( 'Every %1$s week(s) on', 'wpsc-st' ), $no_of_weeks ); // phpcs:ignore?></div>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<?php
								for ( $i = 1; $i <= 7; $i++ ) {
									?>
									<div class="checkbox-container">
										<?php
										$unique_id = uniqid( 'wpsc_' );
										$checked   = in_array( $i, $rule['weekly-days'] ) ? 'checked' : '';
										?>
										<input id="<?php echo esc_attr( $unique_id ); ?>" type="checkbox" name="weekly-days[]" value="<?php echo esc_attr( $i ); ?>" <?php echo esc_attr( $checked ); ?>/>
										<label for="<?php echo esc_attr( $unique_id ); ?>"><?php echo esc_attr( WPSC_Functions::get_day_name( $i ) ); ?></label>
									</div>
									<?php
								}
								?>
							</div>
						</div>

						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12 recurrence monthly">
							<div class="wpsc-tff-label">
								<span class="name"><?php esc_attr_e( 'Settings for monthly recurrence', 'wpsc-st' ); ?></span>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<?php
								$no_of_months = '<input type="number" name="monthly-day-number-x-months" value="' . $rule['monthly-day-number-x-months'] . '">';
								$day          = '<input type="number" name="monthly-day-number-day" value="' . $rule['monthly-day-number-day'] . '">';
								?>
								<input type="radio" <?php checked( $rule['monthly-recurrence-type'], 'monthly-day-number' ); ?> name="monthly-recurrence-type" value="monthly-day-number">
								<?php /* translators: %1$s: Every x month(s), %2$s : days */ ?>
								<div><?php printf( esc_attr__( 'Every %1$s month(s) on day %2$s', 'wpsc-st' ), $no_of_months, $day ); // phpcs:ignore?></div>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<?php
								$no_of_months = '<input type="number" name="monthly-week-number-x-months" value="' . $rule['monthly-week-number-x-months'] . '">';
								ob_start();
								?>
								<select name="monthly-week-number-occurrence">
									<option <?php selected( $rule['monthly-week-number-occurrence'], 1 ); ?> value="1"><?php esc_attr_e( 'First', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['monthly-week-number-occurrence'], 2 ); ?> value="2"><?php esc_attr_e( 'Second', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['monthly-week-number-occurrence'], 3 ); ?> value="3"><?php esc_attr_e( 'Third', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['monthly-week-number-occurrence'], 4 ); ?> value="4"><?php esc_attr_e( 'Fourth', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['monthly-week-number-occurrence'], 5 ); ?> value="5"><?php esc_attr_e( 'Last', 'wpsc-st' ); ?></option>
								</select>
								<?php
								$week_number = ob_get_clean();
								ob_start();
								?>
								<select name="monthly-week-number-day">
									<?php
									for ( $i = 1; $i <= 7; $i++ ) {
										$selected = $rule['monthly-week-number-day'] == $i ? 'selected' : '';
										?>
										<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( WPSC_Functions::get_day_name( $i ) ); ?></option>
										<?php
									}
									?>
								</select>
								<?php
								$day = ob_get_clean();
								?>
								<input type="radio" <?php checked( $rule['monthly-recurrence-type'], 'monthly-week-number' ); ?> name="monthly-recurrence-type" value="monthly-week-number">
								<?php /* translators: %1$s: Every x month(s), %2$s: week, %3$s: day */ ?>
								<div><?php printf( esc_attr__( 'Every %1$s month(s) on day %2$s %3$s', 'wpsc-st' ), $no_of_months, $week_number, $day ); // phpcs:ignore?></div>
							</div>
						</div>

						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12 recurrence yearly">
							<div class="wpsc-tff-label">
								<span class="name"><?php esc_attr_e( 'Settings for yearly recurrence', 'wpsc-st' ); ?></span>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<?php
								$no_of_years = '<input type="number" name="yearly-day-number-x-years" value="' . $rule['yearly-day-number-x-years'] . '">';
								$day         = '<input type="number" name="yearly-day-number-day" value="' . $rule['yearly-day-number-day'] . '">';
								ob_start();
								?>
								<select name="yearly-day-number-month">
									<?php
									for ( $i = 1; $i <= 12; $i++ ) {
										$selected = $rule['yearly-day-number-month'] == $i ? 'selected' : '';
										?>
										<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( WPSC_Functions::get_month_name( $i ) ); ?></option>
										<?php
									}
									?>
								</select>
								<?php
								$month = ob_get_clean();
								?>
								<input type="radio" <?php checked( $rule['yearly-recurrence-type'], 'yearly-day-number' ); ?> name="yearly-recurrence-type" value="yearly-day-number" checked>
								<?php /* translators: %1$s: Every x year(s), %2$s: day, %3$s: month */ ?>
								<div><?php printf( esc_attr__( 'Every %1$s year(s) on day %2$s of %3$s', 'wpsc-st' ), $no_of_years, $day, $month ); // phpcs:ignore?></div>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<?php
								$no_of_years = '<input type="number" name="yearly-week-number-x-years" value="' . $rule['yearly-week-number-x-years'] . '">';
								ob_start();
								?>
								<select name="yearly-week-number-occurrence">
									<option <?php selected( $rule['yearly-week-number-occurrence'], 1 ); ?> value="1"><?php esc_attr_e( 'First', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['yearly-week-number-occurrence'], 2 ); ?> value="2"><?php esc_attr_e( 'Second', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['yearly-week-number-occurrence'], 3 ); ?> value="3"><?php esc_attr_e( 'Third', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['yearly-week-number-occurrence'], 4 ); ?> value="4"><?php esc_attr_e( 'Fourth', 'wpsc-st' ); ?></option>
									<option <?php selected( $rule['yearly-week-number-occurrence'], 5 ); ?> value="5"><?php esc_attr_e( 'Last', 'wpsc-st' ); ?></option>
								</select>
								<?php
								$week_number = ob_get_clean();
								ob_start();
								?>
								<select name="yearly-week-number-day">
									<?php
									for ( $i = 1; $i <= 7; $i++ ) {
										?>
										<option <?php selected( $rule['yearly-week-number-day'], $i ); ?> value="<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( WPSC_Functions::get_day_name( $i ) ); ?></option>
										<?php
									}
									?>
								</select>
								<?php
								$day = ob_get_clean();
								ob_start();
								?>
								<select name="yearly-week-number-month">
									<?php
									for ( $i = 1; $i <= 12; $i++ ) {
										?>
										<option <?php selected( $rule['yearly-week-number-month'], $i ); ?> value="<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( WPSC_Functions::get_month_name( $i ) ); ?></option>
										<?php
									}
									?>
								</select>
								<?php
								$month = ob_get_clean();
								?>
								<input type="radio" <?php checked( $rule['yearly-recurrence-type'], 'yearly-week-number' ); ?> name="yearly-recurrence-type" value="yearly-week-number">
								<?php /* translators: %1$s: Every x year(s), %2$s: week, %3$s: day, %4$s: month */ ?>
								<div><?php printf( esc_attr__( 'Every %1$s year(s) on day %2$s %3$s of %4$s', 'wpsc-st' ), $no_of_years, $week_number, $day, $month ); // phpcs:ignore?></div>
							</div>
						</div>

						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12">
							<div class="wpsc-tff-label">
								<span class="name"><?php esc_attr_e( 'Starts on', 'wpsc-st' ); ?></span>
							</div>
							<input class="starts-on" type="text" name="starts-on" value="<?php echo esc_attr( $rule['starts-on'] ); ?>">
							<script>jQuery('input.starts-on').flatpickr({disableMobile: true});</script>
						</div>

						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12">
							<div class="wpsc-tff-label">
								<span class="name"><?php esc_attr_e( 'Ends on', 'wpsc-st' ); ?></span>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<input type="radio" <?php checked( $rule['ends-on'], 'no-end-date' ); ?> name="ends-on" value="no-end-date">
								<div><?php esc_attr_e( 'No end date', 'wpsc-st' ); ?></div>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<?php $ends_after = '<input type="number" name="ends-after-times" value="' . $rule['ends-after-times'] . '">'; ?>
								<input type="radio" <?php checked( $rule['ends-on'], 'ends-after-times' ); ?> name="ends-on" value="ends-after-times">
								<?php /* translators: %1$s: Ends after x times */ ?>
								<div><?php printf( esc_attr__( 'Ends after %1$s times', 'wpsc-st' ), $ends_after ); // phpcs:ignore?></div>
							</div>
							<div class="wpsc-st-rec-setting-radio-container">
								<?php $end_date = '<input class="ends-on" type="text" name="end-date" value="' . $rule['end-date'] . '">'; ?>
								<input type="radio" <?php checked( $rule['ends-on'], 'end-date' ); ?> name="ends-on" value="end-date">
								<?php /* translators: %1$s: Ends on x day */ ?>
								<div><?php printf( esc_attr__( 'Ends on %1$s', 'wpsc-st' ), $end_date ); // phpcs:ignore?></div>
							</div>
							<script>jQuery('input.ends-on').flatpickr({disableMobile: true});</script>
						</div>
					</div>

					<h3><?php echo esc_attr( wpsc__( 'Ticket Fields', 'supportcandy' ) ); ?></h3>
					<div>

						<?php $cf = WPSC_Custom_Field::get_cf_by_slug( 'customer' ); ?>
						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12">
							<div class="wpsc-tff-label">
								<span class="name"><?php echo esc_attr( $cf->name ); ?>
									<span class="required-char">*</span>
								</span>
							</div>
							<span class="extra-info"><?php echo esc_attr( $cf->extra_info ); ?></span>
							<select class="customer" name="customer">
								<?php
								$customer = isset( $rule['customer'] ) ? new WPSC_Customer( $rule['customer'] ) : new WPSC_Customer();
								if ( $customer->id ) {
									?>
									<option value="<?php echo esc_attr( $customer->id ); ?>">
										<?php
										/* translators: %1$s: Name, %2$s: Email Address */
										printf( esc_attr__( '%1$s (%2$s)', 'wpsc-st' ), esc_attr( $customer->name ), esc_attr( $customer->email ) );
										?>
									</option>
									<?php
								}
								?>
							</select>
							<script>
								jQuery('select.customer').selectWoo({
									ajax: {
										url: supportcandy.ajax_url,
										dataType: 'json',
										delay: 250,
										data: function (params) {
											return {
												q: params.term, // search term
												page: params.page,
												action: 'wpsc_st_customer_autocomplete',
												_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_st_customer_autocomplete' ) ); ?>'
											};
										},
										processResults: function (data, params) {
											var terms = [];
											if ( data ) {
												jQuery.each( data, function( id, text ) {
													terms.push( { id: text.id, text: text.title } );
												});
											}
											return {
												results: terms
											};
										},
										cache: true
									},
									escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
									minimumInputLength: 1
								});
							</script>
						</div>

						<?php $cf = WPSC_Custom_Field::get_cf_by_slug( 'subject' ); ?>
						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12">
							<div class="wpsc-tff-label">
								<span class="name"><?php echo esc_attr( $cf->name ); ?>
									<span class="required-char">*</span>
								</span>
							</div>
							<span class="extra-info"><?php echo esc_attr( $cf->extra_info ); ?></span>
							<input type="text" name="subject" autocomplete="off" value="<?php echo isset( $rule['subject'] ) ? esc_attr( $rule['subject'] ) : ''; ?>">
						</div>

						<?php $cf = WPSC_Custom_Field::get_cf_by_slug( 'description' ); ?>
						<div class="wpsc-tff wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible wpsc-xs-12">
							<div class="wpsc-tff-label">
								<span class="name"><?php echo esc_attr( $cf->name ); ?>
									<span class="required-char">*</span>
								</span>
							</div>
							<span class="extra-info"><?php echo esc_attr( $cf->extra_info ); ?></span>
							<textarea name="<?php echo esc_attr( $cf->slug ); ?>" id="description" class="wpsc_textarea"><?php echo isset( $rule['description'] ) ? wp_kses_post( wp_unslash( $rule['description'] ) ) : ''; ?></textarea>
							<script>
								<?php WPSC_Text_Editor::print_editor_init_scripts( 'description', 'wpsc-description' ); ?>
							</script>
						</div>

						<?php
						foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {
							if (
								! class_exists( $cf->type ) ||
								$cf->field != 'ticket' ||
								in_array( $cf->type::$slug, self::$ignore_cft )
							) {
								continue;
							}
							$value = isset( $rule[ $cf->slug ] ) ? $rule[ $cf->slug ] : '';
							$cf->type::print_cf_input( $cf, $value );
						}
						?>
					</div>

					<h3><?php echo esc_attr( wpsc__( 'Agentonly Fields', 'supportcandy' ) ); ?></h3>
					<div>

					<?php
					foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {
						if (
							! class_exists( $cf->type ) ||
							$cf->field != 'agentonly' ||
							in_array( $cf->type::$slug, self::$ignore_cft )
						) {
							continue;
						}
						$value = isset( $rule[ $cf->slug ] ) ? $rule[ $cf->slug ] : '';
						$cf->type::print_cf_input( $cf, $value );
					}
					?>
					</div>
				</div>
				<script>jQuery('.wpsc-accordion').accordion({heightStyle: "content", collapsible: true, navigation: true});</script>

				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<input type="hidden" name="action" value="wpsc_st_set_edit_rule">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_st_set_edit_rule' ) ); ?>">
			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_st_set_edit_rule(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="jQuery('.wpsc-setting-nav.active').trigger('click');">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>
			<?php

			wp_die();
		}

		/**
		 * Set edit rule
		 *
		 * @return void
		 */
		public static function set_edit_rule() {

			if ( check_ajax_referer( 'wpsc_st_set_edit_rule', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$rules = get_option( 'wpsc-st-rules', array() );
			if ( ! isset( $rules[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$rule = $rules[ $id ];

			// update title.
			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rule['title'] = $title;

			// recurrence period.
			$recurrence_period = isset( $_POST['recurrence-period'] ) ? sanitize_text_field( wp_unslash( $_POST['recurrence-period'] ) ) : '';
			if ( ! $recurrence_period ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rule['recurrence-period'] = $recurrence_period;

			// recurrence settings.
			if ( $recurrence_period == 'daily' ) { // daily recurrence settings.

				$daily_recurrence_type = isset( $_POST['daily-recurrence-type'] ) ? sanitize_text_field( wp_unslash( $_POST['daily-recurrence-type'] ) ) : '';
				if ( ! $daily_recurrence_type ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$rule['daily-recurrence-type'] = $daily_recurrence_type;

				if ( $daily_recurrence_type == 'daily-every-day' ) {

					$daily_x_days = isset( $_POST['daily-x-days'] ) ? intval( $_POST['daily-x-days'] ) : 0;
					if ( ! $daily_x_days ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['daily-x-days'] = $daily_x_days;

				} elseif ( $daily_recurrence_type == 'daily-work-day' ) {

					$daily_x_work_days = isset( $_POST['daily-x-work-days'] ) ? intval( $_POST['daily-x-work-days'] ) : 0;
					if ( ! $daily_x_work_days ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['daily-x-work-days'] = $daily_x_work_days;

				} else {

					wp_send_json_error( 'Bad Request', 400 );
				}
			} elseif ( $recurrence_period == 'weekly' ) { // weekly recurrence settings.

				$weekly_x_weeks = isset( $_POST['weekly-x-weeks'] ) ? intval( $_POST['weekly-x-weeks'] ) : 0;
				if ( ! $weekly_x_weeks ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$rule['weekly-x-weeks'] = $weekly_x_weeks;

				$weekly_days = isset( $_POST['weekly-days'] ) ? array_filter( array_map( 'intval', wp_unslash( $_POST['weekly-days'] ) ) ) : array();
				if ( ! $weekly_days ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$rule['weekly-days'] = $weekly_days;

			} elseif ( $recurrence_period == 'monthly' ) { // monthly recurrence settings.

				$monthly_recurrence_type = isset( $_POST['monthly-recurrence-type'] ) ? sanitize_text_field( wp_unslash( $_POST['monthly-recurrence-type'] ) ) : '';
				if ( ! $monthly_recurrence_type ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$rule['monthly-recurrence-type'] = $monthly_recurrence_type;

				if ( $monthly_recurrence_type == 'monthly-day-number' ) {

					$monthly_day_number_x_months = isset( $_POST['monthly-day-number-x-months'] ) ? intval( $_POST['monthly-day-number-x-months'] ) : 0;
					if ( ! $monthly_day_number_x_months ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['monthly-day-number-x-months'] = $monthly_day_number_x_months;

					$monthly_day_number_day = isset( $_POST['monthly-day-number-day'] ) ? intval( $_POST['monthly-day-number-day'] ) : 0;
					if ( ! $monthly_day_number_day ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['monthly-day-number-day'] = $monthly_day_number_day;

				} elseif ( $monthly_recurrence_type == 'monthly-week-number' ) {

					$monthly_week_number_x_months = isset( $_POST['monthly-week-number-x-months'] ) ? intval( $_POST['monthly-week-number-x-months'] ) : 0;
					if ( ! $monthly_week_number_x_months ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['monthly-week-number-x-months'] = $monthly_week_number_x_months;

					$monthly_week_number_occurence = isset( $_POST['monthly-week-number-occurrence'] ) ? intval( $_POST['monthly-week-number-occurrence'] ) : 0;
					if ( ! $monthly_week_number_occurence ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['monthly-week-number-occurrence'] = $monthly_week_number_occurence;

					$monthly_week_number_day = isset( $_POST['monthly-week-number-day'] ) ? intval( $_POST['monthly-week-number-day'] ) : 0;
					if ( ! $monthly_week_number_day ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['monthly-week-number-day'] = $monthly_week_number_day;

				} else {

					wp_send_json_error( 'Bad Request', 400 );
				}
			} elseif ( $recurrence_period == 'yearly' ) { // yearly recurrence settings.

				$yearly_recurrence_type = isset( $_POST['yearly-recurrence-type'] ) ? sanitize_text_field( wp_unslash( $_POST['yearly-recurrence-type'] ) ) : '';
				if ( ! $yearly_recurrence_type ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$rule['yearly-recurrence-type'] = $yearly_recurrence_type;

				if ( $yearly_recurrence_type == 'yearly-day-number' ) {

					$yearly_day_number_x_years = isset( $_POST['yearly-day-number-x-years'] ) ? intval( $_POST['yearly-day-number-x-years'] ) : 0;
					if ( ! $yearly_day_number_x_years ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['yearly-day-number-x-years'] = $yearly_day_number_x_years;

					$yearly_day_number_day = isset( $_POST['yearly-day-number-day'] ) ? intval( $_POST['yearly-day-number-day'] ) : 0;
					if ( ! $yearly_day_number_day ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['yearly-day-number-day'] = $yearly_day_number_day;

					$yearly_day_number_month = isset( $_POST['yearly-day-number-month'] ) ? intval( $_POST['yearly-day-number-month'] ) : 0;
					if ( ! $yearly_day_number_month ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['yearly-day-number-month'] = $yearly_day_number_month;

				} elseif ( $yearly_recurrence_type == 'yearly-week-number' ) {

					$yearly_week_number_x_years = isset( $_POST['yearly-week-number-x-years'] ) ? intval( $_POST['yearly-week-number-x-years'] ) : 0;
					if ( ! $yearly_week_number_x_years ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['yearly-week-number-x-years'] = $yearly_week_number_x_years;

					$yearly_week_number_occurence = isset( $_POST['yearly-week-number-occurrence'] ) ? intval( $_POST['yearly-week-number-occurrence'] ) : 0;
					if ( ! $yearly_week_number_occurence ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['yearly-week-number-occurrence'] = $yearly_week_number_occurence;

					$yearly_week_number_day = isset( $_POST['yearly-week-number-day'] ) ? intval( $_POST['yearly-week-number-day'] ) : 0;
					if ( ! $yearly_week_number_day ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['yearly-week-number-day'] = $yearly_week_number_day;

					$yearly_week_number_month = isset( $_POST['yearly-week-number-month'] ) ? intval( $_POST['yearly-week-number-month'] ) : 0;
					if ( ! $yearly_week_number_month ) {
						wp_send_json_error( 'Bad Request', 400 );
					}
					$rule['yearly-week-number-month'] = $yearly_week_number_month;

				} else {

					wp_send_json_error( 'Bad Request', 400 );
				}
			} else {

				wp_send_json_error( 'Bad Request', 400 );
			}

			// starts on.
			$starts_on = isset( $_POST['starts-on'] ) ? sanitize_text_field( wp_unslash( $_POST['starts-on'] ) ) : '';
			if (
				! $starts_on ||
				! preg_match( '/\d{4}-\d{2}-\d{2}/', $starts_on )
			) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rule['starts-on'] = $starts_on;

			// ends on.
			$ends_on = isset( $_POST['ends-on'] ) ? sanitize_text_field( wp_unslash( $_POST['ends-on'] ) ) : '';
			if ( ! $ends_on ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rule['ends-on'] = $ends_on;

			// ends on settings.
			if ( $ends_on == 'ends-after-times' ) {

				$ends_after_times = isset( $_POST['ends-after-times'] ) ? intval( $_POST['ends-after-times'] ) : 0;
				if ( ! $ends_after_times ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$rule['ends-after-times'] = $ends_after_times;

			} elseif ( $ends_on == 'end-date' ) {

				$end_date = isset( $_POST['end-date'] ) ? sanitize_text_field( wp_unslash( $_POST['end-date'] ) ) : '';
				if (
					! $end_date ||
					! preg_match( '/\d{4}-\d{2}-\d{2}/', $end_date )
				) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$rule['end-date'] = $end_date;

			} elseif ( $ends_on != 'no-end-date' ) {

				wp_send_json_error( 'Bad Request', 400 );
			}

			// customer.
			$customer = isset( $_POST['customer'] ) ? intval( $_POST['customer'] ) : 0;
			if ( ! $customer ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$customer_object = new WPSC_Customer( $customer );
			if ( ! $customer_object->id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rule['customer'] = $customer;

			// subject.
			$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
			if ( ! $subject ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rule['subject'] = $subject;

			// description.
			$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
			if ( ! $description ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rule['description'] = $description;

			// custom fields.
			foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {
				if (
					! class_exists( $cf->type ) ||
					! in_array( $cf->field, array( 'ticket', 'agentonly' ) ) ||
					in_array( $cf->type::$slug, self::$ignore_cft )
				) {
					continue;
				}
				$rule[ $cf->slug ] = $cf->type::get_cf_input_val( $cf );
			}

			// update in db.
			$rules[ $id ] = $rule;
			update_option( 'wpsc-st-rules', $rules );

			wp_die();
		}

		/**
		 * Delete rule
		 *
		 * @return void
		 */
		public static function delete_rule() {

			if ( check_ajax_referer( 'wpsc_st_delete_rule', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$rules = get_option( 'wpsc-st-rules', array() );
			if ( ! isset( $rules[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			unset( $rules[ $id ] );
			update_option( 'wpsc-st-rules', $rules );

			wp_die();
		}

		/**
		 * Customer autocomplete callback
		 *
		 * @return void
		 */
		public static function customer_autocomplete() {

			if ( check_ajax_referer( 'wpsc_st_customer_autocomplete', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

			$customers = WPSC_Customer::customer_autocomplete( $term );
			wp_send_json( $customers );
		}
	}
endif;

WPSC_ST_Settings_CRUD::init();
