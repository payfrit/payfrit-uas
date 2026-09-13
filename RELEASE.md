# Release checklist

## 0.0.2 scope

This is an alpha developer release of the standalone UAS reference API. It is
safe to demo and integrate against, but it is not a hosted production service.

Before announcing:

- confirm the repository is public;
- confirm `README.md`, `LICENSE`, `CHANGELOG.md`, and API/type specifications
  are present;
- confirm the human-readable `UniversalAmount` specification and JSON Schema
  agree;
- run `php tests.php`;
- run the local server and open `/demo.html`;
- verify the GitHub release tag and commit;
- state clearly that persistence, authentication, live rates, and hosted
  uptime are not included.

## 0.0.2 release notes

Universal Amount Standard API `0.0.2` is a packaging and correctness release.

### Added

- Standalone project packaging with Composer metadata, MIT license, and a
  public release checklist.
- GitHub Actions validation across PHP 8.1, 8.2, and 8.3.
- Machine-readable `UniversalAmount` JSON Schema for the `DECIMAL(28,8)` wire
  contract.
- Interactive browser demo for normalization, arithmetic, conversion, locale
  formatting, and currency metadata.
- Expanded curated currency catalog with global two-decimal currencies plus
  zero- and three-decimal examples.

### Corrected

- Enforced the 20-digit integer limit implied by `DECIMAL(28,8)`.
- Added half-up rounding when multiplication reduces back to eight places.
- Added division-by-zero protection.
- Added conversion validation for supported currency codes, positive rates,
  and valid rate timestamps.
- Made static demo assets work with the documented PHP development command.

### Explicitly alpha

This release does not provide persistence, authentication, rate-provider
synchronization, hosted uptime, request limits, or server-side locale
formatting. The arithmetic endpoints are demonstration/conformance surfaces;
consumers should not build domain workflows directly on them.

## Future release gates

Do not describe the API as production-ready until it has authentication,
request limits, persistence, live-rate provenance, observability, deployment
configuration, and compatibility tests for every published contract.
