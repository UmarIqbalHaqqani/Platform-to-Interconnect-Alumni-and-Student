<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Actions' ) ) :

	final class WPSC_WF_Actions {

		/**
		 * All possible actions
		 *
		 * @var array
		 */
		public static $actions = array();

		/**
		 * Initialize the class
		 *
		 * @return void
		 */
		public static function init() {

			// Load actions.
			add_action( 'init', array( __CLASS__, 'load_actions' ) );

			// add ticket source.
			add_filter( 'wpsc_source_list', array( __CLASS__, 'add_source' ) );

			// Add new action.
			add_action( 'wp_ajax_wpsc_wf_get_add_new_action', array( __CLASS__, 'get_add_action' ) );
			add_action( 'wp_ajax_wpsc_wf_set_add_new_action', array( __CLASS__, 'set_add_action' ) );
		}

		/**
		 * Load actions
		 *
		 * @return void
		 */
		public static function load_actions() {

			self::$actions = apply_filters(
				'wpsc_wf_actions',
				array(
					'add-reply'               => array(
						'title' => esc_attr__( 'Add reply', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Add_Reply',
					),
					'add-note'                => array(
						'title' => esc_attr__( 'Add private note', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Add_Note',
					),
					'change-subject'          => array(
						'title' => esc_attr__( 'Change subject', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Subject',
					),
					'change-status'           => array(
						'title' => esc_attr__( 'Change status', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Status',
					),
					'change-category'         => array(
						'title' => esc_attr__( 'Change category', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Category',
					),
					'change-priority'         => array(
						'title' => esc_attr__( 'Change priority', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Priority',
					),
					'change-assignee'         => array(
						'title' => esc_attr__( 'Change assignee', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Assignee',
					),
					'change-ar'               => array(
						'title' => esc_attr__( 'Change additional recipients', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_AR',
					),
					'change-ticket-fields'    => array(
						'title' => esc_attr__( 'Change ticket fields', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Ticket_Fields',
					),
					'change-agentonly-fields' => array(
						'title' => esc_attr__( 'Change agent only fields', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Agentonly_Fields',
					),
					'change-customer-fields'  => array(
						'title' => esc_attr__( 'Change customer fields', 'wpsc-workflows' ),
						'class' => 'WPSC_WF_Action_Change_Customer_Fields',
					),
				)
			);
		}

		/**
		 * Add workflows source to sources.
		 *
		 * @param array $sources - source array.
		 * @return array
		 */
		public static function add_source( $sources ) {

			$sources['workflows'] = __( 'Workflows', 'wpsc-workflows' );
			return $sources;
		}

		/**
		 * Print action composer input
		 *
		 * @param string $actions - actions json.
		 * @return void
		 */
		public static function print( $actions = '' ) {

			$actions = $actions ? json_decode( html_entity_decode( $actions ), true ) : array();
			?>
			<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Actions', 'wpsc-workflows' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<div class="wpsc-action-container">
						<div class="wf-actions">
							<?php
							if ( $actions ) {
								foreach ( $actions as $slug => $action ) {
									if (
										! isset( self::$actions[ $slug ] ) ||
										! class_exists( self::$actions[ $slug ]['class'] )
									) {
										continue;
									}
									self::$actions[ $slug ]['class']::print( $action );
								}
							}
							?>
						</div>
						<button class="wpsc-button small secondary" onclick="wpsc_wf_get_add_new_action( '<?php echo esc_attr( wp_create_nonce( 'wpsc_wf_get_add_new_action' ) ); ?>' );">
							<?php esc_attr_e( 'Add new action', 'wpsc-workflows' ); ?>
						</button>
					</div>
				</div>
			<?php
		}

		/**
		 * Execute actions
		 *
		 * @param string      $actions - actions json.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param array       $workflow - workflow array.
		 * @return void
		 */
		public static function execute( $actions, $ticket, $workflow ) {

			$actions = $actions ? json_decode( html_entity_decode( $actions ), true ) : array();
			if ( $actions ) {
				foreach ( $actions as $slug => $action ) {
					self::$actions[ $slug ]['class']::execute( $action, $ticket, $workflow );
				}
			}
		}

		/**
		 * Get add new action
		 *
		 * @return void
		 */
		public static function get_add_action() {

			if ( check_ajax_referer( 'wpsc_wf_get_add_new_action', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$title = esc_attr__( 'Add new action', 'wpsc-workflows' );
			$actions = isset( $_POST['actions'] ) ? array_filter( array_map( 'sanitize_text_field', explode( ',', sanitize_text_field( wp_unslash( $_POST['actions'] ) ) ) ) ) : array();

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-wf-add-action">

				<div class="wpsc-input-group">
					<select name="wf-action">
						<?php
						foreach ( self::$actions as $key => $action ) {
							if ( in_array( $key, $actions ) ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $action['title'] ); ?></option>
							<?php
						}
						?>
					</select>
				</div>

				<input type="hidden" name="action" value="wpsc_wf_set_add_new_action">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_wf_set_add_new_action' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_wf_set_add_new_action(this);">
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
		 * Set add new action
		 *
		 * @return void
		 */
		public static function set_add_action() {

			if ( check_ajax_referer( 'wpsc_wf_set_add_new_action', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$action = isset( $_POST['wf-action'] ) ? sanitize_text_field( wp_unslash( $_POST['wf-action'] ) ) : '';
			if ( ! $action || ! isset( self::$actions[ $action ] ) ) {
				wp_send_json_error( 'Invalid action!', 400 );
			}

			self::$actions[ $action ]['class']::print();
			wp_die();
		}
	}

endif;

WPSC_WF_Actions::init();
