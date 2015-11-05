# Change Log
All notable changes to Flarum and its bundled extensions will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [0.1.0-beta.4] - 2015-11-05
### Added
- Add an icon/label to the back button to indicate where it leads
- Add "Loading..." text while the JavaScript payload is loading

### Fixed
- Fix some admin actions resulting in "You do not have permission to do that"
- Fix translation keys persisting after enabling an initial language pack
- Fix translation `=>` references not being parsed in some cases

## [0.1.0-beta.3] - 2015-11-03
### Architecture improvements
- **Composer-driven extension architecture.** All extensions are Composer packages installable via Packagist.
- **Backend codebase & API refactoring.** Classes, namespaces, and events systematically tidied up.

### Improved internationalization
> A huge thanks to @dcsjapan for the countless hours he put in to make this stuff happen. You're amazing!

- New systematic translation key naming scheme.
- Make many hardcoded strings translatable, including administration UI and validation messages.
- More powerful pluralization via use of Symfony's Translation component instead of a proprietary one.

### New moderation tools
- **Hide/restore discussions.** Discussions can be soft-deleted by moderators or by the OP if no one has replied.
- **Flags.** New bundled extension that allows posts to be flagged for moderator review.
- **Approval.** New bundled extension that hides/flags new posts to be approved by the moderation team.
- **Akismet.** New bundled extension that checks new posts for spam with Akismet.
- **IP address logging.** IP addresses are stored with posts for use by extensions (e.g. Akismet).
- **Flood control.** Users must wait at least ten seconds between consecutive posts.

### Other features
- **Social login.** New bundled extensions that allow users to log in with Facebook, Twitter, and GitHub.
- **More compact post layout.** All controls are grouped over to the right.
- **Improved permissions.** The admin Permissions page has been improved with icons and other tweaks.
- **Improved extension management.** The admin Extensions page has a new look and is easier to use.
- **Easier debugging.** The "oops" error message has a Debug button to inspect a failed AJAX request.
- **Improved JavaScript minification.** Minification is done by ClosureCompiler only when debug mode is off, resulting in easier debugging and smaller production assets.

### Added
- Allow HTML tag syntax in translations (#574)
- Add gzip/caching directives to webserver configuration (#514)
- API to set the asset compiler's filename
- Migration generator, available via generate:migration console command
- Tags: Ability to set the tags page as the home page
- `bidi` attribute for Mithril elements as a shortcut to set up bidirectional bindings
- `route` attribute for Mithril elements as a shortcut to link to a route
- Abstract SettingsModal component for quickly building admin config modals
- `Model::afterSave()` API to run callback after a model instance is saved
- Sticky: Allow permission to be configured
- Lock: Allow permission to be configured
- Add a third state to header icons (#500)
- Allow faking of PATCH/DELETE methods (#502)
- More reliable form validation and error handling

### Changed
- Rename `notification_read_time` column in discussions table to `notifications_read_time`.
- Update to FontAwesome 4.4.0.

### Fixed
- Output forum description in meta description tag (#506)
- Allow users to edit their last post in a discussion even if it's hidden
- Allow users to rename their discussion even if their first post is hidden
- API links correctly include the `/api` path (#579)
- Tags: Fix sub-tag ordering algorithm in Chrome (#325)
- Fix several design bugs

## [0.1.0-beta.2] - 2015-09-15
### Added
- Check prerequisites (PHP version, extensions, etc.) before installation (#364)
- Enforce maximum title and post length through validation (#53, #338)
- Ctrl+Enter submits posts (#276)
- Syntax highlighting for code blocks (#248)
- All links open in new window, receive rel=nofollow attribute (#247)
- Default build script for extensions (#438)
- Input validation in installer

### Changed
- Ask for admin password confirmation in installer (#405)
- Increased some text contrasts for accessibility (#390)

### Fixed
- Discussion list did not work with non-empty database prefix (#269, #380)
- Non-admins could not reset their password (#229)
- Requests ending with a slash resulted in a 404 (#334)
- In rare cases, posts did not load correctly (#295)
- Avatars did not show up when installed in a subfolder (#291)
- Installer crashed when views directory was not writable (#376)
- Table prefix could not be set in web installer (#269)
- Enabling an extension disabled all other extensions (#402)
- Invalid custom CSS could crash the application (#400)
- First posts could not be restored or deleted
- Several design bugs
- Set cookies to be HTTP-only
- Tags: Sometimes, tags could not be dragged for reordering in the admin panel (#341)
- Suspend: Use correct column name in when migrating database
- Lock: Check for correct permission when displaying lock control
- Likes: Allow liking permissions to be configured

## 0.1.0-beta - 2015-08-27
First Version

[0.1.0-beta.4]: https://github.com/flarum/core/compare/v0.1.0-beta.3...v0.1.0-beta.4
[0.1.0-beta.3]: https://github.com/flarum/core/compare/v0.1.0-beta.2...v0.1.0-beta.3
[0.1.0-beta.2]: https://github.com/flarum/core/compare/v0.1.0-beta...v0.1.0-beta.2
