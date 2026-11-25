<?php
/**
 * Standalone test suite for wm-salon-sovereign-hub.
 *
 * Runs without WordPress: WordPress functions are small fakes that behave like core where it matters here
 * (sanitize_text_field() collapses line breaks, $_POST values arrive slashed in the tests that need it), the main plugin
 * file, the REST controller and the WP-CLI class are loaded, and a PHP warning or notice fails the case that raised it.
 *
 *   php tests/test-suite.php          exit 0 only when every assertion passes
 *   bash tests/run-mutations.sh       undoes each correction of tests/mutations.php; this suite must fail every time
 *
 * Until 2026-09-15 this file never loaded the REST controller, the WP-CLI class or the main file, asserted the literal
 * HEALTHY and stubbed sanitize_text_field() without collapsing line breaks.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

final class SalonCliError extends RuntimeException {}

// ---------------------------------------------------------------------------------------------------------------------
// WordPress fakes
// ---------------------------------------------------------------------------------------------------------------------

$GLOBALS['salon_actions']         = [];
$GLOBALS['salon_fired_actions']   = [];
$GLOBALS['salon_filters']         = [];
$GLOBALS['salon_shortcodes']      = [];
$GLOBALS['salon_routes']          = [];
$GLOBALS['salon_styles']          = [];
$GLOBALS['salon_textdomains']     = [];
$GLOBALS['salon_options']         = [];
$GLOBALS['salon_transients']      = [];
$GLOBALS['salon_settings']        = [];
$GLOBALS['salon_settings_fields'] = [];
$GLOBALS['salon_block_bindings']  = [];
$GLOBALS['salon_gb_tags']         = [];
$GLOBALS['salon_is_admin']        = false;

function add_action( $tag, $callback, $priority = 10, $args = 1 ) { $GLOBALS['salon_actions'][ $tag ][] = $callback; return true; }
function do_action( $tag, ...$args ) {
	$GLOBALS['salon_fired_actions'][ $tag ][] = $args;
}
function add_filter( $tag, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['salon_filters'][ $tag ][ (int) $priority ][] = [ $callback, (int) $args ];
	return true;
}
function apply_filters( $tag, $value, ...$extra ) {
	if ( empty( $GLOBALS['salon_filters'][ $tag ] ) ) {
		return $value;
	}
	$priorities = $GLOBALS['salon_filters'][ $tag ];
	ksort( $priorities );
	$all = array_merge( [ $value ], $extra );
	foreach ( $priorities as $callbacks ) {
		foreach ( $callbacks as [ $cb, $accepted ] ) {
			$all[0] = $cb( ...array_slice( $all, 0, max( 1, $accepted ) ) );
		}
	}
	return $all[0];
}
function get_option( $name, $default = false ) { return array_key_exists( (string) $name, $GLOBALS['salon_options'] ) ? $GLOBALS['salon_options'][ (string) $name ] : $default; }
function update_option( $name, $value ) { $GLOBALS['salon_options'][ (string) $name ] = $value; return true; }
function delete_option( $name ) { unset( $GLOBALS['salon_options'][ (string) $name ] ); return true; }
function delete_site_option( $name ) { unset( $GLOBALS['salon_options'][ (string) $name ] ); return true; }
function get_transient( $key ) { return $GLOBALS['salon_transients'][ (string) $key ] ?? false; }
function set_transient( $key, $value, $expiration = 0 ) { $GLOBALS['salon_transients'][ (string) $key ] = $value; return true; }
function delete_transient( $key ) { unset( $GLOBALS['salon_transients'][ (string) $key ] ); return true; }
function wp_salt( $scheme = 'auth' ) { return 'wm-salon-unit-test-salt-' . (string) $scheme; }
function current_user_can( $capability ) { return (bool) $GLOBALS['salon_is_admin']; }
function register_setting( $option_group, $option_name, $args = [] ) {
	$GLOBALS['salon_settings'][ (string) $option_name ] = [ 'group' => (string) $option_group, 'args' => (array) $args ];
}
function add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = [] ) {
	$GLOBALS['salon_settings_fields'][ (string) $id ] = [ 'title' => (string) $title, 'callback' => $callback, 'page' => (string) $page ];
}
function register_block_bindings_source( $source_name, array $source_properties ) {
	$GLOBALS['salon_block_bindings'][ (string) $source_name ] = $source_properties;
	return true;
}
function get_block_bindings_source( $source_name ) {
	return $GLOBALS['salon_block_bindings'][ (string) $source_name ] ?? null;
}
class GenerateBlocks_Register_Dynamic_Tag {
	public function __construct( array $args ) {
		$GLOBALS['salon_gb_tags'][ (string) ( $args['tag'] ?? '' ) ] = $args;
	}
}
function add_shortcode( $tag, $callback ) { $GLOBALS['salon_shortcodes'][ $tag ] = $callback; }
function shortcode_exists( $tag ) { return isset( $GLOBALS['salon_shortcodes'][ $tag ] ); }
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['salon_routes'][ '/' . $namespace . $route ] = $args; return true; }
function plugin_dir_path( $file ) { return dirname( (string) $file ) . '/'; }
function plugin_basename( $file ) { return 'wm-salon-sovereign-hub/' . basename( (string) $file ); }
function plugins_url( $path = '', $plugin = '' ) { return 'https://example.test/wp-content/plugins/wm-salon-sovereign-hub/' . ltrim( (string) $path, '/' ); }
function load_plugin_textdomain( $domain, $deprecated = false, $path = false ) { $GLOBALS['salon_textdomains'][] = [ $domain, $path ]; return true; }
function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) { $GLOBALS['salon_styles'][ $handle ] = $src; }
function __( $text, $domain = 'default' ) { return $text; }
function _n( $single, $plural, $number, $domain = 'default' ) { return 1 === (int) $number ? $single : $plural; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = 'default' ) { return esc_html( $text ); }
function esc_attr__( $text, $domain = 'default' ) { return esc_attr( $text ); }
function sanitize_text_field( $str ) {
	if ( is_array( $str ) || is_object( $str ) ) {
		return '';
	}
	return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $str ) ) );
}
function sanitize_textarea_field( $str ) {
	if ( is_array( $str ) || is_object( $str ) ) {
		return '';
	}
	return trim( (string) preg_replace( '/[\t ]+/', ' ', strip_tags( (string) $str ) ) );
}
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value ); }
function wp_slash( $value ) { return is_string( $value ) ? addslashes( $value ) : $value; }
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }

class WP_Error {
	public function __construct( public string $code = '', public string $message = '', public mixed $data = null ) {}
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
class WP_REST_Request {
	public function __construct( private array $params = [] ) {}
	public function get_param( $key ) { return $this->params[ $key ] ?? null; }
}
class WP_REST_Response {
	public function __construct( public mixed $data = null, public int $status = 200 ) {}
	public function get_data() { return $this->data; }
	public function get_status() { return $this->status; }
}
class WP_CLI {
	public static array $out = [];
	public static function line( $m ) { self::$out[] = [ 'line', (string) $m ]; }
	public static function success( $m ) { self::$out[] = [ 'success', (string) $m ]; }
	public static function warning( $m ) { self::$out[] = [ 'warning', (string) $m ]; }
	public static function error( $m ) { throw new SalonCliError( (string) $m ); }
	public static function add_command( ...$args ) {}
}

require_once __DIR__ . '/fixtures/hub-interface-stub.php';
require_once dirname( __DIR__ ) . '/wm-salon-sovereign-hub.php';
require_once dirname( __DIR__ ) . '/includes/class-salon-cli.php';
require_once dirname( __DIR__ ) . '/includes/class-salon-spoke-adapter.php';

// ---------------------------------------------------------------------------------------------------------------------
// Harness
// ---------------------------------------------------------------------------------------------------------------------

$GLOBALS['salon_passed'] = 0;
$GLOBALS['salon_failed'] = 0;

function assert_test( string $name, bool $condition, string $details = '' ): void {
	if ( $condition ) {
		echo "  [PASS] {$name}\n";
		++$GLOBALS['salon_passed'];
	} else {
		echo "  [FAIL] {$name}" . ( '' !== $details ? ': ' . $details : '' ) . "\n";
		++$GLOBALS['salon_failed'];
	}
}

/** One case per closure: an exception, warning or notice is a failed case, not a dead suite. */
function salon_case( string $name, callable $fn ): void {
	set_error_handler(
		static function ( int $no, string $msg, string $file, int $line ): bool {
			throw new ErrorException( $msg, 0, $no, $file, $line );
		}
	);
	try {
		$fn( $name );
	} catch ( \Throwable $t ) {
		assert_test( $name, false, get_class( $t ) . ': ' . $t->getMessage() . ' @ ' . basename( $t->getFile() ) . ':' . $t->getLine() );
	} finally {
		restore_error_handler();
		$_POST                         = [];
		$GLOBALS['salon_transients']   = [];
		$GLOBALS['salon_is_admin']     = false;
		unset(
			$GLOBALS['salon_filters']['wm_salon_stuhlmiete_calculation'],
			$GLOBALS['salon_filters']['wm_salon_inci_database'],
			$GLOBALS['salon_filters']['wm_salon_inci_result'],
			$GLOBALS['salon_filters']['wm_salon_rest_rate_limit'],
			$GLOBALS['salon_filters']['wm_salon_metric_value']
		);
	}
}

