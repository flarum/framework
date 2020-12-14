# Changelog

## [0.1.0-beta.15](https://github.com/flarum/flags/compare/v0.1.0-beta.14.1...v0.1.0-beta.15)

### Changed
- Updated composer.json and admin javascript for new admin area.
- Updated to use newest extenders.

## [0.1.0-beta.14.1](https://github.com/flarum/flags/compare/v0.1.0-beta.14...v0.1.0-beta.14.1)

### Fixed
- Flags cache was instantiated prematurely causing incorrect flags count (#31)

## [0.1.0-beta.14](https://github.com/flarum/flags/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Changed
- Updated mithril to version 2
- Load language strings correctly on en-/disable
- Updated JS dependencies

## [0.1.0-beta.13](https://github.com/flarum/flags/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Updated JS dependencies
- Stop using deprecated core events, use extenders instead

## [0.1.0-beta.12](https://github.com/flarum/flags/compare/v0.1.0-beta.10...v0.1.0-beta.12)

### Changed

- Larger flag modal, disallow users to flag their own posts, increase flag message size, 
allow comment on more reasons, disabled submit on other without comment ([5292e6c](https://github.com/flarum/flags/commit/5292e6cf8a3d4610171f44a6feebb7b31794dd11))

## [0.1.0-beta.10](https://github.com/flarum/flags/compare/v0.1.0-beta.9...v0.1.0-beta.10)

### Fixed
- Guests visiting `api/flags` trigger an exception ([d4680a0](https://github.com/flarum/flags/pull/19/commits/d4680a041afdb286ac85865e5b1f51345a6f9384))

## [0.1.0-beta.9](https://github.com/flarum/flags/compare/v0.1.0-beta.8.1...v0.1.0-beta.9)

### Changed
- Replace event subscribers (that resolve services too early) with listeners ([2f3417b](https://github.com/flarum/flags/commit/2f3417b863793b918d64c51bcdd65a77e05ffdb9))
- Compatibility with Laravel 5.7 ([bd00270](https://github.com/flarum/flags/commit/bd002708c57b5297b1796233d04d18876523ae49))

### Fixed
- API serialization failed when posts for a discussion were not loaded and needed ([9803914](https://github.com/flarum/flags/commit/98039144984eab4e43be7316ecc29fc56959b2c3))

## [0.1.0-beta.8.1](https://github.com/flarum/flags/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix dropping foreign keys in `down` migrations ([e17bd03](https://github.com/flarum/flags/commit/e17bd037b011aac6ef3e38a44ab859a25cd1f763))
