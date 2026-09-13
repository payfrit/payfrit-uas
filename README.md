# Universal Amount Standard API

Version `0.0.2` is a standalone, dependency-free PHP API for exact,
currency-neutral amounts. It is independent of Payfrit and is intended to be
consumed by Payfrit and other products.

This is an alpha developer release, not a hosted production service. The
reference server runs locally with PHP and has no persistence or authentication
yet.

## Requirements

- PHP 8.1 or newer
- No external PHP extensions or Composer packages are required to run the
  reference server

## Run locally

```sh
php -S 127.0.0.1:8080 -t public public/index.php
```

Then open `http://127.0.0.1:8080/v1`.

The interactive capability demo is at `http://127.0.0.1:8080/demo.html`.
Formatting uses the browser's locale data and is presentation-only; the API's
canonical values remain decimal strings.

## From a fresh clone

```sh
git clone https://github.com/payfrit/payfrit-uas.git
cd payfrit-uas
php tests.php
php -S 127.0.0.1:8080 -t public public/index.php
```

The API status is available at `/v1`; the interactive demo is available at
`/demo.html`.

## Data type specification

The `UniversalAmount` contract is documented in
[`docs/universal-amount-type-0.0.2.md`](docs/universal-amount-type-0.0.2.md).
The machine-readable JSON Schema is at
[`schema/universal-amount.schema.json`](schema/universal-amount.schema.json).
The specification defines the canonical eight-place decimal string, validation
rules, arithmetic semantics, currency context, conversion boundaries, and
compatibility guidance.

## Example

```sh
curl -s -X POST http://127.0.0.1:8080/v1/amounts/normalize \
  -H 'Content-Type: application/json' \
  -d '{"amount":"12.34"}'
```

Response:

```json
{"ok":true,"data":{"amount":"12.34000000"}}
```

## Release scope

This first release provides canonical amount normalization, exact addition,
subtraction, multiplication, division, currency metadata, and rate-explicit
conversion. It does not yet include persistence, authentication, live
exchange-rate synchronization, or server-side localized formatting.

Exact decimal arithmetic is a core UAS guarantee because consumers must be able
to calculate totals, nested modifier prices, fees, balances, payouts, and
allocations without binary floating-point errors. The arithmetic endpoint is
public in 0.0.2 as a simple demonstration and conformance surface; arithmetic
may become an internal library operation in a future narrower API contract.
