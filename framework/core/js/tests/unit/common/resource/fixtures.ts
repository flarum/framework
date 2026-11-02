import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { makeUser } from '@flarum/jest-config/factory';
import { app } from '../../../../src/admin';
import { SCHEMA_VERSION } from '../../../../src/common/resource/descriptor';
import type { FieldDescriptor, ResourceDescriptor } from '../../../../src/common/resource/types';

/**
 * A field descriptor as the server sends it: default-valued keys omitted. Writable in both modes
 * unless `extra` says otherwise.
 */
export function fd(name: string, kind: FieldDescriptor['kind'], extra: Partial<FieldDescriptor> = {}): FieldDescriptor {
  return {
    name,
    kind,
    label: name.charAt(0).toUpperCase() + name.slice(1),
    writable: { create: true, update: true },
    nullable: true,
    ...extra,
  };
}

export function descriptor(fields: FieldDescriptor[], extra: Partial<ResourceDescriptor> = {}): ResourceDescriptor {
  return {
    schemaVersion: SCHEMA_VERSION,
    endpoint: '/things',
    capabilities: { list: true, create: true },
    pagination: { defaultLimit: 20, maxLimit: 50 },
    search: true,
    fields,
    ...extra,
  };
}

/**
 * The `GET /api/resource-schemas/{type}` document for a descriptor. A fresh copy, as from the wire:
 * the store merges later pushes into the attributes object it was first given.
 */
export function schemaDocument(d: ResourceDescriptor, type: string = 'things') {
  return { data: { type: 'resource-schemas', id: type, attributes: JSON.parse(JSON.stringify(d)) } };
}

export function bootAdmin(forum: Record<string, unknown> = {}): void {
  bootstrapAdmin({
    resources: [
      { type: 'forums', id: '1', attributes: { apiUrl: 'https://example.test/api', canEditUserCredentials: true, ...forum } },
      makeUser({ id: '1', attributes: { id: 1, username: 'admin', displayName: 'Admin', email: 'admin@machine.local' } }),
    ],
  });
  app.boot();
}

/**
 * Wait for a mounted list page's table, an async chunk (`admin/components/resource/tables`), and
 * redraw so the table is in the tree. Microtasks only, so it works under fake timers too.
 */
export async function awaitTables(out: { redraw: () => void }): Promise<void> {
  await import('../../../../src/admin/components/resource/tables');

  for (let i = 0; i < 20; i++) await Promise.resolve();

  out.redraw();
}

/**
 * The modal class an `app.modal.show()` call got: the class itself, or the default export of the
 * loader it was given for an async modal.
 */
export async function modalClass(modal: unknown): Promise<unknown> {
  if (typeof modal === 'function' && !('prototype' in modal && (modal as any).prototype?.view)) {
    return ((await (modal as () => Promise<{ default: unknown }>)()) as { default: unknown }).default;
  }

  return modal;
}
