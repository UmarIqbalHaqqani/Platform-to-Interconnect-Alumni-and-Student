<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ST_Admin' ) ) :

	final class WPSC_ST_Admin {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// load scripts & styles.
			add_action( 'wpsc_js_backend', array( __CLASS__, 'backend_scripts' ) );
			add_action( 'wpsc_css_backend', array( __CLASS__, 'backend_styles' ) );
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_scripts() {

			echo file_get_contents( WPSC_ST_ABSPATH . 'asset/js/admin.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_styles() {

			if ( is_rtl() ) {
				echo file_get_contents( WPSC_ST_ABSPATH . 'asset/css/admin-rtl.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			} else {
				echo file_get_contents( WPSC_ST_ABSPATH . 'asset/css/admin.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			}
		}
	}
endif;

WPSC_ST_Admin::init();
