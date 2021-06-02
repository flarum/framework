# Changelog

## [1.0.1](https://github.com/flarum/markdown/compare/v1.0.0...v1.0.1)

### Fixed
- Link button is not visible on the markdown toolbar (https://github.com/flarum/markdown/pull/29)

## [1.0.0](https://github.com/flarum/markdown/compare/v0.1.0-beta.16.1...v1.0.0)

### Changed
- Compatibility with Flarum v1.0.0.
- GitHub markdown toolbar moves to flarum/core (https://github.com/flarum/markdown/pull/28)
- Simplification of toolbar and button system (https://github.com/flarum/markdown/pull/28)

### Fixed
- Toolbar buttons submits the form
- Tooltips generate a deprecation warning

### Removed
- Dropped mdarea (https://github.com/flarum/markdown/pull/28)

## [0.1.0-beta.16.1](https://github.com/flarum/markdown/compare/v0.1.0-beta.16...v0.1.0-beta.16.1)

### Added
- Admin setting to disable mdarea (https://github.com/flarum/markdown/pull/27)

## [0.1.0-beta.16](https://github.com/flarum/markdown/compare/v0.1.0-beta.15...v0.1.0-beta.16)

### Added
- Editor Driver support ([ba3e1fb](https://github.com/flarum/markdown/commit/ba3e1fb528ce7e85bde27753f6c1cce3b03fe9d3))

### Changed
- Updated admin category from formatting to feature (https://github.com/flarum/markdown/pull/25)
- Moved locale files from translation pack to extension (https://github.com/flarum/markdown/pull/21)

### Fixed
- Double quote marks cause inconvenience typing ([6d8ea34](https://github.com/flarum/markdown/commit/6d8ea342061fdd2ab267cee93b9f5ade69149123))

## [0.1.0-beta.15](https://github.com/flarum/markdown/compare/v0.1.0-beta.14...v0.1.0-beta.15)

### Changed
- Updated composer.json and admin javascript for new admin area.

## [0.1.0-beta.14](https://github.com/flarum/markdown/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Changed
- Switch image and link defaults (#11)
- Updated mithril to version 2
- Load language strings correctly on en-/disable

### Removed
- Support for IE 11 (#18)

## [0.1.0-beta.13](https://github.com/flarum/markdown/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Updated JS dependencies

### Fixed
- Fixed not hiding other html entities than mentions (#14)

## [0.1.0-beta.12](https://github.com/flarum/markdown/compare/v0.1.0-beta.10...v0.1.0-beta.12)

### Fixed
- Mentions could be seen within spoiler blocks (#13)

## [0.1.0-beta.10](https://github.com/flarum/markdown/compare/v0.1.0-beta.9...v0.1.0-beta.10)

### Added
- The toolbar now has an image button (#8)

### Fixed
- Unusable toolbar in IE11 (#8)
- Tabbing out of the post composer was not possible (#8)

## [0.1.0-beta.9](https://github.com/flarum/markdown/compare/v0.1.0-beta.8...v0.1.0-beta.9)

### Fixed
- Markdown toolbar did not work on IE11 ([31c064f](https://github.com/flarum/markdown/commit/31c064f0c6c945083bc0ebc50cb3e44a676f40e2) and [e762293](https://github.com/flarum/markdown/commit/e7622938b1422e89a50feeb52c9c9ef7b38db95a))
