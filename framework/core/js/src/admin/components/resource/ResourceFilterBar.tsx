import app from '../../app';
import Button from '../../../common/components/Button';
import Dropdown from '../../../common/components/Dropdown';
import LoadingIndicator from '../../../common/components/LoadingIndicator';
import Separator from '../../../common/components/Separator';
import type IGambit from '../../../common/query/IGambit';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import extractText from '../../../common/utils/extractText';
import type Stream from '../../../common/utils/Stream';
import { relatedModel, TEXT_FILTER_TYPES } from '../../../common/resource/inputs/defaults';
import RelationOptions from '../../../common/resource/inputs/RelationOptions';
import type InputFactory from '../../../common/resource/inputs/InputFactory';
import { rangeLabel } from '../../../common/resource/rangeFilterInput';
import { relatedRecordLabel, splitList } from '../../../common/resource/resolvers';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import type ResourceListState from '../../../common/resource/ResourceListState';
import type { Filter } from '../../../common/resource/types';
import type Mithril from 'mithril';
import Drafts from './Drafts';
import FilterChips from './FilterChips';
import ordered from './ordered';

export interface ResourceFilterBarAttrs<M extends Model = Model> {
  definition: ResourceDefinition<M>;
  list: ResourceListState<M>;
}

const anyLabel = () => app.translator.trans('core.lib.resource.filter.any', {}, true);

/**
 * The structured filters of a `ResourceTable`: one "Filter" button whose panel has a labelled
 * section per filter (choices, relation records or the filter's control), and the active filter
 * chips. Not a component: the table places `items()` into its `viewItems()` and `chips()` under its
 * toolbar, and swaps the bar through `ResourceTable.filterBar()`.
 */
export default class ResourceFilterBar<M extends Model = Model> {
  readonly definition: ResourceDefinition<M>;
  readonly list: ResourceListState<M>;
  readonly inputs: InputFactory;
  /** Text filters typed but not yet committed, keyed by filter. */
  readonly drafts = new Drafts();

  /** The record menus of relationship filters, by filter key. */
  protected relations: Record<string, RelationOptions> = {};

  constructor(readonly attrs: ResourceFilterBarAttrs<M>) {
    this.definition = attrs.definition;
    this.list = attrs.list;
    this.inputs = attrs.definition.filterInputs();
  }

  /** The list's `filters()`: one build per descriptor, shared with the list state. */
  get filters(): ItemList<Filter> {
    return this.list.filters();
  }

  /** Stop pending commits and record loads. */
  destroy(): void {
    this.drafts.discard();
    Object.values(this.relations).forEach((options) => options.cancel());
  }

  /** The "Filter" button and its panel (`filters`, 80), while the toolbar offers a filter. */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const filters = this.toolbarFilters();

    if (filters.length) items.add('filters', this.panelView(filters), 80);

