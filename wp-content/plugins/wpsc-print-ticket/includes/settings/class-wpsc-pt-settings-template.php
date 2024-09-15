<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_PT_Settings_Template' ) ) :

	final class WPSC_PT_Settings_Template {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// setting actions.
			add_action( 'wp_ajax_wpsc_pt_get_template_settings', array( __CLASS__, 'get_template_settings' ) );
			add_action( 'wp_ajax_wpsc_pt_set_template_settings', array( __CLASS__, 'set_template_settings' ) );
			add_action( 'wp_ajax_wpsc_pt_reset_template_settings', array( __CLASS__, 'reset_template_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			$header['text'] = '
                <p>Ticket ID : #{{ticket_id}}</p>
                <p>Category : {{ticket_category}}</p>
                <p>Priority : {{ticket_priority}}</p>';

			$header['editor'] = 'html';

			$body['text']   = '
                <strong>Name : </strong>{{customer_name}}<br>
                <strong>Email : </strong>{{customer_email}}<br>
                <strong>Date : </strong>{{date_created}}<br><br>
                <strong>Subject : </strong>{{ticket_subject}}<br><br>
                <strong>Description : </strong><br>
                {{ticket_description}}';
			$body['editor'] = 'html';

			$footer['text']   = '<div>I am Footer</div>';
			$footer['editor'] = 'html';

			update_option(
				'wpsc-pt-template-settings',
				array(
					'header-height'    => is_rtl() ? 50 : 100, // give preference to tcpdf.
					'header-font-size' => 14,
					'header'           => $header,
					'body-font-size'   => 14,
					'body'             => $body,
					'footer-height'    => is_rtl() ? 15 : 50,
					'footer-font-size' => 14,
					'footer'           => $footer,
				)
			);

			// remove string translations.
			WPSC_Translations::remove( 'wpsc-pt-header' );
			WPSC_Translations::remove( 'wpsc-pt-body' );
			WPSC_Translations::remove( 'wpsc-pt-footer' );

			// add string translations.
			WPSC_Translations::add( 'wpsc-pt-header', $header['text'] );
			WPSC_Translations::add( 'wpsc-pt-body', $body['text'] );
			WPSC_Translations::add( 'wpsc-pt-footer', $footer['text'] );
		}

		/**
		 * Get template settings
		 *
		 * @return void
		 */
		public static function get_template_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$settings = get_option( 'wpsc-pt-template-settings' );?>

			<form action="#" onsubmit="return false;" class="wpsc-pt-template-settings">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Header height (px)', 'wpsc-pt' ); ?></label>
					</div>
					<input type="text" name="header-height" value="<?php echo esc_attr( $settings['header-height'] ); ?>">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Header font size (px)', 'wpsc-pt' ); ?></label>
					</div>
					<input type="number" name="header-font-size" value="<?php echo esc_attr( $settings['header-font-size'] ); ?>">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Header', 'wpsc-pt' ); ?>
						</label>
					</div>
					<div class="textarea-container ">
						<div class = "wpsc_tinymce_editor_btns">
							<div class="inner-container">
								<button class="visual-header wpsc-switch-editor <?php echo esc_attr( $settings['header']['editor'] ) == 'html' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_header_tinymce(this, 'wpsc-print-header', 'wpsc_print_header');"><?php echo esc_attr( wpsc__( 'Visual', 'supportcandy' ) ); ?></button>
								<button class="text-header wpsc-switch-editor <?php echo esc_attr( $settings['header']['editor'] ) == 'text' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_header_textarea(this, 'wpsc-print-header')"><?php echo esc_attr( wpsc__( 'Text', 'supportcandy' ) ); ?></button>
							</div>
						</div>
						<?php
						$header_text = $settings['header']['text'] ? WPSC_Translations::get( 'wpsc-pt-header', stripslashes( $settings['header']['text'] ) ) : stripslashes( $settings['header']['text'] );
						?>
						<textarea id="wpsc-print-header" name="header" class="wpsc_textarea"><?php echo wp_kses_post( $header_text ); ?></textarea>
						<div class="wpsc-it-editor-action-container">
							<div class="actions">
								<div class="wpsc-editor-actions">
									<span onclick="wpsc_get_macros()" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Insert Macro', 'supportcandy' ) ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Body font size (px)', 'wpsc-pt' ); ?></label>
					</div>
					<input type="number" name="body-font-size" value="<?php echo esc_attr( $settings['body-font-size'] ); ?>">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Body', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<div class="textarea-container ">
						<div class ="wpsc_tinymce_editor_btns">
							<div class="inner-container">
								<button class="visual-body wpsc-switch-editor <?php echo esc_attr( $settings['body']['editor'] ) == 'html' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_body_tinymce(this, 'wpsc-print-body', 'wpsc_print_body');"><?php echo esc_attr( wpsc__( 'Visual', 'supportcandy' ) ); ?></button>
								<button class="text-body wpsc-switch-editor <?php echo esc_attr( $settings['body']['editor'] ) == 'text' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_body_textarea(this, 'wpsc-print-body')"><?php echo esc_attr( wpsc__( 'Text', 'supportcandy' ) ); ?></button>
							</div>
						</div>
						<?php
						$body_text = $settings['body']['text'] ? WPSC_Translations::get( 'wpsc-pt-body', stripslashes( $settings['body']['text'] ) ) : stripslashes( $settings['body']['text'] );
						?>
						<textarea id="wpsc-print-body" name="body" class="wpsc_textarea"><?php echo wp_kses_post( $body_text ); ?></textarea>
						<div class="wpsc-it-editor-action-container">
							<div class="actions">
								<div class="wpsc-editor-actions">
									<span onclick="wpsc_get_macros()" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Insert Macro', 'supportcandy' ) ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Footer height (px)', 'wpsc-pt' ); ?></label>
					</div>
					<input type="text" name="footer-height" value="<?php echo esc_attr( $settings['footer-height'] ); ?>">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Footer font size (px)', 'wpsc-pt' ); ?></label>
					</div>
					<input type="number" name="footer-font-size" value="<?php echo esc_attr( $settings['footer-font-size'] ); ?>">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Footer', 'wpsc-pt' ); ?>
						</label>
					</div>
					<div class="textarea-container ">
						<div class ="wpsc_tinymce_editor_btns">
							<div class="inner-container">
								<button class="visual-footer wpsc-switch-editor <?php echo esc_attr( $settings['footer']['editor'] ) == 'html' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_footer_tinymce(this, 'wpsc-print-footer', 'wpsc_print_footer');"><?php echo esc_attr( wpsc__( 'Visual', 'supportcandy' ) ); ?></button>
								<button class="text-footer wpsc-switch-editor <?php echo esc_attr( $settings['footer']['editor'] ) == 'text' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_footer_textarea(this, 'wpsc-print-footer')"><?php echo esc_attr( wpsc__( 'Text', 'supportcandy' ) ); ?></button>
							</div>
						</div>
						<?php
						$footer_text = $settings['footer']['text'] ? WPSC_Translations::get( 'wpsc-pt-footer', stripslashes( $settings['footer']['text'] ) ) : stripslashes( $settings['footer']['text'] );
						?>
						<textarea id="wpsc-print-footer" name="footer" class="wpsc_textarea"><?php echo wp_kses_post( $footer_text ); ?></textarea>
						<div class="wpsc-it-editor-action-container">
							<div class="actions">
								<div class="wpsc-editor-actions">
									<span onclick="wpsc_get_macros()" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Insert Macro', 'supportcandy' ) ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<script>
					<?php

					if ( $settings['header']['editor'] == 'html' ) :
						?>
						jQuery('.wpsc-switch-editor.visual-header').trigger('click');
						<?php
					endif;

					if ( $settings['body']['editor'] == 'html' ) :
						?>
						jQuery('.wpsc-switch-editor.visual-body').trigger('click');
						<?php
					endif;

					if ( $settings['footer']['editor'] == 'html' ) :
						?>
						jQuery('.wpsc-switch-editor.visual-footer').trigger('click');
						<?php
					endif;
					?>

					/** Header Tinymce */
					function wpsc_get_header_tinymce(el, selector, body_id){
						jQuery(el).parent().find('.text-header').removeClass('active');
						jQuery(el).addClass('active');
						tinymce.remove('#'+selector);
						tinymce.init({ 
							selector:'#'+selector,
							body_id: body_id,
							menubar: false,
							statusbar: false,
							height : '200',
							plugins: [
							'lists link image directionality'
							],
							image_advtab: true,
							toolbar: 'bold italic underline blockquote | alignleft aligncenter alignright | bullist numlist | rtl | link image',
							directionality: '<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>',
							branding: false,
							autoresize_bottom_margin: 20,
							browser_spellcheck : true,
							relative_urls : false,
							remove_script_host : false,
							convert_urls : true,
							setup: function (editor) {
							}
						});
						jQuery('#editor').val('html');
					}

					/** Header Textarea */
					function wpsc_get_header_textarea(el, selector){
						jQuery(el).parent().find('.visual-header').removeClass('active');
						jQuery(el).addClass('active');
						tinymce.remove('#'+selector);
						jQuery('#editor').val('text');
					}

					/** Body Tinymce */
					function wpsc_get_body_tinymce(el, selector, body_id) {
						jQuery(el).parent().find('.text-body').removeClass('active');
						jQuery(el).addClass('active');
						tinymce.remove('#'+selector);
						tinymce.init({ 
							selector:'#'+selector,
							body_id: body_id,
							menubar: false,
							statusbar: false,
							height : '200',
							plugins: [
							'lists link image directionality'
							],
							image_advtab: true,
							toolbar: 'bold italic underline blockquote | alignleft aligncenter alignright | bullist numlist | rtl | link image',
							directionality: '<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>',
							branding: false,
							autoresize_bottom_margin: 20,
							browser_spellcheck : true,
							relative_urls : false,
							remove_script_host : false,
							convert_urls : true,
							setup: function (editor) {
							}
						});
						jQuery('#body-editor').val('html');
					}

					/** Body Textarea */
					function wpsc_get_body_textarea(el, selector) {
						jQuery(el).parent().find('.visual-body').removeClass('active');
						jQuery(el).addClass('active');
						tinymce.remove('#'+selector);
						jQuery('#body-editor').val('text');
					}

					/** Footer Tinymce */
					function wpsc_get_footer_tinymce(el, selector, body_id) {
						jQuery(el).parent().find('.text-footer').removeClass('active');
						jQuery(el).addClass('active');
						tinymce.remove('#'+selector);
						tinymce.init({ 
							selector:'#'+selector,
							body_id: body_id,
							menubar: false,
							statusbar: false,
							height : '200',
							plugins: [
							'lists link image directionality'
							],
							image_advtab: true,
							toolbar: 'bold italic underline blockquote | alignleft aligncenter alignright | bullist numlist | rtl | link image',
							directionality: '<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>',
							branding: false,
							autoresize_bottom_margin: 20,
							browser_spellcheck : true,
							relative_urls : false,
							remove_script_host : false,
							convert_urls : true,
							setup: function (editor) {
							}
						});
						jQuery('#footer-editor').val('html');
					}

					/** Footer Textarea */
					function wpsc_get_footer_textarea(el, selector) {
						jQuery(el).parent().find('.visual-footer').removeClass('active');
						jQuery(el).addClass('active');
						tinymce.remove('#'+selector);
						jQuery('#footer-editor').val('text');
					}
				</script>
				<?php do_action( 'wpsc_pt_get_template_settings' ); ?>
				<input type="hidden" name="action" value="wpsc_pt_set_template_settings">
				<input type="hidden" id="header-editor" name="header-editor" value="<?php echo esc_attr( $settings['header']['editor'] ); ?>">
				<input type="hidden" id="body-editor" name="body-editor" value="<?php echo esc_attr( $settings['body']['editor'] ); ?>">
				<input type="hidden" id="footer-editor" name="footer-editor" value="<?php echo esc_attr( $settings['footer']['editor'] ); ?>">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_pt_set_template_settings' ) ); ?>">
			</form>
			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_pt_set_template_settings(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_pt_reset_template_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_pt_reset_template_settings' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
			</div>
			<?php
			wp_die();
		}

		/**
		 * Set template settings
		 *
		 * @return void
		 */
		public static function set_template_settings() {

			if ( check_ajax_referer( 'wpsc_pt_set_template_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$header_text = isset( $_POST['header'] ) ? wp_kses_post( wp_unslash( $_POST['header'] ) ) : '';
			$header = array(
				'text'   => $header_text,
				'editor' => isset( $_POST['header-editor'] ) ? sanitize_text_field( wp_unslash( $_POST['header-editor'] ) ) : 'html',
			);

			$body_text = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : '';
			if ( ! $body_text ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$body = array(
				'text'   => $body_text,
				'editor' => isset( $_POST['body-editor'] ) ? sanitize_text_field( wp_unslash( $_POST['body-editor'] ) ) : 'html',
			);

			$footer_text = isset( $_POST['footer'] ) ? wp_kses_post( wp_unslash( $_POST['footer'] ) ) : '';
			$footer = array(
				'text'   => $footer_text,
				'editor' => isset( $_POST['footer-editor'] ) ? sanitize_text_field( wp_unslash( $_POST['footer-editor'] ) ) : 'html',
			);

			update_option(
				'wpsc-pt-template-settings',
				array(
					'header-height'    => isset( $_POST['header-height'] ) ? intval( $_POST['header-height'] ) : 100,
					'header-font-size' => isset( $_POST['header-font-size'] ) ? intval( $_POST['header-font-size'] ) : 14,
					'header'           => $header,
					'body'             => $body,
					'body-font-size'   => isset( $_POST['body-font-size'] ) ? intval( $_POST['body-font-size'] ) : 14,
					'footer-height'    => isset( $_POST['footer-height'] ) ? intval( $_POST['footer-height'] ) : 50,
					'footer-font-size' => isset( $_POST['footer-font-size'] ) ? intval( $_POST['footer-font-size'] ) : 14,
					'footer'           => $footer,
				)
			);

			do_action( 'wpsc_pt_set_template_settings' );

			// remove string translations.
			WPSC_Translations::remove( 'wpsc-pt-header' );
			WPSC_Translations::remove( 'wpsc-pt-body' );
			WPSC_Translations::remove( 'wpsc-pt-footer' );

			// add string translations.
			WPSC_Translations::add( 'wpsc-pt-header', $header_text );
			WPSC_Translations::add( 'wpsc-pt-body', $body_text );
			WPSC_Translations::add( 'wpsc-pt-footer', $footer_text );
			wp_die();
		}

		/**
		 * Reset template settings
		 *
		 * @return void
		 */
		public static function reset_template_settings() {

			if ( check_ajax_referer( 'wpsc_pt_reset_template_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			self::reset();

			do_action( 'wpsc_pt_reset_template_settings' );

			wp_die();
		}
	}
endif;

WPSC_PT_Settings_Template::init();
