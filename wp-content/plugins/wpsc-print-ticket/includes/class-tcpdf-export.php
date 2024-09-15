<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'TCPDF' ) ) {
	return;
}

class TCPDF_EXPORT extends TCPDF {

	/**
	 * Ticket object
	 *
	 * @var WPSC_Ticket
	 */
	public static $ticket;

	/**
	 * PDF custom header
	 *
	 * @return void
	 */
	public function Header() {

		$pt_settings = get_option( 'wpsc-pt-template-settings' );
		$this->SetMargins( PDF_MARGIN_LEFT, $pt_settings['header-height'], PDF_MARGIN_RIGHT );
		$this->SetFont( 'dejavusans', '', $pt_settings['header-font-size'] );
		ob_start();
		echo wp_kses_post( WPSC_Macros::replace( $pt_settings['header']['text'], self::$ticket ) );
		$html = ob_get_clean();
		$this->writeHTML( $html, true, false, false, false, '' );
	}

	/**
	 * PDF custom footer
	 *
	 * @return void
	 */
	public function Footer() {
		$pt_settings = get_option( 'wpsc-pt-template-settings' );
		$this->SetY( -  intval( $pt_settings['footer-height'] ) );
		$this->SetFont( 'dejavusans', '', $pt_settings['footer-font-size'] );
		ob_start();
		echo wp_kses_post( WPSC_Macros::replace( $pt_settings['footer']['text'], self::$ticket ) );
		$html = ob_get_clean();
		$this->writeHTML( $html, true, false, false, false, '' );
	}
}
