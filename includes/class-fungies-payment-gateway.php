<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Fungies_Payment_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'fungies';
		$this->icon               = FUNGIES_WP_PLUGIN_URL . 'assets/img/fungies-icon.png';
		$this->has_fields         = false;
		$this->method_title       = __( 'Fungies Checkout', 'fungies-for-woocommerce' );
		$this->method_description = __( 'Accept payments via Fungies — the merchant of record handles payments, taxes, and compliance.', 'fungies-for-woocommerce' );

		$this->supports = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'fungies-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Fungies Checkout', 'fungies-for-woocommerce' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'fungies-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Title shown to customers during checkout.', 'fungies-for-woocommerce' ),
				'default'     => __( 'Fungies Checkout', 'fungies-for-woocommerce' ),
			),
			'description' => array(
				'title'       => __( 'Description', 'fungies-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Description shown to customers during checkout.', 'fungies-for-woocommerce' ),
				'default'     => __( 'Pay securely via Fungies. All major payment methods accepted.', 'fungies-for-woocommerce' ),
			),
		);
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Built first so a refused cart leaves no pending order or session pointer behind.
		$redirect_url = Fungies_Checkout_URL_Builder::build( $order );
		if ( is_wp_error( $redirect_url ) ) {
			wc_add_notice( $redirect_url->get_error_message(), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_status( 'pending', __( 'Awaiting Fungies payment.', 'fungies-for-woocommerce' ) );

		// Remember the WC order id in the WC session so we can recover it on
		// return even if the Fungies webhook hasn't landed yet (production
		// webhooks fire fast enough to race the customer's redirect).
		Fungies_Return_Resolver::remember( $order_id );

		wc_get_logger()->info(
			sprintf( '[Gateway] Redirecting order #%d to Fungies hosted checkout: %s', $order_id, $redirect_url ),
			array( 'source' => 'fungies' )
		);

		return array(
			'result'   => 'success',
			'redirect' => $redirect_url,
		);
	}
}
