# Changelog

## [0.1.0-beta.10](https://github.com/flarum/mentions/compare/v0.1.0-beta.9...v0.1.0-beta.10)

### Fixed
- Mentions for usernames containing underscores and other special characters could link to wrong users (#37)

## [0.1.0-beta.9](https://github.com/flarum/mentions/compare/v0.1.0-beta.8.1...v0.1.0-beta.9)

### Changed
- Replace event subscribers (that resolve services too early) with listener-based extender ([4b34e50](https://github.com/flarum/mentions/commit/4b34e5096d1a2cef127b41756ebd7b4eb46bb0dd) and [9165321](https://github.com/flarum/mentions/commit/91653218eaeb031f644b1763297097b03c6aaac1))
- Compatibility with Laravel 5.7 ([3ef5ac0](https://github.com/flarum/mentions/commit/3ef5ac0cce350aff9db93c28c8ba3432dab86bcd))
- Use display name instead of username in emails ([acc0516](https://github.com/flarum/mentions/commit/acc0516a18d691095dc3657648f1bc16d0c5f51f)) 

### Fixed
- Post content with code blocks could corrupt notification emails ([d0ffe7b](https://github.com/flarum/mentions/commit/d0ffe7b9f1eb48e03ad546b28199322cd2011650))

## [0.1.0-beta.8.1](https://github.com/flarum/mentions/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix dropping foreign keys in `down` migrations ([53b685a](https://github.com/flarum/mentions/commit/53b685a8539753c88d72eb92237749e3823b3bbf))
- Truncate notification excerpts ([7ebd527](https://github.com/flarum/mentions/commit/7ebd527487df12187a3471f5b4dfe7eaac394c7a))
