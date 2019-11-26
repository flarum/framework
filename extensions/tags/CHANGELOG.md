# Changelog

## [0.1.0-beta.11](https://github.com/flarum/tags/compare/v0.1.0-beta.10...v0.1.0-beta.11)

### Fixed
- Tag change events triggered errors for deleted tags ([e5694e5](https://github.com/flarum/tags/pull/66/commits/e5694e51ef7851523ac6e467b4d7d98d471fd997))

## [0.1.0-beta.10](https://github.com/flarum/tags/compare/v0.1.0-beta.9...v0.1.0-beta.10)

### Added
- SEO: The tags page now has a `rel="canonical"` meta tag, preventing duplicate content (#64)
- SEO: The tags page now has server-rendered content, for better indexing by search engines (#64)

## [0.1.0-beta.9](https://github.com/flarum/tags/compare/v0.1.0-beta.8.2...v0.1.0-beta.9)

### Added
- Allow configuration of icon per tag ([e1a0ff8](https://github.com/flarum/tags/commit/e1a0ff8e0f726fbfe26fa47aea4a0555b109aad0))

### Changed
- Replace event subscribers (that resolve services too early) with listeners ([73c0626](https://github.com/flarum/tags/commit/73c0626e722d2be2b82804eec5746646b64b0c44))
- Compatibility with Laravel 5.7 ([cb683f3](https://github.com/flarum/tags/commit/cb683f37e689a03b25e43e47447025de8e127a56))
- Update html5sortable library ([e8104a6](https://github.com/flarum/tags/commit/e8104a623edff6560c544972b2171faf050ec2ab))

### Fixed
- JS: Vulnerable lodash dependency ([c80cbe8](https://github.com/flarum/tags/commit/c80cbe8ae7063d1c18784e983e9789554dbe4e03))
- Search crashed when searched tag did not exist ([3d6921b](https://github.com/flarum/tags/commit/3d6921bdd257c0f17ea36bd8c1f352670fef66e8))
- Discussions from hidden tags weren't showing when gambits were used ([7275c39](https://github.com/flarum/tags/commit/7275c395799dac0f420aa14afccb1f125622af08))

## [0.1.0-beta.8.2](https://github.com/flarum/tags/compare/v0.1.0-beta.8.1...v0.1.0-beta.8.2)

### Fixed
- Fix dropping foreign keys in `down` migrations ([cad9741](https://github.com/flarum/tags/commit/cad97410e53854d58fefd01916ba3a1c3bd5ba3d))
- Fix `INVALID DATE` errors on tags page ([8d4d01c](https://github.com/flarum/tags/commit/8d4d01c61079fecf84608dda1c64d112f5d9be34))