function salon_split( float $gross, float $rate = 0.30, float $fixed = 0.0, float $vat = 19.0 ): array {
	return WM_Salon_Stuhlmiete_Ledger::calculate_split( $gross, $rate, $fixed, $vat );
}

function salon_error_code( $result ): string {
	return $result instanceof WP_Error ? $result->get_error_code() . '/' . ( $result->get_error_data()['status'] ?? '' ) : 'ok';
}

function salon_luminance( string $hex ): float {
	$hex = ltrim( trim( $hex ), '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$c = array_map(
		static function ( string $pair ): float {
			$v = hexdec( $pair ) / 255;
			return $v <= 0.03928 ? $v / 12.92 : ( ( $v + 0.055 ) / 1.055 ) ** 2.4;
		},
		str_split( $hex, 2 )
	);
	return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function salon_contrast( string $a, string $b ): float {
	$la = salon_luminance( $a );
	$lb = salon_luminance( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/** Text of a document without quoted passages and history lines, where earlier claims are cited on purpose. */
function salon_doc_text( string $path ): string {
	$text = (string) file_get_contents( $path );
	$text = (string) preg_replace( '/"[^"\n]*"|„[^“\n]*“|“[^”\n]*”|«[^»\n]*»/u', '', $text );
	$keep = [];
	foreach ( preg_split( '/\R/', $text ) as $line ) {
		if ( preg_match( '/\b(Until|Bis zum|Hasta el)\b/u', $line ) ) {
			continue;
		}
		$keep[] = $line;
	}
	return implode( "\n", $keep );
}

$root  = dirname( __DIR__ );
$emoji = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B50}]/u';

echo "==============================================================================\n";
echo "  WM SALON SOVEREIGN HUB — STANDALONE SUITE\n";
echo "==============================================================================\n\n";

// =====================================================================================================================
echo "[GROUP 1] Booth-rent split in whole cents\n";
// =====================================================================================================================

salon_case( '1 190 € gross at 30 % and 19 %: net 1 000, VAT 190, salon 357, stylist 833 / 700, no shortfall', function ( string $name ) {
	$s = salon_split( 1190.00 );
	assert_test( $name, 1190.0 === $s['gross_revenue'] && 1000.0 === $s['net_revenue'] && 190.0 === $s['vat_amount'] && 357.0 === $s['salon_share_gross'] && 833.0 === $s['stylist_payout_gross'] && 700.0 === $s['stylist_payout_net'] && 0.0 === $s['shortfall_gross'], var_export( $s, true ) );
} );

salon_case( 'a VAT rate of -100 or 500 is clamped to 0 % or 100 % instead of dividing by zero', function ( string $name ) {
	assert_test( $name, 100.0 === salon_split( 100.00, 0.30, 0.0, -100.0 )['net_revenue'] && 100.0 === salon_split( 200.00, 0.0, 0.0, 500.0 )['net_revenue'] );
} );

salon_case( '30 % of 0.75 € is 0.23 €, and every amount from 0.01 € to 200 € rounds half up to the cent (was: 0.22 on PHP 8.4)', function ( string $name ) {
	$mismatches = 0;
	for ( $cents = 1; $cents <= 20000; $cents++ ) {
		$expected = intdiv( 2 * $cents * 3000 + 10000, 20000 ) / 100.0;
		if ( salon_split( $cents / 100, 0.30 )['salon_share_gross'] !== $expected ) {
			++$mismatches;
		}
	}
	assert_test( $name, 0.23 === salon_split( 0.75, 0.30 )['salon_share_gross'] && 0 === $mismatches, "{$mismatches} mismatches" );
} );

salon_case( 'amounts with a binary error are taken as their cents: 0.29 stays 0.29, 1.005 becomes 1.01', function ( string $name ) {
	assert_test( $name, 0.29 === salon_split( 0.29, 0.0 )['gross_revenue'] && 1.01 === salon_split( 1.005, 0.0 )['gross_revenue'] );
} );

salon_case( 'a fixed rent above the turnover shows a shortfall instead of disappearing (100 €, 30 %, 130 € fixed: salon 160, stylist 0, shortfall 60)', function ( string $name ) {
	$s = salon_split( 100.00, 0.30, 130.00 );
	assert_test( $name, 160.0 === $s['salon_share_gross'] && 0.0 === $s['stylist_payout_gross'] && 60.0 === $s['shortfall_gross'], var_export( $s, true ) );
} );

salon_case( 'an infinite or NaN value and an amount above the limit throw instead of returning INF or NaN (was: HTTP 500)', function ( string $name ) {
	$thrown = 0;
	foreach ( [ [ INF, 0.3, 0.0, 19.0 ], [ 100.0, NAN, 0.0, 19.0 ], [ 100.0, 0.3, INF, 19.0 ], [ 2.0e9, 0.3, 0.0, 19.0 ], [ 100.0, 0.3, 2.0e9, 19.0 ] ] as $args ) {
		try {
			salon_split( ...$args );
		} catch ( InvalidArgumentException $e ) {
			++$thrown;
		}
	}
	assert_test( $name, 5 === $thrown, "{$thrown} of 5 threw" );
} );

// =====================================================================================================================
echo "\n[GROUP 2] INCI list: splitting, empty lists, allergens\n";
// =====================================================================================================================

salon_case( 'a shampoo list: sulfates, silicones and Limonene found; no SULFATFREI badge', function ( string $name ) {
	$r = WM_Salon_INCI_Analyzer::analyze( 'Aqua, Sodium Laureth Sulfate, Cocamidopropyl Betaine, Dimethicone, Parfum, Limonene' );
	assert_test( $name, true === $r['has_sulfates'] && true === $r['has_silicones'] && [ 'Limonene' ] === $r['allergens_found'] && ! in_array( 'SULFATFREI', $r['badges'], true ) && 6 === $r['total_ingredients'], var_export( $r, true ) );
} );

salon_case( 'a list without listed substances scores 100 with CLEAN_BEAUTY', function ( string $name ) {
	$r = WM_Salon_INCI_Analyzer::analyze( 'Aqua, Aloe Barbadensis Leaf Juice, Glycerin, Argania Spinosa Kernel Oil, Xanthan Gum, Tocopherol' );
	assert_test( $name, 100 === $r['clean_score'] && in_array( 'CLEAN_BEAUTY', $r['badges'], true ) && false === $r['has_parabens'] );
} );

salon_case( 'an empty list, a lone comma or only line breaks get no verdict: score null, findings null, no badge (was: 100 with every badge)', function ( string $name ) {
	$results = array_map( [ 'WM_Salon_INCI_Analyzer', 'analyze' ], [ '', ',', " \n ; \n" ] );
	$ok      = true;
	foreach ( $results as $r ) {
		$ok = $ok && 0 === $r['total_ingredients'] && null === $r['clean_score'] && [] === $r['badges'] && null === $r['has_sulfates'];
	}
	assert_test( $name, $ok, var_export( $results, true ) );
} );

salon_case( 'one ingredient per line counts four ingredients (was: one ingredient)', function ( string $name ) {
	$r = WM_Salon_INCI_Analyzer::analyze( "Aqua\nSodium Laureth Sulfate\r\nLimonene\nLinalool" );
	assert_test( $name, 4 === $r['total_ingredients'] && true === $r['has_sulfates'] && [ 'Limonene', 'Linalool' ] === $r['allergens_found'], var_export( $r, true ) );
} );

salon_case( '"1,2-Hexanediol" is one ingredient and a semicolon separates', function ( string $name ) {
	assert_test( $name, 3 === WM_Salon_INCI_Analyzer::analyze( 'Aqua, 1,2-Hexanediol, Glycerin' )['total_ingredients'] && 2 === WM_Salon_INCI_Analyzer::analyze( 'Aqua; Glycerin' )['total_ingredients'] );
} );

salon_case( 'allergens inside parentheses stay in one ingredient and each allergen counts (5 allergens: score 90, named as in the list)', function ( string $name ) {
	$r = WM_Salon_INCI_Analyzer::analyze( 'Parfum (Limonene, Linalool, Citral, Geraniol, Coumarin), Aqua' );
	assert_test( $name, 2 === $r['total_ingredients'] && [ 'Limonene', 'Linalool', 'Citral', 'Geraniol', 'Coumarin' ] === $r['allergens_found'] && 90 === $r['clean_score'], var_export( $r, true ) );
} );

// =====================================================================================================================
echo "\n[GROUP 3] REST routes\n";
// =====================================================================================================================

salon_case( 'the routes declare their arguments: gross revenue required, share 0-1, VAT 0-100, inci a required string', function ( string $name ) {
	$GLOBALS['salon_routes'] = [];
	WM_Salon_REST_Controller::register_routes();
	$s = $GLOBALS['salon_routes']['/wm-salon/v1/stuhlmiete']['args'] ?? [];
	$i = $GLOBALS['salon_routes']['/wm-salon/v1/inci-check']['args'] ?? [];
	assert_test(
		$name,
		true === ( $s['gross_revenue']['required'] ?? false ) && 'number' === ( $s['gross_revenue']['type'] ?? '' ) && 1.0 === ( $s['rent_rate']['maximum'] ?? 0 ) && 0.30 === ( $s['rent_rate']['default'] ?? 0 )
		&& 100.0 === ( $s['vat_rate']['maximum'] ?? 0 ) && true === ( $i['inci']['required'] ?? false ) && 'string' === ( $i['inci']['type'] ?? '' ),
		var_export( [ $s, $i ], true )
	);
} );

salon_case( 'the split handler answers 400 for an empty body, a share of 30, text, 1e309 and a VAT of -100 (was: 200 with zeros or a 100 % share)', function ( string $name ) {
	$codes = array_map(
		static fn( array $body ) => salon_error_code( WM_Salon_REST_Controller::handle_stuhlmiete( new WP_REST_Request( $body ) ) ),
		[ [], [ 'gross_revenue' => 1190, 'rent_rate' => 30 ], [ 'gross_revenue' => 'abc' ], [ 'gross_revenue' => '1e309' ], [ 'gross_revenue' => 100, 'vat_rate' => -100 ] ]
	);
	assert_test( $name, [ 'rest_missing_callback_param/400', 'rest_invalid_param/400', 'rest_invalid_param/400', 'rest_invalid_param/400', 'rest_invalid_param/400' ] === $codes, implode( ', ', $codes ) );
} );

salon_case( 'valid split requests answer 200 with the figures and the shortfall', function ( string $name ) {
	$a = WM_Salon_REST_Controller::handle_stuhlmiete( new WP_REST_Request( [ 'gross_revenue' => '1190', 'rent_rate' => '0.30' ] ) );
	$b = WM_Salon_REST_Controller::handle_stuhlmiete( new WP_REST_Request( [ 'gross_revenue' => 100, 'fixed_rent' => 130 ] ) );
	assert_test( $name, $a instanceof WP_REST_Response && 357.0 === $a->get_data()['data']['salon_share_gross'] && $b instanceof WP_REST_Response && 60.0 === $b->get_data()['data']['shortfall_gross'], var_export( [ $a, $b ], true ) );
} );

salon_case( 'the INCI handler answers 400 for an empty list, a lone comma and an array, and keeps line breaks as separators', function ( string $name ) {
	$codes = array_map(
		static fn( $inci ) => salon_error_code( WM_Salon_REST_Controller::handle_inci_check( new WP_REST_Request( [ 'inci' => $inci ] ) ) ),
		[ '', ',', [ 'Aqua' ] ]
	);
	$lines = WM_Salon_REST_Controller::handle_inci_check( new WP_REST_Request( [ 'inci' => "Aqua\nLimonene\nLinalool" ] ) );
	assert_test( $name, [ 'wm_salon_no_ingredients/400', 'wm_salon_no_ingredients/400', 'rest_invalid_param/400' ] === $codes && $lines instanceof WP_REST_Response && 3 === $lines->get_data()['data']['total_ingredients'], implode( ', ', $codes ) . ' ' . var_export( $lines, true ) );
} );

salon_case( 'both REST routes enforce transient IP-HMAC rate-limiting (429 on burst, zero raw IP stored, admin bypass) and cap INCI size at 64 KiB', function ( string $name ) {
	$GLOBALS['salon_routes'] = [];
	WM_Salon_REST_Controller::register_routes();
	$perm_s = $GLOBALS['salon_routes']['/wm-salon/v1/stuhlmiete']['permission_callback'] ?? null;
	$perm_i = $GLOBALS['salon_routes']['/wm-salon/v1/inci-check']['permission_callback'] ?? null;
	$key    = WM_Salon_REST_Controller::rate_limit_key( '203.0.113.42' );

	add_filter( 'wm_salon_rest_rate_limit', static fn(): int => 2 );
	$req = new WP_REST_Request( [ 'gross_revenue' => 100 ] );
	$r1  = WM_Salon_REST_Controller::check_public_access( $req );
	$r2  = WM_Salon_REST_Controller::check_public_access( $req );
	$r3  = WM_Salon_REST_Controller::check_public_access( $req );

	$GLOBALS['salon_is_admin'] = true;
	$r4                        = WM_Salon_REST_Controller::check_public_access( $req );

	$huge = WM_Salon_REST_Controller::handle_inci_check( new WP_REST_Request( [ 'inci' => str_repeat( 'Aqua, ', 12000 ) ] ) );

	assert_test(
		$name,
		[ 'WM_Salon_REST_Controller', 'check_public_access' ] === $perm_s
		&& [ 'WM_Salon_REST_Controller', 'check_public_access' ] === $perm_i
		&& str_starts_with( $key, 'wm_salon_rl_' )
		&& ! str_contains( $key, '203.0.113.42' )
		&& true === $r1 && true === $r2
		&& 'wm_salon_rate_limited/429' === salon_error_code( $r3 )
		&& true === $r4
		&& 'rest_invalid_param/400' === salon_error_code( $huge )
	);
} );

// =====================================================================================================================
echo "\n[GROUP 4] Shortcodes\n";
// =====================================================================================================================

salon_case( "both shortcodes accept '' as attributes (WordPress before 6.5; was: fatal TypeError)", function ( string $name ) {
	assert_test( $name, str_contains( WM_Salon_Shortcodes::render_stuhlmiete_calculator( '' ), 'wm-salon-calc-box' ) && str_contains( WM_Salon_Shortcodes::render_inci_checker( '' ), 'wm-salon-inci-box' ) );
} );

salon_case( 'no inline style and no colour in the markup; the stylesheet is enqueued', function ( string $name ) {
	$GLOBALS['salon_styles'] = [];
	$_POST                   = [ 'wm_salon_calc_submit' => '1', 'wm_salon_gross' => '100', 'wm_salon_rate' => '30', 'wm_salon_fixed' => '130', 'wm_inci_submit' => '1', 'wm_inci_text' => 'Aqua, Limonene' ];
	$html                    = WM_Salon_Shortcodes::render_stuhlmiete_calculator() . WM_Salon_Shortcodes::render_inci_checker();
	assert_test( $name, ! str_contains( $html, 'style=' ) && 0 === preg_match( '/(?<!&)#[0-9a-fA-F]{3,6}\b/', $html ) && str_ends_with( (string) ( $GLOBALS['salon_styles'][ WM_Salon_Shortcodes::STYLE_HANDLE ] ?? '' ), 'assets/css/wm-salon-hub.css' ), var_export( $GLOBALS['salon_styles'], true ) );
} );

salon_case( 'slashed POST data shows no backslash (was: Parfum \\"Limonene\\")', function ( string $name ) {
	$_POST = [ 'wm_inci_submit' => '1', 'wm_inci_text' => wp_slash( 'Parfum "Limonene", Aqua' ) ];
	$html  = WM_Salon_Shortcodes::render_inci_checker();
	assert_test( $name, ! str_contains( $html, '\\' ) && str_contains( $html, 'Limonene' ), $html );
} );

salon_case( 'the calculator keeps the submitted values and computes from them (was: defaults shown next to the result)', function ( string $name ) {
	$_POST = [ 'wm_salon_calc_submit' => '1', 'wm_salon_gross' => '777', 'wm_salon_rate' => '25', 'wm_salon_fixed' => '0' ];
	$html  = WM_Salon_Shortcodes::render_stuhlmiete_calculator();
	assert_test( $name, str_contains( $html, 'value="777"' ) && str_contains( $html, 'value="25"' ) && str_contains( $html, '194,25 €' ), $html );
} );

salon_case( 'invalid input gives a message instead of figures: text as amount, a share of 150 %', function ( string $name ) {
	$_POST = [ 'wm_salon_calc_submit' => '1', 'wm_salon_gross' => 'abc', 'wm_salon_rate' => '30', 'wm_salon_fixed' => '0' ];
	$a     = WM_Salon_Shortcodes::render_stuhlmiete_calculator();
	$_POST = [ 'wm_salon_calc_submit' => '1', 'wm_salon_gross' => '100', 'wm_salon_rate' => '150', 'wm_salon_fixed' => '0' ];
	$b     = WM_Salon_Shortcodes::render_stuhlmiete_calculator();
	assert_test( $name, str_contains( $a, 'Bitte gültige Beträge' ) && str_contains( $b, 'Bitte gültige Beträge' ) && ! str_contains( $b, 'Abrechnungsergebnis' ), $b );
} );

salon_case( 'a fixed rent above the turnover shows the shortfall', function ( string $name ) {
	$_POST = [ 'wm_salon_calc_submit' => '1', 'wm_salon_gross' => '100', 'wm_salon_rate' => '30', 'wm_salon_fixed' => '130' ];
	assert_test( $name, str_contains( WM_Salon_Shortcodes::render_stuhlmiete_calculator(), 'übersteigt den Umsatz um 60,00 €' ) );
} );

salon_case( 'an empty INCI list says so and shows no score or badge; a list with line breaks counts each line', function ( string $name ) {
	$_POST = [ 'wm_inci_submit' => '1', 'wm_inci_text' => ',' ];
	$empty = WM_Salon_Shortcodes::render_inci_checker();
	$_POST = [ 'wm_inci_submit' => '1', 'wm_inci_text' => "Aqua\nLimonene\nLinalool" ];
	$lines = WM_Salon_Shortcodes::render_inci_checker();
	assert_test( $name, str_contains( $empty, 'keinen Inhaltsstoff' ) && ! str_contains( $empty, 'CLEAN_BEAUTY' ) && ! str_contains( $empty, '/ 100' ) && str_contains( $lines, '3 Inhaltsstoffe erkannt' ), $lines );
} );

salon_case( 'no emoji, no "deklarationspflichtig", no 26-allergen, GoBD or legal-certainty claim; icons are SVG', function ( string $name ) use ( $emoji ) {
	$_POST = [ 'wm_inci_submit' => '1', 'wm_inci_text' => 'Aqua, Linalool', 'wm_salon_calc_submit' => '1', 'wm_salon_gross' => '100', 'wm_salon_rate' => '30', 'wm_salon_fixed' => '130' ];
	$html  = WM_Salon_Shortcodes::render_stuhlmiete_calculator() . WM_Salon_Shortcodes::render_inci_checker();
	assert_test( $name, 0 === preg_match( $emoji, $html ) && ! preg_match( '/deklarationspflichtig|26 EU|GoBD|rechtssicher/u', $html ) && 1 === preg_match( '#<svg class="wm-salon-icon"[^>]*aria-hidden="true"#', $html ) );
} );

// =====================================================================================================================
echo "\n[GROUP 5] Stylesheet: tokens only, contrast, target size\n";
// =====================================================================================================================

$css = (string) file_get_contents( $root . '/assets/css/wm-salon-hub.css' );
preg_match_all( '/^\s*--salon-([a-z0-9-]+):\s*([^;]+);/m', $css, $tm );
$tokens = array_combine( $tm[1], array_map( 'trim', $tm[2] ) );

salon_case( 'rules use var() only: no colour, px, rem or em outside the custom-property lines', function ( string $name ) use ( $css ) {
	$offending = [];
	foreach ( preg_split( '/\R/', (string) preg_replace( '#/\*.*?\*/#s', '', $css ) ) as $line ) {
		if ( preg_match( '/^\s*--salon-/', $line ) ) {
			continue;
		}
		if ( preg_match( '/#[0-9a-fA-F]{3,6}\b|\b\d+(\.\d+)?(px|rem|em)\b/', $line ) ) {
			$offending[] = trim( $line );
		}
	}
	assert_test( $name, [] === $offending, implode( ' | ', $offending ) );
} );

salon_case( 'contrast from the tokens: button text 4.5:1, text pairs 4.5:1, field border, buttons and focus 3:1 (was: 3.19, 3.77, 2.36 and 1.93)', function ( string $name ) use ( $tokens ) {
	$t     = static fn( string $k ): string => $tokens[ $k ] ?? '#000';
	$pairs = [
		[ 'accent-contrast', 'accent', 4.5 ], [ 'accent-contrast', 'accent-inci', 4.5 ],
		[ 'text', 'surface', 4.5 ], [ 'text-muted', 'surface', 4.5 ], [ 'heading', 'surface', 4.5 ], [ 'heading-inci', 'surface', 4.5 ],
		[ 'text', 'surface-raised', 4.5 ], [ 'result-title', 'surface-raised', 4.5 ], [ 'value-salon', 'surface-raised', 4.5 ], [ 'value-stylist', 'surface-raised', 4.5 ],
		[ 'score-good', 'surface-raised', 4.5 ], [ 'score-medium', 'surface-raised', 4.5 ], [ 'score-poor', 'surface-raised', 4.5 ], [ 'badge-text', 'badge-bg', 4.5 ], [ 'warning', 'surface-raised', 4.5 ],
		[ 'field-border', 'surface', 3.0 ], [ 'field-border', 'surface-raised', 3.0 ], [ 'accent', 'surface', 3.0 ], [ 'accent-inci', 'surface', 3.0 ], [ 'focus', 'surface', 3.0 ],
	];
	$low = [];
	foreach ( $pairs as [ $fg, $bg, $min ] ) {
		$ratio = salon_contrast( $t( $fg ), $t( $bg ) );
		if ( $ratio < $min ) {
			$low[] = sprintf( '%s on %s %.2f < %.1f', $fg, $bg, $ratio, $min );
		}
	}
	assert_test( $name, [] === $low, implode( '; ', $low ) );
} );

salon_case( 'buttons and fields are at least 44px high', function ( string $name ) use ( $tokens, $css ) {
	$target = (int) ( $tokens['target'] ?? 0 );
	assert_test( $name, $target >= 44 && str_ends_with( (string) ( $tokens['target'] ?? '' ), 'px' ) && 2 === substr_count( $css, 'min-height: var(--salon-target);' ), (string) $target );
} );

// =====================================================================================================================
echo "\n[GROUP 6] Hub adapter\n";
// =====================================================================================================================

salon_case( 'no settings URL, an unknown action fails, the self test passes and health follows it', function ( string $name ) {
	$adapter = new WM_Salon_Spoke_Adapter();
	assert_test(
		$name,
		null === $adapter->get_settings_url() && false === $adapter->execute_action( 'purge_everything' )['success'] && true === $adapter->execute_action( 'self_test' )['success']
		&& 'HEALTHY' === $adapter->get_health_status() && 'ERROR' === WM_Salon_Spoke_Adapter::health_for( [ 'success' => false ] ) && 'ERROR' === WM_Salon_Spoke_Adapter::health_for( [] )
	);
} );

salon_case( 'telemetry is measured (two registered shortcodes, one when a tag is missing, self test PASS) instead of the literals ACTIVE, 2, 2', function ( string $name ) {
	$adapter = new WM_Salon_Spoke_Adapter();
	$m       = $adapter->get_telemetry_metrics();
	$saved   = $GLOBALS['salon_shortcodes'];
	unset( $GLOBALS['salon_shortcodes']['wm_salon_inci_checker'] );
	$one                          = $adapter->get_telemetry_metrics();
	$GLOBALS['salon_shortcodes'] = $saved;
	assert_test( $name, [ 'shortcodes_registered' => 2, 'self_test' => 'PASS' ] === $m && 1 === $one['shortcodes_registered'], var_export( [ $m, $one ], true ) );
} );

salon_case( 'the SBOM carries the SHA-256 of the main file and names the cent arithmetic', function ( string $name ) use ( $root ) {
	$sbom = ( new WM_Salon_Spoke_Adapter() )->get_sbom_data();
	assert_test( $name, hash_file( 'sha256', $root . '/wm-salon-sovereign-hub.php' ) === ( $sbom['hashes'][0]['content'] ?? null ) && str_contains( $sbom['properties'][0]['value'], 'whole cents' ) && 'GPL-2.0-or-later' === $sbom['licenses'][0]['license']['id'] );
} );

// =====================================================================================================================
echo "\n[GROUP 7] WP-CLI\n";
// =====================================================================================================================

salon_case( 'the documented subcommand names are registered and the underscore names stay as aliases', function ( string $name ) {
	$inci = (string) ( new ReflectionMethod( 'WM_Salon_CLI', 'analyze_inci' ) )->getDocComment();
	$rent = (string) ( new ReflectionMethod( 'WM_Salon_CLI', 'calculate_rent' ) )->getDocComment();
	assert_test( $name, str_contains( $inci, '@subcommand analyze-inci' ) && str_contains( $inci, '@alias analyze_inci' ) && str_contains( $rent, '@subcommand calculate-rent' ) && str_contains( $rent, '@alias calculate_rent' ) );
} );

salon_case( 'calculate-rent takes --vat and --fixed, labels the net amount as net and warns about a shortfall', function ( string $name ) {
	$cli         = new WM_Salon_CLI();
	WP_CLI::$out = [];
	$cli->calculate_rent( [], [ 'revenue' => '1070', 'rate' => '0.30', 'fixed' => '800', 'vat' => '7' ] );
	$lines = array_column( WP_CLI::$out, 1 );
	assert_test(
		$name,
		in_array( 'Nettoumsatz:           1000.00 EUR', $lines, true ) && in_array( 'Enthaltene MwSt:       70.00 EUR', $lines, true ) && in_array( 'Salon-Anteil (brutto): 1121.00 EUR', $lines, true )
		&& [ 'warning', 'Der Salon-Anteil übersteigt den Umsatz um 51.00 EUR.' ] === end( WP_CLI::$out ) && ! preg_grep( '/Nettoumsatz.*inkl/', $lines ),
		var_export( WP_CLI::$out, true )
	);
} );

salon_case( 'analyze-inci with an empty list and calculate-rent without --revenue end with an error', function ( string $name ) {
	$cli    = new WM_Salon_CLI();
	$errors = 0;
	foreach ( [ static fn() => $cli->analyze_inci( [ ',' ], [] ), static fn() => $cli->calculate_rent( [], [] ) ] as $call ) {
		try {
			$call();
		} catch ( SalonCliError $e ) {
			++$errors;
		}
	}
	assert_test( $name, 2 === $errors, "{$errors} of 2" );
} );

// =====================================================================================================================
echo "\n[GROUP 8] Main file, uninstall, catalogs\n";
// =====================================================================================================================

salon_case( 'the text domain is loaded on init from languages/ and wm_salon_ready fires', function ( string $name ) {
	$GLOBALS['salon_textdomains']   = [];
	$GLOBALS['salon_fired_actions'] = [];
	foreach ( $GLOBALS['salon_actions']['init'] ?? [] as $callback ) {
		$callback();
	}
	assert_test(
		$name,
		[ [ 'wm-salon-sovereign-hub', 'wm-salon-sovereign-hub/languages' ] ] === $GLOBALS['salon_textdomains']
		// The version constant of the main file, bumped with the header: a literal here had to be edited on every release.
		&& [ [ WM_SALON_HUB_VERSION ] ] === ( $GLOBALS['salon_fired_actions']['wm_salon_ready'] ?? [] ),
		var_export( [ $GLOBALS['salon_textdomains'], $GLOBALS['salon_fired_actions'] ], true )
	);
} );

salon_case( 'D-UNINSTALL: uninstall.php preserves data by default and deletes only when wm_salon_delete_data_on_uninstall is opted in', function ( string $name ) use ( $root ) {
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		define( 'WP_UNINSTALL_PLUGIN', true );
	}
	$GLOBALS['salon_options'] = [ 'wm_salon_delete_data_on_uninstall' => false ];
	require $root . '/uninstall.php';
	$kept_default = array_key_exists( 'wm_salon_delete_data_on_uninstall', $GLOBALS['salon_options'] );

	$GLOBALS['salon_options'] = [ 'wm_salon_delete_data_on_uninstall' => '0' ];
	require $root . '/uninstall.php';
	$kept_zero = array_key_exists( 'wm_salon_delete_data_on_uninstall', $GLOBALS['salon_options'] );

	$GLOBALS['salon_options'] = [ 'wm_salon_delete_data_on_uninstall' => '1' ];
	require $root . '/uninstall.php';
	$deleted_opt_in = ! array_key_exists( 'wm_salon_delete_data_on_uninstall', $GLOBALS['salon_options'] );

	$GLOBALS['salon_settings']        = [];
	$GLOBALS['salon_settings_fields'] = [];
	WM_Salon_Integrations::register_settings();
	ob_start();
	WM_Salon_Integrations::render_uninstall_field();
	$field_html = (string) ob_get_clean();

	assert_test(
		$name,
		$kept_default && $kept_zero && $deleted_opt_in
		&& false === ( $GLOBALS['salon_settings']['wm_salon_delete_data_on_uninstall']['args']['default'] ?? null )
		&& isset( $GLOBALS['salon_settings_fields']['wm_salon_delete_data_on_uninstall'] )
		&& str_contains( $field_html, 'type="checkbox"' )
		&& ! str_contains( $field_html, 'checked=' )
		&& ! str_contains( $field_html, 'style=' )
	);
} );

