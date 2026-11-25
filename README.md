# WM Salon Sovereign Hub

<p align="center">
  <a href="README.md"><img src="https://img.shields.io/badge/Language-English%20(Default)-blue.svg" alt="English Documentation" /></a>
  <a href="README.de.md"><img src="https://img.shields.io/badge/Sprache-Deutsch-lightgrey.svg" alt="Deutsche Dokumentation" /></a>
  <a href="README.es.md"><img src="https://img.shields.io/badge/Idioma-Espa%C3%B1ol-lightgrey.svg" alt="Documentación en Español" /></a>
  <a href="#translated-documentation"><img src="https://img.shields.io/badge/Translated%20headings-24%20EU%20languages-lightgrey.svg" alt="Translated headings in 24 EU languages" /></a>
</p>

<p align="center">
  <a href="https://wordpress.org"><img src="https://img.shields.io/badge/WordPress-6.4%20--%207.1-21759b.svg?logo=wordpress&logoColor=white" alt="WordPress Version" /></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg?logo=php&logoColor=white" alt="PHP Version" /></a>
  <img src="https://img.shields.io/badge/Zero--CDN-no%20external%20requests-purple.svg" alt="Zero-CDN" />
  <a href="https://www.gnu.org/licenses/gpl-2.0.html"><img src="https://img.shields.io/badge/License-GPLv2%20or%20later-brightgreen.svg" alt="License" /></a>
</p>

