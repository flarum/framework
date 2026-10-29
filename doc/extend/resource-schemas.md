# Resource schemas, tables and forms

Flarum can describe an API resource to the frontend. The server builds a descriptor of the resource's fields, sorts and filters for the current actor. The frontend turns that descriptor into an admin table (sorting, filters, pagination, row actions, column toggles, CSV export, bulk actions) and into create and edit forms with client-side validation. The admin user list, the admin access token list and the admin groups page are built on the table; the groups page also uses the generic form.

This page is the reference for that API. It ships in the first minor release after v2.0.2.

**Audience:** extension developers who know `Extend\ApiResource` and the 2.x JSON:API resources. **Applies to:** `flarum/core` 2.x.

**Stability:** everything listed in [Public API](#public-api) is covered by Flarum's normal backwards-compatibility promise. The descriptor shape is versioned by `schemaVersion`.

## The descriptor endpoint

`GET /api/resource-schemas/{type}` returns the descriptor of one resource type. It is a JSON:API resource of type `resource-schemas` with a single `Show` endpoint. The id is the described type. On the client, the store model is `flarum/common/models/ResourceSchema`, registered as `resource-schemas`.

A trimmed response for `GET /api/resource-schemas/users` as an administrator, with suspend enabled:

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
      "search": true,
      "sorts": ["username", "commentCount", "discussionCount", "lastSeenAt", "joinedAt"],
      "filters": [
        { "key": "email", "kind": "string" },
        { "key": "group", "kind": "relationship", "related": "groups" },
        { "key": "suspended", "kind": "boolean" }
      ],
      "fields": [
        {
          "name": "username", "kind": "string", "label": "Username",
          "required": { "create": true }, "writable": { "create": true, "update": true },
          "constraints": { "minLength": 3, "maxLength": 30, "pattern": "^(?![0-9]*$)[a-z0-9_-]+$", "patternFlags": "i" },
          "sortable": true, "ui": { "column": 90 }
        },
        {
          "name": "password", "kind": "string", "label": "Password", "visible": false,
          "required": { "create": true }, "writable": { "create": true, "update": true },
          "constraints": { "minLength": 8 }
        },
        { "name": "joinTime", "kind": "datetime", "label": "Joined", "ui": { "column": 80 } },
        {
          "name": "groups", "kind": "toMany", "label": "Groups",
          "relationship": { "types": ["groups"] }, "writable": { "update": true },
          "ui": { "column": 70, "display": "nameSingular" }
        }
      ]
    }
  }
}
```

### Top-level keys

The full list is `ResourceDescriber::KEYS`.

| Key | Meaning | When present |
|---|---|---|
| `schemaVersion` | Version of the descriptor shape, currently `1`. | Always |
| `endpoint` | The collection path, for example `/users`. | Always |
| `capabilities` | `list` and `create`: the actor passes the `Index` and `Create` endpoint's authorization (type-level; `Create` sees no model, so only global permissions apply). `delete`: the actor passes the `Delete` endpoint's authorization against a blank stored record (for users, `user.delete`). | `list` and `create` always; `delete` only when true |
| `pagination` | `defaultLimit` and `maxLimit` of the `Index` endpoint. | When `Index` paginates |
| `defaultSort` | The `Index` endpoint's default sort string. | When set (users have none) |
| `search` | `true` when `filter[q]` searches the resource. | When true |
| `sorts` | The sort names visible to the actor. | When there is at least one |
| `filters` | The described filters: `{key, kind, enum?, related?}`. See [Describe search filters](#describe-search-filters). | When at least one is described |
| `fields` | One entry per field the actor can see or write, in declaration order. | Always |

`capabilities.delete` is type-level. A policy that decides per record ("own posts only") sees a blank record and may deny. Whether one row can be deleted is that record's `canDelete` attribute.

### Field keys

Each field has `name`, `kind` and `label`. `kind` is one of `string`, `integer`, `number`, `boolean`, `date`, `datetime`, `array`, `toOne` and `toMany`. A field that is neither visible nor writable is left out. Every other key is omitted when it holds its default:

| Key | Default | Meaning |
|---|---|---|
| `visible` | `true` | The field is serialized on `Show` or `Index`. A check that throws counts as not visible. |
| `writable` | `{}` | `{create?: true, update?: true}`. `create` is listed only for an actor who can create. A check that throws counts as writable and sets `conditional`. |
| `required` | `{}` | `{create?: true, update?: true}`. The exact `required` rule counts. A `required_without` or `required_without_all` rule counts too when none of the fields it names is offered to the client (not described, or `ui.widget: 'hidden'`). That is why users' `username`, `email` and `password` (`requiredOnCreateWithout(['token'])`) are required on create. `required_with`, `required_if` and the like never count. |
| `nullable` | `false` | |
| `conditional` | `false` | Writability depends on the record. The field is offered, and the server decides. |
| `sortable` | `false` | A visible sort has the field's name. |
| `constraints` | `{}` | `minLength`, `maxLength`, `pattern`, `patternFlags`, `enum`, `format` (`email`, `url`), `minimum`, `maximum`, `minItems`, `maxItems`. See below. |
| `default` | none | The `Create` endpoint's default for the field. Scalar kinds only, and only for an actor who can create. |
| `relationship` | none | `{types: string[]}` on `toOne` and `toMany` fields. |
| `ui` | `{}` | The field's `->ui()` hints, without `label`. A `placeholder` that is an existing translation key is sent translated. |

Constraints come from two sources:

- The field type's JSON-schema fragment (`Type::schema()`). Null values and a `minLength` of `0` are dropped.
- The field's string validation rules: `email`, `url`, `in:`, `regex:`, and `min:` / `max:`. `min:` and `max:` map to `minLength`/`maxLength` on strings, `minimum`/`maximum` on integers and numbers, and `minItems`/`maxItems` on arrays. Rule objects and closures are ignored.

A `regex:` rule becomes `pattern` and `patternFlags`. The PCRE flags `i`, `m`, `s` and `u` become the same JS flags. A regex with any other flag (`x`, `U`, `D`, …) is left out, so the client never checks a pattern with a different meaning.

### Labels

The label is resolved on the server in the actor's locale, in this order:

1. `ui.label`, translated when it is an existing translation key, otherwise used as written.
2. The field's `->description()`, when it is an existing translation key.
3. The key `core.admin.resource.{type}.{snake_name}`, when it exists.
4. The humanised field name: `joinTime` becomes "Join time".

### Who may read a descriptor

| Request | Response |
|---|---|
| A guest, for any id | `401` |
| An unknown type, or one that is not describable | `404` |
| An actor who passes neither the `Index` nor the `Create` endpoint's authorization | `403` |
| Otherwise | `200` |

There is no permission of its own. The descriptor is built for the actor who asks and lists only what that actor could already reach through the resource's own endpoints.

For users on a default install, every member may read it: members hold `searchUsers`, and `Create` is open while sign-up is on. A member without `user.editCredentials` gets no `email` filter, and sees `email`, `password` and `username` only as create-only inputs while sign-up is open. If your admin page needs a tighter gate, check your own permission on the client.

### Caching

The response carries an `ETag` (a sha1 of the attributes) and `Cache-Control: private`. Send the ETag back in `If-None-Match` and an unchanged descriptor returns `304 Not Modified` with no body. The descriptor differs by actor and by locale (labels are translated), so it is never shared between users. There is no server-side cache.

On the client, `loadDescriptor(type)` from `flarum/common/resource/descriptor` memoizes the descriptor for the session. A failed load is not memoized. `clearDescriptorCache(type?)` forgets one type or all.

### `schemaVersion`

`schemaVersion` is always sent, even though every other default is omitted. It is currently `1`. New optional keys do not change it. It is raised only in a major release, for a change that would break a client reading version `1`. `loadDescriptor()` rejects a descriptor whose `schemaVersion` is higher than the client supports, with an error that names both versions.

## Make a resource describable

A resource is describable only when an extension opts it in. Core opts in `users`, `access-tokens` and `groups`. Opt in your own resource in `extend.php`:

```php
<?php

