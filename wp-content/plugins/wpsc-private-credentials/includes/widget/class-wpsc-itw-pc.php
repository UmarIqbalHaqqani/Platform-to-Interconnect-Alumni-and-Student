<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ITW_PC' ) ) :

	final class WPSC_ITW_PC {

		/**
		 * Initialization
		 *
		 * @return void
		 */
		public static function init() {

			// edit widget settings.
			add_action( 'wp_ajax_wpsc_get_tw_pc', array( __CLASS__, 'get_tw_pc' ) );
			add_action( 'wp_ajax_wpsc_set_tw_pc', array( __CLASS__, 'set_tw_pc' ) );

			// add new private credentials.
			add_action( 'wp_ajax_wpsc_add_new_pc', array( __CLASS__, 'add_new_pc' ) );
			add_action( 'wp_ajax_nopriv_wpsc_add_new_pc', array( __CLASS__, 'add_new_pc' ) );
			add_action( 'wp_ajax_wpsc_set_new_pc', array( __CLASS__, 'set_new_pc' ) );
			add_action( 'wp_ajax_nopriv_wpsc_set_new_pc', array( __CLASS__, 'set_new_pc' ) );

			// widget actions.
			add_action( 'wp_ajax_wpsc_view_pc_data', array( __CLASS__, 'view_pc_data' ), 10, 2 );
			add_action( 'wp_ajax_nopriv_wpsc_view_pc_data', array( __CLASS__, 'view_pc_data' ), 10, 2 );
			add_action( 'wp_ajax_wpsc_edit_pc_data', array( __CLASS__, 'edit_pc_data' ) );
			add_action( 'wp_ajax_nopriv_wpsc_edit_pc_data', array( __CLASS__, 'edit_pc_data' ) );
			add_action( 'wp_ajax_wpsc_set_edit_pc_data', array( __CLASS__, 'set_edit_pc_data' ) );
			add_action( 'wp_ajax_nopriv_wpsc_set_edit_pc_data', array( __CLASS__, 'set_edit_pc_data' ) );

			add_action( 'wp_ajax_wpsc_delete_pc_data', array( __CLASS__, 'delete_pc_data' ) );
			add_action( 'wp_ajax_nopriv_wpsc_delete_pc_data', array( __CLASS__, 'delete_pc_data' ) );

			// delete all PC when ticket is closed.
			add_action( 'wpsc_change_ticket_status', array( __CLASS__, 'delete_all_pc_data' ) );
		}

		/**
		 * Print body of current widget
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param array       $settings - setting array.
		 * @return void
		 */
		public static function print_widget( $ticket, $settings ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_guest ||
				( $current_user->is_agent && ! WPSC_Individual_Ticket::has_ticket_cap( 'view-pc' ) ) ||
				( ! $current_user->is_agent && $ticket->customer->email != $current_user->customer->email )
				) {
				return;
			}?>

			<div class="wpsc-it-widget">
				<div class="wpsc-widget-header">
					<h2><?php echo esc_attr( $settings['title'] ); ?></h2>
					<?php if ( WPSC_Individual_Ticket::$view_profile == 'customer' || ( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'modify-pc' ) ) ) { ?>  
						<span onclick="wpsc_add_new_pc(<?php echo esc_attr( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_add_new_pc' ) ); ?>')"><?php WPSC_Icons::get( 'plus' ); ?></span>
					<?php } ?>
				</div>
				<div class="wpsc-widget-body">
					<?php
					$pc_data = json_decode( $ticket->pc_data, JSON_OBJECT_AS_ARRAY );
					if ( isset( $pc_data['data'] ) && ! empty( $pc_data['data'] ) ) {
						foreach ( $pc_data['data'] as $key => $value ) {
							?>
							<div class="wpsc-link" style="margin-bottom: 10px;" onclick="wpsc_view_pc_data(<?php echo esc_attr( $ticket->id ); ?>, <?php echo esc_attr( $key ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_view_pc_data' ) ); ?>');"><?php echo esc_attr( $value['title'] ); ?></div>
							<?php
						}
					} else {
						?>
						<div class="wpsc-widget-default"><?php echo esc_attr( wpsc__( 'Not Applicable', 'supportcandy' ) ); ?></div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Add/edit PC title
		 *
		 * @return void
		 */
		public static function get_tw_pc() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$ticket_widgets = get_option( 'wpsc-ticket-widget', array() );
			$pc             = $ticket_widgets['pc'];
			$title          = $pc['title'];
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-pc">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
					</div>
					<input name="label" type="text" value="<?php echo esc_attr( $pc['title'] ); ?>" autocomplete="off">
				</div>
				<input type="hidden" name="action" value="wpsc_set_tw_pc">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_tw_pc' ) ); ?>">
			</form>
			<?php

			$body = ob_get_clean();
			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_tw_pc(this);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php

			$footer   = ob_get_clean();
			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Save PC lable
		 *
		 * @return void
		 */
		public static function set_tw_pc() {

			if ( check_ajax_referer( 'wpsc_set_tw_pc', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $label ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$ticket_widgets                = get_option( 'wpsc-ticket-widget', array() );
			$ticket_widgets['pc']['title'] = $label;
			update_option( 'wpsc-ticket-widget', $ticket_widgets );
			wp_die();
		}

		/**
		 * Add new private credential
		 *
		 * @return void
		 */
		public static function add_new_pc() {

			if ( check_ajax_referer( 'wpsc_add_new_pc', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$ticket = WPSC_Individual_Ticket::$ticket;

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent && ( ! WPSC_Individual_Ticket::has_ticket_cap( 'modify-pc' ) ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$title = esc_attr( wpsc__( 'Add new', 'supportcandy' ) );
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-add-new-pc">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
					</div>
					<input name="title" type="text" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Fields', 'wpsc-pc' ); ?></label>
					</div>
					<div class="wpsc-form-filter-container"></div>
					<div style="padding-bottom: 10px;">
						<button class="wpsc-button small secondary wpsc-form-filter-add-btn" onclick="wpsc_add_textfield();">
							<?php esc_attr_e( '+ Textfield', 'wpsc-pc' ); ?>
						</button>
						<button class="wpsc-button small secondary wpsc-form-filter-add-btn" onclick="wpsc_add_textarea();">
							<?php esc_attr_e( '+ Textarea', 'wpsc-pc' ); ?>
						</button>
					</div>
					<div class="wpsc-form-textfield-snippet" style="display:none;">
						<div class="wpsc-form-filter-item" style="align-items: start;">
							<div class="content">
								<div class="item" style="width: 100%; max-width: 100%;">
									<input type="text" name="pc_label[]" value="" placeholder="<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>">
								</div>
							</div>
							<div class="content" style="flex-grow: 1;">
								<div class="item" style="width: 100%; max-width: 100%;">
									<input type="text" name="pc_value[]" value="" placeholder="<?php esc_attr_e( 'Value', 'supportcandy' ); ?>">
								</div>
							</div>
							<div class="remove-container" style="flex-grow: 0;">
								<span onclick="wpsc_remove_form_filter_item(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
								<input type="hidden" name="pc_type[]" value="text">
							</div>
						</div>
					</div>
					<div class="wpsc-form-textarea-snippet" style="display:none;">
						<div class="wpsc-form-filter-item" style="align-items: start;">
							<div class="content">
								<div class="item" style="width: 100%; max-width: 100%;">
									<input type="text" name="pc_label[]" value="" placeholder="<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>">
								</div>
							</div>
							<div class="content" style="flex-grow: 1;">
								<div class="item" style="width: 100%; max-width: 100%;">
									<textarea name="pc_value[]" placeholder="<?php esc_attr_e( 'Value', 'supportcandy' ); ?>" rows="4" style="width: 100%;"></textarea>
								</div>
							</div>
							<div class="remove-container" style="flex-grow: 0;">
								<span onclick="wpsc_remove_form_filter_item(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
								<input type="hidden" name="pc_type[]" value="textarea">
							</div>
						</div>
					</div>
				</div>

				<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
				<input type="hidden" name="action" value="wpsc_set_new_pc">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_new_pc' ) ); ?>">
			</form>
			<?php

			$body = ob_get_clean();
			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_new_pc(this, <?php echo esc_attr( $ticket->id ); ?>);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php

			$footer   = ob_get_clean();
			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response, 200 );
		}

		/**
		 * Set new private credential
		 *
		 * @return void
		 */
		public static function set_new_pc() {

			if ( check_ajax_referer( 'wpsc_set_new_pc', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$ticket = WPSC_Individual_Ticket::$ticket;

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent && ( ! WPSC_Individual_Ticket::has_ticket_cap( 'modify-pc' ) ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$pc_label = isset( $_POST['pc_label'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['pc_label'] ) ) ) : array();
			if ( ! $pc_label ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$pc_type = isset( $_POST['pc_type'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['pc_type'] ) ) ) : array();
			if ( ! $pc_type ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			if ( isset( $_POST['pc_value'] ) ) {
				$pc_value = array_filter(
					array_map(
						function( $value, $type ) {
							return $type == 'textarea' ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
						},
						wp_unslash( $_POST['pc_value'] ), // phpcs:ignore
						$pc_type
					)
				);
			} else {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$cipher       = 'AES-128-CBC';
			$private_data = $ticket->pc_data;
			$private_data = $private_data ? json_decode( $private_data, JSON_OBJECT_AS_ARRAY ) : array();

			if ( ! ( isset( $private_data['secure_key'] ) && isset( $private_data['secure_iv'] ) ) ) {

				$ekey_size  = 32; // 256 bits
				$secure_key = openssl_random_pseudo_bytes( $ekey_size, $strong );
				$ivlen      = openssl_cipher_iv_length( $cipher );
				$secure_iv  = openssl_random_pseudo_bytes( $ivlen );

				$private_data['secure_key'] = base64_encode( $secure_key );
				$private_data['secure_iv']  = base64_encode( $secure_iv );
				$private_data['data']       = array();
			} else {
				$secure_key = base64_decode( $private_data['secure_key'] );
				$secure_iv  = base64_decode( $private_data['secure_iv'] );
			}

			$data           = array();
			$pc_label_count = count( $pc_label );
			for ( $i = 0; $i < $pc_label_count; $i++ ) {

				if ( ! ( $pc_label[ $i ] || $pc_value[ $i ] ) ) {
					continue;
				}

				$data[] = array(
					'label' => $pc_label[ $i ],
					'value' => $pc_value[ $i ],
					'type'  => $pc_type[ $i ],
				);
			}

			$pc           = array( 'title' => $title );
			$encript_data = array();
			foreach ( $data as $key => $value ) {

				$enc_value      = openssl_encrypt( stripcslashes( $value['value'] ), $cipher, $secure_key, $options = 0, $secure_iv );
				$edata          = array(
					'label' => $value['label'],
					'value' => base64_encode( $enc_value ),
					'type'  => $value['type'],
				);
				$encript_data[] = $edata;
			}

			$pc['data'] = $encript_data;

			if ( ! count( $private_data['data'] ) ) {
				$private_data['data'][1] = $pc;
			} else {
				$private_data['data'][] = $pc;
			}

			$ticket->pc_data = wp_json_encode( $private_data );
			$ticket->save();
			wp_die();
		}

		/**
		 * View private credentials
		 *
		 * @return void
		 */
		public static function view_pc_data() {

			if ( check_ajax_referer( 'wpsc_view_pc_data', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			$ticket_id = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
			$pc_key    = isset( $_POST['key'] ) ? intval( $_POST['key'] ) : 0;
			if ( ! ( $ticket_id && $pc_key ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$ticket = WPSC_Individual_Ticket::$ticket;

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent && ( ! WPSC_Individual_Ticket::has_ticket_cap( 'view-pc' ) ) ) {
				return;
			}

			$pc_data = json_decode( $ticket->pc_data, JSON_OBJECT_AS_ARRAY );

			$cipher     = 'AES-128-CBC';
			$secure_key = base64_decode( $pc_data['secure_key'] );
			$secure_iv  = base64_decode( $pc_data['secure_iv'] );

			$title = $pc_data['data'][ $pc_key ]['title'];

			ob_start();
			?>
			<div style="flex-grow: 1;">
				<table class="credentials wpsc-setting-tbl" style="width: 100%;">
					<thead>
						<tr>
							<th><?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?></th>
							<th><?php esc_attr_e( 'Value', 'Value' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $pc_data['data'][ $pc_key ]['data'] as $key => $value ) {
							$decode_value   = base64_decode( $value['value'] );
							$descrypt_value = openssl_decrypt( $decode_value, $cipher, $secure_key, $options = 0, $secure_iv );
							?>
							<tr>
								<td><?php echo esc_attr( $value['label'] ); ?></td>
								<td><?php echo wp_kses_post( nl2br( $descrypt_value ) ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
			<script>
				jQuery('table.credentials').DataTable({
					ordering: false,
					pageLength: 20,
					bLengthChange: false,
					columnDefs: [ 
						{ targets: '_all', className: 'dt-left' }
					],
					language: supportcandy.translations.datatables
				});
			</script>
			<?php
			$body = ob_get_clean();

			ob_start();
			if ( WPSC_Individual_Ticket::$view_profile == 'customer' || ( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'modify-pc' ) ) ) {
				?>
				<button class="wpsc-button small primary" onclick="wpsc_edit_pc_data(<?php echo esc_attr( $ticket->id ); ?>, <?php echo esc_attr( $pc_key ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_edit_pc_data' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?>
				</button>
				<?php
			}
			if ( WPSC_Individual_Ticket::$view_profile == 'customer' || ( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'delete-pc' ) ) ) {
				?>
				<button class="wpsc-button small primary" onclick="wpsc_delete_pc_data(<?php echo esc_attr( $ticket->id ); ?>, <?php echo esc_attr( $pc_key ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_pc_data' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?>
				</button>
				<?php
			}
			?>
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
		 * Edit PC data
		 *
		 * @return void
		 */
		public static function edit_pc_data() {

			if ( check_ajax_referer( 'wpsc_edit_pc_data', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			$ticket_id = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
			$pc_key    = isset( $_POST['key'] ) ? intval( $_POST['key'] ) : 0;
			if ( ! ( $ticket_id && $pc_key ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$ticket = WPSC_Individual_Ticket::$ticket;

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent && ( ! WPSC_Individual_Ticket::has_ticket_cap( 'modify-pc' ) ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$pc_data = json_decode( $ticket->pc_data, JSON_OBJECT_AS_ARRAY );

			$cipher     = 'AES-128-CBC';
			$secure_key = base64_decode( $pc_data['secure_key'] );
			$secure_iv  = base64_decode( $pc_data['secure_iv'] );

			$title = esc_attr__( 'Edit credential', 'wpsc-pc' );
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-add-edited-pc">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
					</div>
					<input name="title" type="text" value="<?php echo esc_attr( $pc_data['data'][ $pc_key ]['title'] ); ?>" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Fields', 'wpsc-pc' ); ?></label>
					</div>
					<?php
					foreach ( $pc_data['data'][ $pc_key ]['data'] as $key => $value ) {
						$label         = $value['label'];
						$decode_value  = base64_decode( $value['value'] );
						$descrypt_name = openssl_decrypt( $decode_value, $cipher, $secure_key, $options = 0, $secure_iv );

						if ( $value['type'] == 'textarea' ) {
							?>
							<div class="wpsc-form-filter-item" style="align-items: start;">
								<div class="content">
									<div class="item" style="width: 100%; max-width: 100%;">
										<input type="text" name="pc_label[]" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>">
									</div>
								</div>
								<div class="content" style="flex-grow: 1;">
									<div class="item" style="width: 100%; max-width: 100%;">
										<textarea name="pc_value[]" placeholder="<?php esc_attr_e( 'Value', 'supportcandy' ); ?>" rows="4" style="width: 100%;"><?php echo esc_attr( $descrypt_name ); ?></textarea>
									</div>
								</div>
								<div class="remove-container" style="flex-grow: 0;">
									<span onclick="wpsc_remove_form_filter_item(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
									<input type="hidden" name="pc_type[]" value="textarea">
								</div>
							</div>
							<?php
						}

						if ( $value['type'] == 'text' ) {
							?>
							<div class="wpsc-form-filter-item" style="align-items: start;">
								<div class="content">
									<div class="item" style="width: 100%; max-width: 100%;">
										<input type="text" name="pc_label[]" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>">
									</div>
								</div>
								<div class="content" style="flex-grow: 1;">
									<div class="item" style="width: 100%; max-width: 100%;">
										<input type="text" name="pc_value[]" value="<?php echo esc_attr( $descrypt_name ); ?>" placeholder="<?php esc_attr_e( 'Value', 'supportcandy' ); ?>">
									</div>
								</div>
								<div class="remove-container" style="flex-grow: 0;">
									<span onclick="wpsc_remove_form_filter_item(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
									<input type="hidden" name="pc_type[]" value="text">
								</div>
							</div>
							<?php
						}
					}
					?>

					<div class="wpsc-form-filter-container"></div>
					<div style="padding-bottom: 10px;">
						<button class="wpsc-button small secondary wpsc-form-filter-add-btn" onclick="wpsc_add_textfield();">
							<?php esc_attr_e( '+ Textfield', 'wpsc-pc' ); ?>
						</button>
						<button class="wpsc-button small secondary wpsc-form-filter-add-btn" onclick="wpsc_add_textarea();">
							<?php esc_attr_e( '+ Textarea', 'wpsc-pc' ); ?>
						</button>
					</div>
					<div class="wpsc-form-textfield-snippet" style="display:none;">
						<div class="wpsc-form-filter-item" style="align-items: start;">
							<div class="content">
								<div class="item" style="width: 100%; max-width: 100%;">
									<input type="text" name="pc_label[]" value="" placeholder="<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>">
								</div>
							</div>
							<div class="content" style="flex-grow: 1;">
								<div class="item" style="width: 100%; max-width: 100%;">
									<input type="text" name="pc_value[]" value="" placeholder="<?php esc_attr_e( 'Value', 'supportcandy' ); ?>">
								</div>
							</div>
							<div class="remove-container" style="flex-grow: 0;">
								<span onclick="wpsc_remove_form_filter_item(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
								<input type="hidden" name="pc_type[]" value="text">
							</div>
						</div>
					</div>
					<div class="wpsc-form-textarea-snippet" style="display:none;">
						<div class="wpsc-form-filter-item" style="align-items: start;">
							<div class="content">
								<div class="item" style="width: 100%; max-width: 100%;">
									<input type="text" name="pc_label[]" value="" placeholder="<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>">
								</div>
							</div>
							<div class="content" style="flex-grow: 1;">
								<div class="item" style="width: 100%; max-width: 100%;">
									<textarea name="pc_value[]" placeholder="<?php esc_attr_e( 'Value', 'supportcandy' ); ?>" rows="4" style="width: 100%;"></textarea>
								</div>
							</div>
							<div class="remove-container" style="flex-grow: 0;">
								<span onclick="wpsc_remove_form_filter_item(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
								<input type="hidden" name="pc_type[]" value="textarea">
							</div>
						</div>
					</div>
				</div>
				<input type="hidden" name="pc_key" value="<?php echo esc_attr( $pc_key ); ?>">
				<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
				<input type="hidden" name="action" value="wpsc_set_edit_pc_data">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_edit_pc_data' ) ); ?>">
			</form>
			<?php

			$body = ob_get_clean();
			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_edit_pc_data(this, <?php echo esc_attr( $ticket->id ); ?>);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
			</button>
			<?php
			if ( WPSC_Individual_Ticket::$view_profile == 'customer' || ( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'delete-pc' ) ) ) {
				?>
				<button class="wpsc-button small secondary" onclick="wpsc_delete_pc_data(<?php echo esc_attr( $ticket->id ); ?>, <?php echo esc_attr( $pc_key ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_pc_data' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?>
				</button>
				<?php
			}
			?>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php

			$footer   = ob_get_clean();
			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response, 200 );
		}

		/**
		 * Save edited PC data
		 *
		 * @return void
		 */
		public static function set_edit_pc_data() {

			if ( check_ajax_referer( 'wpsc_set_edit_pc_data', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$ticket = WPSC_Individual_Ticket::$ticket;

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent && ( ! WPSC_Individual_Ticket::has_ticket_cap( 'modify-pc' ) ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$pc_data = json_decode( $ticket->pc_data, JSON_OBJECT_AS_ARRAY );

			$pc_key = isset( $_POST['pc_key'] ) ? intval( $_POST['pc_key'] ) : 0;
			if ( ! $pc_key ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$pc_label = isset( $_POST['pc_label'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['pc_label'] ) ) ) : array();
			if ( ! $pc_label ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$pc_type = isset( $_POST['pc_type'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['pc_type'] ) ) ) : array();
			if ( ! $pc_type ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			if ( isset( $_POST['pc_value'] ) ) {
				$pc_value = array_filter(
					array_map(
						function( $value, $type ) {
							return $type == 'textarea' ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
						},
						wp_unslash( $_POST['pc_value'] ), // phpcs:ignore
						$pc_type
					)
				);
			} else {
				wp_send_json_error( 'Bad Request', 400 );
			}

			unset( $pc_data['data'][ $pc_key ] );

			$cipher       = 'AES-128-CBC';
			$private_data = $ticket->pc_data;
			$private_data = $private_data ? $pc_data : array();
			$secure_key   = base64_decode( $private_data['secure_key'] );
			$secure_iv    = base64_decode( $private_data['secure_iv'] );

			$data           = array();
			$count_pc_label = count( $pc_label );
			for ( $i = 0; $i < $count_pc_label; $i++ ) {

				if ( ! ( $pc_label[ $i ] || $pc_value[ $i ] ) ) {
					continue;
				}

				$data[] = array(
					'label' => $pc_label[ $i ],
					'value' => $pc_value[ $i ],
					'type'  => $pc_type[ $i ],
				);
			}

			$pc           = array( 'title' => $title );
			$encript_data = array();
			foreach ( $data as $key => $value ) {

				$enc_value      = openssl_encrypt( stripcslashes( $value['value'] ), $cipher, $secure_key, $options = 0, $secure_iv );
				$edata          = array(
					'label' => $value['label'],
					'value' => base64_encode( $enc_value ),
					'type'  => $value['type'],
				);
				$encript_data[] = $edata;
			}

			$pc['data'] = $encript_data;

			if ( ! count( $private_data['data'] ) ) {
				$private_data['data'][1] = $pc;
			} else {
				$private_data['data'][] = $pc;
			}

			$ticket->pc_data = wp_json_encode( $private_data );
			$ticket->save();

			wp_die();
		}

		/**
		 * Delete PC data
		 *
		 * @return void
		 */
		public static function delete_pc_data() {

			if ( check_ajax_referer( 'wpsc_delete_pc_data', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$ticket = WPSC_Individual_Ticket::$ticket;

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent && ( ! WPSC_Individual_Ticket::has_ticket_cap( 'delete-pc' ) ) ) {
				return;
			}

			$pc_data = json_decode( $ticket->pc_data, JSON_OBJECT_AS_ARRAY );

			$key = isset( $_POST['key'] ) ? intval( $_POST['key'] ) : 0;
			if ( ! $key ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			unset( $pc_data['data'][ $key ] );

			$ticket->pc_data = wp_json_encode( $pc_data );
			$ticket->save();
			wp_die();
		}

		/**
		 * Delete all PC when ticket is closed
		 *
		 * @param object $ticket - ticket object.
		 * @return void
		 */
		public static function delete_all_pc_data( $ticket ) {

			$ticket_status        = $ticket->status->id;
			$settings             = get_option( 'wpsc-gs-general' );
			$closed_ticket_status = $settings['close-ticket-status'];
			$pc_data              = json_decode( $ticket->pc_data, JSON_OBJECT_AS_ARRAY );
			if ( ! $pc_data ) {
				return;
			}

			$pc_keys = array_keys( $pc_data['data'] );

			if ( $ticket_status == $closed_ticket_status ) {

				if ( isset( $pc_keys ) && ! empty( $pc_keys ) ) {

					foreach ( $pc_keys as $key ) {

						unset( $pc_data['data'][ $key ] );
						$ticket->pc_data = wp_json_encode( $pc_data );
						$ticket->save();
					}
				}
			}
		}
	}
endif;

WPSC_ITW_PC::init();
