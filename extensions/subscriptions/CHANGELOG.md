# Changelog

## [0.1.0-beta.15](https://github.com/flarum/subscriptions/compare/v0.1.0-beta.14...v0.1.0-beta.15)

### Changed
- Updated composer.json and admin javascript for new admin area.
- Updated to use newest extenders.

## [0.1.0-beta.14](https://github.com/flarum/subscriptions/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Added
- Meta title now available for following page (#23)

### Changed
- Updated mithril to version 2
- Load language strings correctly on en-/disable
- Updated JS dependencies
- Notification emails now are translatable (#30)

### Fixed
- Button colors for dark mode were too vague (#26)

## [0.1.0-beta.13](https://github.com/flarum/subscriptions/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Updated JS dependencies

### Fixed
- Send "new post" email through queue (flarum/core#1869)

## [0.1.0-beta.12](https://github.com/flarum/subscriptions/compare/v0.1.0-beta.9...v0.1.0-beta.12)

### Fixed
- WhereExists performed extremely bad, impacting the following gambit (#22)

## [0.1.0-beta.9](https://github.com/flarum/subscriptions/compare/v0.1.0-beta.8...v0.1.0-beta.9)

### Changed
- Replace event subscribers (that resolve services too early) with listeners ([7ac73b8](https://github.com/flarum/subscriptions/commit/7ac73b834023e997147d4dd9c851a2ea73deba4b))

### Fixed
- Post content with code blocks could corrupt notification emails ([16a441c](https://github.com/flarum/subscriptions/commit/16a441c8a85fda824b39acd5ec58a6abe3a8d760))
- JS: Vulnerable lodash dependency ([782e31c](https://github.com/flarum/subscriptions/commit/782e31c56a519aa74f80cb8024c7b912a7fdb925))

