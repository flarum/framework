# Changelog

## [1.0.0](https://github.com/flarum/approval/compare/v0.1.0-beta.16...v1.0.0)

### Changed
- Compatibility with Flarum v1.0.0.
- Simplified TagPolicy.

### Fixed
- Unpredictable behaviour existed with discussions marked `is_private` (https://github.com/flarum/approval/pull/26)

## [0.1.0-beta.16](https://github.com/flarum/approval/compare/v0.1.0-beta.15...v0.1.0-beta.16)

### Changed
- Updated admin category from moderation to feature (https://github.com/flarum/approval/pull/24)
- Moved locale files from translation pack to extension (https://github.com/flarum/approval/pull/22)

### Fixed
- Comment and discussion count not updated after post approval (https://github.com/flarum/approval/pull/16)

## [0.1.0-beta.15](https://github.com/flarum/approval/compare/v0.1.0-beta.14...v0.1.0-beta.15)

### Changed
- Updated composer.json and admin javascript for new admin area.
- Updated extend.php with latest extenders.

## [0.1.0-beta.14](https://github.com/flarum/approval/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Changed
- Updated mithril to version 2
- Load language strings correctly on en-/disable
- Updated JS dependencies

### Removed
- Removed AssertPermissionTrait (#19)

## [0.1.0-beta.13](https://github.com/flarum/approval/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Updated JS dependencies
- Stop using deprecated core events, use extenders instead
