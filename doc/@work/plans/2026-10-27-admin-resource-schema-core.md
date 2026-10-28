# Resource schema endpoint + generic admin CRUD in core — Implementation Plan

> **For implementing agents:** execute task-by-task. Use `verification-before-completion` after each task. Spawn `flarum-tester` for the test step when convenient. Steps use checkbox (`- [ ]`) syntax for tracking.

**Status:** draft. It becomes `approved` once the spec's D1–D10 are signed off; Task 0 is that gate.
**Spec:** `doc/@work/specs/2026-10-27-admin-resource-schema-core.md`
**Flarum target:** `2.x` (`flarum/framework` monorepo, `framework/core`)
**Goal:** Add a per-actor resource schema endpoint and generic admin table/form components to core, and rebuild the admin user list on them without breaking any existing extender or JS extension point.
**Architecture summary:**
- **Schema resource.** `ResourceSchemaResource` (JSON:API `resource-schemas`, `Show` only) asks `ResourceDescriber` to build the descriptor. `ResourceDescriber` builds it from `resolveFields()`, `resolveSorts()` and `resolveEndpoints()`, `Type::schema()` and `SearchManager`, evaluated against synthetic contexts for the current actor.
- **Generic frontend.** `flarum/common/resource/*` turns a descriptor into fields, inputs and list state. `flarum/admin/components/resource/ResourceTable` renders it.
- **User list.** `UserListPage` keeps its public methods and passes them into `ResourceTable`.

## Global Constraints

- Paths are relative to `framework/core/` unless they start with `extensions/`.
- PHP 8.2+. Follow core code style (`composer test:phpstan` and `php-cs-fixer` configs at the repo root).
- No change to any existing `UserResource` endpoint, field, sort, default include or pagination (limit 20, max 50).
- New locale keys go under `core.admin.resource.*` (admin UI) and `core.lib.resource.*` (common). Existing `core.admin.users.*` and `core.lib.pagination.*` keys are reused, never renamed.
- JSON:API type `resource-schemas`. Route name `resource-schemas.show`. Path `/api/resource-schemas/{type}`.
- UI hints live in a dedicated `$ui` bag on `FlarumField` (`ui()` / `getUi()`), never inside `$schema`, so `->schema()` cannot wipe them.
- Describability and filter descriptions are opted in through extenders (`Extend\ApiResource::describable()`, `Extend\SearchDriver::describeFilter()`), so an extension can opt in resources and filters it does not own. There is no marker interface on resources.
- Core's `Flarum\Api\Context` gains nothing. Describe-only state lives on `Flarum\Api\Describe\DescribeContext extends Context`.
- The descriptor omits default-valued keys. Defaults are in spec §5. The one exception is `schemaVersion`, which is always emitted (`1` in 2.0).
- 2.0 ships no modal replacement (spec D8). `CreateUserModal` / `EditUserModal` stay the modals `UserListPage` opens. `ResourceFormModal` is built and tested but not wired anywhere.
- All new public API (`ResourceTable`, `ResourceFormModal`, `ResourceDeleteModal`, `ResourceDescriber`, `DescribeContext`, `DescribesFilter`, `->ui()`, `->describable()`, `->describeFilter()`) is marked `@experimental` in docblocks and docs. Only the descriptor endpoint shape is versioned.
- New `UserListPage` API is limited to these additive seams: `createModal()` / `editModal(user)` (D8), and `bulkActionItems()` / `canExport()` (D11). Existing items, keys, priorities, CSS classes and URLs do not change. The only new UI is header and table additions (sort headers, filter row, row delete, "Columns" dropdown, admin-only "Export CSV"). *(This replaces an earlier "no visual change" line that contradicted Task 8; confirm at Task 0.)*
- D11 features are opt-in per `ResourceDefinition`. `bulkActions()` is empty and `canExport()` defaults to `capabilities.list`. Column preferences use `localStorage` only behind `ColumnPreferences`, with every access in try/catch.
- CSV cells never stringify vnodes, and every cell passes the formula-injection guard (spec D11).
- No persistent server cache for descriptors (spec §2). Use ETag/304 only.
- Every `UserListPage` public method keeps its name, signature, item keys and priorities (spec §6).
- JS imports inside `common/resource` and `admin/components/resource` may reference only `flarum/common/*`, `flarum/admin/*` and `mithril`, never a feature such as users.

## Review Focus

- **Guest, or an actor who can neither list nor create users,** calling `GET /api/resource-schemas/users`: expect 403, never a partial descriptor. Tested in Task 4.
- **Describing users as a guest** must not set the locale cookie (the `UserResource::newModel` side effect). Tested in Task 4: no `Set-Cookie: locale` on the 403 response.
- **A `visible` closure that throws on a blank model** hides the field; a `writable` closure that throws sets `conditional: true`. Tested in Task 3 with a scratch resource.
- **gdpr's replaced `Delete` endpoint** is the one the table's delete action reaches (anonymise, not hard delete). Tested in Task 9.
- **An extension `extend(UserListPage.prototype, 'columns')` that returns old-shape `{name, content}`** still renders, and an unknown key doesn't break the sort header. Tested in Task 8.
- **CSV cell `=HYPERLINK(...)`, or a username starting with `@` or `-`**, is exported with a `'` prefix. A comma, quote or newline inside a value round-trips per RFC 4180. Tested in Task 7b.
- **A bulk delete where one row fails** (gdpr refuses it, or the row was already deleted) still deletes the others, and the summary names the failed row. Tested in Task 7c.
- **`localStorage` throwing** (private mode) leaves column toggles working for the session. Tested in Task 7b.
- **A PCRE pattern JS can't compile** (possessive `++`, the `x` flag) skips client validation for that field. It never blocks a valid submit. Tested in Task 6b.

---

## Files

### Create (PHP)
- `src/Api/Describe/DescribeContext.php`: `Context` subclass for the synthetic describe contexts, with `mode()` / `withMode()`.
- `src/Api/Describe/ResourceDescriber.php`: builds the descriptor array for `(AbstractDatabaseResource, User $actor)`.
- `src/Api/Describe/FieldDescriber.php`: describes a single field (kind, flags, constraints, label, ui).
- `src/Api/Describe/RuleHints.php`: tolerant parser from string validation rules to constraint hints (ported from POC `DescriptorBuilder`).
- `src/Api/Resource/ResourceSchemaResource.php`: `resource-schemas` JSON:API resource with `Show`, gate and ETag.
- `src/Search/Filter/DescribesFilter.php`: optional interface `describe(): array`.

### Modify (PHP)
- `src/Api/Schema/Concerns/FlarumField.php`: add `ui(array $hints): static` and `getUi(): array`.
- `src/Extend/ApiResource.php`: add `describable(): self`, which appends the resource class to `flarum.api.describable_resources`.
- `src/Extend/SearchDriver.php`: add `describeFilter(string $searcherClass, string $filterKey, array|callable|string $description): self`, stored in `flarum.search.filter_descriptions`.
- `src/Search/SearchManager.php`: add `filters(string $resourceClass): array`, `describeFilters(string $resourceClass): array` and `fulltext(string $resourceClass): bool`.
- `src/Search/SearchServiceProvider.php`: register the empty `flarum.search.filter_descriptions` binding.
- `src/Api/ApiServiceProvider.php`: register the `flarum.api.describable_resources` binding (empty in Task 3; `UserResource::class` from Task 5) and `ResourceSchemaResource` in `flarum.api.resources`.
- `src/Api/Resource/UserResource.php`: `->ui([...])` on the column fields only (no interface).
- `src/User/Search/Filter/EmailFilter.php`, `GroupFilter.php`: implement `DescribesFilter`.
- `extensions/suspend/src/Query/SuspendedFilter.php`, `extensions/likes/src/Query/LikedFilter.php` (verify path): implement `DescribesFilter`.
- `locale/core.yml`: `core.admin.resource.*` and `core.lib.resource.*`.

### Create (JS)
- `js/src/common/resource/types.ts`: `ResourceDescriptor`, `FieldDescriptor` and `FilterDescriptor` types, matching spec §5.
- `js/src/common/resource/descriptor.ts`: `loadDescriptor(type)`, which memoises per session through `app.store.find('resource-schemas', type)`, plus `clearDescriptorCache()`.
- `js/src/common/resource/ResourceDefinition.ts`, `fieldsFromDescriptor.ts`, `resolvers.ts`, `errors.ts`.
- `js/src/common/resource/inputs/{InputFactory.tsx,defaults.tsx,RelationInput.tsx}`.
- `js/src/common/resource/{ResourceListState.ts,ResourceFormState.ts,ResourceForm.tsx}`.
- `js/src/common/models/ResourceSchema.ts`: store model for `resource-schemas`, registered in `common/Store` models.
- `js/src/admin/components/resource/{ResourceTable.tsx,ResourceFormModal.tsx,ResourceDeleteModal.tsx}`.
- `js/src/common/resource/validation.ts`: `validateValue()` (D11).
- `js/src/common/resource/csv.ts`: `toCsv(rows, columns)` and `exportCsv(state, opts)` (D11).
- `js/src/common/resource/ColumnPreferences.ts`: `load(type)` / `save(type, choices)` (explicit `{key: visible}` choices), backed by localStorage (D11).
- `js/src/common/resource/BulkRunner.ts`: `BulkRunner` interface plus `SequentialBulkRunner` (concurrency 3) (D11).
- `js/src/admin/components/resource/{ColumnsDropdown.tsx,BulkResultModal.tsx}` (D11).
- `less/admin/ResourceTable.less`.

### Modify (JS)
- `js/src/admin/components/UserListPage.tsx`: compose `ResourceTable` while keeping the public API.
- `js/src/common/compat` / export registry: nothing to add. Core auto-exports by path; verify with `yarn build`.

