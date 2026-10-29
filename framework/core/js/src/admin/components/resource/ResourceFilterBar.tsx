import app from '../../app';
import Button from '../../../common/components/Button';
import Dropdown from '../../../common/components/Dropdown';
import LoadingIndicator from '../../../common/components/LoadingIndicator';
import type IGambit from '../../../common/query/IGambit';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import extractText from '../../../common/utils/extractText';
import type Stream from '../../../common/utils/Stream';
import { relatedModel, TEXT_FILTER_TYPES } from '../../../common/resource/inputs/defaults';
import RelationOptions from '../../../common/resource/inputs/RelationOptions';
import type InputFactory from '../../../common/resource/inputs/InputFactory';
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
  /** The definition's `filters()`, built once the descriptor is loaded. */
  filters: ItemList<Filter>;
}

const INLINE_FILTERS = 3;

const anyLabel = () => app.translator.trans('core.lib.resource.filter.any', {}, true);

/**
 * The structured filters of a `ResourceTable`: one dropdown per filter (choices, relation records
 * or the filter's control), "More filters" for the rest, and the active filter chips. Not a
 * component: the table places `items()` into its `viewItems()` and `chips()` into its
 * `subToolbarItems()`, and swaps the bar through `ResourceTable.filterBar()`.
 */
export default class ResourceFilterBar<M extends Model = Model> {
  readonly definition: ResourceDefinition<M>;
  readonly list: ResourceListState<M>;
  readonly filters: ItemList<Filter>;
  readonly inputs: InputFactory;
  /** Text filters typed but not yet committed, keyed by filter. */
  readonly drafts = new Drafts();

  /** Filters the user added from "More filters" or set from the toolbar. */
  protected promoted: Set<string> = new Set();
  /** A filter promoted from "More filters" whose toggle takes focus once drawn. */
  protected pendingFocus: string | null = null;
  /** The record menus of relationship filters, by filter key. */
  protected relations: Record<string, RelationOptions> = {};
  /** Each filter dropdown's element, by filter key. */
  protected doms: Record<string, Element> = {};

  constructor(readonly attrs: ResourceFilterBarAttrs<M>) {
    this.definition = attrs.definition;
    this.list = attrs.list;
    this.filters = attrs.filters;
    this.inputs = attrs.definition.filterInputs();
  }

  /** Stop pending commits and record loads. */
  destroy(): void {
    this.drafts.discard();
    Object.values(this.relations).forEach((options) => options.cancel());
  }

  /**
   * The toolbar items: the first `inlineFilters()` filters (`filter-{key}`, 80 down) and "More
   * filters" (`moreFilters`, 10) for the rest.
   */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const { inline, more } = this.layout();

    inline.forEach(([key, filter], index) => items.add(`filter-${key}`, this.filterView(key, filter), 80 - index));

    if (more.length) items.add('moreFilters', this.moreView(more), 10);

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

  /** How many filters show as toolbar buttons before the rest go under "More filters". */
  inlineFilters(): number {
    return INLINE_FILTERS;
  }

  /**
   * The toolbar filters split into those shown as buttons (the first `inlineFilters()`, the
   * promoted ones and every filter with a value) and the rest.
   */
  layout(): { inline: [string, Filter][]; more: [string, Filter][] } {
    const inline: [string, Filter][] = [];
    const more: [string, Filter][] = [];

    this.toolbarFilters().forEach((entry, index) => {
      const [key] = entry;

      if (index < this.inlineFilters() || this.promoted.has(key) || this.list.getFilter(key) !== '') inline.push(entry);
      else more.push(entry);
    });

    return { inline, more };
  }

  /** "More filters": one item per filter not on the toolbar; picking one adds its button. */
  moreView(filters: [string, Filter][]): Mithril.Children {
    return (
      <Dropdown
        className="ResourceTable-moreFilters"
        buttonClassName="Button"
        icon="fas fa-filter"
        label={app.translator.trans('core.admin.resource.table.more_filters_button')}
        accessibleToggleLabel={app.translator.trans('core.admin.resource.table.more_filters_label', {}, true)}
        helperText={null}
      >
        {filters.map(([key, filter]) => (
          <Button className="ResourceTable-moreFilter" data-filter={key} onclick={() => this.promote(key)}>
            {filter.label}
          </Button>
        ))}
      </Dropdown>
    );
  }

  /** Show a filter from "More filters" as a button; its toggle takes focus once drawn. */
  promote(key: string): void {
    this.promoted.add(key);
    this.pendingFocus = key;
  }

  /** Set a filter, keeping its button inline for the session even when set back to Any. */
  setFilter(key: string, value: string): Promise<void> {
    this.promoted.add(key);

    return this.list.setFilter(key, value);
  }

  /** Remove every structured filter, uncommitted text included. */
  clear(): Promise<void> {
    this.drafts.discard();

    return this.list.clearFilters().catch(() => {});
  }

