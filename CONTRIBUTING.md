# Contributing to WM Salon Sovereign Hub

Thank you for your interest in contributing to **WM Salon Sovereign Hub**, part of the WM Suite plugin suite.

---

## Zero Support & Issue Scope

**Zero technical, configuration, or private support is provided.** See [SUPPORT.md](SUPPORT.md):

- Report reproducible code defects **exclusively** via public [GitHub Issues](https://github.com/arnoldwender/wm-wp-salon-sovereign-hub/issues).
- **Do not** contact the author privately via email, `arnoldwender.com`, or direct messages — private inquiries are never answered.
- Issues and pull requests carry no response or maintenance SLA, and the software is provided strictly **"AS IS"** without warranty or liability.

---

## Development Standards

1. **PHP 8.1+ Strict Typing:** Every PHP file must start with `declare(strict_types=1);`.
2. **Zero-CDN Architecture:** No external resources (CDNs, Google Fonts, external telemetry) may be loaded at runtime.
3. **Money in whole cents:** `WM_Salon_Stuhlmiete_Ledger::calculate_split()` rounds each amount to cents once and then computes in integers with half-up rounding. Do not reintroduce `round()` on float results: 30 % of 0.75 € came out as 0.23 € on PHP 8.3 and 0.22 € on PHP 8.4. Test group 1 compares every amount from 0.01 € to 200 € with an integer reference.
4. **The default is never "all good":** missing or malformed input answers 400 instead of a result with zeros, and an ingredient list without an ingredient gets no score and no badge.
5. **Docs measured against the code:** `README.md` and `DEVELOPERS.md` describe what the plugin registers. A new shortcode, route, command or option is documented in the same pull request; nothing is documented that does not exist.
6. **No legal verdicts:** the calculators return figures. Text that promises legal certainty, GoBD conformity, a duty to declare an allergen or any other regulatory assessment needs a source and Arnold's approval.
7. **Accessibility:** text at least 4.5:1, field borders and buttons at least 3:1, 48px targets, a visible focus outline (test group 5 computes the ratios from `assets/css/wm-salon-hub.css`); colours and dimensions only as custom properties; icons are inline SVG, never emoji.
8. **Tests:** a fix comes with an assertion that fails without it and an entry in `tests/mutations.php`.

---

## Development Workflow

```bash
# 1. Clone the repository
git clone https://github.com/arnoldwender/wm-wp-salon-sovereign-hub.git
cd wm-salon-sovereign-hub

# 2. Run the standalone suite (no WordPress needed) and the mutation check
php tests/test-suite.php
bash tests/run-mutations.sh

# 3. Plugin Check before a pull request (the plugin-check plugin, in a WordPress install)
wp plugin check wm-salon-sovereign-hub
```

---

## Commit Conventions

- Use the format `[Feature] ...`, `[Fix] ...`, `[Security] ...`, `[Docs] ...`.

For support and liability rules, read [SUPPORT.md](SUPPORT.md).
