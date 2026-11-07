<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Fungies_Checkout_URL_Builder {

	/**
	 * @return string|WP_Error The hosted checkout URL, or a WP_Error when the cart has
	 *                         quantities a Fungies checkout cannot charge.
	 */
	public static function build( $order ) {
		$resolved = self::resolve( $order );
		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}
		$checkout_url = $resolved['url'];
		if ( ! $checkout_url ) {
			return $order->get_checkout_order_received_url();
		}
		// Fungies prefill parameter names per docs.fungies.io/developers/
		// checkout-elements/billing-data: `fngs-customer-email` (NOT
		// `fngs-user-email`). `fngs-user-email` is the *outbound* system
		// param Fungies appends to the Instant Redirect URL after purchase
		// — not the inbound prefill param. Sending the wrong key meant the
		// customer's email field was empty on the Fungies hosted checkout
		// page even though we knew the email from the WC order.
		$args = array(
			'fngs-customer-email'   => $order->get_billing_email(),
			'fngs-customer-country' => $order->get_billing_country() ? $order->get_billing_country() : '',
		);
		if ( $resolved['quantity'] > 1 ) {
			$args['fngs-quantity'] = $resolved['quantity'];
		}
		$discount_code = self::resolve_discount_code( $order );
		if ( $discount_code ) {
			$args['fngs-discount-code'] = $discount_code;
		}
		return add_query_arg( $args, $checkout_url );
	}

	private static function resolve_discount_code( $order ) {
		$codes = method_exists( $order, 'get_coupon_codes' ) ? (array) $order->get_coupon_codes() : array();
		if ( empty( $codes ) ) return '';
		$first = (string) reset( $codes );
		if ( '' === $first ) return '';
		self::log( sprintf( 'Order #%d applying discount code "%s" to Fungies checkout URL.', $order->get_id(), $first ) );
		return $first;
	}

	private static function resolve( $order ) {
		$plan = Fungies_Checkout_Plan::from_lines( self::collect_offer_lines( $order ) );

		if ( isset( $plan['error'] ) ) {
			self::log( sprintf( 'Order #%d refused: Fungies checkout needs one quantity for every offer, got %s.', $order->get_id(), wp_json_encode( $plan['quantities'] ) ), 'warning' );
			return new WP_Error(
				'fungies_mixed_quantities',
				__( 'Fungies Checkout charges the same quantity for every product in one order. Please use the same quantity for each product, or buy them in separate orders.', 'fungies-for-woocommerce' )
			);
		}

		$offer_ids = $plan['offers'];
		$quantity  = $plan['quantity'];

		if ( empty( $offer_ids ) ) {
			$stored = self::find_stored_url( $order );
			if ( $stored ) return array( 'url' => $stored, 'quantity' => 1 );
			self::log( sprintf( 'Order #%d has no items mapped to Fungies offers.', $order->get_id() ), 'warning' );
			return array( 'url' => '', 'quantity' => 1 );
		}

		if ( count( $offer_ids ) === 1 ) {
			$store = self::store_url();
			return array( 'url' => $store ? $store . '/checkout/' . $offer_ids[0] : '', 'quantity' => $quantity );
		}

		return array( 'url' => self::build_element_url( $order, $offer_ids ), 'quantity' => $quantity );
	}

	private static function collect_offer_lines( $order ) {
		$lines = array();
		foreach ( $order->get_items() as $item ) {
			$pid      = $item->get_product_id();
			$qty      = max( 1, (int) $item->get_quantity() );
			$offer_id = get_post_meta( $pid, '_fungies_offer_id', true );
			if ( ! $offer_id ) {
				$offer_id = Fungies_Workspace_Meta::get_offer_id( $pid );
			}
			if ( ! $offer_id ) {
				self::log( sprintf( 'Order #%d item "%s" (product %d) has no Fungies offer ID — skipping.', $order->get_id(), $item->get_name(), $pid ), 'warning' );
				continue;
			}
			$lines[] = array( $offer_id, $qty );
		}
		return $lines;
	}

	private static function find_stored_url( $order ) {
		foreach ( $order->get_items() as $item ) {
			$url = get_post_meta( $item->get_product_id(), '_fungies_checkout_url', true );
			if ( $url ) return $url;
		}
		return '';
	}

	private static function build_element_url( $order, $offer_ids ) {
		$store = self::store_url();
		if ( ! $store ) return '';

		$client   = new Fungies_API_Client();
		$response = $client->create_checkout_element( array(
			'name'      => sprintf( 'WC Order #%d', $order->get_id() ),
			'offersIds' => array_values( $offer_ids ),
		) );

		if ( is_wp_error( $response ) ) {
			self::log( sprintf( 'Failed to create Fungies checkout element for order #%d: %s', $order->get_id(), $response->get_error_message() ), 'error' );
			return '';
		}

		$id = $response['data']['checkoutElement']['id'] ?? '';
		if ( ! $id ) {
			self::log( sprintf( 'Fungies checkout element response missing id for order #%d. Response: %s', $order->get_id(), wp_json_encode( $response ) ), 'error' );
			return '';
		}

		$order->update_meta_data( '_fungies_checkout_element_id', $id );
		$order->save();
		self::log( sprintf( 'Created Fungies checkout element %s for order #%d with %d offer(s).', $id, $order->get_id(), count( $offer_ids ) ) );

		return $store . '/checkout-element/' . $id;
	}

	private static function store_url() {
		$raw = Fungies_Admin_Settings::get_option( 'store_url', '' );
		if ( empty( $raw ) ) {
			self::log( 'Fungies Store URL not configured. Go to WooCommerce → Settings → Fungies to set it.', 'error' );
			return '';
		}
		// Defense in depth: even though the admin field uses `type: url` (which
		// runs `esc_url_raw` on save), values can also arrive via DB imports,
		// `wp option update`, or multisite duplication, so re-validate at read
		// time before this string is used to build a customer redirect target.
		$url = esc_url_raw( trim( (string) $raw ), array( 'http', 'https' ) );
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			self::log( sprintf( 'Fungies Store URL is invalid (rejected): "%s". Update it in WooCommerce → Settings → Fungies.', $raw ), 'error' );
			return '';
		}
		return untrailingslashit( $url );
	}

	private static function log( $msg, $level = 'info' ) {
		wc_get_logger()->log( $level, '[Gateway] ' . $msg, array( 'source' => 'fungies' ) );
	}
}
