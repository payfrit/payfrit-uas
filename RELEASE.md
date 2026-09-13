# Universal Amount Standard API 0.0.2

UAS 0.0.2 is an alpha release of a standalone standard and PHP reference
implementation for exact, currency-neutral amounts.

## Highlights

- Canonical `DECIMAL(28,8)` values represented as JSON strings.
- Dependency-free normalization, comparison, addition, subtraction,
  multiplication, and division.
- Half-up rounding at eight places for multiplication and division.
- Explicit-rate conversion with source currency, target currency, and RFC 3339
  rate timestamp.
- Curated metadata for 31 currencies, including decimal and cash-rounding
  conventions.
- Browser demo for normalization, arithmetic, conversion, and locale-aware
  presentation.
- Human-readable type and API specifications plus a JSON Schema.
- Expanded dependency-free test suite and CI across PHP 8.1 through 8.5.

## Run it

```sh
php tests.php
php -S 127.0.0.1:8080 -t public public/index.php
```

Open `http://127.0.0.1:8080/demo.html` for the interactive demo.

## Alpha scope

This release does not include authentication, persistence, hosted uptime, live
exchange-rate synchronization, historical rate storage, or server-side locale
formatting. Rates are supplied explicitly; the API never presents them as live.
