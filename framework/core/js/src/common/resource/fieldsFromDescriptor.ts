import type Model from '../Model';
import ItemList from '../utils/ItemList';
import { isWritable } from './descriptor';
import { resolveInput } from './resolvers';
import type { Field, FieldColumn, FieldDescriptor, Filter, InputResolver, ResourceDescriptor } from './types';

function column<M extends Model>(d: FieldDescriptor, resolved: Partial<Field<M>>): Field<M>['column'] {
  const hint = d.ui?.column;

  if (d.visible === false || hint === false || resolved.column === false) return false;
  if (hint === undefined && !d.sortable) return false;

  const col: FieldColumn<M> = { ...(resolved.column || {}) };

  if (d.sortable) col.sort = d.name;
  if (typeof hint === 'number') col.priority = hint;
  if (d.ui?.hidden) col.hidden = true;

  return col;
}

/**
 * Turn a descriptor into the keyed field registry. The input comes from the
 * first matching resolver (or the given function, e.g. `definition.fieldInput()`) and is kept when
 * the field is writable on create or update and its `ui.widget` is not `'hidden'`; another `ui.widget` value names the input type (see the `widget`
 * resolver) and `ui.placeholder` becomes the input's placeholder. A column is shown for a visible
 * field that is sortable (sorting by its name) or has `ui.column`; a numeric `ui.column` is the
 * column's priority, `false` hides it, and `ui.hidden` starts it hidden. Fields are ordered by
 * `ui.priority` (higher first; absent counts as 0), then in declaration order.
 */
export function fieldsFromDescriptor<M extends Model>(
  d: ResourceDescriptor,
  resolvers: ItemList<InputResolver> | ((d: FieldDescriptor) => Partial<Field<M>> | null)
): ItemList<Field<M>> {
  const fields = new ItemList<Field<M>>();
  const count = d.fields.length;
  // A stable sort, so equal priorities keep the declaration order.
  const ordered = [...d.fields].sort((a, b) => (b.ui?.priority ?? 0) - (a.ui?.priority ?? 0));

  ordered.forEach((descriptor, index) => {
    const resolved = (typeof resolvers === 'function' ? resolvers(descriptor) : resolveInput(descriptor, resolvers)) ?? { input: false };
    // `ui.widget: 'hidden'` (e.g. users' `token`): no form input.
    const editable = descriptor.ui?.widget !== 'hidden' && (isWritable(descriptor, 'create') || isWritable(descriptor, 'update'));

    let input = editable ? (resolved.input ?? false) : false;
    const placeholder = descriptor.ui?.placeholder;

    if (input && typeof input === 'object' && typeof placeholder === 'string' && input.placeholder === undefined) {
      input = { ...input, placeholder };
    }

    const field: Field<M> = {
      label: descriptor.label,
      ...resolved,
      input,
      column: column(descriptor, resolved),
      descriptor,
    };

    fields.add(descriptor.name, field, (count - index) * 10);
  });

  return fields;
}

/**
 * Shallow-merge `patch` into the field at `key`. No-op if the key is absent.
 */
export function patchField<M extends Model>(f: ItemList<Field<M>>, key: string, patch: Partial<Field<M>>): void {
  if (!f.has(key)) return;

  f.setContent(key, { ...f.get(key), ...patch });
}

/**
 * Shallow-merge `patch` into the filter at `key`. No-op if the key is absent.
 */
export function patchFilter(f: ItemList<Filter>, key: string, patch: Partial<Filter>): void {
  if (!f.has(key)) return;

  f.setContent(key, { ...f.get(key), ...patch });
}
