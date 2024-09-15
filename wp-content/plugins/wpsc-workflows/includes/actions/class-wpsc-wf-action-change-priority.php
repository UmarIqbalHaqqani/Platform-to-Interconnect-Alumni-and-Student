<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Action_Change_Priority' ) ) :

	final class WPSC_WF_Action_Change_Priority {

		/**
		 * Slug for this action
		 *
		 * @var string
		 */
		public static $slug = 'change-priority';

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
					<?php $value = isset( $action['priority'] ) ? $action['priority'] : ''; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php echo esc_attr( wpsc__( 'Priority', 'wpsc-workflows' ) ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][priority]">
							<?php
							$priorities = WPSC_Priority::find( array( 'items_per_page' => 0 ) )['results'];
							foreach ( $priorities as $priority ) {
								?>
								<option <?php selected( $priority->id, $value, true ); ?> value="<?php echo intval( $priority->id ); ?>"><?php echo esc_attr( $priority->name ); ?></option>
								<?php
							}
							?>
						</select>
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

			$priority = isset( $action['priority'] ) ? intval( $action['priority'] ) : 0;
			if ( ! $priority ) {
				wp_send_json_error( esc_attr__( 'Priority not set!', 'wpsc-workflows' ), 400 );
			}

			return array(
				'priority' => $priority,
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

			$priorities = array_map(
				fn( $priority ) => $priority->id,
				WPSC_Priority::find( array( 'items_per_page' => 0 ) )['results']
			);

			if ( ! in_array( $action['priority'], $priorities ) || $ticket->priority->id == $action['priority'] ) {
				return;
			}

			$customer_id = $workflow['type'] == 'manual' ? WPSC_Current_User::$current_user->customer->id : 0;
			WPSC_Individual_Ticket::change_priority( $ticket->priority->id, $action['priority'], $customer_id );
		}
	}

endif;
