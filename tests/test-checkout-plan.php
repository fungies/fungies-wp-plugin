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
check( 'paid in full', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, 2, 'USD', array( 'USD' ) ) ) );
check( 'paid more (tax on top)', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 726, 2, 'USD', array( 'USD' ) ) ) );
check( 'paid for 1 of 3 is a shortfall', 'shortfall', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 200, 2, 'USD', array( 'USD' ) ) ) );
check( 'one cent short is a shortfall', 'shortfall', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 599, 2, 'USD', array( 'USD' ) ) ) );
check( 'float total rounding is not a shortfall', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 0.1 + 0.2, 30, 2, 'USD', array( 'USD' ) ) ) );
check( 'zero-decimal currency', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 1500, 1500, 0, 'JPY', array( 'JPY' ) ) ) );
check( 'missing amount is held', 'missing_amount', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, null, 2, 'USD', array( 'USD' ) ) ) );
check( 'other currency is held', 'currency_mismatch', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, 2, 'PLN', array( 'USD', '' ) ) ) );
check( 'product currency counts as the order currency', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, 2, 'eur', array( 'USD', 'EUR' ) ) ) );
check( 'absent decimals default to 2', 'ok', $ok( Fungies_Checkout_Plan::payment_verdict( 6.00, 600, null, null, array( 'USD' ) ) ) );

echo $failures ? "{$failures} failed\n" : "all passed\n";
exit( $failures ? 1 : 0 );
