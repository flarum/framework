# Changelog

## [0.1.0-beta.8.1](https://github.com/flarum/core/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix live output in `migrate:reset` command ([f591585](https://github.com/flarum/core/commit/f591585d02f8c4ff0211c5bf4413dd6baa724c05))
- Fix search with database prefix ([7705a2b](https://github.com/flarum/core/commit/7705a2b7d751943ef9d0c7379ec34f8530b99310))
- Fix invalid join time of admin user created by installer ([57f73c9](https://github.com/flarum/core/commit/57f73c9638eeb825f9e336ed3c443afccfd8995e))
- Ensure InnoDB engine is used for all tables ([fb6b51b](https://github.com/flarum/core/commit/fb6b51b1cfef0af399607fe038603c8240800b2b))
- Fix dropping foreign keys in `down` migrations ([57d5846](https://github.com/flarum/core/commit/57d5846b647881009d9e60f9ffca20b1bb77776e))
- Fix discussion list scroll position not being maintained when hero is not visible ([40dc6ac](https://github.com/flarum/core/commit/40dc6ac604c2a0973356b38217aa8d09352daae5))
- Fix empty meta description tag ([88e43cc](https://github.com/flarum/core/commit/88e43cc6940ee30d6529e9ce659471ec4fb1c474))
- Remove empty attributes on `<html>` tag ([796b577](https://github.com/flarum/core/commit/796b57753d34d4ea741dbebcbc550b17808f6c94))