### Test
- `tests/integration/api/resource_schemas/ShowTest.php`: transport, gate, 404s and ETag.
- `tests/integration/api/resource_schemas/UsersSchemaTest.php`: users descriptor content.
- `tests/integration/api/resource_schemas/InferenceTest.php`: scratch resource for throw/conditional/default rules.
- `tests/unit/Api/Describe/RuleHintsTest.php`
- `tests/integration/extenders/ApiResourceUiHintTest.php`
- `js/tests/unit/common/resource/*.test.ts`: fieldsFromDescriptor, resolvers, errors, ResourceFormState.
- `js/tests/integration/admin/components/ResourceTable.test.ts`
- `js/tests/integration/admin/components/UserListPage.test.ts`: extended to cover the surface contract.
- `extensions/suspend/tests/integration/api/UsersSchemaTest.php`, `extensions/gdpr/tests/integration/...`: extension compatibility.

---

### Task 0 — Decision gate

**Type:** HITL
**Blocked by:** None

- [ ] **Step 1:** Present spec D1–D10 to the team, adding the "Proposal 2" note to work item #169. Pay particular attention to the following:
  - D2: no new permission.
  - D8: confirm BC in 2.0 with 2.1 seams (`createModal()` / `editModal()`, unwired `ResourceFormModal`, `schemaVersion: 1`, `@experimental` markings). Recommended outcome: yes.
  - D10: is this upstream-bound or GB-fork-only?
  - D11: the user-list defaults for 2.0. Recommended: column toggles on, CSV admin-only, bulk select off behind the `bulkActionItems()` seam, and no client validation until 2.1. Also confirm the replaced "no visual change" constraint in Global Constraints.
- [ ] **Step 2:** Record the outcomes in the spec. Set the spec to `approved` and this plan to `approved`.

### Task 1 — `FlarumField::ui()` hints

**Type:** AFK · **Blocked by:** Task 0

**Files:**
- Modify: `src/Api/Schema/Concerns/FlarumField.php`
- Test: `tests/integration/extenders/ApiResourceUiHintTest.php`

**Interfaces:**
- Produces:
  - `FlarumField::ui(array $hints): static`, which merges into a dedicated `protected array $ui` property. `$schema` is never touched, so a later `->schema([...])` keeps the hints. (Revised after review: hints first lived in `schema['x-flarum-ui']`.)
  - `FlarumField::getUi(): array`
  - `ui()` carries an `@experimental` docblock tag. The docblock lists the consumed keys (`column`, `display`, `label`) and the keys reserved for forms in 2.1 (`group`, `priority`, `widget`, `placeholder`).
  - *(Removed after review: `Context::describing()` / `withDescribing()`. Replaced by `DescribeContext` in Task 3.)*

- [x] **Step 1: Write the failing test.**
  - `test_ui_hints_merge_across_extenders`: `Extend\ApiResource(UserResource::class)->field('username', fn ($f) => $f->ui(['column' => 90]))`, applied twice with `['label' => 'x']`. Assert `getUi() === ['column' => 90, 'label' => 'x']`.
  - `test_ui_does_not_clobber_schema`: call `->schema(['foo' => 1])` and then `->ui([...])`. Assert that `getSchema()['foo'] === 1`.
  - `schema_after_ui_keeps_the_hints`: call `->ui([...])` and then `->schema([...])`. Assert the hints survive and `getSchema()` has no `x-flarum-ui` key.
- [x] **Step 2: Run the test and confirm RED.** Command: `composer test -- --filter ApiResourceUiHintTest`, run from `framework/core`. Expected: `Call to undefined method ...::ui()`. (Deps live in the repo-root `vendor`; the working command is `../../vendor/bin/phpunit -c tests/phpunit.integration.xml --filter ApiResourceUiHintTest` from `framework/core`.)
- [x] **Step 3: Implement `ui()` and `getUi()` on `FlarumField`** with a dedicated `$ui` property.
- [x] **Step 4: Run the test and confirm GREEN.**
- [ ] **Step 5: Commit.** `feat(api): field ui hints`

### Task 2 — Search metadata accessors + `DescribesFilter`

**Type:** AFK · **Blocked by:** Task 0

**Files:**
- Create: `src/Search/Filter/DescribesFilter.php`
- Modify: `src/Search/SearchManager.php`, `src/User/Search/Filter/EmailFilter.php`, `src/User/Search/Filter/GroupFilter.php`
- Test: `tests/integration/search/SearchManagerMetadataTest.php`

**Interfaces:**
- Produces:
  - `DescribesFilter::describe(): array{kind: 'string'|'boolean'|'enum'|'relationship', enum?: list<string>, related?: string}`, with an `@experimental` docblock tag.
  - `SearchManager::filters(string $resourceClass): list<FilterInterface>`, for the database driver's searcher for that model. Returns `[]` when the resource is not searchable.
  - `SearchManager::fulltext(string $resourceClass): bool`
  - `Extend\SearchDriver::describeFilter(string $searcherClass, string $filterKey, array|callable|class-string $description)`, `@experimental`. It describes any filter key, including filters the extension does not own or closure-based ones.
  - `SearchManager::describeFilters(string $resourceClass): array<string, array>`. Per registered key: the extender description wins, otherwise the first filter implementing `DescribesFilter`, otherwise the key is left out. Descriptions for unregistered keys are ignored.
- These accessors replace the POC's reads of the `flarum.search.filters` and `flarum.search.fulltext` container bindings.

- [x] **Step 1: Write the failing test.** (Implemented: `filters()` returns an array keyed by filter key, via `FilterManager::all()` and `AbstractSearcher::getFilterManager()`.)
  - `filters(User::class)` returns keys containing `email` and `group`.
  - `EmailFilter->describe() === ['kind' => 'string']`.
  - `GroupFilter->describe() === ['kind' => 'relationship', 'related' => 'groups']`.
  - `fulltext(User::class) === true`.
  - With `Extend\SearchDriver->addFilter(UserSearcher::class, CustomFilter::class)`, the custom key appears.
  - `describeFilters(User::class)` returns the `email` / `group` interface descriptions; an undescribed custom filter is left out; `describeFilter()` describes a filter the extension does not own; an extender description (array or closure) wins over the interface; a description for an unregistered key is ignored.
- [x] **Step 2: Run the test and confirm RED.** (Skipped: the test was written after the code.)
- [x] **Step 3: Implement the accessors and the two `describe()` methods.**
- [x] **Step 4: Run the test and confirm GREEN.** Also run `composer test -- --filter 'users\\\\SearchTest|GroupSearchTest'` to confirm search is unchanged.
- [ ] **Step 5: Commit.** `feat(search): expose searcher filter metadata`

### Task 3 — `ResourceDescriber` (inference core)

**Type:** AFK · **Blocked by:** Tasks 1, 2

**Files:**
- Create: `src/Api/Describe/{ResourceDescriber,FieldDescriber,RuleHints,DescribeContext}.php`
- Modify: `src/Extend/ApiResource.php` (`describable()`), `src/Api/ApiServiceProvider.php` (`flarum.api.describable_resources`)
- Test: `tests/unit/Api/Describe/RuleHintsTest.php`, `tests/integration/api/resource_schemas/InferenceTest.php`

**Interfaces:**
- Consumes: `getUi()` from Task 1; `SearchManager::describeFilters()` and `fulltext()` from Task 2.
- Produces:
  - `ResourceDescriber::isDescribable(Resource $resource): bool`: true when the resource is an `AbstractDatabaseResource` and an instance of a class opted in through `Extend\ApiResource::describable()`.
  - `ResourceDescriber::describe(Resource $resource, User $actor, ServerRequestInterface $request): array`, which throws `InvalidArgumentException` for a non-describable resource and otherwise returns the spec §5 `attributes` shape with default keys omitted. It always emits `schemaVersion: 1` (via a `ResourceDescriber::SCHEMA_VERSION` constant), the only exception to default omission.
  - `DescribeContext extends Context` with `mode()` / `withMode()`.
  - `ResourceDescriber`, `DescribeContext` and `Extend\ApiResource::describable()` carry an `@experimental` docblock tag.
- Rules ported from POC `src/Resource/DescriptorBuilder.php` on `sh/admin-crud/5-hardening`:
  - Synthetic contexts are `DescribeContext`s for `Create` (`newModel()`), `Update` (blank model with `exists = true`), `Show` and `Index`, each with its `mode`.
  - `kind` comes from the field class or `Attribute->type`.
  - `constraints` come from `$type->schema()` filtered to `minLength`, `maxLength`, `pattern`, `enum`, `format`, `minimum` and `maximum`, merged with `RuleHints`.
  - Visibility fails closed and writability fails open with `conditional` (spec D3).
  - `default` is emitted only for scalar kinds.
  - Relationships report `types` from `collections` and `multiple` for ToMany.
  - `capabilities.list` and `capabilities.create` come from the Index and Create endpoints existing and their `isVisible()` passing for the actor.
  - `pagination` is read from the Index endpoint's paginate config. Add a getter on `Endpoint\Index` if one is missing.
  - `sorts` are `resolveSorts()` filtered by `isVisible`.
  - Labels follow spec D7.

