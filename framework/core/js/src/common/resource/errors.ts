import { camelCaseToSnakeCase } from '../utils/string';
import type ItemList from '../utils/ItemList';
import type { Field, JsonApiError, ResolvedError } from './types';

/** One JSON pointer token, unescaped (`~1` → `/`, `~0` → `~`). */
function decodeToken(token: string): string {
  return token.replace(/~1/g, '/').replace(/~0/g, '~');
}

/**
 * The decoded segments of a JSON:API `source.pointer` below `/data/attributes` or
 * `/data/relationships`; for a relationship, its `/data…` tail is dropped. Null for any other
 * pointer.
 *
 * `/data/attributes/translationMap/a~1b/name` → `['translationMap', 'a/b', 'name']`.
 */
export function pointerPath(pointer: string | undefined): string[] | null {
  const match = (pointer ?? '').match(/^\/data\/(attributes|relationships)\/(.+)$/);

  if (!match) return null;

  let path = match[2];

  if (match[1] === 'relationships') {
    path = path.replace(/\/data(\/.*)?$/, '');
  }

  return path.split('/').map(decodeToken);
}

/**
 * An `errorKeys` entry from plain segments, each escaped as a JSON pointer token (`~` → `~0`,
 * `/` → `~1`): the counterpart of `pointerPath()`.
 *
 * `errorKey('translationMap', 'a/b', 'name')` → `'translationMap/a~1b/name'`.
 */
export function errorKey(...segments: string[]): string {
  return segments.map((segment) => segment.replace(/~/g, '~0').replace(/\//g, '~1')).join('/');
}

/** The paths a field claims, as decoded segments: its key, its snake_case key, its `errorKeys`. */
function claims(key: string, field: Field<any>): string[][] {
  const paths = new Set([key, camelCaseToSnakeCase(key)]);

  return [...[...paths].map((path) => [path]), ...(field.errorKeys ?? []).map((path) => path.split('/').map(decodeToken))];
}

function startsWith(segments: string[], prefix: string[]): boolean {
  return prefix.length <= segments.length && prefix.every((segment, i) => segment === segments[i]);
}

/**
 * Map JSON:API 422/403 errors onto field keys through their `source.pointer`, as structured
 * errors. Rules, on the pointer's decoded segments below `/data/attributes` or
 * `/data/relationships` (`~1`/`~0` unescaped; a relationship's `/data…` tail dropped):
 *
 * - A field claims its key, its `snake_case` key (`Extend\Validator` rules answer with column
 *   names) and each of its `errorKeys`.
 * - A claim matches a pointer equal to it or below it, on whole segments.
 * - The longest matching claim across all fields wins (ties: the first field).
 * - `path` is the rest of the pointer below the claim, `/`-joined; absent when the pointer is the
 *   claim itself. No segment of a longer pointer is ever matched on its own.
 * - Unmatched and pointer-less errors land on `'*'`.
 *
 * Pass only the fields that have an input.
 */
export function mapErrors(errors: JsonApiError[], fields: ItemList<Field<any>>): Record<string, ResolvedError[]> {
  const claimed = Object.entries(fields.toObject()).flatMap(([key, item]) => claims(key, item.content as Field<any>).map((path) => ({ key, path })));
  const mapped: Record<string, ResolvedError[]> = {};

  for (const error of errors) {
    const pointer = error.source?.pointer;
    const segments = pointerPath(pointer);
    let best: { key: string; path: string[] } | null = null;

    for (const claim of segments ? claimed : []) {
      if (startsWith(segments!, claim.path) && (!best || claim.path.length > best.path.length)) best = claim;
    }

    const resolved: ResolvedError = { message: error.detail ?? error.title ?? '', error };
    const rest = best ? segments!.slice(best.path.length) : [];

    if (rest.length) resolved.path = rest.join('/');
    if (pointer !== undefined) resolved.pointer = pointer;

    (mapped[best?.key ?? '*'] ??= []).push(resolved);
  }

  return mapped;
}
