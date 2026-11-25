<?php
/**
 * WM Salon Sovereign Hub — shortcodes
 *
 * - [wm_salon_stuhlmiete]: booth rent (Stuhlmiete) revenue split calculator.
 * - [wm_salon_inci_checker]: local INCI ingredient check.
 *
 * Colours and dimensions live in assets/css/wm-salon-hub.css, enqueued by the shortcodes themselves; until 2026-09-15
 * the forms carried inline styles whose button text and field borders fell below the contrast minimums.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_Salon_Shortcodes {

	public const STYLE_HANDLE = 'wm-salon-hub';

	public static function init(): void {
		add_shortcode( 'wm_salon_stuhlmiete', [ self::class, 'render_stuhlmiete_calculator' ] );
		add_shortcode( 'wm_salon_inci_checker', [ self::class, 'render_inci_checker' ] );
	}

	/**
	 * The sheet is enqueued only on pages that render a form; WordPress prints a late style in the footer.
	 */
	private static function enqueue_style(): void {
		wp_enqueue_style( self::STYLE_HANDLE, plugins_url( 'assets/css/wm-salon-hub.css', WM_SALON_HUB_FILE ), [], WM_SALON_HUB_VERSION );
	}

	/**
	 * Decorative inline SVG (monochrome, currentColor, hidden from assistive technology).
	 */
	private static function icon( string $name ): string {
		$paths = [
			'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.1 15.9M14.5 14.5 20 20M8.1 8.1 12 12"/>',
			'leaf'     => '<path d="M11 20A7 7 0 0 1 4 13c0-5 4-9 16-9 0 12-4 16-9 16Z"/><path d="M4 20c4-4 7-7 11-11"/>',
			'warning'  => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
		];
		return '<svg class="wm-salon-icon" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
	}

	/**
	 * A posted field as unslashed plain text ('' when absent or not a string). WordPress adds slashes to $_POST, so
	 * quotes in an ingredient list showed up with backslashes until 2026-09-15.
	 */
	private static function posted( string $key, bool $multiline = false ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only calculators, nothing is stored.
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			return '';
		}
		// Unslashed and sanitised in one expression, so the sniff sees the sanitiser on the input.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $multiline ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	}

	private static function money( float $amount ): string {
		return number_format( $amount, 2, ',', '.' ) . ' €';
	}

	/**
	 * Booth-rent split. $atts is not used: it is typed array|string because WordPress before 6.5 passes '' for a
	 * shortcode without attributes, which the former array type turned into a fatal TypeError.
	 *
	 * @param array<string, string>|string $atts
	 */
	public static function render_stuhlmiete_calculator( array|string $atts = [] ): string {
		self::enqueue_style();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only calculator, nothing is stored.
		$submitted = isset( $_POST['wm_salon_calc_submit'] );
		// The form keeps what was entered; until 2026-09-15 it showed the defaults again next to the result.
		$gross = $submitted ? self::posted( 'wm_salon_gross' ) : '500.00';
		$rate  = $submitted ? self::posted( 'wm_salon_rate' ) : '30';
		$fixed = $submitted ? self::posted( 'wm_salon_fixed' ) : '0.00';

		$output  = '<section class="wm-salon-box wm-salon-calc-box" aria-label="' . esc_attr__( 'Stuhlmiete-Rechner', 'wm-salon-sovereign-hub' ) . '">';
		$output .= '<h3 class="wm-salon-title">' . self::icon( 'scissors' ) . ' ' . esc_html__( 'Stuhlmiete-Rechner', 'wm-salon-sovereign-hub' ) . '</h3>';
		$output .= '<p class="wm-salon-intro">' . esc_html__( 'Berechnet die Umsatzaufteilung zwischen Saloninhaber und selbstständigem Stylisten. Der Rechner prüft nicht, ob die Stuhlmiete sozialversicherungsrechtlich als Selbstständigkeit gilt.', 'wm-salon-sovereign-hub' ) . '</p>';
		$output .= '<form method="post" class="wm-salon-form">';
		$output .= '<label class="wm-salon-field">' . esc_html__( 'Bruttoumsatz des Stylisten (€ inkl. MwSt.)', 'wm-salon-sovereign-hub' ) . ' <input type="number" step="0.01" min="0" name="wm_salon_gross" value="' . esc_attr( $gross ) . '" required class="wm-salon-input"></label>';
		$output .= '<label class="wm-salon-field">' . esc_html__( 'Umsatzbeteiligung Salon (%)', 'wm-salon-sovereign-hub' ) . ' <input type="number" step="1" min="0" max="100" name="wm_salon_rate" value="' . esc_attr( $rate ) . '" required class="wm-salon-input"></label>';
		$output .= '<label class="wm-salon-field">' . esc_html__( 'Feste Stuhlmiete im selben Zeitraum (€)', 'wm-salon-sovereign-hub' ) . ' <input type="number" step="0.01" min="0" name="wm_salon_fixed" value="' . esc_attr( $fixed ) . '" class="wm-salon-input"></label>';
		$output .= '<button type="submit" name="wm_salon_calc_submit" value="1" class="wm-salon-button">' . esc_html__( 'Aufteilung berechnen', 'wm-salon-sovereign-hub' ) . '</button>';
		$output .= '</form>';

		if ( $submitted ) {
			$split = null;
			if ( is_numeric( $gross ) && is_numeric( $rate ) && ( '' === $fixed || is_numeric( $fixed ) ) && (float) $rate >= 0 && (float) $rate <= 100 ) {
				try {
					$split = WM_Salon_Stuhlmiete_Ledger::calculate_split( (float) $gross, (float) $rate / 100.0, '' === $fixed ? 0.0 : (float) $fixed );
				} catch ( InvalidArgumentException $e ) {
					$split = null;
				}
			}

			$output .= '<div class="wm-salon-result" role="region" aria-label="' . esc_attr__( 'Ergebnis', 'wm-salon-sovereign-hub' ) . '">';
			if ( null === $split ) {
				$output .= '<p class="wm-salon-error">' . esc_html__( 'Bitte gültige Beträge und eine Beteiligung zwischen 0 und 100 % eingeben.', 'wm-salon-sovereign-hub' ) . '</p>';
			} else {
				$output .= '<h4 class="wm-salon-result-title">' . esc_html__( 'Abrechnungsergebnis', 'wm-salon-sovereign-hub' ) . '</h4>';
				$output .= '<dl class="wm-salon-figures">';
				$output .= '<dt>' . esc_html__( 'Salon-Anteil (brutto)', 'wm-salon-sovereign-hub' ) . '</dt><dd class="is-salon">' . esc_html( self::money( $split['salon_share_gross'] ) ) . '</dd>';
				$output .= '<dt>' . esc_html__( 'Auszahlung Stylist (brutto)', 'wm-salon-sovereign-hub' ) . '</dt><dd class="is-stylist">' . esc_html( self::money( $split['stylist_payout_gross'] ) ) . '</dd>';
				$output .= '<dt>' . esc_html__( 'Enthaltene MwSt. (19 %)', 'wm-salon-sovereign-hub' ) . '</dt><dd>' . esc_html( self::money( $split['vat_amount'] ) ) . '</dd>';
				$output .= '<dt>' . esc_html__( 'Netto-Umsatz', 'wm-salon-sovereign-hub' ) . '</dt><dd>' . esc_html( self::money( $split['net_revenue'] ) ) . '</dd>';
				$output .= '</dl>';
				if ( $split['shortfall_gross'] > 0 ) {
					$output .= '<p class="wm-salon-error">' . self::icon( 'warning' ) . ' ' . esc_html(
						sprintf(
							/* translators: %s: amount in euros. */
							__( 'Der Salon-Anteil übersteigt den Umsatz um %s; die Auszahlung an den Stylisten ist 0.', 'wm-salon-sovereign-hub' ),
							self::money( $split['shortfall_gross'] )
						)
					) . '</p>';
				}
			}
			$output .= '</div>';
		}

		$output .= '</section>';
		return $output;
	}

	/**
	 * Local INCI check.
	 *
	 * @param array<string, string>|string $atts
	 */
	public static function render_inci_checker( array|string $atts = [] ): string {
		self::enqueue_style();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only check, nothing is stored.
		$submitted = isset( $_POST['wm_inci_submit'] );
		$inci_text = $submitted ? self::posted( 'wm_inci_text', true ) : '';

		$output  = '<section class="wm-salon-box wm-salon-inci-box" aria-label="' . esc_attr__( 'INCI-Prüfer', 'wm-salon-sovereign-hub' ) . '">';
		$output .= '<h3 class="wm-salon-title wm-salon-title-inci">' . self::icon( 'leaf' ) . ' ' . esc_html__( 'Lokaler INCI-Inhaltsstoff-Prüfer', 'wm-salon-sovereign-hub' ) . '</h3>';
		$output .= '<p class="wm-salon-intro">' . esc_html__( 'Prüft eine Inhaltsstoffliste auf dieser Website, ohne externe Anfrage, gegen eine feste Stoffliste: 5 Sulfate, 6 Silikone, 5 Parabene und 8 Duftstoff-Allergene (u. a. Limonene, Linalool, Citral). Stoffe außerhalb dieser Liste werden nicht erkannt; ob und ab welcher Konzentration ein Stoff zu deklarieren ist, prüft der Rechner nicht.', 'wm-salon-sovereign-hub' ) . '</p>';
		$output .= '<form method="post" class="wm-salon-form">';
		$output .= '<label class="wm-salon-field">' . esc_html__( 'INCI-Inhaltsstoffe (durch Komma, Semikolon oder Zeilenumbruch getrennt)', 'wm-salon-sovereign-hub' ) . ' <textarea name="wm_inci_text" rows="3" required class="wm-salon-input" placeholder="Aqua, Cetearyl Alcohol, Dimethicone, Limonene, Methylparaben">' . esc_textarea( $inci_text ) . '</textarea></label>';
		$output .= '<button type="submit" name="wm_inci_submit" value="1" class="wm-salon-button wm-salon-button-inci">' . esc_html__( 'INCI analysieren', 'wm-salon-sovereign-hub' ) . '</button>';
		$output .= '</form>';

		if ( $submitted ) {
			$res     = WM_Salon_INCI_Analyzer::analyze( $inci_text );
			$output .= '<div class="wm-salon-result" role="region" aria-label="' . esc_attr__( 'INCI-Ergebnis', 'wm-salon-sovereign-hub' ) . '">';
			if ( null === $res['clean_score'] ) {
				$output .= '<p class="wm-salon-error">' . esc_html__( 'Die Liste enthält keinen Inhaltsstoff; es gibt kein Ergebnis.', 'wm-salon-sovereign-hub' ) . '</p>';
			} else {
				$band    = $res['clean_score'] >= 80 ? 'is-good' : ( $res['clean_score'] >= 50 ? 'is-medium' : 'is-poor' );
				$output .= '<p class="wm-salon-count">' . esc_html(
					sprintf(
						/* translators: %d: number of ingredients recognised in the list. */
						_n( '%d Inhaltsstoff erkannt', '%d Inhaltsstoffe erkannt', (int) $res['total_ingredients'], 'wm-salon-sovereign-hub' ),
						(int) $res['total_ingredients']
					)
				) . '</p>';
				$output .= '<p class="wm-salon-score"><span class="wm-salon-score-label">' . esc_html__( 'Clean-Score', 'wm-salon-sovereign-hub' ) . '</span> <span class="wm-salon-score-value ' . esc_attr( $band ) . '">' . esc_html( (string) $res['clean_score'] ) . ' / 100</span></p>';
				$output .= '<p class="wm-salon-badges">';
				foreach ( $res['badges'] as $badge ) {
					$output .= '<span class="wm-salon-badge">' . esc_html( $badge ) . '</span> ';
				}
				$output .= '</p>';
				if ( ! empty( $res['allergens_found'] ) ) {
					$output .= '<p class="wm-salon-allergens">' . self::icon( 'warning' ) . ' ' . esc_html(
						sprintf(
							/* translators: %s: comma-separated allergen names. */
							__( 'Duftstoff-Allergene aus der Liste: %s', 'wm-salon-sovereign-hub' ),
							implode( ', ', $res['allergens_found'] )
						)
					) . '</p>';
				}
			}
			$output .= '</div>';
		}

		$output .= '</section>';
		return $output;
	}
}
