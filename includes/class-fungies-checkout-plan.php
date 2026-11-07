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
	 * The part of a WooCommerce order a Fungies checkout can charge: items minus discounts at their
	 * shelf price, with no shipping, fees or WooCommerce's own tax (added after the hand-over).
	 */
	public static function items_total( $subtotal, $discount ) {
		return max( 0.0, (float) $subtotal - (float) $discount );
	}

	/**
	 * What the buyer was charged, in minor units: `data.order.value` is net of tax, so the tax
	 * (`data.order.tax`, same scale) is added back. A missing tax counts as zero.
	 *
	 * @return float|null Null when the value is missing.
	 */
	public static function charged_minor( $value, $tax ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		return (float) $value + ( is_numeric( $tax ) ? (float) $tax : 0.0 );
	}

	/**
	 * A refund without an amount refunds the whole charge, but never more than the order still
	 * holds: on a tax-exclusive offer the WooCommerce order shows the items without the tax.
	 */
	public static function full_refund_amount( $charged, $remaining ) {
		return max( 0.0, min( (float) $charged, (float) $remaining ) );
	}

	/**
	 * Compares what Fungies charged with the items after discounts. The charge is the value plus
	 * the tax, which matches the items whether the offer's price includes tax or not.
	 *
	 * @param float       $order_total      Items after discounts, in major units.
	 * @param mixed       $value            `data.order.value`, minor units, net of tax.
	 * @param mixed       $decimals         `data.order.currencyDecimals`; 2 when absent.
	 * @param string|null $paid_currency    `data.order.currency`.
	 * @param string[]    $order_currencies Currencies the order total may be stated in.
	 * @param mixed       $tax              `data.order.tax`, minor units.
	 * @return array array( 'ok' => bool, 'reason' => string, 'paid' => float|null ).
	 */
	public static function payment_verdict( $order_total, $value, $decimals, $paid_currency, array $order_currencies, $tax ) {
		$charged = self::charged_minor( $value, $tax );
		if ( null === $charged ) {
			return array( 'ok' => false, 'reason' => 'missing_amount', 'paid' => null );
		}

		$decimals = is_numeric( $decimals ) ? max( 0, (int) $decimals ) : 2;
		$unit     = pow( 10, $decimals );
		$paid     = $charged / $unit;

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
