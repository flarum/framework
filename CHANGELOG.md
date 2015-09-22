# Change Log
All notable changes to Flarum and its bundled extensions will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]
### Added
- Ability to hide and restore discussions.
- External authentication (social login) API.
- API to set asset compiler filename.
- Migration generator, available via generate:migration console command.
- Tags: Ability to set the tags page as the home page.
- `bidi` attribute for Mithril elements as a shortcut to set up bidirectional bindings.
- `route` attribute for Mithril elements as a shortcut to link to a route.
- Abstract SettingsModal component for quickly building admin config modals.
- "Debug" button to inspect the response of a failed AJAX request.
- `Model::afterSave()` API to run callback after a model instance is saved.
- Improved admin Permissions page with icons and other tweaks.
- Sticky: Allow permission to be configured.
- Lock: Allow permission to be configured.
- Flags: New extension. Allows posts to be flagged for moderator review.
- Approval: New extension. Flags new posts to be approved by the moderation team.
- Akismet: New extension. Hides/flags spam posts for moderator approval.

### Changed
- Migrations must be namespaced under `Flarum\Migrations\{Core|ExtensionName}`. ([#422](https://github.com/flarum/core/issues/422))
- More compact post layout, with all controls grouped over to the right.
- Rename `notification_read_time` column in discussions table to `notifications_read_time`.
- Update to FontAwesome 4.4.0.

### Fixed
- Output forum description in meta description tag. ([#506](https://github.com/flarum/core/issues/506))
- Allow users to edit their last post in a discussion even if it's hidden.
- Allow users to rename their discussion even if their first post is hidden.
- Fix several design bugs.

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

[unreleased]: https://github.com/flarum/core/compare/v0.1.0-beta.2...HEAD
[0.1.0-beta.2]: https://github.com/flarum/core/compare/v0.1.0-beta...v0.1.0-beta.2
