<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Custom_Fields' ) ) :

	final class WPSC_WF_Custom_Fields {

		/**
		 * Initialize the class
		 *
		 * @return void
		 */
		public static function init() {

			// Add new field.
			add_action( 'wp_ajax_wpsc_wf_cf_get_add_new_field', array( __CLASS__, 'get_add_new_field' ) );
			add_action( 'wp_ajax_wpsc_wf_cf_set_add_new_field', array( __CLASS__, 'set_add_new_field' ) );
		}

		/**
		 * Custom field type relationship
		 *
		 * @return array
		 */
		public static function get_cft_relationship() {

			return apply_filters(
				'wpsc_wf_cft_filters',
				array(
					'cf_checkbox'      => 'WPSC_WF_CF_Checkbox',
					'cf_date'          => 'WPSC_WF_CF_Date',
					'cf_datetime'      => 'WPSC_WF_CF_Datetime',
					'cf_email'         => 'WPSC_WF_CF_Email',
					'cf_multi_select'  => 'WPSC_WF_CF_Multi_Select',
					'cf_number'        => 'WPSC_WF_CF_Number',
					'cf_radio_button'  => 'WPSC_WF_CF_Radio_Button',
					'cf_single_select' => 'WPSC_WF_CF_Single_Select',
					'cf_textfield'     => 'WPSC_WF_CF_Text_Field',
					'cf_textarea'      => 'WPSC_WF_CF_Textarea',
					'cf_time'          => 'WPSC_WF_CF_Time',
					'cf_url'           => 'WPSC_WF_CF_URL',
				)
			);
		}

		/**
		 * Get action slug using field
		 *
		 * @param string $field - either from ticket, agentonly or customer.
		 * @return string
		 */
		public static function get_action_slug( $field ) {

			switch ( $field ) {

				case 'ticket':
					return 'change-ticket-fields';

				case 'agentonly':
					return 'change-agentonly-fields';

				case 'customer':
					return 'change-customer-fields';
			}

			return '';
		}

		/**
		 * Get add new custom field
		 *
		 * @return void
		 */
		public static function get_add_new_field() {

			if ( check_ajax_referer( 'wpsc_wf_cf_get_add_new_field', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$title = esc_attr__( 'Add new field', 'wpsc-workflows' );
			$field = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : 'ticket';
			if ( ! in_array( $field, array( 'ticket', 'agentonly', 'customer' ) ) ) {
				wp_send_json_error( 'Incorrect field!', 400 );
			}
			$fields = isset( $_POST['fields'] ) ? array_filter( array_map( 'sanitize_text_field', explode( ',', sanitize_text_field( wp_unslash( $_POST['fields'] ) ) ) ) ) : array();
			$cf_relationship = self::get_cft_relationship();

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-wf-add-field">

				<div class="wpsc-input-group">
					<select name="cf_slug">
						<?php
						foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {

							if (
								! isset( $cf_relationship[ $cf->type::$slug ] ) ||
								in_array( $cf->slug, $fields ) ||
								$cf->field != $field
							) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $cf->slug ); ?>"><?php echo esc_attr( $cf->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>

				<input type="hidden" name="action" value="wpsc_wf_cf_set_add_new_field">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_wf_cf_set_add_new_field' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_wf_cf_set_add_new_field(this, '<?php echo esc_attr( $field ); ?>');">
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
		 * Set add new custom field
		 *
		 * @return void
		 */
		public static function set_add_new_field() {

			if ( check_ajax_referer( 'wpsc_wf_cf_set_add_new_field', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$slug = isset( $_POST['cf_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_slug'] ) ) : '';
			if ( ! $slug ) {
				wp_send_json_error( 'Bad request!', 400 );
			}

			$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
			if ( ! $cf->id ) {
				wp_send_json_error( 'Bad request!', 400 );
			}

			$cf_relationship = self::get_cft_relationship();
			if ( ! isset( $cf_relationship[ $cf->type::$slug ] ) ) {
				wp_send_json_error( 'Bad request!', 400 );
			}

			$cf_relationship[ $cf->type::$slug ]::print( $cf );

			wp_die();
		}
	}

endif;

WPSC_WF_Custom_Fields::init();
