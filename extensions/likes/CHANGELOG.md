# Changelog

## [0.1.0-beta.9](https://github.com/flarum/likes/compare/v0.1.0-beta.8.1...v0.1.0-beta.9)

### Changed
- Replace event subscribers (that resolve services too early) with listeners ([0b3fbc5](https://github.com/flarum/likes/commit/0b3fbc5813a5b52e8b81aaf557dcf1ec37d1481a))
- Compatibility with Laravel 5.7 ([c2281d1](https://github.com/flarum/likes/commit/c2281d14f6e9268c6eb306781ffb43d74095cc9e))

## [0.1.0-beta.8.1](https://github.com/flarum/likes/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix dropping foreign keys in `down` migrations ([4e92f20](https://github.com/flarum/likes/commit/4e92f20d7a18efc08bb24e0767014e4ba689c805))
- Truncate notification excerpts ([55524aa](https://github.com/flarum/likes/commit/55524aa2e87951c858bf20d960e1f4f9a86a103f))
- Prevent possible crash on discussion view ([84bcc0e](https://github.com/flarum/likes/commit/84bcc0e283295b6109d4bc1449d8ba06b156ca01))
