<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Action_Change_Ticket_Fields' ) ) :

	final class WPSC_WF_Action_Change_Ticket_Fields {

		/**
		 * Slug for this action
		 *
		 * @var string
		 */
		public static $slug = 'change-ticket-fields';

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
					<div class="wf-cf-container ticket">
						<?php
						if ( $action ) {
							$cf_relationship = WPSC_WF_Custom_Fields::get_cft_relationship();
							foreach ( $action as $slug => $val ) {
								$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
								if ( ! $cf || ! isset( $cf_relationship[ $cf->type::$slug ] ) ) {
									continue;
								}
								$cf_relationship[ $cf->type::$slug ]::print( $cf, $val );
							}
						}
						?>
					</div>
					<button class="wpsc-button small secondary" onclick="wpsc_wf_cf_get_add_new_field( '<?php echo esc_attr( wp_create_nonce( 'wpsc_wf_cf_get_add_new_field' ) ); ?>', 'ticket' );">
						<?php esc_attr_e( 'Add new field', 'wpsc-workflows' ); ?>
					</button>
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

			$temp = array();
			$cf_relationship = WPSC_WF_Custom_Fields::get_cft_relationship();
			foreach ( $action as $slug => $val ) {
				$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
				if ( ! $cf || ! isset( $cf_relationship[ $cf->type::$slug ] ) ) {
					continue;
				}
				$temp[ $slug ] = $cf_relationship[ $cf->type::$slug ]::sanitize( $cf, $val );
			}
			return $temp;
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

			$prev = clone $ticket;
			$cf_relationship = WPSC_WF_Custom_Fields::get_cft_relationship();

			foreach ( $action as $slug => $val ) {
				$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
				if ( ! $cf || ! isset( $cf_relationship[ $cf->type::$slug ] ) ) {
					continue;
				}
				$ticket = $cf_relationship[ $cf->type::$slug ]::modify_ticket( $cf, $val, $ticket );
			}

			if ( $prev != $ticket ) {
				$ticket->date_updated = new DateTime();
				$ticket->save();

				do_action( 'wpsc_change_ticket_fields', $prev, $ticket, 0 );

				// update the individual ticket static property for the furture actions.
				WPSC_Individual_Ticket::$ticket = $ticket;
			}
		}
	}

endif;
