<?php
/**
 * Runtime check of wm-salon-sovereign-hub inside a real WordPress.
 *
 * Calls both REST routes through rest_do_request() (so core's argument validation runs), resolves the WP-CLI
 * subcommand names, renders both shortcodes and runs the hub adapter. The plugin stores nothing; the check reads the
 * plugin's options and transients before and after and exits 1 if anything changed. No key or salt is read.
 *
 *   wp eval-file wp-content/plugins/wm-salon-sovereign-hub/tests/wp-runtime-salon.php --user=1
 *   ssh <box> 'wp --path=<www> eval-file - --user=1' < tests/wp-runtime-salon.php
 *
 * `wp eval-file` runs the file inside a method: state lives in $GLOBALS.
 *
 * @package WM_Salon_Sovereign_Hub
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WM_Salon_Stuhlmiete_Ledger' ) || ! defined( 'WM_Salon_Stuhlmiete_Ledger::MAX_AMOUNT' ) ) {
	WP_CLI::error( 'wm-salon-sovereign-hub with the cent arithmetic (MAX_AMOUNT) is not loaded.' );
}

global $wpdb;
$GLOBALS['sart']       = [ 'pass' => 0, 'fail' => 0 ];
$GLOBALS['sart_check'] = static function ( bool $ok, string $label ): void {
	$GLOBALS['sart'][ $ok ? 'pass' : 'fail' ]++;
	echo ( $ok ? '  [PASS] ' : '  [FAIL] ' ) . $label . "\n";
};
$GLOBALS['sart_state'] = static function (): array {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return $wpdb->get_col( $wpdb->prepare( 'SELECT option_name FROM %i WHERE option_name LIKE %s ORDER BY option_name', $wpdb->options, '%' . $wpdb->esc_like( 'wm_salon' ) . '%' ) );
};
$GLOBALS['sart_before'] = $GLOBALS['sart_state']();

register_shutdown_function(
	static function (): void {
		$same = $GLOBALS['sart_state']() === $GLOBALS['sart_before'];
		echo "\n  STORAGE CHECK: " . ( $same ? 'no wm_salon option or transient written' : 'DIFFERENT' ) . "\n";
		echo "  RUNTIME RESULTS: {$GLOBALS['sart']['pass']} PASSED | {$GLOBALS['sart']['fail']} FAILED\n";
		exit( $same && 0 === $GLOBALS['sart']['fail'] ? 0 : 1 );
	}
);

$check = $GLOBALS['sart_check'];
$call  = static function ( string $route, array $params ): WP_REST_Response {
	$request = new WP_REST_Request( 'POST', $route );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_body( (string) wp_json_encode( $params ) );
	return rest_ensure_response( rest_do_request( $request ) );
};

echo "\n=== WM SALON SOVEREIGN HUB · RUNTIME ===\n";

// --- REST ------------------------------------------------------------------------------------------------------------
$statuses = [
	$call( '/wm-salon/v1/stuhlmiete', [] )->get_status(),
	$call( '/wm-salon/v1/stuhlmiete', [ 'gross_revenue' => 1190, 'rent_rate' => 30 ] )->get_status(),
	$call( '/wm-salon/v1/stuhlmiete', [ 'gross_revenue' => 'abc' ] )->get_status(),
	$call( '/wm-salon/v1/stuhlmiete', [ 'gross_revenue' => 100, 'vat_rate' => -100 ] )->get_status(),
	$call( '/wm-salon/v1/inci-check', [ 'inci' => ',' ] )->get_status(),
	$call( '/wm-salon/v1/inci-check', [ 'inci' => [ 'Aqua' ] ] )->get_status(),
];
$check( [ 400, 400, 400, 400, 400, 400 ] === $statuses, 'empty body, a share of 30, text, VAT -100, an empty and an array ingredient list -> 400 (was: 200 with zeros, a 100 % share or a clean verdict): ' . implode( ',', $statuses ) );

$example = $call( '/wm-salon/v1/stuhlmiete', [ 'gross_revenue' => 1190, 'rent_rate' => 0.30 ] );
$tiny    = $call( '/wm-salon/v1/stuhlmiete', [ 'gross_revenue' => 0.75, 'rent_rate' => 0.30 ] );
$short   = $call( '/wm-salon/v1/stuhlmiete', [ 'gross_revenue' => 100, 'rent_rate' => 0.30, 'fixed_rent' => 130 ] );
$check(
	200 === $example->get_status() && 357.0 === (float) $example->get_data()['data']['salon_share_gross'] && 700.0 === (float) $example->get_data()['data']['stylist_payout_net']
	&& 0.23 === (float) $tiny->get_data()['data']['salon_share_gross'] && 60.0 === (float) $short->get_data()['data']['shortfall_gross'],
	'split: 1 190 € gives 357 / 700; 30 % of 0.75 € is 0.23 € on PHP ' . PHP_VERSION . '; a fixed rent above the turnover shows a 60 € shortfall'
);

$lines = $call( '/wm-salon/v1/inci-check', [ 'inci' => "Aqua\nSodium Laureth Sulfate\nLimonene\nLinalool" ] );
$hexa  = $call( '/wm-salon/v1/inci-check', [ 'inci' => 'Aqua, 1,2-Hexanediol, Glycerin' ] );
$check(
	200 === $lines->get_status() && 4 === $lines->get_data()['data']['total_ingredients'] && [ 'Limonene', 'Linalool' ] === $lines->get_data()['data']['allergens_found'] && 3 === $hexa->get_data()['data']['total_ingredients'],
	'INCI: one ingredient per line counts 4 with Limonene and Linalool; "1,2-Hexanediol" stays one ingredient'
);

// --- WP-CLI ----------------------------------------------------------------------------------------------------------
$runner   = WP_CLI::get_runner();
$resolved = [];
foreach ( [ 'analyze-inci', 'analyze_inci', 'calculate-rent', 'calculate_rent' ] as $sub ) {
	$resolved[ $sub ] = method_exists( $runner, 'find_command_to_run' ) && is_array( $runner->find_command_to_run( [ 'salon', $sub ] ) );
}
$check( [ 'analyze-inci' => true, 'analyze_inci' => true, 'calculate-rent' => true, 'calculate_rent' => true ] === $resolved, 'wp salon analyze-inci and calculate-rent are registered, the underscore names as aliases (was: not registered)' );

// --- Shortcodes, text domain, adapter -------------------------------------------------------------------------------
$GLOBALS['sart_warnings'] = [];
set_error_handler(
	static function ( $no, $msg, $file, $line ) {
		$GLOBALS['sart_warnings'][] = $msg . ' @ ' . basename( (string) $file ) . ':' . $line;
		return true;
	}
);
$typed = [];
foreach ( [ 'render_stuhlmiete_calculator', 'render_inci_checker' ] as $method ) {
	try {
		$typed[] = strlen( WM_Salon_Shortcodes::$method( '' ) ) > 0;
	} catch ( \Throwable $t ) {
		$typed[] = get_class( $t );
	}
}
$_POST = [ 'wm_inci_submit' => '1', 'wm_inci_text' => wp_slash( 'Parfum "Limonene", Aqua' ), 'wm_salon_calc_submit' => '1', 'wm_salon_gross' => '777', 'wm_salon_rate' => '25', 'wm_salon_fixed' => '0' ];
$html  = do_shortcode( '[wm_salon_stuhlmiete][wm_salon_inci_checker]' );
$_POST = [];
restore_error_handler();
$check( [ true, true ] === $typed && [] === $GLOBALS['sart_warnings'], "both shortcodes render with '' as attributes, as WordPress before 6.5 passes them, without notices (was: TypeError)" . ( $GLOBALS['sart_warnings'] ? ': ' . $GLOBALS['sart_warnings'][0] : '' ) );
$check( ! str_contains( $html, 'style=' ) && wp_style_is( WM_Salon_Shortcodes::STYLE_HANDLE, 'enqueued' ) && ! str_contains( $html, '\\' ) && str_contains( $html, 'value="777"' ), 'markup without inline styles, stylesheet enqueued, no backslash from slashed input, submitted values kept (was: 20 style attributes, backslashes, defaults)' );

$locale = determine_locale();
if ( file_exists( WM_SALON_HUB_PATH . 'languages/wm-salon-sovereign-hub-' . $locale . '.mo' ) ) {
	$check( is_textdomain_loaded( 'wm-salon-sovereign-hub' ), "the plugin's catalog for {$locale} is loaded once its strings render (was: no gettext call)" );
} else {
	echo "  [INFO] no catalog for {$locale}: text domain not checked\n";
}

if ( class_exists( 'WM_Salon_Spoke_Adapter' ) ) {
	$adapter = new WM_Salon_Spoke_Adapter();
	$tele    = $adapter->get_telemetry_metrics();
	$check( 'HEALTHY' === $adapter->get_health_status() && 2 === $tele['shortcodes_registered'] && 'PASS' === $tele['self_test'] && ! isset( $tele['status'] ), 'hub adapter: HEALTHY from the self test, two registered shortcodes, no literal ACTIVE' );
} else {
	echo "  [INFO] hub not active: adapter not checked\n";
}