salon_case( 'developer filters and ecosystem bridges (wm-salon/metrics Block Bindings + {{wm_salon_metric}} GenerateBlocks tag)', function ( string $name ) {
	add_filter(
		'wm_salon_stuhlmiete_calculation',
		static function ( array $res ): array {
			$res['salon_share_gross'] = 999.0;
			return $res;
		}
	);
	$filtered_split = salon_split( 1190.0 );
	unset( $GLOBALS['salon_filters']['wm_salon_stuhlmiete_calculation'] );

	add_filter(
		'wm_salon_inci_database',
		static function ( array $db ): array {
			$db['silicones'][] = 'cyclohexasiloxane';
			return $db;
		}
	);
	$custom_inci = WM_Salon_INCI_Analyzer::analyze( 'Aqua, Cyclohexasiloxane' );
	unset( $GLOBALS['salon_filters']['wm_salon_inci_database'] );

	$GLOBALS['salon_block_bindings'] = [];
	WM_Salon_Integrations::register_block_bindings();
	$bb_share = WM_Salon_Integrations::get_block_binding_value( [ 'key' => 'salon_share_gross', 'gross' => 1190, 'rate' => 0.30 ] );
	$bb_count = WM_Salon_Integrations::get_block_binding_value( [ 'key' => 'substances_count' ] );

	$GLOBALS['salon_gb_tags'] = [];
	WM_Salon_Integrations::init();
	WM_Salon_Integrations::reset_for_tests();
	WM_Salon_Integrations::register_generateblocks_tags();
	$gb_payout = WM_Salon_Integrations::render_generateblocks_tag( [ 'key' => 'stylist_payout_gross', 'gross' => 1190, 'rate' => 0.30 ] );
	$gb_rep    = apply_filters( 'generateblocks_dynamic_tag_replacement', '', [ 'tag' => 'wm_salon_metric', 'options' => [ 'key' => 'stylist_payout_gross', 'gross' => 1190, 'rate' => 0.30 ] ] );
	$gb_native = apply_filters( 'generateblocks_dynamic_tag_output', 'Post Title', [ 'source' => 'post' ], 'Post Title' );

	assert_test(
		$name,
		999.0 === $filtered_split['salon_share_gross']
		&& true === $custom_inci['has_silicones']
		&& isset( $GLOBALS['salon_block_bindings']['wm-salon/metrics'] )
		&& '357,00 €' === $bb_share
		&& '24' === $bb_count
		&& isset( $GLOBALS['salon_gb_tags']['wm_salon_metric'] )
		&& '833,00 €' === $gb_payout
		&& '833,00 €' === $gb_rep
		&& 'Post Title' === $gb_native
	);
} );

