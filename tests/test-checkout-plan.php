<?php
// Run: php tests/test-checkout-plan.php  (plain PHP 7.4+, no WordPress needed)
define( 'ABSPATH', __DIR__ . '/' );
require __DIR__ . '/../includes/class-fungies-checkout-plan.php';

$failures = 0;
function check( $name, $expected, $actual ) {
	global $failures;
	if ( $expected === $actual ) {
		echo "ok   {$name}\n";
		return;
	}
	$failures++;
	echo "FAIL {$name}\n  expected " . var_export( $expected, true ) . "\n  actual   " . var_export( $actual, true ) . "\n";
}

$a = 'aaaaaaaa-0000-0000-0000-000000000001';
$b = 'bbbbbbbb-0000-0000-0000-000000000002';

check( 'one line of 3 sends the offer once at quantity 3',
	array( 'offers' => array( $a ), 'quantity' => 3 ),
	Fungies_Checkout_Plan::from_lines( array( array( $a, 3 ) ) ) );
check( 'two lines of 2 send each offer once at quantity 2',
	array( 'offers' => array( $a, $b ), 'quantity' => 2 ),
	Fungies_Checkout_Plan::from_lines( array( array( $a, 2 ), array( $b, 2 ) ) ) );
check( 'quantity 1 lines stay quantity 1',
	array( 'offers' => array( $a, $b ), 'quantity' => 1 ),
	Fungies_Checkout_Plan::from_lines( array( array( $a, 1 ), array( $b, 1 ) ) ) );
check( 'two lines of one offer (variations) add up',
	array( 'offers' => array( $a ), 'quantity' => 2 ),
	Fungies_Checkout_Plan::from_lines( array( array( $a, 1 ), array( strtoupper( $a ), 1 ) ) ) );
check( 'mixed quantities are refused, never guessed',
	array( 'error' => 'mixed_quantities', 'quantities' => array( $a => 2, $b => 1 ) ),
	Fungies_Checkout_Plan::from_lines( array( array( $a, 2 ), array( $b, 1 ) ) ) );
check( 'no lines', array( 'offers' => array(), 'quantity' => 0 ), Fungies_Checkout_Plan::from_lines( array() ) );

$ok = function ( $v ) { return $v['ok'] ? 'ok' : $v['reason']; };
check( 'paid in full', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'paid more', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 726, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'paid for 1 of 3 is a shortfall', 'shortfall', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 200, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'one cent short is a shortfall', 'shortfall', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 599, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'float total rounding is not a shortfall', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 0.1 + 0.2, 30, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'zero-decimal currency', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 1500, 1500, 0, 'JPY', array( 'JPY' ), 0 ) ) );
check( 'missing amount is held', 'missing_amount', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, null, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'other currency is held', 'currency_mismatch', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, 2, 'PLN', array( 'USD', '' ), 0 ) ) );
check( 'product currency counts as the order currency', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, 2, 'eur', array( 'USD', 'EUR' ), 0 ) ) );
check( 'absent decimals default to 2', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, null, null, array( 'USD' ), 0 ) ) );

