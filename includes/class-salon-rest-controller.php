<?php
/**
 * WM Salon Sovereign Hub — REST routes
 *
 * - POST /wp-json/wm-salon/v1/stuhlmiete   gross_revenue (required), rent_rate, fixed_rent, vat_rate
 * - POST /wp-json/wm-salon/v1/inci-check   inci (required)
 *
 * Both routes are public and store nothing. Core validates the arguments before a handler runs and the handlers check
 * again, so missing or malformed input answers 400. Until 2026-09-15 an empty body answered 200 with all figures 0,
 * rent_rate=30 (a percentage, as the form takes it) was clamped silently to a 100 % share, an amount of 1e309 answered
 * HTTP 500, and an array as inci was analysed as the word "Array".
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_Salon_REST_Controller {

	public const DEFAULT_RATE_LIMIT  = 60;
	public const RATE_WINDOW_SECONDS = 60;
	public const MAX_INCI_BYTES      = 65536;

	public static function init(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/**
	 * Public calculator access with transient-based IP HMAC rate-limiting.
	 *
	 * Raw client IPs are hashed with HMAC-SHA256 keyed by the site auth salt so no personal IP address is stored.
	 *
	 * @return bool|WP_Error
	 */
	public static function check_public_access( \WP_REST_Request $request ) {
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		$limit = self::DEFAULT_RATE_LIMIT;
		if ( function_exists( 'apply_filters' ) ) {
			$limit = (int) apply_filters( 'wm_salon_rest_rate_limit', $limit, $request );
		}
		if ( $limit <= 0 ) {
			return new WP_Error(
				'wm_salon_rate_limited',
				__( 'Zu viele Anfragen. Bitte in einer Minute erneut versuchen.', 'wm-salon-sovereign-hub' ),
				[ 'status' => 429 ]
			);
		}

		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			return true;
		}

		$key   = self::rate_limit_key();
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return new WP_Error(
				'wm_salon_rate_limited',
				__( 'Zu viele Anfragen. Bitte in einer Minute erneut versuchen.', 'wm-salon-sovereign-hub' ),
				[ 'status' => 429 ]
			);
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW_SECONDS );
		return true;
	}

	/**
	 * Deterministic transient key derived from an HMAC-SHA256 of the client IP.
	 */
	public static function rate_limit_key( ?string $ip_override = null ): string {
		$ip = $ip_override;
		if ( null === $ip ) {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
				: '127.0.0.1';
		}
		$secret = function_exists( 'wp_salt' ) ? (string) wp_salt( 'auth' ) : 'wm-salon-sovereign-hub';
		$digest = hash_hmac( 'sha256', $ip, $secret );
		return 'wm_salon_rl_' . substr( $digest, 0, 24 );
	}

	/**
	 * Arguments of the split route: [ name => [ default (null = required), minimum, maximum ] ].
	 *
	 * @return array<string, array{0: float|null, 1: float, 2: float}>
	 */
	public static function split_limits(): array {
		return [
			'gross_revenue' => [ null, 0.0, WM_Salon_Stuhlmiete_Ledger::MAX_AMOUNT ],
			'rent_rate'     => [ 0.30, 0.0, 1.0 ],
			'fixed_rent'    => [ 0.0, 0.0, WM_Salon_Stuhlmiete_Ledger::MAX_AMOUNT ],
			'vat_rate'      => [ 19.0, 0.0, 100.0 ],
		];
	}

	public static function register_routes(): void {
		$split_args = [];
		foreach ( self::split_limits() as $name => [ $default, $min, $max ] ) {
			$split_args[ $name ] = [
				'type'    => 'number',
				'minimum' => $min,
				'maximum' => $max,
			] + ( null === $default ? [ 'required' => true ] : [ 'default' => $default ] );
		}

		register_rest_route(
			'wm-salon/v1',
			'/stuhlmiete',
			[
				'methods'             => 'POST',
				'callback'            => [ self::class, 'handle_stuhlmiete' ],
				'permission_callback' => [ self::class, 'check_public_access' ],
				'args'                => $split_args,
			]
		);

		register_rest_route(
			'wm-salon/v1',
			'/inci-check',
			[
				'methods'             => 'POST',
				'callback'            => [ self::class, 'handle_inci_check' ],
				'permission_callback' => [ self::class, 'check_public_access' ],
				'args'                => [
					'inci' => [
						'type'     => 'string',
						'required' => true,
					],
				],
			]
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_stuhlmiete( \WP_REST_Request $request ) {
		$values = [];
		// Checked again here for callers that reach the handler without core's argument validation.
		foreach ( self::split_limits() as $name => [ $default, $min, $max ] ) {
			$raw = $request->get_param( $name );
			if ( null === $raw ) {
				if ( null === $default ) {
					/* translators: %s: parameter name. */
					return new WP_Error( 'rest_missing_callback_param', sprintf( __( '%s fehlt.', 'wm-salon-sovereign-hub' ), $name ), [ 'status' => 400 ] );
				}
				$values[ $name ] = $default;
				continue;
			}
			if ( is_string( $raw ) ) {
				$raw = sanitize_text_field( $raw );
			}
			$number = is_int( $raw ) || is_float( $raw ) || ( is_string( $raw ) && is_numeric( $raw ) ) ? (float) $raw : null;
			if ( null === $number || ! is_finite( $number ) || $number < $min || $number > $max ) {
				return new WP_Error(
					'rest_invalid_param',
					/* translators: 1: parameter name, 2: minimum, 3: maximum. */
					sprintf( __( '%1$s muss eine Zahl zwischen %2$s und %3$s sein.', 'wm-salon-sovereign-hub' ), $name, (string) $min, (string) $max ),
					[ 'status' => 400 ]
				);
			}
			$values[ $name ] = $number;
		}

		try {
			$split = WM_Salon_Stuhlmiete_Ledger::calculate_split( $values['gross_revenue'], $values['rent_rate'], $values['fixed_rent'], $values['vat_rate'] );
		} catch ( InvalidArgumentException $e ) {
			return new WP_Error( 'rest_invalid_param', __( 'Die Beträge können nicht berechnet werden.', 'wm-salon-sovereign-hub' ), [ 'status' => 400 ] );
		}

		if ( function_exists( 'do_action' ) ) {
			do_action( 'wm_salon_rest_calculated', 'stuhlmiete', $split, $request );
		}

		return new \WP_REST_Response(
			[
				'success'   => true,
				'data'      => $split,
				'timestamp' => gmdate( 'c' ),
			],
			200
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_inci_check( \WP_REST_Request $request ) {
		$raw = $request->get_param( 'inci' );
		if ( ! is_string( $raw ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'inci muss ein Text sein.', 'wm-salon-sovereign-hub' ), [ 'status' => 400 ] );
		}
		if ( strlen( $raw ) > self::MAX_INCI_BYTES ) {
			return new WP_Error( 'rest_invalid_param', __( 'Die Inhaltsstoffliste überschreitet die zulässige Länge.', 'wm-salon-sovereign-hub' ), [ 'status' => 400 ] );
		}

		// Line breaks separate ingredients, so they are kept (sanitize_text_field() collapsed them).
		$analysis = WM_Salon_INCI_Analyzer::analyze( sanitize_textarea_field( $raw ) );
		if ( 0 === $analysis['total_ingredients'] ) {
			return new WP_Error( 'wm_salon_no_ingredients', __( 'Die Liste enthält keinen Inhaltsstoff.', 'wm-salon-sovereign-hub' ), [ 'status' => 400 ] );
		}

		if ( function_exists( 'do_action' ) ) {
			do_action( 'wm_salon_rest_calculated', 'inci_check', $analysis, $request );
		}

		return new \WP_REST_Response(
			[
				'success'   => true,
				'data'      => $analysis,
				'timestamp' => gmdate( 'c' ),
			],
			200
		);
	}
}
