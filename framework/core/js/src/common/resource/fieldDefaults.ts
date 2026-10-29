import Icon from '../components/Icon';
import humanTime from '../helpers/humanTime';
import type Model from '../Model';
import type { Field, FieldDefaults, FieldDescriptor, Filter, FilterDescriptor } from './types';

/** Strings longer than this get a textarea. */
const TEXTAREA_THRESHOLD = 255;

const empty = (v: unknown): boolean => v === '' || v === null || v === undefined;

const isRelationKind = (d: FieldDescriptor | null): boolean => d?.kind === 'toOne' || d?.kind === 'toMany';

function stringAttrs(d: FieldDescriptor): Record<string, unknown> {
  return d.constraints?.maxLength !== undefined ? { maxlength: d.constraints.maxLength } : {};
}

/**
 * A field's serializer: a value goes through `convert`; an empty one is sent as `emptyValue` when
 * the field is not nullable and has one (strings: ""), else as null. Only a changed field is
 * serialized, so a cleared non-nullable field reaches the server, whose 422 lands on the field.
 */
function serializer(d: FieldDescriptor, convert: (v: any) => unknown = (v) => v, emptyValue?: unknown): Field<any>['serialize'] {
  return (v) => {
    if (!empty(v)) return { [d.name]: convert(v) };

    return { [d.name]: d.nullable || emptyValue === undefined ? null : emptyValue };
  };
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
function toLocalDateTime(value: unknown): string | null {
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
 * `ui.widget` values that name an input type by another name: `color` is rendered by
 * `ColorPreviewInput` (FormGroup's `color-preview`).
 */
export const WIDGET_ALIASES: Record<string, string> = { color: 'color-preview' };

/**
 * The default input of a described field and the value/serialize/column overrides that go with it;
 * null when the field has no default input (arrays, unknown kinds). The first match wins: `ui.widget`,
 * relation, enum, then by kind (boolean, number, date, datetime), textarea (long strings), string.
 * `ResourceDefinition.fieldInput()` calls it; override that to change one field and call `super`.
 */
export function defaultFieldInput(d: FieldDescriptor): FieldDefaults | null {
  // A string field's `ui.widget` (other than 'hidden') names its input type: `color` and `icon`
  // render through FormGroup, any other value through the renderer an extension registers on the
  // form's InputFactory. The inputs read a string, so a missing value starts empty.
  const widget = d.ui?.widget;

  if (d.kind === 'string' && typeof widget === 'string' && widget !== '' && widget !== 'hidden') {
    return {
      input: { type: WIDGET_ALIASES[widget] ?? widget, ...stringAttrs(d) },
      value: (model) => (model?.attribute(d.name) as string | null | undefined) ?? '',
      serialize: serializer(d, (v) => v, ''),
    };
  }

  if (isRelationKind(d)) {
    const type = d.relationship?.types[0];

    if (!type) return null;

    return { input: { type: 'relation', related: type, multiple: d.kind === 'toMany', display: d.ui?.display ?? 'name', nullable: !!d.nullable } };
  }

  if (d.kind === 'string' && d.constraints?.enum) {
    return { input: { type: 'enum', choices: choices(d.constraints.enum), nullable: !!d.nullable }, serialize: serializer(d, (v) => v, '') };
  }

  switch (d.kind) {
    case 'boolean':
      return {
        input: { type: 'switch' },
        column: { render: (model: Model) => (model.attribute(d.name) ? Icon.component({ name: 'fas fa-check' }) : null) },
      };

    case 'integer':
    case 'number': {
      const attrs: Record<string, unknown> = { step: d.kind === 'integer' ? 1 : 'any' };
      if (d.constraints?.minimum !== undefined) attrs.min = d.constraints.minimum;
      if (d.constraints?.maximum !== undefined) attrs.max = d.constraints.maximum;

      return { input: { type: 'number', ...attrs }, serialize: serializer(d, Number) };
    }

    case 'date':
      return {
        input: { type: 'date' },
        value: (model) => (model?.attribute(d.name) as string | null | undefined)?.slice(0, 10) ?? null,
        serialize: serializer(d),
        column: dateColumn(d),
      };

    case 'datetime':
      return {
        input: { type: 'datetime-local' },
        value: (model) => toLocalDateTime(model?.attribute(d.name)),
        serialize: serializer(d, (v) => new Date(v).toISOString()),
        column: dateColumn(d),
      };

    case 'string': {
      const max = d.constraints?.maxLength;

      if (max !== undefined && max > TEXTAREA_THRESHOLD) {
        return { input: { type: 'textarea', ...stringAttrs(d) }, serialize: serializer(d, (v) => v, '') };
      }

      const format = d.constraints?.format;
      const type = format === 'email' || format === 'url' ? format : 'text';

      return { input: { type, ...stringAttrs(d) }, serialize: serializer(d, (v) => v, '') };
    }
  }

  return null;
}

/**
 * The default input of a described filter. The filter's kind decides (`enum` → `enum`, `boolean` →
 * `tri-state`, `relationship` → `relation`); a filter that says less falls back to its same-named
 * field `d` (null when there is none), then to `text`. `ResourceDefinition.filterInput()` calls it.
 */
export function defaultFilterInput(filter: FilterDescriptor, d: FieldDescriptor | null): Filter['input'] {
  const values = filter.kind === 'enum' && filter.enum ? filter.enum : d?.kind === 'string' ? d.constraints?.enum : undefined;

  if (values) return { type: 'enum', choices: choices(values) };

  if (filter.kind === 'boolean' || d?.kind === 'boolean') return { type: 'tri-state' };

  const related = filter.kind === 'relationship' && filter.related ? filter.related : isRelationKind(d) ? d!.relationship?.types[0] : undefined;

  if (related) return { type: 'relation', related, display: d?.ui?.display ?? 'name' };

  return { type: 'text' };
}
