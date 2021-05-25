# Changelog

## [1.0.0](https://github.com/flarum/sticky/compare/v0.1.0-beta.16...v1.0.0)

### Changed
- Compatibility with Flarum v1.0.0.
- List controller now takes into account default sort, even if it is changed from an extension 

## [0.1.0-beta.16](https://github.com/flarum/sticky/compare/v0.1.0-beta.15.1...v0.1.0-beta.16)

### Changed
- Updated admin category from moderation to feature (https://github.com/flarum/sticky/pull/27)
- Moved locale files from translation pack to extension (https://github.com/flarum/sticky/pull/21)
- Make `is_sticky` and `last_posted_at` columns an index for improved performance (https://github.com/flarum/sticky/pull/23)

### Fixed
- Rendering of excerpts failing (https://github.com/flarum/sticky/pull/24)
- Excerpts showing on user profile page for stickied discussions looks erratic (https://github.com/flarum/sticky/pull/25)

## [0.1.0-beta.15.1](https://github.com/flarum/sticky/compare/v0.1.0-beta.14...v0.1.0-beta.15.1)

### Fixed
- Fixed security vulnerability for HTML injection in the first post of stickied discussions. See https://github.com/flarum/sticky/security/advisories/GHSA-h3gg-7wx2-cq3h.

## [0.1.0-beta.15](https://github.com/flarum/sticky/compare/v0.1.0-beta.14...v0.1.0-beta.15)

### Changed
- Updated composer.json and admin javascript for new admin area.
- Updated to use newest extenders.

## [0.1.0-beta.14](https://github.com/flarum/sticky/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Changed
- Updated mithril to version 2
- Load language strings correctly on en-/disable
- Updated JS dependencies

### Fixed
- Excerpts of unread stickied discussions triggers an error (#20)

## [0.1.0-beta.13](https://github.com/flarum/sticky/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Updated JS dependencies

## [0.1.0-beta.9](https://github.com/flarum/sticky/compare/v0.1.0-beta.8...v0.1.0-beta.9)

### Changed
- Compatibility with Laravel 5.7 ([196b2d1](https://github.com/flarum/sticky/commit/196b2d1bfd59ccbe440b4a56bf28f9d17e3d120e))

