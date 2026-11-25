# Changelog — WM Salon Sovereign Hub

All notable changes to **WM Salon Sovereign Hub** are documented in this file in adherence to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Fixed
- Switched GenerateBlocks 2.x Dynamic Tag filtering from `generateblocks_dynamic_tag_output` to `generateblocks_dynamic_tag_replacement` with `mixed` safety.
- Standardized WM Suite Hub spoke registration filter to `wm_register_suite_module` across code, READMEs, translations, and architecture docs; added `Tested up to: 7.1` in `wm-salon-sovereign-hub.php`; regenerated POT/PO/MO catalogs; and synchronized `bom.json`.

## [1.2.1] - 2026-11-21

### Fixed

- The ingredient count of the INCI checker («%d Inhaltsstoff erkannt») repeated one form for several numbers in Croatian, Czech, Irish, Latvian, Lithuanian, Maltese, Polish, Romanian, Slovak and Slovenian (12 catalogs), for example Czech «Rozpoznáno 2 složek» and Polish «Rozpoznano składników: 2» next to an inflected singular; each number now gets the form its language uses (Czech «Rozpoznány 2 složky», Polish «Rozpoznano 2 składniki»). The plugin's code is unchanged.

## [1.2.0] - 2026-11-20

### Changed
- The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
- The `.wordpress-org/` banners show the current version.
- `README.md`, `README.de.md` and `README.es.md` said they describe version 1.1.2 while the plugin shipped 1.1.3; they name the version they describe again.
- `tests/test-suite.php` compares the argument of `wm_salon_ready` with `WM_SALON_HUB_VERSION` instead of a literal that had to be edited on every release.
- `CLAUDE.md` no longer describes `assets/images/` files that had already been moved, and lists the catalog freshness check.

### Removed
- The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

## [1.1.3] - 2026-11-19

- Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.

- Added root `SUPPORT.md`, `.github/ISSUE_TEMPLATE/{config.yml,bug_report.yml}`, and explicit zero-support / "AS IS" liability notices across `README.md`, `README.de.md`, `README.es.md`, `readme.txt`, `SECURITY.md`, and `llms.txt`.
- Migrated plugin author/catalog/repository metadata to personal open-source distribution (`Arnold Wender`, `https://arnoldwender.com/wordpress-plugins/wm-salon-sovereign-hub/`, `https://github.com/arnoldwender/wm-wp-salon-sovereign-hub`), normalized FSF GPL-2.0 `LICENSE` metadata where applicable, and updated `.wordpress-org/` banner branding to `ARNOLD WENDER · OPEN SOURCE`.

## [1.1.2] - 2026-11-19

### Added
- Official `.wordpress-org/` directory visual kit (`icon.svg`, `icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`) and live Playwright screenshots (`screenshot-1.png` through `screenshot-3.png`) with `== Screenshots ==` in `readme.txt`.
- Visual previews and direct link to `https://arnoldwender.com/wordpress-plugins/wm-salon-sovereign-hub/` in `README.md`, `README.de.md`, `README.es.md`, and `llms.txt`.

### Changed
- Archived superseded early mockups and banners non-destructively in `versionen/visuals-pre-2026-11-19/` and excluded `versionen/` from distribution packages via `.distignore`.
- Updated release metadata, SBOM (`bom.json`), `DEVELOPERS.md`, `SECURITY.md`, and stale `README.es.md` version text to `1.1.2`.

---

## [1.1.1] - 2026-11-19

### Changed
- Point `Plugin URI` to `https://arnoldwender.com/wordpress-plugins/wm-salon-sovereign-hub/` and align release metadata at `v1.1.1`.

## [1.1.0] - 2026-11-16

### Added

- **REST IP-HMAC Rate-Limiting & Input Guards:** `WM_Salon_REST_Controller::check_public_access()` protects both public routes (`wm-salon/v1/stuhlmiete` and `wm-salon/v1/inci-check`) with transient-based rate-limiting keyed by an HMAC-SHA256 digest of the client IP (`wm_salon_rl_*`, default 60 requests per minute, HTTP 429 on burst, zero raw IP storage), plus a 64 KiB payload cap on `inci`.
- **Opt-in Uninstallation (`D-UNINSTALL`):** `uninstall.php` preserves all options and transients by default unless `wm_salon_delete_data_on_uninstall` is explicitly enabled via *Settings -> General* (`WM_Salon_Integrations::register_settings()`).
- **Ecosystem Bridges & Developer Hooks:** Registered WordPress 6.5+ Block Bindings source `wm-salon/metrics` and GenerateBlocks 2.x Dynamic Tag `{{wm_salon_metric}}`, alongside filters (`wm_salon_stuhlmiete_calculation`, `wm_salon_inci_database`, `wm_salon_inci_result`, `wm_salon_rest_rate_limit`, `wm_salon_metric_value`) and actions (`wm_salon_ready`, `wm_salon_rest_calculated`).
- **29 Translated Language Catalogs & Release Metadata:** Translated and compiled all 29 `.po`/`.mo` catalogs in `languages/`, added CycloneDX 1.6 `bom.json`, `.license-allowlist.yml`, `.wordpress-org/blueprints/blueprint.json` and symlinked `AGENTS.md -> CLAUDE.md`.

