# Changelog

## [1.2.0](https://github.com/flarum/tags/compare/v1.1.0...v1.2.0)

### Added
- Bypass tag requirements toggle button for able actors (https://github.com/flarum/tags/pull/153).

### Changed
- Eager load tags state with actor id (https://github.com/flarum/tags/pull/149, https://github.com/flarum/tags/pull/151).
- Slashes in tag slug break routing (https://github.com/flarum/tags/pull/150).
- Stop loading tag last posted discussion relation on admin side (https://github.com/flarum/tags/pull/152).

### Fixed
- UI does not reflect bypass tag requirements permission (https://github.com/flarum/tags/pull/148).
- Occassional errors when deleting flagged posts (https://github.com/flarum/tags/pull/154)
- Tag discussion count doesn't adjust when deleting first/only post of the discussion (https://github.com/flarum/tags/pull/154)

## [1.1.0](https://github.com/flarum/tags/compare/v1.0.3...v1.1.0)

### Added
- Custom colorising with CSS Custom Properties (https://github.com/flarum/tags/pulls/139)

### Changed
- Update nojs view to use slug driver (https://github.com/flarum/tags/pulls/142)
- Pass filter params (https://github.com/flarum/tags/pulls/141)
- Eager load actor tag states (https://github.com/flarum/tags/pulls/143)
- Export `getSelectableTags` util (https://github.com/flarum/tags/pulls/144)

### Fixed
- Broken side nav tag listing (https://github.com/flarum/tags/pulls/137)
- Discussions hidden from all users including admins (https://github.com/flarum/tags/pulls/140)
- Unauthorized view of restricted tags (https://github.com/flarum/tags/pulls/145)
- Make clicking edit tag button easier on mobile (https://github.com/flarum/core/issues/3098)


## [1.0.3](https://github.com/flarum/tags/compare/v1.0.2...v1.0.3)

### Fixed
- Sub tags that were previously loaded are visible when visiting the index from another page (https://github.com/flarum/tags/pull/135)
- Discussion pages are showing parent tags after child tags

## [1.0.2](https://github.com/flarum/tags/compare/v1.0.1...v1.0.2)

### Fixed
- All sub tags are open for each primary tag (https://github.com/flarum/tags/pull/134)

## [1.0.1](https://github.com/flarum/tags/compare/v1.0.0...v1.0.1)

### Fixed
- Permission grid does not lazy load secondary tags (https://github.com/flarum/tags/pull/133)

## [1.0.0](https://github.com/flarum/tags/compare/v0.1.0-beta.16...v1.0.0)

### Changed
- Compatibility with Flarum v1.0.0.
- Eager loading additional relations to improve performance (https://github.com/flarum/tags/pull/125)
- Optimize tag permissions querying to improve performance (https://github.com/flarum/tags/pull/126)
- Remove loading all tags on request to improve performance (https://github.com/flarum/tags/pull/87)

### Fixed
- Tags page uses incorrect canonical URL due to reusing the request object (https://github.com/flarum/tags/pull/122)
- Searching while on a tag page causes the search to affect everything and not the subset (https://github.com/flarum/tags/pull/129)
- Sometimes tag pages show the wrong tag information if it had children
- The API returns outdated tag information when saving discussions (https://github.com/flarum/tags/pull/131)

## [0.1.0-beta.16](https://github.com/flarum/tags/compare/v0.1.0-beta.15...v0.1.0-beta.16)

### Added
- Permission to allow bypassing tag requirements (https://github.com/flarum/tags/pull/111)
- `.Taglabel--child` added to tagLabel to allow styling (https://github.com/flarum/tags/pull/114)

### Changed
- Updated admin category from discussion to feature (https://github.com/flarum/tags/pull/118)
- Moved locale files from translation pack to extension (https://github.com/flarum/tags/pull/99)
- Compatibility with Illuminate 8 (https://github.com/flarum/tags/pull/121)
- Eager load relations lastPostedDiscussion requires (https://github.com/flarum/tags/pull/120)

### Fixed
- Prevent page creep with long list of tags (https://github.com/flarum/tags/pull/116)
- Enter key does not submit tag selection modal ([617fc4d](https://github.com/flarum/tags/commit/617fc4d4419fe4d3ef7b388d14965acc83b319ce))
- Editing a tag does not work (https://github.com/flarum/tags/pull/117)
- Tags link not wrapped inside `Button-label` (https://github.com/flarum/tags/pull/113)
- Without selectable tags the tag selection modal errors (https://github.com/flarum/tags/pull/112)

## [0.1.0-beta.15](https://github.com/flarum/tags/compare/v0.1.0-beta.14...v0.1.0-beta.15)

### Added
- Tag tiles have icons (#104).

### Changed
- Updated composer.json and admin javascript for new admin area.
- Updated to use newest extenders.
- Implement new authorization layer ([c3eff74](https://github.com/flarum/tags/commit/c3eff74289d3461e55d7320556b1e5a5ca08e0ac)).

### Fixed
- Guests do not see "new discussion" and get the log in modal when clicked (#98).
- The tag hidden property is not a bidi and is not saved ([3f54b70](https://github.com/flarum/tags/commit/3f54b70733bb94f7f100580f50f6503a0c387ad6)).

### Removed
- TagWillBeSaved event is removed ([05837de](https://github.com/flarum/tags/commit/05837de8bbe11ca094c7ac63f1a23d7aeceb28d2)).

## [0.1.0-beta.14](https://github.com/flarum/tags/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Added
- Introduced Creating and Deleting events (#86)

### Changed
- Updated mithril to version 2
- Load language strings correctly on en-/disable
- Updated JS dependencies
- Allow tag visibility override with an event listener (#79)
- TagWillBeSaved event renamed to Saving (#92)

### Fixed
- Sorting tag structure on mobile hardly worked (#82)
- Discussion count and visibility incorrectly included hidden or private discussions (#78)
- Negated tag filtering does not work (#88)
- Call to non existing method handleErrors (#94)
- Changing tags of discussions by other users is possible (#95)
- Tag modal shows duplicate tags ()

## [0.1.0-beta.13](https://github.com/flarum/tags/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Added
- Add title and description meta tags (#72)


### Changed
- Updated JS dependencies
- Improved performance of subqueries (#75)
- Allow menu items between listed tags and link to tags page (#70)
- Using new model extender

## [0.1.0-beta.12](https://github.com/flarum/tags/compare/v0.1.0-beta.11...v0.1.0-beta.12)

### Fixed
- Icons misaligned in tag selection modal (#73, #76)
- Selected tags indiscernible when they have no icon (#68, #76)
- Group permissions weren't really deleted when a tag was opened up again to the public (#65) 

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
