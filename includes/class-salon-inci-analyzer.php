<?php
/**
 * WM Salon Sovereign Hub — local INCI ingredient check
 *
 * Matches an ingredient list against four fixed lists (sulfates, silicones, parabens, fragrance allergens) on the site
 * itself. It is an orientation aid: an ingredient outside the lists is not recognised, and no product is assessed under
 * any regulation.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_Salon_INCI_Analyzer {

	private const SULFATES = [
		'sodium lauryl sulfate',
		'sodium laureth sulfate',
		'ammonium lauryl sulfate',
		'ammonium laureth sulfate',
		'sodium coco-sulfate',
	];

	private const SILICONES = [
		'dimethicone',
		'cyclopentasiloxane',
		'amodimethicone',
		'cyclomethicone',
		'dimethiconol',
		'phenyl trimethicone',
	];

	private const PARABENS = [
		'methylparaben',
		'ethylparaben',
		'propylparaben',
		'butylparaben',
		'isobutylparaben',
	];

	/**
	 * Eight fragrance allergens. Which of them must be declared on a product, and above which concentration, is not
	 * checked here.
	 */
	private const FRAGRANCE_ALLERGENS = [
		'limonene',
		'linalool',
		'citral',
		'geraniol',
		'coumarin',
		'citronellol',
		'benzyl alcohol',
		'eugenol',
	];

	/**
	 * Return the four substance lists after applying `wm_salon_inci_database`.
	 *
	 * @return array{
	 *     sulfates: list<string>,
	 *     silicones: list<string>,
	 *     parabens: list<string>,
	 *     fragrance_allergens: list<string>
	 * }
	 */
	public static function get_database(): array {
		$defaults = [
			'sulfates'            => self::SULFATES,
			'silicones'           => self::SILICONES,
			'parabens'            => self::PARABENS,
			'fragrance_allergens' => self::FRAGRANCE_ALLERGENS,
		];

		if ( ! function_exists( 'apply_filters' ) ) {
			return $defaults;
		}

		$filtered = apply_filters( 'wm_salon_inci_database', $defaults );
		if ( ! is_array( $filtered ) ) {
			return $defaults;
		}

		foreach ( $defaults as $group => $list ) {
			if ( isset( $filtered[ $group ] ) && is_array( $filtered[ $group ] ) ) {
				$defaults[ $group ] = array_values(
					array_filter(
						array_map(
							static fn( mixed $v ): string => is_string( $v ) ? strtolower( trim( $v ) ) : '',
							$filtered[ $group ]
						),
						static fn( string $v ): bool => '' !== $v
					)
				);
			}
		}

		return $defaults;
	}

	/**
	 * Split an ingredient list into ingredients. Separators are commas, semicolons and line breaks; a comma inside
	 * parentheses or between two digits belongs to the ingredient ("Parfum (Limonene, Linalool)", "1,2-Hexanediol").
	 * Until 2026-09-15 the list was split on every comma only, so a list with one ingredient per line counted as one
	 * ingredient and "1,2-Hexanediol" as two.
	 *
	 * @return list<string>
	 */
	public static function split_ingredients( string $inci_string ): array {
		$items   = [];
		$current = '';
		$depth   = 0;
		$length  = strlen( $inci_string );

		for ( $i = 0; $i < $length; $i++ ) {
			$ch = $inci_string[ $i ];
			if ( '(' === $ch || '[' === $ch ) {
				++$depth;
			} elseif ( ( ')' === $ch || ']' === $ch ) && $depth > 0 ) {
				--$depth;
			}

			$between_digits = ',' === $ch && $i > 0 && $i + 1 < $length && ctype_digit( $inci_string[ $i - 1 ] ) && ctype_digit( $inci_string[ $i + 1 ] );
			$separator      = ( ( ',' === $ch && ! $between_digits ) || ';' === $ch ) && 0 === $depth;
			if ( $separator || "\n" === $ch || "\r" === $ch ) {
				$items[] = $current;
				$current = '';
				continue;
			}
			$current .= $ch;
		}
		$items[] = $current;

		return array_values( array_filter( array_map( 'trim', $items ), static fn( string $item ): bool => '' !== $item ) );
	}

	/**
	 * Analyze an INCI ingredient list.
	 *
	 * A list without any ingredient gets no verdict: the findings and the score are null and there is no badge. Until
	 * 2026-09-15 an empty list or a lone comma scored 100/100 with every badge, CLEAN_BEAUTY included.
	 *
	 * @param string $inci_string Ingredient names separated by commas, semicolons or line breaks.
	 * @return array{
	 *     total_ingredients: int,
	 *     has_sulfates: bool|null,
	 *     has_silicones: bool|null,
	 *     has_parabens: bool|null,
	 *     allergens_found: list<string>,
	 *     clean_score: int|null,
	 *     badges: list<string>
	 * }
	 */
	public static function analyze( string $inci_string ): array {
		$ingredients = array_map( 'strtolower', self::split_ingredients( $inci_string ) );

		if ( [] === $ingredients ) {
			return [
				'total_ingredients' => 0,
				'has_sulfates'      => null,
				'has_silicones'     => null,
				'has_parabens'      => null,
				'allergens_found'   => [],
				'clean_score'       => null,
				'badges'            => [],
			];
		}

		$db            = self::get_database();
		$has_sulfates  = self::contains_any( $ingredients, $db['sulfates'] );
		$has_silicones = self::contains_any( $ingredients, $db['silicones'] );
		$has_parabens  = self::contains_any( $ingredients, $db['parabens'] );

		// One entry per allergen found, named as in the list: five allergens inside one ingredient count five times,
		// as five separate ingredients do (until 2026-09-15 the deduction counted ingredient strings).
		$allergens = [];
		foreach ( $db['fragrance_allergens'] as $allergen ) {
			if ( self::contains_any( $ingredients, [ $allergen ] ) ) {
				$allergens[] = ucwords( $allergen );
			}
		}

		// Clean score: 100 base, deductions for the three groups and up to 10 for allergens.
		$score = 100;
		if ( $has_sulfates ) {
			$score -= 30;
		}
		if ( $has_silicones ) {
			$score -= 20;
		}
		if ( $has_parabens ) {
			$score -= 40;
		}
		$score -= min( 10, count( $allergens ) * 2 );
		$score  = max( 0, $score );

		$badges = [];
		if ( ! $has_sulfates ) {
			$badges[] = 'SULFATFREI';
		}
		if ( ! $has_silicones ) {
			$badges[] = 'SILIKONFREI';
		}
		if ( ! $has_parabens ) {
			$badges[] = 'PARABENFREI';
		}
		if ( $score >= 80 ) {
			$badges[] = 'CLEAN_BEAUTY';
		}

		$result = [
			'total_ingredients' => count( $ingredients ),
			'has_sulfates'      => $has_sulfates,
			'has_silicones'     => $has_silicones,
			'has_parabens'      => $has_parabens,
			'allergens_found'   => $allergens,
			'clean_score'       => $score,
			'badges'            => $badges,
		];

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'wm_salon_inci_result', $result, $inci_string );
			if ( is_array( $filtered ) ) {
				$result = $filtered;
			}
		}

		return $result;
	}

	/**
	 * Whether any ingredient contains any of the names as a substring.
	 *
	 * @param list<string> $ingredients
	 * @param list<string> $names
	 */
	private static function contains_any( array $ingredients, array $names ): bool {
		foreach ( $ingredients as $item ) {
			foreach ( $names as $name ) {
				if ( str_contains( $item, $name ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
