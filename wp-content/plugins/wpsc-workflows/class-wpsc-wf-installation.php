<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Installation' ) ) :

	final class WPSC_WF_Installation {

		/**
		 * Currently installed version
		 *
		 * @var integer
		 */
		public static $current_version;

		/**
		 * For checking whether upgrade available or not
		 *
		 * @var boolean
		 */
		public static $is_upgrade = false;

		/**
		 * Initialize installation
		 */
		public static function init() {

			self::get_current_version();
			self::check_upgrade();

			// db upgrade addon installer hook.
			add_action( 'wpsc_upgrade_install_addons', array( __CLASS__, 'upgrade_install' ) );

			// Database upgrade is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) ) {
				return;
			}

			if ( self::$is_upgrade ) {

				define( 'WPSC_WF_INSTALLING', true );

				// Do not allow parallel process to run.
				if ( 'yes' === get_transient( 'wpsc_wf_installing' ) ) {
					return;
				}

				// Set transient.
				set_transient( 'wpsc_wf_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				// Run installation.
				if ( self::$current_version == 0 ) {

					add_action( 'init', array( __CLASS__, 'initial_setup' ), 1 );
					add_action( 'init', array( __CLASS__, 'set_upgrade_complete' ), 1 );

				} else {

					add_action( 'init', array( __CLASS__, 'upgrade' ), 1 );
				}

				// Delete transient.
				delete_transient( 'wpsc_wf_installing' );
			}

			// activation functionality.
			register_activation_hook( WPSC_WF_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

			// Deactivate functionality.
			register_deactivation_hook( WPSC_WF_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Check version
		 */
		public static function get_current_version() {

			self::$current_version = get_option( 'wpsc_workflows_current_version', 0 );
		}

		/**
		 * Check for upgrade
		 */
		public static function check_upgrade() {

			if ( self::$current_version != WPSC_WF_VERSION ) {
				self::$is_upgrade = true;
			}
		}

		/**
		 * First time installation
		 */
		public static function initial_setup() {

			global $wpdb;

			// agent role permission.
			$roles = get_option( 'wpsc-agent-roles', array() );
			foreach ( $roles as $index => $role ) {
				$roles[ $index ]['caps']['workflows-unassigned']      = true;
				$roles[ $index ]['caps']['workflows-assigned-me']     = true;
				$roles[ $index ]['caps']['workflows-assigned-others'] = true;
			}
			update_option( 'wpsc-agent-roles', $roles );

			// install widget.
			self::install_widget();
		}

		/**
		 * Upgrade the version
		 */
		public static function upgrade() {

			self::set_upgrade_complete();
		}

		/**
		 * Mark upgrade as complete
		 */
		public static function set_upgrade_complete() {

			update_option( 'wpsc_workflows_current_version', WPSC_WF_VERSION );
			self::$current_version = WPSC_WF_VERSION;
			self::$is_upgrade      = false;
		}

		/**
		 * Actions to perform after plugin activated
		 *
		 * @return void
		 */
		public static function activate() {

			// Widget might not be installed as a result of race condition while upgrade.
			// There is an option for administrator to deactivate and then activate the plugin.
			self::install_widget();
			do_action( 'wpsc_workflows_activate' );
		}

		/**
		 * Actions to perform after plugin deactivated
		 *
		 * @return void
		 */
		public static function deactivate() {

			do_action( 'wpsc_workflows_deactivate' );
		}

		/**
		 * Install widget if not already installed
		 *
		 * @return void
		 */
		public static function install_widget() {

			$widgets = get_option( 'wpsc-ticket-widget', array() );
			if ( ! isset( $widgets['workflows'] ) ) {

				$agent_roles = array_keys( get_option( 'wpsc-agent-roles', array() ) );
				$label = esc_attr__( 'Workflows', 'wpsc-workflows' );
				$workflows = array(
					'workflows' => array(
						'title'               => $label,
						'is_enable'           => 1,
						'allow-customer'      => 1,
						'allowed-agent-roles' => $agent_roles,
						'callback'            => 'wpsc_get_tw_workflows()',
						'class'               => 'WPSC_ITW_Workflows',
					),
				);
				// append the widget on the top.
				$widgets = array_merge( $workflows, $widgets );
				update_option( 'wpsc-ticket-widget', $widgets );

				// string translations.
				$string_translations = get_option( 'wpsc-string-translation' );
				$string_translations['wpsc-twt-workflows'] = $label;
				update_option( 'wpsc-string-translation', $string_translations );
			}
		}
	}
endif;

WPSC_WF_Installation::init();
