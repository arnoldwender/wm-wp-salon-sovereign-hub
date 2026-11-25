# WM Salon Sovereign Hub — Developer Manual

> Measured against version `1.1.2` in this repository.

---

## 1. Layout

| Path | Role |
| --- | --- |
| `wm-salon-sovereign-hub.php` | Header, constants (`WM_SALON_HUB_VERSION` / `_FILE` / `_PATH`), `require_once` of the five core classes, `WM_Salon_Shortcodes::init()`, `WM_Salon_REST_Controller::init()`, `WM_Salon_Integrations::init()`, the CLI file when `WP_CLI` is defined, `load_plugin_textdomain()` and `do_action( 'wm_salon_ready' )` on `init`, hub registration on `plugins_loaded` (20). |
| `includes/class-salon-stuhlmiete-ledger.php` | `calculate_split()` in whole cents with filter `wm_salon_stuhlmiete_calculation`, `MAX_AMOUNT`, `sanitize_formula()`. |
| `includes/class-salon-inci-analyzer.php` | `get_database()` (filter `wm_salon_inci_database`), `split_ingredients()`, `analyze()` (filter `wm_salon_inci_result`) and the four default substance lists. |
| `includes/class-salon-shortcodes.php` | `[wm_salon_stuhlmiete]` and `[wm_salon_inci_checker]`, `STYLE_HANDLE`, `enqueue_style()`, `icon()` (inline SVG), `posted()` (unslashed, sanitised POST field), `money()`. |
| `includes/class-salon-rest-controller.php` | Two routes under `wm-salon/v1` with argument schemas, `check_public_access()` (transient IP-HMAC rate-limiting), `rate_limit_key()`, `split_limits()`, and `wm_salon_rest_calculated` action. |
| `includes/class-salon-integrations.php` | Opt-in uninstall setting (`wm_salon_delete_data_on_uninstall` on `general`), WordPress 6.5+ Block Bindings source `wm-salon/metrics`, and GenerateBlocks 2.x Dynamic Tag `{{wm_salon_metric}}`. |
| `includes/class-salon-cli.php` | `WM_Salon_CLI` and `WP_CLI::add_command( 'salon', … )`. |
| `includes/class-salon-spoke-adapter.php` | WM Suite Hub contract (`WM_Plugin_Module_Interface`); returns early when the hub's interface does not exist. |
| `assets/css/wm-salon-hub.css` | The sheet of both forms: custom properties on `.wm-salon-box`, rules only use `var()`. |
| `languages/` | `wm-salon-sovereign-hub.pot` and 29 translated `.po` / `.mo` pairs. |
| `bom.json` | CycloneDX 1.6 Software Bill of Materials with `GPL-2.0-or-later` and SHA-256 of `wm-salon-sovereign-hub.php`. |
| `uninstall.php` | `WP_UNINSTALL_PLUGIN` guard + opt-in check on `wm_salon_delete_data_on_uninstall` (returns immediately unless opted in). |
| `tests/` | `test-suite.php` (standalone), `test-pot-freshness.php` (make-pot parity), `mutations.php` and `run-mutations.sh`, `wp-runtime-salon.php` (inside WordPress), `fixtures/hub-interface-stub.php`. |

---

## 2. Calculations

### `WM_Salon_Stuhlmiete_Ledger::calculate_split( float $gross_revenue, float $rent_rate = 0.30, float $fixed_chair_rent = 0.0, float $vat_rate = 19.0 ): array`

Every amount is rounded to whole cents once; every step after that is integer arithmetic with half-up rounding (`div_half_up( a, b ) = intdiv( 2a + b, 2b )`), so the result does not depend on binary floating point or on the PHP version.

| Step | Computation |
| --- | --- |
| input | a value that is not finite, or `gross_revenue` / `fixed_chair_rent` above `MAX_AMOUNT` (1 000 000 000), throws `InvalidArgumentException` |
| gross, fixed | `round( max( 0, amount ) × 100 )` cents; the product is rounded to six decimals first, so 0.29 stays 29 cents and 1.005 becomes 101 |
| share | `clamp( rent_rate, 0, 1 )` in basis points (0.30 = 3 000) |
| VAT | `clamp( vat_rate, 0, 100 )` in hundredths of a percent (19 percent = 1 900) |
| net_revenue | `gross × 10 000 / ( 10 000 + VAT )`, half up |
| vat_amount | `gross − net` |
| salon_share_gross | `gross × share / 10 000`, half up, plus the fixed rent |
| stylist_payout_gross | `max( 0, gross − salon_share )` |
| stylist_payout_net | `payout × 10 000 / ( 10 000 + VAT )`, half up |
| shortfall_gross | `max( 0, salon_share − gross )` |

Filtered through `wm_salon_stuhlmiete_calculation` before return.

### `WM_Salon_INCI_Analyzer::split_ingredients( string $inci_string ): list<string>`

Separators are `,` and `;` outside parentheses or brackets, and every line break. A comma between two digits is not a separator (`1,2-Hexanediol`). Items are trimmed; empty items are dropped.

### `WM_Salon_INCI_Analyzer::analyze( string $inci_string ): array`

Ingredients are lower-cased; an ingredient matches a list entry from `WM_Salon_INCI_Analyzer::get_database()` (filterable via `wm_salon_inci_database`) when it contains it as a substring.