    return items;
  }

  /** The active filter chips and "Clear all"; null while no filter is set. */
  chips(): Mithril.Children {
    return this.activeCount() ? <FilterChips bar={this} /> : null;
  }

  /** How many described filters have a value. */
  activeCount(): number {
    return ordered(this.filters).filter(([key]) => this.list.getFilter(key) !== '').length;
  }

  /** The type's enabled gambits; empty when it has none or no search. */
  gambits(): IGambit[] {
    if (!this.definition.descriptor?.search) return [];

    return (app.search?.gambits?.for(this.definition.type) ?? []).filter((gambit) => gambit.enabled());
  }

  /**
   * The filters the toolbar offers, by priority. A filter that a gambit of the same key covers is
   * left to the search box unless its `hideWhenSearchable` is false.
   */
  toolbarFilters(): [string, Filter][] {
    const gambitKeys = new Set(this.gambits().map((gambit) => gambit.filterKey()));

    return ordered(this.filters).filter(([key, filter]) => !(gambitKeys.has(key) && filter.hideWhenSearchable !== false));
  }

  /**
   * The "Filter" button, with a badge counting the set filters, and its panel: one section per
   * filter, a heading with its label over its items (`menuItems()`). Flat menu items, so core's
   * phone sheet styling applies to them.
   */
  panelView(filters: [string, Filter][]): Mithril.Children {
    const count = filters.filter(([key]) => this.list.getFilter(key) !== '').length;
    const items: Mithril.Children[] = [];

    filters.forEach(([key, filter], index) => {
      if (index) items.push(<Separator />);

      const heading = (
        <div className="ResourceTable-filterGroupLabel" onclick={(e: MouseEvent) => e.stopPropagation()}>
          {filter.label}
        </div>
      );

      items.push(
        Object.assign(heading, { itemClassName: classList('Dropdown-header', 'ResourceTable-filterGroup', `ResourceTable-filterGroup--${key}`) })
      );

      this.menuItems(key, filter).forEach((item) => items.push(this.inGroup(item, key)));
    });

    return (
      <Dropdown
        className="ResourceTable-filterPanel"
        buttonClassName={classList('Button', count && 'is-active')}
        icon="fas fa-filter"
        label={
          <span className="ResourceTable-filterButtonLabel">
            {app.translator.trans('core.admin.resource.table.filter_button')}
            {count > 0 && (
              <span className="ResourceTable-filterCount" aria-hidden="true">
                {count}
              </span>
            )}
          </span>
        }
        accessibleToggleLabel={app.translator.trans('core.admin.resource.table.filter_toggle_label', { count }, true)}
        helperText={null}
        onshow={() => filters.forEach(([key, filter]) => this.loadRelation(key, filter))}
      >
        {items}
      </Dropdown>
    );
  }

  /** Mark a panel item's `<li>` as belonging to one filter's section. */
  inGroup(item: Mithril.Children, key: string): Mithril.Children {
    if (item && typeof item === 'object' && 'tag' in item) {
      const vnode = item as Mithril.Vnode<{ itemClassName?: string }> & { itemClassName?: string };
      const groupClass = classList('ResourceTable-filterGroupItem', `ResourceTable-filterGroupItem--${key}`);

      // listItems reads `attrs.itemClassName` first, so join an existing one rather than shadow it.
      if (vnode.attrs?.itemClassName) vnode.attrs.itemClassName = classList(vnode.attrs.itemClassName, groupClass);
      else vnode.itemClassName = groupClass;
    }

    return item;
  }

  /** Set a filter. */
  setFilter(key: string, value: string): Promise<void> {
    return this.list.setFilter(key, value);
  }

  /** Remove every structured filter, uncommitted text included. */
  clear(): Promise<void> {
    this.drafts.discard();

    return this.list.clearFilters().catch(() => {});
  }

  /**
   * The `[value, label]` choices of a choice filter (yes/no, enum), "Any" first unless the filter
   * is required; null for others.
   */
  choices(_key: string, filter: Filter): [string, Mithril.Children][] | null {
    if (typeof filter.input === 'function') return null;

    const any: [string, Mithril.Children][] = filter.required ? [] : [['', anyLabel()]];

    if (filter.input.type === 'tri-state') {
      return [
        ...any,
        ['1', app.translator.trans('core.lib.resource.filter.yes', {}, true)],
        ['0', app.translator.trans('core.lib.resource.filter.no', {}, true)],
      ];
    }

    if (filter.input.type === 'enum') {
      return [...any, ...(filter.input.choices as [string, string][])];
    }

    return null;
  }

  /** Whether a filter's value is a comma list (`multiple: true` on its input). */
  isMultiple(filter: Filter): boolean {
    return typeof filter.input !== 'function' && !!filter.input.multiple;
  }

  /** The items of a filter value: each of a comma list, else the value itself. */
  values(filter: Filter, value: string): string[] {
    if (value === '') return [];

    return this.isMultiple(filter) ? splitList(value) : [value];
  }

  /**
   * The value after picking `value` from a filter's menu: a multiple filter toggles it in its comma
   * list ("Any" clears the list), others take it.
   */
  picked(filter: Filter, current: string, value: string): string {
    if (!this.isMultiple(filter) || value === '') return value;

    const values = this.values(filter, current);

    return (values.includes(value) ? values.filter((v) => v !== value) : [...values, value]).join(',');
  }

  /** The value without one item of a multiple filter's comma list; `''` for other filters. */
  without(filter: Filter, current: string, value: string): string {
    return this.isMultiple(filter)
      ? this.values(filter, current)
          .filter((v) => v !== value)
          .join(',')
      : '';
  }

  /** The menu of one filter dropdown. */
  menuItems(key: string, filter: Filter): Mithril.Children[] {
    const choices = this.choices(key, filter);
    const current = this.list.getFilter(key);
    const values = this.values(filter, current);

    if (choices) {
      return choices.map(([value, label]) => this.choice(key, value, label, value === '' ? current === '' : values.includes(value)));
    }

    const relation = this.relationSpec(filter);

    if (relation) return this.relationMenuItems(key, relation);

    return [
      <div className="ResourceTable-filterControl" onclick={(e: MouseEvent) => e.stopPropagation()}>
        {this.control(key, filter)}
      </div>,
    ];
  }

  /**
   * One choice of a choice filter's menu; picking it sets the filter. A multiple filter toggles the
   * choice and keeps its menu open.
   */
  choice(key: string, value: string, label: Mithril.Children, active: boolean): Mithril.Children {
    const filter = this.filters.has(key) ? this.filters.get(key) : null;
    const multiple = !!filter && this.isMultiple(filter);

    return (
      <Button
        icon={active ? 'fas fa-check' : true}
        active={active}
        itemClassName={active ? 'active' : undefined}
        aria-pressed={active ? 'true' : 'false'}
        data-value={value}
        onclick={(e: MouseEvent) => {
          if (multiple && value !== '') e.stopPropagation();

          this.setFilter(key, filter ? this.picked(filter, this.list.getFilter(key), value) : value);
        }}
      >
        {label}
      </Button>
    );
  }

  /**
   * The relationship spec of a `relation` filter, else null. Without a `display` attribute, records
   * are labelled by `relatedRecordLabel()`.
   */
  relationSpec(filter: Filter): { related: string; display?: string } | null {
    if (typeof filter.input === 'function' || filter.input.type !== 'relation' || !filter.input.related) return null;

    return { related: filter.input.related, display: filter.input.display };
  }

  /** The loaded records of one relationship filter's menu. */
  relationOptions(key: string, related: string): RelationOptions {
    return (this.relations[key] ??= new RelationOptions(related));
  }

  /** Load a relationship filter's first records, once. */
  loadRelation(key: string, filter: Filter): void {
    const relation = this.relationSpec(filter);

    if (relation) this.relationOptions(key, relation.related).ensureLoaded();
  }

  /**
   * A search box when the related type is searchable, "Any", the records (the current one first)
   * and "Load more".
   */
  relationMenuItems(key: string, spec: { related: string; display?: string }): Mithril.Children[] {
    const options = this.relationOptions(key, spec.related);
    const current = this.list.getFilter(key);
    const filter = this.filters.has(key) ? this.filters.get(key) : null;
    const currentIds = filter ? this.values(filter, current) : current ? [current] : [];
    const label = (model: Model) => relatedRecordLabel(model, spec.display);
    const items: Mithril.Children[] = [];

    if (options.searchable()) {
      items.push(
        <div className="ResourceTable-filterSearch" onclick={(e: MouseEvent) => e.stopPropagation()}>
          <input
            className="FormControl"
            type="search"
            value={options.query}
            placeholder={app.translator.trans('core.lib.search.placeholder', {}, true)}
            aria-label={app.translator.trans('core.lib.search.placeholder', {}, true)}
            oninput={(e: InputEvent) => options.search((e.target as HTMLInputElement).value)}
            onkeydown={(e: KeyboardEvent) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                options.cancel();
                options.loadItems();
              }
            }}
          />
        </div>
      );
    }

    if (!filter?.required) items.push(this.choice(key, '', anyLabel(), current === ''));

    const exclude = filter?.exclude;
    const records = options.items.filter((model) => !currentIds.includes(model.id()!) && !exclude?.(model));

    currentIds.forEach((id) => {
      const selected = relatedModel(spec.related, id);

      if (selected) items.push(this.choice(key, id, label(selected), true));
    });

    records.forEach((model) => items.push(this.choice(key, model.id()!, label(model), false)));

    if (options.failed) {
      items.push(
        <div className="ResourceTable-filterError" role="alert" onclick={(e: MouseEvent) => e.stopPropagation()}>
          <span>{app.translator.trans('core.lib.resource.relation.load_error')}</span>
          {!options.isLoading && (
            <Button
              className="Button Button--link ResourceTable-filterRetry"
              onclick={(e: MouseEvent) => {
                e.stopPropagation();
                options.retry();
              }}
            >
              {app.translator.trans('core.lib.resource.relation.retry_button')}
            </Button>
          )}
        </div>
      );
    } else if (options.isLoading) {
      items.push(<LoadingIndicator display="block" size="small" />);
    } else if (options.hasMore) {
      items.push(
        <Button
          className="ResourceTable-filterMore"
          icon="fas fa-angle-down"
          onclick={(e: MouseEvent) => {
            e.stopPropagation();
            options.loadMore();
          }}
        >
          {app.translator.trans('core.lib.resource.relation.load_more')}
        </Button>
      );
    }

    return items;
  }

  /** One filter's control from the filter input factory (or the filter's own function). */
  control(key: string, filter: Filter): Mithril.Children {
    const stream = this.stream(key, filter);

    return typeof filter.input === 'function'
      ? filter.input({ stream })
      : this.inputs.render(filter.input, { name: key, stream, label: filter.label });
  }

  /**
   * How a set filter value reads on its button and chip: a multiple filter's items joined by
   * commas, a range as "from – to".
   */
  valueLabel(key: string, filter: Filter, value: string): Mithril.Children {
    if (this.isMultiple(filter)) {
      return this.values(filter, value)
        .map((item) => extractText(this.itemLabel(key, filter, item)))
        .join(', ');
    }

    return this.itemLabel(key, filter, value);
  }

  /** How one value (one item of a multiple filter) reads: its choice, record or range label. */
  itemLabel(key: string, filter: Filter, value: string): Mithril.Children {
    const choice = this.choices(key, filter)?.find(([v]) => v === value);

    if (choice) return choice[1];

    const relation = this.relationSpec(filter);

    if (relation) {
      const model = relatedModel(relation.related, value);

      return model ? relatedRecordLabel(model, relation.display) : value;
    }

    if (typeof filter.input !== 'function' && filter.input.type === 'range') return rangeLabel(value);

    return value;
  }

  /**
   * A stream over one list filter. Text-like filters (or specs with `debounce: true`) keep a draft
   * and commit debounced.
   */
  stream(key: string, filter: Filter): Stream<string> {
    const debounced = typeof filter.input !== 'function' && (filter.input.debounce ?? TEXT_FILTER_TYPES.includes(filter.input.type));

    const stream = (...args: [string] | []) => {
      if (args.length) {
        const value = args[0] ?? '';

        if (debounced) this.drafts.set(key, value, (v) => this.setFilter(key, v));
        else this.setFilter(key, value);

        return value;
      }

      return debounced ? this.drafts.get(key, () => this.list.getFilter(key)) : this.list.getFilter(key);
    };

    return stream as unknown as Stream<string>;
  }

  /**
   * The filter keys the gambits typed into the search send, sign included (`-group` for
   * `-group:x`); on the server they win over a structured filter of the same key.
   */
  searchFilterKeys(): Set<string> {
    const keys = new Set<string>();
    const q = this.list.getQuery();

    if (q && this.gambits().length) {
      app.search.gambits.match(this.definition.type, q, (gambit, matches, negate) => {
        Object.keys(gambit.toFilter(matches, negate)).forEach((key) => keys.add(key));
      });
    }

    return keys;
  }

  /**
   * How the search relates to one structured filter value: `overridden` when a gambit sends the
   * same filter key, `conflict` when it sends the same filter with the opposite sign, else null.
   */
  searchRelation(key: string, value: string, searchKeys: Set<string> = this.searchFilterKeys()): 'overridden' | 'conflict' | null {
    const [sent] = this.list.filterParam(key, value);

    if (searchKeys.has(sent)) return 'overridden';

    const opposite = sent.startsWith('-') ? sent.slice(1) : `-${sent}`;

    return searchKeys.has(opposite) ? 'conflict' : null;
  }
}
