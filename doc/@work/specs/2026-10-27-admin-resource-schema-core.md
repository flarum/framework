# Resource schema endpoint + generic admin CRUD in core — design

**Status:** implemented on `sh/admin-resource-schema-core`; decisions D1–D12 need owner sign-off. The owner decisions of 2026-10-28 are applied. One open decision remains (§7).
**Origin:** GlowingBlue/flarum-extensions#169 "Flarum frontend component library" — Proposal 1 extracts the
POC into `glowingblue/flarum-lib-frontend`. This spec is **Proposal 2: put it in core**, with the admin
user list as the first consumer.
**POC:** `flarum-ext-base-products`, branches `sh/admin-crud/{1-backend,2-common,3-admin,4-products,5-hardening}`,
spec `doc/@work/specs/2026-10-08-admin-resource-crud.md` there.
**Flarum target:** `2.x` (this repo, branch `2.x`). The branch lands after v2.0.2, so this ships in the next minor release.
**Stability:** all new API is normal public API under Flarum's backwards-compatibility promise, because extensions will have to use it. The descriptor shape is versioned by `schemaVersion`.

## 1. Problem

At least three GB extensions (base-products, translate, lib-base pages) hand-roll admin list + form + delete
screens. Core hand-rolls one too: `admin/components/UserListPage.tsx` (491 lines, its own grid, its own
pagination, no sort, no filter UI, no delete). Every one re-declares in TypeScript what the JSON:API resource
already knows: fields, types, writability, sorts, filters, permissions.

