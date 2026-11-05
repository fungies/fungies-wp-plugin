<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Fungies_Loader {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies() {
		$dir = FUNGIES_WP_PLUGIN_DIR . 'includes/';

		require_once $dir . 'class-fungies-api-client.php';
		require_once $dir . 'class-fungies-admin-settings.php';
		require_once $dir . 'class-fungies-workspace-meta.php';
		require_once $dir . 'class-fungies-product-body.php';
		require_once $dir . 'class-fungies-product-push.php';
		require_once $dir . 'class-fungies-product-sync.php';
		require_once $dir . 'class-fungies-coupon-mapper.php';
		require_once $dir . 'class-fungies-coupon-sync.php';
		require_once $dir . 'class-fungies-checkout-plan.php';
		require_once $dir . 'class-fungies-checkout-url-builder.php';
		require_once $dir . 'class-fungies-return-resolver.php';
		require_once $dir . 'class-fungies-payment-gateway.php';
		require_once $dir . 'class-fungies-checkout.php';
		require_once $dir . 'class-fungies-webhook-handler.php';
		require_once $dir . 'class-fungies-order-sync.php';
		require_once $dir . 'class-fungies-order-metabox.php';
		require_once $dir . 'class-fungies-product-metabox.php';
		require_once $dir . 'class-fungies-dashboard-widget.php';
		require_once $dir . 'class-fungies-currency.php';
		require_once $dir . 'class-fungies-blocks-currency.php';
	}

	private function init_hooks() {
		Fungies_Admin_Settings::init();
		Fungies_Product_Sync::init();
		Fungies_Coupon_Sync::init();
		Fungies_Checkout::init();
		Fungies_Webhook_Handler::init();

		Fungies_Order_Metabox::init();
		Fungies_Product_Metabox::init();
		Fungies_Dashboard_Widget::init();
		Fungies_Currency::init();
		Fungies_Blocks_Currency::init();

		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
	}

	public function register_gateway( $gateways ) {
		$gateways[] = 'Fungies_Payment_Gateway';
		return $gateways;
	}
}
