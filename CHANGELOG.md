# Changelog

## [v1.8.1](https://github.com/flarum/framework/compare/v1.8.0...v1.8.1)
### Fixed
* recover temporary solution for html entities in browser title (e72541e35de4f71f9d870bbd9bb46ddf586bdf1d)
* custom contrast color affected by parents (577890d89c593ae5b6cb96083fab69e2f1ae600c)
* reply placeholder wrong positioning (253a3d281dbf5ce3fa712b629b80587cf67e7dbe)
* (mentions) missed post mentions UI changes with lazy loading [#3832]
* (mentions) cannot use newly introduced mentionables extender [#3849]
* (mentions) missing slug from post mention links ([5a4bb7c](5a4bb7ccf226f66dd44816cb69b3d7cfe4ad7f7c))

## [v1.8.0](https://github.com/flarum/framework/compare/v1.7.1...v1.8.0)
### Fixed
- (a11y) reply placeholder not accessible [#3793]
- (bbcode) highlight.js does not work after changing post content [#3817]
- (bbcode) localize quote `wrote` string [#3809]
- (mentions) mentions XHR fired even after mentioning is done [#3806]
- (package-manager) available core updates cause an error in the dashboard ([fab71f2](fab71f2d01fa20ce9b3002833339dc5ea3ea6301))
- (tags) not all tags are loaded in the permission grid [#3804]
- (tags) tag discussion modal filters with exact matches only after first index [#3786]
- (testing) always clear cache in integration test's tearDown [#3818]
- `UserSecurityPage` not exported ([232618a](232618aba604ab003425df38b895208c863d3260))
- `isDark()` utility can receive null value [#3774]
- approving a post does not bump user `comment_count` [#3790]
- circular dependencies disable all involved extensions [#3785]
- color input overflowing the input box [#3796]
- deleting a discussion from the profile does not visually remove it [#3799]
- discussion page showing horizontal scroll on iOS [#3821]
- empty string displayed as SelectDropdown title [#3773]
- filter values are not validated [#3795]
- infinite scroll not initialized for notifications on big screens [#3733]
- notification subject discussion eager loading fails [#3788]
- null as 2nd param in `preg_match` is deprecated [#3801]
- unread count in post stream not visible [#3791]
- unreadable badge icon on certain colors [#3810]
- integrity constraint violation [#3772]
### Changed
- (core,mentions) limit `mentionedBy` post relation results [#3780]
- (likes) limit `likes` relationship results [#3781]
- Change some methods from private to protected, to be able to extend the affected classes [#3802]
- Do not catch exceptions when testing Console commands [#3813]
- drop usage of jquery in `install` and `update` interfaces [#3797]
- extensibility improvements [#3729]
- major frontend JS cleanup [#3609]
- revert ineffective code for encoding of page title [#3768]
- speed up post creation time [#3808]
### Added
- (mentions,tags) tag mentions [#3769]
- add delete own posts permission [#3784]
- add a trait to flush the formatter cache in tests [#3811]
- add user creation to users list page [#3744]
- cli command for enabling or disabling an extension [#3816]
- conditional extenders [#3759]
- provide old content to `Revised` event [#3789]

## [v1.7.1](https://github.com/flarum/framework/compare/v1.7.0...v1.7.1)
### Fixed
- (tags) composer tag selection modal using wrong primary max & min numbers (abc9670659426b765274376945b818b70d84848c)
- missing parameter names in token title translation. (#3752)
- hardcoded language strings in StatusWidget (#3754)
- hide developer tokens section in if there is nothing to display or create (#3753)
- improve sessions user UI on mobile (dd868ab44e11e892d020e3b9412553c6a789e68d)

## [v1.7.0](https://github.com/flarum/framework/compare/v1.6.3...v1.7.0)
### Added
- (actions) allow running JS tests in GH actions  [#3730]
- (core) PHP 8.2 Support [#3709]
- (jest) create jest config package for unit testing [#3678]
- (jest) mithril component testing [#3679]
- (phpstan) foundation for usage in extensions [#3666]
- (seo) Do not use h3 header for poster author in posts stream [#3732]
- (seo) Use h2 header for discussions on discussions list [#3731]
- (seo) shift h1 tag from logo to discussion title [#3724]
- (tags) admin tag selection component (reusable tag selection modal) [#3686]
- Admin User Search [#3712]
- access tokens user management UI [#3587]
- add display name column to admin users list [#3740]
- allow push additional items to the end of the poststream [#3691]
- allow using utf8 characters in tag slugs [#3588]
- expose queue driver, schedule status [#3593]
- expose {time} to eventPost data, fix renamed tooltip [#3698]
- frontend `Model` extender [#3646]
- global logout to clear all sessions, access tokens, email tokens and password tokens [#3605]
- improved page navigation for users list [#3741]
- introduce frontend extenders [#3645]
### Fixed
- (mentions) correctly convert a 3 char. hex color to a 6 char. one [#3694]
- (mentions) post reply mention missing notification on approval [#3738]
- (phpstan) adapt phpstan package for extension use [#3727]
- (tags) clickable tag labels have underline [#3737]
- (tags) tag text color contrast [#3653]
- 3 digit hex color value in color input not supported [#3706]
- column `id` can be ambiguous in group filter with extensions [#3696]
- disallow certain dangerous LESS features ([1761660](1761660c98ea5a3e9665fb8e6041d1f2ee62a444))
- evaluated page title content [#3684]
- invalid translation key for scheduler dashboard [#3736]
- load actor.groups on showforumcontroller [#3716]
- make go-to-page input number-like [#3743]
- normal logout affects all sessions [#3571]
- permissions table on mobile is unusable [#3722]
- post dropdown opens all dropdowns in `.Post-actions` [#3675]
- typo in Formatter extender docblock [#3676]
- undefined showing in dropdown active title [#3700]
### Changed
- (phpstan) enable phpstan in bundled extensions [#3667]
- Add missing states exports to `compat.ts` [#3683]
- Indicate cross-origin request in generic error message [#3669]
- Merge branch 'release/v1.6.2' ([e0b9dcf](e0b9dcfbcd7db175368dbc98255f9223da8df17d))
- The negate field doesn't get used, which means you cant exclude tags [#3713]
- Update forum.less to fix the misalignment of the choose tags button [#3726]
- `yarn audit-fix` ([8ddb0fe](8ddb0feb097dad06c5763107d7a7f7b5a55562c4))
- `yarn` ([ee1e04c](ee1e04cdc26b3e63057a58899f32f482901a95fd))
- convert `Dropdown` components to TS [#3608]
- fix php 8.1 on preg_match 2nd argument being null, which also optimizes slightly ([d7b9a03](d7b9a03f31847c39631ba495df8f515509774610))
- improve group mentions parsing [#3723]
- prepare `@flarum/jest-config` for release ([748cca6](748cca6d12f8b1744a6017c09395725bdbb4a118))
- remove use of deprecated phpunit assertion ([3af0481](3af0481f304277f5380fac9c9b169a7fa651f53b))
- set flarum version to 1.7.0 for dev ([2517bc0](2517bc0f70b0f0e3d3ea3f6ae06af8604d89b25d))
- update JS dependencies  [#3695]

## [v1.6.3](https://github.com/flarum/framework/compare/v1.6.2...v1.6.3)
### Fixed
* Post mentions can be used to read any post on the forum without access control (ab1c868b978e8b0d09a5d682c54665dae17d0985).
* Notifications can leak restricted content (d0a2b95dca57d3dae9a0d77b610b1cb1d0b1766a).
* Any user including unactivated can reply in public discussions whose first post was permanently deleted (12f14112a0ecd1484d97330b82beb2a145919015).
* (subscriptions) Post notifications not getting access checked (https://github.com/flarum/framework/commit/e5f05166a062a9a6eb7c12e28728bfd5db7270e3).

## [v1.6.2](https://github.com/flarum/framework/compare/v1.6.1...v1.6.2)
### Fixed
* XSS Vulnerability in core (https://github.com/flarum/framework/pull/3684).

## [v1.6.1](https://github.com/flarum/framework/compare/v1.6.0...v1.6.1)
### Fixed
* JS dependencies update breaks utilities.

## [v1.6.0](https://github.com/flarum/framework/compare/v1.5.0...v1.6.0)
### Fixed
- (approval) posts approved for deleted users error ([b5874a0](b5874a08e482196f50af50aa78e43c93c29fb647))
- (regression) bad import ([5f2d7fb](5f2d7fb7b6e430d40cf2bb05eca7c73f6ca5a2cc))
- akismet fails when the extension is not on a version ([45d9121](45d91212f6bfa777cae9fc06c55c85d01ffd174d))
- apply flex for AppearancePage colors input [#3651]
- groupmentions have poor contrast on some backgrounds [#3672]
- larastan v1 incompatible with phpstan v1.9.0 [#3665]
- package manager failures not showing alerts [#3647]
- password reset leaks user existence [#3616]
- statistics previous period chart is unclear [#3654]
### Changed
- (package-manager) config composer to use web php version ([fd19645](fd196454a5641776784fa80886cc7577c840f8ed))
- (package-manager) set min core version and add warning ([31c3cfc](31c3cfc4eab4c314260b9b0d11e53ac2d4be158d))
- (statistics) prepare v1.5.1 ([dc215ab](dc215aba59145dfd7b0d6efad4388444f30e47fb))
- Apply fixes from StyleCI ([267f675](267f6759f80bd06f468337245ea6045635e827d9))
- Fix tag discussion count decreased by 2 when hiding before deleting [#3660]
- Log migration path when up/down keys are missing [#3664]
- Make it possible to extend SetupScript [#3643]
- Setup PHPStan Level 5 [#3553]
- `yarn format` ([c5c312d](c5c312db0d800e3b84b94a4abb9691e348dea742))
- add missing last period to custom date ranges [#3661]
- add priorities to profile settings page [#3657]
- allow specifying php extensions in workflow ([b0b47a0](b0b47a0888f513a459b67e9f89e72a61de38f1ce))
- format js ([06963df](06963df4079373fc8fc51b7479e9576f02beb098))
- group mentions [#3658]
- remove styleci from changelog ([b2fa28e](b2fa28e4b57094e46dbdb3d79fab74f290a17d17))
- set flarum version to dev for 1.6.0 ([fc743ba](fc743ba88872031db13597d7365a063b8004c78f))
- throw an exception when no serializer is provided to the controller [#3614]
### Added
- (statistics) support for custom date ranges [#3622]
- Allow additional login params, Introduce `LogInValidator` [#3670]
- Allow additional reset password params, introduce `ForgotPasswordValidator` [#3671]
- add statistics chart export button [#3662]
- allow specifying extensions when installing an instance [#3655]
- contrast util with yiq calculator [#3652]
- customizable session driver [#3610]
- replace `ColorPreviewInput` for GroupModal color input [#3650]
- send notifications of a new reply when post is approved [#3656]

## [v1.5.0](https://github.com/flarum/framework/compare/v1.4.0...v1.5.0)
### Fixed
- (a11y) add accessible labels to notification grid options [#3520]
- (a11y) present post streams as feeds [#3522]
- (a11y) set `aria-busy` when editing a post stream item [#3521]
- (compilation) versioner not inject into compilers [#3589]
- (mentions) accessing `id` of null `user` relation [#3618]
- (subscriptions) add missing table prefix for filter gambit [#3599]
- (tags) use default index sortmap [#3615]
- Move guzzle requirement to core [#3544]
- MyISAM tables for extensions during installation ([75aaef7](75aaef7d76317bc8578eac1439fed8091c87213b), [f926c58](f926c58e0143fe75a4a4c2e93810970c5910afc8))
- Set the translator locale to user preference for email notifications [#3525]
- `$events` property declared dynamically [#3598]
- core settings header has no priority ([33bf228](33bf2284c77863a1bb18d71d87b8516483056a74))
- html entities shown raw in page title [#3542]
- incorrect centring of deleted user avatars in notification list [#3569]
- intellisense imports defaulting to absolute path from `src` folder [#3549]
- minor backward compatible fix for php 8.1 in st_replace ([07b2f86](07b2f86dcc90a3ef17c8ee19a1a07e99a4b17360))
- post query wildcard selection causes ambiguity [#3621]
- potential static caching memory exhaustion [#3548]
- prepare release workflow has invalid layout ([70e483d](70e483d1b185332910be9513fd06cc6342830d49))
- remove deprecation warning for decoding null values ([590639f](590639f5f3e1fe883f28c41e1f175c2826b4b5f4))
- replace `.fa()` mixin usage with `.fas()` [#3537]
- return type hint static is php 8+ ([b01b75e](b01b75e36790d8026dd27ce59051d9581ad47940))
- sticky nav content displays below post stream [#3575]
- titles positioned wrongly with custom header height [#3550]
- typo in error message ([1a189f4](1a189f492320071365286a8835bc49d5a9571753))
- unread notifications are globally cached between users. [#3543]
- update workflow name ([628c281](628c281c39855f01069ddc40b698d80d29fec870))
- user has wrong discussion read status [#3591]
### Changed
- (approval, likes) use subscribers [#3577]
- (package-manager) last tweaks before beta tag ([335c602](335c602cea3fbaee9ad7c32ceecaaf222e5d89a7))
- (statistics) add release notes for 1.4.1 ([f4ace73](f4ace73a3c59434b8717efb2d83f50084f470fe4))
- (statistics) rewrite for performance on very large communities [#3531]
- (statistics) split timed data into per-model XHR requests [#3601]
- (tags) Replace event helper with event dispatcher [#3570]
- Add `loading="lazy"` attribute for avatars [#3578]
- Create CODEOWNERS ([6e48a03](6e48a0303e45bcf210e550ba3e0772bc8443a207))
- MyISAM tables for extensions during installation" ([f128190](f128190f143398dd1262fd1379e634794daee4c1))
- convert `AlertManager` `IndexPage` and `UserPage` components to TS [#3536]
- convert `Badge` `Checkbox` and `Navigation` components to TS [#3532]
- convert core modals to TypeScript [#3515]
- convert page components to TypeScript [#3538]
- debug line slipped in while rebasing a PR [#3580]
- don't pass password field between auth modals [#3626]
- fix github issue templates ([d3e456a](d3e456a1bf42d13b7cd2542c371f392712247c09))
- format code ([4954621](495462183bfb3b33046b293e6b1088ab225968df))
- getting the release workflow in ([5530400](5530400b093b5fd07d670e5c92d8a7da96634cfe))
- link logo at the top with the official website [#3552]
- prevent running both `push` and `pull_request` actions at the same time [#3597]
- refactor prefix matrix and add `MySQL 8.0` & `PHP 7.3` to workflows [#3595]
- relying on a third-party for avatar URL tests is unreliable  [#3586]
- require guzzle 6 or 7 ([46b3b7a](46b3b7a9527b935c3c52269aaad2010c75dcb6d8))
- split FA imports into separate Less file for easy overriding [#3535]
- unify JS actions into one (rewritten `flarum/action-build`) [#3573]
- update version constant during cycle 22 ([d864405](d86440506dd37101e60adec591d4b017e7765ec6))
- use `isCollapsed` instead of `rangeCount` [#3581]
- use github issue template forms [#3526]
### Added
- (likes) Add likes tab to user profile [#3528]
- (likes) Option to prevent users liking their own posts [#3534]
- (modals) support stacking modals, remove bootstrap modals dependency [#3456]
- (subscriptions) add option to send notifications when not caught up [#3503]
- Add custom class for email confirmation alert [#3584]
- Admin debug mode warning [#3590]
- Delete all notifications [#3529]
- Queue package manager commands [#3418]
- Restart the queue worker after cache clearing, ext enable/disable, save settings [#3565]
- add createTableIfNotExists migration helper [#3576]
- add new workflow for generating release meta ([0901e59](0901e59a58a3e1f017762583a2adf419f7f34257))
- clear password & email tokens when appropriate [#3567]
- discussion UTF-8 slug driver [#3606]
- expose assets base url to frontend forum model [#3566]
- extender to add custom less variables [#3530]
- publish assets on admin dashboard cache clear [#3564]
- throttle email change, email confirmation, and password reset endpoints. [#3555]

## [1.4.0](https://github.com/flarum/framework/compare/v1.3.1...v1.4.0)

### Added
- `created_at` and `updated_at` columns added to several tables (https://github.com/flarum/framework/pull/3435)
- Priorities added to AdminNav links (https://github.com/flarum/framework/pull/3453)
- `app.translator` allows retrieving and setting locale (https://github.com/flarum/framework/pull/3451)
- Extensions can now declare custom settings components for use with `buildSettingComponent` (https://github.com/flarum/framework/pull/3494)
- Implement extensibility on `rel` and `target` attributes on links (https://github.com/flarum/framework/pull/3455)
- New backend tests were added to some of the bundled extensions (https://github.com/flarum/framework/issues/3508)

### Changed
- Split boot script for Flarum in HTML footer into two parts for CSP hashing (https://github.com/flarum/framework/pull/3461)
- Split asset compilation by giving assembling compilers its own method (https://github.com/flarum/framework/pull/3446)
- Increase visibility of Component typescript class for better extensibility (https://github.com/flarum/framework/pull/3437)

### Fixed
- Mentioning an event post breaks the notification dropdown (https://github.com/flarum/framework/pull/3493)
- Suspension modal shows after suspension is over (https://github.com/flarum/framework/pull/3449)
- CLI based installations don't exit with an error code on failure (https://github.com/flarum/framework/pull/3452)
- Tabbing through dropdown controls doesn't make them visible (https://github.com/flarum/framework/pull/3450)
- Requiring zero tags on new discussions forces the user to  select tags (https://github.com/flarum/framework/pull/3448)
- Long topic titles in the notification list don't overflow (https://github.com/flarum/framework/pull/3500)
- Subtags of tags the user has access to are visible even if these are not accessible (https://github.com/flarum/framework/pull/3419)
- `assertAdmin` tests access based on wrong gate ability (https://github.com/flarum/framework/pull/3501)
- Increasing the composer header size causes elements to slip underneath (https://github.com/flarum/framework/pull/3502)
- The profile mentions tab errors when sorting by `created_at` (https://github.com/flarum/framework/pull/3506)

## [1.3.1](https://github.com/flarum/framework/compare/v1.3.0...v1.3.1)

### Changed
- UserCard now has ItemList for easier extending (https://github.com/flarum/framework/pull/3436)

### Fixed
- Button to go directly to all results page is hidden while API request for search hasn't completed (https://github.com/flarum/framework/pull/3431)
- Setting extender does not register modifications beyond first fluent call (https://github.com/flarum/framework/pull/3439)
- Link to font awesome icons list no longer works (https://github.com/flarum/framework/commit/df1bdd2ad84e992414c0e1e7be576558b4b0fe29)
- Mentions: mentions with deleted authors not showing (https://github.com/flarum/framework/pull/3432)
- Nicknames: regex validation isn't functional (https://github.com/flarum/framework/pull/3430)
- Subscriptions: reply notifications not working (https://github.com/flarum/framework/pull/3445)
- Suspend: not providing suspension reason breaks mail (https://github.com/flarum/framework/pull/3433)

<!-- One-time commit-based diff due to monorepo rework. Diffing against the 1.2.1 tag doesn't work due to unrelated histories. -->
## [1.3.0](https://github.com/flarum/framework/compare/33d939cb012716ed6309ea02236737ad4f25a75b...v1.3.0)

From v1.2.1 on all bundled Flarum extensions and `flarum/core` are merged into one monorepo. As a result of this, the full code diff linked above
looks rather complex and messy compared to the full list of changes made for this release.

### Added
- [A11Y] Added role feed to DiscussionList (https://github.com/flarum/framework/pull/3359)
- Support multiple confirmation dialogs when closing a tab/window (https://github.com/flarum/framework/pull/3372)
- Markdown: markdown toolbar support for admin frontend (https://github.com/flarum/framework/commit/16d5cc11e3aee5c94aeed877987cdb199a2a0d2c)

### Changed
- Post number calculation is now executed inside the database layer, preventing integrity constraints (https://github.com/flarum/framework/pull/3358)
- Errors from within extensions no longer make Flarum crash but trigger a visible warning (https://github.com/flarum/framework/pull/3349)
- Sorting options for discussion index is now extensible (https://github.com/flarum/framework/pull/3377)
- Event listeners from the framework now are added before those of extensions (https://github.com/flarum/framework/pull/3373)

### Fixed
- Typings and missing typescript components (https://github.com/flarum/framework/pull/3348)
- `Post--by-start-user` CSS class is not added to post html (https://github.com/flarum/framework/pull/3356) 
- Timestamps for notifications are incorrect on servers that have a timezone different than UTC (https://github.com/flarum/framework/pull/3379)
- Extensions with dependencies that are enabled do not cause dependencies to be enforced (https://github.com/flarum/framework/pull/3352)
- Search using non-words doesn't work (https://github.com/flarum/framework/pull/3385)
- Slugs are not working for other languages than English (https://github.com/flarum/framework/pull/3387)
- Deprecations are triggered on PHP 8.1 (https://github.com/flarum/framework/pull/3384)
- Post permalink for subdirectory installs have duplicate paths segments (https://github.com/flarum/framework/pull/3354)
- Composer discussion title is not always clearly visible (https://github.com/flarum/framework/pull/3413)
- Mentions: extensions re-using mentions can cause errors due to missing context (https://github.com/flarum/framework/pull/3382)
- Tags: tag selection modal errors on new discussions when pressing down (https://github.com/flarum/framework/issues/3403)
- [A11Y] Tags: focus to input and layout of tag selection modal are off (https://github.com/flarum/framework/pull/3412)
- Subscriptions: searching inside the following page will search in all discussions (https://github.com/flarum/framework/pull/3376)

## [1.2.1](https://github.com/flarum/framework/compare/v1.2.0...v1.2.1)

### Fixed
- Don't escape single quotes in discussion title meta tags (60600f4d2b8f0c5dac94c329041427a0a08fad42)

## [1.2.0](https://github.com/flarum/framework/compare/v1.1.1...v1.2.0)

### Added
- View `README` documentation in extension pages (https://github.com/flarum/framework/pull/3094).
- Declare & Use CSS Custom Properties (https://github.com/flarum/framework/pull/3146).
- Lazy draw dropdowns to improve performance (https://github.com/flarum/framework/pull/2925).
- Default Settings Extender (https://github.com/flarum/framework/pull/3127).
- Add `textarea` setting type to admin pages (https://github.com/flarum/framework/pull/3141).
- Allow registering settings as `Less` config vars through Settings Extender (https://github.com/flarum/framework/pull/3011).
- Allow replacing of blade template namespaces via extender (https://github.com/flarum/framework/pull/3167).
- Update to Webpack 5 (https://github.com/flarum/framework/pull/3135).
- Introduce `Less` custom function extender with a `is-extension-enabled` function (https://github.com/flarum/framework/pull/3190).
- Support for `few` in ICU Message syntax (https://github.com/flarum/framework/pull/3122).
- ES6 local support for number formatting (https://github.com/flarum/framework/pull/3099).
- Added dedicated endpoint for retrieving single groups (https://github.com/flarum/framework/pull/3084).
- Callback `loadWhere` relation eager loading extender (https://github.com/flarum/framework/pull/3116).
- Extensible document title driver implementation (https://github.com/flarum/framework/pull/3109).
- Type checks, typescript coverage GH action (https://github.com/flarum/framework/pull/3136).
- Add color indicator in appearance admin page instead of validating colors (https://github.com/flarum/framework/pull/3140).
- Add typing files for our translator libraries (https://github.com/flarum/framework/pull/3175).
- `StatusWidget` tools extensibility (https://github.com/flarum/framework/pull/3189).
- Allow switching the `ImageManager` driver (https://github.com/flarum/framework/pull/3195).
- Events for notification read/all read actions (https://github.com/flarum/framework/pull/3203).

### Changed
- Testing with php8.1 (https://github.com/flarum/framework/pull/3102).
- Migrate fully to Yarn (https://github.com/flarum/framework/pull/3155).
- Handle post rendering errors to avoid crashes (https://github.com/flarum/framework/pull/3061).
- Added basic filtering, sorting, and pagination to groups endpoint (https://github.com/flarum/framework/pull/3084).
- Pass IP address to API Client pipeline (https://github.com/flarum/framework/pull/3124).
- Rename Extension Page "Uninstall" to "Purge" (https://github.com/flarum/framework/pull/3123).
- [A11Y] Improve accessibility for discussion reply count on post stream (https://github.com/flarum/framework/pull/3090).
- Improved post loading support (https://github.com/flarum/framework/pull/3100).
- Rewrite SubtreeRetainer into Typescript (https://github.com/flarum/framework/pull/3137).
- Rewrite ModalManager and state to Typescript (https://github.com/flarum/framework/pull/3007).
- Rewrite frontend application files to Typescript (https://github.com/flarum/framework/pull/3006).
- Allow extensions to modify the minimum search length in the Search component (https://github.com/flarum/framework/pull/3130).
- Allow use of any tag in `listItems` helper (https://github.com/flarum/framework/pull/3147).
- Replace `for ... in` with `Array.reduce` (https://github.com/flarum/framework/pull/3149).
- Page title format is now implemented through translations (https://github.com/flarum/framework/pull/3077, https://github.com/flarum/framework/pull/3228)
- Add `aria-label` attribute to the navigation drawer button (https://github.com/flarum/framework/pull/3157).
- Convert extend util to TypeScript (https://github.com/flarum/framework/pull/2928).
- Better typings for DiscussionListState (https://github.com/flarum/framework/pull/3132).
- Rewrite ItemList, update `ItemList` typings (https://github.com/flarum/framework/pull/3005).
- Add priority order to discussion page controls (https://github.com/flarum/framework/pull/3165).
- Use `@php` in Blade templates (https://github.com/flarum/framework/pull/3172).
- Convert some common classes/utils to TS (https://github.com/flarum/framework/pull/2929).
- Convert routes to Typescript (https://github.com/flarum/framework/pull/3177).
- Move admin `colorItems` to an `ItemList` (https://github.com/flarum/framework/pull/3186).
- Centralize pagination/canonical meta URL generation in Document (https://github.com/flarum/framework/pull/3077).
- Use revision versioner to allow custom asset versioning (https://github.com/flarum/framework/pull/3183).
- Split up application error handling (https://github.com/flarum/framework/pull/3184).
- Make SlugManager available to blade template (https://github.com/flarum/framework/pull/3194).
- Convert models to TS (https://github.com/flarum/framework/pull/3174).
- Allow loading relations in other discussion endpoints (https://github.com/flarum/framework/pull/3191).
- Improve selected text stylization (https://github.com/flarum/framework/pull/2961).
- Extract notification `primaryControl` items to an ItemList (https://github.com/flarum/framework/pull/3204).
- Frontend code housekeeping (#3214, #3213).
- Only retain scroll position if coming from discussion (https://github.com/flarum/framework/pull/3229).
- Use `aria-live` regions to focus screenreader attention on alerts as they appear (https://github.com/flarum/framework/pull/3237).
- Prevent unwarranted `a11y` warnings on custom Button subclasses (https://github.com/flarum/framework/pull/3238).

### Fixed
- Missing locale text in the user editing modal (https://github.com/flarum/framework/pull/3093).
- Dashes in table prefix prevent installation (https://github.com/flarum/framework/pull/3089).
- Missing autocomplete attributes to input fields (https://github.com/flarum/framework/pull/3088).
- Missing route parameters throwing an error (https://github.com/flarum/framework/pull/3118).
- Mail settings select component never used (https://github.com/flarum/framework/pull/3120).
- White avatar image throws javascript errors on the profile page (https://github.com/flarum/framework/pull/3119).
- Unformatted avatar upload validation errors (https://github.com/flarum/framework/pull/2946).
- Webkit input clear button shows up with the custom one (https://github.com/flarum/framework/pull/3128).
- Media query breakpoints conflict with Windows display scaling (https://github.com/flarum/framework/pull/3139).
- `typeof this` not recognized by some IDEs (https://github.com/flarum/framework/pull/3142).
- `Model.save()` cannot save `null` `hasOne` relationship (https://github.com/flarum/framework/pull/3131).
- Edit post `until reply` policy broken on PHP 8 (https://github.com/flarum/framework/pull/3145).
- Inaccurate `Component.component` argument typings (https://github.com/flarum/framework/pull/3148).
- Scrolling notification list infinitely repeats (https://github.com/flarum/framework/pull/3159).
- Argument for INFO constant was assigned to `maxfiles` argument incorrectly (bfd81a83cfd0fa8125395a147ff0c9ce622f38e3).
- `Activated` event is sent every time an email is confirmed instead of just once (https://github.com/flarum/framework/pull/3163).
- [A11Y] Modal close button missing accessible label (https://github.com/flarum/framework/pull/3161).
- [A11Y] Auth modal inputs missing accessible labels (https://github.com/flarum/framework/pull/3207).
- [A11Y] Triggering click on drawer button can cause layered backdrops (https://github.com/flarum/framework/pull/3018).
- [A11Y] Focus can leave open nav drawer on mobile (https://github.com/flarum/framework/pull/3018).
- [A11Y] Post action items not showing when focus is within the post (https://github.com/flarum/framework/pull/3173).
- [A11Y] Missing accessible label for alert dismiss button (https://github.com/flarum/framework/pull/3237).
- Error accessing the forum after saving a setting with more than 65k characters (https://github.com/flarum/framework/pull/3162).
- Cannot restart queue from within (https://github.com/flarum/framework/pull/3166).
- `Post--by-actor` not showing when comparing user instances (https://github.com/flarum/framework/pull/3170).
- Incorrect typings for Modal `hide()` method (https://github.com/flarum/framework/pull/3180).
- Avatar Upload throws errors with correct mimetype and incorrect extension (https://github.com/flarum/framework/pull/3181).
- Clicking the dropdown button on a post opens all dropdowns in `Post-actions` (https://github.com/flarum/framework/pull/3185).
- `getPlainContent()` causes external content to be fetched (https://github.com/flarum/framework/pull/3193).
- `listItems` not accepting all `Mithril.Children` (https://github.com/flarum/framework/pull/3176).
- Notifications mark as read option updates all notifications including the read ones (https://github.com/flarum/framework/pull/3202).
- Post meta permalink not properly generated (https://github.com/flarum/framework/pull/3216).
- Broken contribution link in README (https://github.com/flarum/framework/pull/3211).
- `WelcomeHero` is displayed when content is empty (https://github.com/flarum/framework/pull/3219).
- `last_activity_at, last_seen_at` updated on all API requests (https://github.com/flarum/framework/pull/3231).
- `RememberMe` access token updated twice in API requests (https://github.com/flarum/framework/pull/3233).
- Error in `funding` item in `composer.json` bricks the frontend (https://github.com/flarum/framework/pull/3239).
- Escaped quotes in window title (https://github.com/flarum/framework/pull/3264)
- `schedule:list` command fails due to missing timezone configuration.

### Deprecated
- Unused `evented` utility (https://github.com/flarum/framework/pull/3125).

## [1.1.1](https://github.com/flarum/framework/compare/v1.1.0...v1.1.1)

### Fixed
- Performance issue with very large communities.

## [1.1.0](https://github.com/flarum/framework/compare/v1.0.4...v1.1.0)

### Added
- Info command now displays MySQL version, queue driver, mail driver (https://github.com/flarum/framework/pull/2991)
- Use organization Prettier config (https://github.com/flarum/framework/pull/2967)
- Support for global typings in extensions (https://github.com/flarum/framework/pull/2992)
- Typings for class component state attribute (https://github.com/flarum/framework/pull/2995)
- Custom colorising with CSS custom properties (https://github.com/flarum/framework/pull/3001)
- Theme Extender to allow overriding LESS files (https://github.com/flarum/framework/pull/3008)
- Update lastSeenAt when authenticating via API (https://github.com/flarum/framework/pull/3058)
- NoJs Admin View (https://github.com/flarum/framework/pull/3059)
- Preload FontAwesome, JS and CSS, and add `preload` extender (https://github.com/flarum/framework/pull/3057)

### Changed
- Move Day.js plugin types import to global typings (https://github.com/flarum/framework/pull/2954)
- Avoid resolving excluded middleware on each middleware items
- Allow extra attrs provided to `<Select>` to be passed through to the DOM element (https://github.com/flarum/framework/pull/2959)
- Limit height of code blocks (https://github.com/flarum/framework/pull/3012)
- Update normalize.css from v3.0.2 to v8.0.1 (https://github.com/flarum/framework/pull/3015)
- Permission Grid: stick the headers to handle a lot of tags (https://github.com/flarum/framework/pull/2887)
- Use `ItemList` for `DiscussionPage` content (https://github.com/flarum/framework/pull/3004)
- Move email confirmation to POST request (https://github.com/flarum/framework/pull/3038)
- Minor CSS code cleanup (https://github.com/flarum/framework/pull/3026) 
- Replace username with display name in more places (https://github.com/flarum/framework/pull/3040) 
- Rewrite Button to Typescript (https://github.com/flarum/framework/pull/2984)
- Rewrite AdminPage abstract component into Typescript (https://github.com/flarum/framework/pull/2996)
- Allow adding page parameters to PaginatedListState (https://github.com/flarum/framework/pull/2935)
- Pass filter params to getApiDocument (https://github.com/flarum/framework/pull/3037)
- Use author filter instead of gambit to get a user's discussions (https://github.com/flarum/framework/pull/3068)
- [A11Y] Accessibility improvements for the Search component (https://github.com/flarum/framework/pull/3017)
- Add determinsm to extension order resolution (https://github.com/flarum/framework/pull/3076)
- Add cache control headers to the admin area (https://github.com/flarum/framework/pull/3097)

### Fixed
- HLJS 11 new styles resulting in double padding (https://github.com/flarum/framework/pull/2909)
- Internal API client attempting to load an uninstantiated session
- Empty post footer taking visual space (https://github.com/flarum/framework/pull/2926)
- Unrecognized component class custom attribute typings (https://github.com/flarum/framework/pull/2962)
- User edit groups permission not visually depending on view hidden groups permission (https://github.com/flarum/framework/pull/2880)
- Event post excerpt preview triggers error (https://github.com/flarum/framework/pull/2964)
- Missing settings defaults for display name driver and User slug driver (https://github.com/flarum/framework/pull/2971)
- [A11Y] Icons not hidden from screenreaders (https://github.com/flarum/framework/pull/3027)
- [A11Y] Checkboxes not focusable (https://github.com/flarum/framework/pull/3014)
- Uploading ICO favicons resulting in server errors (https://github.com/flarum/framework/pull/2949)
- Missing proper validation for large avatar upload payload (https://github.com/flarum/framework/pull/3042)
- [A11Y] Missing focus rings in control elements (https://github.com/flarum/framework/pull/3016)
- Unsanitised integer query parameters (https://github.com/flarum/framework/pull/3064)

###### Code Contributors
@lhsazevedo, @Ornanovitch, @pierres, @the-turk, @iPurpl3x

###### Issue Reporters
@uamv, @dannyuk1982, @BurnNoticeSpy, @haarp, @peopleinside, @matteocontrini

## [1.0.4](https://github.com/flarum/framework/compare/v1.0.3...v1.0.4)

### Fixed

- Upgrade to v1.0 resets the "view" permission on all tags (https://github.com/flarum/framework/pull/2941)

## [1.0.3](https://github.com/flarum/framework/compare/v1.0.2...v1.0.3)

### Changed

- Removed [forum] prefix from Request Password and Email Confirmation emails ([a4a81c0](https://github.com/flarum/framework/commit/a4a81c0ec237476cd6e7ca00c1ed9465493af476))
- Adopt huntr.dev for handling our security vulnerability reports (https://github.com/flarum/framework/pull/2918)
- Maintenance handler can now be replaced through the service container (ioc) ([4acff91](https://github.com/flarum/framework/commit/4acff91f8063fcced9bf8c9a76fbb510d06823c0))
- The colors on the auto generated avatars are now based on the Display Name of the user (https://github.com/flarum/framework/pull/2873)

### Fixed

- Avatar in notifications list are incorrectly aligned (https://github.com/flarum/framework/pull/2906) 
- FilesystemManager is not compatible with upstream Laravel implementation (https://github.com/flarum/framework/pull/2936)

## [1.0.2](https://github.com/flarum/framework/compare/v1.0.1...v1.0.2)

### Fixed
- Critical XSS vulnerability

## [1.0.1](https://github.com/flarum/framework/compare/v1.0.0...v1.0.1)

### Fixed
- Installation fails on environments without proc_* functions enabled or mysql client binary (https://github.com/flarum/framework/issues/2890)

## [1.0.0](https://github.com/flarum/framework/compare/v0.1.0-beta.16...v1.0.0)

### Added
- Task scheduling
- `load()` method on `ApiController` extender to allow eager loading of relations (https://github.com/flarum/framework/pull/2724)
- Installation supports enabling a set of extensions (https://github.com/flarum/framework/pull/2757)
- RequestUtil helper class added to abstract the logic of the actor, session, locale and route name from the request (https://github.com/flarum/framework/pull/2449)
- Code scanning action with GitHub CodeQL (https://github.com/flarum/framework/pull/2744)
- The Formatter extender now has an `unparse` method to allow extensions to hook into the unparsing of content (https://github.com/flarum/framework/pull/2780)
- A Filesystem extender allows direct modification and addition of filesystem disks (https://github.com/flarum/framework/pull/2732)
- A slug driver based on the User ID was introduced (https://github.com/flarum/framework/pull/2787)
- An extensible users list was added to the admin area (https://github.com/flarum/framework/pull/2626)
- Headers hardened by adding Referer Policy, Xss Protection and Content type (https://github.com/flarum/framework/pull/2721)
- Tooltip component (https://github.com/flarum/framework/pull/2843)
- Moved `insertText` and `styleSelectedText` from markdown to core (https://github.com/flarum/framework/pull/2826)
- A squashed database schema install dump to speed up new installs (https://github.com/flarum/framework/pull/2842)
- Pagination in the canonical URL for discussion pages (https://github.com/flarum/framework/pull/2853)
- PaginatedListState for the DiscussionList and to support paginated lists in the frontend (https://github.com/flarum/framework/pull/2781)
- Introduce the new webpack config and flarum-tsconfig for typehinting (https://github.com/flarum/framework/pull/2856)

### Changed
- Now tracking bundle sizes to keep an eye on web performance (https://github.com/flarum/framework/pull/2695)
- Eager load relations on ListPostsController to improve performance (https://github.com/flarum/framework/pull/2717)
- Replace classList with clsx library (https://github.com/flarum/framework/pull/2760)
- Replaced the javascript based loading spinner with a pure CSS version (https://github.com/flarum/framework/pull/2764)
- Route names now have to be unique (https://github.com/flarum/framework/pull/2771)
- ActorReference is now available from the error handler middleware (https://github.com/flarum/framework/pull/2410)
- The `migrations` table now has an Auto Increment ID (https://github.com/flarum/framework/pull/2794)
- Assets and avatars are now managed using Laravel filesystem disks (https://github.com/flarum/framework/pull/2729)
- Extracted asset publishing (`php flarum assets:publish`) from migrating (https://github.com/flarum/framework/pull/2731)
- Assets were compiled in the format `<asset>-<revision>.<js|css>`, this is now `<asset>.<js|css>?v=<revision>` (https://github.com/flarum/framework/pull/2805)
- The powered by header can now be configured in the config under `headers` (https://github.com/flarum/framework/pull/2777)
- Switched to the ICU format for translation files (https://github.com/flarum/framework/pull/2759)
- Allow extend and override to apply to multiple methods in one call
- Notifications dropdown and list refactored (https://github.com/flarum/framework/pull/2822)
- Updated validation locale strings based on Laravel 8 changes (https://github.com/flarum/framework/pull/2829)
- Caching of permissions is now taken care of centrally, reducing code duplication (https://github.com/flarum/framework/pull/2832)
- Replaced lodash-es by throttle-debounce to reduce bundle size (https://github.com/flarum/framework/pull/2827)
- Internal API requests are now executed through middleware (https://github.com/flarum/framework/pull/2783)
- Permission changes: `viewDiscussions` to `viewForum` and `viewUserList` to `searchUsers` (https://github.com/flarum/framework/pull/2854)

### Fixes
- Javascript is shown when editing the title of a discussion (https://github.com/flarum/framework/pull/2693)
- Canonical url logic uses request object which causes wrong URL's when a different page is default (https://github.com/flarum/framework/pull/2674)
- Dropdown toggle has no aria label (https://github.com/flarum/framework/pull/2668)
- Nav drawer is focusable when off-screen on small viewports (https://github.com/flarum/framework/pull/2666)
- Search input has no aria-label and no role (https://github.com/flarum/framework/pull/2669)
- Code duplication exists between SendConfirmationEmailController and AccountActivationMailer (https://github.com/flarum/framework/pull/2493)
- When setting tags as homepage default, visiting a tag will show all posts (https://github.com/flarum/framework/pull/2754)
- Locale cache is cleared twice when cache clearing (https://github.com/flarum/framework/pull/2738)
- When cache clearing fails an exception can be thrown due to a partial flush (https://github.com/flarum/framework/pull/2756)
- Database migrations rely on MyISAM even though the eventual migrated database does not use it (https://github.com/flarum/framework/pull/2442)
- Discussion search result is not sorted by relevance by default (https://github.com/flarum/framework/pull/2773)
- Extensions cannot register custom searcher classes (https://github.com/flarum/framework/pull/2755)
- Searching discussion titles is not possible (https://github.com/flarum/framework/pull/2698)
- Boot errors due to failing extenders throw a generic error (https://github.com/flarum/framework/pull/2740)
- Required argument to `Component.$()` isn't really required (https://github.com/flarum/framework/pull/2844)
- Component does not allows use of all mithril lifecycle functionality (https://github.com/flarum/framework/pull/2847)

### Removed
- The `make:migration` command has been removed (https://github.com/flarum/framework/pull/2686)
- Background fade on the header has been removed (https://github.com/flarum/framework/pull/2685)
- Remove vendor prefixes in less (https://github.com/flarum/framework/pull/2766)
- The session is no longer available from the User class (https://github.com/flarum/framework/pull/2790)
- The `mail` key is removed from the laravel related config (https://github.com/flarum/framework/pull/2796)

## [0.1.0-beta.16](https://github.com/flarum/framework/compare/v0.1.0-beta.15...v0.1.0-beta.16)

### Added
- Allow event subscribers (https://github.com/flarum/framework/pull/2535)
- Allow Settings extender to have a default value (https://github.com/flarum/framework/pull/2495)
- Allow hooking into the sending of notifications before being send (https://github.com/flarum/framework/pull/2533)
- PHP 8 support (https://github.com/flarum/framework/pull/2507)
- Search extender (https://github.com/flarum/framework/pull/2483)
- User badges to post preview (https://github.com/flarum/framework/pull/2555)
- Optional extension dependencies allow a booting order (https://github.com/flarum/framework/pull/2579)
- Auth extender (https://github.com/flarum/framework/pull/2176)
- `X-Powered-By` header added to allow indexers easier data aggregation of Flarum adoption (https://github.com/flarum/framework/pull/2618)

### Changed
- Run integration tests in transaction (https://github.com/flarum/framework/pull/2304)
- Allow policies to return a boolean for simplified allow/deny (https://github.com/flarum/framework/pull/2534)
- Converted highlight helper to typescript (https://github.com/flarum/framework/pull/2532)
- Add accessibility attributes to Mark as Read button (https://github.com/flarum/framework/pull/2564)
- Dismiss errors on change email modal upon a new request ([00913d5](https://github.com/flarum/framework/commit/00913d5b0be2172cfce1f16aaf64a24f3d2e6d4b))
- Disabled extensions now are marked with a red circle instead of a red dot (https://github.com/flarum/framework/pull/2562)
- Extension dependency errors now show the extension title instead of the ID (https://github.com/flarum/framework/pull/2563)
- Change `mutate` method on ApiSerializer extender to `attributes` (https://github.com/flarum/framework/pull/2578)
- Moved locale files to the core from the language pack (https://github.com/flarum/framework/pull/2408)
- AdminPage extensibility and generic improvements (https://github.com/flarum/framework/pull/2593)
- Remove entry of authors, link to https://flarum.org/team (https://github.com/flarum/framework/pull/2625)
- Search and filtering are split (https://github.com/flarum/framework/pull/2454)
- Move IP identification into a middleware (https://github.com/flarum/framework/pull/2624)
- Editor Driver abstraction introduced (https://github.com/flarum/framework/pull/2594)
- Allow overriding routes (https://github.com/flarum/framework/pull/2577)
- Split user edit permissions into permissions for editing of user credentials, username, groups and suspending (https://github.com/flarum/framework/pull/2620)
- Reduced number of admin extension categories (https://github.com/flarum/framework/pull/2604)
- Move search related classes to a dedicated Query namespace (https://github.com/flarum/framework/pull/2645)
- Rewrite common helpers into typescript (https://github.com/flarum/framework/pull/2541)
- `TextEditor` is moved to the common namespace for use in the admin frontend (https://github.com/flarum/framework/pull/2649)
- Update Laravel/Illuminate components to 8 (https://github.com/flarum/framework/pull/2576)
- Eager load relations in discussion listing to improve performance (https://github.com/flarum/framework/pull/2639)
- Adopt flarum/testing package (https://github.com/flarum/framework/pull/2545)
- Replace `user` gambit with `author` gambit ([612a57c](https://github.com/flarum/framework/commit/612a57c4664415a3ea120103483645c32acc6f12))
- Posts page of on user profile loads posts using username instead of id ([30017ee](https://github.com/flarum/framework/commit/30017eef09ae9e78640c4e2cacd4909fffa8d775))

### Fixed
- Transform css breaks iOS scroll functionality (https://github.com/flarum/framework/pull/2527)
- Composer header is hidden on mobile devices (https://github.com/flarum/framework/pull/2279)
- Cannot delete a post or discussion of a deleted user (https://github.com/flarum/framework/pull/2521)
- DiscussionListPane jumps around not keeping the scroll position (https://github.com/flarum/framework/pull/2402)
- Infinite scroll on notifications dropdown broken (https://github.com/flarum/framework/pull/2524)
- The show language selector switch remains toggled on ([9347b12](https://github.com/flarum/framework/commit/9347b12b47bf4ab97ffb7ca92673604b237c1012))
- Model Visibility extender throws exception on extensions that aren't installed or enabled (https://github.com/flarum/framework/pull/2580)
- Extensions are marked as enabled when enabling fails to unmet extension dependencies (https://github.com/flarum/framework/pull/2558)
- Routes to admin extension pages without a valid ID break the admin page (https://github.com/flarum/framework/pull/2584)
- Disabled fieldset use an incorrect CSS property `disallowed` (https://github.com/flarum/framework/pull/2585)
- Scrolling to a post that is already loaded the Load More button shows and does not trigger (https://github.com/flarum/framework/pull/2388)
- Opening discussions on some mobile devices require a double tap (https://github.com/flarum/framework/pull/2607)
- iOS devices show erratic behavior in the post stream while updating (https://github.com/flarum/framework/pull/2548)
- Small mobile screens partially hides the composer when the keyboard is open (https://github.com/flarum/framework/pull/2631)
- Clearing cache does not clear the template cache in storage/views (https://github.com/flarum/framework/pull/2648)
- Boot errors show critical information (https://github.com/flarum/framework/pull/2633)
- List user endpoint discloses last online even if user choose against it (https://github.com/flarum/framework/pull/2634)
- Group gambit disclosed hidden groups (https://github.com/flarum/framework/pull/2657)
- Search results on small windows not fully visible (https://github.com/flarum/framework/pull/2650)
- Composer goes off screen on Safari when starting to type (https://github.com/flarum/framework/pull/2660)
- A search that has no results shows the search results dropdown ([b88a7cb](https://github.com/flarum/framework/commit/b88a7cb33b56e318f11670e9e2d563aef94db039))
- The composer modal moves around when typing on Safari ([a64c398](https://github.com/flarum/framework/commit/a64c39835aba43e831209609f4a9638ae589aa41))

### Removed
- Deprecated CSRF wildcard path match
- Deprecated policy and visibility scoping events
- Deprecated post types event
- Deprecated validation events
- Deprecated notification events
- Deprecated floodgate
- Deprecated user preferences event
- Deprecated formatting events
- Deprecated api events
- Deprecated bootstrap.php support
- PHP 7.2 support (https://github.com/flarum/framework/pull/2507)
- Bidi attribute in the rendered HTML (https://github.com/flarum/framework/pull/2602)
- `AccessToken::find`, use `AccessToken::findValid` instead (https://github.com/flarum/framework/pull/2651)

### Deprecated
- `GetModelIsPrivate` event (https://github.com/flarum/framework/pull/2587)
- `CheckingPassword` event (https://github.com/flarum/framework/pull/2176)
- `event()` helper (https://github.com/flarum/framework/pull/2608)
- `AccessToken::generate` argument `$lifetime` (https://github.com/flarum/framework/pull/2651)
- `Rememberer::remember` argument `$token` should receive an instance of `RememberAccessToken` with `AccessToken` being deprecated (https://github.com/flarum/framework/pull/2651)
- `Rememberer::rememberUser` (https://github.com/flarum/framework/pull/2651)
- `SessionAuthenticator::logIn` argument `$userId`, should be replaced with `AccessToken` (https://github.com/flarum/framework/pull/2651)
- `TextEditor` has been moved to `common` (https://github.com/flarum/framework/pull/2649) 
- `UserFilter` ([91e8b56](https://github.com/flarum/framework/commit/91e8b569618957c86757ef89bac666e9102db5ae))


## [0.1.0-beta.15](https://github.com/flarum/framework/compare/v0.1.0-beta.14.1...v0.1.0-beta.15)

### Added

- Slug drivers support (https://github.com/flarum/framework/pull/2456).
- Notification type extender (https://github.com/flarum/framework/pull/2424).
- Validation extender (https://github.com/flarum/framework/pull/2102).
- Post extender (https://github.com/flarum/framework/pull/2101).
- Notification channel extender (https://github.com/flarum/framework/pull/2432).
- Service provider extender (https://github.com/flarum/framework/pull/2437).
- API serializer extender (https://github.com/flarum/framework/pull/2438).
- User preferences extender (https://github.com/flarum/framework/pull/2463).
- Settings extender (https://github.com/flarum/framework/pull/2452).
- ApiController extender (https://github.com/flarum/framework/pull/2451).
- Model visibility extender (https://github.com/flarum/framework/pull/2460).
- Policy extender (https://github.com/flarum/framework/pull/2461).

### Changed

- Time helpers converted to Typescript (https://github.com/flarum/framework/pull/2391).
- Improved the formatter extender (https://github.com/flarum/framework/pull/2098).
- Improve wording on installer when facing file permission issues (https://github.com/flarum/framework/pull/2435).
- Background color of checkbox toggles improved for better usability (https://github.com/flarum/framework/pull/2443).
- Route resolving refactored (https://github.com/flarum/framework/pull/2425).
- Administration panel UX refactored (https://github.com/flarum/framework/pull/2409).
- Floodgate moved to middleware and extender added (https://github.com/flarum/framework/pull/2170).
- DRY up image uploading logic (https://github.com/flarum/framework/pull/2477).
- Process isolation on testing (https://github.com/flarum/framework/commit/984f751c718c89501cc09857bc271efa2c7eea8c).
- Forum and admin javascript exports namespaced (https://github.com/flarum/framework/pull/2488).

### Fixed

- Web updater does not take into account subfolder installations (https://github.com/flarum/framework/pull/2426).
- Callables handling in extenders failed (https://github.com/flarum/framework/pull/2423).
- Scrolling on mobile from PostSteam changes didn't work correctly (https://github.com/flarum/framework/pull/2385).
- Side pane covers part of the discussion page due to `app.discussions` being empty (https://github.com/flarum/framework/commit/102e76b084bf47fdfb4c73f95e1fbb322537f7aa).
- Change email modal keeps showing the previous error message even on success (https://github.com/flarum/framework/pull/2467).
- Comment count not updated when discussions are deleted (https://github.com/flarum/framework/pull/2472).
- `goToIndex` in PostStream does not trigger an xhr to retrieve new data (https://github.com/flarum/framework/commit/09e2736cbcc267594b660beabbd001d9030f9880).
- On refresh the post number is reduced by one (https://github.com/flarum/framework/pull/2476).
- Queue worker would instantiate a new Queue factory, not the bound one (https://github.com/flarum/framework/pull/2481).
- Header accidentally has a border bottom (https://github.com/flarum/framework/pull/2489).
- Namespace mentioned in docblock is incorrect (https://github.com/flarum/framework/pull/2494).
- Scrolling inside longer discussions (especially Firefox) skips posts (https://github.com/flarum/framework/commit/210a6b3e253d7917bd1eacd3ed8d2f95073ae99d).
- Uploading avatars that are jpg/jpeg fails with a validation error (https://github.com/flarum/framework/pull/2497).

### Removed

- MomentJS alias (https://github.com/flarum/framework/pull/2428).
- Deprecated user events `GetDisplayName` and `PrepareUserGroups` (https://github.com/flarum/framework/pull/2428).
- AssertPermissionTrait (https://github.com/flarum/framework/pull/2428).
- Path related helpers and methods in Application (https://github.com/flarum/framework/pull/2428).
- Backward compatibility layers from the frontend rewrite (https://github.com/flarum/framework/pull/2428).

### Deprecated

- `CheckingForFlooding` (https://github.com/flarum/framework/commit/8e25bcb68f86cc992c46dfa70368419fe9f936ac).

## [0.1.0-beta.14.1](https://github.com/flarum/framework/compare/v0.1.0-beta.14...v0.1.0-beta.14.1)

### Fixed

- SuperTextarea component is not exported.
- Symfony dependencies do not match those depended on by Laravel (https://github.com/flarum/framework/pull/2407).
- Scripts from textformatter aren't executed (https://github.com/flarum/framework/pull/2415)
- Sub path installations have no page title.
- Losing focus of Composer area when coming from fullscreen.

## [0.1.0-beta.14](https://github.com/flarum/framework/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Added

- Check dependencies before enabling / disabling extensions (https://github.com/flarum/framework/pull/2188)
- Set up temporary infrastructure for TypeScript in core (https://github.com/flarum/framework/pull/2206)
- Better UI for request error modals (https://github.com/flarum/framework/pull/1929)
- Display name extender, tests, frontend UI (https://github.com/flarum/framework/pull/2174)
- Scroll to post or show alert when editing a post from another page (https://github.com/flarum/framework/pull/2108)
- Feature to test email config by sending an email to the current user (https://github.com/flarum/framework/pull/2023)
- Allow searching users by group ID using the group gambit (https://github.com/flarum/framework/pull/2192)
- Use `liveHumanTimes` helper to update times without reload/rerender (https://github.com/flarum/framework/pull/2208)
- View extender, tests (https://github.com/flarum/framework/pull/2134)
- User extender to replace `PrepareUserGroups` (https://github.com/flarum/framework/pull/2110)
- Increase extensibility of skeleton PHP (https://github.com/flarum/framework/pull/2308, https://github.com/flarum/framework/pull/2318)
- Pass a translator instance to `getEmailSubject` in `MailableInterface` (https://github.com/flarum/framework/pull/2244)
- Force LF line endings on windows (https://github.com/flarum/framework/pull/2321)
- Add a `Link` component for internal and external links (https://github.com/flarum/framework/pull/2315)
- `ConfirmDocumentUnload` component
- Error handler middleware can now be manipulated by the middleware extender

### Changed

- Update to Mithril 2 (https://github.com/flarum/framework/pull/2255)
- Stop storing component instances (https://github.com/flarum/framework/issues/1821, https://github.com/flarum/framework/issues/2144)
- Update to Laravel 6.x (https://github.com/flarum/framework/issues/2055)
- `Flarum\Foundation\Application` no longer implements `Illuminate\Contracts\Foundation\Application` (#2142)
- `Flarum\Foundation\Application` no longer inherits `Illuminate\Container\Container` (#2142)
- `paths` have been split off from `Flarum\Foundation\Application` into `Flarum\Foundation\Paths`, which can be injected where needed  (#2142)
- `Flarum\User\Gate` no longer implements `Illuminate\Contracts\Auth\Access\Gate` (https://github.com/flarum/framework/pull/2181)
- Improve Group Gambit performance (https://github.com/flarum/framework/pull/2192)
- Switch to `dayjs` from `momentjs` (https://github.com/flarum/framework/pull/2219)
- Don't create a `bio` column in `users` for new installations (https://github.com/flarum/framework/pull/2215)
- Start converting core JS to TypeScript (https://github.com/flarum/framework/pull/2207)
- Make Carbon an explicit dependency (https://github.com/flarum/framework/commit/3b39c212e0fef7522e7d541a9214ff3817138d5d)
- Use Symfony's translator interface instead of Laravel's (https://github.com/flarum/framework/pull/2243)
- Use newer versions of fontawesome (https://github.com/flarum/framework/pull/2274)
- Use URL generator instead of `app()->url()` where possible (https://github.com/flarum/framework/pull/2302)
- Move config from `config.php` into an injectable helper class (https://github.com/flarum/framework/pull/2271)
- Use reserved TLD for bogus and test urls (https://github.com/flarum/framework/commit/6860b24b70bd04544dde90e537ce021a5fc5a689)
- Replace `m.stream` with `flarum/utils/Stream` (https://github.com/flarum/framework/pull/2316)
- Replace `affixedSidebar` util with `AffixedSidebar` component
- Replace `m.withAttr` with `flarum/utils/withAttr`
- Scroll Listener is now passive, performance improvement (https://github.com/flarum/framework/pull/2387)

### Fixed

- `generate:migration` command for extensions (https://github.com/flarum/framework/commit/443949f7b9d7558dbc1e0994cb898cbac59bec87)
- Container config for `UninstalledSite` (https://github.com/flarum/framework/commit/ecdce44d555dd36a365fd472b2916e677ef173cf)
- Tooltip glitch on page chang (https://github.com/flarum/framework/issues/2118)
- Using multiple extenders in tests (https://github.com/flarum/framework/commit/c4f4f218bf4b175a30880b807f9ccb1a37a25330)
- Header glitch when opening modals (https://github.com/flarum/framework/pull/2131)
- Ensure `SameSite` is explicitly set for cookies (https://github.com/flarum/framework/pull/2159)
- Ensure `Flarum\User\Event\AvatarChanged` event is properly dispatched (https://github.com/flarum/framework/pull/2197)
- Show correct error message on wrong password when changing email (https://github.com/flarum/framework/pull/2171)
- Discussion unreadCount could be higher than commentCount if posts deleted (https://github.com/flarum/framework/pull/2195)
- Don't show page title on the default route (https://github.com/flarum/framework/pull/2047)
- Add page title to `All Discussions` page when it isn't the default route (https://github.com/flarum/framework/pull/2047)
- Accept `'0'` as `false` for `flarum/components/Checkbox` (https://github.com/flarum/framework/pull/2210)
- Fix PostStreamScrubber background (https://github.com/flarum/framework/pull/2222)
- Test port on BaseUrl tests (https://github.com/flarum/framework/pull/2226)
- `UrlGenerator` can now generate urls with optional parameters (https://github.com/flarum/framework/pull/2246)
- Allow `less` to be compiled independently of Flarum (https://github.com/flarum/framework/pull/2252)
- Use correct number abbreviation (https://github.com/flarum/framework/pull/2261)
- Ensure avatar html uses alt tags for accessibility (https://github.com/flarum/framework/pull/2269)
- Escape regex when searching (https://github.com/flarum/framework/pull/2273)
- Remove unneeded semicolons inserted during JS compilation (https://github.com/flarum/framework/pull/2280)
- Don't require a username/password for SMTP (https://github.com/flarum/framework/pull/2287)
- Allow uppercase entries for SMTP encryption validation (https://github.com/flarum/framework/pull/2289)
- Ensure that the right number of posts is returned from list posts API (https://github.com/flarum/framework/pull/2291)
- Fix a variety of PostStream bugs (https://github.com/flarum/framework/pull/2160, https://github.com/flarum/framework/pull/2160)
- Sliding discussion glitch on mobile (https://github.com/flarum/framework/pull/2324)
- Sliding discussion button in wrong place (https://github.com/flarum/framework/pull/2330, https://github.com/flarum/framework/pull/2383)
- Sliding discussion glitch on mobile (https://github.com/flarum/framework/pull/2381)
- Fix PostStream for posts with top margins, and scrubber position when scrolling below posts (https://github.com/flarum/framework/pull/2369)

### Removed

- `Flarum\Event\AbstractConfigureRoutes` event class
- `Flarum\Event\ConfigureApiRoutes` event class
- `Flarum\Event\ConfigureForumRoutes` event class
- `Flarum\Console\Event\Configuring` event class
- `Flarum\Event\ConfigureModelDates` event class
- `Flarum\Event\ConfigureLocales` event class
- `Flarum\Event\ConfigureModelDefaultAttributes` event class
- `Flarum\Event\GetModelRelationship` event class
- `Flarum\User\Event\BioChanged` event class
- `Flarum\Database\MigrationServiceProvider` moved into `Flarum\Database\DatabaseServiceProvider`
- Unused `admin/components/Widget` component (`admin/component/DashboardWidget` should be used instead)
- Mandrill mail driver (https://github.com/flarum/framework/commit/bca833d3f1c34d45d95bf905902368a2753b8908)

### Deprecated

- `Flarum\User\Event\GetDisplayName` event class
- Global path helpers, `Flarum\Foundation\Application` path methods (https://github.com/flarum/framework/pull/2155)
- `Flarum\User\AssertPermissionTrait` (https://github.com/flarum/framework/pull/2044)

## [0.1.0-beta.13](https://github.com/flarum/framework/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Added
- Console extender (#2057)
- CSRF extender (#2095)
- Event extender (#2097)
- Mail extender (#2012)
- Model extender (#2100)
- Posts by users that started a discussion now have the CSS class `.Post--by-start-user`
- PHPUnit 8 compatibility
- Composer 2 compatibility
- Permission groups can now be hidden (#2129)
- Confirmation popup when hiding or deleting posts (#2135)

### Changed
- Updated less.php dependency version to 3.0
- Updated JS dependencies
- All notifications and other emails now processed through the queue, if enabled (#978, #1928, #1931, #2096)
- Simplified uploads, removing need to store intermediate files (#2117)
- Improved date handling for dates older than 1 year (#2034)
- Linting and automatic formatting for JS (#2099)
- Translation files from Language Packs are only loaded for extensions that are enabled (#2020)
- PHP extenders' properties are now `private` instead of `protected`, intentionally making it harder to extend these classes (#1958)
- Preparation for upgrading Laravel components to 5.8 and then 6.0 (#2055, #2117)
- Allowed permission checks based on model classes in addition to instances (#1977)

### Fixed
- Users can no longer restore discussions hidden by admins (#2037)
- Issues of the Modal not showing or auto hiding (#1504, #1813, #2080)
- Columnar layout on admin extensions page was broken in Firefox (#2029, #2111)
- Non-dismissible modals could still be dismissed using the ESC key (#1917)
- New discussions were added to the discussion list above unread sticky posts (#1751, #1868)
- New discussions not visible to users when using Pusher (#2076, #2077)
- Permission icons were aligned unevenly in admin permissions list (#2016, #2018)
- Notification bubble not inversed on mobile with colored header (#1983, #2109)
- Post stream scrubber clicks jumped back to first post (#1945)
- Loading state of Switch toggle component was hard to see (#2039, #1491)
- `Flarum\Extend\Middleware`: The methods `insertBefore()` and `insertAfter()` did not work as described (#2063, #2084)

### Removed
- Support for PHP 7.1 (#2014)
- Zend compatibility bridge (#2010)
- SES mail support (#2011)
- Backward compatibility layer for `Flarum\Mail\DriverInterface`, new methods from beta.12 are now required
- `Flarum\Util\Str` helper class
- `Flarum\Event\ConfigureMiddleware` event

### Deprecated
- `Flarum\Event\AbstractConfigureRoutes` event class
- `Flarum\Event\ConfigureApiRoutes` event class
- `Flarum\Event\ConfigureForumRoutes` event class
- `Flarum\Event\ConfigureLocales` event class

## [0.1.0-beta.12](https://github.com/flarum/framework/compare/v0.1.0-beta.11.1...v0.1.0-beta.12)

### Added
- Full support for PHP 7.4 (#1980)
- Mail settings: Configure region for the Mailgun driver (#1834, #1850)
- Mail settings: Alert admins about incomplete settings (#1763, #1921)
- New permission that allows users to post without throttling (#1255, #1938)
- Basic transliteration of discussion "slugs" / pretty URLs (#194, #1975)
- User profiles: Render basic content on server side (#1901)
- New extender for configuring middleware (#1919, #1952, #1957, #1971)
- New extender for configuring error handling (#1781, #1970)
- Automated tests for PHP extenders to guarantee their backwards compatibility

### Changed
- Profile URLs for non-existing users properly return HTTP 404 (#1846, #1901)
- Confirmation email subject no longer contains the forum title (#1613)
- Improved error handling during Flarum's early boot phase (#1607)
- Updated deprecated "Zend" libraries to their new "Laminas" equivalents (#1963)

### Fixed
- Update page did not work when installed in subdirectories (#1947)
- Avatar upload did not work in IE11 / Edge (#1125, #1570)
- Translation fallback was ignored for client-rendered pages (#1774, #1961)
- The success alert when posting replies was invisible (#1976)

## [0.1.0-beta.11.1](https://github.com/flarum/framework/compare/v0.1.0-beta.11...v0.1.0-beta.11.1)

### Fixed
- Saving custom css in admin failed (#1946)

## [0.1.0-beta.11](https://github.com/flarum/framework/compare/v0.1.0-beta.10...v0.1.0-beta.11)

### Added
- Comments have an additional class `Post--by-actor` when posted by the user (#1927)

### Changed
- Improved support for URL identification during installation (#1861)
- KeyboardNavigatable now has a callback ability (#1922)
- Links are no longer opened with target `_blank` but in the same window (#859)
- Links now have `nofollow ugc` by default as their `rel` attribute (#859, #1884)
- Improved performance of the full text gambit when searching for users (#1877)
- The Queue implementation is now available under its Illuminate contract

### Fixed
- No error handling was possible in the console/cli (#1789)
- Enable scrollbars in log in modals so it fits for GitHub (#1716)
- Reduce log in modal for SSO so it fits for Facebook (#1727)
- Deleting discussions permanently did not delete its posts (#1909)
- Fixed the queue:restart command (#1932)
- Deleted posts were visible to all visitors (#1827)
- Old avatars weren't being deleted when replaced (#1918)
- The search performance regression was reverted (#1764)
- No profile background could be set for remote images (#445)
- Back button sends to home even though it could actually go back (#1942)
- Debug button no longer visible (#1687)
- Modals on smaller screens use the whole width of the page

## [0.1.0-beta.10](https://github.com/flarum/framework/compare/v0.1.0-beta.9...v0.1.0-beta.10)

### Added
- Initial queue support: Infrastructure for offloading long-running tasks (e.g. email sending) to background workers (#1773)
- Notifications can now be marked as read without visiting a discussion (#151)
- SEO: The discussion list now has a `rel="canonical"` meta tag, preventing duplicate content (#1134, #1814)
- The "Edit User" permission can now be edited in the UI (#1845)
- New status message and redirect after user deletion (#1750, #1777)
- Errors in Flarum's boot process are now presented with more detailed information (#1607)

### Changed
- Better, more detailed and extensible error handling (#1641, #1843)
- Error pages in debug mode now return the same HTTP status codes as in production (#1648)
- Tweak HTTP status codes for authentication / authorization errors (#1854)
- Already-used links from account activation emails now show a better error message (#1337)

### Fixed
- Security vulnerabilities in dependencies
- Performance: High CPU usage when scrolling in a discussion (#1222)
- Special characters crashed the search (#1498)
- Missing declarations for language and text direction in HTML output (#1772)
- Private messages were counted in user post counts (#1695)
- Extensions could not change the forum's default page (#1819)
- API requests authenticated using access tokens needed to provide a CSRF token (#1828)
- Accessibility: Screenreaders did not read the "Back to discussion list" link (#1835)

## [0.1.0-beta.9](https://github.com/flarum/framework/compare/v0.1.0-beta.8.2...v0.1.0-beta.9)

### Added
- New `hasPermission()` helper method for `Group` objects ([9684fbc](https://github.com/flarum/framework/commit/9684fbc4da07d32aa322d9228302a23418412cb9))
- Expose supported mail drivers in IoC container ([208bad3](https://github.com/flarum/framework/commit/208bad393f37bfdb76007afcddfa4b7451563e9d))
- More test for some API endpoints ([1670590](https://github.com/flarum/framework/commit/167059027e5a066d618599c90164ef1b5a509148))
- The `Formatter\Rendering` event now receives the HTTP request instance as well ([0ab9fac](https://github.com/flarum/framework/commit/0ab9facc4bd59a260575e6fc650793c663e5866a))
- More and better validation in installer UIs
- Check and enforce minimum MariaDB ([7ff9a90](https://github.com/flarum/framework/commit/7ff9a90204923293adc520d3c02dc984845d4f9f))
- Revert publication of assets when installation fails ([ed9591c](https://github.com/flarum/framework/commit/ed9591c16fb2ea7a4be3387b805d855a53e0a7d5))
- Benefit from Laravel's database reconnection logic in long-running tasks ([e0becd0](https://github.com/flarum/framework/commit/e0becd0c7bda939048923c1f86648793feee78d5))
- The "vendor path" (where Composer dependencies can be found) can now be configured ([5e1680c](https://github.com/flarum/framework/commit/5e1680c458cd3ba274faeb92de3ac2053789131e))

### Changed
- Performance: Actually cache translations on disk ([0d16fac](https://github.com/flarum/framework/commit/0d16fac001bb735ee66e82871183516aeac269b7))
- Allow per-site extenders to override extension extenders ([ba594de](https://github.com/flarum/framework/commit/ba594de13a033480834d53d73f747b05fe9796f8))
- Do not resolve objects from the IoC container (in service providers and extenders) until they are actually used
- Replace event subscribers (that resolve objects from the IoC container) with listeners (that resolve lazily)
- Use custom service provider for Mail component ([ac5e26a](https://github.com/flarum/framework/commit/ac5e26a254d89e21bd4c115b6cbd40338e2e4b4b))
- Update to Laravel 5.7, revert custom logic for building database index names
- Refactored installer, extracted Installation class and pipeline for reuse in CLI and web installers ([790d5be](https://github.com/flarum/framework/commit/790d5beee5e283178716bc8f9901c758d9e5b6a0))
- Use whitelist for enabling pre-installed extensions during installation ([4585f03](https://github.com/flarum/framework/commit/4585f03ee356c92942fbc2ae8c683c651b473954))
- Update minimum MySQL version ([7ff9a90](https://github.com/flarum/framework/commit/7ff9a90204923293adc520d3c02dc984845d4f9f))

### Fixed
- Signing up via OAuth providers was broken ([67f9375](https://github.com/flarum/framework/commit/67f9375d4745add194ae3249d526197c32fd5461))
- Group badges were overlapping ([16eb1fa](https://github.com/flarum/framework/commit/16eb1fa63b6d7b80ec30c24c0e406a2b7ab09934))
- API: Endpoint for uninstalling extensions returned an error ([c761802](https://github.com/flarum/framework/commit/c76180290056ddbab67baf5ede814fcedf1dcf14))
- Documentation links in installer were outdated ([b58380e](https://github.com/flarum/framework/commit/b58380e224ee54abdade3d0a4cc107ef5c91c9a9))
- Event posts where counted when aggregating user posts ([671fdec](https://github.com/flarum/framework/commit/671fdec8d0a092ccceb5d4d5f657d0f4287fc4c7))
- Admins could not reset user passwords ([c67fb2d](https://github.com/flarum/framework/commit/c67fb2d4b6a128c71d65dc6703310c0b62f91be2))
- Several down migrations were invalid
- Validation errors on reset password page resulted in HTTP 404 ([4611abe](https://github.com/flarum/framework/commit/4611abe5db8b94ca3dc7bf9c447fca7c67358ee3))
- `is:unread` gambit generated an invalid query ([e17bb0b](https://github.com/flarum/framework/commit/e17bb0b4331f2c92459292195c6b7db8cde1f9f3))
- Entire forum was breaking when the `custom_less` setting was missing from the database ([bf2c5a5](https://github.com/flarum/framework/commit/bf2c5a5564dff3f5ef13efe7a8d69f2617570ce6))
- Dropdown icon was not showing in user card when on user page ([12fdfc9](https://github.com/flarum/framework/commit/12fdfc9b544a27f6fe59c82ad6bddd3420cc0181))
- Requests were missing the `original*` attributes, which broke installations in subfolders ([56fde28](https://github.com/flarum/framework/commit/56fde28e436f52fee0c03c538f0a6049bc584b53))
- Special characters such as `%` and `_` could return incorrect results ([ee3640e](https://github.com/flarum/framework/commit/ee3640e1605ff67fef4b3d5cd0596f14a6ae73c9))
- FontAwesome component package changed paths in version 5.9.0 ([5eb69e1](https://github.com/flarum/framework/commit/5eb69e1f59fa73fdfd5badbf41a05a6a040e7426))
- Some server environments had problems accessing the system-wide tmp path for storing JS file maps ([54660eb](https://github.com/flarum/framework/commit/54660ebd6311f9ea142f1b573263d0d907400786))
- Content length of posts.content was not migrated to mediumText in 2017 ([590b311](https://github.com/flarum/framework/commit/590b3115708bf94a9c7f169d98c6126380c7056e))
- An error occurred when going to the previous route if there was no previous route found ([985b87da](https://github.com/flarum/framework/commit/985b87da6c9942c568a1a192e2fdcfde72e030ee))

### Removed
- `php flarum install --defaults` - this was meant to be used in our old development VM ([44c9109](https://github.com/flarum/framework/commit/44c91099cd77138bb5fc29f14fb1e81a9781272d))
- Obsolete `id` attributes in JSON-API responses ([ecc3b5e](https://github.com/flarum/framework/commit/ecc3b5e2271f8d9b38d52cd54476d86995dbe32e) and [7a44086](https://github.com/flarum/framework/commit/7a44086bf3a0e3ba907dceb13d07ac695eca05ea))

## [0.1.0-beta.8.1](https://github.com/flarum/framework/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Fix live output in `migrate:reset` command ([f591585](https://github.com/flarum/framework/commit/f591585d02f8c4ff0211c5bf4413dd6baa724c05))
- Fix search with database prefix ([7705a2b](https://github.com/flarum/framework/commit/7705a2b7d751943ef9d0c7379ec34f8530b99310))
- Fix invalid join time of admin user created by installer ([57f73c9](https://github.com/flarum/framework/commit/57f73c9638eeb825f9e336ed3c443afccfd8995e))
- Ensure InnoDB engine is used for all tables ([fb6b51b](https://github.com/flarum/framework/commit/fb6b51b1cfef0af399607fe038603c8240800b2b), [6370f7e](https://github.com/flarum/framework/commit/6370f7ecffa9ea7d5fb64d9551400edbc63318db))
- Fix dropping foreign keys in `down` migrations ([57d5846](https://github.com/flarum/framework/commit/57d5846b647881009d9e60f9ffca20b1bb77776e))
- Fix discussion list scroll position not being maintained when hero is not visible ([40dc6ac](https://github.com/flarum/framework/commit/40dc6ac604c2a0973356b38217aa8d09352daae5))
- Fix empty meta description tag ([88e43cc](https://github.com/flarum/framework/commit/88e43cc6940ee30d6529e9ce659471ec4fb1c474))
- Remove empty attributes on `<html>` tag ([796b577](https://github.com/flarum/framework/commit/796b57753d34d4ea741dbebcbc550b17808f6c94))
