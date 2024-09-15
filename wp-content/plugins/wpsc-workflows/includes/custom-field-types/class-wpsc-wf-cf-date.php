<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_CF_Date' ) ) :

	final class WPSC_WF_CF_Date {

		/**
		 * Print custom field input
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param string            $val - pre-defined value.
		 * @return void
		 */
		public static function print( $cf, $val = '' ) {

			$val = $val && ! is_object( $val ) ? new DateTime( $val ) : '';

			$unique_id = uniqid( 'wpsc_' );
			?>
			<div class="wpsc-input-group <?php echo esc_attr( $cf->field ); ?>" data-slug="<?php echo esc_attr( $cf->slug ); ?>">
				<div class="label-container">
					<label for="">
						<?php echo esc_attr( $cf->name ); ?>
						<span
							class="required-char"
							title="<?php esc_attr_e( 'Remove', 'wpsc-workflows' ); ?>"
							onclick="wpsc_wf_cf_remove_field(this);"
							>[x]</span>
					</label>
				</div>
				<div class="wpsc-input-close-group">
					<input
						id="<?php echo esc_attr( $unique_id ); ?>"
						type="text"
						name="actions[<?php echo esc_attr( WPSC_WF_Custom_Fields::get_action_slug( $cf->field ) ); ?>][<?php echo esc_attr( $cf->slug ); ?>]"
						value="<?php echo $val && is_object( $val ) ? esc_attr( $val->format( 'Y-m-d' ) ) : ''; ?>"
						autocomplete="off"/>
					<span onclick="wpsc_clear_date(this);"><?php WPSC_Icons::get( 'times' ); ?></span>
				</div>
				<script>
					jQuery('#<?php echo esc_attr( $unique_id ); ?>').flatpickr({
						<?php echo $cf->date_range == 'future' ? 'minDate: new Date(),' : ''; ?>
						<?php echo $cf->date_range == 'past' ? 'maxDate: new Date(),' : ''; ?>
						disableMobile: true,
						<?php echo $cf->date_range == 'range' ? "minDate:'" . esc_attr( $cf->start_range->format( 'Y-m-d' ) ) . "', maxDate:'" . esc_attr( $cf->end_range->format( 'Y-m-d' ) ) . "'" : ''; ?>
					}); 
				</script>
			</div>
			<?php
		}

		/**
		 * Sanitize value of given custom field
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param array             $val - value to be sanitized.
		 * @return string
		 */
		public static function sanitize( $cf, $val ) {

			$val = WPSC_Functions::sanitize_date( sanitize_text_field( $val ) );
			if ( ! $val ) {
				/* translators: %1$s: cusotom field name */
				wp_send_json_error( sprintf( esc_attr__( '%1$s not set!', 'wpsc-workflows' ), $cf->name ), 400 );
			}

			return $val;
		}

		/**
		 * Modify ticket
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param array             $val - value to assign.
		 * @param WPSC_Ticket       $ticket - ticket object to set value for.
		 * @return WPSC_Ticket
		 */
		public static function modify_ticket( $cf, $val, $ticket ) {

			$ticket->{$cf->slug} = $val;
			return $ticket;
		}

		/**
		 * Modify customer
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param array             $val - value to assign.
		 * @param WPSC_Ticket       $ticket - ticket object to set value for.
		 * @return void
		 */
		public static function modify_customer( $cf, $val, $ticket ) {

			$customer = $ticket->customer;
			$prev_val = $customer->{$cf->slug} && is_object( $customer->{$cf->slug} ) ? $customer->{$cf->slug}->format( 'Y-m-d H:i:s' ) : '';

			if ( $prev_val == $val ) {
				return;
			}

			$customer->{$cf->slug} = $val;
			$customer->save();

			// Set log for this change.
			WPSC_Log::insert(
				array(
					'type'         => 'customer',
					'ref_id'       => $customer->id,
					'modified_by'  => 0,
					'body'         => wp_json_encode(
						array(
							'slug' => $cf->slug,
							'prev' => $prev_val,
							'new'  => $val,
						)
					),
					'date_created' => ( new DateTime() )->format( 'Y-m-d H:i:s' ),
				)
			);
		}
	}
endif;
