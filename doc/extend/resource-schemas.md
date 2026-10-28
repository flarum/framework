# Resource schemas and the resource table

Flarum 2.0 can describe an API resource to the admin frontend. The server builds a descriptor of the resource's fields, sorts and filters for the current actor. The admin frontend turns that descriptor into a table with sorting, filters, pagination, row actions, column toggles and a CSV export. The admin user list is built this way.

This guide shows how to opt a resource in, how to shape what the table shows, and how to extend the user list.

> **Experimental.** Everything on this page except the descriptor shape is `@experimental` in 2.0. See [Stability](#stability) before you build on it.

**Audience:** extension developers who know `Extend\ApiResource` and the 2.x JSON:API resources. **Applies to:** `flarum/core` 2.x.

## The descriptor endpoint

`GET /api/resource-schemas/{type}` returns the descriptor of one resource type. It is a normal JSON:API resource of type `resource-schemas` with a single `Show` endpoint. The id is the described resource's type.

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

| Key | Meaning | When present |
|---|---|---|
| `schemaVersion` | Version of the descriptor shape. `1` in 2.0. | Always |
| `endpoint` | The resource's collection path, for example `/users`. | Always |
| `capabilities` | `list` and `create`: whether the actor passes the `Index` and `Create` endpoints. `delete`: the actor passes the `Delete` endpoint's authorization, checked against a blank stored record (for users, `user.delete`). | `list` and `create` always; `delete` only when true |
| `pagination` | `defaultLimit` and `maxLimit` of the `Index` endpoint. | When `Index` paginates |
| `defaultSort` | The `Index` endpoint's default sort string. | When it is set (users have none) |
| `search` | `true` when the resource's searcher has a fulltext filter. | When true |
| `sorts` | The sort names visible to the actor. | When there is at least one |
| `filters` | The described filters, see [Describe search filters](#describe-search-filters). | When at least one is described |
| `fields` | One entry per field the actor can see or write. | Always |

`capabilities.delete` is type-level. A policy that decides per record ("own posts only") sees a blank record here and may deny. Whether one row can be deleted is still that record's `canDelete` attribute.

### Field keys

Each field has `name`, `kind` and `label`. `kind` is one of `string`, `integer`, `number`, `boolean`, `date`, `datetime`, `array`, `toOne` and `toMany`. The other keys are left out when they hold their default:

| Key | Default | Meaning |
|---|---|---|
| `visible` | `true` | The field is serialized on `Show` or `Index`. |
| `writable` | `{}` | `{create?: true, update?: true}`. `create` is only listed for an actor who can create. |
| `required` | `{}` | `{create?: true, update?: true}`. |
| `nullable` | `false` | |
| `conditional` | `false` | The writable check threw on a blank model. The field is offered, and the server still decides. |
| `sortable` | `false` | A sort with the field's name is visible. |
| `constraints` | `{}` | From the field type's schema: `minLength`, `maxLength`, `pattern`, `patternFlags`, `enum`, `format`, `minimum`, `maximum`, `minItems`, `maxItems`. |
| `default` | none | The field's default, for create forms. |
| `relationship` | none | `{types: string[]}` on `toOne` and `toMany` fields. |
| `ui` | `{}` | The field's `->ui()` hints, without `label`. |

The label is resolved on the server in the actor's locale, in this order:

1. `ui.label`, translated when it is an existing translation key, otherwise used as written.
2. The field's `->description()`, when it is an existing translation key.
3. The key `core.admin.resource.{type}.{snake_name}`, when it exists.
4. The humanised field name: `joinTime` becomes "Join time".

### Who may read a descriptor

- Guests get `401` for every id.
- An unknown type, or a type that is not describable, returns `404`.
- The actor must pass the authorization of the resource's `Index` endpoint **or** its `Create` endpoint. Otherwise the response is `403`. There is no extra permission.

The descriptor is built for the actor who asks. It only lists what that actor could already reach through the resource's own endpoints. For users on a default install, every member may read it: members hold `searchUsers`, and `Create` is open while sign-up is on. For example, a member without `user.editCredentials` gets no `email` filter, and sees `email` only as a create-only input (`"visible": false`) while sign-up is open. If your admin page needs a tighter gate, check your own permission on the client.

### Caching

The response carries an `ETag` (a sha1 of the attributes) and `Cache-Control: private`. Send the ETag back in `If-None-Match` and an unchanged descriptor returns `304 Not Modified` with no body. The descriptor depends on the actor and the locale, so it is never shared between users. There is no server-side cache.

On the client, `loadDescriptor(type)` from `flarum/common/resource/descriptor` memoizes the descriptor for the session. `clearDescriptorCache(type?)` forgets it.

### `schemaVersion`

`schemaVersion` is always sent, even though every other default is left out. It is `1` in 2.0. New optional keys do not change it. A change that would break a client that reads version `1` raises it. Check it if you read the endpoint yourself.

## Make a resource describable

A resource is describable only when an extension opts it in. Core opts in `users`. Opt in your own resource in `extend.php`:

```php
<?php

use Acme\Badges\Api\Resource\BadgeResource;
use Flarum\Extend;

return [
    (new Extend\ApiResource(BadgeResource::class))
        ->describable(),
];
```

The resource must extend `Flarum\Api\Resource\AbstractDatabaseResource`. Subclasses of an opted-in resource count as describable too. You can opt in a resource you do not own in the same way.

## Add UI hints with `->ui()`

Every Flarum schema field (`Flarum\Api\Schema\*` attributes and relationships) has a `ui(array $hints)` method. The hints are stored apart from `->schema()`, so a later `->schema()` call does not remove them. Several calls merge their keys, so two extensions can each add hints to the same field.

Add a hint to your own field:

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

| Key | Value | 2.0 |
|---|---|---|
| `column` | `true`, or an integer priority (higher is further left). `false` hides the column. | Used |
| `display` | The attribute used to label a related record, for example `nameSingular` for groups. | Used |
| `label` | A translation key or a literal label. | Used |
| `group` | Form section. | Reserved for forms |
| `priority` | Form order. | Reserved for forms |
| `widget` | Form input type. | Reserved for forms |
| `placeholder` | Input placeholder. | Reserved for forms |

Reserved keys are accepted and sent in the descriptor, but 2.0 ignores them. There is one exception: `widget: 'hidden'` already marks a field that no form offers, such as the users `token` field. A `required_without` rule that names only such fields makes the other field describe as required.

In a generic `ResourceTable`, a visible field becomes a column when it has `ui.column` or is sortable. The admin user list adds columns only for fields with `ui.column`.

## Describe search filters

A filter appears in `filters` only when it is described. There are two ways to describe one.

### Implement `DescribesFilter` on your filter

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

An ungated filter returns the same description for everyone. Likes' `LikedFilter` returns `['kind' => 'relationship', 'related' => 'posts']`.

### Boolean filters must honour `$negate`

The table sends "Yes" of a boolean filter as `filter[key]=1` and "No" as `filter[-key]=1`. It never sends `filter[key]=0`. `FilterManager` strips the leading `-` and calls `filter()` with `$negate = true`. The value is not read.

So a filter described as `boolean` must constrain the query on `$negate`, not on `$value`. `SuspendedFilter` does this: without `$negate` it matches suspended users, with it the others. A filter that ignores `$negate` returns the "Yes" rows for "No".

The same applies to a filter of another kind that shares its key with a `boolean` field: the table treats it as boolean too.

### Relationship filters and their display attribute

A `relationship` filter lists the records of its `related` type. Each record is labelled by a `display` attribute, `name` by default.

When the filter has a field of the same name, it takes that field's `ui.display`. When it does not, it takes the `ui.display` of a relationship field over the same type. The users `group` filter has no `group` field, so it takes `nameSingular` from the `groups` field. Give the relationship field a `display` hint and the filter follows.

### Use `Extend\SearchDriver::describeFilter()`

Use the extender for a filter you do not own. The description is an array, a callable, or the class name of an invokable class:

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

- An extender description wins over the filter's own `describe()`.
- Without either, the filter is left out.
- A description for a key that has no registered filter is ignored.
- Only the database search driver is read.

The list of filters is not guaranteed to be complete. Do not build UI that assumes it is.

## Show a resource in an admin page

`ResourceTable` renders a full table for one type from its `ResourceDefinition`. The frontend store needs a model for the type, and the backend resource must be describable.

Register the model and the page:

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

Render the table:

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

The table loads the descriptor and then the list. It reads and writes the page, sort, search and filters in the URL. Pass `syncUrl={false}` to turn that off.

What the table offers, and what decides it:

- **Columns** come from the fields. Pass `columns` (an `ItemList<ColumnData>`) to replace them.
- **Create** shows when `definition.canCreate()` is true. **Edit** and **Delete** show per row from the record's `canEdit` and `canDelete` attributes. Create and edit open `ResourceFormModal` unless you pass `onCreate` or `onEdit`. Delete opens `ResourceDeleteModal`, which calls the resource's `Delete` endpoint.
- **Export CSV** shows when `definition.canExport()` is true. It defaults to `capabilities.list`. Pass `exportable` or override `canExport()` to narrow it, for example when the type holds personal data.
- **Search** shows when the descriptor has `search`. It is a core `Input`. When the type has enabled gambits, it sits inside the gambit autocomplete, so `key:value` can be typed.
- **Filters** show as dropdown buttons labelled "{label}: {value}". The first `inlineFilters` (default 3, `ResourceTable.INLINE_FILTERS`) are on the toolbar. The rest are under "More filters"; picking one adds its button. A filter with a value is always on the toolbar.
- A filter whose key a gambit covers can be left to the search box, where it is typed as `key:value`. The `searchCoveredFilters` attribute sets the policy: `'text'` (the default) leaves out only text-like filters, `'all'` every covered filter, `'none'` none. A filter's own `hideWhenSearchable` wins over the policy. The user list exposes the policy as `UserListPage.searchCoveredFilters()`, which returns `'text'`, so Group and Suspended keep their dropdowns.
- **Boolean filters** offer Any, Yes and No. No is sent as `filter[-key]=1`, see [Boolean filters must honour `$negate`](#boolean-filters-must-honour-negate).
- **Chips** sit in a second row, one per filter with a value, with a remove button and "Clear all". When a gambit typed in the search sets the same key, the chip is marked as overridden, because the gambit wins on the server.
- **Columns dropdown** and **Export CSV** sit at the end of the second row. The dropdown lists every column whose `toggleable` is not `false`. The choice is kept in the browser's `localStorage`. The table still works when storage is blocked.
- **Compact mode.** Below core's phone breakpoint, or with `compactFilters`, the filters fold into one "Filters (n)" dropdown.
- **Bulk select** appears only when `definition.bulkActions(selection, context)` returns items. It is empty by default. `definition.bulkDelete(context)` is a ready-made "Delete selected" item.

To change the definition, extend `ResourceDefinition`. For example, `query()` adds request parameters and `actions(model)` adds row actions:

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

Every region of `ResourceTable` is an `ItemList` method you can extend: `items()`, `toolbarItems()`, `viewItems()`, `actionItems()`, `subToolbarItems()`, `displayItems()`, `chipItems()`, `columns()` and `rowActions(model)`.

- `items()` holds `toolbar`, `subToolbar` (only when it has items), `table` and `pagination`.
- `viewItems()` holds `search`, `sort`, one `filter-{key}` per inline filter and `moreFilters`. In compact mode it holds one `filters` item instead of the filter items.
- `subToolbarItems()` holds `filters` (the chips and "Clear all") and `display`.
- `displayItems()` holds `columns` and `export`. They used to be toolbar items.
- `chipItems()` holds one chip per filter with a value, keyed by the filter key.

## Extend the admin user list

`UserListPage` is built on `ResourceTable` and keeps all its existing methods, item keys and priorities. `headerItems`, `actionItems`, `columns`, `userActionItems`, `headerInfo`, `loadPage` and `goToPage` work as before. A column can now also set `sort`, `className`, `attrs`, `toggleable`, `hidden` and `csv`.

`headerItems()` no longer renders as a row of its own. Its items render inside the table's toolbar:

- `search` takes the table's search slot.
- `totalUsers` takes the count slot.
- Every other item goes in the actions group, which keeps the class `UserListPage-header`.

The filters, chips, "Columns" and "Export CSV" come from `ResourceTable`, as described above. The user list has no sort dropdown: sort by the column headers.

2.0 adds four seams.

| Method | Default | Use it to |
|---|---|---|
| `createModal()` | `CreateUserModal` | Change the modal the "New User" button opens. It gets an `onCreated(user)` attribute. |
| `editModal(user)` | A lazy loader for `EditUserModal` | Change the modal a row's "Edit" opens. It gets `user` and `onsaved(user)` attributes. |
| `bulkActionItems(context)` | An empty `ItemList` | Add bulk actions. The checkbox column appears once this has an item. |
| `canExport()` | `app.session.user.isAdmin()` | Change who sees "Export CSV". The export contains email addresses. |

Both modal seams may return a modal class or a loader such as `() => import('./components/MyModal')`. Anything `app.modal.show()` accepts works.

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

Each selected user is deleted through `DELETE /api/users/{id}`, so an extension that replaces the `Delete` endpoint (such as gdpr) still runs for every user. Users whose `canDelete` is false are skipped. A summary lists what was deleted, skipped or failed.

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

## Stability

These are marked `@experimental` in 2.0:

- `ResourceTable`, `ResourceFormModal` and `ResourceDeleteModal` (`flarum/admin/components/resource/*`);
- everything under `flarum/common/resource/*`, including `RelationOptions`, `Filter.kind` and `Filter.negatable`, and `ResourceListState.isNegatable()`, `filterParam()` and `clearFilters()`;
- `ResourceTable.INLINE_FILTERS` and the `inlineFilters` and `compactFilters` attributes;
- `Flarum\Api\Describe\ResourceDescriber` and `Flarum\Api\Describe\DescribeContext`;
- `Flarum\Search\Filter\DescribesFilter`;
- `->ui()` on schema fields;
- `Extend\ApiResource::describable()` and `Extend\SearchDriver::describeFilter()`;
- the `UserListPage` seams `createModal()`, `editModal()`, `bulkActionItems()` and `canExport()`.

`Pill` gained a `deleteLabel` attribute, the accessible label of its delete button. It is a backward-compatible addition and not experimental.

The experimental APIs may change in 2.1 without a backwards-compatibility break being declared. Only the descriptor shape returned by `GET /api/resource-schemas/{type}` is versioned, through `schemaVersion`. Pin your extension to a minor version if it depends on an experimental API, and test it against each 2.x minor release.

## Upgrading to 2.1

2.1 is planned to move the admin user list onto the generic form modal. 2.0 ships the seams for it and changes no behaviour.

- `UserListPage.createModal()` and `editModal()` will return `ResourceFormModal` subclasses (`UserResourceFormModal`). Nothing else on `UserListPage` changes.
- If you extend `CreateUserModal.prototype` or `EditUserModal.prototype` with `fields`, move to `extend(UserResourceFormModal.prototype, 'fields', …)`. The `ItemList` keys stay the same. `data()`, `submitData()` and `onsaved` keep their meaning.
- The forum's `EditUserModal` (used by nicknames) is not changed. Only the admin user list switches.
- 2.0 emits no deprecation warnings. 2.1 adds `@deprecated` and a `console.warn` on the old modal hooks, but only when generic forms are enabled. The old hooks are not removed before 3.0.

If you override `createModal()` or `editModal()` yourself, your override keeps working in 2.1.

## Release note: the user list is a `<table>`

The admin user list used to be a CSS grid of `div` elements with ARIA table roles. It is now a native `<table>`.

- The root element has the class `UserListPage-table`. `UserListPage-grid` (with `--loading`, `--loadingPage` and `--loaded`) is on the table container.
- Cells keep their `UserListPage-grid-*` and `UserList-*` classes and their `data-user-id` and `data-column-name` attributes.
- The `--columns` inline style is gone.
- The header items are no longer a row above the list. The table toolbar holds them and the filters in two rows: search, filters and actions first, then the filter chips, "Columns" and "Export CSV". The actions group keeps the class `UserListPage-header`.

If your CSS relies on `display: grid` or `grid-template-columns` on the user list, update it for table layout.

The TypeScript-private members `pageNumber`, `loadingPageNumber`, `isLoadingPage`, `getTotalPageCount` and `setPageNumberInUrl` were removed. Use `loadPage()` and `goToPage()`.
