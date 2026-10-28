import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { makeUser } from '@flarum/jest-config/factory';
import { app } from '../../../../src/admin';
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
    schemaVersion: 1,
    endpoint: '/things',
    capabilities: { list: true, create: true },
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
