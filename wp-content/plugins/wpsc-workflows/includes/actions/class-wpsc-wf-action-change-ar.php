<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Action_Change_AR' ) ) :

	final class WPSC_WF_Action_Change_AR {

		/**
		 * Slug for this action
		 *
		 * @var string
		 */
		public static $slug = 'change-ar';

		/**
		 * Print input field
		 *
		 * @param array $action - pre-defined json value.
		 * @return void
		 */
		public static function print( $action = array() ) {

			?>
			<div class="wf-action-item" data-slug="<?php echo esc_attr( self::$slug ); ?>">
				<div class="wf-action-header">
					<span class="wf-action-title"><?php echo esc_attr( WPSC_WF_Actions::$actions[ self::$slug ]['title'] ); ?></span>
					<span class="wf-remove-action" onclick="wpsc_wf_remove_action(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
				</div>
				<div class="wf-action-body">
					<?php $change_policy = isset( $action['change-policy'] ) ? $action['change-policy'] : 'replace'; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Change policy', 'wpsc-workflows' ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][change-policy]">
							<option <?php selected( 'replace', $change_policy, true ); ?> value="replace"><?php esc_attr_e( 'Replace', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'merge', $change_policy, true ); ?> value="merge"><?php esc_attr_e( 'Merge', 'wpsc-workflows' ); ?></option>
						</select>
					</div>
					<?php $emails = isset( $action['emails'] ) ? $action['emails'] : array(); ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php echo esc_attr( wpsc__( 'Email address (one per line)', 'supportcandy' ) ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<textarea rows="5" name="actions[<?php echo esc_attr( self::$slug ); ?>][emails]"><?php echo esc_attr( implode( PHP_EOL, $emails ) ); ?></textarea>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Sanitize action input data to store in db
		 *
		 * @param array $action - actioin input array of this type.
		 * @return array
		 */
		public static function sanitize_action( $action ) {

			$change_policy = isset( $action['change-policy'] ) ? sanitize_key( $action['change-policy'] ) : '';
			if ( ! $change_policy ) {
				wp_send_json_error( esc_attr__( 'Change policy is not set!', 'wpsc-workflows' ), 400 );
			}

			$emails = isset( $action['emails'] ) ? array_unique( array_filter( array_map( 'sanitize_email', explode( PHP_EOL, sanitize_textarea_field( $action['emails'] ) ) ) ) ) : array();
			if ( ! $emails ) {
				wp_send_json_error( esc_attr__( 'Emails not set!', 'wpsc-workflows' ), 400 );
			}

			return array(
				'change-policy' => $change_policy,
				'emails'        => $emails,
			);
		}

		/**
		 * Execute the action of this type
		 *
		 * @param array       $action - action details.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param array       $workflow - workflow array.
		 * @return void
		 */
		public static function execute( $action, $ticket, $workflow ) {

			$prev = $ticket->add_recipients;
			$new = $action['emails'];

			if ( $action['change-policy'] == 'merge' ) {
				$new = array_unique( array_merge( $prev, $new ) );
			}

			if ( ! ( array_diff( $new, $prev ) || array_diff( $prev, $new ) ) ) {
				return;
			}

			$ticket->add_recipients = $new;
			$ticket->date_updated   = new DateTime();
			$ticket->save();

			$customer_id = $workflow['type'] == 'manual' ? WPSC_Current_User::$current_user->customer->id : 0;
			do_action( 'wpsc_change_ticket_add_recipients', $ticket, $prev, $new, $customer_id );
		}
	}

endif;
