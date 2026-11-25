<?php
/**
 * WM Salon Sovereign Hub — WP-CLI commands
 *
 * WP-CLI names a subcommand after its method unless the docblock sets @subcommand. Until 2026-09-15 the documented
 * `wp salon analyze-inci` and `wp salon calculate-rent` answered "not a registered subcommand" (measured on local and staging WordPress
 * installs); the underscore names stay as aliases.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_Salon_CLI {

	/**
	 * Shows the version and the entry points of both calculators.
	 *
	 * ## EXAMPLES
	 *
	 *     wp salon status
	 */
	public function status( array $args, array $assoc_args ): void {
		$opt_in = function_exists( 'get_option' ) && filter_var( get_option( 'wm_salon_delete_data_on_uninstall', false ), FILTER_VALIDATE_BOOLEAN )
			? 'aktiviert'
			: 'deaktiviert (Standard: Daten erhalten)';
		WP_CLI::line( 'WM Salon Sovereign Hub ' . ( defined( 'WM_SALON_HUB_VERSION' ) ? WM_SALON_HUB_VERSION : '?' ) );
		WP_CLI::line( 'Stuhlmiete:  [wm_salon_stuhlmiete], REST /wm-salon/v1/stuhlmiete, wp salon calculate-rent' );
		WP_CLI::line( 'INCI-Prüfer: [wm_salon_inci_checker], REST /wm-salon/v1/inci-check, wp salon analyze-inci' );
		WP_CLI::line( 'Deinstallations-Löschung: ' . $opt_in );
	}

	/**
	 * Matches an ingredient list against the fixed lists of sulfates, silicones, parabens and fragrance allergens.
	 *
	 * ## OPTIONS
	 *
	 * <ingredients>
	 * : Ingredients separated by commas, semicolons or line breaks.
	 *
	 * ## EXAMPLES
	 *
	 *     wp salon analyze-inci "Aqua, Sodium Laureth Sulfate, Dimethicone, Limonene"
	 *
	 * @subcommand analyze-inci
	 * @alias analyze_inci
	 */
	public function analyze_inci( array $args, array $assoc_args ): void {
		$res = WM_Salon_INCI_Analyzer::analyze( (string) ( $args[0] ?? '' ) );

		if ( null === $res['clean_score'] ) {
			WP_CLI::error( 'Die Liste enthält keinen Inhaltsstoff.' );
			return;
		}

		WP_CLI::line( "INCI-Analyse ({$res['total_ingredients']} Inhaltsstoffe):" );
		WP_CLI::line( 'Clean-Score: ' . $res['clean_score'] . ' / 100' );
		WP_CLI::line( 'Sulfate:     ' . ( $res['has_sulfates'] ? 'gefunden' : 'nicht gefunden' ) );
		WP_CLI::line( 'Silikone:    ' . ( $res['has_silicones'] ? 'gefunden' : 'nicht gefunden' ) );
		WP_CLI::line( 'Parabene:    ' . ( $res['has_parabens'] ? 'gefunden' : 'nicht gefunden' ) );
		if ( ! empty( $res['allergens_found'] ) ) {
			WP_CLI::line( 'Allergene:   ' . implode( ', ', $res['allergens_found'] ) );
		}
		if ( ! empty( $res['badges'] ) ) {
			WP_CLI::line( 'Badges:      ' . implode( ', ', $res['badges'] ) );
		}
	}

	/**
	 * Splits a gross turnover between salon and stylist.
	 *
	 * ## OPTIONS
	 *
	 * --revenue=<revenue>
	 * : Gross turnover in EUR, VAT included.
	 *
	 * [--rate=<rate>]
	 * : Salon share as a fraction (0.30 for 30 %).
	 * ---
	 * default: 0.30
	 * ---
	 *
	 * [--fixed=<fixed>]
	 * : Fixed chair rent for the same period in EUR.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--vat=<vat>]
	 * : VAT rate in percent.
	 * ---
	 * default: 19
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp salon calculate-rent --revenue=1190 --rate=0.30
	 *
	 * @subcommand calculate-rent
	 * @alias calculate_rent
	 */
	public function calculate_rent( array $args, array $assoc_args ): void {
		$values = [];
		foreach ( [ 'revenue' => null, 'rate' => '0.30', 'fixed' => '0', 'vat' => '19' ] as $key => $default ) {
			$raw = $assoc_args[ $key ] ?? $default;
			if ( null === $raw || ! is_numeric( $raw ) ) {
				WP_CLI::error( sprintf( '--%s muss eine Zahl sein.', $key ) );
				return;
			}
			$values[ $key ] = (float) $raw;
		}

		try {
			$split = WM_Salon_Stuhlmiete_Ledger::calculate_split( $values['revenue'], $values['rate'], $values['fixed'], $values['vat'] );
		} catch ( InvalidArgumentException $e ) {
			WP_CLI::error( $e->getMessage() );
			return;
		}

		WP_CLI::line( sprintf( 'Bruttoumsatz:          %0.2f EUR', $split['gross_revenue'] ) );
		WP_CLI::line( sprintf( 'Nettoumsatz:           %0.2f EUR', $split['net_revenue'] ) );
		WP_CLI::line( sprintf( 'Enthaltene MwSt:       %0.2f EUR', $split['vat_amount'] ) );
		WP_CLI::line( sprintf( 'Salon-Anteil (brutto): %0.2f EUR', $split['salon_share_gross'] ) );
		WP_CLI::line( sprintf( 'Stylist (brutto):      %0.2f EUR (netto %0.2f EUR)', $split['stylist_payout_gross'], $split['stylist_payout_net'] ) );
		if ( $split['shortfall_gross'] > 0 ) {
			WP_CLI::warning( sprintf( 'Der Salon-Anteil übersteigt den Umsatz um %0.2f EUR.', $split['shortfall_gross'] ) );
		}
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'salon', 'WM_Salon_CLI' );
}
