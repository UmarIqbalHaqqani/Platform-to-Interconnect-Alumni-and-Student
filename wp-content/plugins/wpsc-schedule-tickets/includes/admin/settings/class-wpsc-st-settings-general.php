<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ST_Settings_General' ) ) :

	final class WPSC_ST_Settings_General {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// setting actions.
			add_action( 'wp_ajax_wpsc_st_get_general_settings', array( __CLASS__, 'get_general_settings' ) );
			add_action( 'wp_ajax_wpsc_st_set_general_settings', array( __CLASS__, 'set_general_settings' ) );
			add_action( 'wp_ajax_wpsc_st_reset_general_settings', array( __CLASS__, 'reset_general_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			update_option(
				'wpsc-st-general-settings',
				array(
					'schedule-existing' => 0,
				)
			);
		}

		/**
		 * Get general settings
		 *
		 * @return void
		 */
		public static function get_general_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$general = get_option( 'wpsc-st-general-settings' );?>
			<form action="#" onsubmit="return false;" class="wpsc-st-general-settings">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Schedule from existing ticket', 'wpsc-st' ); ?></label>
					</div>
					<select name="schedule-existing">
						<option <?php selected( $general['schedule-existing'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $general['schedule-existing'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>

				<?php do_action( 'wpsc_st_get_general_settings' ); ?>

				<input type="hidden" name="action" value="wpsc_st_set_general_settings">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_st_set_general_settings' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_st_set_general_settings(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_st_reset_general_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_st_reset_general_settings' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
			</div>
			<?php

			wp_die();
		}

		/**
		 * Set general settings
		 *
		 * @return void
		 */
		public static function set_general_settings() {

			if ( check_ajax_referer( 'wpsc_st_set_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			update_option(
				'wpsc-st-general-settings',
				array(
					'schedule-existing' => isset( $_POST['schedule-existing'] ) ? intval( $_POST['schedule-existing'] ) : 0,
				)
			);

			do_action( 'wpsc_st_set_general_settings' );

			wp_die();
		}

		/**
		 * Reset general settings
		 *
		 * @return void
		 */
		public static function reset_general_settings() {

			if ( check_ajax_referer( 'wpsc_st_reset_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			self::reset();

			do_action( 'wpsc_st_reset_general_settings' );

			wp_die();
		}
	}
endif;

WPSC_ST_Settings_General::init();
