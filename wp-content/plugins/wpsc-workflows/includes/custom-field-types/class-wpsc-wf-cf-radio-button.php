<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_CF_Radio_Button' ) ) :

	final class WPSC_WF_CF_Radio_Button {

		/**
		 * Print custom field input
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param string            $val - pre-defined value.
		 * @return void
		 */
		public static function print( $cf, $val = '' ) {

			$options = $cf->get_options();
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
				<?php
				foreach ( $options as $option ) {
					$checked = $option->id == $val ? 'checked' : ''
					?>
					<div class="radio-container">
					<?php $unique_id = uniqid( 'wpsc_' ); ?>
						<input id="<?php echo esc_attr( $unique_id ); ?>" type="radio" <?php echo esc_attr( $checked ); ?> name="actions[<?php echo esc_attr( WPSC_WF_Custom_Fields::get_action_slug( $cf->field ) ); ?>][<?php echo esc_attr( $cf->slug ); ?>]" value="<?php echo esc_attr( $option->id ); ?>"/>
						<label for="<?php echo esc_attr( $unique_id ); ?>"><?php echo esc_attr( $option->name ); ?></label>
					</div>
					<?php
				}
				?>
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

			$val = WPSC_Functions::sanitize_option( intval( $val ), $cf, $cf->get_options() );
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
			$prev_val = $customer->{$cf->slug} ? $customer->{$cf->slug}->id : '';

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

