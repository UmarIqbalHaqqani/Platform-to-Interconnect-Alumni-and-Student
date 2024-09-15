<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Settings_Automatic' ) ) :

	final class WPSC_WF_Settings_Automatic {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// filter conditions.
			add_filter( 'wpsc_wf_conditions', array( __CLASS__, 'filter_conditions' ) );

			// setting actions.
			add_action( 'wp_ajax_wpsc_wf_get_automatic_settings', array( __CLASS__, 'load_list' ) );

			// add new.
			add_action( 'wp_ajax_wpsc_wfa_get_add_workflow', array( __CLASS__, 'get_add_workflow' ) );
			add_action( 'wp_ajax_wpsc_wfa_set_add_workflow', array( __CLASS__, 'set_add_workflow' ) );

			// Enable/Disable.
			add_action( 'wp_ajax_wpsc_wfa_disable_workflow', array( __CLASS__, 'disable_workflow' ) );
			add_action( 'wp_ajax_wpsc_wfa_enable_workflow', array( __CLASS__, 'enable_workflow' ) );

			// edit.
			add_action( 'wp_ajax_wpsc_wfa_get_edit_workflow', array( __CLASS__, 'get_edit_workflow' ) );
			add_action( 'wp_ajax_wpsc_wfa_set_edit_workflow', array( __CLASS__, 'set_edit_workflow' ) );

			// clone.
			add_action( 'wp_ajax_wpsc_wfa_get_clone_workflow', array( __CLASS__, 'get_clone_workflow' ) );
			add_action( 'wp_ajax_wpsc_wfa_set_clone_workflow', array( __CLASS__, 'set_clone_workflow' ) );

			// Delete.
			add_action( 'wp_ajax_wpsc_wfa_delete_workflow', array( __CLASS__, 'delete_workflow' ) );
		}

		/**
		 * Filter conditions for email templates
		 *
		 * @param array $conditions - all possible ticket conditions.
		 * @return array
		 */
		public static function filter_conditions( $conditions ) {

			$ignore_list = apply_filters(
				'wpsc_wf_conditions_ignore_list',
				array(
					'cft'   => array( // custom field types.
						'cf_tutor_order',
						'cf_learnpress_order',
						'cf_lifter_order',
					),
					'other' => array(), // other(custom) condition slug.
				)
			);

			foreach ( $conditions as $slug => $item ) {

				if ( $item['type'] == 'cf' ) {

					$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
					if ( in_array( $cf->type::$slug, $ignore_list['cft'] ) ) {
						unset( $conditions[ $slug ] );
					}
				} else {

					if ( in_array( $slug, $ignore_list['other'] ) ) {
						unset( $conditions[ $slug ] );
					}
				}
			}

			return $conditions;
		}

		/**
		 * Load automatic workflows list
		 *
		 * @return void
		 */
		public static function load_list() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			?>
			<table class="wpsc-wf-list wpsc-setting-tbl">	
				<thead>
					<tr>
						<th><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></th>
						<th><?php echo esc_attr( wpsc__( 'Trigger', 'supportcandy' ) ); ?></th>
						<th><?php echo esc_attr( wpsc__( 'Status', 'supportcandy' ) ); ?></th>
						<th><?php echo esc_attr( wpsc__( 'Actions', 'supportcandy' ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $workflows as $id => $workflow ) {
						?>
						<tr>
							<td><?php echo esc_attr( $workflow['title'] ); ?></td>
							<td><?php echo isset( WPSC_Triggers::$triggers[ $workflow['trigger'] ] ) ? esc_attr( WPSC_Triggers::$triggers[ $workflow['trigger'] ] ) : ''; ?></td>
							<td><?php $workflow['status'] == 1 ? esc_html_e( 'Enabled', 'supportcandy' ) : esc_html_e( 'Disabled', 'supportcandy' ); ?></td>
							<td>
								<?php
								if ( $workflow['status'] ) {
									?>
									<a href="javascript:wpsc_wfa_disable_workflow('<?php echo esc_attr( $id ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_disable_workflow' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Disable', 'supportcandy' ) ); ?></a> |
									<?php
								} else {
									?>
									<a href="javascript:wpsc_wfa_enable_workflow('<?php echo esc_attr( $id ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_enable_workflow' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Enable', 'supportcandy' ) ); ?></a> |
									<?php
								}
								?>
								<a href="javascript:wpsc_wfa_get_edit_workflow('<?php echo esc_attr( $id ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_get_edit_workflow' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></a> |
								<a href="javascript:wpsc_wfa_get_clone_workflow('<?php echo esc_attr( $id ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_get_clone_workflow' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Clone', 'supportcandy' ) ); ?></a> |
								<a href="javascript:wpsc_wfa_delete_workflow('<?php echo esc_attr( $id ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_delete_workflow' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?></a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<script>
				jQuery('table.wpsc-wf-list').DataTable({
					ordering: false,
					pageLength: 20,
					bLengthChange: false,
					columnDefs: [ 
						{ targets: -1, searchable: false },
						{ targets: '_all', className: 'dt-left' }
					],
					dom: 'Bfrtip',
					buttons: [
						{
							text: '<?php echo esc_attr( wpsc__( 'Add new', 'supportcandy' ) ); ?>',
							className: 'wpsc-button small primary',
							action: function ( e, dt, node, config ) {

								wpsc_show_modal();
								var data = { action: 'wpsc_wfa_get_add_workflow', _ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_get_add_workflow' ) ); ?>' };
								jQuery.post(
									supportcandy.ajax_url,
									data,
									function (response) {
										// Set to modal.
										jQuery( '.wpsc-modal-header' ).text( response.title );
										jQuery( '.wpsc-modal-body' ).html( response.body );
										jQuery( '.wpsc-modal-footer' ).html( response.footer );
										// Display modal.
										wpsc_show_modal_inner_container();
									}
								);
							}
						}
					],
					language: supportcandy.translations.datatables
				});

			</script>
			<?php

			wp_die();
		}

		/**
		 * Get add workflow
		 *
		 * @return void
		 */
		public static function get_add_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_get_add_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$title = esc_attr( wpsc__( 'Add new', 'supportcandy' ) );

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-wf-settings">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="title" autocomplete="off">
				</div>

				<?php WPSC_Triggers::print( 'trigger', 'wpsc_wf_triggers', '', true ); ?>

				<input type="hidden" name="action" value="wpsc_wfa_set_add_workflow">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_set_add_workflow' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_wfa_set_add_workflow(this);">
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
		 * Set add workflow
		 *
		 * @return void
		 */
		public static function set_add_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_set_add_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( __( 'Title not given!', 'wpsc-workflows' ), 400 );
			}

			$trigger = isset( $_REQUEST['trigger'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['trigger'] ) ) : '';
			if ( ! $trigger || ! isset( WPSC_Triggers::$triggers[ $trigger ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$index = 1;
			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( $workflows ) {
				end( $workflows );
				$last_index = key( $workflows );
				reset( $workflows );
				$index = intval( $last_index ) + 1;
			}

			$workflows[ $index ] = array(
				'type'                  => 'automatic',
				'status'                => 0,
				'title'                 => $title,
				'current-user-operator' => 'any',
				'current-user'          => array(),
				'trigger'               => $trigger,
				'conditions'            => '',
				'actions'               => '',
			);

			update_option( 'wpsc-wf-automatic', $workflows );
			wp_send_json(
				array(
					'index' => $index,
					'nonce' => wp_create_nonce( 'wpsc_wfa_get_edit_workflow' ),
				),
				200
			);
		}

		/**
		 * Disable the workflow
		 *
		 * @return void
		 */
		public static function disable_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_disable_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflows[ $id ]['status'] = 0;
			update_option( 'wpsc-wf-automatic', $workflows );
			wp_die();
		}

		/**
		 * Enable the workflow
		 *
		 * @return void
		 */
		public static function enable_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_enable_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( new WP_Error( 'invalid_id', 'Bad request' ), 400 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( new WP_Error( 'invalid_id', 'Bad request' ), 400 );
			}

			if ( $workflows[ $id ]['title'] && $workflows[ $id ]['conditions'] || $workflows[ $id ]['actions'] ) {

				$workflows[ $id ]['status'] = 1;
				update_option( 'wpsc-wf-automatic', $workflows );

			} else {

				wp_send_json(
					array(
						'success' => false,
						'data'    => array(
							array(
								'code'    => 'incomplete',
								'message' => 'Incomplete workflow',
								'index'   => $id,
								'nonce'   => wp_create_nonce( 'wpsc_wfa_get_edit_workflow' ),
							),
						),
					),
					400
				);
			}
		}

		/**
		 * Get edit workflow
		 *
		 * @return void
		 */
		public static function get_edit_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_get_edit_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$roles = get_option( 'wpsc-agent-roles' );
			$current_user = array( 'customer' => wpsc__( 'Customer', 'supportcandy' ) );
			foreach ( $roles as $key => $role ) {
				$current_user[ $key ] = $role['label'];
			}

			$workflow = $workflows[ $id ];
			?>

			<form action="#" onsubmit="return false;" class="wpsc-wf-settings">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="title" value="<?php echo esc_attr( $workflow['title'] ); ?>" autocomplete="off">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Current user', 'wpsc-workflows' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<select class="current-user-operator" name="current-user-operator" style="margin-bottom: 5px;">
						<option <?php selected( 'any', $workflow['current-user-operator'], true ); ?> value="any"><?php esc_attr_e( 'Any', 'wpsc-workflows' ); ?></option>
						<option <?php selected( 'matches', $workflow['current-user-operator'], true ); ?> value="matches"><?php echo esc_attr( wpsc__( 'Matches', 'supportcandy' ) ); ?></option>
						<option <?php selected( 'not-matches', $workflow['current-user-operator'], true ); ?> value="not-matches"><?php echo esc_attr( wpsc__( 'Not Matches', 'supportcandy' ) ); ?></option>
					</select>
					<div class="custom-roles" style="margin-top: 5px; <?php echo $workflow['current-user-operator'] == 'any' ? 'display:none;' : ''; ?>">
						<select class="current-user" multiple name="current-user[]">
							<?php
							foreach ( $current_user as $key => $role ) {
								$selected = in_array( $key, $workflow['current-user'] ) ? 'selected' : '';
								?>
								<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $role ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<script>
						jQuery( 'select.current-user-operator' ).change( function(){
							if ( jQuery(this).val() == 'any' ) {
								jQuery( 'div.custom-roles' ).hide();
							} else {
								jQuery( 'div.custom-roles' ).show();
							}
						});
						jQuery( 'select.current-user' ).selectWoo();
					</script>
				</div>

				<?php WPSC_Triggers::print( 'trigger', 'wpsc_wf_triggers', $workflow['trigger'], true ); ?>

				<?php WPSC_Ticket_Conditions::print( 'conditions', 'wpsc_wf_conditions', $workflow['conditions'], true ); ?>

				<?php WPSC_WF_Actions::print( $workflow['actions'] ); ?>

				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<input type="hidden" name="action" value="wpsc_wfa_set_edit_workflow">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_set_edit_workflow' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary"
					onclick="wpsc_wfa_set_edit_workflow(this);">
					<?php $workflow['status'] == 1 ? esc_html_e( 'Submit', 'supportcandy' ) : esc_html_e( 'Enable', 'supportcandy' ); ?>
				</button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_wf_get_automatic_settings();">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
				</button>
			</div>
			<?php

			wp_die();
		}

		/**
		 * Set edit workflow
		 *
		 * @return void
		 */
		public static function set_edit_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_set_edit_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflow = $workflows[ $id ];

			$title = isset( $_REQUEST['title'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( __( 'Title not given!', 'wpsc-workflows' ), 400 );
			}
			$workflow['title'] = $title;

			$workflow['current-user-operator'] = isset( $_POST['current-user-operator'] ) ? sanitize_text_field( wp_unslash( $_POST['current-user-operator'] ) ) : 'any';
			$workflow['current-user-operator'] = in_array( $workflow['current-user-operator'], array( 'any', 'matches', 'not-matches' ) ) ? $workflow['current-user-operator'] : 'any';

			$workflow['current-user'] = isset( $_POST['current-user'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['current-user'] ) ) ) : array();
			if ( $workflow['current-user-operator'] == 'any' ) {
				$workflow['current-user'] = array();
			}

			if ( $workflow['current-user-operator'] != 'any' && ! $workflow['current-user'] ) {
				wp_send_json_error( __( 'Please select current user!', 'wpsc-workflows' ), 400 );
			}

			$trigger = isset( $_REQUEST['trigger'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['trigger'] ) ) : '';
			if ( ! $trigger || ! isset( WPSC_Triggers::$triggers[ $trigger ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$workflow['trigger'] = $trigger;

			$conditions = isset( $_POST['conditions'] ) ? sanitize_text_field( wp_unslash( $_POST['conditions'] ) ) : '';
			if ( ! $conditions || $conditions == '[]' || ! WPSC_Ticket_Conditions::is_valid_input_conditions( 'wpsc_wf_conditions', $conditions ) ) {
				wp_send_json_error( __( 'Conditions not set!', 'wpsc-workflows' ), 400 );
			}
			$workflow['conditions'] = $conditions;

			// We will sanitize the actions data in subsquent action class.
			$actions = isset( $_POST['actions'] ) ? $_POST['actions'] : array(); // phpcs:ignore
			if ( $actions ) {
				foreach ( $actions as $slug => $action ) {
					$slug = sanitize_key( $slug );
					if ( ! isset( WPSC_WF_Actions::$actions[ $slug ] ) || ! class_exists( WPSC_WF_Actions::$actions[ $slug ]['class'] ) ) {
						unset( $actions[ $slug ] );
						continue;
					}
					$action = WPSC_WF_Actions::$actions[ $slug ]['class']::sanitize_action( $action );
					if ( $action ) {
						$actions[ $slug ] = $action;
					} else {
						unset( $actions[ $slug ] );
					}
				}
			}
			if ( ! $actions ) {
				wp_send_json_error( __( 'Actions not set!', 'wpsc-workflows' ), 400 );
			}

			$workflow['actions'] = wp_json_encode( $actions );
			$workflow['status'] = 1;

			$workflows[ $id ] = $workflow;
			update_option( 'wpsc-wf-automatic', $workflows );
			wp_die();
		}

		/**
		 * Get clone modal response
		 *
		 * @return void
		 */
		public static function get_clone_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_get_clone_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflow = $workflows[ $id ];

			$title = esc_attr__( 'Clone workflow', 'wpsc-workflows' );

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-wf-settings">

				<?php $title = $workflow['title'] . ' clone'; ?>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="title" value="<?php echo esc_attr( $title ); ?>" autocomplete="off">
				</div>

				<?php WPSC_Triggers::print( 'trigger', 'wpsc_wf_triggers', $workflow['trigger'], true ); ?>

				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<input type="hidden" name="action" value="wpsc_wfa_set_clone_workflow">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_wfa_set_clone_workflow' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_wfa_set_clone_workflow(this);">
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
		 * Set clone workflow
		 *
		 * @return void
		 */
		public static function set_clone_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_set_clone_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( __( 'Title not given!', 'wpsc-workflows' ), 400 );
			}

			$trigger = isset( $_REQUEST['trigger'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['trigger'] ) ) : '';
			if ( ! $trigger || ! isset( WPSC_Triggers::$triggers[ $trigger ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflow = $workflows[ $id ];
			$workflow['title'] = $title;
			$workflow['trigger'] = $trigger;
			$workflow['status'] = 0;
			$workflows[] = $workflow;

			end( $workflows );
			$index = key( $workflows );

			update_option( 'wpsc-wf-automatic', $workflows );
			wp_send_json(
				array(
					'index' => $index,
					'nonce' => wp_create_nonce( 'wpsc_wfa_get_edit_workflow' ),
				),
				200
			);
		}

		/**
		 * Delete workflow
		 *
		 * @return void
		 */
		public static function delete_workflow() {

			if ( check_ajax_referer( 'wpsc_wfa_delete_workflow', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			unset( $workflows[ $id ] );
			update_option( 'wpsc-wf-automatic', $workflows );
			wp_die();
		}
	}
endif;

WPSC_WF_Settings_Automatic::init();
