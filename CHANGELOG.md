# Changelog

## [1.0.4](https://github.com/flarum/core/compare/v1.0.3...v1.0.4)

### Fixed

- Upgrade to v1.0 resets the "view" permission on all tags (https://github.com/flarum/core/pull/2941)

## [1.0.3](https://github.com/flarum/core/compare/v1.0.2...v1.0.3)

### Changed

- Removed [forum] prefix from Request Password and Email Confirmation emails ([a4a81c0](https://github.com/flarum/core/commit/a4a81c0ec237476cd6e7ca00c1ed9465493af476))
- Adopt huntr.dev for handling our security vulnerability reports (https://github.com/flarum/core/pull/2918)
- Maintenance handler can now be replaced through the service container (ioc) ([4acff91](https://github.com/flarum/core/commit/4acff91f8063fcced9bf8c9a76fbb510d06823c0))
- The colors on the auto generated avatars are now based on the Display Name of the user (https://github.com/flarum/core/pull/2873)

### Fixed

- Avatar in notifications list are incorrectly aligned (https://github.com/flarum/core/pull/2906) 
- FilesystemManager is not compatible with upstream Laravel implementation (https://github.com/flarum/core/pull/2936)

## [1.0.2](https://github.com/flarum/core/compare/v1.0.1...v1.0.2)

### Fixed
- Critical XSS vulnerability

## [1.0.1](https://github.com/flarum/core/compare/v1.0.0...v1.0.1)

### Fixed
- Installation fails on environments without proc_* functions enabled or mysql client binary (https://github.com/flarum/core/issues/2890)

## [1.0.0](https://github.com/flarum/core/compare/v0.1.0-beta.16...v1.0.0)

### Added
- Task scheduling
- `load()` method on `ApiController` extender to allow eager loading of relations (https://github.com/flarum/core/pull/2724)
- Installation supports enabling a set of extensions (https://github.com/flarum/core/pull/2757)
- RequestUtil helper class added to abstract the logic of the actor, session, locale and route name from the request (https://github.com/flarum/core/pull/2449)
- Code scanning action with GitHub CodeQL (https://github.com/flarum/core/pull/2744)
- The Formatter extender now has an `unparse` method to allow extensions to hook into the unparsing of content (https://github.com/flarum/core/pull/2780)
- A Filesystem extender allows direct modification and addition of filesystem disks (https://github.com/flarum/core/pull/2732)
- A slug driver based on the User ID was introduced (https://github.com/flarum/core/pull/2787)
- An extensible users list was added to the admin area (https://github.com/flarum/core/pull/2626)
- Headers hardened by adding Referer Policy, Xss Protection and Content type (https://github.com/flarum/core/pull/2721)
- Tooltip component (https://github.com/flarum/core/pull/2843)
- Moved `insertText` and `styleSelectedText` from markdown to core (https://github.com/flarum/core/pull/2826)
- A squashed database schema install dump to speed up new installs (https://github.com/flarum/core/pull/2842)
- Pagination in the canonical URL for discussion pages (https://github.com/flarum/core/pull/2853)
- PaginatedListState for the DiscussionList and to support paginated lists in the frontend (https://github.com/flarum/core/pull/2781)
- Introduce the new webpack config and flarum-tsconfig for typehinting (https://github.com/flarum/core/pull/2856)

### Changed
- Now tracking bundle sizes to keep an eye on web performance (https://github.com/flarum/core/pull/2695)
- Eager load relations on ListPostsController to improve performance (https://github.com/flarum/core/pull/2717)
- Replace classList with clsx library (https://github.com/flarum/core/pull/2760)
- Replaced the javascript based loading spinner with a pure CSS version (https://github.com/flarum/core/pull/2764)
- Route names now have to be unique (https://github.com/flarum/core/pull/2771)
- ActorReference is now available from the error handler middleware (https://github.com/flarum/core/pull/2410)
- The `migrations` table now has an Auto Increment ID (https://github.com/flarum/core/pull/2794)
- Assets and avatars are now managed using Laravel filesystem disks (https://github.com/flarum/core/pull/2729)
- Extracted asset publishing (`php flarum assets:publish`) from migrating (https://github.com/flarum/core/pull/2731)
- Assets were compiled in the format `<asset>-<revision>.<js|css>`, this is now `<asset>.<js|css>?v=<revision>` (https://github.com/flarum/core/pull/2805)
- The powered by header can now be configured in the config under `headers` (https://github.com/flarum/core/pull/2777)
- Switched to the ICU format for translation files (https://github.com/flarum/core/pull/2759)
- Allow extend and override to apply to multiple methods in one call
- Notifications dropdown and list refactored (https://github.com/flarum/core/pull/2822)
- Updated validation locale strings based on Laravel 8 changes (https://github.com/flarum/core/pull/2829)
- Caching of permissions is now taken care of centrally, reducing code duplication (https://github.com/flarum/core/pull/2832)
- Replaced lodash-es by throttle-debounce to reduce bundle size (https://github.com/flarum/core/pull/2827)
- Internal API requests are now executed through middleware (https://github.com/flarum/core/pull/2783)
- Permission changes: `viewDiscussions` to `viewForum` and `viewUserList` to `searchUsers` (https://github.com/flarum/core/pull/2854)

### Fixes
- Javascript is shown when editing the title of a discussion (https://github.com/flarum/core/pull/2693)
- Canonical url logic uses request object which causes wrong URL's when a different page is default (https://github.com/flarum/core/pull/2674)
- Dropdown toggle has no aria label (https://github.com/flarum/core/pull/2668)
- Nav drawer is focusable when off-screen on small viewports (https://github.com/flarum/core/pull/2666)
- Search input has no aria-label and no role (https://github.com/flarum/core/pull/2669)
- Code duplication exists between SendConfirmationEmailController and AccountActivationMailer (https://github.com/flarum/core/pull/2493)
- When setting tags as homepage default, visiting a tag will show all posts (https://github.com/flarum/core/pull/2754)
- Locale cache is cleared twice when cache clearing (https://github.com/flarum/core/pull/2738)
- When cache clearing fails an exception can be thrown due to a partial flush (https://github.com/flarum/core/pull/2756)
- Database migrations rely on MyISAM even though the eventual migrated database does not use it (https://github.com/flarum/core/pull/2442)
- Discussion search result is not sorted by relevance by default (https://github.com/flarum/core/pull/2773)
- Extensions cannot register custom searcher classes (https://github.com/flarum/core/pull/2755)
- Searching discussion titles is not possible (https://github.com/flarum/core/pull/2698)
- Boot errors due to failing extenders throw a generic error (https://github.com/flarum/core/pull/2740)
- Required argument to `Component.$()` isn't really required (https://github.com/flarum/core/pull/2844)
- Component does not allows use of all mithril lifecycle functionality (https://github.com/flarum/core/pull/2847)

### Removed
- The `make:migration` command has been removed (https://github.com/flarum/core/pull/2686)
- Background fade on the header has been removed (https://github.com/flarum/core/pull/2685)
- Remove vendor prefixes in less (https://github.com/flarum/core/pull/2766)
- The session is no longer available from the User class (https://github.com/flarum/core/pull/2790)
- The `mail` key is removed from the laravel related config (https://github.com/flarum/core/pull/2796)

## [0.1.0-beta.16](https://github.com/flarum/core/compare/v0.1.0-beta.15...v0.1.0-beta.16)

### Added
- Allow event subscribers (https://github.com/flarum/core/pull/2535)
- Allow Settings extender to have a default value (https://github.com/flarum/core/pull/2495)
- Allow hooking into the sending of notifications before being send (https://github.com/flarum/core/pull/2533)
- PHP 8 support (https://github.com/flarum/core/pull/2507)
- Search extender (https://github.com/flarum/core/pull/2483)
- User badges to post preview (https://github.com/flarum/core/pull/2555)
- Optional extension dependencies allow a booting order (https://github.com/flarum/core/pull/2579)
- Auth extender (https://github.com/flarum/core/pull/2176)
- `X-Powered-By` header added to allow indexers easier data aggregation of Flarum adoption (https://github.com/flarum/core/pull/2618)

### Changed
- Run integration tests in transaction (https://github.com/flarum/core/pull/2304)
- Allow policies to return a boolean for simplified allow/deny (https://github.com/flarum/core/pull/2534)
- Converted highlight helper to typescript (https://github.com/flarum/core/pull/2532)
- Add accessibility attributes to Mark as Read button (https://github.com/flarum/core/pull/2564)
- Dismiss errors on change email modal upon a new request ([00913d5](https://github.com/flarum/core/commit/00913d5b0be2172cfce1f16aaf64a24f3d2e6d4b))
- Disabled extensions now are marked with a red circle instead of a red dot (https://github.com/flarum/core/pull/2562)
- Extension dependency errors now show the extension title instead of the ID (https://github.com/flarum/core/pull/2563)
- Change `mutate` method on ApiSerializer extender to `attributes` (https://github.com/flarum/core/pull/2578)
- Moved locale files to the core from the language pack (https://github.com/flarum/core/pull/2408)
- AdminPage extensibility and generic improvements (https://github.com/flarum/core/pull/2593)
- Remove entry of authors, link to https://flarum.org/team (https://github.com/flarum/core/pull/2625)
- Search and filtering are split (https://github.com/flarum/core/pull/2454)
- Move IP identification into a middleware (https://github.com/flarum/core/pull/2624)
- Editor Driver abstraction introduced (https://github.com/flarum/core/pull/2594)
- Allow overriding routes (https://github.com/flarum/core/pull/2577)
- Split user edit permissions into permissions for editing of user credentials, username, groups and suspending (https://github.com/flarum/core/pull/2620)
- Reduced number of admin extension categories (https://github.com/flarum/core/pull/2604)
- Move search related classes to a dedicated Query namespace (https://github.com/flarum/core/pull/2645)
- Rewrite common helpers into typescript (https://github.com/flarum/core/pull/2541)
- `TextEditor` is moved to the common namespace for use in the admin frontend (https://github.com/flarum/core/pull/2649)
- Update Laravel/Illuminate components to 8 (https://github.com/flarum/core/pull/2576)
- Eager load relations in discussion listing to improve performance (https://github.com/flarum/core/pull/2639)
- Adopt flarum/testing package (https://github.com/flarum/core/pull/2545)
- Replace `user` gambit with `author` gambit ([612a57c](https://github.com/flarum/core/commit/612a57c4664415a3ea120103483645c32acc6f12))
- Posts page of on user profile loads posts using username instead of id ([30017ee](https://github.com/flarum/core/commit/30017eef09ae9e78640c4e2cacd4909fffa8d775))

### Fixed
- Transform css breaks iOS scroll functionality (https://github.com/flarum/core/pull/2527)
- Composer header is hidden on mobile devices (https://github.com/flarum/core/pull/2279)
- Cannot delete a post or discussion of a deleted user (https://github.com/flarum/core/pull/2521)
- DiscussionListPane jumps around not keeping the scroll position (https://github.com/flarum/core/pull/2402)
- Infinite scroll on notifications dropdown broken (https://github.com/flarum/core/pull/2524)
- The show language selector switch remains toggled on ([9347b12](https://github.com/flarum/core/commit/9347b12b47bf4ab97ffb7ca92673604b237c1012))
- Model Visibility extender throws exception on extensions that aren't installed or enabled (https://github.com/flarum/core/pull/2580)
- Extensions are marked as enabled when enabling fails to unmet extension dependencies (https://github.com/flarum/core/pull/2558)
- Routes to admin extension pages without a valid ID break the admin page (https://github.com/flarum/core/pull/2584)
- Disabled fieldset use an incorrect CSS property `disallowed` (https://github.com/flarum/core/pull/2585)
- Scrolling to a post that is already loaded the Load More button shows and does not trigger (https://github.com/flarum/core/pull/2388)
- Opening discussions on some mobile devices require a double tap (https://github.com/flarum/core/pull/2607)
- iOS devices show erratic behavior in the post stream while updating (https://github.com/flarum/core/pull/2548)
- Small mobile screens partially hides the composer when the keyboard is open (https://github.com/flarum/core/pull/2631)
- Clearing cache does not clear the template cache in storage/views (https://github.com/flarum/core/pull/2648)
- Boot errors show critical information (https://github.com/flarum/core/pull/2633)
- List user endpoint discloses last online even if user choose against it (https://github.com/flarum/core/pull/2634)
- Group gambit disclosed hidden groups (https://github.com/flarum/core/pull/2657)
- Search results on small windows not fully visible (https://github.com/flarum/core/pull/2650)
- Composer goes off screen on Safari when starting to type (https://github.com/flarum/core/pull/2660)
- A search that has no results shows the search results dropdown ([b88a7cb](https://github.com/flarum/core/commit/b88a7cb33b56e318f11670e9e2d563aef94db039))
- The composer modal moves around when typing on Safari ([a64c398](https://github.com/flarum/core/commit/a64c39835aba43e831209609f4a9638ae589aa41))

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
- PHP 7.2 support (https://github.com/flarum/core/pull/2507)
- Bidi attribute in the rendered HTML (https://github.com/flarum/core/pull/2602)
- `AccessToken::find`, use `AccessToken::findValid` instead (https://github.com/flarum/core/pull/2651)

### Deprecated
- `GetModelIsPrivate` event (https://github.com/flarum/core/pull/2587)
- `CheckingPassword` event (https://github.com/flarum/core/pull/2176)
- `event()` helper (https://github.com/flarum/core/pull/2608)
- `AccessToken::generate` argument `$lifetime` (https://github.com/flarum/core/pull/2651)
- `Rememberer::remember` argument `$token` should receive an instance of `RememberAccessToken` with `AccessToken` being deprecated (https://github.com/flarum/core/pull/2651)
- `Rememberer::rememberUser` (https://github.com/flarum/core/pull/2651)
- `SessionAuthenticator::logIn` argument `$userId`, should be replaced with `AccessToken` (https://github.com/flarum/core/pull/2651)
- `TextEditor` has been moved to `common` (https://github.com/flarum/core/pull/2649) 
- `UserFilter` ([91e8b56](https://github.com/flarum/core/commit/91e8b569618957c86757ef89bac666e9102db5ae))


## [0.1.0-beta.15](https://github.com/flarum/core/compare/v0.1.0-beta.14.1...v0.1.0-beta.15)

### Added

- Slug drivers support (https://github.com/flarum/core/pull/2456).
- Notification type extender (https://github.com/flarum/core/pull/2424).
- Validation extender (https://github.com/flarum/core/pull/2102).
- Post extender (https://github.com/flarum/core/pull/2101).
- Notification channel extender (https://github.com/flarum/core/pull/2432).
- Service provider extender (https://github.com/flarum/core/pull/2437).
- API serializer extender (https://github.com/flarum/core/pull/2438).
- User preferences extender (https://github.com/flarum/core/pull/2463).
- Settings extender (https://github.com/flarum/core/pull/2452).
- ApiController extender (https://github.com/flarum/core/pull/2451).
- Model visibility extender (https://github.com/flarum/core/pull/2460).
- Policy extender (https://github.com/flarum/core/pull/2461).

### Changed

- Time helpers converted to Typescript (https://github.com/flarum/core/pull/2391).
- Improved the formatter extender (https://github.com/flarum/core/pull/2098).
- Improve wording on installer when facing file permission issues (https://github.com/flarum/core/pull/2435).
- Background color of checkbox toggles improved for better usability (https://github.com/flarum/core/pull/2443).
- Route resolving refactored (https://github.com/flarum/core/pull/2425).
- Administration panel UX refactored (https://github.com/flarum/core/pull/2409).
- Floodgate moved to middleware and extender added (https://github.com/flarum/core/pull/2170).
- DRY up image uploading logic (https://github.com/flarum/core/pull/2477).
- Process isolation on testing (https://github.com/flarum/core/commit/984f751c718c89501cc09857bc271efa2c7eea8c).
- Forum and admin javascript exports namespaced (https://github.com/flarum/core/pull/2488).

### Fixed

- Web updater does not take into account subfolder installations (https://github.com/flarum/core/pull/2426).
- Callables handling in extenders failed (https://github.com/flarum/core/pull/2423).
- Scrolling on mobile from PostSteam changes didn't work correctly (https://github.com/flarum/core/pull/2385).
- Side pane covers part of the discussion page due to `app.discussions` being empty (https://github.com/flarum/core/commit/102e76b084bf47fdfb4c73f95e1fbb322537f7aa).
- Change email modal keeps showing the previous error message even on success (https://github.com/flarum/core/pull/2467).
- Comment count not updated when discussions are deleted (https://github.com/flarum/core/pull/2472).
- `goToIndex` in PostStream does not trigger an xhr to retrieve new data (https://github.com/flarum/core/commit/09e2736cbcc267594b660beabbd001d9030f9880).
- On refresh the post number is reduced by one (https://github.com/flarum/core/pull/2476).
- Queue worker would instantiate a new Queue factory, not the bound one (https://github.com/flarum/core/pull/2481).
- Header accidentally has a border bottom (https://github.com/flarum/core/pull/2489).
- Namespace mentioned in docblock is incorrect (https://github.com/flarum/core/pull/2494).
- Scrolling inside longer discussions (especially Firefox) skips posts (https://github.com/flarum/core/commit/210a6b3e253d7917bd1eacd3ed8d2f95073ae99d).
- Uploading avatars that are jpg/jpeg fails with a validation error (https://github.com/flarum/core/pull/2497).

### Removed

- MomentJS alias (https://github.com/flarum/core/pull/2428).
- Deprecated user events `GetDisplayName` and `PrepareUserGroups` (https://github.com/flarum/core/pull/2428).
- AssertPermissionTrait (https://github.com/flarum/core/pull/2428).
- Path related helpers and methods in Application (https://github.com/flarum/core/pull/2428).
- Backward compatibility layers from the frontend rewrite (https://github.com/flarum/core/pull/2428).

### Deprecated

- `CheckingForFlooding` (https://github.com/flarum/core/commit/8e25bcb68f86cc992c46dfa70368419fe9f936ac).

## [0.1.0-beta.14.1](https://github.com/flarum/core/compare/v0.1.0-beta.14...v0.1.0-beta.14.1)

### Fixed

- SuperTextarea component is not exported.
- Symfony dependencies do not match those depended on by Laravel (https://github.com/flarum/core/pull/2407).
- Scripts from textformatter aren't executed (https://github.com/flarum/core/pull/2415)
- Sub path installations have no page title.
- Losing focus of Composer area when coming from fullscreen.

## [0.1.0-beta.14](https://github.com/flarum/core/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Added

- Check dependencies before enabling / disabling extensions (https://github.com/flarum/core/pull/2188)
- Set up temporary infrastructure for TypeScript in core (https://github.com/flarum/core/pull/2206)
- Better UI for request error modals (https://github.com/flarum/core/pull/1929)
- Display name extender, tests, frontend UI (https://github.com/flarum/core/pull/2174)
- Scroll to post or show alert when editing a post from another page (https://github.com/flarum/core/pull/2108)
- Feature to test email config by sending an email to the current user (https://github.com/flarum/core/pull/2023)
- Allow searching users by group ID using the group gambit (https://github.com/flarum/core/pull/2192)
- Use `liveHumanTimes` helper to update times without reload/rerender (https://github.com/flarum/core/pull/2208)
- View extender, tests (https://github.com/flarum/core/pull/2134)
- User extender to replace `PrepareUserGroups` (https://github.com/flarum/core/pull/2110)
- Increase extensibility of skeleton PHP (https://github.com/flarum/core/pull/2308, https://github.com/flarum/core/pull/2318)
- Pass a translator instance to `getEmailSubject` in `MailableInterface` (https://github.com/flarum/core/pull/2244)
- Force LF line endings on windows (https://github.com/flarum/core/pull/2321)
- Add a `Link` component for internal and external links (https://github.com/flarum/core/pull/2315)
- `ConfirmDocumentUnload` component
- Error handler middleware can now be manipulated by the middleware extender

### Changed

- Update to Mithril 2 (https://github.com/flarum/core/pull/2255)
- Stop storing component instances (https://github.com/flarum/core/issues/1821, https://github.com/flarum/core/issues/2144)
- Update to Laravel 6.x (https://github.com/flarum/core/issues/2055)
- `Flarum\Foundation\Application` no longer implements `Illuminate\Contracts\Foundation\Application` (#2142)
- `Flarum\Foundation\Application` no longer inherits `Illuminate\Container\Container` (#2142)
- `paths` have been split off from `Flarum\Foundation\Application` into `Flarum\Foundation\Paths`, which can be injected where needed  (#2142)
- `Flarum\User\Gate` no longer implements `Illuminate\Contracts\Auth\Access\Gate` (https://github.com/flarum/core/pull/2181)
- Improve Group Gambit performance (https://github.com/flarum/core/pull/2192)
- Switch to `dayjs` from `momentjs` (https://github.com/flarum/core/pull/2219)
- Don't create a `bio` column in `users` for new installations (https://github.com/flarum/core/pull/2215)
- Start converting core JS to TypeScript (https://github.com/flarum/core/pull/2207)
- Make Carbon an explicit dependency (https://github.com/flarum/core/commit/3b39c212e0fef7522e7d541a9214ff3817138d5d)
- Use Symfony's translator interface instead of Laravel's (https://github.com/flarum/core/pull/2243)
- Use newer versions of fontawesome (https://github.com/flarum/core/pull/2274)
- Use URL generator instead of `app()->url()` where possible (https://github.com/flarum/core/pull/2302)
- Move config from `config.php` into an injectable helper class (https://github.com/flarum/core/pull/2271)
- Use reserved TLD for bogus and test urls (https://github.com/flarum/core/commit/6860b24b70bd04544dde90e537ce021a5fc5a689)
- Replace `m.stream` with `flarum/utils/Stream` (https://github.com/flarum/core/pull/2316)
- Replace `affixedSidebar` util with `AffixedSidebar` component
- Replace `m.withAttr` with `flarum/utils/withAttr`
- Scroll Listener is now passive, performance improvement (https://github.com/flarum/core/pull/2387)

### Fixed

- `generate:migration` command for extensions (https://github.com/flarum/core/commit/443949f7b9d7558dbc1e0994cb898cbac59bec87)
- Container config for `UninstalledSite` (https://github.com/flarum/core/commit/ecdce44d555dd36a365fd472b2916e677ef173cf)
- Tooltip glitch on page chang (https://github.com/flarum/core/issues/2118)
- Using multiple extenders in tests (https://github.com/flarum/core/commit/c4f4f218bf4b175a30880b807f9ccb1a37a25330)
- Header glitch when opening modals (https://github.com/flarum/core/pull/2131)
- Ensure `SameSite` is explicitly set for cookies (https://github.com/flarum/core/pull/2159)
- Ensure `Flarum\User\Event\AvatarChanged` event is properly dispatched (https://github.com/flarum/core/pull/2197)
- Show correct error message on wrong password when changing email (https://github.com/flarum/core/pull/2171)
- Discussion unreadCount could be higher than commentCount if posts deleted (https://github.com/flarum/core/pull/2195)
- Don't show page title on the default route (https://github.com/flarum/core/pull/2047)
- Add page title to `All Discussions` page when it isn't the default route (https://github.com/flarum/core/pull/2047)
- Accept `'0'` as `false` for `flarum/components/Checkbox` (https://github.com/flarum/core/pull/2210)
- Fix PostStreamScrubber background (https://github.com/flarum/core/pull/2222)
- Test port on BaseUrl tests (https://github.com/flarum/core/pull/2226)
- `UrlGenerator` can now generate urls with optional parameters (https://github.com/flarum/core/pull/2246)
- Allow `less` to be compiled independently of Flarum (https://github.com/flarum/core/pull/2252)
- Use correct number abbreviation (https://github.com/flarum/core/pull/2261)
- Ensure avatar html uses alt tags for accessibility (https://github.com/flarum/core/pull/2269)
- Escape regex when searching (https://github.com/flarum/core/pull/2273)
- Remove unneeded semicolons inserted during JS compilation (https://github.com/flarum/core/pull/2280)
- Don't require a username/password for SMTP (https://github.com/flarum/core/pull/2287)
- Allow uppercase entries for SMTP encryption validation (https://github.com/flarum/core/pull/2289)
- Ensure that the right number of posts is returned from list posts API (https://github.com/flarum/core/pull/2291)
- Fix a variety of PostStream bugs (https://github.com/flarum/core/pull/2160, https://github.com/flarum/core/pull/2160)
- Sliding discussion glitch on mobile (https://github.com/flarum/core/pull/2324)
- Sliding discussion button in wrong place (https://github.com/flarum/core/pull/2330, https://github.com/flarum/core/pull/2383)
- Sliding discussion glitch on mobile (https://github.com/flarum/core/pull/2381)
- Fix PostStream for posts with top margins, and scrubber position when scrolling below posts (https://github.com/flarum/core/pull/2369)

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
- Mandrill mail driver (https://github.com/flarum/core/commit/bca833d3f1c34d45d95bf905902368a2753b8908)

### Deprecated

- `Flarum\User\Event\GetDisplayName` event class
- Global path helpers, `Flarum\Foundation\Application` path methods (https://github.com/flarum/core/pull/2155)
- `Flarum\User\AssertPermissionTrait` (https://github.com/flarum/core/pull/2044)

## [0.1.0-beta.13](https://github.com/flarum/core/compare/v0.1.0-beta.12...v0.1.0-beta.13)

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

## [0.1.0-beta.12](https://github.com/flarum/core/compare/v0.1.0-beta.11.1...v0.1.0-beta.12)

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

## [0.1.0-beta.11.1](https://github.com/flarum/core/compare/v0.1.0-beta.11...v0.1.0-beta.11.1)

### Fixed
- Saving custom css in admin failed (#1946)

## [0.1.0-beta.11](https://github.com/flarum/core/compare/v0.1.0-beta.10...v0.1.0-beta.11)

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

## [0.1.0-beta.10](https://github.com/flarum/core/compare/v0.1.0-beta.9...v0.1.0-beta.10)

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

## [0.1.0-beta.9](https://github.com/flarum/core/compare/v0.1.0-beta.8.2...v0.1.0-beta.9)

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
