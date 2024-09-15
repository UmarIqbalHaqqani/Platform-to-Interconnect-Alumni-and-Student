<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Settings' ) ) :

	final class WPSC_WF_Settings {

		/**
		 * Tabs for this section
		 *
		 * @var array
		 */
		private static $tabs;

		/**
		 * Current tab
		 *
		 * @var string
		 */
		public static $current_tab;

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// add settings section.
			add_filter( 'wpsc_icons', array( __CLASS__, 'add_icons' ) );
			add_filter( 'wpsc_settings_page_sections', array( __CLASS__, 'add_settings_tab' ) );

			// Load tabs for this section.
			add_action( 'admin_init', array( __CLASS__, 'load_tabs' ) );

			// Add current tab to admin localization data.
			add_filter( 'wpsc_admin_localizations', array( __CLASS__, 'localizations' ) );

			// Load section tab layout.
			add_action( 'wp_ajax_wpsc_get_wf_settings', array( __CLASS__, 'get_wf_settings' ) );

			// trigger filter.
			add_filter( 'wpsc_wf_triggers', array( __CLASS__, 'filter_triggers' ) );

			// filter conditions.
			add_filter( 'wpsc_wf_conditions', array( __CLASS__, 'filter_conditions' ) );
		}

		/**
		 * Add icons to library
		 *
		 * @param array $icons - icon list.
		 * @return array
		 */
		public static function add_icons( $icons ) {

			$icons['workflow'] = file_get_contents( WPSC_WF_ABSPATH . 'asset/icons/workflow.svg' ); //phpcs:ignore
			return $icons;
		}

		/**
		 * Settings tab
		 *
		 * @param array $sections - setting menus.
		 * @return array
		 */
		public static function add_settings_tab( $sections ) {

			$sections['workflows'] = array(
				'slug'     => 'workflows',
				'icon'     => 'workflow',
				'label'    => esc_attr__( 'Workflows', 'wpsc-workflows' ),
				'callback' => 'wpsc_get_wf_settings',
			);
			return $sections;
		}

		/**
		 * Load tabs for this section
		 */
		public static function load_tabs() {

			self::$tabs = apply_filters(
				'wpsc_wf_tabs',
				array(
					'automatic' => array(
						'slug'     => 'automatic',
						'label'    => esc_attr( wpsc__( 'Automatic', 'supportcandy' ) ),
						'callback' => 'wpsc_wf_get_automatic_settings',
					),
					'manual'    => array(
						'slug'     => 'manual',
						'label'    => esc_attr__( 'Manual', 'wpsc-ep' ),
						'callback' => 'wpsc_wf_get_manual_settings',
					),
				)
			);

			self::$current_tab = isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'automatic'; //phpcs:ignore
		}

		/**
		 * Add localizations to local JS
		 *
		 * @param array $localizations - localizations.
		 * @return array
		 */
		public static function localizations( $localizations ) {

			if ( ! ( WPSC_Settings::$is_current_page && WPSC_Settings::$current_section === 'workflows' ) ) {
				return $localizations;
			}

			// Current section.
			$localizations['current_tab'] = self::$current_tab;

			return $localizations;
		}

		/**
		 * General setion body layout
		 *
		 * @return void
		 */
		public static function get_wf_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}?>

			<div class="wpsc-setting-tab-container">
				<?php
				foreach ( self::$tabs as $key => $tab ) :
					$active = self::$current_tab === $key ? 'active' : ''
					?>
					<button 
						class="<?php echo esc_attr( $key ) . ' ' . esc_attr( $active ); ?>"
						onclick="<?php echo esc_attr( $tab['callback'] ) . '();'; ?>">
						<?php echo esc_attr( $tab['label'] ); ?>
						</button>
					<?php
				endforeach;
				?>
			</div>
			<div class="wpsc-setting-section-body"></div>
			<?php
			wp_die();
		}

		/**
		 * Filter triggers
		 *
		 * @param array $triggers - all possible triggers.
		 * @return array
		 */
		public static function filter_triggers( $triggers ) {

			$ignore_list = apply_filters( 'wpsc_wf_trigger_ignore_list', array( 'delete-ticket' ) );
			foreach ( $triggers as $key => $val ) {
				if ( in_array( $key, $ignore_list ) ) {
					unset( $triggers[ $key ] );
				}
			}

			return $triggers;
		}

		/**
		 * Filter conditions for workflow settings
		 *
		 * @param array $conditions - all possible ticket conditions.
		 * @return array
		 */
		public static function filter_conditions( $conditions ) {

			$ignore_list = apply_filters(
				'wpsc_wf_conditions_ignore_list',
				array(
					'cft'   => array( // custom field types.
						'df_id',
						'cf_woo_order',
						'cf_edd_order',
						'cf_lifter_order',
						'cf_tutor_order',
						'cf_learnpress_order',
					),
					'other' => array(), // other(custom) condition slug.
				)
			);

			foreach ( $conditions as $slug => $item ) {

				if ( $item['type'] == 'cf' ) {

					$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
					if ( in_array( $cf->type::$slug, $ignore_list['cft'] ) ) {
						unset( $conditions[ $slug ] );
					}
				} else {

					if ( in_array( $slug, $ignore_list['other'] ) ) {
						unset( $conditions[ $slug ] );
					}
				}
			}

			return $conditions;
		}
	}
endif;

WPSC_WF_Settings::init();
