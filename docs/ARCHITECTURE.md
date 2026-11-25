# Architecture — WM Salon Sovereign Hub
`wm-salon-sovereign-hub` · version `1.1.0` from the plugin header · WordPress 6.4+ · PHP 8.1+ · text domain `wm-salon-sovereign-hub`

Every figure below comes from the code, not from any document — the commands are given so they can be re-run.

```bash
# the inventory in this file
python3 the fleet's architecture inventory tool wm-salon-sovereign-hub
```

---

## What runs where

```
WM_Salon_Stuhlmiete_Ledger   booth-rent split, computed in WHOLE CENTS (filter: wm_salon_stuhlmiete_calculation)
WM_Salon_INCI_Analyzer       INCI ingredient check (filters: wm_salon_inci_database, wm_salon_inci_result)

each reachable three ways:
  a shortcode · a rate-limited POST route under wm-salon/v1 · a wp salon subcommand

WM_Salon_Integrations        WP 6.5+ Block Bindings (wm-salon/metrics), GenerateBlocks 2.x ({{wm_salon_metric}}), opt-in uninstall setting
WM_Salon_Spoke_Adapter       added through the wm_register_suite_module filter
```

Plain classes under `includes/` (`class-salon-*.php`) — no namespace, no autoloader.
**No custom tables.** Uninstallation preserves data by default unless `wm_salon_delete_data_on_uninstall` is enabled.

---

## Wiring — what to touch for a given change

| Change | Files, in order | Trap |
|---|---|---|
| Touch the money maths | `WM_Salon_Stuhlmiete_Ledger::calculate_split()` | **integers only.** Each amount is rounded to cents once with `scaled()`, then computed with `div_half_up()`. Never round a float result: PHP 8.3 and 8.4 disagree — 30 % of 0.75 € is 0.23 € on one and 0.22 € on the other |
| Touch ingredient parsing | `WM_Salon_INCI_Analyzer` | separators are commas, semicolons and line breaks — **except** a comma inside parentheses or between digits. Sanitise with `sanitize_textarea_field()`, never `sanitize_text_field()`, which collapses the line breaks the parser needs |
| Add a shortcode | `WM_Salon_Shortcodes` | the callback must accept `array\|string`: WordPress before 6.5 passes `''` when no attributes are given |
| Add a REST route | `WM_Salon_REST_Controller` | arguments carry a schema **and** the handler checks them again. The existing routes are public and store nothing; anything that writes needs a nonce and a capability |
| Add an icon | inline SVG with `aria-hidden="true"` | **no emoji**, no `style=` and no colour in the PHP output (group 4); the sheet keeps colours and dimensions on custom-property lines (group 5) |

---

## Fragile spots

1. **Money is integer cents end to end.** See the wiring table — this is a cross-PHP-version correctness bug, not a style preference.
2. **The default is never "all good".** Missing or malformed input answers 400 from the route and an error from the form and the command — never zeros. An ingredient list with no ingredients gets `null` findings, a `null` score and no badge. Health is `HEALTHY` only while `self_test` passes.
3. **A shortfall is shown, not hidden.** When the salon's share exceeds the turnover, `shortfall_gross` carries the difference and both the form and the command say so. Clamping it to zero would report a balanced split that is not balanced.
4. **The calculators give figures, not legal verdicts.** "rechtssicher", "GoBD", "§ 7 SGB IV konform", "deklarationspflichtig" and allergen counts the analyzer does not check have all been removed once — the booth-rent split in particular says nothing about whether an arrangement is self-employment or employment, which is the question its users actually have.
5. **Never run `i18n-sync-fleet.py` on this plugin.** Use `wp-plugin-fleet-catalog-regenerate.sh`.
6. **The banner and cockpit mockup show booking, GoBD/DATEV export and conformity claims the plugin does not have.** Excluded from the distributable package via `.distignore`.

---

<!-- wm-measured-inventory -->

## Measured inventory

Regenerated from the code, never hand-edited. Everything above this line is written by hand and stays.

```bash
python3 the fleet's architecture inventory tool wm-salon-sovereign-hub --append-to docs/ARCHITECTURE.md
```

## Inventory by layer

**9 PHP files, 1451 lines** (excluding `tests/`, `vendor/`, `node_modules/`, `languages/`).

| Layer | Files | Lines |
|---|---:|---:|
| `(raíz)` | 2 | 104 |
| `includes` | 7 | 1347 |

---

## Classes

| Class | File |
|---|---|
| `WM_Salon_CLI` | [`includes/class-salon-cli.php`](../includes/class-salon-cli.php) |
| `WM_Salon_INCI_Analyzer` | [`includes/class-salon-inci-analyzer.php`](../includes/class-salon-inci-analyzer.php) |
| `WM_Salon_Integrations` | [`includes/class-salon-integrations.php`](../includes/class-salon-integrations.php) |
| `WM_Salon_REST_Controller` | [`includes/class-salon-rest-controller.php`](../includes/class-salon-rest-controller.php) |
| `WM_Salon_Shortcodes` | [`includes/class-salon-shortcodes.php`](../includes/class-salon-shortcodes.php) |
| `WM_Salon_Spoke_Adapter` | [`includes/class-salon-spoke-adapter.php`](../includes/class-salon-spoke-adapter.php) |
| `WM_Salon_Stuhlmiete_Ledger` | [`includes/class-salon-stuhlmiete-ledger.php`](../includes/class-salon-stuhlmiete-ledger.php) |

---

## Entry points

**REST routes:** `/inci-check`, `/stuhlmiete`

**WP-CLI:** `salon`

**Shortcodes:** `wm_salon_inci_checker`, `wm_salon_stuhlmiete`

---

## Persistence — who writes each key and who reads it

| Key | Written by | Read by |
|---|---|---|
| `wm_salon_delete_data_on_uninstall` | — | `includes/class-salon-cli.php`, `uninstall.php` |

**Read but never written by this plugin:** `wm_salon_delete_data_on_uninstall`. The behaviour behind them is reachable only through `wp option update` or a filter — there is no screen that sets them, so an operator cannot switch it on.


---

## Gates

- `tests/mutations.php`
- `tests/run-mutations.sh`
- `tests/test-pot-freshness.php`
- `tests/test-suite.php`
- `tests/wp-runtime-salon.php`
- `.github/workflows/ci.yml`

What CI actually runs is in the workflow files above — a test file that no workflow invokes is not a gate.

---

## Drift detected against the documentation

Nothing measurable: versions agree and every class named in the documents exists.

