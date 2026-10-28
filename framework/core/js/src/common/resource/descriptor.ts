import app from '../app';
import type ResourceSchema from '../models/ResourceSchema';
import type { FieldDescriptor, FormMode, ResourceDescriptor } from './types';

/**
 * The highest descriptor `schemaVersion` this client understands.
 */
export const SCHEMA_VERSION = 1;

const cache = new Map<string, Promise<ResourceDescriptor>>();

/**
 * Find the `resource-schemas` record of a type, memoized per session. The server sends an ETag with
 * `Cache-Control: private`, so the browser revalidates across sessions. Rejects (without the global
 * error alert) on 403 and other failures, and on a `schemaVersion` newer than `SCHEMA_VERSION`; a failed load is not memoized.
 *
 * The descriptor is a copy of the response's attributes, not the stored record's: the store merges
 * a refetch into the attributes object it already holds, so a key the server now omits would
 * survive, and a descriptor handed out earlier would change underneath its user.
 */
export function loadDescriptor(type: string): Promise<ResourceDescriptor> {
  let promise = cache.get(type);

  if (!promise) {
    promise = app.store
      .find<ResourceSchema>('resource-schemas', type, {}, { errorHandler: () => {} })
      .then((schema) => {
        const data = schema.payload?.data;
        const attributes = data && !Array.isArray(data) ? data.attributes : schema.data.attributes;

        const descriptor = JSON.parse(JSON.stringify(attributes)) as ResourceDescriptor;

        if (descriptor.schemaVersion > SCHEMA_VERSION) {
          throw new Error(
            `The "${type}" resource descriptor has schemaVersion ${descriptor.schemaVersion}, but this client supports up to ${SCHEMA_VERSION}.`
          );
        }

        return descriptor;
      })
      .catch((error) => {
        cache.delete(type);
        throw error;
      });

    cache.set(type, promise);
  }

  return promise;
}

/**
 * Forget memoized descriptors (all, or one type).
 */
export function clearDescriptorCache(type?: string): void {
  if (type === undefined) cache.clear();
  else cache.delete(type);
}

export function isWritable(d: FieldDescriptor, mode: FormMode): boolean {
  return !!d.writable?.[mode];
}

export function isRequired(d: FieldDescriptor, mode: FormMode): boolean {
  return !!d.required?.[mode];
}
