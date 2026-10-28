# Resource schema endpoint + generic admin CRUD in core — decisions

**Status:** in-progress. Implemented on `sh/admin-resource-schema-core`; D1–D13 await owner sign-off (Task 0). The owner decisions of 2026-10-28 are applied.
**Reference:** [`doc/extend/resource-schemas.md`](../../extend/resource-schemas.md) documents what exists and how to use it. This file holds only the decisions and why.
**Origin:** GlowingBlue/flarum-extensions#169 "Flarum frontend component library". Proposal 1 extracts the POC into `glowingblue/flarum-lib-frontend`; this is Proposal 2: put it in core, with the admin user list as the first consumer.
**POC:** `flarum-ext-base-products`, branches `sh/admin-crud/{1-backend,2-common,3-admin,4-products,5-hardening}`, spec `doc/@work/specs/2026-10-08-admin-resource-crud.md` there.
**Target:** `flarum/framework` `2.x`, the first minor release after v2.0.2. All new API is under the normal BC promise; the descriptor shape is versioned by `schemaVersion`.

## Problem

At least three GB extensions (base-products, translate, lib-base pages) hand-roll admin list, form and delete screens. Core hand-rolled one too: the old `UserListPage` (its own grid and pagination, no sort, no filter UI, no delete). Each re-declares in TypeScript what the JSON:API resource already knows: fields, types, writability, sorts, filters, permissions.

The POC proved the fix: describe the resource once on the server, derived from the `AbstractDatabaseResource`, render the screen generically, customise through ItemLists. In core this also removes the library's boot-order convention, its `flarum.search.*` container couplings and the duplicated user grid.

## Decisions

Each decision: what, why, rejected alternatives. Shapes, keys and API surfaces are in the guide.

**D1 — Transport: a JSON:API resource.** `ResourceSchemaResource`, type `resource-schemas`, one `Show` endpoint at `GET /api/resource-schemas/{type}`.
- *Why:* follows the `ExtensionReadmeResource` precedent; JSON:API errors, `Extend\ApiResource` extensibility and `app.store` on the client come for free.
- *Rejected:* the POC's `Extend\Routes` + `TextResponse`, a second non-JSON:API protocol under `/api`.

**D2 — Opt-in through an extender, gated by "list or create".** `Extend\ApiResource::describable()`; access rules in the guide ("Who may read a descriptor").
- *Why an extender:* an extension cannot make a resource it does not own implement an interface; with the extender, core opts in `users` and any extension can opt in any resource.
- *Why no new permission:* "you can describe what you can list or create". The descriptor is built per actor, so it reveals only what the actor already reaches through the resource's own endpoints.
- *Accepted (2026-10-28):* on a default install every member may read the users descriptor (members hold `searchUsers`; `Create` is open while sign-up is on, FND-295). Create-only keys are absent when the actor cannot create (FND-296). A page that needs a tighter gate checks its own permission client-side.
- Guests get 401 before the type is looked up, so they cannot probe which types are opted in.
- `capabilities.delete` runs the `Delete` endpoint's own authorization against a blank stored record, so users need `user.delete`; per-row `canDelete` still decides.
- *Rejected:* a marker interface (see above); describing every `AbstractDatabaseResource`, which leaks field names of resources no admin UI uses.

**D3 — Inference against synthetic `DescribeContext`s.** Closures run against blank `create` / `update` / `show` / `index` contexts, instances of `DescribeContext extends Context` with `mode()`. The describer strips query parameters, body and route parameters from the live request; closures see the actor.
- *Why a subclass:* core's `Context` stays unchanged; a row-dependent closure checks `instanceof DescribeContext` instead of throwing; `with*()` clones keep the subclass.
- *Why these failure rules:* a `visible` closure that throws hides the field (fail closed, nothing leaks); a `writable` closure that throws counts as writable with `conditional: true` (fail open is safe, because the server still enforces it).
- *Why guests never call `newModel()`:* `UserResource::newModel()` sets the locale cookie for guests. Guests never reach the builder anyway (D2); the builder uses a bare model for them as a second guard.
- *Rejected:* `Context::describing()` / `withDescribing()` on core's `Context` (first review), which adds describe-only state to every request.

