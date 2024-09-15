<?php

namespace Hostinger\EasyOnboarding\Admin;

use Hostinger\EasyOnboarding\Helper;
use Hostinger\WpHelper\Utils;

defined( 'ABSPATH' ) || exit;


class Hooks {

	private Helper $helper;

	private const DAY_IN_SECONDS = 86400;
	public const WOOCOMMERCE_PAGES = array(
		'/wp-admin/admin.php?page=wc-admin',
		'/wp-admin/edit.php?post_type=shop_order',
		'/wp-admin/admin.php?page=wc-admin&path=/customers',
		'/wp-admin/edit.php?post_type=shop_coupon&legacy_coupon_menu=1',
		'/wp-admin/admin.php?page=wc-admin&path=/marketing',
		'/wp-admin/admin.php?page=wc-reports',
		'/wp-admin/admin.php?page=wc-settings',
		'/wp-admin/admin.php?page=wc-status',
		'/wp-admin/admin.php?page=wc-admin&path=/extensions',
		'/wp-admin/edit.php?post_type=product',
		'/wp-admin/post-new.php?post_type=product',
		'/wp-admin/edit.php?post_type=product&page=product-reviews',
		'/wp-admin/edit.php?post_type=product&page=product_attributes',
		'/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product',
		'/wp-admin/edit-tags.php?taxonomy=product_tag&post_type=product',
		'/wp-admin/admin.php?page=wc-admin&path=/analytics/overview',
		'/wp-admin/admin.php?page=wc-admin',
	);

	public function __construct() {
		$this->helper = new Helper();

		// Admin footer actions
		add_action( 'admin_footer', array( $this, 'rate_plugin' ) );

		// Admin init actions
		add_action( 'admin_init', array( $this, 'admin_init_actions' ) );

		// Admin notices
		add_action( 'admin_notices', array( $this, 'omnisend_discount_notice' ) );

		// Admin Styles
		add_action( 'admin_head', array( $this, 'hide_notices' ) );

		// WooCommerce filters
		add_filter( 'woocommerce_prevent_automatic_wizard_redirect', function () {
			return true;
		}, 999 );
		add_filter( 'woocommerce_enable_setup_wizard', function () {
			return false;
		}, 999 );

		// Spectra
		add_filter( 'uagb_enable_redirect_activation', function () {
			return false;
		}, 999 );

		// Prevent Stripe checkout onboarding redirect
		add_filter( 'cpsw_prevent_onboarding_redirect', function () {
			return true;
		}, 999 );
	}

	public function hide_notices() {
		?>
		<style>
            .post-php.post-type-product #wpadminbar .wpforms-menu-notification-counter,
            .post-php.post-type-product #wpadminbar .aioseo-menu-notification-counter,
            .post-php.post-type-product .woocommerce-layout__header-tasks-reminder-bar,
            .post-php.post-type-product .litespeed_icon.notice.is-dismissible,
            .post-php.post-type-product .monsterinsights-menu-notification-indicator,
            .post-php.post-type-product .aioseo-review-plugin-cta,
            .post-php.post-type-product .omnisend-connection-notice-hidden,
            .post-php.post-type-product #astra-upgrade-pro-wc {
                display: none !important;
            }

            .notice.hts-notice {
                display: block !important;
            }

