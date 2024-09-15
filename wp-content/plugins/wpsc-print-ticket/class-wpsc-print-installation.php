<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_PRINT_Installation' ) ) :

	final class WPSC_PRINT_Installation {

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

				define( 'WPSC_PRINT_INSTALLING', true );

				// Do not allow parallel process to run.
				if ( 'yes' === get_transient( 'wpsc_print_installing' ) ) {
					return;
				}

				// Set transient.
				set_transient( 'wpsc_print_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				// Run installation.
				if ( self::$current_version == 0 ) {

					add_action( 'init', array( __CLASS__, 'initial_setup' ), 1 );
					add_action( 'init', array( __CLASS__, 'set_upgrade_complete' ), 1 );

				} else {

					add_action( 'init', array( __CLASS__, 'upgrade' ), 1 );
				}

				// Delete transient.
				delete_transient( 'wpsc_print_installing' );
			}

			// activation functionality.
			register_activation_hook( WPSC_PRINT_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

			// Deactivate functionality.
			register_deactivation_hook( WPSC_PRINT_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Check version
		 */
		public static function get_current_version() {

			self::$current_version = get_option( 'wpsc_pt_current_version', 0 );
		}

		/**
		 * Check for upgrade
		 */
		public static function check_upgrade() {

			if ( self::$current_version != WPSC_PRINT_VERSION ) {
				self::$is_upgrade = true;
			}
		}

		/**
		 * DB upgrade addon installer hook callback
		 *
		 * @return void
		 */
		public static function upgrade_install() {

			self::initial_setup();
			self::set_upgrade_complete();
		}

		/**
		 * First time installation
		 */
		public static function initial_setup() {

			$string_translations = get_option( 'wpsc-string-translation' );

			// general settings.
			update_option(
				'wpsc-pt-general-settings',
				array(
					'thankyou-page-button'    => 1,
					'allow-print-to-customer' => 0,
					'button-label'            => esc_attr__( 'Print', 'wpsc-pt' ),
					'library'                 => is_rtl() ? 'tcpdf' : 'dompdf',
				)
			);

			// template.
			$template = array(
				'header-height'    => is_rtl() ? 50 : 100, // give preference to tcpdf.
				'header-font-size' => 14,
				'header'           => array(
					'text'   => '<p>Ticket ID : #{{ticket_id}}</p><p>Category : {{ticket_category}}</p><p>Priority : {{ticket_priority}}</p>',
					'editor' => 'html',
				),
				'body-font-size'   => 14,
				'body'             => array(
					'text'   => '<strong>Name : </strong>{{customer_name}}<br><strong>Email : </strong>{{customer_email}}<br><strong>Date : </strong>{{date_created}}<br><br><strong>Subject : </strong>{{ticket_subject}}<br><br><strong>Description : </strong><br>{{ticket_description}}',
					'editor' => 'html',
				),
				'footer-height'    => is_rtl() ? 15 : 50,
				'footer-font-size' => 14,
				'footer'           => array(
					'text'   => '<div>I am Footer</div>',
					'editor' => 'html',
				),
			);
			update_option( 'wpsc-pt-template-settings', $template );
			$string_translations['wpsc-pt-header'] = $template['header']['text'];
			$string_translations['wpsc-pt-body'] = $template['body']['text'];
			$string_translations['wpsc-pt-footer'] = $template['footer']['text'];

			// update string translations.
			update_option( 'wpsc-string-translation', $string_translations );
		}

		/**
		 * Upgrade the version
		 */
		public static function upgrade() {

			if ( version_compare( self::$current_version, '3.0.4', '<' ) ) {

				$setting = get_option( 'wpsc-pt-general-settings' );
				$setting['library'] = 'dompdf';
				update_option( 'wpsc-pt-general-settings', $setting );
			}

			self::set_upgrade_complete();
		}

		/**
		 * Mark upgrade as complete
		 */
		public static function set_upgrade_complete() {

			update_option( 'wpsc_pt_current_version', WPSC_PRINT_VERSION );
			self::$current_version = WPSC_PRINT_VERSION;
			self::$is_upgrade      = false;
		}

		/**
		 * Actions to perform after plugin activated
		 *
		 * @return void
		 */
		public static function activate() {

			do_action( 'wpsc_pt_activate' );
		}

		/**
		 * Actions to perform after plugin deactivated
		 *
		 * @return void
		 */
		public static function deactivate() {

			do_action( 'wpsc_pt_deactivate' );
		}
	}
endif;

WPSC_PRINT_Installation::init();
