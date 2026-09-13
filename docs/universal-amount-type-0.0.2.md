# UniversalAmount Data Type 0.0.2

`UniversalAmount` is the foundational data type of the independent Universal
Amount Standard API. It represents an exact value without assuming that USD or
any other currency is the platform's base unit.

## Canonical representation

The canonical value is a signed fixed-point decimal with exactly eight digits
after the decimal point:

```text
DECIMAL(28,8)
```

The wire representation is always a JSON string:

```json
{ "amount": "12.34000000" }
```

The machine-readable schema is available at
`uas-api/schema/universal-amount.schema.json`.

## Invariants

- `amount` is required and is a decimal string, never a JSON number.
- The value has exactly eight fractional digits after normalization.
- Leading zeroes are removed from the integer part, except for zero itself.
- Positive signs are not emitted.
- Negative zero is normalized to `0.00000000`.
- Exponential notation, `NaN`, infinity, grouping separators, currency symbols,
  whitespace inside the value, and excess precision are rejected.
- The maximum representable magnitude is bounded by the selected storage
  implementation's `DECIMAL(28,8)` contract.

## Currency context

`UniversalAmount` does not contain a mandatory currency code. A containing
resource must supply context when currency has meaning:

```json
{
  "amount": "12.34000000",
  "currencyContext": "USD"
}
```

The context may identify a business pricing currency, wallet currency, source
currency, viewer display currency, or provider settlement currency. These are
different roles and must not be conflated.

Any amount that may later be converted must retain an immutable source-context
reference or historical snapshot. A viewer's display preference is never a
source currency.

## Arithmetic

Addition, subtraction, comparison, and allocation operate on normalized UAS
values. Implementations must use decimal-safe arithmetic and must not convert
through binary floating point.

Multiplication by a rate or quantity produces a value normalized to eight
places. Rounding is not implicit in the type; the caller must name the boundary
rounding policy when reducing precision.

Allocation must be deterministic and all allocated values must sum exactly to
the original amount. The API's later allocation contract will use a documented
largest-remainder rule.

## Display and conversion

Formatting is derived from `UniversalAmount` and context. Formatted strings are
never valid arithmetic inputs.

Conversion requires all of the following:

- source amount;
- source currency/context;
- target currency/context;
- dated exchange rate;
- rounding mode; and
- target currency precision or provider minor-unit rules.

The canonical UAS amount is not changed by a display-currency preference. A
conversion record must preserve the rate, timestamp, rounding policy, and
resulting boundary amount so it can be reproduced.

## Compatibility guidance

Implementations may map `UniversalAmount` to SQL decimal, arbitrary-precision
integer-plus-scale, or another native decimal type. The mapping is compatible
only when it preserves the wire and arithmetic semantics above.

Legacy cents and decimal-dollar fields are projections, not alternate
canonical forms. A value in a two-decimal currency may be projected to cents,
but a universal amount must not be defined as cents.
