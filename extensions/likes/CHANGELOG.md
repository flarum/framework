# Changelog

## [0.1.0-beta.16](https://github.com/flarum/likes/compare/v0.1.0-beta.15...v0.1.0-beta.16)

### Changed
- Updated admin category from discussion to feature (https://github.com/flarum/likes/pull/26)
- Moved locale files from translation pack to extension (https://github.com/flarum/likes/pull/23)

## [0.1.0-beta.15](https://github.com/flarum/likes/compare/v0.1.0-beta.14...v0.1.0-beta.15)

### Changed
- Updated composer.json and admin javascript for new admin area.
- Updated to use newest extenders.

## [0.1.0-beta.14](https://github.com/flarum/likes/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Changed
- Updated mithril to version 2
- Load language strings correctly on en-/disable
- Updated JS dependencies

## [0.1.0-beta.13](https://github.com/flarum/likes/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Updated JS dependencies
- Stop using deprecated core events, use extenders instead

## [0.1.0-beta.12](https://github.com/flarum/likes/compare/v0.1.0-beta.9...v0.1.0-beta.12)

### Fixed

- Fix notifications being sent to deleted users ([726ddfe](https://github.com/flarum/likes/commit/726ddfe1b45f2752f6179848a7128e520b1860fc))

## [0.1.0-beta.9](https://github.com/flarum/likes/compare/v0.1.0-beta.8.1...v0.1.0-beta.9)

### Changed
- Replace event subscribers (that resolve services too early) with listeners ([0b3fbc5](https://github.com/flarum/likes/commit/0b3fbc5813a5b52e8b81aaf557dcf1ec37d1481a))
- Compatibility with Laravel 5.7 ([c2281d1](https://github.com/flarum/likes/commit/c2281d14f6e9268c6eb306781ffb43d74095cc9e))

## [0.1.0-beta.8.1](https://github.com/flarum/likes/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix dropping foreign keys in `down` migrations ([4e92f20](https://github.com/flarum/likes/commit/4e92f20d7a18efc08bb24e0767014e4ba689c805))
- Truncate notification excerpts ([55524aa](https://github.com/flarum/likes/commit/55524aa2e87951c858bf20d960e1f4f9a86a103f))
- Prevent possible crash on discussion view ([84bcc0e](https://github.com/flarum/likes/commit/84bcc0e283295b6109d4bc1449d8ba06b156ca01))