use Acme\Badges\Api\Resource\BadgeResource;
use Flarum\Extend;

return [
    (new Extend\ApiResource(BadgeResource::class))
        ->describable(),
];
```

The resource must extend `Flarum\Api\Resource\AbstractDatabaseResource`. Subclasses of an opted-in resource are describable too. You can opt in a resource you do not own in the same way.

### Closures during describing

The descriptor is inferred by running the resource's field, endpoint and sort closures against synthetic contexts, one each for `create`, `update`, `show` and `index`. They are instances of `Flarum\Api\Describe\DescribeContext`, a subclass of `Flarum\Api\Context` with `mode(): 'create'|'update'|'show'|'index'`.

- The model is an unsaved instance from `newModel()` (`exists = true` for `update` and `show`). The `index` context has no model.
- The request carries the actor. The describer strips the query parameters, body and route parameters of the live request.
- A closure that needs a real record can check `$context instanceof DescribeContext` and return early.

## Add UI hints with `->ui()`

Every Flarum schema field (`Flarum\Api\Schema\*` attributes and relationships) has `ui(array $hints)` and `getUi()`. The hints are stored apart from `->schema()`, so a later `->schema()` call keeps them. Several calls merge their keys, so two extensions can each add hints to one field.

```php
<?php

use Flarum\Api\Schema;

Schema\Str::make('name')
    ->ui(['column' => 90, 'label' => 'acme-badges.admin.badges.name_label']);
```

Add a hint to a field you do not own with `Extend\ApiResource::field()`. This shows the comment count as a column in the admin user list:

```php
<?php

use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Extend;

