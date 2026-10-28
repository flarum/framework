import type Mithril from 'mithril';
import app from '../app';
import Button from '../components/Button';
import type Model from '../Model';
import ItemList from '../utils/ItemList';
import extractText from '../utils/extractText';
import type { BulkActionContext, BulkSummary } from './BulkRunner';
import { loadDescriptor } from './descriptor';
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
 *
 * @experimental
 */
export default class ResourceDefinition<M extends Model = Model> {
  descriptor: ResourceDescriptor | null = null;

  /** Whether a `bulkDelete()` run is in progress, so a second click is ignored. */
  protected bulkDeleting: boolean = false;

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
      // A relationship filter named apart from its field (`group` / `groups`) takes the display hint
      // of a relationship field over the same type.
      const related =
        !field && filter.kind === 'relationship' && filter.related
          ? (this.descriptor!.fields.find((d) => d.relationship?.types.includes(filter.related!)) ?? null)
          : null;

      filters.add(
        filter.key,
        {
          label: field?.label ?? humanize(filter.key),
          input: resolveFilterInput(filter, field?.descriptor ?? related, resolvers),
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
   * column only when this has items. Add `bulkDelete(context)` for the built-in delete.
   *
   * @experimental
   */
  bulkActions(_selection: ResourceSelection, _context: BulkActionContext<M>): ItemList<Mithril.Children> {
    return new ItemList<Mithril.Children>();
  }

  /**
   * A "Delete selected" button for `bulkActions()`. It confirms with the count, leaves out rows whose
   * `canDelete` is false, deletes the others one request per record through `context.runner` (so an
   * extension's replaced `Delete` endpoint runs per record), shows the summary through
   * `context.showResult()` and then refreshes the list.
   *
   * Opt-in: no definition offers it unless it adds it.
   *
   * @experimental
   */
  bulkDelete(context: BulkActionContext<M>): Mithril.Children {
    const count = context.list.selectedModels().length;

    return m(
      Button,
      {
        className: 'Button Button--danger ResourceTable-bulkDelete',
        icon: 'fas fa-trash-alt',
        disabled: !count,
        loading: this.bulkDeleting,
        // The summary reports the deletes; a failed reload has already shown its error.
        onclick: () => this.runBulkDelete(context).catch(() => {}),
      },
      app.translator.trans('core.lib.resource.bulk.delete_button', { count })
    );
  }

  /**
   * The work behind `bulkDelete()`. Resolves to the summary, or null when there was nothing to do or
   * the user cancelled.
   *
   * @experimental
   */
  async runBulkDelete(context: BulkActionContext<M>): Promise<BulkSummary<M> | null> {
    if (this.bulkDeleting) return null;

    const { list } = context;
    const selected = list.selectedModels();
    const deletable = selected.filter((model) => !!model.attribute('canDelete'));
    const skipped = selected.filter((model) => !model.attribute('canDelete'));

    if (!selected.length) return null;

    if (deletable.length && !confirm(extractText(app.translator.trans('core.lib.resource.bulk.delete_confirmation', { count: deletable.length })))) {
      return null;
    }

    this.bulkDeleting = true;
    m.redraw();

    try {
      // The summary reports each failure, so the per-request error alert is suppressed.
      const result = await context.runner.run(deletable, (model) => model.delete({}, { errorHandler: () => {} }));
      const summary: BulkSummary<M> = { ...result, skipped };
      const page = list.currentPage();
      const emptied = result.ok.length >= list.getItems().length;

      context.showResult(summary);
      await list.goto(emptied && page > 1 ? page - 1 : page);

      return summary;
    } finally {
      this.bulkDeleting = false;
      m.redraw();
    }
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

  label(_n: number): string {
    return humanize(this.type);
  }
}
