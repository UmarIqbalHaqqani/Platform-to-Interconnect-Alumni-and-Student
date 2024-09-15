<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_PC_Actions' ) ) :

	final class WPSC_PC_Actions {

		/**
		 * Initializing class
		 *
		 * @return void
		 */
		public static function init() {

			// add en_from to schema.
			add_action( 'wpsc_ticket_schema', array( __CLASS__, 'add_ticket_schema' ) );

			// private credentials menu access.
			add_action( 'wpsc_add_agent_role_ticket_permissions', array( __CLASS__, 'add_agent_role_ticket_permissions' ) );
			add_filter( 'wpsc_set_add_agent_role', array( __CLASS__, 'set_add_agent_role_ticket_permission' ), 10, 2 );
			add_action( 'wpsc_edit_agent_role_ticket_permissions', array( __CLASS__, 'edit_agent_role_ticket_permissions' ) );
			add_filter( 'wpsc_set_edit_agent_role', array( __CLASS__, 'set_edit_agent_role_ticket_permission' ), 10, 3 );

			add_action( 'wpsc_after_anonymizing_ticket', array( __CLASS__, 'anonymize_ticket' ) );
		}

		/**
		 * Add private credential schema for ticket
		 *
		 * @param array $schema - schema.
		 * @return array
		 */
		public static function add_ticket_schema( $schema ) {

			$pc_data = array(
				'pc_data' => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
			);
			return array_merge( $schema, $pc_data );
		}

		/**
		 * Add permisstion settings to add agent role
		 *
		 * @return void
		 */
		public static function add_agent_role_ticket_permissions() {?>

			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'View Credentials', 'wpsc-pc' ); ?></label>
				</div>
				<div class="checkbox-group">
					<div>
						<input name="caps[]" type="checkbox" value="view-pc-unassigned">
						<span><?php echo esc_attr( wpsc__( 'Unassigned', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" value="view-pc-assigned-me">
						<span><?php echo esc_attr( wpsc__( 'Assigned to me', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" value="view-pc-assigned-others">
						<span><?php echo esc_attr( wpsc__( 'Assigned to others', 'supportcandy' ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'Modify Credentials', 'wpsc-pc' ); ?></label>
				</div>
				<div class="checkbox-group">
					<div>
						<input name="caps[]" type="checkbox" value="modify-pc-unassigned">
						<span><?php echo esc_attr( wpsc__( 'Unassigned', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" value="modify-pc-assigned-me">
						<span><?php echo esc_attr( wpsc__( 'Assigned to me', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" value="modify-pc-assigned-others">
						<span><?php echo esc_attr( wpsc__( 'Assigned to others', 'supportcandy' ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'Delete Credentials', 'wpsc-pc' ); ?></label>
				</div>
				<div class="checkbox-group">
					<div>
						<input name="caps[]" type="checkbox" value="delete-pc-unassigned">
						<span><?php echo esc_attr( wpsc__( 'Unassigned', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" value="delete-pc-assigned-me">
						<span><?php echo esc_attr( wpsc__( 'Assigned to me', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" value="delete-pc-assigned-others">
						<span><?php echo esc_attr( wpsc__( 'Assigned to others', 'supportcandy' ) ); ?></span>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Set ticket permissions for this filter
		 *
		 * @param array  $args - arg name.
		 * @param string $caps - capabilities.
		 * @return array
		 */
		public static function set_add_agent_role_ticket_permission( $args, $caps ) {

			$args['caps']['view-pc-unassigned']      = in_array( 'view-pc-unassigned', $caps ) ? true : false;
			$args['caps']['view-pc-assigned-me']     = in_array( 'view-pc-assigned-me', $caps ) ? true : false;
			$args['caps']['view-pc-assigned-others'] = in_array( 'view-pc-assigned-others', $caps ) ? true : false;

			$args['caps']['modify-pc-unassigned']      = in_array( 'modify-pc-unassigned', $caps ) ? true : false;
			$args['caps']['modify-pc-assigned-me']     = in_array( 'modify-pc-assigned-me', $caps ) ? true : false;
			$args['caps']['modify-pc-assigned-others'] = in_array( 'modify-pc-assigned-others', $caps ) ? true : false;

			$args['caps']['delete-pc-unassigned']      = in_array( 'delete-pc-unassigned', $caps ) ? true : false;
			$args['caps']['delete-pc-assigned-me']     = in_array( 'delete-pc-assigned-me', $caps ) ? true : false;
			$args['caps']['delete-pc-assigned-others'] = in_array( 'delete-pc-assigned-others', $caps ) ? true : false;

			return $args;
		}

		/**
		 * Edit permisstion settings to add agent role
		 *
		 * @param string $role - capabilities role.
		 * @return void
		 */
		public static function edit_agent_role_ticket_permissions( $role ) {
			?>

			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'View Credentials', 'wpsc-pc' ); ?></label>
				</div>
				<div class="checkbox-group">
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['view-pc-unassigned'], 1 ); ?> value="view-pc-unassigned">
						<span><?php echo esc_attr( wpsc__( 'Unassigned', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['view-pc-assigned-me'], 1 ); ?> value="view-pc-assigned-me">
						<span><?php echo esc_attr( wpsc__( 'Assigned to me', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['view-pc-assigned-others'], 1 ); ?> value="view-pc-assigned-others">
						<span><?php echo esc_attr( wpsc__( 'Assigned to others', 'supportcandy' ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'Modify Credentials', 'wpsc-pc' ); ?></label>
				</div>
				<div class="checkbox-group">
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['modify-pc-unassigned'], 1 ); ?> value="modify-pc-unassigned">
						<span><?php echo esc_attr( wpsc__( 'Unassigned', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['modify-pc-assigned-me'], 1 ); ?> value="modify-pc-assigned-me">
						<span><?php echo esc_attr( wpsc__( 'Assigned to me', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['modify-pc-assigned-others'], 1 ); ?> value="modify-pc-assigned-others">
						<span><?php echo esc_attr( wpsc__( 'Assigned to others', 'supportcandy' ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'Delete Credentials', 'wpsc-pc' ); ?></label>
				</div>
				<div class="checkbox-group">
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['delete-pc-unassigned'], 1 ); ?> value="delete-pc-unassigned">
						<span><?php echo esc_attr( wpsc__( 'Unassigned', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['delete-pc-assigned-me'], 1 ); ?> value="delete-pc-assigned-me">
						<span><?php echo esc_attr( wpsc__( 'Assigned to me', 'supportcandy' ) ); ?></span>
					</div>
					<div>
						<input name="caps[]" type="checkbox" <?php checked( $role['caps']['delete-pc-assigned-others'], 1 ); ?> value="delete-pc-assigned-others">
						<span><?php echo esc_attr( wpsc__( 'Assigned to others', 'supportcandy' ) ); ?></span>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Set edit agent role
		 *
		 * @param array  $new - changed value.
		 * @param array  $prev - existing value.
		 * @param string $caps - capabilities.
		 * @return array
		 */
		public static function set_edit_agent_role_ticket_permission( $new, $prev, $caps ) {

			$new['caps']['view-pc-unassigned']      = in_array( 'view-pc-unassigned', $caps ) ? true : false;
			$new['caps']['view-pc-assigned-me']     = in_array( 'view-pc-assigned-me', $caps ) ? true : false;
			$new['caps']['view-pc-assigned-others'] = in_array( 'view-pc-assigned-others', $caps ) ? true : false;

			$new['caps']['modify-pc-unassigned']      = in_array( 'modify-pc-unassigned', $caps ) ? true : false;
			$new['caps']['modify-pc-assigned-me']     = in_array( 'modify-pc-assigned-me', $caps ) ? true : false;
			$new['caps']['modify-pc-assigned-others'] = in_array( 'modify-pc-assigned-others', $caps ) ? true : false;

			$new['caps']['delete-pc-unassigned']      = in_array( 'delete-pc-unassigned', $caps ) ? true : false;
			$new['caps']['delete-pc-assigned-me']     = in_array( 'delete-pc-assigned-me', $caps ) ? true : false;
			$new['caps']['delete-pc-assigned-others'] = in_array( 'delete-pc-assigned-others', $caps ) ? true : false;

			return $new;
		}

		/**
		 * Anonymize ticket & customer data
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function anonymize_ticket( $ticket ) {

			$ticket->pc_data = '';
			$ticket->save();
		}

	}
endif;

WPSC_PC_Actions::init();
