import Icon from '../components/Icon';
import humanTime from '../helpers/humanTime';
import type Model from '../Model';
import ItemList from '../utils/ItemList';
import type { Field, FieldDescriptor, Filter, FilterDescriptor, FilterResolver, InputResolver } from './types';

/** Strings longer than this get a textarea. */
export const TEXTAREA_THRESHOLD = 255;

const empty = (v: unknown): boolean => v === '' || v === null || v === undefined;

const isRelationKind = (d: FieldDescriptor | null): boolean => d?.kind === 'toOne' || d?.kind === 'toMany';

function stringAttrs(d: FieldDescriptor): Record<string, unknown> {
  return d.constraints?.maxLength !== undefined ? { maxlength: d.constraints.maxLength } : {};
}

function nullableString(d: FieldDescriptor): Field<any>['serialize'] {
  return (v) => ({ [d.name]: empty(v) && d.nullable ? null : (v ?? '') });
}

function choices(values: string[]): [string, string][] {
  return values.map((value) => [value, value]);
}

function pad(n: number): string {
  return String(n).padStart(2, '0');
}

/**
 * ISO 8601 → the local `YYYY-MM-DDTHH:mm` a datetime-local input expects.
 */
export function toLocalDateTime(value: unknown): string | null {
  if (empty(value)) return null;

  const date = new Date(value as string);

  if (isNaN(date.getTime())) return null;

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function dateColumn(d: FieldDescriptor): Field<any>['column'] {
  return {
    render: (model: Model) => {
      const raw = model.attribute<string | null>(d.name);

      return raw ? humanTime(new Date(raw)) : null;
    },
  };
}

/**
 * The default input resolvers, one per field kind. A resolver maps a descriptor to an input spec
 * (rendered by the form's `InputFactory`) plus value/serialize/column overrides. Add your own with a
 * higher priority; a resolver returning `null` falls through.
 */
export function defaultInputResolvers(): ItemList<InputResolver> {
  const resolvers = new ItemList<InputResolver>();

  resolvers.add(
    'relation',
    (d) => {
      if (!isRelationKind(d)) return null;

      const type = d.relationship?.types[0];

      if (!type) return { input: false };

      return {
        input: {
          type: 'relation',
          related: type,
          multiple: d.kind === 'toMany',
          display: d.ui?.display ?? 'name',
          nullable: !!d.nullable,
        },
      };
    },
    50
  );

  resolvers.add(
    'enum',
    (d) => {
      if (d.kind !== 'string' || !d.constraints?.enum) return null;

      return { input: { type: 'enum', choices: choices(d.constraints.enum), nullable: !!d.nullable }, serialize: nullableString(d) };
    },
    40
  );

  resolvers.add(
    'boolean',
    (d) =>
      d.kind === 'boolean'
        ? {
            input: { type: 'switch' },
            column: { render: (model: Model) => (model.attribute(d.name) ? Icon.component({ name: 'fas fa-check' }) : null) },
          }
        : null,
    30
  );

  resolvers.add(
    'number',
    (d) => {
      if (d.kind !== 'integer' && d.kind !== 'number') return null;

      const attrs: Record<string, unknown> = { step: d.kind === 'integer' ? 1 : 'any' };
      if (d.constraints?.minimum !== undefined) attrs.min = d.constraints.minimum;
      if (d.constraints?.maximum !== undefined) attrs.max = d.constraints.maximum;

      return {
        input: { type: 'number', ...attrs },
        serialize: (v) => ({ [d.name]: empty(v) ? null : Number(v) }),
      };
    },
    30
  );

  resolvers.add(
    'date',
    (d) =>
      d.kind === 'date'
        ? {
            input: { type: 'date' },
            value: (model) => (model?.attribute(d.name) as string | null | undefined)?.slice(0, 10) ?? null,
            serialize: (v) => ({ [d.name]: empty(v) ? null : v }),
            column: dateColumn(d),
          }
        : null,
    30
  );

  resolvers.add(
    'datetime',
    (d) =>
      d.kind === 'datetime'
        ? {
            input: { type: 'datetime-local' },
            value: (model) => toLocalDateTime(model?.attribute(d.name)),
            serialize: (v) => ({ [d.name]: empty(v) ? null : new Date(v as string).toISOString() }),
            column: dateColumn(d),
          }
        : null,
    30
  );

  resolvers.add(
    'textarea',
    (d) => {
      const max = d.constraints?.maxLength;

      return d.kind === 'string' && max !== undefined && max > TEXTAREA_THRESHOLD
        ? { input: { type: 'textarea', ...stringAttrs(d) }, serialize: nullableString(d) }
        : null;
    },
    20
  );

  resolvers.add(
    'string',
    (d) => {
      if (d.kind !== 'string') return null;

      const format = d.constraints?.format;
      const type = format === 'email' || format === 'url' ? format : 'text';

      return { input: { type, ...stringAttrs(d) }, serialize: nullableString(d) };
    },
    10
  );

  // Arrays (and anything unknown) have no default input.
  resolvers.add('fallback', () => ({ input: false }), -100);

  return resolvers;
}

/**
 * The first non-null resolver result by priority.
 */
export function resolveInput(d: FieldDescriptor, resolvers: ItemList<InputResolver>): Partial<Field<any>> {
  for (const resolver of resolvers.toArray()) {
    const resolved = resolver(d);

    if (resolved) return resolved;
  }

  return { input: false };
}

/**
 * The default filter resolvers. The described filter's kind decides (`enum` → `enum`, `boolean` →
 * `tri-state`, `relationship` → `relation`); a filter that says less falls back to its same-named
 * field, then to `text`. Add your own with a higher priority; a resolver returning `null` falls
 * through.
 */
export function defaultFilterResolvers(): ItemList<FilterResolver> {
  const resolvers = new ItemList<FilterResolver>();

  resolvers.add(
    'enum',
    (filter, d) => {
      const values = filter.kind === 'enum' && filter.enum ? filter.enum : d?.kind === 'string' ? d.constraints?.enum : undefined;

      return values ? { type: 'enum', choices: choices(values) } : null;
    },
    40
  );

  resolvers.add('boolean', (filter, d) => (filter.kind === 'boolean' || d?.kind === 'boolean' ? { type: 'tri-state' } : null), 30);

  resolvers.add(
    'relation',
    (filter, d) => {
      const related = filter.kind === 'relationship' && filter.related ? filter.related : isRelationKind(d) ? d!.relationship?.types[0] : undefined;

      return related ? { type: 'relation', related, display: d?.ui?.display ?? 'name' } : null;
    },
    30
  );

  resolvers.add('fallback', () => ({ type: 'text' }), -100);

  return resolvers;
}

/**
 * The first non-null filter resolver result by priority.
 */
export function resolveFilterInput(filter: FilterDescriptor, d: FieldDescriptor | null, resolvers: ItemList<FilterResolver>): Filter['input'] {
  for (const resolver of resolvers.toArray()) {
    const resolved = resolver(filter, d);

    if (resolved) return resolved;
  }

  return { type: 'text' };
}