  /**
   * One filter as a dropdown button labelled "{label}: {value}". Choice filters (yes/no, enum)
   * list their values, relationship filters the related records; other filters (text, an
   * extension's custom control) render their control inside the menu.
   */
  filterView(key: string, filter: Filter): Mithril.Children {
    const value = this.list.getFilter(key);
    const valueLabel = value === '' ? anyLabel() : this.valueLabel(key, filter, value);
    const drawn = (vnode: Mithril.VnodeDOM) => {
      this.doms[key] = vnode.dom;
      this.focusPending(key);
    };

    return (
      <Dropdown
        className={classList('ResourceTable-filter', `ResourceTable-filter--${key}`, value !== '' && 'is-active')}
        buttonClassName="Button"
        label={
          <span>
            <span className="ResourceTable-filterName">{filter.label}:</span> <span className="ResourceTable-filterValue">{valueLabel}</span>
          </span>
        }
        accessibleToggleLabel={app.translator.trans(
          'core.admin.resource.table.filter_toggle_label',
          { label: extractText(filter.label), value: extractText(valueLabel) },
          true
        )}
        helperText={null}
        oncreate={drawn}
        onupdate={drawn}
        onshow={() => {
          this.promoted.add(key);
          this.loadRelation(key, filter);
          // Focus the first text box of the menu (a text filter, a relationship search).
          (this.doms[key]?.querySelector('.Dropdown-menu input') as HTMLElement | null)?.focus();
        }}
      >
        {this.menuItems(key, filter)}
      </Dropdown>
    );
  }

  /** Focus a promoted filter's toggle after the redraw that created it. */
  focusPending(key: string): void {
    if (this.pendingFocus !== key) return;

    const toggle = this.doms[key]?.querySelector('.Dropdown-toggle') as HTMLElement | null;

    if (toggle) {
      this.pendingFocus = null;
      toggle.focus();
    }
  }

  /** The `[value, label]` choices of a choice filter (yes/no, enum), "Any" first; null for others. */
  choices(_key: string, filter: Filter): [string, Mithril.Children][] | null {
    if (typeof filter.input === 'function') return null;

    if (filter.input.type === 'tri-state') {
      return [
        ['', anyLabel()],
        ['1', app.translator.trans('core.lib.resource.filter.yes', {}, true)],
        ['0', app.translator.trans('core.lib.resource.filter.no', {}, true)],
      ];
    }

    if (filter.input.type === 'enum') {
      return [['', anyLabel()], ...(filter.input.choices as [string, string][])];
    }

    return null;
  }

  /** The menu of one filter dropdown. */
  menuItems(key: string, filter: Filter): Mithril.Children[] {
    const choices = this.choices(key, filter);
    const current = this.list.getFilter(key);

    if (choices) {
      return choices.map(([value, label]) => this.choice(key, value, label, value === current));
    }

    const relation = this.relationSpec(filter);

    if (relation) return this.relationMenuItems(key, relation);

    return [
      <div className="ResourceTable-filterControl" onclick={(e: MouseEvent) => e.stopPropagation()}>
        {this.control(key, filter)}
      </div>,
    ];
  }

  /** One choice of a choice filter's menu; picking it sets the filter. */
  choice(key: string, value: string, label: Mithril.Children, active: boolean): Mithril.Children {
    return (
      <Button
        icon={active ? 'fas fa-check' : true}
        active={active}
        itemClassName={active ? 'active' : undefined}
        aria-pressed={active ? 'true' : 'false'}
        data-value={value}
        onclick={() => this.setFilter(key, value)}
      >
        {label}
      </Button>
    );
  }

  /** The relationship spec of a `relation` filter, else null. */
  relationSpec(filter: Filter): { related: string; display: string } | null {
    if (typeof filter.input === 'function' || filter.input.type !== 'relation' || !filter.input.related) return null;

    return { related: filter.input.related, display: filter.input.display ?? 'name' };
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
  relationMenuItems(key: string, spec: { related: string; display: string }): Mithril.Children[] {
    const options = this.relationOptions(key, spec.related);
    const current = this.list.getFilter(key);
    const label = (model: Model) => String(model.attribute(spec.display) ?? model.id());
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

    items.push(this.choice(key, '', anyLabel(), current === ''));

    const selected = current ? relatedModel(spec.related, current) : null;
    const exclude = this.filters.has(key) ? this.filters.get(key).exclude : undefined;
    const records = options.items.filter((model) => model.id() !== current && !exclude?.(model));

    if (selected) items.push(this.choice(key, current, label(selected), true));

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

  /** How a set filter value reads on its button and chip. */
  valueLabel(key: string, filter: Filter, value: string): Mithril.Children {
    const choice = this.choices(key, filter)?.find(([v]) => v === value);

    if (choice) return choice[1];

    const relation = this.relationSpec(filter);

    if (relation) {
      const model = relatedModel(relation.related, value);

      return model ? String(model.attribute(relation.display) ?? value) : value;
    }

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