- [x] **Step 1: Write the failing tests.**
  - `RuleHintsTest`: `['required','email:filter','max:30','min:3','in:a,b','regex:/^(?![0-9]*$)[a-z0-9_-]+$/i']` maps to `{required:true, format:'email', maxLength:30, minLength:3, enum:['a','b'], pattern:'^(?![0-9]*$)[a-z0-9_-]+$'}`. Core `Str::email()`/`regex()` emit rules, not type schema, so this parser is the only source for them. Rule objects and closures are ignored.
  - `InferenceTest`: register a scratch resource through `(new Extend\ApiResource(...))->describable()` in the test (POC `ScratchProductPolicy` pattern) and assert:
    - a field whose `visible` throws is absent;
    - a field whose `writable` throws has `conditional: true` and `writable.update: true`;
    - a `->writableOnCreate()` field reports `writable: {create:true}`;
    - a `Str::make()->enum([...])` field reports `constraints.enum`;
    - a hidden-but-writable field reports `visible:false`;
    - the output has no `visible:true` keys anywhere (default omission);
    - the output always has `schemaVersion === 1`, even when everything else is default;
    - a closure sees a `DescribeContext` with the right `mode()`;
    - a model-dependent closure that does not throw (`fn (Group $g) => $g->exists`) is evaluated against the synthetic model (update only);
    - a resource that was not opted in throws `InvalidArgumentException`;
    - an extension can opt in a core resource it does not own (`GroupResource`).
