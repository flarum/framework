import type { ResourceDescriptor } from '../../../../src/common/resource/types';

/**
 * `GET /api/resource-schemas/users` as an admin, following `FieldDescriber`'s output (default-valued
 * keys omitted). The PHP side is pinned by `tests/integration/api/resource_schemas/UsersSchemaTest.php`
 * (sorts, search, pagination, capabilities, filters, username constraints, password write-only,
 * credentials required on create, the hidden `token` widget, groups relationship and the `ui`
 * hints); keep the two in step.
 */
export function usersDescriptor(): ResourceDescriptor {
  return {
    schemaVersion: 1,
    endpoint: '/users',
    capabilities: { list: true, create: true, delete: true },
    pagination: { defaultLimit: 20, maxLimit: 50 },
    search: true,
    sorts: ['username', 'commentCount', 'discussionCount', 'lastSeenAt', 'joinedAt'],
    filters: [
      { key: 'email', kind: 'string' },
      { key: 'group', kind: 'relationship', related: 'groups' },
    ],
    fields: [
      {
        name: 'username',
        kind: 'string',
        label: 'Username',
        writable: { create: true, update: true },
        required: { create: true },
        constraints: { minLength: 3, maxLength: 30, pattern: '^(?![0-9]*$)[a-z0-9_-]+$', patternFlags: 'i' },
        sortable: true,
        ui: { column: 90 },
      },
      {
        name: 'email',
        kind: 'string',
        label: 'Email',
        writable: { create: true, update: true },
        required: { create: true },
        constraints: { format: 'email' },
      },
      { name: 'isEmailConfirmed', kind: 'boolean', label: 'Is email confirmed', writable: { create: true, update: true } },
      {
        name: 'password',
        kind: 'string',
        label: 'Password',
        visible: false,
        writable: { create: true, update: true },
        required: { create: true },
        constraints: { minLength: 8 },
      },
      { name: 'token', kind: 'string', label: 'Token', visible: false, writable: { create: true }, ui: { widget: 'hidden' } },
      { name: 'displayName', kind: 'string', label: 'Display name', ui: { column: 85 } },
      { name: 'avatarUrl', kind: 'string', label: 'Avatar url' },
      { name: 'avatarSrcset', kind: 'string', label: 'Avatar srcset' },
      { name: 'hasUploadedAvatar', kind: 'boolean', label: 'Has uploaded avatar' },
      { name: 'slug', kind: 'string', label: 'Slug' },
      { name: 'joinTime', kind: 'datetime', label: 'Joined', ui: { column: 80 } },
      { name: 'discussionCount', kind: 'integer', label: 'Discussion count', sortable: true },
      { name: 'commentCount', kind: 'integer', label: 'Comment count', sortable: true },
      { name: 'lastSeenAt', kind: 'datetime', label: 'Last seen at', nullable: true, sortable: true },
      { name: 'canEdit', kind: 'boolean', label: 'Can edit' },
      { name: 'canEditAvatar', kind: 'boolean', label: 'Can edit avatar' },
      { name: 'canEditCredentials', kind: 'boolean', label: 'Can edit credentials' },
      { name: 'canEditGroups', kind: 'boolean', label: 'Can edit groups' },
      { name: 'canDelete', kind: 'boolean', label: 'Can delete' },
      {
        name: 'groups',
        kind: 'toMany',
        label: 'Groups',
        writable: { update: true },
        relationship: { types: ['groups'] },
        ui: { column: 70, display: 'nameSingular' },
      },
    ],
  };
}