			<?php if ( $this->is_woocommerce_admin_page() ) : ?>
            #wpadminbar .wpforms-menu-notification-counter,
            #wpadminbar .aioseo-menu-notification-counter,
            .woocommerce-layout__header-tasks-reminder-bar,
            .litespeed_icon.notice.is-dismissible,
            .monsterinsights-menu-notification-indicator,
            .aioseo-review-plugin-cta,
            .omnisend-connection-notice-hidden,
            #astra-upgrade-pro-wc {
                display: none !important;
            }

            .notice.hts-notice {
                display: block !important;
            }

			<?php endif; ?>
		</style>
		<?php
	}

	public function admin_init_actions(): void {
		$this->hide_astra_builder_selection_screen();
		$this->hide_metaboxes();
		$this->hide_monsterinsight_notice();
	}

	public function hide_metaboxes(): void {
		$this->hide_plugin_metabox( 'google-analytics-for-wordpress/googleanalytics.php', 'monsterinsights-metabox', 'metaboxhidden_product' );
		$this->hide_plugin_metabox( 'all-in-one-seo-pack/all_in_one_seo_pack.php', 'aioseo-settings', 'aioseo-settings' );
		$this->hide_plugin_metabox( 'litespeed-cache/litespeed-cache.php', 'litespeed_meta_boxes', 'litespeed_meta_boxes' );
		$this->hide_astra_theme_metabox();
		$this->hide_custom_fields_metabox();
	}

	public function hide_astra_theme_metabox(): void {
		if ( ! $this->is_astra_theme_active() ) {
			return;
		}
		$this->hide_metabox( 'astra_settings_meta_box', 'astra_metabox' );
	}

	public function hide_custom_fields_metabox(): void {
		$this->hide_metabox( 'postcustom', 'custom_fields_metabox' );
	}

	public function hide_metabox( string $metabox_id, string $transient_suffix ): void {
		$helper        = new Utils();
		$user_id       = get_current_user_id();
		$transient_key = $transient_suffix . '_' . $user_id;
		$hide_metabox  = get_transient( $transient_key );

		if ( $hide_metabox ) {
			return;
		}

		$hide_metabox = get_user_meta( $user_id, 'metaboxhidden_product', true );

		if ( ! is_array( $hide_metabox ) ) {
			$hide_metabox = array();
		}

		if ( $helper->isThisPage( 'post-new.php?post_type=product' ) ) {
			if ( ! in_array( $metabox_id, $hide_metabox ) ) {
				array_push( $hide_metabox, $metabox_id );
			}

			update_user_meta( $user_id, 'metaboxhidden_product', $hide_metabox );
			set_transient( $transient_key, 'hidden', self::DAY_IN_SECONDS );
		}
	}

	public function is_astra_theme_active(): bool {
		$theme = wp_get_theme();

		return $theme->get( 'Name' ) === 'Astra';
	}

	public function hide_monsterinsight_notice(): void {
		if ( is_plugin_active( 'google-analytics-for-wordpress/googleanalytics.php' ) ) {
			define( 'MONSTERINSIGHTS_DISABLE_TRACKING', true );
		}
	}

	public function rate_plugin(): void {
		$promotional_banner_hidden = get_transient( 'hts_hide_promotional_banner_transient' );
		$two_hours_in_seconds      = 7200;

		if ( $promotional_banner_hidden && time() > $promotional_banner_hidden + $two_hours_in_seconds ) {
			require_once HOSTINGER_EASY_ONBOARDING_ABSPATH . 'includes/Admin/Views/Partials/RateUs.php';
		}
	}

	public function omnisend_discount_notice(): void {
		$omnisend_notice_hidden = get_transient( 'hts_omnisend_notice_hidden' );

		if ( $omnisend_notice_hidden === false && $this->helper->is_this_page( '/wp-admin/admin.php?page=omnisend' ) && ( Helper::is_plugin_active( 'class-omnisend-core-bootstrap' ) || Helper::is_plugin_active( 'omnisend-woocommerce' ) ) ) : ?>
			<div class="notice notice-info hts-admin-notice hts-omnisend">
				<p><?php echo wp_kses( __( 'Use the special discount code <b>ONLYHOSTINGER30</b> to get 30% off on Omnisend for 6 months when you upgrade.', 'hostinger-easy-onboarding' ), array( 'b' => array() ) ); ?></p>
				<div>
					<a class="button button-primary"
					   href="https://your.omnisend.com/LXqyZ0"
					   target="_blank"><?= esc_html__( 'Get Discount', 'hostinger-easy-onboarding' ); ?></a>
					<button type="button" class="notice-dismiss"></button>
				</div>
			</div>
		<?php endif;
		wp_nonce_field( 'hts_close_omnisend', 'hts_close_omnisend_nonce', true );
	}

	public function hide_astra_builder_selection_screen(): void {
		add_filter( 'st_enable_block_page_builder', '__return_true' );
	}

	public function hide_plugin_metabox( string $plugin_slug, string $metabox_id, string $transient_suffix ): void {
		$helper        = new Utils();
		$user_id       = get_current_user_id();
		$transient_key = $transient_suffix . '_' . $user_id;
		$hide_metabox  = get_transient( $transient_key );

		if ( $hide_metabox ) {
			return;
		}

		$hide_metabox = get_user_meta( $user_id, 'metaboxhidden_product', true );

		if ( ! is_plugin_active( $plugin_slug ) ) {
			return;
		}

		if ( ! is_array( $hide_metabox ) ) {
			$hide_metabox = array();
		}

		if ( $helper->isThisPage( 'post-new.php?post_type=product' ) ) {
			if ( ! in_array( $metabox_id, $hide_metabox ) ) {
				array_push( $hide_metabox, $metabox_id );
			}

			update_user_meta( $user_id, 'metaboxhidden_product', $hide_metabox );
			set_transient( $transient_key, 'hidden', self::DAY_IN_SECONDS );
		}
	}

	private function is_woocommerce_admin_page(): bool {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$current_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] );

		if ( defined( 'DOING_AJAX' ) && \DOING_AJAX ) {
			return false;
		}

		if ( isset( $current_uri ) && strpos( $current_uri, '/wp-json/' ) !== false ) {
			return false;
		}

		foreach ( self::WOOCOMMERCE_PAGES as $page ) {
			if ( strpos( $current_uri, $page ) !== false ) {
				return true;
			}
		}

		return false;
	}
}