return [
    (new Extend\ApiResource(UserResource::class))
        ->field('commentCount', fn (Schema\Integer $field) => $field->ui(['column' => 50])),
];
```

### Hint keys

| Key | Value | Read by core |
|---|---|---|
| `column` | `true`, or an integer priority (higher is further left). `false` hides the column. | Tables |
| `display` | The attribute that labels a related record, for example `nameSingular` for groups. | Tables, relation inputs and relationship filters |
| `label` | A translation key or a literal. The server resolves it into the field's `label`. | Server |
| `widget` | `'hidden'`: the field gets no form input (users' `token`), and a `required_without` rule naming only such fields describes as required. On a `string` field, any other value names the input type: `'color'` is the colour input with preview (`color-preview`), `'icon'` the icon input with picker, and anything else is used as the type as written. Register a renderer for a type of your own on the definition's `formInputs()` factory; a type without a renderer goes to `FormGroup`. A blank value is sent as `null` when the field is nullable. | Forms. `'hidden'` on any field, the other values on `string` fields only. |
| `placeholder` | The input's placeholder. A translation key that exists is sent translated, like `label`; anything else is sent as written. | Forms, unless the field's input sets its own placeholder |
| `group`, `priority` | Form section, form order. | Not read. Accepted and sent in the descriptor. |

In a `ResourceTable` with inferred columns, a visible field becomes a column when it has `ui.column` or is sortable. The admin user list adds columns only for fields with `ui.column`.

## Describe search filters

A filter appears in `filters` only when it is described. There are two ways to describe one.

### Implement `DescribesFilter`

`Flarum\Search\Filter\DescribesFilter` has one method, `describe(User $actor): ?array`. Return `kind` and, for some kinds, `enum` or `related`. `kind` is one of `string`, `boolean`, `enum` and `relationship`.

Return `null` to hide the filter from this actor. Mirror the permission check that `filter()` makes, so the admin UI never offers a filter that does nothing. This is how suspend's `SuspendedFilter` works:

```php
<?php

namespace Flarum\Suspend\Query;

use Carbon\Carbon;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\User\Guest;
use Flarum\User\User;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class SuspendedFilter implements FilterInterface, DescribesFilter
{
    public function getFilterKey(): string
    {
        return 'suspended';
    }

    public function describe(User $actor): ?array
    {
        // Mirrors `filter()`, which ignores the value unless the actor can suspend.
        if (! $actor->can('suspend', new Guest())) {
            return null;
        }

        return ['kind' => 'boolean'];
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        if (! $state->getActor()->can('suspend', new Guest())) {
            return;
        }

        // `$negate` is true for `filter[-suspended]`: match users who are not suspended.
        $state->getQuery()->where(function ($query) use ($negate) {
            if ($negate) {
                $query->where('suspended_until', null)->orWhere('suspended_until', '<', Carbon::now());
            } else {
                $query->where('suspended_until', '>', Carbon::now());
            }
        });
    }
}
```

Core's `EmailFilter` returns null without `user.editCredentials`. `GroupFilter` (`relationship` → `groups`) and likes' `LikedFilter` (`relationship` → `posts`) are ungated.

### Use `Extend\SearchDriver::describeFilter()`

Use the extender for a filter you do not own, or for a closure filter. The description is an array, a callable, or the class name of an invokable class:

```php
<?php

use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Search\UserSearcher;
use Flarum\User\User;

return [
    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        // An array is shown to every actor who can read the descriptor.
        ->describeFilter(UserSearcher::class, 'liked', ['kind' => 'relationship', 'related' => 'posts'])
        // A callable gets the actor and returns null to hide the filter.
        ->describeFilter(UserSearcher::class, 'suspended', fn (User $actor) => $actor->hasPermission('user.suspend')
            ? ['kind' => 'boolean']
            : null),
];
```

The rules:

- An extender description wins over the filter's own `describe()`. Otherwise the first filter for the key that implements `DescribesFilter` describes it. Otherwise the key is left out.
- A description for a key that has no registered filter is ignored.
- Descriptions are keyed by the exact searcher class. If a site replaces the searcher with a subclass, describe the filter for that subclass too.
- Only the database search driver is described. When a site searches the resource with another driver (for example an external index), the descriptor keeps `search: true` while the resource is searchable, because that driver serves `filter[q]`, and lists no filters. A filter sent together with `q` runs on that driver, so the database filters' descriptions might not match it.

The list of filters is not guaranteed to be complete. Do not build UI that assumes it is.

### Boolean filters must honour `$negate`

The table sends "Yes" of a boolean filter as `filter[key]=1` and "No" as `filter[-key]=1`. It never sends `filter[key]=0`. `FilterManager` strips the leading `-` and calls `filter()` with `$negate = true`. The value is not read.

So a filter described as `boolean` must constrain the query on `$negate`, not on `$value`. A filter that ignores `$negate` returns the "Yes" rows for "No".

The table treats a filter as negatable when it is described as `boolean`, or when a `boolean` field has its key. Set `negatable` on the client-side `Filter` to override this.

### Relationship filters and their display attribute

A `relationship` filter lists the records of its `related` type, each labelled by a display attribute. The attribute is the `ui.display` of the field with the filter's key; else of a relationship field over the same type; else `name`. The users `group` filter has no `group` field, so it takes `nameSingular` from the `groups` field.

## Show a resource in an admin page

`ResourceTable` (`flarum/admin/components/resource/ResourceTable`) renders a table for one type from its `ResourceDefinition` (`flarum/common/resource/ResourceDefinition`). The frontend store needs a model for the type, and the backend resource must be describable.

```ts
// js/src/common/extend.ts
import Extend from 'flarum/common/extenders';
import Badge from './models/Badge';

export default [new Extend.Store().add('badges', Badge)];
```

```ts
// js/src/admin/extend.ts
import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import BadgeListPage from './components/BadgeListPage';

export default [...commonExtend, new Extend.Admin().page(BadgeListPage)];
```

```tsx
// js/src/admin/components/BadgeListPage.tsx
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import ResourceTable from 'flarum/admin/components/resource/ResourceTable';
import ResourceDefinition from 'flarum/common/resource/ResourceDefinition';
import type Badge from '../../common/models/Badge';

