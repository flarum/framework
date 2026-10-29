import app from '../app';
import type ResourceSchema from '../models/ResourceSchema';
import type { FieldDescriptor, FormMode, RequiredCondition, ResourceDescriptor } from './types';
import { isAbsent } from './validation';

/**
 * The highest descriptor `schemaVersion` this client understands.
 */
export const SCHEMA_VERSION = 1;

const cache = new Map<string, Promise<ResourceDescriptor>>();

/**
 * Find the `resource-schemas` record of a type, memoized per session. The server sends an ETag with
 * `Cache-Control: private`, so the browser revalidates across sessions. Rejects (without the global
 * error alert) on 403 and other failures, and on a `schemaVersion` newer than `SCHEMA_VERSION`; a
 * failed load is not memoized.
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

const ACCEPTED = ['yes', 'on', '1', 'true'];
const DECLINED = ['no', 'off', '0', 'false'];

/**
 * A value as Laravel compares it in `required_if` / `unless`: booleans match `'1'`/`'true'` and
 * `'0'`/`'false'`, everything else its string form.
 */
function comparable(value: unknown): string[] {
  if (value === true) return ['1', 'true'];
  if (value === false) return ['0', 'false'];
  if (value === null || value === undefined) return [];

  return [String(value)];
}

/**
 * Whether one `requiredWhen` condition holds for the given form values, keyed by field name. A
 * field missing from `values` (hidden, or not in the form) is absent. Presence follows Laravel:
 * not null, not a blank string, not an empty array.
 */
export function conditionHolds(condition: RequiredCondition, values: Record<string, unknown>): boolean {
  const present = (name: string) => !isAbsent(values[name]);
  const [first] = condition.fields;
  const value = comparable(values[first]);
  const matches = (list: string[]) => value.some((v) => list.includes(v.toLowerCase()));

  switch (condition.rule) {
    case 'with':
      return condition.fields.some(present);
    case 'withAll':
      return condition.fields.every(present);
    case 'without':
      return !condition.fields.every(present);
    case 'withoutAll':
      return !condition.fields.some(present);
    case 'if':
      return value.some((v) => (condition.values ?? []).includes(v));
    case 'unless':
      return !value.some((v) => (condition.values ?? []).includes(v));
    case 'ifAccepted':
      return matches(ACCEPTED);
    case 'ifDeclined':
      return matches(DECLINED);
    default:
      return false;
  }
}

/**
 * Whether the field is required in `mode`: unconditionally (`required`), or because one of its
 * `requiredWhen` conditions holds for `values` (the form's current values by field name; omitted
 * means an empty form). `requiredConditional` adds nothing here: the server decides those.
 */
export function isRequired(d: FieldDescriptor, mode: FormMode, values: Record<string, unknown> = {}): boolean {
  if (d.required?.[mode]) return true;

  return (d.requiredWhen?.[mode] ?? []).some((condition) => conditionHolds(condition, values));
}
