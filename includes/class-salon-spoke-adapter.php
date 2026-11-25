<?php
/**
 * WM Salon Sovereign Hub — Suite Hub spoke adapter
 *
 * Implements the WM Suite Hub contract (WM_Plugin_Module_Interface): health and telemetry from a self test of
 * both calculators, and a component entry for the hub's software bill of materials.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
	return;
}

use ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface;

final class WM_Salon_Spoke_Adapter implements WM_Plugin_Module_Interface {

	public function get_module_slug(): string {
		return 'wm-salon-sovereign-hub';
	}

	public function get_name(): string {
		return 'WM Salon Sovereign Hub';
	}

	public function get_version(): string {
		return defined( 'WM_SALON_HUB_VERSION' ) ? WM_SALON_HUB_VERSION : '1.2.1';
	}

	public function get_description(): string {
		return 'Stuhlmiete-Rechner (Umsatzaufteilung Salon / Stylist) & lokaler INCI-Inhaltsstoff-Prüfer.';
	}

	/**
	 * HEALTHY only when both calculators pass the self test. Until 2026-09-15 this returned a literal HEALTHY.
	 */
	public function get_health_status(): string {
		return self::health_for( $this->execute_action( 'self_test' ) );
	}

	/**
	 * @param array{success?: bool} $self_test
	 */
	public static function health_for( array $self_test ): string {
		return true === ( $self_test['success'] ?? false ) ? 'HEALTHY' : 'ERROR';
	}

	public function get_sbom_data(): array {
		$data = [
			'bom-ref'     => 'pkg:wordpress/wm-salon-sovereign-hub@' . $this->get_version(),
			'type'        => 'application',
			'name'        => $this->get_name(),
			'version'     => $this->get_version(),
			'description' => $this->get_description(),
			'scope'       => 'required',
			'licenses'    => [
				[
					'license' => [
						'id' => 'GPL-2.0-or-later',
					],
				],
			],
			'properties'  => [
				[
					'name'  => 'wm:features:stuhlmiete',
					'value' => 'Revenue split calculator in whole cents (shortcode, REST, WP-CLI); stores nothing',
				],
				[
					'name'  => 'wm:features:inci_analyzer',
					'value' => 'Local ingredient list match (5 sulfates, 6 silicones, 5 parabens, 8 fragrance allergens); no external request',
				],
			],
		];

		// A hash that cannot be computed is left out rather than reported as false.
		$main = dirname( __DIR__ ) . '/wm-salon-sovereign-hub.php';
		$hash = is_readable( $main ) ? hash_file( 'sha256', $main ) : false;
		if ( is_string( $hash ) ) {
			$data['hashes'] = [
				[
					'alg'     => 'SHA-256',
					'content' => $hash,
				],
			];
		}
		return $data;
	}

	public function get_dependencies(): array {
		return [];
	}

	/**
	 * The plugin has no admin screen.
	 */
	public function get_settings_url(): ?string {
		return null;
	}

	/**
	 * Measured values only. Until 2026-09-15 telemetry was the literals ACTIVE, 2 and 2.
	 */
	public function get_telemetry_metrics(): array {
		$shortcodes = 0;
		foreach ( [ 'wm_salon_stuhlmiete', 'wm_salon_inci_checker' ] as $tag ) {
			$shortcodes += function_exists( 'shortcode_exists' ) && shortcode_exists( $tag ) ? 1 : 0;
		}
		return [
			'shortcodes_registered' => $shortcodes,
			'self_test'             => $this->execute_action( 'self_test' )['success'] ? 'PASS' : 'FAIL',
		];
	}

	/**
	 * `self_test` runs both calculators against known values, including a rounding case, a fixed rent above the
	 * turnover and an empty ingredient list. Every other action is unknown and says so.
	 */
	public function execute_action( string $action, array $params = [] ): array {
		if ( 'self_test' !== $action ) {
			return [
				'success' => false,
				/* translators: %s: action name. */
				'message' => sprintf( __( 'Unbekannte Spoke-Aktion "%s".', 'wm-salon-sovereign-hub' ), esc_html( $action ) ),
			];
		}

		try {
			$split     = WM_Salon_Stuhlmiete_Ledger::calculate_split( 1190.00, 0.30, 0.0, 19.0 );
			$rounding  = WM_Salon_Stuhlmiete_Ledger::calculate_split( 0.75, 0.30, 0.0, 19.0 );
			$shortfall = WM_Salon_Stuhlmiete_Ledger::calculate_split( 100.00, 0.30, 130.00, 19.0 );
		} catch ( InvalidArgumentException $e ) {
			$split = $rounding = $shortfall = null; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
		}
		$inci  = WM_Salon_INCI_Analyzer::analyze( 'Aqua, Sodium Laureth Sulfate, Dimethicone, Limonene' );
		$empty = WM_Salon_INCI_Analyzer::analyze( ',' );

		$pass = null !== $split
			&& 1000.00 === $split['net_revenue'] && 357.00 === $split['salon_share_gross']
			&& 0.23 === $rounding['salon_share_gross']
			&& 0.0 === $shortfall['stylist_payout_gross'] && 60.00 === $shortfall['shortfall_gross']
			&& true === $inci['has_sulfates'] && true === $inci['has_silicones'] && [ 'Limonene' ] === $inci['allergens_found']
			&& null === $empty['clean_score'] && [] === $empty['badges'];

		return [
			'success' => $pass,
			'message' => $pass
				? __( 'Stuhlmiete-Rechner und INCI-Prüfer liefern die erwarteten Werte.', 'wm-salon-sovereign-hub' )
				: __( 'Selbsttest fehlgeschlagen: ein Rechner liefert abweichende Werte.', 'wm-salon-sovereign-hub' ),
		];
	}
}