export default class BadgeListPage extends ExtensionPage {
  definition = new ResourceDefinition<Badge>('badges');

  content() {
    return (
      <div className="container">
        <ResourceTable definition={this.definition} />
      </div>
    );
  }
}
```

The table loads the descriptor and then the list. It reads and writes the page, sort, search and filters in the URL.

### `ResourceTable` attributes (`ResourceTableAttrs`)

| Attribute | Default | Meaning |
|---|---|---|
| `definition` | required | The `ResourceDefinition`. |
| `state` | a new `ResourceListState` | An existing list state. |
| `syncUrl` | `true` | Sync page, sort, search and filters with the URL. |
| `pageSize` | 20 (`PaginatedListState.DEFAULT_PAGE_SIZE`) | Rows per page. Ignored when `state` is passed. |
| `columns` | inferred from the fields | An `ItemList<ColumnData>` that replaces the inferred columns. Its keys become `data-column-name`. |
| `className` | | Added to the root element. |
| `tableClassName` | | Added to the table container (around the `<table>` and its loading overlay). |
| `rowAttrs(model)` | | Extra attributes for one row. |
| `onCreate()`, `onEdit(model)` | `ResourceFormModal` | Open your own modal instead of the built-in form modal. |
| `formInputs`, `filterInputs` | `definition.formInputs()`, `definition.filterInputs()` | The `InputFactory` for the form modal and the filter toolbar. |
| `exportable` | `definition.canExport()` | Show "Export CSV". |
| `actionsColumn` | `true` | Append the actions column (`rowActions()`). Pass `false` when `columns` brings its own. |
| `inlineFilters` | `ResourceTable.INLINE_FILTERS` (3) | Filters shown as toolbar buttons before the rest go under "More filters". |
| `compactFilters` | at phone width | Fold the filters into one "Filters (n)" dropdown. |
| `searchCoveredFilters` | `'all'` | Which filters a gambit covers and the toolbar leaves to the search box: `'all'`, `'text'` (text-like filters only) or `'none'`. Type `SearchCoveredFilters`. |

### `ColumnData`

`{name, content(model)}` plus optional keys:

| Key | Meaning |
|---|---|
| `sort` | The descriptor sort the header toggles. |
| `className` | Added to the header cell and every body cell. |
| `attrs(model)` | Extra attributes for one record's cell. |
| `toggleable` | Listed in the "Columns" dropdown. Default `true`. |
| `hidden` | Hidden until the user shows it. |
| `csv(model)` | The CSV value. Without it, the CSV takes the raw attribute when the column key is a non-relationship field, else leaves the column out. |

### What the table offers

- **Create** shows when `definition.canCreate()` is true (`capabilities.create`). **Edit** and **Delete** show per row from the record's `canEdit` and `canDelete` attributes, so a resource must serialize them, for example `Schema\Boolean::make('canDelete')->get(fn (Badge $badge, Context $context) => $context->getActor()->can('delete', $badge))`. A row without them gets neither button. Delete opens `ResourceDeleteModal`, which calls the type's `Delete` endpoint (an extension's replacement included).
- **Sort** by the column headers and a sort dropdown, over the descriptor's `sorts`.
- **Search** is a core `Input`, shown when the descriptor has `search`. When the type has enabled gambits, it sits inside the gambit autocomplete, so `key:value` can be typed.
- **Filters** are dropdown buttons labelled "{label}: {value}". The first `inlineFilters` are on the toolbar; the rest are under "More filters", and picking one adds its button. A filter with a value is always on the toolbar.
- **Search-covered filters.** A filter whose key a gambit covers is left to the search box under the `searchCoveredFilters` policy. A filter's own `hideWhenSearchable` wins over the policy; `false` keeps its dropdown.
- **Boolean filters** offer Any, Yes and No. No is sent as `filter[-key]=1`.
- **Relationship filters** list the related records through `RelationOptions`, with a search box (when the type is searchable), "Load more" and "Retry" after a failed request. A filter's `exclude(model)` leaves records out.
- **Chips** sit in a second row, one per filter with a value, with a remove button and "Clear all". A chip is marked overridden when a gambit typed in the search sends the same key with the same sign (the gambit wins on the server), and marked as a conflict when it sends the opposite sign (`-suspended` against `suspended`).
- **Columns** dropdown: every column whose `toggleable` is not `false`. Only explicit choices are stored, in `localStorage` under `flarum.resourceTable.{type}.columns`. A column never toggled follows its `hidden` default. The table works when storage is blocked.
- **Export CSV** (`definition.canExport()`, default `capabilities.list`): pages through the current filter and sort at `pagination.maxLimit`, up to 5000 rows, through the normal list endpoint, so the file holds only what the actor can see. It downloads `{type}-{YYYY-MM-DD}.csv` with a UTF-8 BOM. Quoting follows RFC 4180. A value starting with `=`, `+`, `-`, `@`, tab or carriage return gets a `'` prefix. The button turns into a cancel button with progress while running.
- **Bulk select** appears only when `definition.bulkActions(selection, context)` returns items. It is empty by default. The selection holds the ids of the current page and is cleared on any page, sort, filter or search change. `definition.bulkDelete(context)` is a ready-made "Delete selected" item: it confirms with the deletable count, skips rows whose `canDelete` is false, runs one `DELETE` per record (three at a time) and opens `BulkResultModal` with what was deleted, skipped or failed.
- **Row menus** open below their row even in a short table: `floatMenus()` positions an open menu `fixed` at its toggle.
- **Unpaginated types.** When the descriptor has no `pagination` (the `Index` endpoint sets no limit, as for groups), `ResourceListState` requests the list without `page[limit]` and `page[offset]`, so the server sends every record. The list stays on page 1, a `?page=` in the URL is ignored, and no pager shows. `list.isPaginated()` tells the two cases apart; it is `true` until the descriptor is loaded.

