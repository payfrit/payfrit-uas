# Changelog

## 0.0.2 — packaging and correctness release

- Added standalone Composer metadata, MIT licensing, release guidance, and
  GitHub Actions validation.
- Added a machine-readable `UniversalAmount` JSON Schema and expanded public
  type documentation.
- Added an interactive browser demo covering normalization, arithmetic,
  conversion, formatting, and currency metadata.
- Made demo currency selectors load the published catalog dynamically and
  expanded locale formatting examples with readable labels.
- Expanded the curated currency catalog with global currencies and zero-, two-,
  and three-decimal examples.
- Enforced `DECIMAL(28,8)` magnitude limits and half-up multiplication
  rounding.
- Added validation for division by zero, supported currency codes, positive
  conversion rates, and rate timestamps.
- Normalized arithmetic negative zero and rejected extra operands for binary
  arithmetic operations.
- Fixed static demo asset serving with the documented PHP server command.
- This remains an alpha developer release without persistence, authentication,
  live rate synchronization, or hosted uptime.

## 0.0.1 — initial public release

- Added exact fixed-point `UniversalAmount` normalization.
- Added decimal-safe addition and subtraction.
- Added decimal-safe multiplication and division.
- Added explicit-rate multiplication/conversion.
- Added initial currency metadata for USD, EUR, GBP, JPY, and KWD.
- Added versioned HTTP endpoints and JSON Schema.
- Added dependency-free PHP smoke tests.