salon_case( 'every msgid of the code is in the template; no catalog carries double-encoded UTF-8', function ( string $name ) use ( $root ) {
	$code = '';
	foreach ( glob( $root . '/includes/*.php' ) as $file ) {
		$code .= (string) file_get_contents( $file );
	}
	preg_match_all( "/\b(?:__|esc_html__|esc_attr__)\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $single );
	preg_match_all( "/\b_n\(\s*'((?:[^'\\\\]|\\\\.)*)',\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $plural );
	$pot     = (string) @file_get_contents( $root . '/languages/wm-salon-sovereign-hub.pot' );
	$missing = [];
	foreach ( array_merge( $single[1], $plural[1], $plural[2] ) as $msgid ) {
		if ( ! str_contains( $pot, '"' . addcslashes( stripslashes( $msgid ), '"' ) . '"' ) ) {
			$missing[] = $msgid;
		}
	}
	$broken = [];
	foreach ( glob( $root . '/languages/*.{po,pot}', GLOB_BRACE ) as $file ) {
		if ( preg_match( '/Ã[\x{0080}-\x{00BF}]|Ã[¤¶¼Ÿ„–œ©]/u', (string) file_get_contents( $file ) ) ) {
			$broken[] = basename( $file );
		}
	}
	assert_test( $name, count( $single[1] ) >= 20 && [] === $missing && [] === $broken, 'missing: ' . implode( ' | ', $missing ) . ' · double-encoded: ' . implode( ', ', $broken ) );
} );

// =====================================================================================================================
echo "\n[GROUP 9] Documents say what the code does\n";
// =====================================================================================================================

salon_case( 'README: no marketing image embedded; documents the cent arithmetic, the shortfall and the hyphenated commands', function ( string $name ) use ( $root ) {
	$readme = (string) file_get_contents( $root . '/README.md' );
	assert_test( $name, 0 === preg_match( '/!\[[^\]]*\]\((?!https?:)[^)]*assets\/images/', $readme ) && str_contains( $readme, 'shortfall_gross' ) && str_contains( $readme, 'whole cents' ) && str_contains( $readme, 'wp salon analyze-inci' ) && ! str_contains( $readme, 'Translations-24%20EU%20Languages' ) );
} );

salon_case( 'humans.txt, llms.txt, SECURITY.md, CONTRIBUTING.md, CHANGELOG.md and the German README carry no CEO, unbuilt stack, SLA, legal article, unverified allergen duty or emoji', function ( string $name ) use ( $root, $emoji ) {
	$humans = salon_doc_text( $root . '/public/humans.txt' );
	$llms   = salon_doc_text( $root . '/llms.txt' );
	$sec    = salon_doc_text( $root . '/SECURITY.md' );
	$contr  = salon_doc_text( $root . '/CONTRIBUTING.md' );
	$de     = salon_doc_text( $root . '/README.de.md' );
	$hits   = array_filter(
		[
			'humans CEO'         => str_contains( $humans, 'CEO' ),
			'humans stack'       => (bool) preg_match( '/Gutenberg|React|Transaction-Safe|InnoDB|Audit Logs|Compliant Architecture|WCAG 2\.2 AA|PSR-12/', $humans ),
			'llms PSR-12'        => str_contains( $llms, 'PSR-12' ),
			'security SLA'       => (bool) preg_match( '/Within 24 hours|Within 72 hours|CRA Art\. 14|BSI Guidelines|:white_check_mark:/', $sec ),
			'contributing claim' => str_contains( $contr, 'high standards of security, accessibility, and legal compliance' ),
			'allergen duty'      => (bool) preg_match( '/deklarationspflichtig|declarable|declaración obligatoria/u', $de . (string) file_get_contents( $root . '/README.md' ) . (string) file_get_contents( $root . '/README.es.md' ) . (string) file_get_contents( $root . '/readme.txt' ) ),
			'emoji'              => 1 === preg_match( $emoji, $humans . $llms . $sec . $contr . (string) file_get_contents( $root . '/CHANGELOG.md' ) ),
		]
	);
	assert_test( $name, [] === $hits, implode( ', ', array_keys( $hits ) ) );
} );

salon_case( 'AGENTS.md is a symlink to CLAUDE.md; bom.json is valid CycloneDX 1.6; readme.txt short description <= 150 chars; images and repo docs out of package', function ( string $name ) use ( $root ) {
	$readme_txt = (string) file_get_contents( $root . '/readme.txt' );
	$short      = preg_match( '/^License URI:.*\R\R(.+)$/m', $readme_txt, $m ) ? trim( $m[1] ) : '';
	$dist       = (string) file_get_contents( $root . '/.distignore' );
	$claude     = (string) file_get_contents( $root . '/CLAUDE.md' );
	$bom        = json_decode( (string) @file_get_contents( $root . '/bom.json' ), true );
	assert_test(
		$name,
		is_link( $root . '/AGENTS.md' ) && 'CLAUDE.md' === readlink( $root . '/AGENTS.md' )
		&& file_get_contents( $root . '/AGENTS.md' ) === $claude
		&& str_contains( $claude, 'There is no `/src/`.' )
		&& is_array( $bom ) && '1.6' === ( $bom['specVersion'] ?? '' )
		&& hash_file( 'sha256', $root . '/wm-salon-sovereign-hub.php' ) === ( $bom['metadata']['component']['hashes'][0]['content'] ?? '' )
		&& '' !== $short && strlen( $short ) <= 150
		&& 1 === preg_match( '/^assets\/images\/$/m', $dist ) && 1 === preg_match( '/^DEVELOPERS\.md$/m', $dist ),
		strlen( $short ) . ': ' . $short
	);
} );

salon_case( 'CI runs the suite with its results line and the mutation check, and no SBOM step that can only warn', function ( string $name ) use ( $root ) {
	$ci = (string) file_get_contents( $root . '/.github/workflows/ci.yml' );
	assert_test( $name, str_contains( $ci, 'run: bash tests/run-mutations.sh' ) && str_contains( $ci, 'TEST SUITE RESULTS' ) && ! str_contains( $ci, 'bom.json' ) );
} );

// ---------------------------------------------------------------------------------------------------------------------

echo "\n==============================================================================\n";
echo "  TEST SUITE RESULTS: {$GLOBALS['salon_passed']} PASSED | {$GLOBALS['salon_failed']} FAILED\n";
echo "==============================================================================\n";

exit( $GLOBALS['salon_failed'] > 0 ? 1 : 0 );
