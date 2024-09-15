<?php
use Dompdf\Dompdf;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_Print_Frontend' ) ) :

	final class WPSC_Print_Frontend {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// scripts and styles.
			add_action( 'wpsc_js_frontend', array( __CLASS__, 'frontend_scripts' ) );
			add_action( 'wpsc_css_frontend', array( __CLASS__, 'frontend_styles' ) );

			// add print button in individual ticket.
			add_filter( 'wpsc_individual_ticket_actions', array( __CLASS__, 'print_button' ), 10, 2 );

			// thankyou page print.
			add_filter( 'wpsc_after_thankyou_text', array( __CLASS__, 'after_thankyou_text' ), 10, 2 );

			// download file.
			add_action( 'init', array( __CLASS__, 'print_ticket' ), 100 );

			add_filter( 'wpsc_ticket_field_val_textarea', array( __CLASS__, 'get_textarea_field_val' ), 10, 4 );
		}

		/**
		 * Frontend scripts
		 *
		 * @return void
		 */
		public static function frontend_scripts() {

			echo file_get_contents( WPSC_PRINT_ABSPATH . 'asset/js/public.js' ) . PHP_EOL . PHP_EOL; //phpcs:ignore
		}

		/**
		 * Frontend styles
		 *
		 * @return void
		 */
		public static function frontend_styles() {

			if ( is_rtl() ) {
				echo file_get_contents( WPSC_PRINT_ABSPATH . 'asset/css/public-rtl.css' ) . PHP_EOL . PHP_EOL; //phpcs:ignore
			} else {
				echo file_get_contents( WPSC_PRINT_ABSPATH . 'asset/css/public.css' ) . PHP_EOL . PHP_EOL; //phpcs:ignore
			}
		}

		/**
		 * After thank you page text
		 *
		 * @param string      $thankyou_text - text.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return string
		 */
		public static function after_thankyou_text( $thankyou_text, $ticket ) {

			$current_user = WPSC_Current_User::$current_user;
			$settings     = get_option( 'wpsc-pt-general-settings' );

			if ( $settings['thankyou-page-button'] &&
				(
					$current_user->is_agent ||
					(
						$settings['allow-print-to-customer'] &&
						( $current_user->is_customer || $current_user->is_guest )
					)
				)
			) {
				$thankyou_text .= '<button class="wpsc-button normal primary" onclick="wpsc_print_ticket(' . $ticket->id . ', \'' . wp_create_nonce( 'wpsc_print_ticket' ) . '\');" type="submit">' . $settings['button-label'] . '</button></a>';
			}
			return $thankyou_text;
		}

		/**
		 * Print button
		 *
		 * @param array       $actions - actions.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return array
		 */
		public static function print_button( $actions, $ticket ) {

			$current_user = WPSC_Current_User::$current_user;
			$settings     = get_option( 'wpsc-pt-general-settings' );

			if ( ! $current_user->is_agent && $current_user->is_customer && ! ( $settings['allow-print-to-customer'] ) ) {
				return $actions;
			}

			$actions['print'] = array(
				'label'    => $settings['button-label'],
				'callback' => 'wpsc_print_ticket(' . $ticket->id . ', \'' . wp_create_nonce( 'wpsc_print_ticket' ) . '\');',
			);
			return $actions;
		}

		/**
		 * Print ticket
		 *
		 * @return void
		 */
		public static function print_ticket() {

			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'wpsc_downlaod_pdf' ) {

				if ( check_ajax_referer( 'wpsc_print_ticket', '_ajax_nonce', false ) != 1 ) {
					wp_send_json_error( 'Unauthorised request!', 401 );
				}

				$ticket_id = isset( $_REQUEST['ticket_id'] ) ? intval( $_REQUEST['ticket_id'] ) : 0;
				if ( ! $ticket_id ) {
					wp_send_json_error( 'Unautorized', 400 );
				}

				$current_user = WPSC_Current_User::$current_user;
				$settings     = get_option( 'wpsc-pt-general-settings' );

				$ticket = new WPSC_Ticket( $ticket_id );
				if ( ! $ticket->id ) {
					wp_send_json_error( 'Unautorized', 400 );
				}

				WPSC_Individual_Ticket::$ticket = $ticket;

				if (
					( ! $current_user->is_agent && WPSC_Individual_Ticket::is_customer() && ! $settings['allow-print-to-customer'] ) ||
					( $current_user->is_agent && ! ( WPSC_Individual_Ticket::has_ticket_cap( 'view' ) ) )
				) {
					wp_send_json_error( 'Unautorized', 400 );
				}

				if ( $settings['library'] == 'dompdf' ) {
					self::print_ticket_dompdf( $ticket );
				} elseif ( $settings['library'] == 'tcpdf' ) {
					self::print_ticket_tcpdf( $ticket );
				}
			}
		}

		/**
		 * Print ticket using DOMPDF library
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function print_ticket_dompdf( $ticket ) {

			$pt_settings  = get_option( 'wpsc-pt-template-settings' );
			$filename = 'Ticket_' . $ticket->id . '.pdf';

			// Header.
			$header_height = stripslashes( $pt_settings['header-height'] );
			ob_start();?>
			<style type="text/css">
				#header_right_info { width: 100%; position: relative; margin-top: -60px; }
				#tbl_header_info { width: 300px; right: 0; }
				#tbl_header_info, #tbl_header_info tr, #tbl_header_info td { border: none; }
			</style>
			<?php

			echo wp_kses_post( html_entity_decode( $pt_settings['header']['text'] ) );
			$header_html = ob_get_clean();

			// Footer.
			$footer_height = stripslashes( $pt_settings['footer-height'] );
			ob_start();

			echo wp_kses_post( html_entity_decode( $pt_settings['footer']['text'] ) );
			$footer_html = ob_get_clean();

			// Body.
			ob_start();
			echo wp_kses_post( html_entity_decode( $pt_settings['body']['text'] ) );
			$body_html = ob_get_clean();

			// Rendering.
			ob_start();
			?>
			<html>
				<head>
					<style>
						header { 
							position: relative; 
							top: 0px; 
							left: 0px; 
							right: 0px;
							height: <?php echo esc_attr( $header_height ); ?>px;
							font-size: <?php echo esc_attr( $pt_settings['header-font-size'] ); ?>;
						}
						footer { 
							position: fixed; 
							bottom: 0px; 
							left: 0px; 
							right: 0px; 
							height: <?php echo esc_attr( $footer_height ); ?>px; 
							font-size: <?php echo esc_attr( $pt_settings['footer-font-size'] ); ?>;
						}
						body {
							font-family: DejaVu Sans, sans-serif;
							font-size: <?php echo esc_attr( $pt_settings['body-font-size'] ); ?>;
						}
					</style>
				</head>
				<body>
					<header>
						<?php echo wp_kses_post( WPSC_Macros::replace( $pt_settings['header']['text'], $ticket, 'print_ticket' ) ); ?>
					</header>

					<main>
						<?php echo wp_kses_post( WPSC_Macros::replace( $pt_settings['body']['text'], $ticket, 'print_ticket' ) ); ?>
					</main>

					<footer>
						<?php echo wp_kses_post( WPSC_Macros::replace( $pt_settings['footer']['text'], $ticket, 'print_ticket' ) ); ?>
					</footer>
				</body>
			</html>
			<?php
			$html_to_render = apply_filters( 'wpsc_print_ticket_content', ob_get_clean(), $ticket );

			$dompdf = new Dompdf();
			$dompdf->set_option( 'isRemoteEnabled', true );
			$dompdf = apply_filters( 'wpsc_print_ticket_settings', $dompdf );
			$dompdf->loadHtml( $html_to_render );
			$dompdf->render();
			$canvas = $dompdf->get_canvas();
			$canvas->page_text( 300, 770, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10, array( 0, 0, 0 ) );
			$dompdf->stream( $filename );
			exit;
		}

		/**
		 * Print ticket using TCPDF library
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function print_ticket_tcpdf( $ticket ) {

			$pt_settings  = get_option( 'wpsc-pt-template-settings' );
			$filename = 'Ticket_' . $ticket->id;

			// create new PDF document.
			$pdf = new TCPDF_EXPORT( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
			$pdf::$ticket = $ticket;

			// set document information.
			$pdf->SetTitle( $filename );

			// set default header data.
			$pdf->SetHeaderData( PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING );

			// set header and footer fonts.
			$pdf->setHeaderFont( array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
			$pdf->setFooterFont( array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

			// set default monospaced font.
			$pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

			// set margins.
			$pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
			$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
			$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );

			$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

			// set auto page breaks.
			$pdf->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );

			// set font.
			$pdf->setFont( 'dejavusans', '', $pt_settings['body-font-size'], '', true );

			// add a page.
			$pdf->AddPage();

			// set some text to print.

			ob_start();
			echo wp_kses_post( WPSC_Macros::replace( $pt_settings['body']['text'], $ticket ) );
			$html = ob_get_clean();

			$pdf->writeHTML( $html );

			// Close and output PDF document.
			$pdf->Output( $filename . '.pdf', 'D' );
			exit;
		}

		/**
		 * Format textarea value
		 *
		 * @param string            $value - field value.
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @param string            $module - module name.
		 * @return string
		 */
		public static function get_textarea_field_val( $value, $cf, $ticket, $module ) {

			if ( $module == 'print_ticket' ) {
				return nl2br( $value );
			} else {
				return $value;
			}
		}
	}
endif;

WPSC_Print_Frontend::init();
