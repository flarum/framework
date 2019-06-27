# Changelog

## Unreleased

### Added
- New `hasPermission()` helper method for `Group` objects ([9684fbc](https://github.com/flarum/core/commit/9684fbc4da07d32aa322d9228302a23418412cb9))
- Expose supported mail drivers in IoC container ([208bad3](https://github.com/flarum/core/commit/208bad393f37bfdb76007afcddfa4b7451563e9d))
- More test for some API endpoints ([1670590](https://github.com/flarum/core/commit/167059027e5a066d618599c90164ef1b5a509148))
- The `Formatter\Rendering` event now receives the HTTP request instance as well ([0ab9fac](https://github.com/flarum/core/commit/0ab9facc4bd59a260575e6fc650793c663e5866a))
- More and better validation in installer UIs
- Check and enforce minimum MariaDB ([7ff9a90](https://github.com/flarum/core/commit/7ff9a90204923293adc520d3c02dc984845d4f9f))
- Revert publication of assets when installation fails ([ed9591c](https://github.com/flarum/core/commit/ed9591c16fb2ea7a4be3387b805d855a53e0a7d5))
- Benefit from Laravel's database reconnection logic in long-running tasks ([e0becd0](https://github.com/flarum/core/commit/e0becd0c7bda939048923c1f86648793feee78d5))
- The "vendor path" (where Composer dependencies can be found) can now be configured ([5e1680c](https://github.com/flarum/core/commit/5e1680c458cd3ba274faeb92de3ac2053789131e))

### Changed
- Performance: Actually cache translations on disk ([0d16fac](https://github.com/flarum/core/commit/0d16fac001bb735ee66e82871183516aeac269b7))
- Allow per-site extenders to override extension extenders ([ba594de](https://github.com/flarum/core/commit/ba594de13a033480834d53d73f747b05fe9796f8))
- Do not resolve objects from the IoC container (in service providers and extenders) until they are actually used
- Replace event subscribers (that resolve objects from the IoC container) with listeners (that resolve lazily)
- Use custom service provider for Mail component ([ac5e26a](https://github.com/flarum/core/commit/ac5e26a254d89e21bd4c115b6cbd40338e2e4b4b))
- Update to Laravel 5.7, revert custom logic for building database index names
- Refactored installer, extracted Installation class and pipeline for reuse in CLI and web installers ([790d5be](https://github.com/flarum/core/commit/790d5beee5e283178716bc8f9901c758d9e5b6a0))
- Use whitelist for enabling pre-installed extensions during installation ([4585f03](https://github.com/flarum/core/commit/4585f03ee356c92942fbc2ae8c683c651b473954))
- Update minimum MySQL version ([7ff9a90](https://github.com/flarum/core/commit/7ff9a90204923293adc520d3c02dc984845d4f9f))

### Fixed
- Signing up via OAuth providers was broken ([67f9375](https://github.com/flarum/core/commit/67f9375d4745add194ae3249d526197c32fd5461))
- Group badges were overlapping ([16eb1fa](https://github.com/flarum/core/commit/16eb1fa63b6d7b80ec30c24c0e406a2b7ab09934))
- API: Endpoint for uninstalling extensions returned an error ([c761802](https://github.com/flarum/core/commit/c76180290056ddbab67baf5ede814fcedf1dcf14))
- Documentation links in installer were outdated ([b58380e](https://github.com/flarum/core/commit/b58380e224ee54abdade3d0a4cc107ef5c91c9a9))
- Event posts where counted when aggregating user posts ([671fdec](https://github.com/flarum/core/commit/671fdec8d0a092ccceb5d4d5f657d0f4287fc4c7))
- Admins could not reset user passwords ([c67fb2d](https://github.com/flarum/core/commit/c67fb2d4b6a128c71d65dc6703310c0b62f91be2))
- Several down migrations were invalid
- Validation errors on reset password page resulted in HTTP 404 ([4611abe](https://github.com/flarum/core/commit/4611abe5db8b94ca3dc7bf9c447fca7c67358ee3))
- `is:unread` gambit generated an invalid query ([e17bb0b](https://github.com/flarum/core/commit/e17bb0b4331f2c92459292195c6b7db8cde1f9f3))
- Entire forum was breaking when the `custom_less` setting was missing from the database ([bf2c5a5](https://github.com/flarum/core/commit/bf2c5a5564dff3f5ef13efe7a8d69f2617570ce6))
- Dropdown icon was not showing in user card when on user page ([12fdfc9](https://github.com/flarum/core/commit/12fdfc9b544a27f6fe59c82ad6bddd3420cc0181))
- Requests were missing the `original*` attributes, which broke installations in subfolders ([56fde28](https://github.com/flarum/core/commit/56fde28e436f52fee0c03c538f0a6049bc584b53))
- Special characters such as `%` and `_` could return incorrect results ([ee3640e](https://github.com/flarum/core/commit/ee3640e1605ff67fef4b3d5cd0596f14a6ae73c9))
- FontAwesome component package changed paths in version 5.9.0 ([5eb69e1](https://github.com/flarum/core/commit/5eb69e1f59fa73fdfd5badbf41a05a6a040e7426))
- Some server environments had problems accessing the system-wide tmp path for storing JS file maps ([54660eb](https://github.com/flarum/core/commit/54660ebd6311f9ea142f1b573263d0d907400786))
- Content length of posts.content was not migrated to mediumText in 2017 ([590b311](https://github.com/flarum/core/commit/590b3115708bf94a9c7f169d98c6126380c7056e))
- An error occurred when going to the previous route if there was no previous route found ([985b87da](https://github.com/flarum/core/commit/985b87da6c9942c568a1a192e2fdcfde72e030ee))

### Removed
- `php flarum install --defaults` - this was meant to be used in our old development VM ([44c9109](https://github.com/flarum/core/commit/44c91099cd77138bb5fc29f14fb1e81a9781272d))
- Obsolete `id` attributes in JSON-API responses ([ecc3b5e](https://github.com/flarum/core/commit/ecc3b5e2271f8d9b38d52cd54476d86995dbe32e) and [7a44086](https://github.com/flarum/core/commit/7a44086bf3a0e3ba907dceb13d07ac695eca05ea))

## [0.1.0-beta.8.1](https://github.com/flarum/core/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix live output in `migrate:reset` command ([f591585](https://github.com/flarum/core/commit/f591585d02f8c4ff0211c5bf4413dd6baa724c05))
- Fix search with database prefix ([7705a2b](https://github.com/flarum/core/commit/7705a2b7d751943ef9d0c7379ec34f8530b99310))
- Fix invalid join time of admin user created by installer ([57f73c9](https://github.com/flarum/core/commit/57f73c9638eeb825f9e336ed3c443afccfd8995e))
- Ensure InnoDB engine is used for all tables ([fb6b51b](https://github.com/flarum/core/commit/fb6b51b1cfef0af399607fe038603c8240800b2b), [6370f7e](https://github.com/flarum/core/commit/6370f7ecffa9ea7d5fb64d9551400edbc63318db))
- Fix dropping foreign keys in `down` migrations ([57d5846](https://github.com/flarum/core/commit/57d5846b647881009d9e60f9ffca20b1bb77776e))
- Fix discussion list scroll position not being maintained when hero is not visible ([40dc6ac](https://github.com/flarum/core/commit/40dc6ac604c2a0973356b38217aa8d09352daae5))
- Fix empty meta description tag ([88e43cc](https://github.com/flarum/core/commit/88e43cc6940ee30d6529e9ce659471ec4fb1c474))
- Remove empty attributes on `<html>` tag ([796b577](https://github.com/flarum/core/commit/796b57753d34d4ea741dbebcbc550b17808f6c94))
