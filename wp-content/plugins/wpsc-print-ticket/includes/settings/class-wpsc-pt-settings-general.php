<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_PT_Settings_General' ) ) :

	final class WPSC_PT_Settings_General {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// setting actions.
			add_action( 'wp_ajax_wpsc_pt_get_general_settings', array( __CLASS__, 'get_general_settings' ) );
			add_action( 'wp_ajax_wpsc_pt_set_general_settings', array( __CLASS__, 'set_general_settings' ) );
			add_action( 'wp_ajax_wpsc_pt_reset_general_settings', array( __CLASS__, 'reset_general_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			update_option(
				'wpsc-pt-general-settings',
				array(
					'thankyou-page-button'    => 1,
					'allow-print-to-customer' => 0,
					'button-label'            => esc_attr__( 'Print', 'wpsc-pt' ),
					'library'                 => is_rtl() ? 'tcpdf' : 'dompdf',
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

			$settings = get_option( 'wpsc-pt-general-settings' );?>

			<form action="#" onsubmit="return false;" class="wpsc-pt-general-settings">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Thank you page print button', 'wpsc-pt' ); ?></label>
					</div>
					<select name="thankyou-page-button">
						<option <?php selected( $settings['thankyou-page-button'], '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'Disable', 'supportcandy' ) ); ?></option>
						<option <?php selected( $settings['thankyou-page-button'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Enable', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Print button Label', 'wpsc-pt' ); ?></label>
					</div>
					<input type="text" name="button-label" value="<?php echo esc_attr( WPSC_Translations::get( 'wpsc-print-button-label', $settings['button-label'] ) ); ?>">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Allow to customer', 'wpsc-pt' ); ?></label>
					</div>
					<select name="allow-print-to-customer">
						<option <?php selected( $settings['allow-print-to-customer'], '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'Disable', 'supportcandy' ) ); ?></option>
						<option <?php selected( $settings['allow-print-to-customer'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Enable', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Library', 'wpsc-pt' ); ?></label>
					</div>
					<select name="library">
						<option <?php selected( $settings['library'], 'dompdf' ); ?> value="dompdf"><?php echo esc_attr__( 'DOMPDF', 'wpsc-pt' ); ?></option>
						<option <?php selected( $settings['library'], 'tcpdf' ); ?> value="tcpdf"><?php echo esc_attr__( 'TCPDF', 'wpsc-pt' ); ?></option>
					</select>
				</div>
				<?php do_action( 'wpsc_pt_get_general_settings' ); ?>
				<input type="hidden" name="action" value="wpsc_pt_set_general_settings">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_pt_set_general_settings' ) ); ?>">
			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_pt_set_general_settings(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_pt_reset_general_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_pt_reset_general_settings' ) ); ?>');">
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

			if ( check_ajax_referer( 'wpsc_pt_set_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			update_option(
				'wpsc-pt-general-settings',
				array(
					'thankyou-page-button'    => isset( $_POST['thankyou-page-button'] ) ? intval( $_POST['thankyou-page-button'] ) : 1,
					'allow-print-to-customer' => isset( $_POST['allow-print-to-customer'] ) ? intval( $_POST['allow-print-to-customer'] ) : 1,
					'button-label'            => ! empty( $_POST['button-label'] ) ? sanitize_text_field( wp_unslash( $_POST['button-label'] ) ) : esc_attr__( 'Print', 'wpsc-pt' ),
					'library'                 => isset( $_POST['library'] ) ? sanitize_text_field( wp_unslash( $_POST['library'] ) ) : 'dompdf',
				)
			);

			// Add new translatopn.
			$settings = get_option( 'wpsc-pt-general-settings' );
			WPSC_Translations::add( 'wpsc-print-button-label', $settings['button-label'] );

			do_action( 'wpsc_pt_set_general_settings' );

			wp_die();
		}

		/**
		 * Reset general settings
		 *
		 * @return void
		 */
		public static function reset_general_settings() {

			if ( check_ajax_referer( 'wpsc_pt_reset_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			self::reset();

			do_action( 'wpsc_pt_reset_general_settings' );

			wp_die();
		}
	}
endif;

WPSC_PT_Settings_General::init();