### Added — the catalog freshness gate (2026-09-20)

- `tests/test-pot-freshness.php` and two CI steps on PHP 8.3: the gate extracts the translatable strings from the source and fails if any of them lacks an entry in the `.pot`. A string with no catalog entry cannot be translated by anyone into any language — the translator never sees it, nothing reports an error, and the interface simply stays German.
- **This catalog was already current** — 32 translatable strings, 32 entries, nothing missing and nothing stale — and that is the reason to add the gate rather than a reason to skip it. Fifteen of the fleet's twenty-four plugins had drifted, one of them down to a single entry still shared with the code, and not one of them had a check that could notice. Being correct today is not a mechanism.
- The gate takes its definition of "fresh" from `wp i18n make-pot` rather than a regex of its own, reads the text domain from the plugin header instead of holding a second copy, treats 0 extracted strings as a failure rather than a pass, ignores the plugin-header fields `make-pot` adds (nobody translates an author name or a URL), requires wp-cli with no escape hatch, and runs its own self-test before judging the plugin.
### The version this file opens on matches the version the plugin ships (D15, 2026-09-17)

- **The plugin is 1.0.0, and the changelog now opens on 1.0.0 too.** This file used to open on `[1.1.0]`, a release that `git tag` (0) and `gh release list` (0) both deny and that no other file in the repo agreed with. The `1.1.0` entry moved **verbatim and complete** to an appendix at the foot of this file: nothing was deleted, because a changelog is a record and the record is that those entries were written. Moved automatically by the changelog verification script, which refuses to write if a single non-blank line would be lost.

### Fixed (2026-09-15)

Found by a reader pass that compared the documents with the code and measured two WordPress environments (PHP 8.3 and PHP 8.4) before anything changed. Each fix has an assertion in `tests/test-suite.php` that fails without it and an entry in `tests/mutations.php`.

