<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Pure rules for what a Fungies checkout can charge. Kept free of WordPress calls so the
 * tests in tests/ run on plain PHP.
 */
class Fungies_Checkout_Plan {

	/**
	 * A Fungies checkout holds each offer once and one `fngs-quantity` for all of them, so a
	 * cart is expressible only when every offer ends up with the same quantity.
	 *
	 * @param array $lines List of array( offer_id, quantity ), one per cart line.
	 * @return array array( 'offers' => string[], 'quantity' => int ) when expressible, otherwise
	 *               array( 'error' => 'mixed_quantities', 'quantities' => array( offer_id => int ) ).
	 */
	public static function from_lines( array $lines ) {
		$offers     = array();
		$quantities = array();
		foreach ( $lines as $line ) {
			$offer_id = (string) $line[0];
			$key      = strtolower( $offer_id );
			if ( ! isset( $offers[ $key ] ) ) {
				$offers[ $key ]     = $offer_id;
				$quantities[ $key ] = 0;
			}
			$quantities[ $key ] += max( 1, (int) $line[1] );
		}

		if ( empty( $offers ) ) {
			return array( 'offers' => array(), 'quantity' => 0 );
		}

		$distinct = array_values( array_unique( $quantities ) );
		if ( count( $distinct ) > 1 ) {
			return array(
				'error'      => 'mixed_quantities',
				'quantities' => array_combine( array_values( $offers ), array_values( $quantities ) ),
			);
		}

		return array(
			'offers'   => array_values( $offers ),
			'quantity' => (int) $distinct[0],
		);
	}

	/**
	 * Compares what Fungies reports as paid with the WooCommerce order total.
	 *
	 * @param float       $order_total      WooCommerce order total in major units.
	 * @param mixed       $value            Paid amount in minor units (`data.order.value`).
	 * @param mixed       $decimals         `data.order.currencyDecimals`; 2 when absent.
	 * @param string|null $paid_currency    `data.order.currency`.
	 * @param string[]    $order_currencies Currencies the order total may be stated in.
	 * @return array array( 'ok' => bool, 'reason' => string, 'paid' => float|null ).
	 */
	public static function payment_verdict( $order_total, $value, $decimals, $paid_currency, array $order_currencies ) {
		if ( ! is_numeric( $value ) ) {
			return array( 'ok' => false, 'reason' => 'missing_amount', 'paid' => null );
		}

		$decimals = is_numeric( $decimals ) ? max( 0, (int) $decimals ) : 2;
		$unit     = pow( 10, $decimals );
		$paid     = (float) $value / $unit;

		$accepted = array_filter( array_map( 'strtoupper', array_map( 'strval', $order_currencies ) ) );
		if ( $paid_currency && ! empty( $accepted ) && ! in_array( strtoupper( (string) $paid_currency ), $accepted, true ) ) {
			return array( 'ok' => false, 'reason' => 'currency_mismatch', 'paid' => $paid );
		}

		if ( $paid + 0.5 / $unit < (float) $order_total ) {
			return array( 'ok' => false, 'reason' => 'shortfall', 'paid' => $paid );
		}

		return array( 'ok' => true, 'reason' => '', 'paid' => $paid );
	}
}
