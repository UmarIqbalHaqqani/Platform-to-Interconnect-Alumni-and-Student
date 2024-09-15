<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Action_Change_Assignee' ) ) :

	final class WPSC_WF_Action_Change_Assignee {

		/**
		 * Slug for this action
		 *
		 * @var string
		 */
		public static $slug = 'change-assignee';

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
					<?php $change_policy = isset( $action['change-policy'] ) ? $action['change-policy'] : 'replace'; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Change policy', 'wpsc-workflows' ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][change-policy]">
							<option <?php selected( 'replace', $change_policy, true ); ?> value="replace"><?php esc_attr_e( 'Replace', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'merge', $change_policy, true ); ?> value="merge"><?php esc_attr_e( 'Merge', 'wpsc-workflows' ); ?></option>
						</select>
					</div>
					<?php $current_agent = isset( $action['current-agent'] ) ? $action['current-agent'] : 0; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Assign current agent ( if any )', 'wpsc-workflows' ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<small style="margin-bottom: 5px; font-size: 10px;"><?php esc_attr_e( 'Assign the current user if he is an agent.', 'wpsc-workflows' ); ?></small>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][current-agent]">
							<option <?php selected( 0, $current_agent, true ); ?> value="0"><?php esc_attr_e( 'No', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 1, $current_agent, true ); ?> value="1"><?php esc_attr_e( 'Yes', 'wpsc-workflows' ); ?></option>
						</select>
					</div>
					<?php
					$assignee = array_filter(
						array_map(
							function( $id ) {
								$agent = new WPSC_Agent( intval( $id ) );
								return $agent->id ? $agent : false;
							},
							isset( $action['assignee'] ) ? $action['assignee'] : array()
						)
					);
					?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Assignee', 'wpsc-workflows' ); ?></label>
						</div>
						<small style="margin-bottom: 5px; font-size: 10px;"><?php esc_attr_e( 'At least one agent/agentgroup is required if assign current agent is disabled.', 'wpsc-workflows' ); ?></small>
						<select class="assignee" name="actions[<?php echo esc_attr( self::$slug ); ?>][assignee][]" multiple>
							<?php
							foreach ( $assignee as $agent ) {
								?>
								<option value="<?php echo esc_attr( $agent->id ); ?>" selected><?php echo esc_attr( $agent->name ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<?php $policy = isset( $action['agent-assignment-policy'] ) ? $action['agent-assignment-policy'] : 'all'; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Agent assignment policy', 'wpsc-workflows' ); ?></label>
						</div>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][agent-assignment-policy]">
							<option <?php selected( 'all', $policy, true ); ?> value="all"><?php esc_attr_e( 'Assign all', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'one-closest-wh', $policy, true ); ?> value="one-closest-wh"><?php esc_attr_e( 'Assign one (closest working hours)', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'one-min-workload', $policy, true ); ?> value="one-min-workload"><?php esc_attr_e( 'Assign one (minimum workload)', 'wpsc-workflows' ); ?></option>
						</select>
					</div>
					<?php $policy = isset( $action['agentgroup-assignment-policy'] ) ? $action['agentgroup-assignment-policy'] : 'group-only'; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Agentgroup assignment policy', 'wpsc-workflows' ); ?></label>
						</div>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][agentgroup-assignment-policy]">
							<option <?php selected( 'group-only', $policy, true ); ?> value="group-only"><?php esc_attr_e( 'Group only', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'group-member-supervisor-closest-wh', $policy, true ); ?> value="group-member-supervisor-closest-wh"><?php esc_attr_e( 'Group and one of its members including supervisor (closest working hours)', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'group-member-supervisor-min-workload', $policy, true ); ?> value="group-member-supervisor-min-workload"><?php esc_attr_e( 'Group and one of its members including supervisor (minimum workload)', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'group-member-closest-wh', $policy, true ); ?> value="group-member-closest-wh"><?php esc_attr_e( 'Group and one of its members excluding supervisors (closest working hours)', 'wpsc-workflows' ); ?></option>
							<option <?php selected( 'group-member-min-workload', $policy, true ); ?> value="group-member-min-workload"><?php esc_attr_e( 'Group and one of its members excluding supervisors (minimum workload)', 'wpsc-workflows' ); ?></option>
						</select>
					</div>
					<script>
						jQuery('select.assignee').selectWoo({
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

			$current_agent = isset( $action['current-agent'] ) ? intval( $action['current-agent'] ) : 0;
			$assignee = array_unique(
				array_filter(
					array_map(
						function( $id ) {
							$agent = new WPSC_Agent( intval( $id ) );
							return $agent->id ? $agent->id : false;
						},
						isset( $action['assignee'] ) ? $action['assignee'] : array()
					)
				)
			);

			if ( ! $current_agent && ! $assignee ) {
				wp_send_json_error( esc_attr__( 'At least one agent/group is required if assign current agent is disabled.', 'wpsc-workflows' ), 400 );
			}

			return array(
				'change-policy'                => isset( $action['change-policy'] ) ? sanitize_key( $action['change-policy'] ) : 'replace',
				'current-agent'                => $current_agent,
				'assignee'                     => $assignee,
				'agent-assignment-policy'      => isset( $action['agent-assignment-policy'] ) ? sanitize_key( $action['agent-assignment-policy'] ) : 'all',
				'agentgroup-assignment-policy' => isset( $action['agentgroup-assignment-policy'] ) ? sanitize_key( $action['agentgroup-assignment-policy'] ) : 'group-only',
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

			$current_user = WPSC_Current_User::$current_user;
			$now = ( new DateTime() )->setTimezone( wp_timezone() );
			$assignee = array();

			// current agent.
			if ( $action['current-agent'] && $current_user->is_agent ) {
				$assignee[] = $current_user->agent->id;
			}

			// filter agents.
			$agents = array_filter(
				array_map(
					function ( $id ) {
						$agent = new WPSC_Agent( $id );
						return $agent->id && ! $agent->is_agentgroup ? $agent->id : false;
					},
					$action['assignee']
				)
			);

			if ( $agents ) {

				switch ( $action['agent-assignment-policy'] ) {

					case 'all':
						$assignee = array_merge( $assignee, $agents );
						break;

					case 'one-closest-wh':
						$assignee[] = self::get_one_agent_with_cwh( $agents, $now );
						break;

					case 'one-min-workload':
						$assignee[] = self::get_one_agent_with_mw( $agents, $now );
						break;
				}
			}

			// agentgroups.
			if ( class_exists( 'WPSC_Agentgroups' ) ) {

				$agentgroups = array_filter(
					array_map(
						function ( $id ) {
							$agent = new WPSC_Agent( $id );
							if ( $agent->id && $agent->is_agentgroup ) {
								$agentgroup = WPSC_Agentgroup::get_by_agent_id( $agent->id );
								return $agentgroup ? $agent->id : false;
							} else {
								return false;
							}
						},
						$action['assignee']
					)
				);

				$assignee = array_merge( $assignee, $agentgroups );

				if ( $action['agentgroup-assignment-policy'] !== 'group-only' ) {

					foreach ( $agentgroups as $agent_id ) {

						$agentgroup = WPSC_Agentgroup::get_by_agent_id( $agent_id );
						$agents = array();

						switch ( $action['agentgroup-assignment-policy'] ) {

							case 'group-member-supervisor-closest-wh':
							case 'group-member-supervisor-min-workload':
								$agents = array_filter(
									array_map(
										fn( $agent ) => $agent->id && $agent->is_active ? $agent->id : false,
										$agentgroup->agents
									)
								);
								break;

							case 'group-member-closest-wh':
							case 'group-member-min-workload':
								$agents = array_filter(
									array_map(
										fn( $agent ) => $agent->id && $agent->is_active && ! in_array( $agent, $agentgroup->supervisors ) ? $agent->id : false,
										$agentgroup->agents
									)
								);
								break;
						}

						switch ( $action['agentgroup-assignment-policy'] ) {

							case 'group-member-supervisor-closest-wh':
							case 'group-member-closest-wh':
								$assignee[] = self::get_one_agent_with_cwh( $agents, $now );
								break;

							case 'group-member-supervisor-min-workload':
							case 'group-member-min-workload':
								$assignee[] = self::get_one_agent_with_mw( $agents, $now );
								break;
						}
					}
				}
			}

			$new_ids = array_unique( $assignee );
			$prev_ids = array_filter(
				array_map(
					fn( $agent ) => $agent->id ? $agent->id : false,
					$ticket->assigned_agent
				)
			);

			// change policy.
			if ( $action['change-policy'] == 'merge' ) {
				$new_ids = array_unique( array_merge( $prev_ids, $new_ids ) );
			}

			$assignee = array_filter(
				array_map(
					fn( $id ) => new WPSC_Agent( $id ),
					$new_ids
				)
			);

			// return if no change.
			if (
				count( array_diff( $new_ids, $prev_ids ) ) === 0 &&
				count( array_diff( $prev_ids, $new_ids ) ) === 0
			) {
				return;
			}

			$customer_id = $workflow['type'] == 'manual' ? WPSC_Current_User::$current_user->customer->id : 0;
			WPSC_Individual_Ticket::change_assignee( $ticket->assigned_agent, $assignee, $customer_id );
		}

		/**
		 * Get one agent with closest working hr
		 *
		 * @param array    $agents - agent ids.
		 * @param DateTime $date_created - rule created date.
		 * @return integer
		 */
		public static function get_one_agent_with_cwh( $agents, $date_created ) {

			// return first index in case of only one agent.
			if ( count( $agents ) == 1 ) {
				return $agents[0];
			}

			$working_hrs = array();
			foreach ( $agents as $agent_id ) {
				$working_hrs[] = array_merge( array( 'agent_id' => $agent_id ), WPSC_Working_Hour::get_closest_wh_by_date( $date_created, $agent_id ) );
			}

			// sort by start time.
			usort(
				$working_hrs,
				function( $item1, $item2 ) {
					return $item1['start_time'] <=> $item2['start_time'];
				}
			);

			// sort by same start time.
			$temp = array_filter(
				$working_hrs,
				function( $v, $k ) use ( $working_hrs ) {
					return $v['start_time'] == $working_hrs[0]['start_time'];
				},
				ARRAY_FILTER_USE_BOTH
			);

			// return if there is only one agent.
			if ( count( $temp ) == 1 ) {
				return $temp[0]['agent_id'];
			}

			// add wokload to array.
			foreach ( $temp as $key => $wh ) {
				$agent          = new WPSC_Agent( $wh['agent_id'] );
				$wh['workload'] = $agent->workload;
				$temp[ $key ]   = $wh;
			}

			// sort by workload.
			usort(
				$temp,
				function( $item1, $item2 ) {
					return $item1['workload'] <=> $item2['workload'];
				}
			);

			return $temp[0]['agent_id'];
		}

		/**
		 * Get one agent with minimum work load
		 *
		 * @param array    $agents - agent ids.
		 * @param DateTime $date_created - rule created date.
		 * @return integer
		 */
		public static function get_one_agent_with_mw( $agents, $date_created ) {

			// return first index in case of only one agent.
			if ( count( $agents ) == 1 ) {
				return $agents[0];
			}

			$temp_agents = array();
			foreach ( $agents as $agent_id ) {
				$agent         = new WPSC_Agent( $agent_id );
				$temp_agents[] = array(
					'agent'    => $agent,
					'workload' => $agent->workload,
				);
			}

			// sort agents by workload.
			usort(
				$temp_agents,
				function( $item1, $item2 ) {
					return $item1['workload'] <=> $item2['workload'];
				}
			);

			// sort by same start time.
			$temp = array_filter(
				$temp_agents,
				function( $v, $k ) use ( $temp_agents ) {
					return $v['workload'] == $temp_agents[0]['workload'];
				},
				ARRAY_FILTER_USE_BOTH
			);

			// if it is only one agent left, return it.
			if ( count( $temp ) == 1 ) {
				return ( $temp[0]['agent'] )->id;
			}

			// get working hrs of remaining agents.
			$working_hrs = array();
			foreach ( $temp as $agent_temp ) {
				$working_hrs[] = array_merge(
					array( 'agent' => $agent_temp['agent'] ),
					WPSC_Working_Hour::get_closest_wh_by_date( $date_created, $agent_temp['agent']->id )
				);
			}

			// sort by start time.
			usort(
				$working_hrs,
				function( $item1, $item2 ) {
					return $item1['start_time'] <=> $item2['start_time'];
				}
			);

			// sort by same start time.
			$temp = array_filter(
				$working_hrs,
				function( $v, $k ) use ( $working_hrs ) {
					return $v['start_time'] == $working_hrs[0]['start_time'];
				},
				ARRAY_FILTER_USE_BOTH
			);

			return $temp[0]['agent']->id;
		}
	}

endif;
