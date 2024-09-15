<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_PC_Admin' ) ) :

	final class WPSC_PC_Admin {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// load scripts & styles.
			add_action( 'wpsc_js_frontend', array( __CLASS__, 'frontend_scripts' ) );
			add_action( 'wpsc_js_backend', array( __CLASS__, 'backend_scripts' ) );
		}

		/**
		 * Frontend scripts
		 *
		 * @return void
		 */
		public static function frontend_scripts() {

			echo file_get_contents( WPSC_PC_ABSPATH . 'assets/js/public.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_scripts() {

			echo file_get_contents( WPSC_PC_ABSPATH . 'assets/js/admin.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}
	}
endif;

WPSC_PC_Admin::init();
