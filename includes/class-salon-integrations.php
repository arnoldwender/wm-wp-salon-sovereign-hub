<?php
/**
 * WM Salon Sovereign Hub — WordPress & GenerateBlocks ecosystem bridges + opt-in uninstall setting
 *
 * - Opt-in uninstall setting (`wm_salon_delete_data_on_uninstall`, default `false`) on Settings -> General.
 * - WordPress 6.5+ Block Bindings source `wm-salon/metrics`.
 * - GenerateBlocks 2.x Dynamic Tag `{{wm_salon_metric}}`.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_Salon_Integrations {

	public const UNINSTALL_OPTION     = 'wm_salon_delete_data_on_uninstall';
	public const BLOCK_BINDING_SOURCE = 'wm-salon/metrics';
	public const DYNAMIC_TAG_NAME     = 'wm_salon_metric';

	private static bool $gb_registered = false;

	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'init', [ self::class, 'register_block_bindings' ], 20 );
		add_action( 'init', [ self::class, 'register_generateblocks_tags' ], 20 );
		add_action( 'generateblocks_after_init', [ self::class, 'register_generateblocks_tags' ] );
		add_filter( 'generateblocks_dynamic_tag_replacement', [ self::class, 'filter_generateblocks_output' ], 10, 2 );
	}

	/**
	 * Normalize mixed truthy/falsy values to a boolean.
	 */
	public static function sanitize_boolean( mixed $value ): bool {
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Register opt-in data deletion setting (`D-UNINSTALL`, default false) and expose checkbox on General Settings.
	 */
	public static function register_settings(): void {
		if ( function_exists( 'register_setting' ) ) {
			register_setting(
				'general',
				self::UNINSTALL_OPTION,
				[
					'type'              => 'boolean',
					'description'       => __( 'Plugin-Optionen und Rate-Limit-Transients beim Deinstallieren löschen.', 'wm-salon-sovereign-hub' ),
					'sanitize_callback' => [ self::class, 'sanitize_boolean' ],
					'default'           => false,
					'show_in_rest'      => false,
				]
			);
		}

		if ( function_exists( 'add_settings_field' ) ) {
			add_settings_field(
				self::UNINSTALL_OPTION,
				__( 'WM Salon Sovereign Hub — Deinstallation', 'wm-salon-sovereign-hub' ),
				[ self::class, 'render_uninstall_field' ],
				'general'
			);
		}
	}

	/**
	 * Render the opt-in uninstall checkbox on Settings -> General.
	 */
	public static function render_uninstall_field(): void {
		$raw     = function_exists( 'get_option' ) ? get_option( self::UNINSTALL_OPTION, false ) : false;
		$enabled = self::sanitize_boolean( $raw );
		echo '<label for="wm_salon_delete_data_on_uninstall">';
		echo '<input type="checkbox" id="wm_salon_delete_data_on_uninstall" name="' . esc_attr( self::UNINSTALL_OPTION ) . '" value="1"' . ( $enabled ? ' checked="checked"' : '' ) . ' /> ';
		echo esc_html__( 'Optionen und Rate-Limit-Transients beim Deinstallieren des Plugins endgültig löschen (standardmäßig deaktiviert).', 'wm-salon-sovereign-hub' );
		echo '</label>';
	}

	/**
	 * Register WP 6.5+ Block Bindings source `wm-salon/metrics`.
	 */
	public static function register_block_bindings(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}
		if ( function_exists( 'get_block_bindings_source' ) && null !== get_block_bindings_source( self::BLOCK_BINDING_SOURCE ) ) {
			return;
		}

		register_block_bindings_source(
			'wm-salon/metrics',
			[
				'label'              => __( 'WM Salon Kennzahlen', 'wm-salon-sovereign-hub' ),
				'get_value_callback' => [ self::class, 'get_block_binding_value' ],
				'uses_context'       => [ 'postId', 'postType' ],
			]
		);
	}

	/**
	 * Block Bindings value callback for `wm-salon/metrics`.
	 *
	 * @param array<string, mixed> $source_args    Binding arguments (`key`, optional `gross`, `rate`, `fixed`, `vat`).
	 * @param mixed                $block_instance Block instance.
	 * @param string               $attribute_name Bound attribute name.
	 */
	public static function get_block_binding_value( array $source_args, mixed $block_instance = null, string $attribute_name = '' ): ?string {
		$key = isset( $source_args['key'] ) && is_string( $source_args['key'] )
			? self::sanitize_metric_key( $source_args['key'] )
			: '';
		if ( '' === $key ) {
			return null;
		}
		return self::resolve_metric( $key, $source_args );
	}

	/**
	 * Register GenerateBlocks 2.x Dynamic Tag `{{wm_salon_metric}}`.
	 */
	public static function register_generateblocks_tags(): void {
		if ( self::$gb_registered || ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			return;
		}
		self::$gb_registered = true;

		new \GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'    => __( 'WM Salon Kennzahl', 'wm-salon-sovereign-hub' ),
				'tag'      => self::DYNAMIC_TAG_NAME,
				'type'     => 'wm-salon',
				'supports' => [],
				'options'  => [
					'key'   => [
						'type'  => 'text',
						'label' => __( 'Kennzahl-Schlüssel', 'wm-salon-sovereign-hub' ),
					],
					'gross' => [
						'type'  => 'text',
						'label' => __( 'Bruttoumsatz (€)', 'wm-salon-sovereign-hub' ),
					],
					'rate'  => [
						'type'  => 'text',
						'label' => __( 'Salon-Anteil (0–1)', 'wm-salon-sovereign-hub' ),
					],
					'fixed' => [
						'type'  => 'text',
						'label' => __( 'Feste Miete (€)', 'wm-salon-sovereign-hub' ),
					],
				],
				'return'   => [ self::class, 'render_generateblocks_tag' ],
			]
		);
	}

	/**
	 * Reset registration state (used in standalone tests).
	 */
	public static function reset_for_tests(): void {
		self::$gb_registered = false;
	}

	/**
	 * Render callback for `{{wm_salon_metric}}`.
	 *
	 * @param array<string, mixed> $options Tag options.
	 * @param array<string, mixed> $block   Block array.
	 * @param mixed                $instance Block instance.
	 */
	public static function render_generateblocks_tag( array $options = [], array $block = [], mixed $instance = null ): string {
		$key = isset( $options['key'] ) && is_string( $options['key'] )
			? self::sanitize_metric_key( $options['key'] )
			: 'version';
		$val = self::resolve_metric( $key, $options );
		return null === $val ? '' : esc_html( $val );
	}

	/**
	 * Pass-through filter for GenerateBlocks dynamic tag output when `wm_salon_metric` is evaluated.
	 *
	 * @param mixed $output      Rendered tag output.
	 * @param mixed $tag_details Parsed tag details.
	 */
	public static function filter_generateblocks_output( mixed $output, mixed $tag_details = null ): mixed {
		if ( is_array( $tag_details ) && self::DYNAMIC_TAG_NAME === ( $tag_details['tag'] ?? '' ) && ( '' === $output || null === $output ) ) {
			$opts = isset( $tag_details['options'] ) && is_array( $tag_details['options'] ) ? $tag_details['options'] : [];
			return self::render_generateblocks_tag( $opts );
		}
		return $output;
	}

	/**
	 * Compute a dynamic salon metric for Block Bindings or GenerateBlocks.
	 *
	 * @param string               $key     Metric identifier.
	 * @param array<string, mixed> $options Optional calculation inputs (`gross`, `rate`, `fixed`, `vat`).
	 */
	public static function resolve_metric( string $key, array $options = [] ): ?string {
		$db    = WM_Salon_INCI_Analyzer::get_database();
		$value = null;

		switch ( $key ) {
			case 'version':
				$value = defined( 'WM_SALON_HUB_VERSION' ) ? WM_SALON_HUB_VERSION : '1.2.1';
				break;
			case 'default_vat_rate':
				$value = '19 %';
				break;
			case 'default_salon_rate':
				$value = '30 %';
				break;
			case 'sulfates_count':
				$value = (string) count( $db['sulfates'] );
				break;
			case 'silicones_count':
				$value = (string) count( $db['silicones'] );
				break;
			case 'parabens_count':
				$value = (string) count( $db['parabens'] );
				break;
			case 'allergens_count':
				$value = (string) count( $db['fragrance_allergens'] );
				break;
			case 'substances_count':
				$value = (string) ( count( $db['sulfates'] ) + count( $db['silicones'] ) + count( $db['parabens'] ) + count( $db['fragrance_allergens'] ) );
				break;
			case 'gross_revenue':
			case 'net_revenue':
			case 'vat_amount':
			case 'salon_share_gross':
			case 'stylist_payout_gross':
			case 'stylist_payout_net':
			case 'shortfall_gross':
				$gross = isset( $options['gross'] ) && is_numeric( $options['gross'] ) ? (float) $options['gross'] : 1190.0;
				$rate  = isset( $options['rate'] ) && is_numeric( $options['rate'] ) ? (float) $options['rate'] : 0.30;
				$fixed = isset( $options['fixed'] ) && is_numeric( $options['fixed'] ) ? (float) $options['fixed'] : 0.0;
				$vat   = isset( $options['vat'] ) && is_numeric( $options['vat'] ) ? (float) $options['vat'] : 19.0;
				try {
					$split = WM_Salon_Stuhlmiete_Ledger::calculate_split( $gross, $rate, $fixed, $vat );
					if ( isset( $split[ $key ] ) && is_numeric( $split[ $key ] ) ) {
						$value = number_format( (float) $split[ $key ], 2, ',', '.' ) . ' €';
					}
				} catch ( InvalidArgumentException $e ) {
					$value = null;
				}
				break;
		}

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'wm_salon_metric_value', $value, $key, $options );
			$value    = null === $filtered ? null : (string) $filtered;
		}

		return $value;
	}

	private static function sanitize_metric_key( string $raw ): string {
		return (string) preg_replace( '/[^a-z0-9_]/', '', strtolower( trim( $raw ) ) );
	}
}
