<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Action_Add_Reply' ) ) :

	final class WPSC_WF_Action_Add_Reply {

		/**
		 * Slug for this action
		 *
		 * @var string
		 */
		public static $slug = 'add-reply';

		/**
		 * Print input field
		 *
		 * @param array $action - pre-defined json value.
		 * @return void
		 */
		public static function print( $action = array() ) {

			$unique_id = uniqid( 'wpsc_' );
			?>
			<div class="wf-action-item" data-slug="<?php echo esc_attr( self::$slug ); ?>">
				<div class="wf-action-header">
					<span class="wf-action-title"><?php echo esc_attr( WPSC_WF_Actions::$actions[ self::$slug ]['title'] ); ?></span>
					<span class="wf-remove-action" onclick="wpsc_wf_remove_action(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
				</div>
				<div class="wf-action-body">
					<?php $body = isset( $action['body'] ) ? $action['body'] : ''; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php echo esc_attr( wpsc__( 'Reply', 'wpsc-workflows' ) ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<textarea class="wpsc_textarea" id="<?php echo esc_attr( $unique_id ); ?>" name="actions[<?php echo esc_attr( self::$slug ); ?>][body]"><?php echo esc_attr( $body ); ?></textarea>
						<div class="wpsc-it-editor-action-container">
							<div class="actions">
								<div class="wpsc-editor-actions">
									<span onclick="wpsc_get_macros()" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Insert Macro', 'supportcandy' ) ); ?></span>
								</div>
							</div>
						</div>
						<script><?php WPSC_Text_Editor::print_editor_init_scripts( $unique_id, $unique_id . '_body' ); ?></script>
					</div>
					<?php $current_agent = isset( $action['current-agent'] ) ? intval( $action['current-agent'] ) : 0; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php echo esc_attr( wpsc__( 'Current agent (if any)', 'wpsc-workflows' ) ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][current-agent]">
							<option <?php selected( $current_agent, 1, true ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
							<option <?php selected( $current_agent, 0, true ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
						</select>
					</div>
					<?php $agent = isset( $action['fallback-agent'] ) && $action['fallback-agent'] ? new WPSC_Agent( $action['fallback-agent'] ) : 0; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php echo esc_attr( wpsc__( 'Fallback agent', 'wpsc-workflows' ) ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<select class="reply-agent" name="actions[<?php echo esc_attr( self::$slug ); ?>][fallback-agent]">
							<?php
							if ( is_object( $agent ) && $agent->id ) {
								?>
								<option value="<?php echo esc_attr( $agent->id ); ?>" selected><?php echo esc_attr( $agent->name ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<script>
						jQuery('select.reply-agent').selectWoo({
							ajax: {
								url: supportcandy.ajax_url,
								dataType: 'json',
								delay: 250,
								data: function (params) {
									return {
										q: params.term, // search term
										page: params.page,
										action: 'wpsc_agent_autocomplete_admin_access',
										_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_agent_autocomplete_admin_access' ) ); ?>',
										isMultiple: 1, // to avoid none
										isAgentgroup: 0
									};
								},
								processResults: function (data, params) {
									var terms = [];
									if ( data ) {
										jQuery.each( data, function( id, text ) {
											terms.push( { id: text.id, text: text.title } );
										});
									}
									return {
										results: terms
									};
								},
								cache: true
							},
							escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
							minimumInputLength: 0,
							allowClear: false,
						});
					</script>
				</div>
			</div>
			<?php
		}

		/**
		 * Sanitize action input data to store in db
		 *
		 * @param array $action - actioin input array of this type.
		 * @return array
		 */
		public static function sanitize_action( $action ) {

			$body = isset( $action['body'] ) ? wp_kses_post( wp_unslash( $action['body'] ) ) : '';
			if ( ! $body ) {
				wp_send_json_error( esc_attr__( 'Reply text not set!', 'wpsc-workflows' ), 400 );
			}

			$current_agent = isset( $action['current-agent'] ) ? intval( $action['current-agent'] ) : null;
			if ( ! is_numeric( $current_agent ) ) {
				wp_send_json_error( wpsc__( 'Bad request!', 'supportcandy' ), 400 );
			}

			$fallback_agent = isset( $action['fallback-agent'] ) ? intval( $action['fallback-agent'] ) : 0;
			if ( ! $fallback_agent ) {
				wp_send_json_error( esc_attr__( 'Fallback agent not set!', 'wpsc-workflows' ), 400 );
			}

			$temp = new WPSC_Agent( $fallback_agent );
			if ( ! $temp->id ) {
				wp_send_json_error( esc_attr__( 'Invalid fallback agent!', 'wpsc-workflows' ), 400 );
			}

			return array(
				'body'           => $body,
				'current-agent'  => $current_agent,
				'fallback-agent' => $fallback_agent,
			);
		}

		/**
		 * Execute the action of this type
		 *
		 * @param array       $action - action details.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param array       $workflow - workflow array.
		 * @return void
		 */
		public static function execute( $action, $ticket, $workflow ) {

			// avoid looping.
			$last_reply = $ticket->get_last_reply();
			if ( $last_reply && $last_reply->body == $action['body'] ) {
				return;
			}

			$agent = new WPSC_Agent();

			// current agent.
			if ( $action['current-agent'] ) {
				$current_user = WPSC_Current_User::$current_user;
				if ( $current_user->is_agent ) {
					$agent = $current_user->agent;
				}
			}

			// try fallback agent.
			if ( ! $agent->id ) {
				$agent = new WPSC_Agent( $action['fallback-agent'] );
			}

			// return if still agent is not set.
			if ( ! $agent->id ) {
				return;
			}

			// submit reply.
			$thread = WPSC_Thread::insert(
				array(
					'ticket'     => $ticket->id,
					'customer'   => $agent->customer->id,
					'type'       => 'reply',
					'body'       => WPSC_Macros::replace( $action['body'], $ticket ),
					'ip_address' => WPSC_DF_IP_Address::get_current_user_ip(),
					'source'     => 'workflows',
					'os'         => WPSC_DF_OS::get_user_platform(),
					'browser'    => WPSC_DF_Browser::get_user_browser(),
				)
			);

			$ticket->date_updated  = new DateTime();
			$ticket->last_reply_on = new DateTime();
			$ticket->last_reply_by = $agent->customer->id;
			$ticket->save();

			do_action( 'wpsc_post_reply', $thread );
		}
	}

endif;