### Regions

Every region is an `ItemList` method you can extend:

| Method | Keys |
|---|---|
| `items()` | `toolbar`, `subToolbar` (only when it has items), `table`, `pagination` |
| `toolbarItems()` | `view`, `actions` (the count, then `actionItems()`), `bulk` (only with bulk actions) |
| `viewItems()` | `search`, `sort`, one `filter-{key}` per inline filter, `moreFilters`. In compact mode, one `filters` item instead of the filter items. |
| `actionItems()` | `refresh`, `create` (when `canCreate()`), then `definition.toolbar(list)` |
| `subToolbarItems()` | `filters` (chips and "Clear all"), `display` |
| `displayItems()` | `columns`, `export` |
| `chipItems()` | one chip per filter with a value, keyed by the filter key |
| `columns()` | one column per key |
| `rowActions(model)` | `edit`, `delete`, then `definition.actions(model)` |

### `ResourceDefinition`

`new ResourceDefinition('badges')` infers everything from the descriptor. Subclass it to change what views get:

| Method | Default |
|---|---|
| `load()` | Fetches the descriptor (memoized). Views await it. |
| `fields()` | The field registry from the descriptor (`fieldsFromDescriptor()`). |
| `inputResolvers()` | `defaultInputResolvers()`: `widget` (a string field's `ui.widget`, see [Hint keys](#hint-keys)), then one per kind (`relation`, `enum`, `boolean`, `number`, `date`, `datetime`, `textarea`, `string`). `array` fields get no input. |
| `formInputs()`, `filterInputs()` | The `InputFactory` for forms and for the filter toolbar. |
| `filterResolvers()`, `filters()` | One `Filter` per described filter. |
| `actions(model)`, `toolbar(list)` | Empty `ItemList`s for row actions and toolbar items. |
| `bulkActions(selection, context)`, `bulkDelete(context)`, `runBulkDelete(context)` | No bulk actions; the delete helper. |
| `query()` | Extra list request parameters, merged under the list's own. |
| `canCreate()`, `canExport()` | `capabilities.create`, `capabilities.list`. |
| `recordLabel(model)` | The name of one record in the edit modal title ("Edit {name}"), the delete confirmation, the bulk result and the row checkbox's label: the first non-empty string among `name`, `title`, `label`, `displayName`, `username` and `slug`, else `#id`. Override it when the naming attribute is another one. |
| `createTitle()` | The create modal's title, e.g. "Create Group". `null` (the default) keeps the generic "Create". |
| `label(n)` | The humanised type. |

```ts
import ResourceDefinition from 'flarum/common/resource/ResourceDefinition';
import type Badge from '../../common/models/Badge';

export default class BadgeDefinition extends ResourceDefinition<Badge> {
  constructor() {
    super('badges');
  }

  query() {
    return { include: 'category' };
  }
}
```

A field registry entry (`Field` in `flarum/common/resource/types`) has `label`, `column` (`false` hides it), `input` (an `InputSpec`, a function of the field context, or `false`), `value(model)`, `serialize(value, initial)`, `when(model)`, `errorKeys`, `onerrors(errors, form)` and the inferred `descriptor`. Change one entry with `patchField(fields, key, patch)` from `flarum/common/resource/fieldsFromDescriptor`. `renderColumn()` in `flarum/common/resource/field` renders a cell the way inferred columns do.

## Forms

The forms render the fields the actor may write in the current mode, check them against the descriptor before sending, and send only changed fields. The server answers 403 for a non-writable field present in a payload, so dirty-only saves are what make one form work for every actor.

### `ResourceFormModal`

`flarum/admin/components/resource/ResourceFormModal` creates or edits one record in a modal. `ResourceTable` opens it for Create and Edit unless `onCreate` / `onEdit` are passed.

```ts
import app from 'flarum/admin/app';
import ResourceFormModal from 'flarum/admin/components/resource/ResourceFormModal';

app.modal.show(ResourceFormModal, {
  definition: this.definition,
  model: badge, // omit or null to create
  onsaved: (saved) => this.reload(),
});
```

| Attribute | Meaning |
|---|---|
| `definition` | The `ResourceDefinition`. |
| `model` | The record to edit; omit or `null` to create one. |
| `onsaved(model)` | Called with the saved record before the modal closes. |
| `inputs` | The `InputFactory`; default `definition.formInputs()`. |

Its overridable surface mirrors `CreateUserModal` and `EditUserModal`:

| Method | Default |
|---|---|
| `fields()` | `ItemList`: one item per writable field (keyed by field name, at the field's priority), `errors` (-5, errors no field matched), `submit` (-10). Empty while loading. |
| `fieldView(key, field)` | The field's input with its error below. |
| `data()` | The save data of the changed fields (`formState.buildSaveData()`). |
| `submitData()` | What the submit sends; `data()` by default. |
| `onsaved(model)` | Calls the `onsaved` attribute, then closes. |
| `title()`, `className()`, `body()` | The modal parts. |

`formState` holds the `ResourceFormState` once the descriptor is loaded.

### `ResourceForm`

`flarum/common/resource/ResourceForm` is the same form inline, in admin or forum. Attributes: `definition`, `model`, `onsaved(model)`, `onstate(state)` (called once the state is ready), `submit` (render the button, default `true`), `autofocus`, `inputs`. `items()` holds one item per field, `errors` (-90) and `submit` (-100). `fieldView()`, `errorView(error)`, `autofocus(root)` and `focusFirstInvalid()` are overridable. `renderFormField()`, `renderFieldError()` and `focusFirstControl()` are exported for views that lay out fields themselves.

### `ResourceFormState`

`flarum/common/resource/ResourceFormState` is the editing state of one record. `ResourceFormState.create(definition, model)` awaits the descriptor first.

| Member | Meaning |
|---|---|
| `model`, `mode()`, `isCreating()` | The record (`null` while creating; replaced by the saved record), `'create'` or `'update'`. |
| `visibleFields()` | Fields with an input that pass `when()` and are writable in the mode. |
| `stream(key)`, `initial`, `reset()` | One stream per visible field and the values it started with. A new record's empty field starts from the descriptor `default`. |
| `isDirty(key)`, `dirtyKeys()`, `buildSaveData()` | The changed fields and their deep-merged save data. |
| `validate()`, `validateField(key)` | Client-side validation, see below. |
| `save(data?)` | Validates, then saves `data` (default `buildSaveData()`). |
| `errors` | Messages keyed by field, `'*'` for the rest. |
| `shared(key, initial)` | View state several inputs share; never sent. |

`save()` with invalid fields rejects with `ValidationError` (`flarum/common/resource/validation`, `errors: Record<string, string[]>`) and sends no request. On a 422 or 403 response, `mapErrors()` (`flarum/common/resource/errors`) puts each error under its field through its `source.pointer`: by key, by `snake_case(key)` (rules added through `Extend\Validator`), or by the field's `errorKeys`. Unmatched errors go to `'*'`. The promise still rejects. Other errors show core's alert. A field that only holds a container other fields write into must be `input: false`, or it takes the errors of the fields inside.

### Client-side validation

`validateValue(field, value, mode)` returns translated messages for `required` in the mode and the `constraints`. `validate()` runs it on submit: every visible field while creating, only changed fields while updating. `validateField(key)` runs it on blur for a changed field; it never blocks and clears only errors it set.

- An absent value (`null`, blank string, empty array) is checked only against `required`.
- Length counts code points. A `pattern` JS cannot compile is skipped with a debug warning.
- `conditional` fields, fields without a descriptor, uniqueness, `minItems` and `maxItems` are left to the server.

Message keys: `core.lib.resource.validation.{required,min_length,max_length,pattern,enum,email,url,minimum,maximum}`.

### Inputs

`InputFactory` (`flarum/common/resource/inputs/InputFactory`) renders an `InputSpec` (`{type, …attrs}`) through the renderer registered for its type, else through `FormGroup` (`text`, `textarea`, `switch`, `number`, `date`, `datetime-local`, …). Methods: `register(type, renderer)`, `unregister(type)`, `has(type)`, `render(spec, ctx)`.

| Set | Types |
|---|---|
| `defaultFormInputs()` | `relation` (`{related, multiple?, display?, nullable?, search?}`, the stream holds model(s)), `enum` (`{choices, nullable?}`) |
| `defaultFilterInputs()` | `enum`, `select`, `text`, `search`, `number`, `email`, `url`, `tri-state` (any / yes / no), `relation` (a single picker; the stream holds the id) |

Register a type on `definition.formInputs()` to add or replace an input in every form of the resource.

`RelationInput` (`flarum/common/resource/inputs/RelationInput`) is an async picker over `app.store.find(type, {filter: {q}, page})`: a select for one record, switches for many, a search box and "Load more". Attributes: `type`, `multiple`, `display`, `stream`, `name`, `placeholder`, `nullable`, `disabled`, `search`, `pageSize` (default 20). A type in `RelationInput.UNSEARCHABLE_TYPES` gets no search box by default, because its list endpoint has no fulltext filter. The admin user list adds `groups`. `RelationOptions` (`inputs/RelationOptions`) is the same paged, searchable loading outside a component.

## Extend the admin user list

`UserListPage` is built on `ResourceTable`. It keeps its methods, item keys and priorities: `headerItems`, `actionItems`, `columns`, `userActionItems`, `headerInfo`, `loadPage` and `goToPage` work as before, and so do the 1-based `?page=` URL, 50 rows per page and the `filter[q]` gambit search. `CreateUserModal` and `EditUserModal` are unchanged and still the modals it opens. A column can also set the optional `ColumnData` keys. The module exports the types `ColumnData` and `UserListModal`.

`headerItems()` render inside the table's toolbar:

- `search` takes the search slot.
- Items ranked above `totalUsers` go in the view group, at their own priority.
- `totalUsers` takes the count slot. While a search or filter narrows the list, it reads "N matching users of M".
- The remaining items (`actions`, "New User") go in the actions group, which keeps the class `UserListPage-header`.

The user list sorts by its column headers and has no sort dropdown. `searchCoveredFilters()` returns `'all'`, so the stock list shows no group, suspended or email dropdowns: they are typed in the search as `group:`, `is:suspended` and `email:`. Only filters without a gambit get a dropdown, such as likes' `liked`. The group filter leaves out the guest group, which applies when an extension brings the group dropdown back by overriding `searchCoveredFilters()`.

| Method | Default | Use it to |
|---|---|---|
| `createModal()` | `CreateUserModal` | Change the modal "New User" opens. It gets `onCreated(user)`. |
| `editModal(user)` | A lazy loader for `EditUserModal` | Change the modal a row's "Edit" opens. It gets `user` and `onsaved(user)`. |
| `bulkActionItems(context)` | An empty `ItemList` | Add bulk actions. The checkbox column appears once this has an item. |
| `canExport()` | `app.session.user.isAdmin()` | Change who sees "Export CSV". The export contains email addresses. |
| `searchCoveredFilters()` | `'all'` | Choose which filters stay in the search box instead of the toolbar. |

Both modal seams may return a modal class or a loader such as `() => import('./components/MyModal')`: anything `app.modal.show()` accepts (`UserListModal`).

Column toggles are on; `id`, `username` and `userActions` cannot be hidden. The row menu (`userActionItems`) gains `delete` (-100), shown when `user.canDelete()`, which opens `ResourceDeleteModal`.

Offer a bulk delete:

```ts
import { extend } from 'flarum/common/extend';
import UserListPage from 'flarum/admin/components/UserListPage';
import type User from 'flarum/common/models/User';
import ResourceDefinition from 'flarum/common/resource/ResourceDefinition';

// One definition, so a running delete is not started twice.
const users = new ResourceDefinition<User>('users');

extend(UserListPage.prototype, 'bulkActionItems', function (items, context) {
  items.add('delete', users.bulkDelete(context), 100);
});
```

Each selected user is deleted through `DELETE /api/users/{id}`, so an extension that replaces the `Delete` endpoint (such as gdpr) still runs for every user.

Open your own create modal:

```ts
import { override } from 'flarum/common/extend';
import UserListPage from 'flarum/admin/components/UserListPage';
import InviteUserModal from './components/InviteUserModal';

override(UserListPage.prototype, 'createModal', function () {
  return InviteUserModal;
});
```

`InviteUserModal` must call `this.attrs.onCreated(user)` after it saves, so the page bumps the user total and reloads.

## The admin access token list

`AccessTokenListPage` (`flarum/admin/components/AccessTokenListPage`, route `accessTokens`, `/admin#/access-tokens`) lists every session, remembered session and developer token for actors with `moderateAccessTokens`. Without that permission the `access-tokens` list endpoint returns only the actor's own tokens, so a member who reads the descriptor learns nothing about other users' tokens. Rows are revoked one at a time; the actor's current session cannot be revoked there, because only logging out ends it.

The `access-tokens` descriptor has the sorts `createdAt` and `lastActivityAt`, an includable `user` relationship, and the attributes `type` and `canDelete`. `canDelete` is true when the token is not the actor's current session and the actor may revoke it. `capabilities.delete` is type-level: the `Delete` endpoint has no `can()` check. The `user` filter (`relationship` → `users`) is described only for actors with `moderateAccessTokens`. The `type` filter is an `enum` of the registered token types (`AccessToken::getModels()`), an extension's types included.

The module exports these seams:

| Export | Default |
|---|---|
| `AccessTokenDefinition` | `include: 'user'`; `canCreate()` is false (developer tokens are created by their owners on the forum); `canExport()` is admins only, because the export holds IP addresses; the `type` filter shows translated type labels. |
| `AccessTokenListState` | Writes `DEFAULT_ACCESS_TOKEN_SORT` (`-lastActivityAt`) to the URL when it names no sort. |
| `AccessTokenTable` | `columns()` renders the inferred columns for reading (`user` cannot be hidden). `rowActions()` replaces `delete` with `revoke`, or with `current` (a lock) for the actor's current session. |

`AccessTokenRevokeModal` (`flarum/admin/components/AccessTokenRevokeModal`) extends `ResourceDeleteModal` and revokes through `DELETE /api/access-tokens/{id}`. The same module exports `accessTokenTypeLabel(type)` and `accessTokenLabel(token)`.

## The admin groups page

`GroupListPage` (`flarum/admin/components/GroupListPage`, route `groups`, `/admin#/groups`) lists every group the actor can see and creates, edits and deletes them through `ResourceFormModal` and `ResourceDeleteModal`. It has no CSV export. Reordering groups stays on the Permissions page.

The `groups` descriptor:

- `nameSingular` (column 90) and `namePlural` (column 85) have translated placeholders. They are required on create. An update may leave them out, but may not empty them (`filled`).
- `color` has `widget: 'color'` and `icon` has `widget: 'icon'`. `isHidden` is column 60.
- These five fields are writable when creating (the `Create` endpoint checks `createGroup`) or when the actor can edit the group.
- `canEdit` and `canDelete` come from `GroupPolicy`. The admin, guest and member groups (ids 1–3) are never deletable, see [Release notes](#release-notes).
- The `Index` endpoint has no limit, so there is no `pagination` and the page shows every group.
- The `hidden` filter is described as `boolean`.

The module exports `GroupDefinition`. It adds a `badge` column (priority 100, no input), help text under the icon input, `recordLabel()` returns the singular name, and `createTitle()` gives the create modal the title "Create Group". Add a column or an input the same way as on any resource: a `->ui()` hint on a field added through `Extend\ApiResource(GroupResource::class)`, or `extend(GroupDefinition.prototype, 'fields', …)`.

## Public API

Covered by the backwards-compatibility promise:

- **PHP**
  - `Flarum\Api\Resource\ResourceSchemaResource` (`GET /api/resource-schemas/{type}`).
  - `Flarum\Api\Describe\ResourceDescriber`: `isDescribable()`, `canDescribe()`, `describe()`, `KEYS`, `SCHEMA_VERSION`. `Flarum\Api\Describe\DescribeContext`.
  - `FlarumField::ui()` and `getUi()`.
  - `Extend\ApiResource::describable()` and `Extend\SearchDriver::describeFilter()`.
  - `Flarum\Search\Filter\DescribesFilter`.
  - `SearchManager::filters($modelClass)` (`array<string, list<FilterInterface>>`), `fulltext($modelClass)`, `describeFilters($modelClass, $actor)`, `describeSearch($modelClass, $actor)`; `AbstractSearcher::getFilterManager()`; `FilterManager::all()`.
- **JS, `flarum/common/`**
  - `models/ResourceSchema` (store key `resource-schemas`).
  - `resource/types`, `resource/descriptor` (`loadDescriptor`, `clearDescriptorCache`, `isWritable`, `isRequired`), `resource/ResourceDefinition`, `resource/ResourceListState`, `resource/fieldsFromDescriptor`, `resource/field`, `resource/resolvers`.
  - `resource/ResourceForm`, `resource/ResourceFormState`, `resource/validation`, `resource/errors`.
  - `resource/inputs/InputFactory`, `resource/inputs/defaults`, `resource/inputs/RelationInput`, `resource/inputs/RelationOptions`.
  - `resource/csv`, `resource/ColumnPreferences`, `resource/BulkRunner`, `resource/recordLabel` (`recordLabel`, `RECORD_LABEL_ATTRIBUTES`; `flarum/admin/components/resource/recordLabel` re-exports it).
  - `Pill`'s `deleteLabel` attribute.
- **JS, `flarum/admin/`**
  - `components/resource/ResourceTable` (with `ResourceTableAttrs`, `ColumnData`, `SearchCoveredFilters`, `INLINE_FILTERS` and the regions above), `ResourceFormModal`, `ResourceDeleteModal`, `ColumnsDropdown`, `BulkResultModal`, `floatMenus`.
  - `UserListPage`'s `createModal()`, `editModal()`, `bulkActionItems()`, `canExport()`, `searchCoveredFilters()`, and its exported types `ColumnData` and `UserListModal`.
  - `components/AccessTokenListPage` (`AccessTokenDefinition`, `AccessTokenListState`, `AccessTokenTable`, `DEFAULT_ACCESS_TOKEN_SORT`) and `components/AccessTokenRevokeModal` (`accessTokenTypeLabel`, `accessTokenLabel`).
  - `components/GroupListPage` (`GroupDefinition`).

Internal, not covered: the container bindings `flarum.api.describable_resources` and `flarum.search.filter_descriptions` (use the extenders), and the classes `Flarum\Api\Describe\FieldDescriber` and `Flarum\Api\Describe\RuleHints` (the descriptor from `ResourceDescriber::describe()` is the contract).

## Release notes

The admin user list changes in two ways that can break a theme or an extension.

**The user list is a native `<table>`.** It used to be a CSS grid of `div` elements with ARIA table roles.

- The root element has the class `UserListPage-table`. `UserListPage-grid` (with `--loading`, `--loadingPage` and `--loaded`) is on the table container.
- Cells keep their `UserListPage-grid-*` and `UserList-*` classes and their `data-user-id` and `data-column-name` attributes.
- The `--columns` inline style is gone.

If your CSS relies on `display: grid`, `grid-template-columns` or `--columns` on the user list, update it for table layout.

**`headerItems()` render inside the table toolbar.** The old header row above the list is gone. See [Extend the admin user list](#extend-the-admin-user-list) for where each item goes.

The TypeScript-private members `pageNumber`, `loadingPageNumber`, `isLoadingPage`, `getTotalPageCount` and `setPageNumberInUrl` were removed. Use `loadPage()` and `goToPage()`.

`Pill` gained a `deleteLabel` attribute, the accessible label of its delete button.

**The admin, guest and member groups cannot be deleted.** `GroupPolicy::delete()` force-denies groups 1–3 for every actor, administrators included, because core relies on them (`Guest`, `User::groups()`). `DELETE /api/groups/1`, `/2` and `/3` return `403`, and their rows carry `canDelete: false`. The moderator group and custom groups can still be deleted. The legacy `EditGroupModal` hides Delete for all three (it hid it only for the admin group; the Permissions page never opens it for guest or member).

**A group update need not send both names.** `nameSingular` and `namePlural` used to be required on every request; they are now required on create only, and may not be emptied. A `PATCH /api/groups/{id}` with only `color` succeeds.
