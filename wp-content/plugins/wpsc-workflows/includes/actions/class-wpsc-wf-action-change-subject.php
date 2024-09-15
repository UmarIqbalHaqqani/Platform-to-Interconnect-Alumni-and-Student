<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Action_Change_Subject' ) ) :

	final class WPSC_WF_Action_Change_Subject {

		/**
		 * Slug for this action
		 *
		 * @var string
		 */
		public static $slug = 'change-subject';

		/**
		 * Print input field
		 *
		 * @param array $action - pre-defined json value.
		 * @return void
		 */
		public static function print( $action = array() ) {

			$unique_id = uniqid( 'wpsc_' );
			?>
			<div class="wf-action-item" data-slug="<?php echo esc_attr( self::$slug ); ?>">
				<div class="wf-action-header">
					<span class="wf-action-title"><?php echo esc_attr( WPSC_WF_Actions::$actions[ self::$slug ]['title'] ); ?></span>
					<span class="wf-remove-action" onclick="wpsc_wf_remove_action(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
				</div>
				<div class="wf-action-body">
					<?php $subject = isset( $action['subject'] ) ? $action['subject'] : ''; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php echo esc_attr( wpsc__( 'Subject', 'wpsc-workflows' ) ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<input type="text" name="actions[<?php echo esc_attr( self::$slug ); ?>][subject]" value="<?php echo esc_attr( $subject ); ?>" />
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

			$subject = isset( $action['subject'] ) ? sanitize_text_field( wp_unslash( $action['subject'] ) ) : '';
			if ( ! $subject ) {
				wp_send_json_error( esc_attr__( 'Subject not set!', 'wpsc-workflows' ), 400 );
			}

			return array(
				'subject' => $subject,
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

			// avoid looping.
			$prev = $ticket->subject;
			if ( $prev == $action['subject'] ) {
				return;
			}

			$ticket->subject = $action['subject'];
			$ticket->date_updated = new DateTime();
			$ticket->save();

			$customer_id = $workflow['type'] == 'manual' ? WPSC_Current_User::$current_user->customer->id : 0;
			do_action( 'wpsc_change_ticket_subject', $ticket, $prev, $action['subject'], $customer_id );
		}
	}

endif;
