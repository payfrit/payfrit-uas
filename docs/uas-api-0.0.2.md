# Universal Amount Standard API 0.0.2

This is the first public release of the independent UAS API. Payfrit may
consume it, but Payfrit is not part of the API's domain model.

The foundational data type is specified separately in
`docs/universal-amount-type-0.0.2.md`.

Exact arithmetic is part of the UAS implementation guarantee, even where a
consumer does not call an arithmetic endpoint directly. It protects totals,
fees, nested modifier calculations, balances, payouts, conversions, and
allocation. The `/v1/amounts/add` endpoint is included in 0.0.2 primarily as a
demonstration and conformance surface.

## Contract

Canonical amounts are decimal strings normalized to eight fractional places.
Clients must never send JSON floating-point money values.

### `GET /v1`

Returns release metadata and capabilities.

### `GET /v1/currencies`

Returns supported currency metadata, including conventional decimal places and
cash rounding. The initial release includes a curated set of common global
currencies; it is not yet a complete ISO 4217 catalog.

### `POST /v1/amounts/normalize`

Request:

```json
{ "amount": "12.34" }
```

Response:

```json
{ "ok": true, "data": { "amount": "12.34000000" } }
```

### `POST /v1/amounts/add`, `/subtract`, `/multiply`, `/divide`

Request:

```json
{ "amounts": ["12.34000000", "0.00500000"] }
```

The request contains two amounts for subtraction, multiplication, and division;
addition also accepts an array of more than two amounts. The response contains
one canonical amount. Division rounds half-up at the eighth fractional place.
For example, adding the values above returns `12.34500000`.

### `POST /v1/conversions`

Conversion is explicit-rate only in 0.0.2. The API does not claim that a rate
is current or fetch a provider automatically.

```json
{
  "amount": "12.34000000",
  "sourceCurrency": "USD",
  "targetCurrency": "EUR",
  "rate": "0.92000000",
  "rateTimestamp": "2026-09-12T00:00:00Z"
}
```

The response preserves the supplied source, target, rate, and timestamp.

## Not in 0.0.2

Persistence, API authentication, live exchange-rate synchronization, localized
formatting, provider-specific minor-unit conversion, and historical rate
storage are planned subsequent releases.
