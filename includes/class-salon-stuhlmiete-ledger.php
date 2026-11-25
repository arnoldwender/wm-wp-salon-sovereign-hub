<?php
/**
 * WM Salon Sovereign Hub — booth rent (Stuhlmiete) revenue split
 *
 * Splits a stylist's gross turnover into the salon's booth rent and the stylist's payout. A calculation only: nothing
 * is stored, and it does not assess whether a booth rental is genuine self-employment.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_Salon_Stuhlmiete_Ledger {

	/**
	 * Largest accepted amount in euros. The split works in whole cents and multiplies cents by up to 20 000; one
	 * billion euros keeps every intermediate value far inside PHP_INT_MAX.
	 */
	public const MAX_AMOUNT = 1000000000.0;

	/**
	 * Sanitize text to prevent Formula Injection (CWE-1236).
	 */
	public static function sanitize_formula( string $text ): string {
		if ( '' !== $text && in_array( $text[0], [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
			return "'" . $text;
		}
		return $text;
	}

	/**
	 * Calculate the booth-rent split between salon and independent stylist.
	 *
	 * Amounts are rounded to whole cents first and every step is computed in integer cents with commercial rounding
	 * (half up), so the result does not depend on binary floating point or on the PHP version. Until 2026-09-15 the
	 * split rounded floats: 30 % of 0.75 € gave 0.23 € on PHP 8.3 and 0.22 € on PHP 8.4 (measured on PHP 8.3 and PHP 8.4
	 * WordPress installs). A fixed rent larger than what is left is not hidden: the payout stays at 0 and
	 * `shortfall_gross` says by how much the salon's share exceeds the turnover.
	 *
	 * @param float $gross_revenue    Total client payment inclusive of VAT, in euros.
	 * @param float $rent_rate        Salon share as a fraction (0.30 for 30 %), clamped to 0-1.
	 * @param float $fixed_chair_rent Fixed chair rent for the same period, in euros.
	 * @param float $vat_rate         VAT rate in percent (19.0), clamped to 0-100.
	 * @return array{
	 *     gross_revenue: float,
	 *     net_revenue: float,
	 *     vat_amount: float,
	 *     salon_share_gross: float,
	 *     stylist_payout_gross: float,
	 *     stylist_payout_net: float,
	 *     shortfall_gross: float
	 * }
	 * @throws InvalidArgumentException For a value that is not a finite number, or an amount above MAX_AMOUNT.
	 */
	public static function calculate_split(
		float $gross_revenue,
		float $rent_rate = 0.30,
		float $fixed_chair_rent = 0.0,
		float $vat_rate = 19.0
	): array {
		foreach ( [ $gross_revenue, $rent_rate, $fixed_chair_rent, $vat_rate ] as $value ) {
			if ( ! is_finite( $value ) ) {
				throw new InvalidArgumentException( 'Betrag oder Satz ist keine endliche Zahl.' );
			}
		}
		if ( $gross_revenue > self::MAX_AMOUNT || $fixed_chair_rent > self::MAX_AMOUNT ) {
			throw new InvalidArgumentException( 'Betrag über der Obergrenze des Rechners.' );
		}

		$gross_cents = self::scaled( max( 0.0, $gross_revenue ), 100 );
		$fixed_cents = self::scaled( max( 0.0, $fixed_chair_rent ), 100 );
		// Share in basis points (0.30 = 3 000) and VAT in hundredths of a percent (19 % = 1 900). A negative VAT rate
		// used to make the divisor 0 (DivisionByZeroError, HTTP 500 on the public route, 2026-09-14).
		$rate_bp = self::scaled( max( 0.0, min( 1.0, $rent_rate ) ), 10000 );
		$vat_bp  = self::scaled( max( 0.0, min( 100.0, $vat_rate ) ), 100 );
		$divisor = 10000 + $vat_bp;

		$net_cents        = self::div_half_up( $gross_cents * 10000, $divisor );
		$salon_cents      = self::div_half_up( $gross_cents * $rate_bp, 10000 ) + $fixed_cents;
		$remaining_cents  = $gross_cents - $salon_cents;
		$payout_cents     = max( 0, $remaining_cents );
		$payout_net_cents = self::div_half_up( $payout_cents * 10000, $divisor );

		$result = [
			'gross_revenue'        => $gross_cents / 100.0,
			'net_revenue'          => $net_cents / 100.0,
			'vat_amount'           => ( $gross_cents - $net_cents ) / 100.0,
			'salon_share_gross'    => $salon_cents / 100.0,
			'stylist_payout_gross' => $payout_cents / 100.0,
			'stylist_payout_net'   => $payout_net_cents / 100.0,
			'shortfall_gross'      => max( 0, -$remaining_cents ) / 100.0,
		];

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters(
				'wm_salon_stuhlmiete_calculation',
				$result,
				[
					'gross_revenue'    => $gross_revenue,
					'rent_rate'        => $rent_rate,
					'fixed_chair_rent' => $fixed_chair_rent,
					'vat_rate'         => $vat_rate,
				]
			);
			if ( is_array( $filtered ) ) {
				$result = $filtered;
			}
		}

		return $result;
	}

	/**
	 * A non-negative value times a power of ten, as an integer. The product is first rounded to six decimals, which
	 * removes the binary error of a value with few decimals (0.29 × 100 = 28.999999999999996) the same way on every
	 * PHP version; the second round is half up.
	 */
	private static function scaled( float $value, int $factor ): int {
		return (int) round( round( $value * $factor, 6 ) );
	}

	/**
	 * Integer division of two non-negative integers, rounded half up.
	 */
	private static function div_half_up( int $dividend, int $divisor ): int {
		return intdiv( 2 * $dividend + $divisor, 2 * $divisor );
	}
}