- **The split rounded differently on each PHP version.** 30 % of 0.75 € gave 0.23 € on PHP 8.3 and 0.22 € on PHP 8.4, because float `round()` changed in PHP 8.4. `calculate_split()` now rounds each amount to whole cents once and computes every step in integer cents, rounding half up; test group 1 compares every amount from 0.01 € to 200 € at 30 % with an integer reference.
- **A fixed rent larger than the turnover vanished from the result.** 100 € at 30 % with a fixed rent of 130 € returned a salon share of 160 € and a payout of 0 without saying that 60 € were not covered. The result carries `shortfall_gross`, and the form and `wp salon calculate-rent` say by how much the share exceeds the turnover.
- **The split route accepted anything.** An empty body and `gross_revenue=abc` answered 200 with every figure 0, `rent_rate=30` (a percentage, as the form takes it) became a full salon share, and `gross_revenue=1e309` answered HTTP 500. Both routes declare their arguments (gross revenue required, share 0–1, VAT 0–100, amounts up to 1 000 000 000 €), the handler checks them again, and malformed input answers 400. The ledger throws for a value that is not a finite number.
- **An empty ingredient list got the best verdict.** An empty list, a lone comma or an array (analysed as the word "Array", with a PHP warning) scored 100 out of 100 with every badge, `CLEAN_BEAUTY` included. A list without an ingredient now has no score and no badge; the route answers 400, the form says the list holds no ingredient, and `wp salon analyze-inci` ends with an error.
- **Ingredient lists were split on every comma and nothing else.** A list with one ingredient per line counted as one ingredient (the route's `sanitize_text_field()` also collapsed the line breaks), `1,2-Hexanediol` counted as two, and `Parfum (Limonene, Linalool, …)` was cut inside the parentheses. Commas, semicolons and line breaks separate now, except a comma inside parentheses or between two digits. Each allergen found counts once, named as in the list, so five allergens inside one ingredient deduct as much as five separate ones.
- **Both shortcodes were fatal on WordPress before 6.5.** A shortcode without attributes receives `''` there, which the `array` parameter type turned into a TypeError. The callbacks accept `array|string`.
- **The forms mangled their input.** Slashed POST data showed backslashes (`Parfum \"Limonene\"`), the calculator showed the default values next to a result computed from other ones, and text or a share above 100 % was computed as 0 or clamped. Values are unslashed and kept, and invalid input gives a message instead of figures.
- **Inline styles below the contrast minimums.** The two forms carried 31 inline `style` attributes with 40 hex colours in the source; white button text stood at 3.19:1 on the calculator button and 3.77:1 on the INCI button, and the field borders at 2.36:1 against the form and 1.93:1 around the result. Both forms use `assets/css/wm-salon-hub.css` (custom properties only, text at least 4.5:1, borders and buttons at least 3:1, 48px targets, a visible focus outline); test group 5 computes the ratios from the sheet.
- **Health and telemetry were literals.** The hub adapter reported `HEALTHY`, `ACTIVE`, 2 and 2 whatever the state. Health follows the `self_test` action, and telemetry counts the registered shortcodes and reports the self test.
- **The documented WP-CLI names did not exist.** `wp salon analyze-inci` and `wp salon calculate-rent` answered "not a registered subcommand" on both sites, because WP-CLI names a subcommand after its method. They are registered with `@subcommand`; `analyze_inci` and `calculate_rent` stay as aliases. `calculate-rent` takes `--fixed` and `--vat` and no longer labels the net turnover "inkl. 19% MwSt".
- **Nothing was translatable.** The code had no gettext call and the text domain was never loaded, while 29 catalogs translated four words the code never printed. The strings go through gettext, the text domain loads on `init`, and the catalogs are generated from the code (German source strings, no translations yet).
- **`uninstall.php`** deleted an option and a transient that no commit of the plugin ever wrote; it keeps only the guard.

### Changed (2026-09-15)

- CI: a PHP syntax check, the suite must print its results line with 0 failures, the mutation check runs, and the SBOM step that could only warn is gone.
- `tests/wp-runtime-salon.php` checks the routes through `rest_do_request()`, the WP-CLI names, both shortcodes and the adapter inside WordPress, and confirms that no option or transient was written.
- `assets/images/`, `README.de.md`, `README.es.md` and `DEVELOPERS.md` are left out of the plugin package. The README no longer embeds the banner, which advertises booking, "GoBD & DATEV export" and "§ 7 SGB IV compliant".

### Documentation (2026-09-14)

- `README.md`, `README.de.md`, `README.es.md`, the 24 translated READMEs, `DEVELOPERS.md`, `llms.txt`, `CONTRIBUTING.md`, `SECURITY.md`, `readme.txt` and the header description describe what the plugin ships: two calculators (booth-rent split, INCI ingredient check), each as a shortcode, a public REST route and a WP-CLI command, plus a hub adapter. Until now they promised a multi-tenant booth manager with stylist accounts, tax IDs and client data, a `[wm_salon_booking]` calendar, § 14 UStG invoicing, "commission-free booking management", a cosmetic batch tracker, an admin screen, `wp salon verify|export`, REST routes `/status` `/submit` `/export`, three tables, GoBD audit exports, an HMAC-SHA256 chain and a `wm_salon_batch_logged` action; none of those exist in this repository (measured: no `add_menu_page`, `dbDelta`, `hash_hmac`, `get_option` or `do_action`). The translated READMEs are generated from `README.md` by the translation sync script (PR #3); their header tagline too (PR #4).
- The entries **[1.1.0]** and **[1.0.0]** below list features that never shipped as described; they are kept as the record of what was written at the time, each such line marked. The plugin header stays at 1.0.0.

### Fixed (2026-09-14)

- **POST wm-salon/v1/stuhlmiete answered HTTP 500 for vat_rate=-100.** The rate went unchecked into `1 + rate / 100`, a multiplier of 0, and the division threw `DivisionByZeroError` (reproduced on a local WordPress install). `calculate_split()` now clamps the VAT rate to 0 to 100 percent. Test group 1 fails without the clamp.
- **The hub adapter confirmed every action.** `execute_action()` returned "executed successfully" for any action name; it now implements `self_test` (both calculators against known values) and reports unknown actions as failures. `get_settings_url()` pointed at `admin.php?page=wm-salon-hub`, a page nothing registers; it returns `null`.
- **Frontend claims:** the INCI checker said it checks "26 EU-deklarationspflichtige Allergene" — the list holds eight; the Stuhlmiete calculator headed itself "§ 7 SGB IV & GoBD" and promised a "rechtssichere" split. Both now say what they compute and that they give no legal verdict. The three emoji in the forms became inline SVG icons (`aria-hidden`). `wp salon status` printed a hardcoded version and "§ 7 SGB IV konform / BETRIEBSBEREIT"; it prints the version constant and the entry points.

### Changed (2026-09-14)

- **Plugin Check on the dist view:** `readme.txt` added (PR #2). Without it the checker read `README.md` as the readme and flagged the missing stable tag, tested version and licence line. The new file mirrors the plugin header (Stable tag 1.0.0, Requires PHP 8.1, GPLv2 or later) and describes only what the code registers. `Tested up to: 7.1` is backed by the local WordPress runtime pass.
- `.distignore` keeps tests, docs, CI and tool caches out of the distributable ZIP (PR #1).
- `.github/scripts/security-audit.py` matches shell calls as whole function names (PR #5) and, from 2026-09-15, reads PHP through a lexer instead of skipping lines (PR #6).

---

## [1.0.0] — 2026-08-25

### Initial Enterprise Release

- Core architectural implementation for Salon & Booth Rental Governance. — *what shipped are two calculators*
- WP-CLI management commands under `wp salon`.
- Revisionssicheres GoBD cryptographic ledger and telemetry hooks. — *never shipped*
- Strict compliance with UStG § 4 Nr. 12 / § 14, EU Kosmetikverordnung (EG) Nr. 1223/2009 & GoBD. — *no such assessment is made by the code*

---

## Appendix — the entry written for 1.1.0 on 2026-08-29, never released

> **Not a released version — the plugin does not carry this number.** Measured 2026-09-17: `git tag` → 0, `gh release list` → 0, and `wm-salon-sovereign-hub.php`, `readme.txt`, `SECURITY.md`, `includes/class-salon-spoke-adapter.php` carry **1.0.0**, which is the version this plugin ships as. The entry is kept verbatim as the record of what was written on 2026-08-29; only its heading changed, because Keep a Changelog reserves `## [x.y.z]` for releases and this was never one. Raising the plugin to 1.1.0 means raising `wm-salon-sovereign-hub.php`, `readme.txt`, `SECURITY.md`, `includes/class-salon-spoke-adapter.php` in the same commit — the number lives in more than one file.

### Added

- **24 Official European Union Languages:** Full binary `.mo` and source `.po` translation packages in `languages/` (`bg_BG`, `cs_CZ`, `da_DK`, `de_DE`, `el_GR`, `en_GB`, `es_ES`, `et_EE`, `fi_FI`, `fr_FR`, `ga_IE`, `hr_HR`, `hu_HU`, `it_IT`, `lt_LT`, `lv_LV`, `mt_MT`, `nl_NL`, `pl_PL`, `pt_PT`, `ro_RO`, `sk_SK`, `sl_SI`, `sv_SE`). — *the catalogs hold four words each and the code has no gettext call*
- **Developer Documentation Suite:** Created `DEVELOPERS.md` with complete architecture diagrams, hook references, database schema, and REST API guides. — *the manual described tables, routes and a hash chain that never shipped; rewritten from the code on 2026-09-14*
- **Decoupled REST API Controller:** Registered namespace `/wp-json/wm-salon/v1` with strict nonce and permission validation. — *the namespace exists with two public routes and no nonce*
- **Zero-CDN Frontend Shortcode:** Implemented `[wm_salon_stuhlmiete]` with inline SVG icons and CSS design tokens. — *the shortcode exists; it used emoji and inline hex styles, no tokens*
- **CycloneDX v1.6 SBOM Engine:** Dynamic SHA-256 entry file integrity hashing and spoke adapter integration for **WM Suite Hub**.
- **Enterprise Showcase Assets:** High-fidelity 16:9 marketing banner and cockpit showcase mockups conditioned on real screenshot topology. — *the cockpit mockup shows a screen the plugin does not have*
- **Clean Uninstallation Lifecycle:** Comprehensive `uninstall.php` handler for clean database and transient cache purging. — *it deletes an option and a transient the plugin never writes; there is no table*

### Security

- **CWE-1236 Mitigation:** Active sanitization against formula injection attacks on all CSV exports and dynamic text inputs. — *never shipped: there is no CSV export; `sanitize_formula()` has no caller*
- **Constant-Time Cryptography:** Enforced `hash_equals()` verification on all HMAC-SHA256 signature chains. — *never shipped: there is no hash chain*
- **SQL Parameterization:** Refactored all queries to use strict `$wpdb->prepare()` with parameterized placeholders. — *never applied: the plugin runs no database query*

### Quality & Tests

- Expanded standalone deterministic test suite (`tests/test-suite.php`) with a complete green pass rate and zero external dependencies.
