<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Admin' ) ) :

	final class WPSC_WF_Admin {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// load scripts & styles.
			add_action( 'wpsc_js_frontend', array( __CLASS__, 'frontend_scripts' ) );
			add_action( 'wpsc_js_backend', array( __CLASS__, 'backend_scripts' ) );
			add_action( 'wpsc_css_backend', array( __CLASS__, 'backend_styles' ) );

			// agent role capabilities.
			add_action( 'wpsc_add_agent_role_ticket_permissions', array( __CLASS__, 'add_agent_role_ticket_permissions' ) );
			add_filter( 'wpsc_set_add_agent_role', array( __CLASS__, 'set_add_agent_role_ticket_permission' ), 10, 2 );
			add_action( 'wpsc_edit_agent_role_ticket_permissions', array( __CLASS__, 'edit_agent_role_ticket_permissions' ) );
			add_filter( 'wpsc_set_edit_agent_role', array( __CLASS__, 'set_edit_agent_role_ticket_permission' ), 10, 3 );
		}

		/**
		 * Frontend scripts
		 *
		 * @return void
		 */
		public static function frontend_scripts() {

			echo file_get_contents( WPSC_WF_ABSPATH . 'asset/js/public.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_scripts() {

			echo file_get_contents( WPSC_WF_ABSPATH . 'asset/js/admin.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_styles() {

			if ( is_rtl() ) {
				echo file_get_contents( WPSC_WF_ABSPATH . 'asset/css/admin-rtl.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			} else {
				echo file_get_contents( WPSC_WF_ABSPATH . 'asset/css/admin.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			}
		}

		/**
		 * Add permisstion settings to add agent role
		 *
		 * @return void
		 */
		public static function add_agent_role_ticket_permissions() {?>

			<tr>
				<td><label for=""><?php esc_attr_e( 'Trigger Workflows', 'wpsc-workflows' ); ?></label></td>
				<td><input name="caps[]" type="checkbox" value="workflows-unassigned" class="wpsc-una"></td>
				<td><input name="caps[]" type="checkbox" value="workflows-assigned-me" class="wpsc-ame"></td>
				<td><input name="caps[]" type="checkbox" value="workflows-assigned-others" class="wpsc-ao"></td>
			</tr>			
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

			$args['caps']['workflows-unassigned']      = in_array( 'workflows-unassigned', $caps ) ? true : false;
			$args['caps']['workflows-assigned-me']     = in_array( 'workflows-assigned-me', $caps ) ? true : false;
			$args['caps']['workflows-assigned-others'] = in_array( 'workflows-assigned-others', $caps ) ? true : false;

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

			<tr>
				<td><label for=""><?php esc_attr_e( 'Trigger Workflows', 'wpsc-workflows' ); ?></label></td>
				<td><input name="caps[]" type="checkbox" <?php checked( $role['caps']['workflows-unassigned'], 1 ); ?> value="workflows-unassigned" class="wpsc-una"></td>
				<td><input name="caps[]" type="checkbox" <?php checked( $role['caps']['workflows-assigned-me'], 1 ); ?> value="workflows-assigned-me" class="wpsc-ame"></td>
				<td><input name="caps[]" type="checkbox" <?php checked( $role['caps']['workflows-assigned-others'], 1 ); ?> value="workflows-assigned-others" class="wpsc-ao"></td>
			</tr>

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

			$new['caps']['workflows-unassigned']      = in_array( 'workflows-unassigned', $caps ) ? true : false;
			$new['caps']['workflows-assigned-me']     = in_array( 'workflows-assigned-me', $caps ) ? true : false;
			$new['caps']['workflows-assigned-others'] = in_array( 'workflows-assigned-others', $caps ) ? true : false;

			return $new;
		}
	}
endif;

WPSC_WF_Admin::init();

// Load settings classes.
foreach ( glob( WPSC_WF_ABSPATH . 'includes/settings/*.php' ) as $filename ) {
	include_once $filename;
}

// Load action classes.
foreach ( glob( WPSC_WF_ABSPATH . 'includes/actions/*.php' ) as $filename ) {
	include_once $filename;
}

// Load custom field types.
foreach ( glob( WPSC_WF_ABSPATH . 'includes/custom-field-types/*.php' ) as $filename ) {
	include_once $filename;
}
