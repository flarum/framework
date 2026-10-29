import type Mithril from 'mithril';
import type Model from '../Model';
import ItemList from '../utils/ItemList';
import type { BulkActionContext } from './BulkRunner';
import { loadDescriptor } from './descriptor';
import recordLabel from './recordLabel';
import { fieldsFromDescriptor } from './fieldsFromDescriptor';
import { bareFilterInput, defaultFilterInputs, defaultFormInputs } from './inputs/defaults';
import InputFactory from './inputs/InputFactory';
import { defaultFilterResolvers, defaultInputResolvers, resolveFilterInput } from './resolvers';
import type { ResourceSelection } from './ResourceListState';
import type ResourceListState from './ResourceListState';
import type { Field, Filter, FilterResolver, InputResolver, ResourceDescriptor } from './types';

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

  inputResolvers(): ItemList<InputResolver> {
    return defaultInputResolvers();
  }

  /**
   * The input factory this resource's forms render with. Register a type here to add or replace an
   * input in every form of the resource; `ResourceForm`'s `inputs` attr replaces it for one form.
   */
  formInputs(): InputFactory {
    return new InputFactory(defaultFormInputs());
  }

  /**
   * The input factory the filter bar renders with.
   */
  filterInputs(): InputFactory {
    return new InputFactory(defaultFilterInputs(), bareFilterInput);
  }

  fields(): ItemList<Field<M>> {
    if (!this.descriptor) {
      throw new Error(`ResourceDefinition(${this.type}): call load() before fields().`);
    }

    return fieldsFromDescriptor<M>(this.descriptor, this.inputResolvers());
  }

  /**
   * Maps described filters to input specs, see `defaultFilterResolvers()`.
   */
  filterResolvers(): ItemList<FilterResolver> {
    return defaultFilterResolvers();
  }

  /**
   * One filter per described filter, its input from the first matching `filterResolvers()` entry,
   * rendered by `filterInputs()`. Labelled by the same-named field, else the humanized key.
   */
  filters(): ItemList<Filter> {
    const filters = new ItemList<Filter>();
    const described = this.descriptor?.filters ?? [];

    if (!described.length) return filters;

    const fields = this.fields();
    const resolvers = this.filterResolvers();

    described.forEach((filter, index) => {
      const field = fields.has(filter.key) ? fields.get(filter.key) : null;

      filters.add(
        filter.key,
        {
          label: field?.label ?? humanize(filter.key),
          input: resolveFilterInput(filter, field?.descriptor ?? null, resolvers),
          kind: filter.kind,
          negatable: filter.kind === 'boolean' || field?.descriptor?.kind === 'boolean',
        },
        (described.length - index) * 10
      );
    });

    return filters;
  }

  actions(_m: M): ItemList<Mithril.Children> {
    return new ItemList<Mithril.Children>();
  }

  toolbar(_s: ResourceListState<M>): ItemList<Mithril.Children> {
    return new ItemList<Mithril.Children>();
  }

  /**
   * The actions offered for the selected records. Empty by default; a table renders its checkbox
   * column only when this has items. Add `bulkDeleteAction(context)` (`./bulkDelete`) for the built-in
   * delete.
   */
  bulkActions(_selection: ResourceSelection, _context: BulkActionContext<M>): ItemList<Mithril.Children> {
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
   * to the first non-empty naming attribute (`name`, `title`, `label`, `displayName`, `username`,
   * `slug`), else `#id`.
   */
  recordLabel(model: M): string {
    return recordLabel(model);
  }

  /**
   * The name of `count` records of this type, e.g. "Group" for 1 ("Create Group") and "Groups" for
   * more (the search placeholder, the table caption). Defaults to the humanized type for any count;
   * override it with an ICU plural translation.
   */
  label(_count: number): string {
    return humanize(this.type);
  }
}
