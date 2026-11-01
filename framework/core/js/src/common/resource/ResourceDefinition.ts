import type Mithril from 'mithril';
import type Model from '../Model';
import ItemList from '../utils/ItemList';
import { deleteAction, editAction, type ResourceAction } from './actions';
import { loadDescriptor } from './descriptor';
import recordLabel from './recordLabel';
import { fieldsFromDescriptor } from './fieldsFromDescriptor';
import { bareFilterInput, defaultFilterInputs, defaultFormInputs } from './inputs/defaults';
import InputFactory from './inputs/InputFactory';
import { rangeFilterInput } from './rangeFilterInput';
import { defaultFilterResolvers, defaultInputResolvers, resolveFilterInput, resolveInput } from './resolvers';
import type ResourceListState from './ResourceListState';
import type { Field, FieldDescriptor, Filter, FilterDescriptor, FilterResolver, InputResolver, ResourceDescriptor } from './types';

function humanize(value: string): string {
  const words = value
    .replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace(/[-_]+/g, ' ')
    .toLowerCase();

  return words.charAt(0).toUpperCase() + words.slice(1);
}

/**
 * Everything a view needs to know about a resource type. `new ResourceDefinition('users')` is a full
 * definition whose fields, sorts and filters are inferred from the server's descriptor; subclasses
 * (and `extend()`) override by key.
 */
export default class ResourceDefinition<M extends Model = Model> {
  descriptor: ResourceDescriptor | null = null;

  constructor(readonly type: string) {}

  /**
   * Fetch the descriptor (memoized per session). Views await this before rendering.
   */
  async load(): Promise<this> {
    this.descriptor = await loadDescriptor(this.type);

    return this;
  }

  /**
   * Maps described fields to inputs, see `defaultInputResolvers()`. Add a keyed resolver with a
   * higher priority to handle your fields; `fieldInput()` consults the list.
   */
  inputResolvers(): ItemList<InputResolver> {
    return defaultInputResolvers();
  }

  /**
   * A described field's input and the value/serialize/column overrides that go with it, null for
   * none: the first matching `inputResolvers()` entry. Override it to decide one field outright and
   * return `super.fieldInput(d)` for the rest.
   */
  fieldInput(d: FieldDescriptor): Partial<Field<M>> | null {
    return (resolveInput(d, this.inputResolvers()) as Partial<Field<M>>) ?? null;
  }

  /**
   * The input factory this resource's forms render with. Register a type here to add or replace an
   * input in every form of the resource; `ResourceForm`'s `inputs` attr replaces it for one form.
   */
  formInputs(): InputFactory {
    return new InputFactory(defaultFormInputs());
  }

  /**
   * The input factory the filter bar renders with: the default filter inputs plus `range`.
   */
  filterInputs(): InputFactory {
    return new InputFactory(defaultFilterInputs(), bareFilterInput).register('range', rangeFilterInput);
  }

  fields(): ItemList<Field<M>> {
    if (!this.descriptor) {
      throw new Error(`ResourceDefinition(${this.type}): call load() before fields().`);
    }

    return fieldsFromDescriptor<M>(this.descriptor, (d) => this.fieldInput(d));
  }

  /**
   * Maps described filters to input specs, see `defaultFilterResolvers()`.
   */
  filterResolvers(): ItemList<FilterResolver> {
    return defaultFilterResolvers();
  }

  /**
   * A described filter's input; `d` is the same-named field's descriptor, null when there is none.
   * The first matching `filterResolvers()` entry. Override it to decide one filter outright and
   * return `super.filterInput(filter, d)` for the rest.
   */
  filterInput(filter: FilterDescriptor, d: FieldDescriptor | null): Filter['input'] {
    return resolveFilterInput(filter, d, this.filterResolvers());
  }

  /**
   * One filter per described filter, its input from `filterInput()`, rendered by `filterInputs()`.
   * Labelled by the server's `label`, else the same-named field's, else the humanized key. Negatable
   * as the descriptor says, else when it is a boolean filter or named after a boolean field.
   */
  filters(): ItemList<Filter> {
    const filters = new ItemList<Filter>();
    const described = this.descriptor?.filters ?? [];

    if (!described.length) return filters;

    const fields = this.fields();

    described.forEach((filter, index) => {
      const field = fields.has(filter.key) ? fields.get(filter.key) : null;

      filters.add(
        filter.key,
        {
          label: filter.label ?? field?.label ?? humanize(filter.key),
          input: this.filterInput(filter, field?.descriptor ?? null),
          kind: filter.kind,
          negatable: filter.negatable ?? (filter.kind === 'boolean' || field?.descriptor?.kind === 'boolean'),
          ...(filter.required ? { required: true } : {}),
          ...(filter.default !== undefined ? { default: filter.default } : {}),
        },
        (described.length - index) * 10
      );
    });

    return filters;
  }

  /**
   * The operations offered on rows and on the selection, see `ResourceAction`. Built in, rows only:
   * `edit` and the destructive `delete`, each for the records whose descriptor `rowAbilities`
   * attribute is truthy; neither without `rowAbilities`. Set the `delete`'s `scope` to `'both'` to
   * offer it on the selection too.
   */
  resourceActions(): ItemList<ResourceAction<M>> {
    const items = new ItemList<ResourceAction<M>>();
    const abilities = this.descriptor?.rowAbilities;

    if (abilities?.edit) items.add('edit', editAction<M>(abilities.edit), 100);
    if (abilities?.delete) items.add('delete', deleteAction<M>(abilities.delete), 90);

    return items;
  }

  toolbar(_s: ResourceListState<M>): ItemList<Mithril.Children> {
    return new ItemList<Mithril.Children>();
  }

  /**
   * Extra list request parameters, e.g. `{ include: 'groups' }`, merged under the list's own.
   */
  query(): Record<string, unknown> {
    return {};
  }

  /**
   * Whether the actor may create records of this type. False until the descriptor is loaded.
   */
  canCreate(): boolean {
    return !!this.descriptor?.capabilities.create;
  }

  /**
   * Whether tables of this type offer a CSV export. Defaults to whether the actor may list the type,
   * since the export only pages through the same list endpoint. Override it to narrow the audience,
   * e.g. for types with personal data. False until the descriptor is loaded.
   */
  canExport(): boolean {
    return !!this.descriptor?.capabilities.list;
  }

  /**
   * A short human name for one record, in modal titles, confirmations and the bulk summary. Defaults
   * to `recordLabel()`.
   */
  recordLabel(model: M): string {
    return recordLabel(model);
  }

  /**
   * The name of `count` records of this type, e.g. "Group" for 1 ("Create Group") and "Groups" for
   * more (the search placeholder, the table caption): the descriptor's server-translated `label`
   * (`one` for 1, else `other`), else the humanized type for any count.
   */
  label(count: number): string {
    const label = this.descriptor?.label;

    if (label) return count === 1 ? label.one : label.other;

    return humanize(this.type);
  }
}
