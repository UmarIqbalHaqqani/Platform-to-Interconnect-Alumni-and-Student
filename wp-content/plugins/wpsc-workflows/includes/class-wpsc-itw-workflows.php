<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ITW_Workflows' ) ) :

	final class WPSC_ITW_Workflows {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// trigger workflow.
			add_action( 'wp_ajax_wpsc_itw_trigger_workflow', array( __CLASS__, 'trigger_workflow' ) );
			add_action( 'wp_ajax_noprev_wpsc_itw_trigger_workflow', array( __CLASS__, 'trigger_workflow' ) );

			// widget settings.
			add_action( 'wp_ajax_wpsc_get_tw_workflows', array( __CLASS__, 'get_widget_settings' ) );
			add_action( 'wp_ajax_wpsc_set_tw_workflows', array( __CLASS__, 'set_widget_settings' ) );
		}

		/**
		 * Prints body of current widget
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param array       $settings - widget settings.
		 * @return void
		 */
		public static function print_widget( $ticket, $settings ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ( WPSC_Individual_Ticket::$view_profile == 'agent' && ! WPSC_Individual_Ticket::has_ticket_cap( 'workflows' ) ) || $current_user->is_guest ) {
				return;
			}

			$ids = array();
			$workflows = get_option( 'wpsc-wf-manual', array() );
			foreach ( $workflows as $id => $workflow ) {

				if (
					! $workflow['status'] ||
					! self::is_valid_current_user( $workflow ) ||
					! WPSC_Ticket_Conditions::is_valid( $workflow['conditions'], $ticket )
				) {
					continue;
				}

				$ids[] = $id;
			}

			// do not print widget if there are no applicable workflows.
			if ( ! $ids ) {
				return;
			}
			?>

			<div class="wpsc-it-widget wpsc-itw-workflows">
				<div class="wpsc-widget-header">
					<h2>
						<?php
						$settings_title = $settings['title'] ? WPSC_Translations::get( 'wpsc-twt-ticket-fields', stripslashes( $settings['title'] ) ) : stripslashes( $settings['title'] );
						echo esc_attr( $settings_title )
						?>
					</h2>
				</div>
				<div class="wpsc-widget-body">
					<?php
					foreach ( $workflows as $id => $workflow ) {
						if ( in_array( $id, $ids ) ) {
							?>
							<div
								class="wpsc-link"
								onclick="wpsc_itw_trigger_workflow(<?php echo intval( $id ); ?>, <?php echo intval( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_itw_trigger_workflow_' . $id . '_' . $ticket->id ) ); ?>')"
								style="margin-bottom: 5px;"
								>
								<?php echo esc_attr( $workflow['title'] ); ?>
							</div>
							<?php
						}
					}
					do_action( 'wpsc_itw_workflows', $ticket )
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Trigger workflow
		 *
		 * @return void
		 */
		public static function trigger_workflow() {

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			$ticket_id = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
			if ( ! $id || ! $ticket_id ) {
				wp_send_json_error( 'Bad request!', 400 );
			}

			if ( check_ajax_referer( 'wpsc_itw_trigger_workflow_' . $id . '_' . $ticket_id, '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			if ( WPSC_Individual_Ticket::$view_profile == 'agent' && ! WPSC_Individual_Ticket::has_ticket_cap( 'workflows' ) ) {
				wp_send_json_error( 'Unauthorized!', 400 );
			}

			$ticket = WPSC_Individual_Ticket::$ticket;
			$workflows = get_option( 'wpsc-wf-manual', array() );
			if ( ! isset( $workflows[ $id ] ) ) {
				wp_send_json_error( 'Bad request!', 400 );
			}

			$workflow = $workflows[ $id ];
			if (
				! $workflow['status'] ||
				! self::is_valid_current_user( $workflow ) ||
				! WPSC_Ticket_Conditions::is_valid( $workflow['conditions'], $ticket )
			) {
				wp_send_json_error( 'Bad request!', 400 );
			}

			// execute actions.
			WPSC_Individual_Ticket::$reply_profile = 'agent';
			WPSC_WF_Actions::execute( $workflow['actions'], $ticket, $workflow );
			wp_die();
		}

		/**
		 * Check whether or not current user is allowed to trigger supplied workflow
		 *
		 * @param array $workflow - workflow settings.
		 * @return boolean
		 */
		public static function is_valid_current_user( $workflow ) {

			$current_user = WPSC_Current_User::$current_user;
			$flag = false;

			switch ( $workflow['current-user-operator'] ) {

				case 'any':
					$flag = true;
					break;

				case 'matches':
					if (
						( WPSC_Individual_Ticket::$view_profile == 'agent' && in_array( $current_user->agent->role, $workflow['current-user'] ) ) ||
						( WPSC_Individual_Ticket::$view_profile == 'customer' && in_array( 'customer', $workflow['current-user'] ) )
					) {
						$flag = true;
					}
					break;

				case 'not-matches':
					if ( ! (
						( WPSC_Individual_Ticket::$view_profile == 'agent' && in_array( $current_user->agent->role, $workflow['current-user'] ) ) ||
						( WPSC_Individual_Ticket::$view_profile == 'customer' && in_array( 'customer', $workflow['current-user'] ) )
					) ) {
						$flag = true;
					}
					break;
			}

			return $flag;
		}

		/**
		 * Get widget settings
		 */
		public static function get_widget_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$ticket_widgets = get_option( 'wpsc-ticket-widget', array() );
			$widget = $ticket_widgets['workflows'];
			$title = $widget['title'];
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-widget-settings">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Title', 'supportcandy' ); ?></label>
					</div>
					<input name="label" type="text" value="<?php echo esc_attr( $widget['title'] ); ?>" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Enable', 'supportcandy' ); ?></label>
					</div>
					<select name="is_enable">
						<option <?php selected( $widget['is_enable'], '1' ); ?> value="1"><?php esc_attr_e( 'Yes', 'supportcandy' ); ?></option>
						<option <?php selected( $widget['is_enable'], '0' ); ?>  value="0"><?php esc_attr_e( 'No', 'supportcandy' ); ?></option>
					</select>
				</div>
				<input type="hidden" name="action" value="wpsc_set_tw_workflows">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_tw_workflows' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_tw_workflows(this);">
				<?php esc_attr_e( 'Submit', 'supportcandy' ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Cancel', 'supportcandy' ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			wp_send_json(
				array(
					'title'  => $title,
					'body'   => $body,
					'footer' => $footer,
				)
			);
		}

		/**
		 * Set widget settings
		 */
		public static function set_widget_settings() {

			if ( check_ajax_referer( 'wpsc_set_tw_workflows', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $label ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$ticket_widgets = get_option( 'wpsc-ticket-widget', array() );
			$ticket_widgets['workflows']['title'] = $label;
			$ticket_widgets['workflows']['is_enable'] = isset( $_POST['is_enable'] ) ? intval( $_POST['is_enable'] ) : 0;
			update_option( 'wpsc-ticket-widget', $ticket_widgets );

			// remove string translations.
			WPSC_Translations::remove( 'wpsc-twt-workflows' );
			WPSC_Translations::add( 'wpsc-twt-workflows', stripslashes( $label ) );
			wp_die();
		}
	}
endif;

WPSC_ITW_Workflows::init();
