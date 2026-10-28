import { camelCaseToSnakeCase } from '../utils/string';
import type ItemList from '../utils/ItemList';
import type { Field, JsonApiError } from './types';

/**
 * Candidate field names for a JSON pointer, most specific first: the full path below
 * `/data/attributes` or `/data/relationships`, its first segment, its last segment.
 *
 * `/data/attributes/translations/de/name` → `translations/de/name`, `translations`, `name`.
 */
function candidates(pointer: string): string[] {
  const match = pointer.match(/^\/data\/(attributes|relationships)\/(.+)$/);

  if (!match) {
    return [];
  }

  let path = match[2];

  if (match[1] === 'relationships') {
    path = path.replace(/\/data(\/.*)?$/, '');
  }

  const segments = path.split('/');

  return [...new Set([path, segments[0], segments[segments.length - 1]])];
}

function matches(key: string, field: Field<any>, name: string): boolean {
  return key === name || camelCaseToSnakeCase(key) === name || !!field.errorKeys?.includes(name);
}

/**
 * Map JSON:API 422/403 errors onto field keys through their `source.pointer`. A pointer matches a
 * field by key, by `snake_case(key)` (rules added by `Extend\Validator`), or by `errorKeys`. Unmatched
 * errors land on `'*'`. Several errors for one key are joined by newlines.
 *
 * Pass only the fields that have an input. A field that merely holds a container other fields
 * write into (e.g. `translations`) must be `input: false`: otherwise it matches every pointer below
 * it by its first segment and takes the errors of the fields inside.
 *
 * @experimental
 */
export function mapErrors(errors: JsonApiError[], fields: ItemList<Field<any>>): Record<string, string> {
  const entries = Object.entries(fields.toObject()).map(([key, item]) => [key, item.content as Field<any>] as const);
  const mapped: Record<string, string> = {};

  for (const error of errors) {
    const message = error.detail ?? error.title ?? '';
    let target = '*';

    for (const name of candidates(error.source?.pointer ?? '')) {
      const found = entries.find(([key, field]) => matches(key, field, name));

      if (found) {
        target = found[0];
        break;
      }
    }

    mapped[target] = mapped[target] ? `${mapped[target]}\n${message}` : message;
  }

  return mapped;
}
