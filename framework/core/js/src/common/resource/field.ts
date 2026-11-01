import app from '../app';
import Model from '../Model';
import type Mithril from 'mithril';
import type { ModelIdentifier } from '../Model';
import type ItemList from '../utils/ItemList';
import { relatedRecordLabel } from './resolvers';
import type { Field, FieldDescriptor, InputSpec, SaveData } from './types';

export function isRelation(d: FieldDescriptor | undefined): boolean {
  return d?.kind === 'toOne' || d?.kind === 'toMany';
}

/**
 * The related model(s) of a relationship as currently held in the store.
 */
export function relatedValue(m: Model | null, key: string, many: boolean): Model | Model[] | null {
  const data = m?.data.relationships?.[key]?.data as ModelIdentifier | ModelIdentifier[] | undefined;
  const find = (identifier: ModelIdentifier) => app.store.getById(identifier.type, identifier.id) ?? null;

  if (many) {
    return (Array.isArray(data) ? data : []).map(find).filter(Boolean) as Model[];
  }

  return data && !Array.isArray(data) ? find(data) : null;
}

/**
 * The current value of a field on a model: the field's getter, else the relationship (relations),
 * else the raw attribute.
 */
export function fieldValue<M extends Model>(key: string, f: Field<M>, m: M | null): unknown {
  if (f.value) {
    return f.value(m);
  }

  if (isRelation(f.descriptor)) {
    return relatedValue(m, key, f.descriptor!.kind === 'toMany');
  }

  return m?.attribute(key) ?? null;
}

/**
 * The save data for one field value: the field's serializer, else `{ relationships: { [key]: v } }`
 * for relations, else `{ [key]: v }`.
 */
export function fieldSave<M extends Model>(key: string, f: Field<M>, v: unknown, initial?: unknown): SaveData {
  if (f.serialize) {
    return f.serialize(v, initial);
  }

  if (isRelation(f.descriptor)) {
    return { relationships: { [key]: v as Model | Model[] | null } };
  }

  return { [key]: v };
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value) && !(value instanceof Model) && !(value instanceof Date);
}

/** Merges `source` into a copy of `target`, recursing where both sides pass `mergeable`. */
function deepMerge(
  target: Record<string, unknown>,
  source: Record<string, unknown>,
  mergeable: (value: unknown) => value is Record<string, unknown> = isPlainObject
): Record<string, unknown> {
  const out: Record<string, unknown> = { ...target };

  for (const key of Object.keys(source)) {
    const value = source[key];
    out[key] = mergeable(out[key]) && mergeable(value) ? deepMerge(out[key] as Record<string, unknown>, value, mergeable) : value;
  }

  return out;
}

/** Whether `patchInput()` merges into a value rather than replacing it: plain objects, not vnodes. */
function isMergeable(value: unknown): value is Record<string, unknown> {
  return isPlainObject(value) && !('tag' in value && 'attrs' in value);
}

/**
 * Patch the field at `key`: deep-merged into its input when that is an input spec (e.g.
 * `{ help: … }`, `{ type: 'textarea' }`), else shallow-merged into the field itself. Vnodes, models
 * and arrays are replaced, not merged. No-op if the key is absent.
 */
export function patchInput<M extends Model>(fields: ItemList<Field<M>>, key: string, patch: Partial<InputSpec> | Partial<Field<M>>): void {
  if (!fields.has(key)) return;

  const field = fields.get(key);

  if (field.input && typeof field.input === 'object') {
    fields.setContent(key, { ...field, input: deepMerge(field.input, patch as Record<string, unknown>, isMergeable) as InputSpec });
  } else {
    fields.setContent(key, { ...field, ...(patch as Partial<Field<M>>) });
  }
}

/**
 * Deep-merge save data parts (e.g. several fields into one container map, several
 * relations into one `relationships` object). Models, arrays and dates are replaced, not merged.
 */
export function mergeSaveData(...parts: SaveData[]): SaveData {
  return parts.reduce<Record<string, unknown>>((merged, part) => deepMerge(merged, part), {}) as SaveData;
}

/**
 * A comparable snapshot of a field value: models become identifiers, `''`/`undefined` become `null`.
 */
export function snapshot(value: unknown): string {
  return JSON.stringify(value ?? null, (_key, v) => {
    if (v instanceof Model) return { type: v.data.type, id: v.id() };
    if (v === '' || v === undefined) return null;

    return v;
  });
}

/**
 * Render a column cell: the column's renderer, else the value as text (relations by their
 * `ui.display` attribute, else `relatedRecordLabel()`'s fallbacks).
 */
export function renderColumn<M extends Model>(key: string, f: Field<M>, m: M): Mithril.Children {
  if (f.column && f.column.render) {
    return f.column.render(m);
  }

  const value = fieldValue(key, f, m);
  const display = f.descriptor?.ui?.display;
  const text = (v: unknown): string => {
    if (v instanceof Model) return relatedRecordLabel(v, display);
    if (v === null || v === undefined) return '';
    if (typeof v === 'object') return JSON.stringify(v);

    return String(v);
  };

  return Array.isArray(value) ? value.map(text).join(', ') : text(value);
}