// A 6.00 cart with 5.00 shipping and 1.20 tax added by WooCommerce: Fungies is paid 6.00.
check( 'shipping and tax on the Woo side do not hold a paid order', 'ok',
	$ok( Fungies_Checkout_Plan::payment_verdict( Fungies_Checkout_Plan::items_total( 6.00, 0 ), 600, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'the whole Woo total (12.20) would have held it', 'shortfall',
	$ok( Fungies_Checkout_Plan::payment_verdict( 12.20, 600, 2, 'USD', array( 'USD' ), 0 ) ) );
check( 'a discount lowers the comparable total', 4.5, Fungies_Checkout_Plan::items_total( 6.00, 1.50 ) );
check( 'a short payment is still held against items', 'shortfall',
	$ok( Fungies_Checkout_Plan::payment_verdict( Fungies_Checkout_Plan::items_total( 6.00, 0 ), 200, 2, 'USD', array( 'USD' ), 0 ) ) );

// The value is net of tax; the charge is value + tax. Offer 100.00 net / 123.00 gross at 23%, 10% off.
$items_incl = Fungies_Checkout_Plan::items_total( 123.00, 12.30 );
$items_excl = Fungies_Checkout_Plan::items_total( 100.00, 10.00 );
check( 'EUR tax-inclusive offer paid in full is not held', 'ok',
	$ok( Fungies_Checkout_Plan::payment_verdict( $items_incl, 9000, 2, 'EUR', array( 'EUR' ), 2070 ) ) );
check( 'EUR tax-exclusive offer paid in full is not held', 'ok',
	$ok( Fungies_Checkout_Plan::payment_verdict( $items_excl, 9000, 2, 'EUR', array( 'EUR' ), 2070 ) ) );
check( 'EUR tax-inclusive offer paid for half is held', 'shortfall',
	$ok( Fungies_Checkout_Plan::payment_verdict( $items_incl, 4500, 2, 'EUR', array( 'EUR' ), 1035 ) ) );
check( 'EUR tax-inclusive offer one cent short is held', 'shortfall',
	$ok( Fungies_Checkout_Plan::payment_verdict( $items_incl, 9000, 2, 'EUR', array( 'EUR' ), 2069 ) ) );
check( 'EUR tax-exclusive offer paid for half is held', 'shortfall',
	$ok( Fungies_Checkout_Plan::payment_verdict( $items_excl, 3700, 2, 'EUR', array( 'EUR' ), 851 ) ) );
// JPY 1100 with 10% tax: value 1000, tax 100, no decimals.
check( 'JPY tax-inclusive offer paid in full is not held', 'ok',
	$ok( Fungies_Checkout_Plan::payment_verdict( 1100, 1000, 0, 'JPY', array( 'JPY' ), 100 ) ) );
check( 'JPY tax-exclusive offer paid in full is not held', 'ok',
	$ok( Fungies_Checkout_Plan::payment_verdict( 1000, 1000, 0, 'JPY', array( 'JPY' ), 100 ) ) );
check( 'JPY tax-inclusive offer one yen short is held', 'shortfall',
	$ok( Fungies_Checkout_Plan::payment_verdict( 1100, 999, 0, 'JPY', array( 'JPY' ), 100 ) ) );
check( 'a missing tax counts as zero', 'shortfall',
	$ok( Fungies_Checkout_Plan::payment_verdict( $items_incl, 9000, 2, 'EUR', array( 'EUR' ), null ) ) );
// Open: on a tax-exclusive offer a charge short by less than the tax still passes.
check( 'known gap: exclusive offer short by less than the tax passes', 'ok',
	$ok( Fungies_Checkout_Plan::payment_verdict( $items_excl, 8000, 2, 'EUR', array( 'EUR' ), 1840 ) ) );

check( 'the charge adds the tax to the value', 11070.0, Fungies_Checkout_Plan::charged_minor( 9000, 2070 ) );
check( 'the charge without a tax is the value', 9000.0, Fungies_Checkout_Plan::charged_minor( '9000', null ) );
check( 'no value, no charge', null, Fungies_Checkout_Plan::charged_minor( null, 2070 ) );
check( 'a full refund of a tax-inclusive order refunds the charge', 110.7,
	Fungies_Checkout_Plan::full_refund_amount( 110.70, 110.70 ) );
check( 'a full refund never exceeds what the order holds', 90.0,
	Fungies_Checkout_Plan::full_refund_amount( 110.70, 90.00 ) );
check( 'a full refund of a fully refunded order is zero', 0.0,
	Fungies_Checkout_Plan::full_refund_amount( 110.70, 0 ) );

echo $failures ? "{$failures} failed\n" : "all passed\n";
exit( $failures ? 1 : 0 );