The POC proved the fix: describe the resource once (server-side, derived from the `AbstractDatabaseResource`),
render the screen generically, customise through ItemLists. Putting it in core removes the library's boot-order
problem (Proposal 1 option A's "wiring first" convention), the `flarum.search.*` container couplings, and the
duplicated `UserListPage` grid.

## 2. What is really needed (brainstorm outcome)

Kept from the POC (proved necessary by base-products):

| Need | Why it can't be skipped |
|---|---|
| Field list incl. extender-added fields | The whole point: `suspend`, `nicknames`, `gdpr`, `flags`, `messages` add `UserResource` fields today |
| Per-actor `visible` / `writable{create,update}` | json-api-server 403s any non-writable field in a payload (FND-220/234); a form must know what to render |
| Kind + constraints (maxLength, enum, format, min/max, nullable, required) | Picks the input widget; avoids round-trip-only validation for the obvious cases |
| Sorts, filters, fulltext flag | Table header + filter bar |
| Relationship target type(s) + to-one/to-many | Relation picker (`groups` on users) |
| Resource capabilities (create/list) | POC needed a per-resource forum attribute hack (`canCreateProducts`) because nothing generic existed |
| Per-row `canEdit` / `canDelete` | Row actions; `UserResource` already has both |
| Labels resolved server-side | Extension-added fields carry their own keys; one place to resolve |

Dropped or deferred (YAGNI, SCIM shows big IdPs ship without them too):

| Not needed in v1 | Reason |
|---|---|
| Persistent server cache of the descriptor | Building it is a few closure calls, admin-only traffic. Removes the POC's "closures must be pure / not actor-id dependent" caveat entirely (FND-123). ETag + client memo stays |
| A list-of-all-schemas endpoint (SCIM `/ResourceTypes`) | No consumer needs discovery; the admin page knows its type. Add later as `Index` on the same resource |
| A global capabilities doc (SCIM `/ServiceProviderConfig`) | SCIM's flaw: global flags. Capabilities go **per resource and per field** instead |
| SCIM filter grammar, bulk, ETag-on-records, PATCH ops | JSON:API `filter[key]` + `page` + `sort` + dirty-only PATCH already cover it |
| Generic action schema (approve, suspend, …) | Row actions stay JS ItemLists calling custom endpoints (POC non-goal, still right) |
| Replacing `CreateUserModal` / `EditUserModal` | Their `fields`/`data`/`submitData` seams are public and `EditUserModal` is shared with the forum (nicknames extends it there). See D8 |
| ~~Client-side validation, bulk select, CSV, column toggles~~ | **Now in scope, see D11.** These were POC non-goals; D11 adds the generic mechanism and keeps the user list's use of it opt-in |

## 3. Learning from SCIM (AWS IAM Identity Center)

AWS ships a tiny functional subset (filter = `eq` on 2–4 whitelisted attributes, no sort, cursor pages ≤100)
but complete discovery (`/Schemas`, `/ResourceTypes`, `/ServiceProviderConfig`), because clients build UIs
from it. Mapping applied here:

| SCIM attribute key | Here | Source in core |
|---|---|---|
| `type` | `kind` | field class / `Attribute->type` |
| `mutability` readOnly / readWrite / immutable / writeOnly | `writable: {create, update}` + `visible` (writeOnly = `!visible && writable`, e.g. `password`) | `isWritable()` / `isVisible()` per actor |
| `returned` | `visible` | `isVisible()` |
| `required` | `required: {create, update}` (SCIM's single flag is ambiguous for PATCH) | `requiredOnCreate` / `requiredOnUpdate` |
| `canonicalValues` | `constraints.enum` — **enforced** (SCIM only suggests) | `Type::schema()['enum']` |
| `referenceTypes` | `relationship: {types, multiple}` | `Relationship->collections` |
| `uniqueness` | not exposed (server reports 422) | — |
| `subAttributes` / `complex` | not modelled; `Arr` fields need a `ui.widget` | — |
| `description` | `label`, `description` (translated) | `->description()` + `ui.label` |
| ServiceProviderConfig `filter/sort` flags | per resource `sorts[]`, `filters[]`, `search`; per field `sortable`/`filterable` | `resolveSorts()`, `SearchManager` |
| `filter.maxResults` / count | `pagination: {defaultLimit, maxLimit}` | `Index::paginate()` |
| Error schema `scimType` | standard JSON:API errors with `source.pointer` | unchanged |
| Default-valued keys omitted | same: emit only non-default keys | — |

## 4. Decisions

Each lists the recommendation first.

**D1 — Transport: a JSON:API resource, not a raw route.** `Flarum\Api\Resource\ResourceSchemaResource`, type
`resource-schemas`, `Findable`, one `Show` endpoint: `GET /api/resource-schemas/{type}`. This follows the
`ExtensionReadmeResource` precedent and gets JSON:API errors, `Extend\ApiResource` extensibility and
`app.store` on the client for free. *Alt B:* the POC's `Extend\Routes` + `TextResponse`. That works but is a
second, non-JSON:API protocol in `/api`.

**D2 — Opt-in plus gate.** A resource is describable only if it is opted in through the extender
`(new Extend\ApiResource(X::class))->describable()`. The opt-in is stored in the container list
`flarum.api.describable_resources` and checked with `ResourceDescriber::isDescribable()` (`instanceof`, so
subclasses count). It is an extender rather than a marker interface because an extension cannot make a
resource it does not own implement an interface; with the extender, core opts in `users` and any extension
can opt in its own resources or core's. An undescribed type or an unknown type returns 404. A guest gets 401
for any type, before the type is looked up, so guests cannot probe which types are opted in. An actor who
fails both checks below gets 403.
The actor must pass the authorization of the resource's `Index` **or**
`Create` endpoint. No new permission is introduced: "you can describe what you can list or create." FND-102
applies: `Create` with a null model only sees global permissions. That is correct here, because the question is
type-level.
For users on a default install, this admits **every member**: the default seed grants `searchUsers` to members,
and `UserResource`'s `Create` is visible to everyone while `allow_sign_up` is on (FND-295). This is accepted
(decided 2026-10-28). The descriptor is built per actor, so a member only learns the field names, sorts and
filters they could already reach through `GET /api/users` or sign-up. For example, without `editCredentials`
`email` is never readable (`visible: false`) and the `email` filter is absent. While sign-up is open, `email`,
`password` and `username` still appear as create-only inputs, because the sign-up form asks for them anyway.
Create-only keys are absent when the actor cannot create (FND-296). Filters are actor-scoped through
`DescribesFilter::describe(User $actor)` (D6). `capabilities.delete` runs the `Delete` endpoint's own authorization against a blank stored record (so
users need `user.delete`); each row's `canDelete` still decides. An admin page that needs a tighter gate checks its own permission client-side.
*Alt B:* describe every `AbstractDatabaseResource`. This leaks field names of resources no admin UI uses.

**D3 — Inference with synthetic contexts, using a dedicated `DescribeContext`.** The builder evaluates closures
against blank `Create` / `Update` / `Show` / `Index` contexts, as the POC does. The contexts are instances of
`Flarum\Api\Describe\DescribeContext extends Flarum\Api\Context`, which adds
`mode(): 'create'|'update'|'show'|'index'`. Core's `Context` itself is unchanged. A row-dependent closure checks
`$context instanceof DescribeContext` to short-circuit instead of throwing; the `with*()` methods clone, so
derived contexts keep the subclass. Closures see an unsaved model (`exists = true` for update and show) and a
request with no body, query or route parameters. The rules carry
over from the POC hardening: a `visible` closure that throws hides the field (fails closed); a `writable`
closure that throws counts as writable and sets `conditional: true`, because the server still enforces it.
The descriptor must not create `newModel()` side effects. `UserResource::newModel()` sets the locale cookie
for guests, so the builder calls `newModel()` only for an authenticated actor. Guests never reach the builder
(D2).

**D4 — Hints live on the field via `FlarumField::ui(array $hints)`.** This merges into a dedicated `$ui`
property read by `getUi()`, not into `$schema`. `->schema()` replaces the whole schema array, so hints stored
there would be wiped by a later `->schema()` call; a separate bag also keeps them out of any future OpenAPI
output. Several extenders calling `->ui()` merge their keys. The
keys are `widget`, `label`, `placeholder`, `column` (bool|int priority), `priority` and `display` (the
attribute used to label a related record). An extension adds a column with no JS:
`Extend\ApiResource(UserResource::class)->field('suspendedUntil', fn ($f) => $f->ui(['column' => true]))`.

**D5 — Constraints come from `Type::schema()`, not reflection.** json-api-server types already return a
JSON-Schema fragment (`minLength`, `maxLength`, `pattern`, `enum`, `format`, `minimum`, `maximum`). That
replaces the POC's public-property reads, which were rc coupling. Validation rules are still read as strings
for `required`, `email`, `url`, `in:`, `min` and `max`, using the POC's tolerant parser.

**D6 — Search metadata through `SearchManager`, not container strings.** Add
`SearchManager::filters(string $resourceClass): array<FilterInterface>` and
`SearchManager::fulltext(string $resourceClass): bool`. A filter can optionally implement
`Flarum\Search\Filter\DescribesFilter`, whose `describe(User $actor): ?array` returns `{kind, enum?, related?}`, or
null to hide the filter from that actor. It mirrors the permission check of the filter's own `filter()`: core's
`EmailFilter` returns null without `user.editCredentials`, suspend's `SuspendedFilter` returns null unless the actor
can `suspend`, while `GroupFilter` and likes' `LikedFilter` are ungated. Because
an extension cannot add the interface to a filter it does not own (or to a closure filter), there is also an
extender: `(new Extend\SearchDriver(DatabaseSearchDriver::class))->describeFilter(UserSearcher::class, 'key',
array|callable|class-string)`. An array description is static; a callable or invokable class receives the
actor and may return null to hide the filter. `SearchManager::describeFilters(string $resourceClass, User $actor):
array<string, array>` resolves each registered key for that actor: the extender description wins, otherwise the first filter implementing
`DescribesFilter`, otherwise the filter is left out of the descriptor. A description for a key with no
registered filter is ignored. The core `EmailFilter` and `GroupFilter`, plus `SuspendedFilter` and
`LikedFilter` in the bundled extensions, implement the interface. The admin UI must not assume the filter list
is complete. `SearchManager::describeSearch(string $resourceClass, User $actor): array{fulltext, filters}` returns both while
resolving the searcher once; the describer uses it. Two more public methods back these:
`AbstractSearcher::getFilterManager(): FilterManager` and `FilterManager::all(): array<string, list<FilterInterface>>`.

*Which searcher.* The descriptions describe the database searcher (`SearchManager::databaseSearcher()`),
and only while the resource is searched by the database driver (`SearchManager::driverFor()`). A site that uses
another driver for the resource gets `search: false` and no filters, because the database filters would not match
what its `Index` runs (reviewer B1).

*Negation contract.* The client sends a boolean filter's "Yes" as `filter[key]=1` and its "No" as
`filter[-key]=1`. It never sends `filter[key]=0`. `FilterManager` strips the leading `-` and calls `filter()`
with `$negate = true`. So a filter described as `boolean` must constrain the query on `$negate`; its value is
not read. suspend's `SuspendedFilter` does this. A filter that ignores `$negate` returns the "Yes" rows for "No".
On the client, a described `boolean` filter and a filter named after a boolean field are negatable;
`Filter.negatable` overrides that per filter.

**D7 — Labels are resolved server-side.** Resolution order:
1. `ui.label`, as a key or a literal.
2. `->description()` when it is a translation key.
3. `core.admin.resource.{type}.{snake}`, or the extension's own key passed via `ui.label`.
4. The humanised name.

The descriptor carries the locale in its ETag input. Core user columns reuse the existing
`core.admin.users.grid.columns.*` keys.

**D8 — UserListPage keeps its public API and composes the generic table; the user modals stay.**

**Decision:** the release keeps every existing extender and JS extension point working. No modal is replaced. The release adds the seams below. There is no feature flag and no dual implementation. The two user-facing BC breaks are listed in §6: the user list markup is a native `<table>`, and `headerItems()` render inside the table toolbar.

- `UserListPage` keeps:
  - its class path and `AdminPage` base;
  - `headerItems`, `actionItems`, `columns`, `userActionItems`, `headerInfo`, `loadPage(pageNumber)` and `goToPage(page)`, with the same item keys and priorities;
  - the `UserListPage-*` / `UserList-*` CSS classes and the `data-user-id` / `data-column-name` attributes;
  - the 1-based `?page=` URL parameter, 50 rows per page, `modelStatistics.users.total` and the `filter[q]` gambit search.
- `ColumnData` becomes the generic column type. `{name, content(model)}` stays valid and gains optional `sort?: string`, `className?` and `attrs?`, so every existing `extend(UserListPage.prototype, 'columns', …)` keeps compiling and rendering.
- The new behaviour is additive: sortable headers from the descriptor's sorts, the filter toolbar (D12), columns for fields hinted with `ui.column`, and a `delete` row action gated by `canDelete`. The delete opens `ResourceDeleteModal`; GDPR's replaced `Delete` endpoint is still what runs.
- `CreateUserModal` and `EditUserModal` are unchanged. `ResourceTable`'s create and edit hooks open them, through the two seam methods below.

**Seams** (all additive, no behaviour change):
1. `UserListPage` gets two overridable methods, `createModal()` (default `CreateUserModal`) and `editModal(user: User)` (default: a stable lazy loader `() => import('…/EditUserModal')`, which keeps today's lazy-loaded chunk). Both are typed as anything `app.modal.show()` accepts. `ResourceTable`'s `onCreate` / `onEdit` call them, with the same `onCreated` / `onsaved` callbacks and the same `modelStatistics.users.total` bump. They are hooks for swapping the modal. No switch to a generic form modal is scheduled.
2. `ResourceFormModal` is built and tested against the users descriptor but **not wired** into any page. Its overridable surface mirrors the old modals (`fields()` ItemList, `data()`, `submitData()`, `onsaved`). A jest parity test pins its writable field names against `EditUserModal`'s core fields (username, email, password, groups). It ships, together with `ResourceForm` and `common/resource/inputs/*`, but it is not documented: there is no stable promise of a form UI yet. See the open decision in §7.
3. The descriptor carries `schemaVersion: 1`, always emitted (the one exception to default-omission). The `ui` keys reserved for forms (`group`, `priority`, `widget`, `placeholder`) are documented now, although the release consumes only `column`, `display` and `label` (plus `widget: 'hidden'`, see §5).
4. All new public JS and PHP API (§6, "New public API") follows the normal BC promise. The descriptor shape is versioned by `schemaVersion`.

*Rejected alternative:* replacing the modals with `ResourceFormModal`. This would break `fields()`/`data()`/`submitData()` extenders and the forum's `EditUserModal` use (nicknames).

**D9 — Frontend location and names.** The generic code lives in core under:
- `js/src/common/resource/`: `types`, `descriptor` (loader), `ResourceDefinition`, `fieldsFromDescriptor`, `resolvers`, `InputFactory`, `inputs/*`, `ResourceListState extends PaginatedListState`, `ResourceFormState`, `ResourceForm`, `errors`.
- `js/src/admin/components/resource/`: `ResourceTable`, `ResourceFormModal`, `ResourceDeleteModal`.

Import paths are `flarum/common/resource/*` and `flarum/admin/components/resource/*`. `TabbedExtensionPage`
stays out of v1; it is an ExtensionPage concern and is unrelated to users.

**D10 — Relation to work item #169 Proposal 1.** Core supersedes the lib. Until a core release ships it, GB
extensions keep the base-products-sealed copy. The lib is not extracted. Once core ships, base-products and
translate switch their imports to `flarum/common/resource/*`. The descriptor shape is kept a superset of the
POC's shape so that `ProductDefinition` ports mechanically. **Risk:** this is the `flarum/framework` 2.x
line. If the change is meant for upstream, the Flarum core team must agree to the RFC first, and Proposal 1
remains the fallback. All new API falls under the normal BC promise once it is released, so upstream feedback
must land before the release. The descriptor shape is versioned through `schemaVersion`.

**D11 — Table and form features: client-side validation, bulk select, CSV export, column toggles.** The
generic mechanism ships in this release. Each feature is opt-in per `ResourceDefinition`, so a page gets nothing
it did not ask for. The user list opts in only where the result is purely additive (D8). Each feature names a
possible later path, so the release ships the cheap and safe version without blocking a later one.

| Feature | This release (built) | Kept out, prepared for later |
|---|---|---|
| **Client-side validation** | `common/resource/validation.ts`: `validateValue(field: FieldDescriptor, value, mode: 'create'\|'update'): string[]`, a pure function over `required`, `nullable` and `constraints` (`minLength`, `maxLength`, `pattern` + `patternFlags`, `enum`, `format` email/url, `minimum`, `maximum`). `ResourceFormState.validate()` runs it on submit and on blur, blocks the request when errors exist, and puts messages in the same slots `mapErrors` fills, so one rendering path serves both. While updating, only changed fields are checked. Fields marked `conditional` are skipped, as is uniqueness. The server stays authoritative. A `pattern` the JS `RegExp` cannot compile is skipped and logged once with `fireDebugWarning`. | Async checks such as "username taken" (`ui.asyncValidate` is reserved). The user modals gain nothing; they would get validation only by moving to a generic form (D8, §7). |
| **Bulk select** | `ResourceListState.selection`: a page-scoped set of ids, cleared on page, filter, search or sort change. `ResourceDefinition.bulkActions(selection, context): ItemList` is empty by default; `context` (`BulkActionContext`: `{list, runner, showResult}`) is built by the table, because `common` may not import the admin result modal. The checkbox column renders only when that list has items. Built-in `bulkDelete(context)` helper: confirms with the deletable count, skips rows whose `canDelete` is false, runs `DELETE` per row through a `BulkRunner` with concurrency 3, then opens `BulkResultModal` with a per-row summary (n deleted, m failed with reasons, k skipped). Each row goes through the normal endpoint, so gdpr's replaced `Delete` still runs per user. | "Select all N matching" (`selection.mode: 'ids' \| 'matching'`; `'matching'` is reserved as `never`). Swapping `BulkRunner` for json-api-server's `Atomic` extension once core registers it: one request, all-or-nothing. The `BulkRunner` interface makes that a single replacement. |
| **CSV export** | Client-side: `exportCsv(state, {columns, maxRows = 5000, onProgress, signal})` pages through the current filter and sort at `pagination.maxLimit`, with progress and abort, and downloads `{type}-{YYYY-MM-DD}.csv` with a UTF-8 BOM. A cell's value comes from the column's `csv?(model)`, otherwise the raw attribute named by the descriptor field, otherwise the column is skipped (rendered vnodes are never stringified). Escaping follows RFC 4180, plus a formula-injection guard: a value starting with `= + - @ \t \r` gets a `'` prefix. The export uses the same API, so it can never contain data the actor could not already see. It is gated by `ResourceDefinition.canExport()`, which defaults to `capabilities.list`. | A server-side streaming export (`GET /api/{type}` with `Accept: text/csv`, through the searcher) for sets larger than `maxRows`. `exportCsv` keeps its signature and switches transport. |
| **Column toggles** | `ColumnData` gains `toggleable?: boolean` (default `true`) and `hidden?: boolean` (default-hidden). A "Columns" dropdown in `ResourceTable`'s `displayItems()` (row 2, D12) lists the toggleable columns with checkboxes. Only explicit choices are stored, as `{columnKey: visible}` in `localStorage` under `flarum.resourceTable.{type}.columns`, so a column the user never toggled follows its `hidden` default. Every read and write is wrapped in try/catch, the table works without storage, and stale keys for columns that no longer exist are ignored, not deleted. | Server-side persistence in user preferences (`Extend\User()->registerPreference('resourceTable.{type}.columns')`), so a choice follows the admin across devices. The storage access is behind `ColumnPreferences` (`load()`/`save()`), so a later change swaps only the backend. |

User-list defaults, all additive; the existing `emailAddress` show/hide toggle stays:
- **Column toggles:** on. `id`, `username` and `userActions` are `toggleable: false`.
- **CSV export:** on, for admins only. `UserListPage.canExport()` returns `app.session.user.isAdmin()`, because emails are PII. A mod with only `searchUsers` sees no export button.
- **Bulk select:** **off**. `UserListPage.bulkActionItems(context): ItemList` is a new seam and is empty, so no checkbox column appears. An extension can add items (for example `definition.bulkDelete(context)`) and get the column.
- **Client-side validation:** none, because the user modals stay (D8).
- **One actions column.** The user list passes `actionsColumn={false}`, so its own `userActions` column (the `userActionItems(user)` dropdown) is the only actions column.

Descriptor additions, which are additive, so `schemaVersion` stays `1`:
- `capabilities.delete`: the `Delete` endpoint exists and the actor passes its authorization against a blank stored record (users: `user.delete`). A policy that decides per record may deny here; per-row `canDelete` still decides.
- `constraints.patternFlags`: the PCRE flags translated to JS (`i`, `m`, `s`, `u`; anything else drops the pattern).

**D12 — Filter toolbar.** `ResourceTable` renders search and filters as a labelled toolbar in two rows.

- **Row 1 (`toolbarItems()` → `viewItems()`):**
  - *Search.* A core `Input`, shown when the descriptor has `search`. When the type has enabled gambits it sits
    inside the gambit autocomplete, so `key:value` can be typed.
  - *Filter dropdowns.* One button per filter, labelled "{label}: {value}". The first `inlineFilters` (default 3,
    `ResourceTable.INLINE_FILTERS`) are on the toolbar as `filter-{key}`. The rest are under "More filters"
    (`moreFilters`); picking one adds its button. A filter with a value is always inline.
  - *Boolean filters* offer Any, Yes and No. No is sent as `filter[-key]=1` (D6 negation contract).
  - *Relationship filters* list the `related` records through `RelationOptions`: a search box, "Load more" while
    the response has a next link, and "Retry" after a failed request. `Filter.exclude(model)` leaves records out;
    the user list excludes the guest group. A relationship filter's record label is the `ui.display` of the
    same-named field, else of a relationship field over the same type (users' `group` takes `nameSingular` from
    `groups`), else `name`.
- **Row 2 (`subToolbarItems()`, rendered only when it has items):**
  - *Chips* (`chipItems()`): one per filter with a value, each with a remove button, plus "Clear all". A chip is
    compared with the gambits typed in the search. When a gambit sends the exact same key, sign included, the chip
    is marked overridden, because the gambit wins on the server. When a gambit sends the key with the opposite
    sign (`-suspended` against `suspended`), the chip is marked as a conflict.
  - *Display* (`displayItems()`): `columns` (the "Columns" dropdown) and `export` ("Export CSV").
- **Compact mode.** Below core's phone breakpoint, or with the `compactFilters` attr, the filters fold into one
  "Filters (n)" sheet (`viewItems()` key `filters`).
- **Search-covered filters.** A filter whose key a gambit covers can be left to the search box, where it is typed
  as `key:value`. The `searchCoveredFilters` attr sets the policy. The default is `'all'`: every covered filter is
  left out of the toolbar. `'text'` leaves out only text-like filters; `'none'` leaves out none.
  `Filter.hideWhenSearchable` overrides the policy per filter; `false` keeps the dropdown.
  `UserListPage.searchCoveredFilters()` returns `'all'`. On users, `group`, `suspended` and `email` are typed as
  `group:`, `is:suspended` and `email:`. likes' `liked` has no gambit, so it keeps its dropdown.
- **Row menus** sit inside the horizontally scrolling table container, which also clips vertically. `floatMenus()`
  positions an open row menu `fixed` at its toggle and keeps it there on scroll and resize, so a menu below a
  short table is not cut off.
- **User count.** The user list's count slot shows "N matching users of M" while a search or filter narrows the
  list (`core.admin.users.matching_users`, N is the API total for the current query), else the forum total.
- **User list header.** `UserListPage.headerItems()` render inside this toolbar: `search` in the search slot; items
  ranked above `totalUsers` in the view group at their own priority (`header-{key}`); `totalUsers` in the count
  slot; the rest (`actions`, "New User") in the actions group, which keeps the class `UserListPage-header`. The user
  list has no sort dropdown: it sorts by the column headers.

## 5. Descriptor shape (v1)

`GET /api/resource-schemas/users` as admin:

```json
{
  "data": {
    "type": "resource-schemas",
    "id": "users",
    "attributes": {
      "schemaVersion": 1,
      "endpoint": "/users",
      "capabilities": { "list": true, "create": true, "delete": true },
      "pagination": { "defaultLimit": 20, "maxLimit": 50 },
      "defaultSort": "-joinedAt",
      "search": true,
      "sorts": ["username", "commentCount", "discussionCount", "lastSeenAt", "joinedAt"],
      "filters": [
        { "key": "email", "kind": "string" },
        { "key": "group", "kind": "relationship", "related": "groups" },
        { "key": "suspended", "kind": "boolean" }
      ],
      "fields": [
        { "name": "username", "kind": "string", "label": "Username",
          "required": { "create": true }, "writable": { "create": true, "update": true },
          "constraints": { "minLength": 3, "maxLength": 30, "pattern": "^(?![0-9]*$)[a-z0-9_-]+$", "patternFlags": "i" }, "sortable": true,
          "ui": { "column": 90 } },
        { "name": "email", "kind": "string", "label": "Email",
          "writable": { "create": true, "update": true }, "constraints": { "format": "email" } },
        { "name": "password", "kind": "string", "label": "Password", "visible": false,
          "writable": { "create": true, "update": true }, "constraints": { "minLength": 8 } },
        { "name": "joinTime", "kind": "datetime", "label": "Joined", "ui": { "column": 80 } },
        { "name": "groups", "kind": "toMany", "label": "Groups",
          "relationship": { "types": ["groups"] }, "writable": { "update": true }, "ui": { "display": "nameSingular" } }
      ]
    }
  }
}
```

`defaultSort` is the Index endpoint's default sort string, omitted when it is null (core's users Index has
none; the example shows the shape). `pagination` is omitted when the Index endpoint does not paginate.
The full list of top-level keys is `ResourceDescriber::KEYS`.

`required.create` also counts a `required_without` / `required_without_all` rule when every field it names is
either not described or has `ui.widget === 'hidden'`, because the client cannot send the alternative. That is
why `username`, `email` and `password` (`requiredOnCreateWithout(['token'])`, `token` hidden) report
`required: { create: true }`. `required_with`, `required_if` and the like never count.

`schemaVersion` is always emitted. It is an exception to default-omission, so clients can detect shape changes. New optional keys do not raise it; a change that would break a version-1 client does.

The `ui` keys `column`, `display` and `label` are consumed. The keys `group`, `priority`, `widget` and `placeholder` are reserved for forms: they are documented and accepted, but not consumed. One exception: `widget: 'hidden'` marks a field no form offers (users' `token`).

Defaults, which are omitted from the response (every key except `schemaVersion`):

| Key | Default |
|---|---|
| `visible` | `true` |
| `writable` (each mode) | `false` |
| `required` (each mode) | `false` |
| `nullable` | `false` |
| `sortable` | `false` |
| `filterable` | `false` |
| `conditional` | `false` |
| `ui` | `{}` |

Response headers: `ETag` is the sha1 of the attributes, together with `Cache-Control: private`. A request
with a matching `If-None-Match` gets a 304.

## 6. Must-survive surface

The tests in Task 9 pin each entry below.

| Surface | Today | After |
|---|---|---|
| `Extend\ApiResource(UserResource::class)` fields/endpoints/sorts (suspend, nicknames, gdpr, flags, messages, mentions, realtime, subscriptions) | resolved via `Extendable` | unchanged; they also appear in the descriptor |
| `Extend\SearchDriver->addFilter(UserSearcher, …)` (suspend `suspended`, likes `liked`) and `setFulltext` (nicknames) | used by `Index` | unchanged; listed in `filters[]` |
| gdpr `endpoint(Delete, UserResourceDeleteEndpoint)` | replaced delete | unchanged; the table's delete calls `DELETE /api/users/{id}` |
| `GET /api/users` limit 20 / max 50, default include `groups`, `filter[q]` gambits | unchanged | unchanged |
| `UserListPage.prototype.{headerItems,actionItems,columns,userActionItems,headerInfo,loadPage,goToPage}` | public | same signatures, keys, priorities |
| **BC break:** where `UserListPage.headerItems()` render | a row of its own above the list (`.UserListPage-header`) | inside the table toolbar (D12): `search` in the search slot, items ranked above `totalUsers` in the view group, `totalUsers` in the count slot, the rest in the actions group, which keeps the class `UserListPage-header`. The old header row is gone. **Release-note item** |
| **BC break:** user list markup | CSS-grid of `div`s (`.UserListPage-grid`, `--columns` inline style, ARIA table roles) | a native `<table>`; `UserListPage-grid` (+ `--loading`/`--loadingPage`) sits on the table container only, the root is `UserListPage-table`; class names and `data-*` attributes on cells are kept, `--columns` is gone. **Release-note item:** third-party CSS relying on `display: grid` / `grid-template-columns` must be revisited. Private members `pageNumber`, `loadingPageNumber`, `isLoadingPage`, `getTotalPageCount`, `setPageNumberInUrl` are removed (TS-private, unused in bundled extensions) |
| gdpr `extend(UserListPage.prototype, 'userActionItems')` → `export-data` | dropdown item | still rendered in the row actions dropdown |
| `CreateUserModal` `fields/submitData/onCreated`, `EditUserModal` `oninit/fields/data` | — | untouched |
| `UserListPage.prototype.{createModal,editModal}` (new) | — | additive seams; defaults return `CreateUserModal` / a lazy loader resolving to `EditUserModal`; an override changes which modal opens, with the same `onCreated` / `onsaved` and total bump |
| `ResourceFormModal`, `ResourceForm`, `common/resource/inputs/*` (new) | — | shipped and tested against users, not wired and not documented; mirrors `fields()/data()/submitData()/onsaved`. Open decision, §7 |
| Descriptor `schemaVersion` (new) | — | always `1` |
| `ColumnData` | `{name, content}` | adds optional `sort`, `className`, `attrs`, `toggleable`, `hidden`, `csv`; old shape still valid |
| `UserListPage.prototype.{bulkActionItems,canExport,searchCoveredFilters}` (new, D11/D12) | — | additive seams; `bulkActionItems(context)` is empty, so no checkbox column; `canExport()` is admin-only; `searchCoveredFilters()` returns `'all'` |
| User list toolbar (D11, D12) | search, total, create | plus filter dropdowns, chips, a "Columns" dropdown and, for admins, "Export CSV"; existing items keep their keys and priorities |
| Stability | — | all new API (below) is under the normal BC promise; the descriptor shape is versioned by `schemaVersion` |
| `Flarum\Api\Context` | — | unchanged; describe-only state lives on the `DescribeContext` subclass |
| `core.lib.pagination.*`, `core.admin.users.*` locale keys | — | kept; new keys go under `core.admin.resource.*` / `core.lib.resource.*` |
| Admin nav `userList`, route `users` | — | unchanged |

**New public API** (all under the normal BC promise):

- PHP:
  - `Flarum\Api\Resource\ResourceSchemaResource` (`GET /api/resource-schemas/{type}`);
  - `Flarum\Api\Describe\ResourceDescriber` (`isDescribable()`, `canDescribe()`, `describe()`, `KEYS`, `SCHEMA_VERSION`) and `Flarum\Api\Describe\DescribeContext`;
  - `FlarumField::ui()` / `getUi()`;
  - `Extend\ApiResource::describable()` and `Extend\SearchDriver::describeFilter()`;
  - `Flarum\Search\Filter\DescribesFilter`;
  - `SearchManager::filters()`, `fulltext()`, `describeFilters()`, `describeSearch()`;
  - `AbstractSearcher::getFilterManager()` and `FilterManager::all()`.
- JS:
  - `flarum/common/resource/*`: `ResourceDefinition`, `ResourceListState`, `ResourceFormState`, `descriptor` (`loadDescriptor`, `clearDescriptorCache`), `types` (incl. `Filter.negatable`, `Filter.exclude`, `Filter.hideWhenSearchable`), `fieldsFromDescriptor`, `resolvers`, `errors`, `validation`, `csv`, `ColumnPreferences`, `BulkRunner`, `inputs/RelationOptions`;
  - `flarum/admin/components/resource/*`: `ResourceTable` (incl. `INLINE_FILTERS` and the `inlineFilters`, `compactFilters`, `searchCoveredFilters`, `actionsColumn`, `tableClassName` attrs), `ResourceDeleteModal`, `ColumnsDropdown`, `BulkResultModal`, `floatMenus`;
  - the `UserListPage` seams `createModal()`, `editModal()`, `bulkActionItems()`, `canExport()`, `searchCoveredFilters()`;
  - `Pill`'s new `deleteLabel` attribute.
- Shipped but undocumented: `ResourceFormModal`, `ResourceForm`, `InputFactory` and `common/resource/inputs/*` other than `RelationOptions` (§7).

**Release notes for the next minor.** Two user-facing BC breaks:
1. The admin user list is a native `<table>`. Third-party CSS that relies on `display: grid`, `grid-template-columns` or the `--columns` style breaks.
2. `UserListPage.headerItems()` render inside the table toolbar, as above. The old header row is gone.

## 7. Open decision: the shipped form components

`ResourceFormModal`, `ResourceForm` and the form inputs ship, because `ResourceTable`'s default create and edit open
`ResourceFormModal` and the parity test pins it. No core page uses them, so the guide does not document them. Core
auto-exports by path, so an extension can still import them, and under the normal BC promise an importable class
is hard to change. The owner decides between:

- **(a) Keep shipping them undocumented.** No churn; `ResourceTable` keeps a working default create and edit.
  The risk is an implied promise on a form UI nobody has used yet.
- **(b) Remove them until a page uses them.** `ResourceTable` then shows Create and Edit only when `onCreate` /
  `onEdit` are passed. The tests and the POC port move to the branch that adds the first form page.

**Recommendation: (b).** The BC promise now covers everything importable, and the form is the least proven part:
it has no consumer in core, and its `fields()` surface would freeze before the first real port tells us what it
needs. Removing it costs one attr check in `ResourceTable` and keeps the D8 seams as the way to swap the modal.

## 8. Non-goals

- Migrating other core admin pages to the generic table in v1. `EditGroupModal`, permissions and extensions stay hand-written.
- Forum-side `ResourceForm` consumers. The form is in `common/` and works there, but no forum page migrates.
- Translatable fields, which are base-products' contract.
