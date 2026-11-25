<?php
/**
 * Literal mutations for tests/run-mutations.sh: each entry undoes one correction of 2026-09-14/15; the
 * standalone suite must report a failure for every one. Search strings must occur exactly once.
 *
 * @package WM_Salon_Sovereign_Hub
 */

return [
	[
		'Salon share truncated instead of rounded half up',
		[
			[ 'includes/class-salon-stuhlmiete-ledger.php', '$salon_cents      = self::div_half_up( $gross_cents * $rate_bp, 10000 ) + $fixed_cents;', '$salon_cents      = intdiv( $gross_cents * $rate_bp, 10000 ) + $fixed_cents;' ],
		],
	],
	[
		'Amounts scaled by a plain cast (0.29 becomes 28 cents)',
		[
			[ 'includes/class-salon-stuhlmiete-ledger.php', 'return (int) round( round( $value * $factor, 6 ) );', 'return (int) ( $value * $factor );' ],
		],
	],
	[
		'Shortfall always 0',
		[
			[ 'includes/class-salon-stuhlmiete-ledger.php', '\'shortfall_gross\'      => max( 0, -$remaining_cents ) / 100.0,', '\'shortfall_gross\'      => 0.0,' ],
		],
	],
	[
		'Infinite and NaN values not refused',
		[
			[ 'includes/class-salon-stuhlmiete-ledger.php', 'if ( ! is_finite( $value ) ) {', 'if ( false ) {' ],
		],
	],
	[
		'Amounts above the limit accepted',
		[
			[ 'includes/class-salon-stuhlmiete-ledger.php', 'if ( $gross_revenue > self::MAX_AMOUNT || $fixed_chair_rent > self::MAX_AMOUNT ) {', 'if ( false ) {' ],
		],
	],
	[
		'VAT rate not clamped (division by zero)',
		[
			[ 'includes/class-salon-stuhlmiete-ledger.php', '$vat_bp  = self::scaled( max( 0.0, min( 100.0, $vat_rate ) ), 100 );', '$vat_bp  = (int) round( $vat_rate * 100 );' ],
		],
	],
	[
		'An empty list scores 100 with every badge again',
		[
			[ 'includes/class-salon-inci-analyzer.php', 'if ( [] === $ingredients ) {', 'if ( false ) {' ],
		],
	],
	[
		'Line breaks not separators',
		[
			[ 'includes/class-salon-inci-analyzer.php', 'if ( $separator || "\\n" === $ch || "\\r" === $ch ) {', 'if ( $separator ) {' ],
		],
	],
	[
		'Comma between digits splits 1,2-Hexanediol',
		[
			[ 'includes/class-salon-inci-analyzer.php', '$separator      = ( ( \',\' === $ch && ! $between_digits ) || \';\' === $ch ) && 0 === $depth;', '$separator      = ( \',\' === $ch || \';\' === $ch ) && 0 === $depth;' ],
		],
	],
	[
		'Commas inside parentheses split the ingredient',
		[
			[ 'includes/class-salon-inci-analyzer.php', '&& 0 === $depth;', '&& true;' ],
		],
	],
	[
		'Semicolon not a separator',
		[
			[ 'includes/class-salon-inci-analyzer.php', ' || \';\' === $ch ) && 0 === $depth;', ' ) && 0 === $depth;' ],
		],
	],
	[
		'Allergens counted per ingredient string again',
		[
			[ 'includes/class-salon-inci-analyzer.php', "foreach ( \$db['fragrance_allergens'] as \$allergen ) {\n\t\t\tif ( self::contains_any( \$ingredients, [ \$allergen ] ) ) {\n\t\t\t\t\$allergens[] = ucwords( \$allergen );", "foreach ( \$ingredients as \$allergen ) {\n\t\t\tif ( self::contains_any( [ \$allergen ], \$db['fragrance_allergens'] ) ) {\n\t\t\t\t\$allergens[] = ucwords( \$allergen );" ],
		],
	],
	[
		'gross_revenue optional in the route schema',
		[
			[ 'includes/class-salon-rest-controller.php', '+ ( null === $default ? [ \'required\' => true ] : [ \'default\' => $default ] );', '+ ( null === $default ? [ \'default\' => 0.0 ] : [ \'default\' => $default ] );' ],
		],
	],
	[
		'rent_rate accepts a percentage again',
		[
			[ 'includes/class-salon-rest-controller.php', '\'rent_rate\'     => [ 0.30, 0.0, 1.0 ],', '\'rent_rate\'     => [ 0.30, 0.0, 100.0 ],' ],
		],
	],
	[
		'inci not required',
		[
			[ 'includes/class-salon-rest-controller.php', '\'required\' => true,', '\'required\' => false,' ],
		],
	],
	[
		'Split handler trusts core validation for ranges',
		[
			[ 'includes/class-salon-rest-controller.php', 'if ( null === $number || ! is_finite( $number ) || $number < $min || $number > $max ) {', 'if ( null === $number ) {' ],
		],
	],
	[
		'An empty body reaches the calculation',
		[
			[ 'includes/class-salon-rest-controller.php', 'if ( null === $default ) {', 'if ( false ) {' ],
		],
	],
	[
		'Array as inci analysed as text',
		[
			[ 'includes/class-salon-rest-controller.php', 'if ( ! is_string( $raw ) ) {', 'if ( false ) {' ],
		],
	],
	[
		'A list without ingredients answers 200',
		[
			[ 'includes/class-salon-rest-controller.php', 'if ( 0 === $analysis[\'total_ingredients\'] ) {', 'if ( false ) {' ],
		],
	],
	[
		'Route list sanitised as one line (line breaks collapsed)',
		[
			[ 'includes/class-salon-rest-controller.php', 'WM_Salon_INCI_Analyzer::analyze( sanitize_textarea_field( $raw ) );', 'WM_Salon_INCI_Analyzer::analyze( sanitize_text_field( $raw ) );' ],
		],
	],
	[
		'Calculator typed array again (TypeError on WordPress 6.4)',
		[
			[ 'includes/class-salon-shortcodes.php', 'public static function render_stuhlmiete_calculator( array|string $atts = [] ): string {', 'public static function render_stuhlmiete_calculator( array $atts = [] ): string {' ],
		],
	],
	[
		'INCI checker typed array again (TypeError on WordPress 6.4)',
		[
			[ 'includes/class-salon-shortcodes.php', 'public static function render_inci_checker( array|string $atts = [] ): string {', 'public static function render_inci_checker( array $atts = [] ): string {' ],
		],
	],
	[
		'Stylesheet not enqueued',
		[
			[ 'includes/class-salon-shortcodes.php', 'wp_enqueue_style( self::STYLE_HANDLE, plugins_url(', 'if ( false ) wp_enqueue_style( self::STYLE_HANDLE, plugins_url(' ],
		],
	],
	[
		'Inline style on the box again',
		[
			[ 'includes/class-salon-shortcodes.php', '\'<section class="wm-salon-box wm-salon-calc-box"', '\'<section style="padding:24px" class="wm-salon-box wm-salon-calc-box"' ],
		],
	],
	[
		'POST values not unslashed',
		[
			[ 'includes/class-salon-shortcodes.php', '? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) :', '? sanitize_textarea_field( $_POST[ $key ] ) :' ],
		],
	],
	[
		'Calculator shows the default amount instead of the submitted one',
		[
			[ 'includes/class-salon-shortcodes.php', 'name="wm_salon_gross" value="\' . esc_attr( $gross ) . \'"', 'name="wm_salon_gross" value="500.00"' ],
		],
	],
	[
		'Share outside 0-100 % computed',
		[
			[ 'includes/class-salon-shortcodes.php', ' && (float) $rate >= 0 && (float) $rate <= 100', '' ],
		],
	],
	[
		'Non-numeric input computed as 0',
		[
			[ 'includes/class-salon-shortcodes.php', 'if ( is_numeric( $gross ) && is_numeric( $rate ) && ( \'\' === $fixed || is_numeric( $fixed ) )', 'if ( ( \'\' === $fixed || is_numeric( $fixed ) )' ],
		],
	],
	[
		'Shortfall message removed',
		[
			[ 'includes/class-salon-shortcodes.php', 'if ( $split[\'shortfall_gross\'] > 0 ) {', 'if ( false ) {' ],
		],
	],
	[
		'Empty INCI list scored in the form',
		[
			[ 'includes/class-salon-shortcodes.php', 'if ( null === $res[\'clean_score\'] ) {', 'if ( false ) {' ],
		],
	],
	[
		'Form list sanitised as one line (line breaks collapsed)',
		[
			[ 'includes/class-salon-shortcodes.php', 'self::posted( \'wm_inci_text\', true )', 'self::posted( \'wm_inci_text\' )' ],
		],
	],
	[
		'Emoji instead of the SVG icon',
		[
			[ 'includes/class-salon-shortcodes.php', 'self::icon( \'scissors\' ) . \' \' . esc_html__( \'Stuhlmiete-Rechner\'', '\'✂ \' . esc_html__( \'Stuhlmiete-Rechner\'' ],
		],
	],
	[
		'INCI intro claims declarable allergens again',
		[
			[ 'includes/class-salon-shortcodes.php', '8 Duftstoff-Allergene (u. a. Limonene, Linalool, Citral).', '8 deklarationspflichtige Duftstoff-Allergene (u. a. Limonene, Linalool, Citral).' ],
		],
	],
	[
		'Hex colour in a rule',
		[
			[ 'assets/css/wm-salon-hub.css', "\tbackground: var(--salon-accent);\n\tcolor: var(--salon-accent-contrast);", "\tbackground: #b45309;\n\tcolor: var(--salon-accent-contrast);" ],
		],
	],
	[
		'Calculator button colour of 2026-09-14 (3.19:1)',
		[
			[ 'assets/css/wm-salon-hub.css', '--salon-accent: #b45309;', '--salon-accent: #d97706;' ],
		],
	],
	[
		'INCI button colour of 2026-09-14 (3.77:1)',
		[
			[ 'assets/css/wm-salon-hub.css', '--salon-accent-inci: #047857;', '--salon-accent-inci: #059669;' ],
		],
	],
	[
		'Field border of 2026-09-14 (2.36:1)',
		[
			[ 'assets/css/wm-salon-hub.css', '--salon-field-border: #94a3b8;', '--salon-field-border: #475569;' ],
		],
	],
	[
		'Targets of 36px',
		[
			[ 'assets/css/wm-salon-hub.css', '--salon-target: 48px;', '--salon-target: 36px;' ],
		],
	],
	[
		'Health literal HEALTHY again',
		[
			[ 'includes/class-salon-spoke-adapter.php', 'return true === ( $self_test[\'success\'] ?? false ) ? \'HEALTHY\' : \'ERROR\';', 'return \'HEALTHY\';' ],
		],
	],
	[
		'Telemetry shortcode count literal 2 again',
		[
			[ 'includes/class-salon-spoke-adapter.php', '\'shortcodes_registered\' => $shortcodes,', '\'shortcodes_registered\' => 2,' ],
		],
	],
	[
		'Telemetry literal status ACTIVE again',
		[
			[ 'includes/class-salon-spoke-adapter.php', "\t\treturn [\n\t\t\t'shortcodes_registered'", "\t\treturn [\n\t\t\t'status'                => 'ACTIVE',\n\t\t\t'shortcodes_registered'" ],
		],
	],
	[
		'Unknown actions confirmed again',
		[
			[ 'includes/class-salon-spoke-adapter.php', '\'success\' => false,', '\'success\' => true,' ],
		],
	],
	[
		'Settings URL points at a page nothing registers',
		[
			[ 'includes/class-salon-spoke-adapter.php', 'return null;', 'return \'admin.php?page=wm-salon-hub\';' ],
		],
	],
	[
		'SBOM hash not of the main file',
		[
			[ 'includes/class-salon-spoke-adapter.php', '$main = dirname( __DIR__ ) . \'/wm-salon-sovereign-hub.php\';', '$main = __FILE__;' ],
		],
	],
	[
		'analyze-inci not registered under its documented name',
		[
			[ 'includes/class-salon-cli.php', '@subcommand analyze-inci', '@subcommand analyze_inci' ],
		],
	],
	[
		'calculate-rent not registered under its documented name',
		[
			[ 'includes/class-salon-cli.php', '@subcommand calculate-rent', '@subcommand calculate_rent' ],
		],
	],
	[
		'--vat ignored (19 % always)',
		[
			[ 'includes/class-salon-cli.php', '$values[\'fixed\'], $values[\'vat\'] );', '$values[\'fixed\'], 19.0 );' ],
		],
	],
	[
		'Net turnover labelled as including VAT again',
		[
			[ 'includes/class-salon-cli.php', 'WP_CLI::line( sprintf( \'Nettoumsatz:           %0.2f EUR\', $split[\'net_revenue\'] ) );', 'WP_CLI::line( sprintf( \'Nettoumsatz:           %0.2f EUR (inkl. MwSt)\', $split[\'net_revenue\'] ) );' ],
		],
	],
	[
		'Shortfall warning missing in the command',
		[
			[ 'includes/class-salon-cli.php', 'if ( $split[\'shortfall_gross\'] > 0 ) {', 'if ( false ) {' ],
		],
	],
	[
		'Empty list analysed in the command',
		[
			[ 'includes/class-salon-cli.php', 'if ( null === $res[\'clean_score\'] ) {', 'if ( false ) {' ],
		],
	],
	[
		'--revenue optional',
		[
			[ 'includes/class-salon-cli.php', 'if ( null === $raw || ! is_numeric( $raw ) ) {', 'if ( null !== $raw && ! is_numeric( $raw ) ) {' ],
		],
	],
	[
		'Text domain not loaded',
		[
			[ 'wm-salon-sovereign-hub.php', 'load_plugin_textdomain( \'wm-salon-sovereign-hub\', false, dirname( plugin_basename( WM_SALON_HUB_FILE ) ) . \'/languages\' );', '// text domain not loaded' ],
		],
	],
	[
		'Uninstall deletes data without explicit opt-in',
		[
			[ 'uninstall.php', 'if ( ! filter_var( get_option( \'wm_salon_delete_data_on_uninstall\', false ), FILTER_VALIDATE_BOOLEAN ) ) {', 'if ( false ) {' ],
		],
	],
	[
		'REST IP-HMAC rate-limit threshold ignored',
		[
			[ 'includes/class-salon-rest-controller.php', 'if ( $count >= $limit ) {', 'if ( false ) {' ],
		],
	],
	[
		'Oversized INCI payload accepted by REST handler',
		[
			[ 'includes/class-salon-rest-controller.php', 'if ( strlen( $raw ) > self::MAX_INCI_BYTES ) {', 'if ( false ) {' ],
		],
	],
	[
		'Opt-in uninstall setting defaults to true instead of false',
		[
			[ 'includes/class-salon-integrations.php', '\'default\'           => false,', '\'default\'           => true,' ],
		],
	],
	[
		'Block Bindings source wm-salon/metrics not registered',
		[
			[ 'includes/class-salon-integrations.php', 'if ( ! function_exists( \'register_block_bindings_source\' ) ) {', 'if ( true ) {' ],
		],
	],
	[
		'GenerateBlocks dynamic tag wm_salon_metric not registered',
		[
			[ 'includes/class-salon-integrations.php', 'if ( self::$gb_registered || ! class_exists( \'GenerateBlocks_Register_Dynamic_Tag\' ) ) {', 'if ( true ) {' ],
		],
	],
	[
		'INCI database filter wm_salon_inci_database ignored',
		[
			[ 'includes/class-salon-inci-analyzer.php', '$filtered = apply_filters( \'wm_salon_inci_database\', $defaults );', '$filtered = null;' ],
		],
	],
	[
		'Stuhlmiete calculation filter wm_salon_stuhlmiete_calculation ignored',
		[
			[ 'includes/class-salon-stuhlmiete-ledger.php', '\'wm_salon_stuhlmiete_calculation\',', '\'wm_salon_disabled_filter\',' ],
		],
	],
	[
		'Template without a string of the code',
		[
			[ 'languages/wm-salon-sovereign-hub.pot', 'msgid "Aufteilung berechnen"', 'msgid "Rechnen"' ],
		],
	],
	[
		'de_DE catalog double-encoded again',
		[
			[ 'languages/wm-salon-sovereign-hub-de_DE.po', 'msgid "INCI-Prüfer"', 'msgid "INCI-PrÃ¼fer"' ],
		],
	],
	[
		'README embeds the marketing banner again',
		[
			[ 'README.md', "## Images\n\n", "## Images\n\n![WM Salon Sovereign Hub banner](assets/images/salon-hub-banner.jpg)\n\n" ],
		],
	],
	[
		'German README says deklarationspflichtig again',
		[
			[ 'README.de.md', '8 Duftstoff-Allergene', '8 deklarationspflichtige Duftstoff-Allergene' ],
		],
	],
	[
		'humans.txt says Founder & CEO again',
		[
			[ 'public/humans.txt', 'Author: Arnold Wender', 'Author: Arnold Wender -- Founder & CEO' ],
		],
	],
	[
		'SECURITY.md promises a response time again',
		[
			[ 'SECURITY.md', 'Reports are acknowledged and handled as quickly as possible; no response time is guaranteed.', '- **Acknowledgement:** Within 24 hours.' ],
		],
	],
	[
		'AGENTS.md diverges from CLAUDE.md',
		[
			[ 'AGENTS.md', 'There is no `/src/`.', 'Core business logic is organized under `/src/` and `/includes/`.' ],
		],
	],
	[
		'readme.txt short description over 150 characters',
		[
			[ 'readme.txt', 'and an on-site INCI ingredient checker for cosmetic products.', 'and an on-site INCI ingredient checker for cosmetic products and fragrance allergens.' ],
		],
	],
	[
		'Marketing images in the package again',
		[
			[ '.distignore', "\nassets/images/\n", "\nassets/imgs-kept/\n" ],
		],
	],
	[
		'llms.txt claims PSR-12 again',
		[
			[ 'llms.txt', '- PHP 8.1+ with strict types; WordPress coding style', '- PHP 8.1+ / Strict Types / PSR-12 / WPCS' ],
		],
	],
	[
		'CONTRIBUTING.md claims legal compliance again',
		[
			[ 'CONTRIBUTING.md', 'part of the WM Suite plugin suite.', 'which adheres to high standards of security, accessibility, and legal compliance.' ],
		],
	],
	[
		'CHANGELOG heading with an emoji again',
		[
			[ 'CHANGELOG.md', '### Fixed (2026-09-15)', '### 🐛 Fixed (2026-09-15)' ],
		],
	],
	[
		'CI without the mutation check',
		[
			[ '.github/workflows/ci.yml', 'run: bash tests/run-mutations.sh', 'run: echo skipped' ],
		],
	],
];
