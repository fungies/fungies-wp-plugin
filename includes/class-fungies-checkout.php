<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Fungies_Checkout {

	public static function init() {
		add_action( 'woocommerce_thankyou_fungies', array( __CLASS__, 'handle_thankyou' ), 10, 1 );
		add_action( 'woocommerce_api_fungies_return', array( __CLASS__, 'handle_return' ) );
	}

	public static function handle_return() {
		// Read-only post-payment redirect handler. The customer is bounced back
		// from Fungies hosted checkout with `fngs-order-id` + `fngs-user-email`.
		// Authoritative payment state lives in the parallel webhook, which is
		// itself HMAC-SHA256-verified — this handler only resolves the WC order
		// and redirects, performs no state mutation worth a nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$fungies_order_id = isset( $_GET['fngs-order-id'] ) ? sanitize_text_field( wp_unslash( $_GET['fngs-order-id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$fungies_email    = isset( $_GET['fngs-user-email'] ) ? sanitize_email( wp_unslash( $_GET['fngs-user-email'] ) ) : '';

		wc_get_logger()->info(
			'[Return] Customer returned from Fungies. fngs-order-id=' . $fungies_order_id,
			array( 'source' => 'fungies' )
		);

		$wc_order = Fungies_Return_Resolver::resolve( $fungies_order_id, $fungies_email );

		if ( $wc_order && $fungies_order_id ) {
			self::link_fungies_order_meta( $wc_order, $fungies_order_id );
		}

		Fungies_Return_Resolver::clear();

		if ( $wc_order ) {
			wp_safe_redirect( $wc_order->get_checkout_order_received_url() );
			exit;
		}

		wc_get_logger()->warning(
			'[Return] Could not resolve any WC order for return. Falling back to checkout URL.',
			array( 'source' => 'fungies' )
		);
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	private static function link_fungies_order_meta( $wc_order, $fungies_order_id ) {
		if ( $wc_order->get_meta( '_fungies_order_id' ) ) return;
		$wc_order->update_meta_data( '_fungies_order_id', $fungies_order_id );
		$wc_order->save();
		wc_get_logger()->info(
			sprintf( '[Return] Linked Fungies order %s -> WC order #%d', $fungies_order_id, $wc_order->get_id() ),
			array( 'source' => 'fungies' )
		);
	}

	public static function handle_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$status = $order->get_status();

		if ( in_array( $status, array( 'completed', 'processing' ), true ) ) {
			echo '<p class="woocommerce-info">';
			esc_html_e( 'Your Fungies payment was successful! Thank you for your purchase.', 'fungies-for-woocommerce' );
			echo '</p>';
			return;
		}

		if ( 'pending' === $status ) {
			echo '<p class="woocommerce-info">';
			esc_html_e( 'Your payment is being processed by Fungies. You will receive a confirmation email shortly.', 'fungies-for-woocommerce' );
			echo '</p>';
			return;
		}

		if ( 'on-hold' === $status ) {
			echo '<p class="woocommerce-info">';
			esc_html_e( 'Your order is on hold while the store reviews your Fungies payment. You will hear from the store shortly.', 'fungies-for-woocommerce' );
			echo '</p>';
			return;
		}

		if ( 'failed' === $status ) {
			echo '<p class="woocommerce-error">';
			esc_html_e( 'Your Fungies payment could not be completed. Please try again or contact support.', 'fungies-for-woocommerce' );
			echo '</p>';
		}
	}

	public static function get_return_url() {
		return add_query_arg( 'wc-api', 'fungies_return', home_url( '/' ) );
	}

}
