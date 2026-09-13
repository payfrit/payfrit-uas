# Universal Amount Standard API

Version `0.0.1` is a standalone, dependency-free PHP API for exact,
currency-neutral amounts. It is independent of Payfrit and is intended to be
consumed by Payfrit and other products.

## Run locally

```sh
php -S 127.0.0.1:8080 -t public public/index.php
```

Then open `http://127.0.0.1:8080/v1`.

The interactive capability demo is at `http://127.0.0.1:8080/demo.html`.
Formatting uses the browser's locale data and is presentation-only; the API's
canonical values remain decimal strings.

## Release scope

This first release provides canonical amount normalization, exact addition and
subtraction, currency metadata, and rate-explicit conversion. It does not yet
include persistence, authentication, live rate-provider synchronization, or
localized formatting.

Exact decimal arithmetic is a core UAS guarantee because consumers must be able
to calculate totals, nested modifier prices, fees, balances, payouts, and
allocations without binary floating-point errors. The arithmetic endpoint is
public in 0.0.1 as a simple demonstration and conformance surface; arithmetic
may become an internal library operation in a future narrower API contract.