| List | Default entries |
| --- | --- |
| sulfates | sodium lauryl sulfate, sodium laureth sulfate, ammonium lauryl sulfate, ammonium laureth sulfate, sodium coco-sulfate |
| silicones | dimethicone, cyclopentasiloxane, amodimethicone, cyclomethicone, dimethiconol, phenyl trimethicone |
| parabens | methylparaben, ethylparaben, propylparaben, butylparaben, isobutylparaben |
| fragrance allergens | limonene, linalool, citral, geraniol, coumarin, citronellol, benzyl alcohol, eugenol |

Returns `total_ingredients`, `has_sulfates`, `has_silicones`, `has_parabens`, `allergens_found`, `clean_score` and `badges`, filtered via `wm_salon_inci_result`. A list without any ingredient returns `total_ingredients` 0, `null` for the three findings and the score, and no allergen or badge.

---

## 3. Shortcodes

| Tag | Form fields | Output |
| --- | --- | --- |
| `[wm_salon_stuhlmiete]` | `wm_salon_gross`, `wm_salon_rate` (percent, 0–100), `wm_salon_fixed`, submit `wm_salon_calc_submit` | form with submitted values, split breakdown, shortfall warning or validation error |
| `[wm_salon_inci_checker]` | `wm_inci_text` (text area, `sanitize_textarea_field`, line breaks kept), submit `wm_inci_submit` | form with submitted list, ingredient count, clean score, badges, allergens or empty-list notice |

---

## 4. REST API

| Method | Route | Permission | Arguments | Response |
| --- | --- | --- | --- | --- |
| `POST` | `/wp-json/wm-salon/v1/stuhlmiete` | `WM_Salon_REST_Controller::check_public_access` | `gross_revenue` (number, required, 0–1 000 000 000), `rent_rate` (number, 0–1, default 0.30), `fixed_rent` (number, 0–1 000 000 000, default 0), `vat_rate` (number, 0–100, default 19) | `200 { success, data, timestamp }`; `400` on missing/invalid param; `429 wm_salon_rate_limited` |
| `POST` | `/wp-json/wm-salon/v1/inci-check` | `WM_Salon_REST_Controller::check_public_access` | `inci` (string, required, max 65 536 bytes) | `200 { success, data, timestamp }`; `400 rest_invalid_param` or `wm_salon_no_ingredients`; `429 wm_salon_rate_limited` |

`check_public_access()` hashes `REMOTE_ADDR` with `hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) )` and increments a 60-second transient `wm_salon_rl_<digest24>` (default limit: 60 requests/min, filterable via `wm_salon_rest_rate_limit`). Administrators (`manage_options`) bypass transient throttling.

---

## 5. WP-CLI

```bash
wp salon status
wp salon analyze-inci "Aqua, Sodium Laureth Sulfate, Dimethicone, Limonene"
wp salon calculate-rent --revenue=1190 [--rate=0.30] [--fixed=0] [--vat=19]
```

---

## 6. Hooks & Ecosystem Bridges

### Filters offered by the plugin

| Filter | Parameters | Description |
| --- | --- | --- |
| `wm_salon_stuhlmiete_calculation` | `array $result, array $inputs` | Filters computed booth-rent split figures. |
| `wm_salon_inci_database` | `array $lists` | Customises `sulfates`, `silicones`, `parabens` and `fragrance_allergens` lists. |
| `wm_salon_inci_result` | `array $result, string $inci_string` | Filters non-empty INCI analysis output. |
| `wm_salon_rest_rate_limit` | `int $limit, WP_REST_Request $request` | Customises per-minute IP-HMAC REST threshold (default `60`). |
| `wm_salon_metric_value` | `?string $value, string $key, array $options` | Filters values resolved for Block Bindings and GenerateBlocks dynamic tags. |

### Actions offered by the plugin

| Action | Parameters | Description |
| --- | --- | --- |
| `wm_salon_ready` | `string $version` | Fires on `init` once textdomain is loaded. |
| `wm_salon_rest_calculated` | `string $type, array $result, WP_REST_Request $request` | Fires after a successful REST calculation (`stuhlmiete` or `inci_check`). |

### Block Bindings & GenerateBlocks 2.x

- **WordPress 6.5+ Block Bindings:** source `wm-salon/metrics` (`args.key` in `version`, `default_vat_rate`, `default_salon_rate`, `sulfates_count`, `silicones_count`, `parabens_count`, `allergens_count`, `substances_count`, `gross_revenue`, `net_revenue`, `vat_amount`, `salon_share_gross`, `stylist_payout_gross`, `stylist_payout_net`, `shortfall_gross`).
- **GenerateBlocks 2.x Dynamic Tag:** `{{wm_salon_metric key:salon_share_gross|gross:1190|rate:0.30}}`.

---

## 7. WM Suite Hub spoke

`WM_Salon_Spoke_Adapter`: slug `wm-salon-sovereign-hub`, version from `WM_SALON_HUB_VERSION` (`1.1.2`), health `HEALTHY` only when `self_test` passes (`ERROR` otherwise), SBOM component with `GPL-2.0-or-later` and SHA-256 of `wm-salon-sovereign-hub.php`, `get_settings_url()` = `null`, telemetry `shortcodes_registered` and `self_test`.

---

## 8. Tests

```bash
php tests/test-suite.php
php tests/test-pot-freshness.php
bash tests/run-mutations.sh
wp eval-file wp-content/plugins/wm-salon-sovereign-hub/tests/wp-runtime-salon.php --user=1
```

---

## 9. Security disclosure

See [SECURITY.md](SECURITY.md).
