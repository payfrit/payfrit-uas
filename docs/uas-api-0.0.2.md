# Universal Amount Standard API 0.0.2

This is an alpha release of the independent UAS API. Payfrit may consume it,
but Payfrit is not part of the API's domain model.

The foundational data type is specified separately in
`docs/universal-amount-type-0.0.2.md`.

Exact arithmetic is part of the UAS implementation guarantee, even where a
consumer does not call an arithmetic endpoint directly. It protects totals,
fees, nested modifier calculations, balances, payouts, conversions, and
allocation. The arithmetic endpoints are included in 0.0.2 primarily as a
demonstration and conformance surface.

## Contract

Canonical amounts are decimal strings normalized to eight fractional places.
Amount and rate inputs must also be JSON strings; JSON numbers are rejected.
Input helpers accept shorthand strings such as `.1`, but responses always use
canonical strings such as `0.10000000`.

### `GET /v1`

Returns release metadata and capabilities.

### `GET /v1/currencies`

Returns a curated set of common global currencies. Each entry contains an ISO
4217 code, English name, conventional decimal places, and cash-rounding
increment. Decimal and cash metadata follows
[Unicode CLDR 48.2 supplemental currency data](https://github.com/unicode-org/cldr/blob/release-48-2/common/supplemental/supplementalData.xml);
this endpoint is not yet a complete ISO 4217 catalog.

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
one canonical amount. Multiplication and division round half-up at the eighth
fractional place. For example, adding the values above returns `12.34500000`.

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

`rateTimestamp` must be an RFC 3339 string. The response canonicalizes the
amount, rate, and currency codes and preserves the timestamp. It produces an
eight-place UAS result; it does not round that result to the target currency's
minor unit.

Invalid input returns HTTP `400` with `invalid_json` or `invalid_request`.
Unknown routes return HTTP `404` with `not_found`.

## Not in 0.0.2

Persistence, API authentication, live exchange-rate synchronization,
server-side localized formatting, provider-specific minor-unit conversion, and
historical rate storage are planned subsequent releases.
