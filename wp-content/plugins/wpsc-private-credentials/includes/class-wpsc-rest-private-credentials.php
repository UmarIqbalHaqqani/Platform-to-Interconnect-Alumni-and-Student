<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_REST_Private_Credentials' ) ) :

	final class WPSC_REST_Private_Credentials {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wpsc_rest_register_routes', array( __CLASS__, 'register_routes' ) );
			add_filter( 'wpsc_rest_prevent_ticket_data', array( __CLASS__, 'prevent_ticket_data' ) );
		}

		/**
		 * Register routes
		 *
		 * @return void
		 */
		public static function register_routes() {

		}

		/**
		 * Modify ticket response for usergroup fields
		 *
		 * @param array $data - ticket slug array.
		 * @return array
		 */
		public static function prevent_ticket_data( $data ) {

			$data[] = 'pc_data';
			return $data;
		}
	}
endif;

WPSC_REST_Private_Credentials::init();
