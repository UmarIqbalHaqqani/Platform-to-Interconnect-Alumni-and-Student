<?php // phpcs:ignore
/**
 * Plugin Name: SupportCandy - Print Tickets
 * Plugin URI: https://supportcandy.net/
 * Description: Print ticket for SupportCandy!
 * Version: 3.0.7
 * Author: SupportCandy
 * Author URI: https://supportcandy.net/
 * Requires at least: 5.6
 * Tested up to: 6.0
 * Text Domain: wpsc-pt
 * Domain Path: /i18n
 */

if ( ! class_exists( 'PSM_Support_Candy' ) ) {
	return;
}

if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
	return;
}

// exit if core plugin is installing.
if ( defined( 'WPSC_INSTALLING' ) ) {
	return;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}
use Dompdf\Dompdf;

if ( ! class_exists( 'WPSC_PRINT' ) ) :

	final class WPSC_PRINT {

		/**
		 * Addon version
		 *
		 * @var integer
		 */
		public static $version = '3.0.7';

		/**
		 * Constructor for main class
		 */
		public static function init() {

			self::define_constants();
			add_action( 'init', array( __CLASS__, 'load_textdomain' ), 1 );
			self::load_files();

			// Return if installation is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) || defined( 'WPSC_PRINT_INSTALLING' ) ) {
				return;
			}

			add_action( 'admin_init', array( __CLASS__, 'plugin_updator' ) );
		}

		/**
		 * Defines global constants that can be availabel anywhere in WordPress
		 *
		 * @return void
		 */
		public static function define_constants() {

			self::define( 'WPSC_PRINT_PLUGIN_FILE', __FILE__ );
			self::define( 'WPSC_PRINT_ABSPATH', dirname( __FILE__ ) . '/' );
			self::define( 'WPSC_PRINT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			self::define( 'WPSC_PRINT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			self::define( 'WPSC_PRINT_VERSION', self::$version );
			self::define( 'WPSC_PRINT_STORE_ID', 16152 );
		}

		/**
		 * Loads internationalization strings
		 *
		 * @return void
		 */
		public static function load_textdomain() {

			$locale = apply_filters( 'plugin_locale', get_locale(), 'wpsc-pt' );
			load_textdomain( 'wpsc-pt', WP_LANG_DIR . '/supportcandy/wpsc-pt-' . $locale . '.mo' );
			load_plugin_textdomain( 'wpsc-pt', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n' );
		}

		/**
		 * Load all classes
		 *
		 * @return void
		 */
		private static function load_files() {

			// Load installation.
			include_once WPSC_PRINT_ABSPATH . 'class-wpsc-print-installation.php';

			// Return if installation is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) || defined( 'WPSC_PRINT_INSTALLING' ) ) {
				return;
			}

			$settings = get_option( 'wpsc-pt-general-settings' );

			// Load dompdf library.
			if ( ! class_exists( 'Dompdf\Dompdf' ) && $settings['library'] == 'dompdf' ) {
				require_once WPSC_PRINT_ABSPATH . 'asset/lib/dompdf/autoload.inc.php';
			}

			if ( ! class_exists( 'TCPDF' ) && $settings['library'] == 'tcpdf' ) {
				require_once WPSC_PRINT_ABSPATH . 'asset/lib/tcpdf/tcpdf.php';
			}

			// Load common classes.
			foreach ( glob( WPSC_PRINT_ABSPATH . 'includes/*.php' ) as $filename ) {
				include_once $filename;
			}
		}

		/**
		 * Define constants
		 *
		 * @param string $name - name of global constant.
		 * @param string $value - value of constant.
		 * @return void
		 */
		private static function define( $name, $value ) {

			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Plugin updator
		 *
		 * @return void
		 */
		public static function plugin_updator() {

			$licenses = get_option( 'wpsc-licenses', array() );
			$license  = isset( $licenses['print'] ) ? $licenses['print'] : array();
			if ( $license ) {
				$edd_updater = new WPSC_EDD_SL_Plugin_Updater(
					WPSC_STORE_URL,
					__FILE__,
					array(
						'version' => WPSC_PRINT_VERSION,
						'license' => $license['key'],
						'item_id' => WPSC_PRINT_STORE_ID,
						'author'  => 'Pradeep Makone',
						'url'     => home_url(),
					)
				);
			}
		}
	}
endif;

WPSC_PRINT::init();
