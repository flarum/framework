# Changelog

## [0.1.0-beta.15](https://github.com/flarum/pusher/compare/v0.1.0-beta.14.1...v0.1.0-beta.15)

### Changed
- Updated composer.json and admin javascript for new admin area.
- Updated to use newest extenders.

## [0.1.0-beta.14.1](https://github.com/flarum/pusher/compare/v0.1.0-beta.14...v0.1.0-beta.14.1)

### Fixed
- Children were incorrectly passed to show update highlight
- Update discussion list caused an error (#27)

## [0.1.0-beta.14](https://github.com/flarum/pusher/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Changed
- Updated mithril to version 2
- Load language strings correctly on en-/disable
- Updated JS dependencies

### Fixed
- Publish no events for tags when flarum/tags isn't installed (#25)

## [0.1.0-beta.13](https://github.com/flarum/pusher/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Use different CDN for loading Pusher JS library (#20)
- Updated JS dependencies

## [0.1.0-beta.9](https://github.com/flarum/pusher/compare/v0.1.0-beta.8.1...v0.1.0-beta.9)

### Changed
- Replace event subscribers (that resolve services too early) with listeners ([da0f0af](https://github.com/flarum/pusher/commit/da0f0afb24bae39535b4beaf750f311c403adef1) and [28a70ff](https://github.com/flarum/pusher/commit/28a70ff074014bc75acee6eff7a74faecf5ae341))

## [0.1.0-beta.8.1](https://github.com/flarum/pusher/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix broken functionality ([00b127c](https://github.com/flarum/pusher/commit/00b127c576e5554bc04b491ec47ae57f8525fac3))
