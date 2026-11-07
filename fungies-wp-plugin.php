<?php
/**
 * Plugin Name: Fungies for WooCommerce
 * Plugin URI: https://github.com/dukenukemall/fungies-wp-plugin
 * Description: Connect your WooCommerce store to Fungies.io — sync products, use Fungies checkout, and keep orders in sync.
 * Version: 2.4.5
 * Author: Fungies
 * Author URI: https://fungies.io
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fungies-for-woocommerce
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FUNGIES_WP_VERSION', '2.4.5' );
define( 'FUNGIES_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FUNGIES_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FUNGIES_WP_PLUGIN_FILE', __FILE__ );
define( 'FUNGIES_API_BASE_URL', 'https://api.fungies.io/v0' );
define( 'FUNGIES_API_STAGING_URL', 'https://api.stage.fungies.net/v0' );

require_once FUNGIES_WP_PLUGIN_DIR . 'includes/class-fungies-loader.php';

add_action( 'woocommerce_blocks_loaded', 'fungies_register_block_payment_method' );

function fungies_register_block_payment_method() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once FUNGIES_WP_PLUGIN_DIR . 'includes/class-fungies-blocks-payment.php';

	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry ) {
			$registry->register( new Fungies_Blocks_Payment() );
		}
	);
}

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

function fungies_wp_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'Fungies for WooCommerce requires WooCommerce to be installed and active.', 'fungies-for-woocommerce' );
			echo '</p></div>';
		} );
		return;
	}

	Fungies_Loader::instance();
}
add_action( 'plugins_loaded', 'fungies_wp_init' );

register_activation_hook( __FILE__, function () {
	if ( ! wp_next_scheduled( 'fungies_product_sync_cron' ) ) {
		wp_schedule_event( time(), 'hourly', 'fungies_product_sync_cron' );
	}
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'fungies_product_sync_cron' );
} );
