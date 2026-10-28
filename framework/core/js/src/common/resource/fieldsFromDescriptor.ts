import type Model from '../Model';
import ItemList from '../utils/ItemList';
import { isWritable } from './descriptor';
import { resolveInput } from './resolvers';
import type { Field, FieldColumn, FieldDescriptor, InputResolver, ResourceDescriptor } from './types';

function column<M extends Model>(d: FieldDescriptor, resolved: Partial<Field<M>>): Field<M>['column'] {
  const hint = d.ui?.column;

  if (d.visible === false || hint === false || resolved.column === false) return false;
  if (hint === undefined && !d.sortable) return false;

  const col: FieldColumn<M> = { ...(resolved.column || {}) };

  if (d.sortable) col.sort = d.name;
  if (typeof hint === 'number') col.priority = hint;

  return col;
}

/**
 * Turn a descriptor into the keyed field registry, in declaration order. The input comes from the
 * first matching resolver and is kept when the field is writable on create or update and its
 * `ui.widget` is not `'hidden'` (other widget values are reserved until 2.1). A column is
 * shown for a visible field that is sortable (sorting by its name) or has `ui.column`; a numeric
 * `ui.column` is the column's priority, `false` hides it.
 *
 * @experimental
 */
export function fieldsFromDescriptor<M extends Model>(d: ResourceDescriptor, resolvers: ItemList<InputResolver>): ItemList<Field<M>> {
  const fields = new ItemList<Field<M>>();
  const count = d.fields.length;

  d.fields.forEach((descriptor, index) => {
    const resolved = resolveInput(descriptor, resolvers) as Partial<Field<M>>;
    // `ui.widget: 'hidden'` (e.g. users' `token`) is the one widget value 2.0 reads: no form input.
    const editable = descriptor.ui?.widget !== 'hidden' && (isWritable(descriptor, 'create') || isWritable(descriptor, 'update'));

    const field: Field<M> = {
      label: descriptor.label,
      ...resolved,
      input: editable ? (resolved.input ?? false) : false,
      column: column(descriptor, resolved),
      descriptor,
    };

    fields.add(descriptor.name, field, (count - index) * 10);
  });

  return fields;
}

/**
 * Shallow-merge `patch` into the field at `key`. No-op if the key is absent.
 *
 * @experimental
 */
export function patchField<M extends Model>(f: ItemList<Field<M>>, key: string, patch: Partial<Field<M>>): void {
  if (!f.has(key)) return;

  f.setContent(key, { ...f.get(key), ...patch });
}
