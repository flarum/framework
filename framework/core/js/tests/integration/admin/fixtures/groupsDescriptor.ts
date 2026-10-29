import type { ResourceDescriptor } from '../../../../src/common/resource/types';

/**
 * `GET /api/resource-schemas/groups` as an admin. The PHP side is pinned by
 * `tests/integration/api/resource_schemas/GroupsSchemaTest.php` (no pagination, names required on create,
 * the colour and icon widgets with their placeholders, the hidden filter); keep the two in step.
 */
export function groupsDescriptor(): ResourceDescriptor {
  const write = { create: true, update: true };

  return {
    schemaVersion: 1,
    endpoint: '/groups',
    capabilities: { list: true, create: true, delete: true },
    sorts: ['nameSingular', 'namePlural', 'isHidden'],
    filters: [{ key: 'hidden', kind: 'boolean' }],
    fields: [
      {
        name: 'nameSingular',
        kind: 'string',
        label: 'Name (singular)',
        required: { create: true },
        writable: write,
        sortable: true,
        ui: { column: 90, placeholder: 'Singular (e.g. Mod)' },
      },
      {
        name: 'namePlural',
        kind: 'string',
        label: 'Name (plural)',
        required: { create: true },
        writable: write,
        sortable: true,
        ui: { column: 85, placeholder: 'Plural (e.g. Mods)' },
      },
      { name: 'color', kind: 'string', label: 'Color', writable: write, nullable: true, ui: { widget: 'color', placeholder: '#aaaaaa' } },
      { name: 'icon', kind: 'string', label: 'Icon', writable: write, nullable: true, ui: { widget: 'icon', placeholder: 'fas fa-bolt' } },
      { name: 'isHidden', kind: 'boolean', label: 'Hide on forum', writable: write, sortable: true, ui: { column: 60 } },
      { name: 'position', kind: 'integer', label: 'Position', nullable: true },
      { name: 'canEdit', kind: 'boolean', label: 'Can edit' },
      { name: 'canDelete', kind: 'boolean', label: 'Can delete' },
    ],
  };
}