> **Two calculators for hair salons: the booth-rent (Stuhlmiete) revenue split between a salon and an independent stylist, and an on-site INCI ingredient checker — as shortcodes, REST routes, WP-CLI commands, Block Bindings and GenerateBlocks dynamic tags**  
> **Plugin page:** [https://arnoldwender.com/wordpress-plugins/wm-salon-sovereign-hub/](https://arnoldwender.com/wordpress-plugins/wm-salon-sovereign-hub/)  
> **Built with an agentic workflow:** developed with AI coding agents. Review and test it like any third-party code before you use it in production.  
> **Support & Liability:** Zero support — reproducible bugs on [GitHub Issues](https://github.com/arnoldwender/wm-wp-salon-sovereign-hub/issues) only · no private support · AS IS / no liability ([SUPPORT.md](SUPPORT.md))

> This README describes what version 1.2.1 ships, measured directly against the repository source code: two registered shortcodes (`[wm_salon_stuhlmiete]` and `[wm_salon_inci_checker]`), two rate-limited REST routes under `wm-salon/v1`, one `WP_CLI::add_command` (`wp salon` with `status`, `analyze-inci` and `calculate-rent`), WordPress 6.5+ Block Bindings (`wm-salon/metrics`), a GenerateBlocks 2.x dynamic tag (`{{wm_salon_metric}}`), developer filters and actions, an opt-in uninstallation setting (`wm_salon_delete_data_on_uninstall`, disabled by default), a WM Suite Hub spoke adapter, CycloneDX 1.6 SBOM (`bom.json`) and 29 translated `.po`/`.mo` language catalogs.

---

## Images

!WM Salon Sovereign Hub banner

The banner above and the live WordPress screenshots below live in `.wordpress-org/`; the old marketing mockups were archived outside the repository on 2026-11-20 (release 1.2.0).

| Booth-Rent Split | INCI Ingredient Checker | Uninstall Setting |
|---|---|---|
| ![Whole-cent chair-rent split and VAT calculator](.wordpress-org/screenshot-1.png) | ![Curated INCI ingredient checker matching sulphates, silicones, parabens and 8 fragrance allergens](.wordpress-org/screenshot-2.png) | ![General Settings opt-in field controlling uninstall cleanup](.wordpress-org/screenshot-3.png) |

---

## What the plugin does

1. **Booth-rent split.** `WM_Salon_Stuhlmiete_Ledger::calculate_split( gross, rate, fixed, vat )` takes a stylist's gross turnover (VAT included), the salon's share as a fraction (clamped to 0–1), an optional fixed rent for the same period and a VAT rate (clamped to 0 to 100 percent). It rounds each amount to whole cents once and computes every step in integer cents with commercial rounding (half up), so a 0.30 share on a sub-euro amount rounds identically on every PHP version. It returns gross and net revenue, the VAT amount, the salon's share (percentage share plus fixed rent), the stylist's payout gross and net, and `shortfall_gross`: by how much the salon's share exceeds the turnover when the fixed rent is larger than what is left (the payout is then 0). A value that is not a finite number, or an amount above the 1 000 000 000 limit, throws `InvalidArgumentException`.
2. **Split front doors.** The shortcode `[wm_salon_stuhlmiete]` is a form with gross turnover, the salon's share in percent (0–100) and a fixed rent; VAT is fixed at 19 percent. It keeps the entered values, prints the split below the form with a message when the fixed rent exceeds the turnover, and asks for valid amounts instead of computing with 0. `POST /wp-json/wm-salon/v1/stuhlmiete` takes `gross_revenue` (required), `rent_rate` (0–1, default 0.30), `fixed_rent` (default 0) and `vat_rate` (0 to 100, default 19); a missing, non-numeric or out-of-range value answers 400. `wp salon calculate-rent --revenue=<EUR> [--rate=<share>] [--fixed=<EUR>] [--vat=<percent>]` prints the same figures and warns about a shortfall.
3. **INCI ingredient checker.** `WM_Salon_INCI_Analyzer::analyze( "Aqua, …" )` splits a list on commas, semicolons and line breaks — a comma inside parentheses or between two digits stays part of the ingredient, so `Parfum (Limonene, Linalool)` and `1,2-Hexanediol` are one ingredient each — and matches each ingredient against four substance lists by substring: 5 sulfates, 6 silicones, 5 parabens and 8 fragrance allergens (limonene, linalool, citral, geraniol, coumarin, citronellol, benzyl alcohol, eugenol; whether and above which concentration one of them must be listed on packaging is not checked). It returns the ingredient count, the three yes/no findings, the allergens found (each once, named as in the list), a clean score (100 minus 30 for sulfates, 20 for silicones, 40 for parabens, 2 per allergen up to 10) and the badges `SULFATFREI`, `SILIKONFREI`, `PARABENFREI`, `CLEAN_BEAUTY` (score 80 or above). A list without any ingredient gets no verdict: findings and score `null`, no badge.
4. **INCI front doors.** `[wm_salon_inci_checker]` is a form with a text area; it prints the number of ingredients recognised, the score, the badges and the allergens found, or says that the list holds no ingredient. `POST /wp-json/wm-salon/v1/inci-check` takes `inci` (a required string up to 64 KiB) and answers 400 for an oversized or empty ingredient list; `wp salon analyze-inci "<ingredients>"` ends with an error for an empty list.
5. **REST IP-HMAC rate-limiting.** Both public `wm-salon/v1` endpoints run `WM_Salon_REST_Controller::check_public_access()`, which hashes the client IP with `hash_hmac( 'sha256', ... )` keyed by the WordPress auth salt and tracks request counts in short-lived transients (`wm_salon_rl_*`, default 60 requests per minute, HTTP 429 when exceeded). No raw IP address is stored.
6. **Ecosystem & developer extensibility.** WordPress 6.5+ Block Bindings source `wm-salon/metrics` and GenerateBlocks 2.x Dynamic Tag `{{wm_salon_metric}}` expose salon figures (`version`, `substances_count`, `salon_share_gross`, `stylist_payout_gross`, etc.) to blocks and templates. Developers can hook into `wm_salon_stuhlmiete_calculation`, `wm_salon_inci_database`, `wm_salon_inci_result`, `wm_salon_rest_rate_limit`, `wm_salon_metric_value`, `wm_salon_ready` and `wm_salon_rest_calculated`.
7. **WP-CLI & WM Suite Hub spoke.** `wp salon status` prints the version, entry points and uninstallation policy. `includes/class-salon-spoke-adapter.php` registers through `wm_register_suite_module` with a CycloneDX SBOM component, SHA-256 hash of the main file and measured `self_test` health.
8. **Front end, i18n & opt-in uninstallation.** Both forms enqueue `assets/css/wm-salon-hub.css` only when rendered (custom properties only, WCAG contrast ratios verified in tests, 48px targets, visible focus ring). `languages/` ships `wm-salon-sovereign-hub.pot` and 29 compiled `.po`/`.mo` catalogs. On uninstallation, `uninstall.php` preserves all options and transients by default unless `wm_salon_delete_data_on_uninstall` is explicitly enabled under *Settings → General*.

### Not shipped

No dedicated salon booking calendar, stylist account management, client database or invoicing; no cosmetic batch tracking; no custom database tables; no external network requests; no frontend JavaScript dependency.

---

## Quick start

1. Install and activate the plugin (WordPress 6.4+, PHP 8.1+).
2. Place `[wm_salon_stuhlmiete]` and / or `[wm_salon_inci_checker]` on a page. Each form posts back to the same page and prints its result below the form.
3. Call `POST /wp-json/wm-salon/v1/stuhlmiete` with `gross_revenue` and `rent_rate` (`0.30`), or `/inci-check` with `inci`; on the server, run `wp salon calculate-rent --revenue=1190 --rate=0.30`.
4. Bind core blocks via `wm-salon/metrics` or insert `{{wm_salon_metric key:substances_count}}` in GenerateBlocks 2.x.

---

## Developers

Classes, routes, hooks, dynamic tags, commands and the hub adapter: **[DEVELOPERS.md](DEVELOPERS.md)**.

```bash
php tests/test-suite.php        # standalone assertions, no WordPress needed
php tests/test-pot-freshness.php # verifies languages/*.pot matches code gettext strings
bash tests/run-mutations.sh     # each correction undone once; the suite must fail every time
```

### Known limits

- The default INCI match is a substring search over four curated lists (extendable via the `wm_salon_inci_database` filter). Unlisted substances are not detected, so the checker is an orientation aid, not a regulatory product assessment.
- The shortcode calculator applies the standard 19 percent VAT rate; the REST route, Block Bindings / GenerateBlocks options and WP-CLI accept custom VAT rates.

---

## Translated documentation

Translated READMEs live in `docs/translations/README.<lang>.md`; their headings are translated and their bodies are generated from this file, which is the reference. All 29 gettext `.po`/`.mo` UI catalogs in `languages/` are translated and compiled.

| Language (Native) | Locale Code | Language (Native) | Locale Code |
|---|---|---|---|
| [Български](docs/translations/README.bg.md) | `bg_BG` | [Magyar](docs/translations/README.hu.md) | `hu_HU` |
| [Čeština](docs/translations/README.cs.md) | `cs_CZ` | [Italiano](docs/translations/README.it.md) | `it_IT` |
| [Dansk](docs/translations/README.da.md) | `da_DK` | [Lietuvių](docs/translations/README.lt.md) | `lt_LT` |
| [Deutsch](README.de.md) | `de_DE` | [Latviešu](docs/translations/README.lv.md) | `lv_LV` |
| [Ελληνικά](docs/translations/README.el.md) | `el_GR` | [Malti](docs/translations/README.mt.md) | `mt_MT` |
| [English](README.md) | `en_GB` | [Nederlands](docs/translations/README.nl.md) | `nl_NL` |
| [Español](README.es.md) | `es_ES` | [Polski](docs/translations/README.pl.md) | `pl_PL` |
| [Eesti](docs/translations/README.et.md) | `et_EE` | [Português](docs/translations/README.pt.md) | `pt_PT` |
| [Suomi](docs/translations/README.fi.md) | `fi_FI` | [Română](docs/translations/README.ro.md) | `ro_RO` |
| [Français](docs/translations/README.fr.md) | `fr_FR` | [Slovenčina](docs/translations/README.sk.md) | `sk_SK` |
| [Gaeilge](docs/translations/README.ga.md) | `ga_IE` | [Slovenščina](docs/translations/README.sl.md) | `sl_SI` |
| [Hrvatski](docs/translations/README.hr.md) | `hr_HR` | [Svenska](docs/translations/README.sv.md) | `sv_SE` |

---

## Compliance

- **Zero external requests:** revenue figures and ingredient lists are computed on the site and never sent anywhere.
- **Privacy:** calculator inputs are never stored; REST rate-limiting hashes client IPs with HMAC-SHA256 before writing short-lived counter transients.
- **Scope:** the split calculator does not assess whether a booth rental is genuine self-employment (§ 7 SGB IV) or produce an invoice (§ 14 UStG); the INCI checker does not assess a product under Regulation (EC) No 1223/2009. Both give figures, not legal or regulatory verdicts.

---

## Contribution & Security

- [CONTRIBUTING.md](CONTRIBUTING.md) · [SECURITY.md](SECURITY.md) · [CHANGELOG.md](CHANGELOG.md)

---

## License

Distributed under the **GNU General Public License v2 or later** (`GPL-2.0-or-later`).  
Copyright (C) 2026 Arnold Wender.  
