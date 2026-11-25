=== WM Salon Sovereign Hub ===
Contributors: arnoldwender
Tags: salon, booth-rental, cosmetics, inci, calculator
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Booth rental (Stuhlmiete) revenue split between a salon and its independent stylists, and an on-site INCI ingredient checker for cosmetic products.

== Description ==

The plugin gives a hair salon or barbershop two calculators that run directly on its own site, without sending revenue figures or product data anywhere.

* **Stuhlmiete split:** the shortcode `[wm_salon_stuhlmiete]` splits a stylist's gross turnover between the salon's booth rent and the stylist's payout. It takes the gross revenue, the salon's share in percent and an optional fixed rent, applies standard VAT and returns net revenue, VAT amount, the salon's share and the stylist's payout gross and net, computed in whole cents. When the fixed rent exceeds the turnover, the payout is 0 and the form shows the difference. It does not assess whether a booth rental is genuine self-employment and produces no invoice.
* **INCI checker:** the shortcode `[wm_salon_inci_checker]` reads an INCI ingredient list separated by commas, semicolons or line breaks and matches it against four curated lists (5 sulfates, 6 silicones, 5 parabens and 8 fragrance allergens). It reports the number of ingredients, a clean score out of 100, whether sulfates, silicones or parabens were found, the allergens found and the badges earned. Ingredients outside the list are not recognised, an empty list gets no score, and packaging concentration thresholds are not checked.
* **REST API with IP-HMAC rate-limiting:** `wm-salon/v1/stuhlmiete` and `wm-salon/v1/inci-check` run the same two calculations for a form or an app, enforce strict schema and handler sanitization, answer 400 for missing or malformed input, and throttle anonymous traffic via HMAC-SHA256 hashed IP transients without storing raw IP addresses.
* **Block Bindings, GenerateBlocks & Developer Hooks:** registers WordPress 6.5+ Block Bindings source `wm-salon/metrics`, GenerateBlocks 2.x Dynamic Tag `{{wm_salon_metric}}`, and developer filters/actions (`wm_salon_stuhlmiete_calculation`, `wm_salon_inci_database`, `wm_salon_inci_result`, `wm_salon_ready`).
* **WP-CLI:** `wp salon status`, `wp salon analyze-inci "<ingredients>"` and `wp salon calculate-rent --revenue=<EUR> [--rate=<share>] [--fixed=<EUR>] [--vat=<percent>]`.
* **Translations & Opt-in Uninstallation:** ships 29 compiled `.po`/`.mo` language catalogs and preserves options and transients on uninstall by default unless `wm_salon_delete_data_on_uninstall` is explicitly enabled under Settings > General.

Zero-CDN: the stylesheet ships locally with the plugin and nothing is loaded from another host.

== Frequently Asked Questions ==

= Is technical or private support provided? =

No. This plugin comes with zero support (`0` support). Private inquiries sent via email, `arnoldwender.com`, or social/chat channels are not answered. Reproducible bugs belong solely on GitHub Issues (`https://github.com/arnoldwender/wm-wp-salon-sovereign-hub/issues`) with no guarantee of reply or resolution. The software is provided **"AS IS"** at your sole risk with zero warranty or liability (see `SUPPORT.md` in the repository).

== Screenshots ==

1. Whole-cent chair-rent split and VAT calculator rendered via [wm_salon_stuhlmiete]
2. Curated INCI ingredient checker matching sulphates, silicones, parabens and 8 fragrance allergens
3. General Settings opt-in field controlling uninstall cleanup (wm_salon_delete_data_on_uninstall)

== Changelog ==


= 1.2.1 =
* Fixed: The ingredient count of the INCI checker («%d Inhaltsstoff erkannt») repeated one form for several numbers in Croatian, Czech, Irish, Latvian, Lithuanian, Maltese, Polish, Romanian, Slovak and Slovenian (12 catalogs), for example Czech «Rozpoznáno 2 složek» and Polish «Rozpoznano składników: 2» next to an inflected singular; each number now gets the form its language uses (Czech «Rozpoznány 2 složky», Polish «Rozpoznano 2 składniki»). The plugin's code is unchanged.

= 1.2.0 =
* The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
* The `.wordpress-org/` banners show the current version.
* Removed: The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

= 1.1.3 =
* Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.
* Added root `SUPPORT.md`, GitHub Issue templates, explicit zero-support & "AS IS" liability notices in READMEs and `readme.txt`, and normalized canonical GPLv2+ `arnoldwender.com` / `arnoldwender/wm-wp-salon-sovereign-hub` links.
* Updated `.wordpress-org/` visual banners to personal `ARNOLD WENDER · OPEN SOURCE` branding.

= 1.1.2 =
* Add `.wordpress-org/` vector icons, banners (`772x250`, `1544x500`), live WordPress screenshots (`screenshot-1..3.png`) and `== Screenshots ==` descriptions.
* Update trilingual READMEs and `llms.txt` with live previews and direct plugin catalog link (`https://arnoldwender.com/wordpress-plugins/wm-salon-sovereign-hub/`).

= 1.1.1 =
* Point Plugin URI to the official plugin page.

= 1.1.0 =
* Added transient-based IP HMAC-SHA256 rate-limiting (`WM_Salon_REST_Controller::check_public_access`, HTTP 429) and payload size validation on `wm-salon/v1/stuhlmiete` and `wm-salon/v1/inci-check`.
* Added opt-in uninstallation lifecycle (`wm_salon_delete_data_on_uninstall` under Settings > General, disabled by default so plugin data is preserved unless opted in).
* Added WordPress 6.5+ Block Bindings source `wm-salon/metrics` and GenerateBlocks 2.x Dynamic Tag `{{wm_salon_metric}}`.
* Added developer filters (`wm_salon_stuhlmiete_calculation`, `wm_salon_inci_database`, `wm_salon_inci_result`, `wm_salon_rest_rate_limit`, `wm_salon_metric_value`) and actions (`wm_salon_ready`, `wm_salon_rest_calculated`).
* Translated and compiled all 29 `.po`/`.mo` language catalogs, added CycloneDX 1.6 `bom.json` and WordPress Playground blueprint.
* Computed booth-rent splits in whole cents with commercial half-up rounding across all PHP versions, and reported shortfall amounts when fixed rent exceeds turnover.
* Validated REST and shortcode inputs strictly (returning 400 or a form message instead of zeros, full shares or server errors) and preserved line breaks and parenthesised allergens in INCI lists.
* Moved shortcode styling into `assets/css/wm-salon-hub.css` using CSS custom properties with verified contrast ratios and inline SVG icons.

= 1.0.0 =
* Initial release: the two calculators as shortcodes, REST routes and WP-CLI commands.

== Upgrade Notice ==

= 1.2.1 =
Translation fix only: plural forms in 10 languages. No code, data or settings change.

= 1.2.0 =
Hub users: update WM Suite Hub to 1.2.0 together with this plugin. The hub contract moved to a new PHP namespace; until both are updated the plugin keeps working on its own but is not listed in the hub cockpit.