**D4 — UI hints in a dedicated bag: `FlarumField::ui()`.**
- *Why not `$schema`:* `->schema()` replaces the whole array, so a later call would wipe hints; a separate bag also keeps them out of any future OpenAPI output. Several extenders merge their keys.
- *Why:* an extension adds a user-list column with no JS (suspend's `suspendedUntil`).
- *Rejected:* `schema['x-flarum-ui']` (first implementation, replaced after review).

**D5 — Constraints from `Type::schema()`, plus a tolerant rule parser.** json-api-server types already return a JSON-Schema fragment. String rules `required`, `email`, `url`, `in:`, `regex:` (with `patternFlags`), `min:` and `max:` (mapped by kind) fill the rest; core's `Str::email()` / `regex()` emit rules, not type schema.
- *Why:* the POC read public properties, which coupled it to json-api-server internals.
- A PCRE flag JS lacks drops the pattern, so the client never validates against a pattern with a different meaning.

**D6 — Search metadata through `SearchManager` and `DescribesFilter`.**
- *Why `SearchManager` methods:* they replace the POC's reads of the `flarum.search.filters` / `flarum.search.fulltext` container bindings.
- *Why `describe(User $actor): ?array`:* a filter that `filter()` ignores for an actor must not be offered to that actor; null hides it. `EmailFilter` and `SuspendedFilter` mirror their gates.
- *Why also `Extend\SearchDriver::describeFilter()`:* an extension cannot add the interface to a filter it does not own, or to a closure filter. Descriptions are keyed by the exact searcher class.
- *Why only filters that are described:* an undescribed filter's kind is unknown, so the UI could not render it correctly. The UI must not assume the list is complete.
- *Driver rule:* `SearchManager::query()` sends only fulltext queries to the configured driver; filter-only queries always run on the database searcher (FND-186). For a resource whose configured driver is not the database driver, the descriptor keeps `search: true` while the resource is searchable, because that driver serves `q`, and describes no filters: a filter combined with `q` runs on that driver, where the database descriptions may not hold. Conservative by design (reviewer B1).
- *Negation contract:* boolean "No" is `filter[-key]=1`, never `filter[key]=0`. *Why:* core's `FilterManager` already negates a leading `-`, and boolean filters such as `suspended` ignore the value, so `=0` would return the "Yes" rows.

**D7 — Labels resolved server-side.** Order in the guide ("Labels").
- *Why:* extension-added fields carry their own translation keys; one place resolves them in the actor's locale. Core user columns reuse `core.admin.users.grid.columns.*`.
- The ETag is the sha1 of the attributes. The locale is not an input, but the labels differ by locale, so the ETag does too.

**D8 — `UserListPage` keeps its public API and composes `ResourceTable`; the user modals stay.**
- No feature flag, no dual implementation. Kept surface and the two seams `createModal()` / `editModal(user)` are in the guide.
- *Why the modals stay:* `CreateUserModal` / `EditUserModal` have public `fields()` / `data()` / `submitData()` seams, and `EditUserModal` is shared with the forum (nicknames extends it there).
- *Why `editModal()` returns a lazy loader:* it keeps `EditUserModal` in its own chunk, as before.
- Two accepted BC breaks, in the guide's release notes: native `<table>` markup, and `headerItems()` inside the table toolbar.
- *Rejected:* replacing the user modals with `ResourceFormModal`, which breaks `fields()`/`data()`/`submitData()` extenders and the forum's `EditUserModal` use.

**D9 — Frontend location.** `flarum/common/resource/*` for everything that works in forum and admin (definition, list and form state, inputs, validation, CSV, bulk runner); `flarum/admin/components/resource/*` for the table and modals.
- *Why:* forms are usable on the forum; `common` must not import admin code, which is why bulk actions get a `BulkActionContext` from the table. A boundary test enforces the import rule and the absence of `core.admin.*` keys in `common/resource`.
- *Rejected:* a `TabbedExtensionPage` in this work; it is an ExtensionPage concern, unrelated to users.

**D10 — Core supersedes #169 Proposal 1.** The lib is not extracted. GB extensions keep the base-products-sealed copy until a core release ships, then switch imports to `flarum/common/resource/*`. The descriptor stays a superset of the POC's shape, so `ProductDefinition` ports mechanically.
- *Risk:* this is the `flarum/framework` 2.x line. If it goes upstream, the Flarum core team must agree to the RFC first, and Proposal 1 is the fallback. Upstream feedback must land before release, since the BC promise applies from then.

**D11 — Validation, bulk select, CSV export, column toggles: opt-in per `ResourceDefinition`.** Behaviour in the guide.
- *Why opt-in:* a page gets nothing it did not ask for; the user list opts in only where the result is purely additive.
- *Why client-side only, each:* the cheap, safe version ships now. Validation leaves uniqueness and `conditional` fields to the server. Bulk runs one request per row (so gdpr's replaced `Delete` runs per user) instead of json-api-server's `Atomic`, which core does not register. CSV pages through the list endpoint, so it can never contain data the actor could not already see; the formula guard prefixes `'`. Column choices stay in `localStorage`, explicit choices only, so a later default-hidden column starts hidden.
- *User-list defaults:* column toggles on (`id`, `username`, `userActions` fixed); CSV admin-only, because emails are PII; bulk select off behind the empty `bulkActionItems()` seam; no client validation, because the user modals stay (D8); one actions column (`actionsColumn={false}`).
- *Not included:* select-all-matching (the selection holds the current page's ids), server-side CSV streaming, server-side column preferences, async validation.

**D12 — Labelled filter toolbar, search-covered filters hidden by default.** Two rows (search, filters, count, actions; then chips, columns, export). Regions and keys in the guide.
- *Why labelled dropdowns, inline first three, "More filters":* a type can have many filters; a filter with a value always stays visible.
- *Why chips mark gambit overlap:* a gambit typed in the search wins on the server; the chip says so instead of silently doing nothing.
- *Why `searchCoveredFilters: 'all'` by default (owner, 2026-10-28):* a filter a gambit covers would be offered twice. On the stock user list, `group`, `suspended` and `email` are typed as gambits, so no dropdown shows for them; only filters without a gambit (likes' `liked`) get one. The group filter's guest exclusion applies when an extension brings the dropdown back through `searchCoveredFilters()`.
- *Why `floatMenus()`:* the horizontally scrolling table container also clips vertically, which cut off row menus below a short table.
- *Why `headerItems()` fill the toolbar slots:* one toolbar instead of two stacked headers; the actions group keeps `UserListPage-header`.

**D13 — Access tokens page.** `access-tokens` is describable and core ships `AccessTokenListPage` (`/admin#/access-tokens`) on `ResourceTable`. Surface in the guide ("The admin access token list").
- *Why this page:* one admin place to see and revoke every user's sessions and developer tokens, and a second consumer of the generic table that is not users.
- *Resource changes:* sorts `createdAt` / `lastActivityAt`, an includable `user`, and `type` / `canDelete` attributes. `canDelete` = not the actor's current session and `can('revoke')`, so the row decides. `capabilities.delete` stays type-level, because the `Delete` endpoint has no `can()`.
- *Why members may read the descriptor:* the list endpoint is scoped to the actor's own tokens (`whereVisibleTo`), so D2's "list or create" gate reveals nothing new. The `user` filter is described only with `moderateAccessTokens`, since without it the filter could only pick the actor.
- *Why the current session is not revocable there:* the API refuses it (only logout ends it); the row shows a lock instead of "Revoke".
- *Why export is admin-only:* the export holds IP addresses (same reasoning as the user list's emails).
- *Why no create:* developer tokens are created by their owners on the forum.
- *Why `-lastActivityAt` is written to the URL:* the resource has no default sort, and the sort controls must show the active one.

**Form components — public API (owner, 2026-10-28; resolves the former open decision).** `ResourceFormModal`, `ResourceForm`, `ResourceFormState`, the inputs (`InputFactory`, defaults, `RelationInput` / `RelationOptions`), validation and error mapping are documented in the guide.
- *Why:* GB extensions use them, and `ResourceTable`'s default Create and Edit open `ResourceFormModal`. Core auto-exports by path, so an undocumented class would still be importable and hard to change.
- `ResourceFormModal` mirrors the old user modals' surface (`fields()`, `data()`, `submitData()`, `onsaved`), so an extender ports by renaming the base class; a jest parity test pins its writable users fields against `EditUserModal`'s.
- *Rejected:* (a) ship undocumented, which implies a promise without stating it; (b) remove until a core page uses them, which breaks the extensions that already use them.

## Open items

- **Task 0 — owner sign-off of D1–D13.** Points to confirm: D2 (no new permission; every member may read the users descriptor), D8 (seams and the two BC breaks), D10 (upstream-bound or GB-fork-only), D11 user-list defaults, D12 (`'all'` default), D13.
- **Manual click-through (plan Task 10 Step 2), partly done 2026-10-28.** Done: an automated probe on a scratch dev forum (65 PASS; its real failures were fixed) and targeted checks of the toolbar, counts, export and row menus. Open: the edit and delete probe steps (timed out), opening an exported CSV in a spreadsheet (umlauts via BOM, commas in names, no formula executes), a high-contrast theme, and the owner's manual sign-off. Scope: `/admin#/users` with suspend, nicknames and gdpr; sort; filter; `email:` gambit; create, edit, delete, gdpr export; `?page=2`; hidden columns survive reload and a private window.

## Possible follow-ups

- Swap the user modals for generic form modals through `createModal()` / `editModal()`. The forum `EditUserModal` (nicknames) stays until a forum-side form exists.
- An `Index` on `resource-schemas` (SCIM `/ResourceTypes`) once a consumer needs discovery.
- `bulkDelete` on by default in `UserListPage.bulkActionItems()`.
- `BulkRunner` backed by json-api-server `Atomic` once core registers it.
- Select all N matching records.
- Server-side `text/csv` export for sets larger than `maxRows`.
- `ColumnPreferences` backed by a registered user preference.
- Async validation (for example "username taken").
- Move base-products and translate onto `flarum/common/resource/*`, closing #169 Proposal 1.

## As built

`git log --oneline 2.x..HEAD`, oldest first:

- `151a3d602` docs: admin resource schema spec and plan
- `fe4ba577e` feat(api): field ui hints
- `f3f02473e` feat(search): expose searcher filter metadata
- `7fb18d011` feat(api): infer resource descriptors from json:api resources
- `5dd3007ec` feat(api): resource-schemas endpoint
- `8eb8780c0` feat(users): describe the users resource
- `3cbef9ef4` feat(common): generic resource definition, form and list state
- `eba4b1542` feat(admin): generic resource table and modals
- `7cdffd907` refactor(admin): build the user list on the resource table
- `82f08a7f7` feat(extensions): describe user filters and suspend column
- `2865e7260` docs: resource schemas and resource table
- `462a56cc3` fix(api): check the Delete endpoint's authorization for capabilities.delete
- `8c07fcaf7` refactor(admin): write full translation keys in the bulk result modal and validation
- `e98ac2c05` feat(admin): labelled filter toolbar for resource tables
- `68a5713eb` feat(admin): count matching users and leave guests out of the group filter
- `7f661dbe8` feat(admin): configure which filters the search box covers
- `72f9c7ba0` fix(admin): show row menus below a short table, and left-align Delete
- `a884756ce` refactor: make the resource schema and table API public, hide search-covered filters by default
- `a1586067f` fix(search): describe no search for a resource another driver searches
- `7719918d2` docs: merge the as-built design into the spec, drop experimental and future-version claims
- `b23b1601b` feat(admin): access tokens page
- `e9b67b7f9` fix: review fixes for the resource schema API
- (this commit) docs: consolidate the spec, plan and guide

The task-by-task plan was folded into this file and deleted; git history keeps it (`doc/@work/plans/2026-10-27-admin-resource-schema-core.md` at `7719918d2`).

## Non-goals

- Migrating the other core admin pages (permissions, extensions) to the generic table in this work.
- Forum pages built on `ResourceForm`. It lives in `common/` and works there, but no forum page migrates.
- Translatable fields, which are base-products' contract.
- A persistent server cache of descriptors: building one is a few closure calls on admin-only traffic, and a cache would bring back the POC's "closures must not depend on the actor" caveat (FND-123). ETag and the client memo stay.
- A global capabilities document (SCIM `/ServiceProviderConfig`): global flags are SCIM's weak spot; capabilities go per resource and per field instead.
- SCIM filter grammar, PATCH ops and record ETags: JSON:API `filter[key]`, `page`, `sort` and dirty-only PATCH cover it.
- A generic action schema (approve, suspend, …): row actions stay JS ItemLists calling their own endpoints.