- [x] **Step 2: Run the tests and confirm RED.**
- [x] **Step 3: Implement the classes.** `FieldDescriber` owns the per-field logic; `ResourceDescriber` owns the resource-level keys. (Done. No `Index` getter was needed: `limit`, `maxLimit` and `defaultSort` are already public. A field is dropped only when it is neither visible nor writable. After review: the `Describable` interface was replaced by the `describable()` extender, `Context::withDescribing()` by `DescribeContext`, and `filters` now come from `SearchManager::describeFilters()`. After the second review: type-schema `null` values and the default `minLength: 0` are dropped; `min:`/`max:` map by kind (`minLength`/`maxLength` for strings, `minimum`/`maximum` for integers and numbers, `minItems`/`maxItems` for arrays) via `RuleHints::parse($rules, $kind)`; `url` maps to `format: 'url'`; only the exact `required` rule counts; a guest gets `new ($resource->model())` instead of `newModel()`; `capabilities.create` is checked against a context without a model (type-level, global permissions only); visibility is a plain fail-closed bool (a throwing read mode never counts, a mode returning true wins); an uninitialised `Relationship::$collections` reports `types: []`; `label` is emitted on every field per D7 and removed from the copied `ui` bag; `FieldDescriber` takes `Flarum\Locale\TranslatorInterface`; `SearchManager`'s new methods take `$modelClass`.)
- [x] **Step 4: Run the tests and confirm GREEN.**
- [ ] **Step 5: Commit.** `feat(api): infer resource descriptors from json:api resources`

### Task 3b — Descriptor additions for D11

**Type:** AFK · **Blocked by:** Task 3

**Files:**
- Modify: `src/Api/Describe/RuleHints.php` and `src/Api/Describe/ResourceDescriber.php`
- Test: `tests/unit/Api/Describe/RuleHintsTest.php` and `tests/integration/api/resource_schemas/InferenceTest.php`

**Interfaces:**
- Produces:
  - `constraints.patternFlags`. PCRE flags `i`, `m`, `s` and `u` map to the same JS flags. Any other flag (`x`, `U`, `D`, …) drops `pattern` and `patternFlags` entirely.
  - `capabilities.delete: true` when the resource has a `Delete` endpoint and the actor is not a guest.
- `schemaVersion` stays `1`.

- [x] **Step 1: Write the failing tests.**
  - In `RuleHintsTest`:
    - `regex:/^a+$/i` maps to `{pattern:'^a+$', patternFlags:'i'}`.
    - `regex:/^a+$/x` produces no `pattern` key.
    - `regex:#^a$#` (non-slash delimiter) maps to `{pattern:'^a$'}`.
  - In `InferenceTest`, the scratch resource with a `Delete` endpoint has `capabilities.delete === true`, and the same resource without one has no `delete` key.
- [x] **Step 2: Run and confirm RED.** Command: `../../vendor/bin/phpunit -c tests/phpunit.unit.xml --filter RuleHintsTest`, then the same with `phpunit.integration.xml --filter InferenceTest`, from `framework/core`.
- [x] **Step 3: Implement.** (Done. `RuleHints` also accepts paired bracket delimiters such as `{…}` and `(…)`, and normalises the flag order to `imsu`. `capabilities.delete` is type-level only: the `Delete` endpoint's `isVisible()` is not evaluated, because its `can('delete')` needs a row.)
- [x] **Step 4: Run and confirm GREEN.**
- [ ] **Step 5: Commit.** `feat(api): describe pattern flags and delete capability`

### Task 4 — `resource-schemas` endpoint

**Type:** AFK · **Blocked by:** Task 3

**Files:**
- Create: `src/Api/Resource/ResourceSchemaResource.php`
- Modify: `src/Api/ApiServiceProvider.php`
- Test: `tests/integration/api/resource_schemas/ShowTest.php`

**Interfaces:**
- Consumes: `ResourceDescriber::describe()` from Task 3, and `JsonApi::getResource($type)`.
- Produces: `GET /api/resource-schemas/{type}`, returning a JSON:API document with id `{type}` and the spec §5 attributes, including `schemaVersion`.
  - `find()` returns `null` (→ 404) when the type is unknown or `ResourceDescriber::isDescribable()` is false.
  - The `Show` endpoint is `authenticated()`. A `before` hook asserts that the actor passes the target's Index or Create authorization, otherwise 403. Follow `ExtensionReadmeResource`.
  - The response carries `ETag: "sha1(json attributes)"` and `Cache-Control: private`, and returns 304 on a matching `If-None-Match`. Use the endpoint `response()` hook, or a small middleware if the hook can't set the status.

- [x] **Step 1: Write the failing test.** `ShowTest` cases (the 403 case deletes the member group's default `searchUsers` seed and sets `allow_sign_up` off: with sign-up open, `UserResource`'s Create endpoint is visible to every actor, so the D2 "Index or Create" gate admits every member; open decision, see report):
  - Admin gets 200 for `users`, with `data.id === 'users'` and `data.attributes.schemaVersion === 1`.
  - A normal user without `searchUsers`, with sign-up off, gets 403. (On a default install members have `searchUsers` and sign-up is open, so they get 200 by design: spec D2, FND-295. The test removes both.)
  - A normal user in a group granted `searchUsers` gets 200.
  - A guest gets 401, and the response has no `locale` cookie.
  - `discussions` (not opted in with `describable()` in v1) gets 404.
  - `nope` gets 404.
  - A second request with `If-None-Match` set to the first response's ETag gets 304 and an empty body.
- [x] **Step 2: Run the test and confirm RED.** (5 of 7 failed with 404; the two 404 cases passed trivially.) Command: `composer test -- --filter resource_schemas`
- [x] **Step 3: Implement the resource and register it in `flarum.api.resources`.** (ETag via the endpoint `response()` hook: serialize with `Serializer`, sha1 the JSON of `data.attributes`, `EmptyResponse(304)` on a matching `If-None-Match`; no middleware needed. Gate is a `before()` hook. Model is a per-request `stdClass`; the descriptor is built lazily in field getters, so guests never reach `describe()`. `ResourceDescriber` is resolved lazily from the container, because injecting it recurses through `JsonApi` → `flarum.api.resources`. `find()` also 404s a class-name id.)
- [x] **Step 4: Run the test and confirm GREEN.**
- [ ] **Step 5: Commit.** `feat(api): resource-schemas endpoint`

  > **Post-review (backend, round 2):** fixes test-first (RED then GREEN).
  > - W1: `DescribesFilter::describe(User $actor): ?array` (null hides); `SearchManager::describeFilters($modelClass, User $actor)`; extender callables receive the actor and may return null, arrays stay static. `EmailFilter` and `SuspendedFilter` mirror their `filter()` gates (`user.editCredentials`, `can('suspend', new Guest)`); `GroupFilter` and `LikedFilter` are ungated, like their `filter()`.
  > - W2: `ResourceDescriber::canDescribe()` (same `DescribeContext` + `allowed()` path as `capabilities.list` / `.create`, create without a model); the before-hook uses it; `canListOrCreate` removed.
  > - W3: `ResourceDescriber::KEYS` replaces `ResourceSchemaResource::ATTRIBUTES`; `InferenceTest` scratch resource `scratch-full-users` emits every optional key, checked against `describe()` and the HTTP response.
  > - N1: `find()` asserts a registered actor first, so guests get 401 for any id.
  > - N2: `SearchManager::describeSearch()` returns fulltext + filters from one searcher build; no state on the singleton.
  > - N4/N5: `token` carries `ui(['widget' => 'hidden'])`; `required_without` / `required_without_all` count as required when no alternative is offered (described and not a hidden widget). Two passes in `ResourceDescriber::describeFields()`; only fields with such a rule are re-described.
  > - N7/N8: ETag `json_encode(..., JSON_THROW_ON_ERROR)`; `const int SCHEMA_VERSION`, `const array KEYS / CONSTRAINT_KEYS`.
  > - Tests: ShowTest (class-name id 404, guest 401 for any id, non-matching / weak / `*` If-None-Match, private 304, admin vs member ETag), UsersSchemaTest (credentials required, token hidden, default-member descriptor), member cases in suspend, likes and nicknames.

### Task 5 — Describe users

**Type:** AFK · **Blocked by:** Task 4

**Files:**
- Modify: `src/Api/ApiServiceProvider.php` (`flarum.api.describable_resources` defaults to `[UserResource::class]`).
- Modify: `src/Api/Resource/UserResource.php` (add `->ui()` to `username` `['column'=>90]`, `displayName` `['column'=>85]`, `joinTime` `['column'=>80]` and `groups` `['column'=>70,'display'=>'nameSingular']`. Field `label`s point at the existing `core.admin.users.grid.columns.*.title` keys.)
- Test: `tests/integration/api/resource_schemas/UsersSchemaTest.php`

- [x] **Step 1: Write the failing test.** As admin, assert:
  - `sorts == ['username','commentCount','discussionCount','lastSeenAt','joinedAt']`;
  - `filters[*].key ⊇ ['email','group']`;
  - `search === true`;
  - `pagination == {defaultLimit:20, maxLimit:50}`;
  - `capabilities == {list:true, create:true, delete:true}`;
  - `username.constraints.patternFlags === 'i'`;
  - `schemaVersion === 1`;
  - the `password` field has `visible:false` and `writable.create:true`;
  - the `groups` field has `kind:'toMany'`, `relationship.types == ['groups']` and `writable.update:true`;
  - `username.constraints` has `minLength 3`, `maxLength 30` and the regex pattern;
  - there are no self-only fields (`unreadNotificationCount`, `preferences`, `markedAllAsReadAt`; FND-283).

  For a mod with only `searchUsers`, assert that `email` is absent (no `editCredentials`) and `groups.writable` is absent.

  *Done:* 7 tests in `UsersSchemaTest`, which also assert the four column fields' `ui` hints and translated labels ("Username", "Display name", "Joined", "Groups"). The mod case closes sign-up (`allow_sign_up=false`): with sign-up open anyone may create an account, so `email` being create-writable is then truthful.
- [x] **Step 2: Run the test and confirm RED.** *Done:* 7/7 failed with 404 `Resource [resource-schemas.users] not found` (users not yet describable by default).
- [x] **Step 3: Implement.** If self-only fields leak, gate them on `! $context instanceof DescribeContext` in `UserResource`. This is the only allowed `UserResource` change besides `ui()`.
  *Done:* binding defaults to `[Resource\UserResource::class]`; the redundant `describable()` opt-in was removed from `ShowTest`. `ui()` on the four column fields, each with its `core.admin.users.grid.columns.{username,display_name,join_time,group_badges}.title` label. Self-only fields did **not** leak (their `actor->id === user->id` checks are false against the synthetic, id-less model), so no `DescribeContext` gate was added. **Describer change:** a `searchUsers`-only actor got `email` with `visible:false, writable.create:true`, because `email` is writable whenever `creating()`. `ResourceDescriber` now computes `capabilities.create` first and passes the write modes to `FieldDescriber::describe()` (new optional `$writeModes` param); when the actor cannot create, create-time writability, `required.create` and `default` are not described.
- [x] **Step 4: Run the test and confirm GREEN.** Then run the whole `tests/integration/api/users` suite and confirm it is unchanged.
  *Done:* `UsersSchemaTest` 7 tests / 42 assertions OK. `--filter 'resource_schemas|api\\users\\|search\\|ApiResourceUiHintTest'` 201 tests / 852 assertions OK; Describe unit tests 16 OK; phpstan on `src/Api` clean. (The 3 deprecations are vendor `middlewares/utils` on PHP 8.5.)
- [ ] **Step 5: Commit.** `feat(users): describe the users resource`

### Task 6 — Common JS: descriptor → fields → inputs → state

**Type:** AFK · **Blocked by:** Task 4 (shape only, so this can run in parallel with Task 5)

**Files:** everything under `js/src/common/resource/*` plus `js/src/common/models/ResourceSchema.ts`, with tests in `js/tests/unit/common/resource/*`.

**Interfaces:**
- Port from POC `js/src/common/resource/*` on `sh/admin-crud/5-hardening`, with these deltas:
  - The loader uses `app.store.find('resource-schemas', type)` instead of raw `app.request`.
  - Types follow spec §5: `constraints.*` instead of the POC's top-level `maxLength`/`enum`/`format`, and `relationship.types` instead of `related`.
  - Locale keys are `core.lib.resource.*`.
  - `InputFactory` gains `unregister(type)` (work item fix 6).
- Produces:
  - `ResourceDefinition<M>`, with `load()`, `fields()`, `inputResolvers()`, `formInputs()`, `filterInputs()`, `filterResolvers()`, `filters()`, `actions(m)`, `toolbar(state)`, `query()`, `canCreate()` and `label(n)`. `canCreate()` defaults to `descriptor.capabilities.create`, replacing the POC's forum-attribute hack.
  - `Field<M>`, as in the POC.
  - `ResourceListState extends PaginatedListState`.
  - `ResourceFormState`, which saves dirty fields only (FND-220).
  - `mapErrors(errors, fields)`. A write-through container field must be `input:false` (work item fix 4); document this in the docblock.

- [x] **Step 1: Port the POC unit tests** (`js/tests/unit/resource/*` in base-products) and adapt them to the new shape. Add `fieldsFromDescriptor` cases:
  - `canCreate` follows `capabilities.create`;
  - `constraints.enum` resolves to the enum input;
  - `visible:false && writable.create` gives input-only with no column.
- [x] **Step 2: Run the tests and confirm RED.** Command: `yarn --cwd framework/core/js test resource`, run from the repo root.
- [x] **Step 3: Port and adapt the implementation.**
- [x] **Step 4: Run and confirm GREEN, plus `yarn --cwd framework/core/js check-typings`.**
  > **Notes (Task 6):** 8 suites / 101 tests in `tests/unit/common/resource/` (boundary, descriptor, fieldsFromDescriptor, field, errors, form, inputs, listState). Deviations:
  > - Core source uses relative imports, so "only `flarum/common/*`" means "only `src/common/**` plus `mithril`"; `boundary.test.ts` enforces it (the POC's ESLint run has no core counterpart).
  > - Descriptor types keep the wire shape (default-valued keys optional); `isWritable(d, mode)` / `isRequired(d, mode)` in `descriptor.ts` read the mode flags. Filters are `FilterDescriptor` objects; `FilterResolver` takes `(filter, fieldDescriptor)`; the filter's own `kind`/`enum`/`related` wins over the same-named field.
  > - `loadDescriptor` returns a deep copy of the response attributes: the store `Object.assign`s a refetch into the attributes object it first got, which kept omitted keys and mutated descriptors handed out earlier.
  > - `ResourceFormState.save()` adopts the model `save()` resolves to (a new record resolves to a different store instance), else a second save would POST again.
  > - Numeric `ui.column` becomes `column.priority`; field order is declaration order. Reserved `ui.priority`, `ui.widget`, `ui.placeholder` and the POC's `help` / `relatedSearch` are not consumed (spec §5); `RelationInput` search defaults to on.
  > - No `index.ts` barrel (core auto-exports by path). `canCreate()` reads `capabilities.create`. `InputFactory.unregister(type)` added.
- [ ] **Step 5: Commit.** `feat(common): generic resource definition, form and list state`

### Task 6b — Client-side validation

**Type:** AFK · **Blocked by:** Task 6

**Files:**
- Create: `js/src/common/resource/validation.ts`
- Modify: `js/src/common/resource/ResourceFormState.ts`
- Test: `js/tests/unit/common/resource/validation.test.ts`

**Interfaces:**
- Produces:
  - `validateValue(field: FieldDescriptor, value: unknown, mode: 'create' | 'update'): string[]`. It is pure and returns translated messages from `core.lib.resource.validation.{required,min_length,max_length,pattern,enum,email,url,minimum,maximum}`.
  - `ResourceFormState.validate(): Record<string, string[]>`. It checks only the fields that are visible and writable in the current mode and not `conditional`.
  - `ResourceFormState.save()` calls `validate()` first. If there are errors, it fills the same per-field error slots `mapErrors` uses, rejects without a request, and focuses the first invalid field.
  - Blur validation is per field and never blocks.
- The pattern is compiled once per field with `new RegExp(pattern, patternFlags)` inside a try. On failure the field skips `pattern` and calls `fireDebugWarning` once.

- [x] **Step 1: Write the failing tests.**
  - `required.create` with empty `''`, `null` and `[]` each yields the `required` message. `required.update` with an unchanged (non-dirty) value yields none.
  - `minLength 3` and `maxLength 30` on `'ab'` and on a 31-char string each fail. An empty, non-required value passes every constraint.
  - `pattern '^(?![0-9]*$)[a-z0-9_-]+$'` with `patternFlags 'i'`: `'Simon'` passes and `'123'` fails.
  - `pattern 'a++'` (invalid in JS) passes and emits one debug warning.
  - `format: 'email'`: `'a@b'` passes and `'nope'` fails. `format: 'url'` accepts `https://x.y` only.
  - `enum ['a','b']` rejects `'c'`. `minimum 1` rejects `0`.
  - `ResourceFormState.save()` with an invalid field makes no `app.request` call and sets that field's errors. A `conditional` field with an invalid value does not block.
- [x] **Step 2: Run and confirm RED.** Command: `yarn --cwd framework/core/js test validation`
- [x] **Step 3: Implement.**
- [x] **Step 4: Run and confirm GREEN, plus `check-typings`.**
  > **Notes (Task 6b):** `validation.test.ts`, 9 tests (the plan's list). Deviations:
  > - `save()` rejects with `ValidationError` (`errors: Record<string, string[]>`). The state has no DOM, so `ResourceForm` focuses the first `.has-error` field on catching it (`focusFirstInvalid()`).
  > - While updating, only changed fields are validated (only they are sent); this is what makes "unchanged `required.update`" pass. Blur (`validateField(key)`, wired via `onfocusout`) checks changed fields only and clears only errors it set, never the server's.
  > - Blank (whitespace-only) strings count as absent, as in Laravel: only `required` applies. Length counts code points. `minItems`/`maxItems` are not checked (no message keys in the plan).
- [ ] **Step 5: Commit.** `feat(common): client-side validation from resource descriptors`

### Task 7 — Admin JS: `ResourceTable`, form and delete modals

**Type:** AFK · **Blocked by:** Task 6

**Files:** `js/src/admin/components/resource/{ResourceTable,ResourceFormModal,ResourceDeleteModal}.tsx` and `less/admin/ResourceTable.less`. Tests: `js/tests/integration/admin/components/ResourceTable.test.ts` and `js/tests/integration/admin/components/ResourceFormModal.test.ts`.

**Interfaces:**
- Produces:
  - `export interface ColumnData<M> { name: Mithril.Children; content: (model: M) => Mithril.Children; sort?: string; className?: string }`. This is the superset of `UserListPage`'s `ColumnData`, which will re-export it.
  - `ResourceTable` attrs, as in the POC (`definition`, `state?`, `syncUrl?`, `pageSize?`, `formInputs?`, `filterInputs?`) plus:
    - `columns?: ItemList<ColumnData>`, which overrides the descriptor columns;
    - `className?`, the root class (the user list passes `UserListPage-grid`);
    - `onCreate?()` and `onEdit?(model)`, which replace the default `ResourceFormModal`. `UserListPage` passes callbacks that call its `createModal()` / `editModal()` seams (Task 8);
    - `rowAttrs?(model)`, used for `data-user-id`.
  - `ResourceFormModal` mirrors the old modals' overridable surface so extenders can port by renaming the base class: `fields(): ItemList` (same ItemList-of-inputs idea as `CreateUserModal` / `EditUserModal`), `data()`, `submitData()` (dirty-only, FND-220) and an `onsaved` callback. It is built and tested against the users descriptor in 2.0 but is **not wired** to any page.
  - `@experimental` docblock tag on the class docblocks of `ResourceTable`, `ResourceFormModal` and `ResourceDeleteModal`, and on their public attrs and methods.
  - Region ItemLists, as in the POC: `toolbarItems`, `actionItems`, `rowActions`, `headerView`, `rowView` and `paginationView`.
- Edit and delete are gated by `model.attribute('canEdit'/'canDelete')`. Pagination uses core `Pagination` when the total is known.

- [x] **Step 1: Write the failing test.** Render with a stub descriptor (two sortable fields and a filter) and assert:
  - header cells for the sortable columns are clickable and set `sort` in the request params;
  - typing in search debounces and then sets `filter[q]`;
  - a row with `canDelete:false` has no delete action;
  - passing `columns` replaces the inferred columns;
  - each cell has `data-column-name` set to its ItemList key;
  - `onCreate` and `onEdit(model)` are called instead of opening the default modal when they are passed.

  Add `ResourceFormModal.test.ts` (parity test). Render `ResourceFormModal` for the `users` stub descriptor as an admin and assert:
  - the writable field names match `EditUserModal`'s core fields: `username`, `email`, `password` and `groups`;
  - `fields()` is an `ItemList`, and `extend(ResourceFormModal.prototype, 'fields', ...)` can add an item;
  - `submitData()` sends dirty fields only;
  - `onsaved` is called with the saved model.
- [x] **Step 2: Run the test and confirm RED.**
- [x] **Step 3: Implement**, porting from POC `js/src/admin/resource/*`.
- [x] **Step 4: Run the test and confirm GREEN.** Also run `yarn --cwd framework/core/js build` and confirm the build succeeds.
  > **Notes (Task 7):** `ResourceTable.test.ts` (6 tests) and `ResourceFormModal.test.ts` (4 tests), plus one `inputs.test.ts` case for the relation search fix; full suite 118 suites / 878 tests, `check-typings` and `build` clean. Deviations:
  > - `ColumnData.attrs` is `(model) => attrs` for a record's cell (the spec names it without a shape). Inferred columns are built as `ColumnData` too, so header/cell rendering has one path; a field column's numeric `priority` orders it.
  > - The actions column (`rowActions`) is always appended after `columns`. Task 8 must decide how it relates to `UserListPage`'s own `userActions` column.
  > - `ResourceFormModal` extends `FormModal` and lays out the fields itself (no nested `ResourceForm`), so `fields()` is the rendered ItemList: one item per writable field, keyed by name, plus `errors` and `submit` (-10, as in `EditUserModal`). `data()` is the dirty save data; `submitData()` is what is sent and defaults to `data()`. To share rendering, `ResourceForm.tsx` now exports `renderFormField()` / `renderFieldError()` (its `fieldView`/`errorView` delegate), and `ResourceFormState.save(data?)` takes the payload to send (default `buildSaveData()`).
  > - Parity: an admin editing another user gets `username`, `email`, `isEmailConfirmed`, `password`, `groups`. The test checks against `EditUserModal`'s real `fields()` keys and pins `isEmailConfirmed` as the only extra (`EditUserModal` sets it through the "Activate" button in its email item). In create mode the inferred form also offers `token` (write-only, create-only); a 2.1 users subclass will want to hide it.
  > - Relation search: `RelationInput.UNSEARCHABLE_TYPES` (~~default `{'groups'}`~~ empty since Task 8; the admin user list registers `groups`) and `RelationInput.isSearchable(type)` decide the search box when the spec has no `search`; the resolvers no longer force `search: true`. This covers the users form's `groups` and the filter bar's `group` filter.
  > - The users stub descriptor (`tests/integration/admin/fixtures/usersDescriptor.ts`) is built from `UserResource` + `FieldDescriber` and the assertions in `UsersSchemaTest.php`, not from a captured response.
  > - Locale keys: `core.admin.resource.{table,form,delete}.*`. Less: `less/admin/ResourceTable.less`, imported from `less/admin.less`.
- [ ] **Step 5: Commit.** `feat(admin): generic resource table and modals`

### Task 7b — Column toggles + CSV export

**Type:** AFK · **Blocked by:** Task 7

**Files:**
- Create: `js/src/common/resource/{ColumnPreferences.ts,csv.ts}` and `js/src/admin/components/resource/ColumnsDropdown.tsx`
- Modify: `ResourceTable.tsx` (`viewItems` gains `columns` and `export`), `ResourceDefinition.ts` (`canExport()`)
- Test: `js/tests/unit/common/resource/{csv,ColumnPreferences}.test.ts` and `ResourceTable.test.ts` (new cases)

**Interfaces:**
- Produces:
  - `ColumnData` gains `toggleable?: boolean` (default `true`), `hidden?: boolean` and `csv?: (model) => string | number | null`.
  - `ColumnPreferences.load(type): Map<string, boolean>` and `ColumnPreferences.save(type, choices: Map<string, boolean>): void` (explicit choices only, key → visible; corrected after 7b, see the notes), with the key `flarum.resourceTable.{type}.columns`. Both swallow storage errors and fall back to an in-memory map.
  - `toCsv(rows: unknown[][], header: string[]): string`, with CRLF line endings, RFC 4180 quoting and the formula guard (prefix `'` on a leading `= + - @ \t \r`).
  - `exportCsv(state: ResourceListState, {maxRows = 5000, onProgress, signal}): Promise<{rows: number, truncated: boolean}>`. It pages at `pagination.maxLimit` with the state's current filter and sort, and downloads `{type}-{YYYY-MM-DD}.csv` through a Blob link. The file is prefixed with a UTF-8 BOM so Excel reads umlauts correctly, and the `exportCsv` test asserts the first bytes are `﻿`. Cell value: `column.csv(m)`, otherwise the raw attribute when the column key equals a descriptor field name, otherwise the column is skipped. Hidden columns are excluded.
  - `ResourceDefinition.canExport(): boolean`, which defaults to `descriptor.capabilities.list`. When it is false, the export button isn't rendered.
  - `ResourceTable` attr `exportable?: boolean`, which defaults to `definition.canExport()`.

- [x] **Step 1: Write the failing tests.**
  - `toCsv([['a,b','he said "x"','line\nbreak','=1+1','@x','-5']], [...])` matches the exact expected string, so `-5` becomes `'-5`. This is intentional because the guard is uniform; the docblock notes the trade-off.
  - `ColumnPreferences` round-trips. With `localStorage.setItem` throwing, `save` does not throw and `load` returns the in-memory value.
  - In `ResourceTable`:
    - toggling a column off hides its header and cells and persists the choice;
    - a column with `toggleable:false` has no checkbox;
    - a `hidden:true` column starts hidden;
    - a stored key for a removed column is ignored.
  - `exportCsv` over a stubbed 2-page store calls `find` twice with `page[limit]=maxLimit` and the active `filter`/`sort`. With `maxRows=1` it returns `truncated:true`. The abort signal stops after the current page.
- [x] **Step 2: Run and confirm RED.**
- [x] **Step 3: Implement.**
- [x] **Step 4: Run and confirm GREEN, plus `check-typings`.**
  > **Notes (Task 7b):** `csv.test.ts` (6: 3 `toCsv`/`csvCell`, 3 `exportCsv` paging + BOM/filename, truncation, abort), `ColumnPreferences.test.ts` (3) and 5 new `ResourceTable.test.ts` cases (4 column toggles, 1 export button gating). Full suite 120 suites / 892 tests; `check-typings` and prettier clean; `build` not run (it rewrites tracked `js/dist`). Deviations:
  > - `exportCsv(state, {columns, maxRows, onProgress, signal})` takes the table's `[key, column][]` as `columns`: the list state has no columns. `csv.ts` lives in `common`, so it types them structurally (`ExportColumn`: `name`, `csv?`, `hidden?`), which `ColumnData` satisfies. `ResourceTable` passes its visible columns. Headers are `extractText(column.name)`. `csvCell(value)` is exported too; `toCsv` ends every record (header included) with CRLF; objects/arrays are JSON, `Date` is ISO.
  > - `ResourceListState.listParams()` (new, public) exposes the protected `requestParams()` so the export reuses the list's exact sort/`filter[q]`/filters/`include`.
  > - Paging continues while the page is non-empty and `links.next` is set, or (without it) `offset < meta.page.total`, or a full page came back: `links.next` is dropped on a post-filtered short page. Pages after the first still request the total (no `page[total]=0`); progress reports `(rows, min(total, maxRows))`.
  > - Abort: checked after each page; the in-flight request finishes, then the promise rejects with an `AbortError` and nothing downloads. Truncation (`maxRows` reached with more rows) still downloads, and `ResourceTable` shows a warning alert.
  > - `ColumnPreferences` is a class with static `key()`/`load()`/`save()` and an `@internal` `clear()` for tests. Memory wins over storage on read, so a failed `setItem` still reads back. A corrupt or non-array value reads as no preference. ~~The stored set is the full hidden set, so once the user has made a choice, `hidden:true` defaults no longer apply (a later-added default-hidden column shows until toggled).~~ **Corrected (with Task 7c):** only explicit choices are stored, as a JSON object `{columnKey: visible}` under the same key. `load(type): Map<string, boolean>` (empty, never null, when there are none) and `save(type, choices: Map<string, boolean>)`. A column the user never toggled follows its `hidden` default, so a default-hidden column added later starts hidden (new `ResourceTable` case). A stored array (the 7b format) reads as "these keys explicitly hidden"; non-boolean values are dropped. `ResourceTable.hiddenColumns()` became `storedChoices()` (protected); ~~`toggleColumn()` still prunes choices for columns that no longer exist or are not toggleable~~ **(Task 8 cleanup)** `toggleColumn()` keeps them: stale keys are ignored, not deleted (spec D11), so a column whose extension is off for a while gets its choice back. `ColumnPreferences.test.ts` now has 4 cases (legacy array added).
  > - `ResourceTable`: `viewItems` gains `columns` (-10, only when a column is toggleable) and `export` (-20), after the filters. New `@experimental` methods `visibleColumns()`, `isColumnVisible()`, `toggleColumn()`, `exportButton()`, `exportCsv(maxRows?)` and the `exporting` property; the export button turns into a cancel button with progress while running, and `onremove` aborts it. ~~Saving drops stored keys of columns that no longer exist or are not toggleable.~~ Saving keeps them (Task 8 cleanup). `ColumnsDropdown` takes `{columns: {key, name, visible}[], onchange(key, visible)}`; checkbox clicks stop propagation so the menu stays open.
  > - Locale keys under `core.admin.resource.table.*`: `columns_button`, `columns_label`, `export_button`, `export_error`, `export_progress`, `export_progress_total`, `export_truncated`. Less: `.ResourceTable-columns`, `.ResourceTable-columnToggle`.
- [ ] **Step 5: Commit.** `feat(admin): column toggles and csv export for resource tables`

### Task 7c — Bulk select + bulk delete

**Type:** AFK · **Blocked by:** Task 7

**Files:**
- Create: `js/src/common/resource/BulkRunner.ts` and `js/src/admin/components/resource/BulkResultModal.tsx`
- Modify: `ResourceListState.ts` (`selection`), `ResourceDefinition.ts` (`bulkActions()`, `bulkDelete()`), `ResourceTable.tsx` (checkbox column, `bulkActionItems` toolbar region)
- Test: `js/tests/unit/common/resource/BulkRunner.test.ts` and `ResourceTable.test.ts` (new cases)

**Interfaces:**
- Produces:
  - `ResourceListState.selection`, with the shape `{ mode: 'ids'; ids: Set<string> }`. `'matching'` is reserved in the type with a `never` branch. The selection is cleared on page, sort, filter or search change.
  - `interface BulkRunner { run<M>(models: M[], op: (m: M) => Promise<unknown>, onProgress?): Promise<BulkResult<M>> }`, where `BulkResult = { ok: M[]; failed: {model: M; error: unknown}[] }`.
  - `SequentialBulkRunner(concurrency = 3)`.
  - `ResourceDefinition.bulkActions(selection): ItemList` is empty by default.
  - `ResourceDefinition.bulkDelete(): ItemList entry` is a helper that a definition adds explicitly. It confirms with the count, filters to rows with `canDelete`, runs `model.delete()` through the runner, opens `BulkResultModal` and then refreshes the list.
  - The checkbox column (with a header select-all-on-page) renders only when `bulkActions(selection)` has at least one item.

- [x] **Step 1: Write the failing tests.**
  - The runner, given 5 models where op #3 rejects, returns `ok.length 4` and `failed[0].model` = #3, and never has more than 3 ops in flight (assert with a counter).
  - In `ResourceTable`:
    - with empty `bulkActions` there is no checkbox column;
    - with one bulk action there is a checkbox per row;
    - select-all selects the current page only;
    - changing page clears the selection;
    - `bulkDelete` over 3 selected rows, one with `canDelete:false`, issues 2 `DELETE` requests and the result modal reports 2 deleted and 1 skipped.
- [x] **Step 2: Run and confirm RED.**
- [x] **Step 3: Implement.**
- [x] **Step 4: Run and confirm GREEN, plus `check-typings`.**
  > **Notes (Task 7c):** `BulkRunner.test.ts` (1) and 5 new `ResourceTable.test.ts` cases (plus the 7b correction's case). RED: `BulkRunner`/`ResourceTable` suites failed on the missing modules. The two behavioural cases (page change clears the selection; a later default-hidden column starts hidden) were also checked by mutating the code back: both fail. Full suite 121 suites / 900 tests; `check-typings` and prettier clean; `build` not run (it rewrites tracked `js/dist`). Deviations:
  > - **`bulkActions(selection, context)`**, not `bulkActions(selection)`. `ResourceDefinition` is in `common` and may not import the admin `BulkResultModal` (import boundary), and the helper needs the list to read the selected models and refresh. `BulkActionContext<M>` (in `BulkRunner.ts`) is `{list, runner, showResult(summary)}`; `ResourceTable.bulkContext()` builds it with `bulkRunner()` (overridable, default `new SequentialBulkRunner()`) and `showResult` opening `BulkResultModal` with `{summary}`. `bulkDelete(context)` returns the button; `runBulkDelete(context)` is the (public, awaitable) work.
  > - Types: `BulkResult<M>` as planned; `BulkSummary<M> = BulkResult<M> & {skipped}`; `BulkProgress(done, total)`. The runner keeps `ok`/`failed` in input order, catches synchronous throws too, and calls `onProgress` after each settled op.
  > - Selection: `ResourceSelection = IdsSelection | MatchingSelection` with `MatchingSelection = never` (reserved); `ResourceSelectionMode = 'ids' | 'matching'`. Methods switch on `selection.mode` and end in a `never` branch. `ResourceListState.goto()` clears it, so page, sort, filter, search, the refresh button and the post-delete refresh all clear it. New `@experimental` methods: `isSelected`, `setSelected`, `setPageSelected`, `isPageSelected`, `clearSelection`, `selectedModels` (page order; ids are page-scoped).
  > - `bulkDelete`: confirms with `window.confirm` (as `EditGroupModal`/`ExtensionPage` do) and the **deletable** count; no confirm when nothing is deletable (the summary then only lists skipped rows). Deletes pass `errorHandler: () => {}` so one alert per failed row doesn't stack up; the modal shows each failure's reason (the content core's alert would have shown, else `result_unknown_error`). After the modal opens, the list reloads the page (one page back if every row on it was deleted). A `bulkDeleting` flag guards double clicks; the button is disabled with no selection.
  > - `ResourceTable`: `bulkActionItems()` (the definition's list), `bulkView()` (toolbar region `bulk`, priority 25, with a "{count} selected" live count), `selectAllView()` (header checkbox, indeterminate when partly selected), `selectView(model)`; `rowView(model, columns, selectable = false)` gained the third param and an `is-selected` row class. The spec's extra "**and** `capabilities.delete || bulkActions`" condition is not implemented; the plan's "renders only when `bulkActions(selection)` has items" is.
  > - `BulkResultModal` is delete-specific in wording (`result_deleted`, title "Bulk delete"); `bodyItems()` (`deleted`, `failed`, `skipped`) and `reason(error)` are the extension points.
  > - Locale keys: `core.admin.resource.bulk.{close_button,delete_button,delete_confirmation,result_deleted,result_failed,result_skipped,result_title,result_unknown_error}` and `core.admin.resource.table.{bulk_label,select_all_label,select_label,selected}`. ~~`bulkDelete()` lives in `common` but uses `core.admin.*` keys: it only renders in the admin table.~~ **(Task 8 cleanup)** `delete_button` and `delete_confirmation` moved to `core.lib.resource.bulk.*`, since `bulkDelete()` is common code; `BulkResultModal` (admin) keeps the rest under `core.admin.resource.bulk.*`. Less: `.ResourceTable-bulk`, `-selectedCount`, `-selectHeader`, `-selectCell`, `-row.is-selected`, `.BulkResultModal`.
- [ ] **Step 5: Commit.** `feat(admin): bulk selection and bulk delete for resource tables`

### Task 8 — Rebuild `UserListPage` on `ResourceTable`

**Type:** AFK · **Blocked by:** Tasks 5, 7, 7b, 7c

**Files:**
- Modify: `js/src/admin/components/UserListPage.tsx`
- Test: `js/tests/integration/admin/components/UserListPage.test.ts`

**Interfaces:**
- Keep exactly the following:
  - `headerItems()`, with keys `search` 100, `totalUsers` 90 and `actions` 80. `search` still wraps `GambitsAutocompleteDropdown resource="users"`.
  - `actionItems()`, with `createUser` 100, which opens `this.createModal()` (default `CreateUserModal`) with `onCreated` and bumps `modelStatistics.users.total`.
  - `columns()`, with keys `id` 100, `username` 90, `displayName` 85, `joinDate` 80, `groupBadges` 70, `emailAddress` 70 and `userActions` -90. Add `sort` to `username` and `joinDate` (`joinedAt`). `userActions` still renders the `userActionItems(user)` Dropdown.
  - `userActionItems(user)`, with `editUser` opening `this.editModal(user)` (default `EditUserModal`) with the same `user` and `onsaved` attrs as today. Add `delete` at priority -100, gated by `user.canDelete()`, which opens `ResourceDeleteModal`.
  - New additive seams (spec D8): `createModal(): typeof Modal` returns `CreateUserModal`, and `editModal(user: User): typeof Modal` returns `EditUserModal`. Both carry an `@experimental` docblock tag. `ResourceTable`'s `onCreate` / `onEdit` call them. Overriding them (via `override` or `extend`) changes which modal opens in 2.1 with a one-line change. Nothing else about the modals changes in 2.0.
  - `headerInfo()` and `loadPage(pageNumber)` (zero-based, delegating to the list state's page). `loadPage` sends `filter[q]`, `page[limit]=50` and the current sort.
  - `goToPage(page)` (one-based) and the `?page=` URL.
- New D11 seams, both `@experimental`:
  - `bulkActionItems(): ItemList` is empty in 2.0. It is passed to the table's `bulkActions`, so there is no checkbox column unless an extension adds an item.
  - `canExport(): boolean` returns `!!app.session.user?.isAdmin()`.
- Column toggles are on. The `id`, `username` and `userActions` columns get `toggleable:false`. `emailAddress` gets `csv: u => u.email() ?? ''` and keeps its per-row show/hide toggle. `groupBadges` gets `csv: u => u.groups().map(g => g.nameSingular()).join('; ')`, and `joinDate` gets `csv: u => u.joinTime()?.toISOString()`.
- `UserListPage` passes `columns()` into `ResourceTable` (D8), so descriptor-only columns are appended only for `ui.column` fields that are not already keyed.
- The descriptor's `filters` render in a filter row beneath the header items. Filters are additive: gambits keep working in `filter[q]`.

- [x] **Step 1: Extend the test file with a surface-contract test** that asserts each method above exists on the prototype and returns the listed keys in order. Add these cases:
  - `extend(UserListPage.prototype,'columns', items => items.add('legacy', {name:'L', content: u => u.username()}))` renders an `L` column.
  - `extend(UserListPage.prototype,'userActionItems', ...)` adds a dropdown item, as gdpr does.
  - The URL `?page=2` loads with offset 50.
  - Clicking the username header requests `sort=username`.
  - Seams, default: `createModal()` returns `CreateUserModal` and `editModal(user)` returns `EditUserModal`. Clicking create / edit opens those modals, and `extend(CreateUserModal.prototype, 'fields')` still takes effect.
  - Seams, override: `override(UserListPage.prototype, 'createModal', () => StubModal)` makes create open the stub modal, and the same for `editModal`. The stub's `onCreated` callback still bumps `app.data.modelStatistics.users.total`, and `onsaved` is still passed to the edit modal.
  - D11:
    - by default there is no checkbox column;
    - `extend(UserListPage.prototype, 'bulkActionItems', items => items.add('x', ...))` makes the checkbox column appear;
    - an admin sees "Export CSV", and a user with only `searchUsers` does not;
    - the "Columns" dropdown has no checkbox for `id`, `username` or `userActions`;
    - a CSV export row for a user contains the username, email and `;`-joined group names.
- [x] **Step 2: Run the test and confirm RED.**
- [x] **Step 3: Implement.** `UserListPage` keeps its private state names where an extension could reach them (`query`, `pageData`, `numPerPage`) as getters onto the list state.
- [x] **Step 4: Run the test and confirm GREEN.** Also run `yarn --cwd framework/core/js test` (the whole suite) and `check-typings`.
  > **Notes (Task 8):** `UserListPage.test.ts` grew from 1 to 21 tests (RED: 19 failing, the 2 passing were "it renders" and the groups picker). Full suite 121 suites / 924 tests (adds 20 here, 2 in `ResourceTable.test.ts`, 2 in `boundary.test.ts`); `check-typings` and prettier clean; `build` not run (it rewrites tracked `js/dist`). Design and deviations:
  > - **One actions column.** `ResourceTable` gained the attr `actionsColumn?: boolean` (default true); false leaves out the actions header, the per-row `rowActions()` cell and its empty-state colspan. `UserListPage` passes false, so its own `userActions` column (-90, the `userActionItems(user)` Dropdown, `toggleable:false`) is the only actions column, and gdpr's `export-data` item renders in it. The table's `rowActions()`/`onEdit` path is unused on this page; `onCreate`/`onEdit` are still passed and call the seams.
  > - **`UserListTable`**, a module-private `ResourceTable` subclass inside `UserListPage.tsx` (not exported, so no new API): drops the table's `search` and `sort` view items (the page header keeps the gambit search; sorting is the column headers) and its `actions` toolbar region (refresh, create: the page header has "New User"). It keeps the filter row, "Columns" and "Export CSV". It adds `UserListPage-grid-header` to header cells and `UserListPage-grid-rowItem` (+ `--shaded` on odd rows) plus `data-user-id` to body cells, so the old selectors (including the email toggle's `[data-column-name=emailAddress][data-user-id=…]`) still match. The root gets `UserListPage-grid` plus `--loading`/`--loadingPage`/`--loaded`. Old-shape columns keep the `invalid_column_content` fallback for falsy content.
  > - **Markup** is now a `<table>` (`th`/`td`), not the CSS grid of `div`s; `less/admin/UsersListPage.less` drops the grid layout rules (`display:grid`, `--columns`, `.loading-container()`, cell flex) that would break a table. The `--columns` inline style is gone.
  > - **State.** The page owns `definition` (`ResourceDefinition<User>('users')`) and `list` (`ResourceListState`, `pageSize: 50`), both `protected`, and passes `state`. `query` (including unsent typing), `pageData` (`undefined` until a page is loaded) and `numPerPage` are protected getters onto the list. `loadPage(n)` is `list.goto(n + 1)`; `goToPage(p)` is `loadPage(p - 1)`. The search debounce stays 250 ms and sends `filter[q]` through `list.setQuery()`.
  > - **URL.** The list syncs `page`, `sort`, `q` and `filter[…]`. `?page=2` loads offset 50. Deviation: `page` is omitted on page 1 (the list's `writeUrl()`), where the old page always wrote `?page=1`; and an out-of-range `?page=` shows the empty state instead of re-requesting the last page.
  > - **Seams.** `createModal()` returns `CreateUserModal`. `editModal(user)` returns a stable module-level loader `() => import('…/EditUserModal')`, not the class: today's edit button lazy-loads that chunk, and returning the class would pull it into the admin bundle. Both are typed `UserListModal` (= what `app.modal.show()` accepts) instead of the plan's `typeof Modal`. The edit modal gets `{user, onsaved}`; today's call passed only `{user}` and `EditUserModal` ignores `onsaved` (it is there for the 2.1 `ResourceFormModal` switch).
  > - **`bulkActionItems(context: BulkActionContext<User>)`**, not `bulkActionItems()`: an item such as `definition.bulkDelete(context)` needs the list and runner (the same reason as 7c's `bulkActions(selection, context)`). Empty by default. `canExport()` is `!!app.session.user?.isAdmin()`, passed as `exportable`.
  > - **Delete** is `userActionItems` key `delete` (-100, `user.canDelete()`, class `UserList-deleteUser`, label `core.admin.resource.table.delete_button`): `ResourceDeleteModal` with the page's definition; `ondeleted` lowers `modelStatistics.users.total` and reloads (one page back when the last row went).
  > - **Columns.** `id`, `username`, `userActions` are `toggleable:false`; `username` sorts by `username`, `joinDate` by `joinedAt`. CSV: `joinDate` ISO time, `groupBadges` `; `-joined `nameSingular`s, `emailAddress` the email; `username` and `displayName` export their raw attribute (key = field); `id` and `userActions` are left out. Descriptor columns are appended only for fields with an explicit `ui.column` hint that the page doesn't already show: `username`, `displayName`, `joinTime` (→ `joinDate`) and `groups` (→ `groupBadges`) are mapped in a private `FIELD_COLUMNS`; sortable-only fields (`commentCount`, …) get no column. suspend's `suspendedUntil` (`ui.column 60`) appears after `emailAddress`.
  > - **Cleanups (Tasks 6–7c):** `RelationInput.UNSEARCHABLE_TYPES` starts empty; `UserListPage.tsx` registers `groups` at module level. `bulkDelete()`'s `delete_button` / `delete_confirmation` moved to `core.lib.resource.bulk.*` (`BulkResultModal` keeps `core.admin.resource.bulk.*`). `toggleColumn()` keeps stored choices for absent or non-toggleable columns (ignored, not deleted, spec D11). `boundary.test.ts` now also fails on any `core.admin.*`/`core.forum.*` key in `common/resource` and on a seeded `UNSEARCHABLE_TYPES`.
  > - Test harness: the jest setup has no global `dayjs` (core's bundle exposes one), so the test sets it; `AutocompleteDropdown.updateMaxHeight` is stubbed (domino has no `getBoundingClientRect`); the descriptor goes through the real store while `users` lists are stubbed.
  > **Post-review (frontend):** fixes from the frontend review, test-first (RED then GREEN). Full suite 121 suites / 949 tests (+25); `check-typings` and prettier clean; `build` not run.
  > - **Grid scope.** `ResourceTable` gained the attr `tableClassName` (added to `.ResourceTable-container`, the element around the `<table>` and its loading overlay). `UserListPage` passes `className="UserListPage-table"` (root) and `tableClassName="UserListPage-grid …--loading|--loadingPage|--loaded"`, so `UserListPage-grid` no longer wraps the toolbar, filter row or pagination.
  > - **Out-of-range page.** `ResourceListState.goto()`: a page above 1 that comes back empty, with a known total and past `pageCount()`, goes to the last page (as the old `loadPage` did). This replaces the Task 8 deviation "an out-of-range `?page=` shows the empty state". Without a total it stays put.
  > - **Totals.** `ResourceListState.loadPage()` sets `totalItems` from each response: `meta.page.total` when it is a number (0 included), else null. `PaginatedListState` is unchanged.
  > - **Rows kept while reloading.** `ResourceListState.refresh()` keeps the current page once one is loaded: a search, sort or filter change shows the rows with `isLoading()` (the table's `--loading` container, the users table's `UserListPage-grid--loadingPage`) until the new page replaces them. Only the first load is the empty initial-loading state.
  > - **Bulk delete and the user total.** The private `UserListTable` wraps `bulkContext().showResult`: before the summary shows, the users of `summary.ok` that no longer exist (`!model.exists`, i.e. deleted) lower `userCount` / `modelStatistics.users.total`. Counting deleted users rather than `ok.length` keeps a future non-delete bulk action from lowering the total.
  > - **Unhandled rejections.** `ResourceTable.debounce` terminates the commit with `.catch(() => {})` before `.finally`; `bulkDelete()`'s `onclick` catches `runBulkDelete()` (a failed reload has already alerted); `UserListPage.onremove()` cancels the pending `throttledSearch`.
  > - **Once per view.** `ResourceTable.view()` builds `bulkActionItems()` once and the toolbar and table share it (`currentBulkActions()`; outside a view pass it builds fresh). `UserListPage` builds `definition.fields()` once per page (the descriptor is memoized). The `invalid_column_content` fallback is translated per cell instead of one shared instance. `FIELD_COLUMNS` membership uses `hasOwnProperty` (`Object.hasOwn` is outside the configured TS lib).
  > - **CSV.** `csvCell` exports a vnode-like object (with `tag`) or a value `JSON.stringify` rejects (circular, BigInt) as `''`.
  > - **`ui.widget: 'hidden'`.** `fieldsFromDescriptor` gives such a field no form input (users' `token`); other `widget` values stay reserved and ignored. The users fixture now has `token.ui.widget: 'hidden'` and `required: {create: true}` on `username`, `email`, `password`; `ResourceFormModal.test.ts` checks the create form has no token and marks the three required.
  > - **Tests added:** page-1 URL without `page`; `?page=99`; zero / missing totals; rows kept on search/sort/filter (state, table, users page); `editModal()` returns the stable lazy loader resolving to `EditUserModal`; `onremove` clears timers and aborts a running export; the export error alert clears `exporting`; no unhandled rejection from a failed debounced search or a failed post-bulk-delete reload; bulk actions built once per redraw; end-to-end bulk delete with one failed DELETE (2 deleted, the failed row named, total −2); end-to-end CSV with `@evil` / `=HYPERLINK("x")`.
  > - **Comments.** `ResourceTable.tsx` / `ResourceListState.ts`: `@experimental` stays on the classes, exported types, the attrs interfaces and the extension seams (`items`, `toolbarItems`, `viewItems`, `actionItems`, `bulkActionItems`, `bulkContext`, `bulkRunner`, `columns`, `rowActions`); dropped from helper members, and docblocks that only restated the name were removed.
- [ ] **Step 5: Commit.** `refactor(admin): build the user list on the resource table`

### Task 9 — Bundled extension compatibility

**Type:** AFK · **Blocked by:** Task 8

**Files:**
- Modify: suspend `SuspendedFilter` and likes `LikedFilter` gain `DescribesFilter` (`boolean` and `relationship`→`posts` respectively; verify the likes semantics).
- Modify: suspend `UserResourceFields`, adding `->ui(['column' => 60])` on `suspendedUntil`.
- Test: `extensions/suspend/tests/integration/api/UsersSchemaTest.php` and `extensions/gdpr/tests/integration/api/UsersSchemaDeleteTest.php`.

- [x] **Step 1: Write the failing tests.** (PHP done 2026-10-28)
  - With suspend enabled, the users descriptor contains a `suspended` filter of kind `boolean`, and a field `suspendedUntil` with `ui.column 60` that is writable on update for the admin. → `extensions/suspend/tests/integration/api/UsersSchemaTest.php`
  - With nicknames enabled, `nickname` is described as `visible:false`, `writable {create, update}`, `nullable`, kind `string`, for the admin. Verified: `->visible(false)` plus `->writable(can('editNickname'))`, so the plan's "writable and invisible" guess was right. → `extensions/nicknames/tests/integration/api/UsersSchemaTest.php`
  - With gdpr enabled, `DELETE /api/users/{id}` as admin still goes through `UserResourceDeleteEndpoint` (reuses `DeleteUserTest`'s anonymise assertion), and the users descriptor has `capabilities.delete: true`. → `extensions/gdpr/tests/integration/api/UsersSchemaDeleteTest.php`
  - With likes enabled, the `liked` filter is described as `relationship` → `posts`. → `extensions/likes/tests/integration/api/UsersSchemaTest.php`
  - [ ] gdpr's admin JS `userActionItems` → `export-data` still renders. Add a jest case in `extensions/gdpr/js` if a test setup exists there; otherwise do a manual click-through in Task 10. **Pending Task 8.**
- [x] **Step 2: Run the tests and confirm RED where new.** suspend (2 failures: no `suspended` filter, no `ui.column`) and likes (no `liked` filter) were RED. nicknames and gdpr passed straight away, as expected: they pin behaviour that must keep working.
- [x] **Step 3: Implement.** `SuspendedFilter` and `LikedFilter` implement `DescribesFilter`; `suspendedUntil` gets `->ui(['column' => 60])`. Likes semantics verified: `LikedFilter` on `UserSearcher` takes a **post id** and keeps users with a `post_likes` row for that post, so `related: posts`. (`likedBy` is a separate `PostSearcher` filter taking a username or user id; it is not on users.)
- [x] **Step 4: Run each extension's suite and confirm GREEN.** Integration suites, run with a private sqlite DB per extension: suspend 31 tests, likes 30, nicknames 32, gdpr 111, all OK. gdpr's 3 warnings are existing `RepeatedQueryDetector` notices on `POST /api/gdpr-exports`, unrelated. PHPStan is clean on `extensions/{suspend,likes}/src`.
- [ ] **Step 5: Commit.** `feat(extensions): describe user filters and suspend column`

### Task 10 — Locales, docs, click-through

**Type:** HITL (visual sign-off) · **Blocked by:** Task 9

- [ ] **Step 1: Add the `core.admin.resource.*` and `core.lib.resource.*` keys** used by Tasks 6–8, with en only (core policy). Run `yarn --cwd framework/core/js build`.
- [ ] **Step 2: Do a click-through on a dev forum** using the `flarum-clickthrough-probe` skill:
  - `/admin#/users` with suspend, nicknames and gdpr enabled;
  - sort by username and joined date;
  - filter by group and by suspended;
  - search with the gambit `email:`;
  - create a user, edit a user, delete as admin, then gdpr export;
  - `?page=2` deep link;
  - hide two columns, reload, and confirm they stay hidden; repeat in a private window and confirm the table still works;
  - export CSV with an active group filter, then open the file in a spreadsheet: check umlauts (UTF-8 BOM), commas in display names, and that no formula executes;
  - a high-contrast theme.
- [ ] **Step 3: Write the extender docs.** Cover `->ui()`, `Describable`, `DescribesFilter`, `ResourceTable` and `schemaVersion`, for the 2.x docs repo or the CHANGELOG `Added` section. Include:
  - the `@experimental` policy: `ResourceTable`, `ResourceFormModal`, `ResourceDeleteModal`, `ResourceDescriber`, `DescribesFilter` and `->ui()` may change in 2.1 without a BC break; only the descriptor endpoint shape is versioned through `schemaVersion`;
  - the `ui` keys: `column`, `display` and `label` are consumed in 2.0; `group`, `priority`, `widget` and `placeholder` are reserved for forms;
  - an "Upgrading to 2.1" note (spec D8 migration path): `UserListPage.createModal()` / `editModal()` will return `ResourceFormModal` subclasses; `extend(CreateUserModal.prototype, 'fields')` moves to `extend(UserResourceFormModal.prototype, 'fields')` with the same ItemList keys; the forum `EditUserModal` is untouched; 2.1 adds `@deprecated` and `console.warn` on the old modal hooks only when generic forms are enabled; 2.0 emits none.
- [ ] **Step 4: Commit.** `docs: resource schemas and resource table`

---

## Follow-ups (not in this plan)

- 2.1 modal migration (spec D8 "2.1 migration path"):
  - override `UserListPage.createModal()` / `editModal()` to return `ResourceFormModal` subclasses (`UserResourceFormModal`);
  - extenders move `extend(CreateUserModal.prototype, 'fields')` to `extend(UserResourceFormModal.prototype, 'fields')` with the same ItemList keys;
  - the forum `EditUserModal` (nicknames) stays until a forum-side form lands;
  - add `@deprecated` and `console.warn` on the old modal hooks only when generic forms are enabled, with removal no earlier than 3.0.
- An `Index` on `resource-schemas` (SCIM `/ResourceTypes`) once a consumer needs discovery.
- D11 2.1 paths, each a single swap behind an interface already shipped in 2.0:
  - `bulkDelete` enabled by default on `UserListPage.bulkActionItems()`;
  - `BulkRunner` swapped to json-api-server `Atomic` once core registers it;
  - `selection.mode: 'matching'` ("select all N");
  - `exportCsv` moved to a server-side `text/csv` stream for sets larger than `maxRows`;
  - `ColumnPreferences` backed by a registered user preference;
  - async validation via `ui.asyncValidate`;
  - client validation on users arriving with the `ResourceFormModal` switch (D8).
- Move base-products and translate onto `flarum/common/resource/*`. This closes #169 Proposal 1.
