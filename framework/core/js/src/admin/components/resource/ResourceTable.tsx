import app from '../../app';
import Component, { type ComponentAttrs } from '../../../common/Component';
import Alert from '../../../common/components/Alert';
import Button from '../../../common/components/Button';
import Dropdown from '../../../common/components/Dropdown';
import GambitsAutocompleteDropdown from '../../../common/components/GambitsAutocompleteDropdown';
import Icon from '../../../common/components/Icon';
import Input from '../../../common/components/Input';
import LoadingIndicator from '../../../common/components/LoadingIndicator';
import Pagination from '../../../common/components/Pagination';
import Pill from '../../../common/components/Pill';
import Separator from '../../../common/components/Separator';
import type IGambit from '../../../common/query/IGambit';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import extractText from '../../../common/utils/extractText';
import type Stream from '../../../common/utils/Stream';
import { SequentialBulkRunner, type BulkActionContext, type BulkRunner, type BulkSummary } from '../../../common/resource/BulkRunner';
import ColumnPreferences from '../../../common/resource/ColumnPreferences';
import { exportCsv } from '../../../common/resource/csv';
import { renderColumn } from '../../../common/resource/field';
import { relatedModel, TEXT_FILTER_TYPES } from '../../../common/resource/inputs/defaults';
import RelationOptions from '../../../common/resource/inputs/RelationOptions';
import type InputFactory from '../../../common/resource/inputs/InputFactory';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import ResourceListState from '../../../common/resource/ResourceListState';
import type { Field, Filter } from '../../../common/resource/types';
import type Mithril from 'mithril';
import BulkResultModal from './BulkResultModal';
import ColumnsDropdown from './ColumnsDropdown';
import floatMenus from './floatMenus';
import ResourceDeleteModal, { type ResourceDeleteModalAttrs } from './ResourceDeleteModal';
import ResourceFormModal from './ResourceFormModal';
import type ModalManagerState from '../../../common/states/ModalManagerState';

/** What `app.modal.show()` takes: a modal class or a loader of one. */
type ModalClass = Parameters<ModalManagerState['show']>[0];

/**
 * One table column. `{name, content}` is the shape `UserListPage.columns()` has always used; the rest
 * is optional.
 */
export interface ColumnData<M extends Model = Model> {
  /** The header cell's content. */
  name: Mithril.Children;
  /** A cell's content for one record. */
  content: (model: M) => Mithril.Children;
  /** The descriptor sort this column's header toggles; the header is plain unless the type has it. */
  sort?: string;
  /** Added to the header cell and every body cell of the column. */
  className?: string;
  /** Extra attributes for one record's cell, e.g. `data-*` or `aria-*`. */
  attrs?: (model: M) => Record<string, unknown>;
  /** Listed in the "Columns" dropdown, so the user can hide it. Default true. */
  toggleable?: boolean;
  /** Hidden until the user shows it. A stored choice for this column wins; other columns' choices don't affect it. */
  hidden?: boolean;
  /**
   * The column's CSV value. Without it, the CSV takes the raw attribute when the column key is a
   * descriptor field, else leaves the column out; rendered content is never exported.
   */
  csv?: (model: M) => string | number | null;
}

export interface ResourceTableAttrs<M extends Model = Model> extends ComponentAttrs {
  definition: ResourceDefinition<M>;
  /** An existing list state; one is created (and owned) otherwise. */
  state?: ResourceListState<M>;
  /** Sync sort, search, filters and page with the URL. Default true. */
  syncUrl?: boolean;
  pageSize?: number;
  /** The input factory for the create/edit form; default `definition.formInputs()`. */
  formInputs?: InputFactory;
  /** The input factory for the filter bar; default `definition.filterInputs()`. */
  filterInputs?: InputFactory;
  /** Replaces the columns inferred from the descriptor; the keys become `data-column-name`. */
  columns?: ItemList<ColumnData<M>>;
  /** Added to the root element's class. */
  className?: string;
  /** Added to the table container's class (the element around the `<table>` and its loading overlay). */
  tableClassName?: string;
  /** Replaces the default create modal (`ResourceFormModal`). */
  onCreate?: () => void;
  /** Replaces the default edit modal (`ResourceFormModal`). */
  onEdit?: (model: M) => void;
  /** Extra attributes for one record's row, e.g. `data-user-id`. */
  rowAttrs?: (model: M) => Record<string, unknown>;
  /**
   * Append the actions column (`rowActions()`: edit, delete, the definition's actions). Default
   * true. Pass false when `columns` brings its own actions column, so there is exactly one.
   */
  actionsColumn?: boolean;
  /**
   * How many filters show as toolbar buttons before the rest go under "More filters". Default
   * `ResourceTable.INLINE_FILTERS` (3).
   */
  inlineFilters?: number;
  /**
   * Collapse the filters into one "Filters" dropdown. Default: at phone width.
   */
  compactFilters?: boolean;
  /**
   * Which filters the toolbar leaves out when a gambit of the same key covers them, so they are
   * typed into the search box as `key:value` instead: `'all'` (default) every covered filter,
   * `'text'` only text-like filters, `'none'` none. A filter's own `hideWhenSearchable` wins.
   */
  searchCoveredFilters?: SearchCoveredFilters;
}

export type SearchCoveredFilters = 'text' | 'all' | 'none';

const anyLabel = () => app.translator.trans('core.lib.resource.filter.any', {}, true);
/** Core's `@phone` breakpoint. */
const PHONE_QUERY = '(max-width: 767.98px)';

/** Item-list entries in render order: priority descending, insertion order for ties. */
function ordered<T>(list: ItemList<T>): [string, T][] {
  return Object.entries(list.toObject())
    .map(([key, item], index) => ({ key, content: item.content as T, priority: item.priority, index }))
    .sort((a, b) => b.priority - a.priority || a.index - b.index)
    .map(({ key, content }) => [key, content]);
}

/**
 * The admin table for one resource type: columns from the definition's fields (or the `columns`
 * attr), header and dropdown sorting over the descriptor's sorts, debounced search, one control per
 * described filter, numbered (core `Pagination`) or prev/next pagination, and create/edit/delete
 * gated by `canCreate()` and the rows' `canEdit`/`canDelete` attributes. Every region is an
 * `ItemList` for extensions.
 */
export default class ResourceTable<M extends Model = Model, Attrs extends ResourceTableAttrs<M> = ResourceTableAttrs<M>> extends Component<Attrs> {
  static SEARCH_DELAY = 300;
  static INLINE_FILTERS = 3;

  list!: ResourceListState<M>;
  filterInputs!: InputFactory;
  /** Null until the descriptor is loaded. */
  fields: ItemList<Field<M>> | null = null;
  /** Built once the descriptor is loaded; `definition.filters()` rebuilds every field. */
  filters: ItemList<Filter> | null = null;
  loadError: boolean = false;
  /** The running export, if any. */
  exporting: { controller: AbortController; rows: number; total: number | null } | null = null;
  /** Removes the row menus' fixed positioning listeners (`floatMenus`). */
  protected unfloatMenus: (() => void) | null = null;

  /** The user's explicit column choices (key to visible); read from `ColumnPreferences` on first use. */
  protected columnChoices: Map<string, boolean> | null = null;

  /** Uncommitted text in the search and text filters, so typing isn't reverted by the debounce. */
  protected drafts: Record<string, string> = {};
  protected timers: Record<string, ReturnType<typeof setTimeout>> = {};

  /** Filters the user added from "More filters". */
  protected promotedFilters: Set<string> = new Set();
  /** A filter promoted from "More filters" whose toggle takes focus once drawn. */
  protected pendingFocus: string | null = null;
  /** The record menus of relationship filters, by filter key. */
  protected relations: Record<string, RelationOptions> = {};
  /** Whether the viewport is phone-sized. */
  protected phone: boolean = false;
  protected phoneQuery: MediaQueryList | null = null;
  protected onPhoneChange = (e: MediaQueryListEvent) => {
    this.phone = e.matches;
    m.redraw();
  };

  /** `bulkActionItems()` for the view pass in progress, so the toolbar and the table share one build. */
  protected viewBulkActions: ItemList<Mithril.Children> | null = null;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    const { definition, state, syncUrl, pageSize } = this.attrs;

    this.list = state ?? new ResourceListState<M>(definition, { syncUrl, pageSize });
    this.filterInputs = this.attrs.filterInputs ?? definition.filterInputs();

    if (typeof window.matchMedia === 'function') {
      this.phoneQuery = window.matchMedia(PHONE_QUERY);
      this.phone = this.phoneQuery.matches;
      this.phoneQuery.addEventListener?.('change', this.onPhoneChange);
    }

    definition
      .load()
      .then(() => {
        this.fields = definition.fields();
        this.filters = definition.filters();
        m.redraw();

        return this.list.load();
      })
      .catch((error) => {
        // A rejected descriptor request, or a bug in a resolver or an extend() callback.
        console.error(`ResourceTable(${definition.type}):`, error);
        this.loadError = true;
      })
      .finally(() => m.redraw());
  }

  onremove(vnode: Mithril.VnodeDOM<Attrs, this>) {
    super.onremove(vnode);

    Object.values(this.timers).forEach(clearTimeout);
    Object.values(this.relations).forEach((options) => options.cancel());
    this.phoneQuery?.removeEventListener?.('change', this.onPhoneChange);
    this.exporting?.controller.abort();
  }

  view(): Mithril.Children {
    const className = classList('ResourceTable', `ResourceTable--${this.attrs.definition.type}`, this.attrs.className);

    if (this.loadError) {
      return (
        <div className={classList(className, 'ResourceTable-error')}>
          <Alert type="error" dismissible={false}>
            {app.translator.trans('core.admin.resource.table.load_error')}
          </Alert>
        </div>
      );
    }

    if (!this.fields) {
      return (
        <div className={className}>
          <LoadingIndicator size="large" />
        </div>
      );
    }

    this.viewBulkActions = this.bulkActionItems();

    try {
      return <div className={className}>{this.items().toArray()}</div>;
    } finally {
      this.viewBulkActions = null;
    }
  }

  /** The bulk actions of the current view pass, else a fresh `bulkActionItems()`. */
  protected currentBulkActions(): ItemList<Mithril.Children> {
    return this.viewBulkActions ?? this.bulkActionItems();
  }

  /**
   * The table's regions: `toolbar`, `subToolbar` (active filter chips, "Columns", "Export CSV"),
   * `table`, `pagination`.
   */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('toolbar', <div className="ResourceTable-toolbar">{this.toolbarItems().toArray()}</div>, 100);

    const sub = this.subToolbarItems();

    if (!sub.isEmpty()) {
      items.add('subToolbar', <div className="ResourceTable-subToolbar">{sub.toArray()}</div>, 75);
    }

    items.add('table', this.tableView(), 50);
    items.add('pagination', this.paginationView(), 0);

    return items;
  }

  // ---------------------------------------------------------------------------------------------
  // Toolbar

  /**
   * `view` (search, sort, filters), `actions` (the total, then `actionItems()`) and, when the
   * definition has bulk actions, `bulk`.
   */
  toolbarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('view', <div className="ResourceTable-toolbarView">{this.viewItems().toArray()}</div>, 100);
    items.add(
      'actions',
      <div className="ResourceTable-toolbarActions">
        {this.countView(this.list.hasTotal() ? app.translator.trans('core.admin.resource.table.total', { count: this.list.totalItems }) : null)}
        {this.actionItems().toArray()}
      </div>,
      50
    );

    const bulk = this.currentBulkActions();

    if (!bulk.isEmpty()) {
      items.add('bulk', this.bulkView(bulk), 25);
    }

    return items;
  }

  /**
   * The count slot: a polite live region, so assistive technology hears the new total after a
   * search or filter change.
   */
  countView(content: Mithril.Children): Mithril.Children {
    return (
      <div className="ResourceTable-count" role="status" aria-live="polite">
        {content && <div className="ResourceTable-total">{content}</div>}
      </div>
    );
  }

  /** The bulk toolbar: the selected count, then the bulk actions. */
  bulkView(actions: ItemList<Mithril.Children>): Mithril.Children {
    const count = this.list.selectedModels().length;

    return (
      <div className="ResourceTable-bulk" role="group" aria-label={app.translator.trans('core.admin.resource.table.bulk_label', {}, true)}>
        <span className="ResourceTable-selectedCount" aria-live="polite">
          {app.translator.trans('core.admin.resource.table.selected', { count })}
        </span>
        {actions.toArray()}
      </div>
    );
  }

  /**
   * The definition's bulk actions for the current selection. While empty, the table has no checkbox
   * column.
   */
  bulkActionItems(): ItemList<Mithril.Children> {
    return this.attrs.definition.bulkActions(this.list.selection, this.bulkContext());
  }

  /**
   * What the definition's bulk actions run with: this table's list, `bulkRunner()`, and
   * `BulkResultModal` for the outcome.
   */
  bulkContext(): BulkActionContext<M> {
    return {
      list: this.list,
      runner: this.bulkRunner(),
      showResult: (summary: BulkSummary<M>) => app.modal.show(BulkResultModal, { summary, definition: this.attrs.definition }),
    };
  }

  /**
   * The runner bulk actions use: one request per record, 3 at a time.
   */
  bulkRunner(): BulkRunner {
    return new SequentialBulkRunner();
  }

  /**
   * Search (`search`, when the type is searchable), sort, then the filters: the first
   * `inlineFilters` (`filter-{key}`) and "More filters" (`moreFilters`) for the rest, or one
   * "Filters" dropdown (`filters`) in compact mode.
   */
  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.isSearchable()) {
      items.add('search', this.searchView(), 100);
    }

    if (this.list.sorts().length) {
      items.add('sort', this.sortDropdown(), 90);
    }

    if (this.isCompact()) {
      const filters = this.toolbarFilters();

      if (filters.length) items.add('filters', this.compactFiltersView(filters), 80);

      return items;
    }

    const { inline, more } = this.filterLayout();

    inline.forEach(([key, filter], index) => {
      items.add(`filter-${key}`, this.filterView(key, filter), 80 - index);
    });

    if (more.length) {
      items.add('moreFilters', this.moreFiltersView(more), 10);
    }

    return items;
  }

  /**
   * The row under the toolbar: the active filter chips and "Clear all" (`filters`), then `display`
   * ("Columns" and "Export CSV").
   */
  subToolbarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const chips = this.chipItems();

    if (!chips.isEmpty()) {
      items.add(
        'filters',
        <div className="ResourceTable-chips" role="group" aria-label={app.translator.trans('core.admin.resource.table.filters_label', {}, true)}>
          <div className="Pill-list">{chips.toArray()}</div>
          <Button className="Button Button--link ResourceTable-clearFilters" onclick={() => this.clearFilters()}>
            {app.translator.trans('core.admin.resource.table.clear_filters_button')}
          </Button>
        </div>,
        100
      );
    }

    const display = this.displayItems();

    if (!display.isEmpty()) {
      items.add('display', <div className="ResourceTable-display">{display.toArray()}</div>, 50);
    }

    return items;
  }

  /**
   * The "Columns" dropdown (when a column is toggleable) and the CSV export (when `definition.canExport()`).
   */
  displayItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const toggleable = ordered(this.columns()).filter(([, column]) => column.toggleable !== false);

    if (toggleable.length) {
      items.add(
        'columns',
        <ColumnsDropdown
          columns={toggleable.map(([key, column]) => ({ key, name: column.name, visible: this.isColumnVisible(key, column) }))}
          onchange={(key: string, visible: boolean) => this.toggleColumn(key, visible)}
        />,
        20
      );
    }

    if (this.attrs.definition.canExport()) {
      items.add('export', this.exportButton(), 10);
    }

    return items;
  }

  /**
   * Refresh, create (when `canCreate()`), then the definition's toolbar items.
   */
  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'refresh',
      <Button
        className="Button Button--icon ResourceTable-refresh"
        icon="fas fa-sync"
        title={app.translator.trans('core.admin.resource.table.refresh_button', {}, true)}
        aria-label={app.translator.trans('core.admin.resource.table.refresh_button', {}, true)}
        disabled={this.list.isLoading()}
        onclick={() => this.reload()}
      />,
      100
    );

    if (this.attrs.definition.canCreate()) {
      items.add(
        'create',
        <Button className="Button Button--primary ResourceTable-create" icon="fas fa-plus" onclick={() => this.create()}>
          {app.translator.trans('core.admin.resource.table.create_button')}
        </Button>,
        90
      );
    }

    return items.merge(this.attrs.definition.toolbar(this.list));
  }

  /** Whether the descriptor offers `filter[q]`. */
  isSearchable(): boolean {
    return !!this.attrs.definition.descriptor?.search;
  }

  /** The search box: core `Input`, inside the gambit autocomplete when the type has gambits. */
  searchView(): Mithril.Children {
    const type = this.attrs.definition.type;
    const id = `ResourceTable-search-${type}`;
    const value = this.draft('q', () => this.list.getQuery());
    const onchange = (v: string) => this.debounce('q', v, (q) => this.list.setQuery(q));

    const input = (
      <Input
        className="ResourceTable-searchInput"
        type="search"
        prefixIcon="fas fa-search"
        clearable={true}
        loading={'q' in this.drafts || this.list.isLoading()}
        value={value}
        placeholder={app.translator.trans('core.admin.resource.table.search_placeholder', { label: this.attrs.definition.label(2) }, true)}
        ariaLabel={app.translator.trans('core.admin.resource.table.search_label', {}, true)}
        inputAttrs={{ className: 'ResourceTable-search', id }}
        onchange={onchange}
      />
    );

    return (
      <div className="ResourceTable-searchGroup">
        <label className="visually-hidden" for={id}>
          {app.translator.trans('core.admin.resource.table.search_label')}
        </label>
        {this.gambits().length ? (
          <GambitsAutocompleteDropdown resource={type} query={value} onchange={onchange}>
            {input}
          </GambitsAutocompleteDropdown>
        ) : (
          input
        )}
      </div>
    );
  }

  /** The type's enabled gambits; empty when it has none. */
  gambits(): IGambit[] {
    return (app.search?.gambits?.for(this.attrs.definition.type) ?? []).filter((gambit) => gambit.enabled());
  }

  /**
   * The filter keys the gambits typed into the search send, sign included (`-group` for
   * `-group:x`); on the server they win over a structured filter of the same key.
   */
  searchFilterKeys(): Set<string> {
    const keys = new Set<string>();
    const q = this.list.getQuery();

    if (q && this.gambits().length) {
      app.search.gambits.match(this.attrs.definition.type, q, (gambit, matches, negate) => {
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

  /**
   * The filters the toolbar offers, by priority, without those the search box covers
   * (`isCoveredBySearch()`).
   */
  toolbarFilters(): [string, Filter][] {
    const gambitKeys = this.isSearchable() ? new Set(this.gambits().map((gambit) => gambit.filterKey())) : new Set<string>();

    return ordered(this.filters ?? new ItemList<Filter>()).filter(([key, filter]) => !(gambitKeys.has(key) && this.isCoveredBySearch(key, filter)));
  }

  /**
   * Whether a filter that a gambit of the same key covers is left to the search box: the filter's
   * `hideWhenSearchable`, else the `searchCoveredFilters` policy.
   */
  isCoveredBySearch(key: string, filter: Filter): boolean {
    if (filter.hideWhenSearchable !== undefined) return filter.hideWhenSearchable;

    const policy = this.attrs.searchCoveredFilters ?? 'all';

    return policy === 'all' || (policy === 'text' && this.isTextFilter(filter));
  }

  isTextFilter(filter: Filter): boolean {
    return typeof filter.input !== 'function' && TEXT_FILTER_TYPES.includes(filter.input.type);
  }

  /**
   * The toolbar filters split into those shown as buttons (the first `inlineFilters`, the ones the
   * user picked from "More filters" and every filter with a value) and the rest.
   */
  filterLayout(): { inline: [string, Filter][]; more: [string, Filter][] } {
    const limit = this.attrs.inlineFilters ?? ResourceTable.INLINE_FILTERS;
    const inline: [string, Filter][] = [];
    const more: [string, Filter][] = [];

    this.toolbarFilters().forEach((entry, index) => {
      const [key] = entry;

      if (index < limit || this.promotedFilters.has(key) || this.list.getFilter(key) !== '') inline.push(entry);
      else more.push(entry);
    });

    return { inline, more };
  }

  /** "More filters": one item per filter not on the toolbar; picking one adds its button. */
  moreFiltersView(filters: [string, Filter][]): Mithril.Children {
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
          <Button className="ResourceTable-moreFilter" data-filter={key} onclick={() => this.promoteFilter(key)}>
            {filter.label}
          </Button>
        ))}
      </Dropdown>
    );
  }

  /** Show a filter from "More filters" as a button; its toggle takes focus once drawn. */
  promoteFilter(key: string): void {
    this.promotedFilters.add(key);
    this.pendingFocus = key;
  }

  /** Focus a promoted filter's toggle after the redraw that created it. */
  protected focusPending(key: string, dom: Element | null): void {
    if (this.pendingFocus !== key) return;

    const toggle = dom?.querySelector('.Dropdown-toggle') as HTMLElement | null;

    if (toggle) {
      this.pendingFocus = null;
      toggle.focus();
    }
  }

  /** Set a filter from the toolbar, keeping its button inline for the session even when set back to Any. */
  protected setToolbarFilter(key: string, value: string): Promise<void> {
    this.promotedFilters.add(key);

    return this.list.setFilter(key, value);
  }

  /** Whether the filters collapse into one "Filters" dropdown: the `compactFilters` attr, else phone width. */
  isCompact(): boolean {
    return this.attrs.compactFilters ?? this.phone;
  }

  /**
   * The compact "Filters (n)" dropdown: one section per filter, a heading with its label over the
   * same items its desktop dropdown lists (`filterMenuItems`). Flat menu items, so core's phone
   * sheet styling applies to them.
   */
  compactFiltersView(filters: [string, Filter][]): Mithril.Children {
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

      this.filterMenuItems(key, filter).forEach((item) => items.push(this.inFilterGroup(item, key)));
    });

    return (
      <Dropdown
        className="ResourceTable-filtersMenu"
        buttonClassName={classList('Button', count && 'is-active')}
        icon="fas fa-filter"
        label={app.translator.trans('core.admin.resource.table.filters_button', { count })}
        accessibleToggleLabel={extractText(app.translator.trans('core.admin.resource.table.filters_button', { count }))}
        helperText={null}
        onshow={() =>
          filters.forEach(([key, filter]) => {
            const relation = this.relationSpec(filter);
            if (relation) this.relationOptions(key, relation.related).ensureLoaded();
          })
        }
      >
        {items}
      </Dropdown>
    );
  }

  /** Mark a menu item's `<li>` as belonging to one filter's section of the compact menu. */
  protected inFilterGroup(item: Mithril.Children, key: string): Mithril.Children {
    if (item && typeof item === 'object' && 'tag' in item) {
      const vnode = item as Mithril.Vnode<{ itemClassName?: string }> & { itemClassName?: string };
      const groupClass = classList('ResourceTable-filterGroupItem', `ResourceTable-filterGroupItem--${key}`);

      // listItems reads `attrs.itemClassName` first, so join an existing one rather than shadow it.
      if (vnode.attrs?.itemClassName) vnode.attrs.itemClassName = classList(vnode.attrs.itemClassName, groupClass);
      else vnode.itemClassName = groupClass;
    }

    return item;
  }

  /** The sort dropdown: the default order, then each descriptor sort ascending and descending. */
  sortDropdown(): Mithril.Children {
    const current = this.list.getSort();
    const options: [string, Mithril.Children][] = [['', app.translator.trans('core.admin.resource.table.sort_default')]];

    this.list.sorts().forEach((sort) => {
      const label = this.sortLabel(sort);
      options.push([sort, app.translator.trans('core.admin.resource.table.sort_ascending', { label })]);
      options.push([`-${sort}`, app.translator.trans('core.admin.resource.table.sort_descending', { label })]);
    });

    const active = options.find(([value]) => value === current) ?? options[0];

    return (
      <Dropdown
        className="ResourceTable-sortDropdown"
        buttonClassName="Button"
        label={active[1]}
        accessibleToggleLabel={app.translator.trans('core.admin.resource.table.sort_label', {}, true)}
        helperText={null}
      >
        {options.map(([value, label]) => (
          <Button icon={value === current ? 'fas fa-check' : true} active={value === current} onclick={() => this.list.setSort(value)}>
            {label}
          </Button>
        ))}
      </Dropdown>
    );
  }

  /** The label of a sort: its same-named field, else a column sorting by it, else the humanized key. */
  sortLabel(sort: string): Mithril.Children {
    const fields = this.fields!;

    if (fields.has(sort)) return fields.get(sort).label;

    const column = ordered(this.columns()).find(([, column]) => column.sort === sort);

    if (column) return column[1].name;

    return sort
      .replace(/([a-z])([A-Z])/g, '$1 $2')
      .replace(/[-_]+/g, ' ')
      .replace(/^./, (c) => c.toUpperCase());
  }

  /**
   * One filter as a dropdown button labelled "{label}: {value}". Choice filters (yes/no, enum)
   * list their values, relationship filters the related records; other filters (text, an
   * extension's custom control) render their control inside the menu.
   */
  filterView(key: string, filter: Filter): Mithril.Children {
    const value = this.list.getFilter(key);
    const valueLabel = value === '' ? anyLabel() : this.filterValueLabel(key, filter, value);
    const relation = this.relationSpec(filter);

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
        oncreate={(vnode: Mithril.VnodeDOM) => this.focusPending(key, vnode.dom)}
        onupdate={(vnode: Mithril.VnodeDOM) => this.focusPending(key, vnode.dom)}
        onshow={() => {
          this.promotedFilters.add(key);
          if (relation) this.relationOptions(key, relation.related).ensureLoaded();
          this.focusFilterMenu(key);
        }}
      >
        {this.filterMenuItems(key, filter)}
      </Dropdown>
    );
  }

  /** Focus the first text box of an opened filter menu (a text filter, a relationship search). */
  protected focusFilterMenu(key: string): void {
    (this.element?.querySelector(`.ResourceTable-filter--${key} .Dropdown-menu input`) as HTMLElement | null)?.focus();
  }

  /** The `[value, label]` choices of a choice filter (yes/no, enum), "Any" first; null for others. */
  filterChoices(_key: string, filter: Filter): [string, Mithril.Children][] | null {
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
  filterMenuItems(key: string, filter: Filter): Mithril.Children[] {
    const choices = this.filterChoices(key, filter);
    const current = this.list.getFilter(key);

    if (choices) {
      return choices.map(([value, label]) => this.filterChoice(key, value, label, value === current));
    }

    const relation = this.relationSpec(filter);

    if (relation) return this.relationMenuItems(key, relation);

    return [
      <div className="ResourceTable-filterControl" onclick={(e: MouseEvent) => e.stopPropagation()}>
        {this.filterControl(key, filter)}
      </div>,
    ];
  }

  protected filterChoice(key: string, value: string, label: Mithril.Children, active: boolean): Mithril.Children {
    return (
      <Button
        icon={active ? 'fas fa-check' : true}
        active={active}
        itemClassName={active ? 'active' : undefined}
        aria-pressed={active ? 'true' : 'false'}
        data-value={value}
        onclick={() => this.setToolbarFilter(key, value)}
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

    items.push(this.filterChoice(key, '', anyLabel(), current === ''));

    const selected = current ? relatedModel(spec.related, current) : null;
    const exclude = this.filters?.has(key) ? this.filters.get(key).exclude : undefined;
    const records = options.items.filter((model) => model.id() !== current && !exclude?.(model));

    if (selected) items.push(this.filterChoice(key, current, label(selected), true));

    records.forEach((model) => items.push(this.filterChoice(key, model.id()!, label(model), false)));

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
  filterControl(key: string, filter: Filter): Mithril.Children {
    const stream = this.filterStream(key, filter);

    return typeof filter.input === 'function'
      ? filter.input({ stream })
      : this.filterInputs.render(filter.input, { name: key, stream, label: filter.label });
  }

  /** How a set filter value reads on its button and chip. */
  filterValueLabel(key: string, filter: Filter, value: string): Mithril.Children {
    const choice = this.filterChoices(key, filter)?.find(([v]) => v === value);

    if (choice) return choice[1];

    const relation = this.relationSpec(filter);

    if (relation) {
      const model = relatedModel(relation.related, value);

      return model ? String(model.attribute(relation.display) ?? value) : value;
    }

    return value;
  }

  /**
   * One chip per filter with a value: "{label}: {value}" and a remove button. A chip whose filter
   * key a gambit in the search also sends is marked overridden, since the gambit wins; one the
   * search sends with the opposite sign (`-suspended` vs `suspended`) is marked as conflicting.
   */
  chipItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const searchKeys = this.searchFilterKeys();

    ordered(this.filters ?? new ItemList<Filter>()).forEach(([key, filter], index) => {
      const value = this.list.getFilter(key);

      if (value === '') return;

      const relation = this.searchRelation(key, value, searchKeys);
      const note =
        relation === 'overridden'
          ? app.translator.trans('core.admin.resource.table.overridden_by_search', {}, true)
          : relation === 'conflict'
            ? app.translator.trans('core.admin.resource.table.conflicts_with_search', {}, true)
            : null;
      const label = extractText(filter.label);

      items.add(
        key,
        <Pill
          className={classList(
            'ResourceTable-chip',
            `ResourceTable-chip--${key}`,
            relation === 'overridden' && 'ResourceTable-chip--overridden',
            relation === 'conflict' && 'ResourceTable-chip--conflict'
          )}
          deletable={true}
          deleteLabel={app.translator.trans('core.admin.resource.table.remove_filter_label', { label }, true)}
          ondelete={() => this.list.setFilter(key, '')}
        >
          <span className="ResourceTable-chipText" title={note ?? undefined}>
            {app.translator.trans('core.admin.resource.table.filter_label', { label, value: this.filterValueLabel(key, filter, value) })}
          </span>
          {note && <span className="visually-hidden">({note})</span>}
        </Pill>,
        100 - index
      );
    });

    return items;
  }

  /** Remove every structured filter. */
  clearFilters(): Promise<void> {
    Object.keys(this.drafts)
      .filter((key) => key.startsWith('filter:'))
      .forEach((key) => {
        clearTimeout(this.timers[key]);
        delete this.drafts[key];
      });

    return this.list.clearFilters().catch(() => {});
  }

  /**
   * A stream over one list filter. Text-like filters (or specs with `debounce: true`) keep a draft
   * and commit debounced.
   */
  filterStream(key: string, filter: Filter): Stream<string> {
    const debounced = typeof filter.input !== 'function' && (filter.input.debounce ?? TEXT_FILTER_TYPES.includes(filter.input.type));
    const draftKey = `filter:${key}`;

    const stream = (...args: [string] | []) => {
      if (args.length) {
        const value = args[0] ?? '';

        if (debounced) this.debounce(draftKey, value, (v) => this.setToolbarFilter(key, v));
        else this.setToolbarFilter(key, value);

        return value;
      }

      return debounced ? this.draft(draftKey, () => this.list.getFilter(key)) : this.list.getFilter(key);
    };

    return stream as unknown as Stream<string>;
  }

  protected draft(key: string, committed: () => string): string {
    return key in this.drafts ? this.drafts[key] : committed();
  }

  protected debounce(key: string, value: string, commit: (value: string) => Promise<void>): void {
    this.drafts[key] = value;

    clearTimeout(this.timers[key]);

    this.timers[key] = setTimeout(() => {
      delete this.timers[key];
      commit(value)
        // A failed request has already shown its error.
        .catch(() => {})
        .finally(() => {
          if (this.drafts[key] === value) delete this.drafts[key];
          m.redraw();
        });
      m.redraw();
    }, ResourceTable.SEARCH_DELAY);
  }

  // ---------------------------------------------------------------------------------------------
  // Table

  /**
   * The columns: the `columns` attr, else one per field with a column, by the column's priority.
   */
  columns(): ItemList<ColumnData<M>> {
    if (this.attrs.columns) return this.attrs.columns;

    const columns = new ItemList<ColumnData<M>>();

    Object.entries(this.fields!.toObject()).forEach(([key, item]) => {
      const field = item.content as Field<M>;

      if (!field.column) return;

      columns.add(
        key,
        { name: field.label, content: (model) => renderColumn(key, field, model), sort: field.column.sort },
        field.column.priority ?? item.priority
      );
    });

    return columns;
  }

  /**
   * The columns on screen, in order: toggleable ones the user hid (or `hidden` ones the user has
   * not explicitly shown) are left out.
   */
  visibleColumns(): [string, ColumnData<M>][] {
    return ordered(this.columns()).filter(([key, column]) => this.isColumnVisible(key, column));
  }

  isColumnVisible(key: string, column: ColumnData<M>): boolean {
    if (column.toggleable === false) return true;

    return this.storedChoices().get(key) ?? !column.hidden;
  }

  /**
   * Show or hide a column and store the choice. Only explicit choices are stored, so columns the
   * user never toggled keep following their `hidden` default. Stored choices for columns that are
   * absent (an extension that is disabled for now) or not toggleable are kept and ignored, so the
   * choice is back when the column is.
   */
  toggleColumn(key: string, visible: boolean): void {
    const choices = new Map(this.storedChoices());

    choices.set(key, visible);

    this.columnChoices = choices;
    ColumnPreferences.save(this.attrs.definition.type, choices);
  }

  /** The user's explicit column choices, key to visible. */
  protected storedChoices(): Map<string, boolean> {
    return (this.columnChoices ??= ColumnPreferences.load(this.attrs.definition.type));
  }

  /** The table: header, rows (or the empty state) and a loading overlay. */
  tableView(): Mithril.Children {
    const columns = this.visibleColumns();
    const models = this.list.getItems();
    const loading = this.list.isLoading();
    const initial = this.list.isInitialLoading() && !models.length;
    const selectable = !this.currentBulkActions().isEmpty();
    const actions = this.attrs.actionsColumn !== false;

    return (
      <div
        className={classList('ResourceTable-container', loading && 'ResourceTable-container--loading', this.attrs.tableClassName)}
        aria-busy={loading ? 'true' : 'false'}
        oncreate={(vnode: Mithril.VnodeDOM) => (this.unfloatMenus = floatMenus(vnode.dom as HTMLElement))}
        onremove={() => this.unfloatMenus?.()}
      >
        <table className="ResourceTable-table">
          <caption className="visually-hidden">{this.attrs.definition.label(2)}</caption>
          <thead>
            <tr>
              {selectable && this.selectAllView()}
              {columns.map(([key, column]) => this.headerView(key, column))}
              {actions && (
                <th scope="col" className="ResourceTable-actionsHeader">
                  <span className="visually-hidden">{app.translator.trans('core.admin.resource.table.actions_label')}</span>
                </th>
              )}
            </tr>
          </thead>
          <tbody>
            {initial
              ? null
              : models.length
                ? models.map((model) => this.rowView(model, columns, selectable))
                : this.emptyView(columns.length + (selectable ? 1 : 0) + (actions ? 1 : 0))}
          </tbody>
        </table>
        {loading && <LoadingIndicator size="large" />}
      </div>
    );
  }

  selectAllView(): Mithril.Children {
    const models = this.list.getItems();

    return (
      <th scope="col" className="ResourceTable-selectHeader">
        <input
          type="checkbox"
          className="ResourceTable-selectAll"
          aria-label={app.translator.trans('core.admin.resource.table.select_all_label', {}, true)}
          checked={this.list.isPageSelected()}
          indeterminate={!this.list.isPageSelected() && this.list.selectedModels().length > 0}
          disabled={!models.length}
          onchange={(e: Event) => this.list.setPageSelected((e.target as HTMLInputElement).checked)}
        />
      </th>
    );
  }

  selectView(model: M): Mithril.Children {
    const id = model.id()!;

    return (
      <td className="ResourceTable-selectCell">
        <input
          type="checkbox"
          className="ResourceTable-select"
          aria-label={app.translator.trans('core.admin.resource.table.select_label', { name: this.attrs.definition.recordLabel(model) }, true)}
          checked={this.list.isSelected(id)}
          onchange={(e: Event) => this.list.setSelected(id, (e.target as HTMLInputElement).checked)}
        />
      </td>
    );
  }

  headerView(key: string, column: ColumnData<M>): Mithril.Children {
    const sort = column.sort;

    if (!sort || !this.list.isSortable(sort)) {
      return (
        <th scope="col" data-column-name={key} className={column.className}>
          {column.name}
        </th>
      );
    }

    const current = this.list.getSort();
    const direction = current === sort ? 'ascending' : current === `-${sort}` ? 'descending' : 'none';
    const icon = { ascending: 'fas fa-sort-up', descending: 'fas fa-sort-down', none: 'fas fa-sort' }[direction];

    return (
      <th
        scope="col"
        data-column-name={key}
        aria-sort={direction}
        className={classList(column.className, 'ResourceTable-sortable', direction !== 'none' && 'is-sorted')}
      >
        <button type="button" className="ResourceTable-sort" onclick={() => this.toggleSort(sort)}>
          {column.name}
          <Icon name={icon} className="ResourceTable-sortIcon" />
        </button>
      </th>
    );
  }

  /** None → ascending → descending → default order. */
  toggleSort(sort: string): Promise<void> {
    const current = this.list.getSort();
    const next = current === sort ? `-${sort}` : current === `-${sort}` ? '' : sort;

    return this.list.setSort(next);
  }

  /**
   * One record's row: the checkbox when `selectable`, a cell per column (`data-column-name` is the
   * column key), then the row actions (unless the `actionsColumn` attr is false).
   */
  rowView(model: M, columns: [string, ColumnData<M>][], selectable: boolean = false): Mithril.Children {
    const selected = selectable && this.list.isSelected(model.id()!);

    return (
      <tr
        key={model.id()}
        data-id={model.id()}
        className={classList('ResourceTable-row', selected && 'is-selected')}
        {...(this.attrs.rowAttrs?.(model) ?? {})}
      >
        {selectable && this.selectView(model)}
        {columns.map(([key, column]) => (
          <td data-column-name={key} className={column.className} {...(column.attrs?.(model) ?? {})}>
            {column.content(model)}
          </td>
        ))}
        {this.attrs.actionsColumn !== false && <td className="ResourceTable-actions">{this.rowActions(model).toArray()}</td>}
      </tr>
    );
  }

  /**
   * Edit (`canEdit`), delete (`canDelete`), then the definition's row actions.
   */
  rowActions(model: M): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (model.attribute('canEdit')) {
      items.add(
        'edit',
        <Button
          className="Button Button--icon ResourceTable-edit"
          icon="fas fa-pencil-alt fa-fw"
          title={app.translator.trans('core.admin.resource.table.edit_button', {}, true)}
          aria-label={app.translator.trans('core.admin.resource.table.edit_button', {}, true)}
          onclick={() => this.edit(model)}
        />,
        100
      );
    }

    if (model.attribute('canDelete')) {
      items.add(
        'delete',
        <Button
          className="Button Button--icon Button--danger ResourceTable-delete"
          icon="fas fa-trash-alt fa-fw"
          title={app.translator.trans('core.admin.resource.table.delete_button', {}, true)}
          aria-label={app.translator.trans('core.admin.resource.table.delete_button', {}, true)}
          onclick={() => this.delete(model)}
        />,
        90
      );
    }

    return items.merge(this.attrs.definition.actions(model));
  }

  /** The row shown when the page has no records. */
  emptyView(colspan: number): Mithril.Children {
    const params = this.list.getParams();
    const filtered = !!params.q || !!Object.keys(params.filter ?? {}).length;

    return (
      <tr>
        <td colSpan={colspan} className="ResourceTable-empty">
          {filtered ? app.translator.trans('core.admin.resource.table.no_results') : app.translator.trans('core.admin.resource.table.empty')}
        </td>
      </tr>
    );
  }

  // ---------------------------------------------------------------------------------------------
  // Pagination

  /** Core's `Pagination` when the API sends a total (the toolbar shows it), else previous/next. */
  paginationView(): Mithril.Children {
    const list = this.list;
    const page = list.currentPage();
    const pages = list.pageCount();

    if (pages !== null) {
      return pages > 1 ? (
        <div className="ResourceTable-pagination">
          <Pagination total={list.totalItems!} perPage={list.pageSize!} currentPage={page} onChange={(p: number) => list.goto(p)} />
        </div>
      ) : null;
    }

    if (!list.hasPrev() && !list.hasNext() && page <= 1) return null;

    return (
      <nav
        className="ResourceTable-pagination ResourceTable-prevNext"
        aria-label={app.translator.trans('core.admin.resource.table.pagination_label', {}, true)}
      >
        <Button
          className="Button ResourceTable-prev"
          icon="fas fa-chevron-left"
          disabled={page <= 1 || list.isLoading()}
          onclick={() => list.goto(page - 1)}
        >
          {app.translator.trans('core.admin.resource.table.previous_button')}
        </Button>
        <span className="ResourceTable-page">{app.translator.trans('core.admin.resource.table.page', { page })}</span>
        <Button className="Button ResourceTable-next" disabled={!list.hasNext() || list.isLoading()} onclick={() => list.goto(page + 1)}>
          {app.translator.trans('core.admin.resource.table.next_button')}
          <Icon name="fas fa-chevron-right" className="Button-icon ResourceTable-nextIcon" />
        </Button>
      </nav>
    );
  }

  // ---------------------------------------------------------------------------------------------
  // Export

  exportButton(): Mithril.Children {
    const exporting = this.exporting;

    if (exporting) {
      return (
        <Button
          className="Button ResourceTable-export ResourceTable-export--running"
          icon="fas fa-times"
          onclick={() => exporting.controller.abort()}
        >
          {exporting.total
            ? app.translator.trans('core.admin.resource.table.export_progress_total', { count: exporting.rows, total: exporting.total })
            : app.translator.trans('core.admin.resource.table.export_progress', { count: exporting.rows })}
        </Button>
      );
    }

    return (
      <Button className="Button ResourceTable-export" icon="fas fa-file-csv" onclick={() => this.exportCsv()}>
        {app.translator.trans('core.admin.resource.table.export_button')}
      </Button>
    );
  }

  /** Download the visible columns of every record matching the current search, filters and sort. */
  exportCsv(maxRows?: number): Promise<void> {
    if (this.exporting) return Promise.resolve();

    const controller = new AbortController();
    this.exporting = { controller, rows: 0, total: null };

    return exportCsv(this.list, {
      columns: this.visibleColumns(),
      maxRows,
      signal: controller.signal,
      onProgress: (rows, total) => {
        if (this.exporting) Object.assign(this.exporting, { rows, total });
        m.redraw();
      },
    })
      .then(({ rows, truncated }) => {
        if (truncated) app.alerts.show({ type: 'warning' }, app.translator.trans('core.admin.resource.table.export_truncated', { count: rows }));
      })
      .catch((error) => {
        if (error?.name === 'AbortError') return;

        console.error(`ResourceTable(${this.attrs.definition.type}): export failed`, error);
        app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.resource.table.export_error'));
      })
      .finally(() => {
        this.exporting = null;
        m.redraw();
      });
  }

  // ---------------------------------------------------------------------------------------------
  // Actions

  /** Reload the current page, keeping the rows on screen. */
  reload(page: number = this.list.currentPage()): Promise<void> {
    return this.list.goto(Math.max(1, page));
  }

  /** The `onCreate` attr, else `ResourceFormModal` for a new record. */
  create(): void {
    if (this.attrs.onCreate) {
      this.attrs.onCreate();
      return;
    }

    app.modal.show(ResourceFormModal, {
      definition: this.attrs.definition,
      model: null,
      inputs: this.attrs.formInputs,
      onsaved: () => this.reload(),
    });
  }

  /** The `onEdit` attr, else `ResourceFormModal` for the record. */
  edit(model: M): void {
    if (this.attrs.onEdit) {
      this.attrs.onEdit(model);
      return;
    }

    app.modal.show(ResourceFormModal, {
      definition: this.attrs.definition,
      model,
      inputs: this.attrs.formInputs,
      onsaved: () => this.reload(),
    });
  }

  /**
   * The modal that confirms and deletes one record, and its attrs. `delete()` chains the list
   * reload onto the attrs' `ondeleted`.
   */
  deleteModal(model: M): { modal: ModalClass; attrs: Pick<ResourceDeleteModalAttrs<M>, 'definition' | 'model' | 'ondeleted'> } {
    return { modal: ResourceDeleteModal, attrs: { definition: this.attrs.definition, model } };
  }

  /** `deleteModal()` for the record; deleting the last row of a page goes back one page. */
  delete(model: M): void {
    const { modal, attrs } = this.deleteModal(model);

    app.modal.show(modal, {
      ...attrs,
      ondeleted: (deleted: M) => {
        attrs.ondeleted?.(deleted);
        this.list.gotoAfterRemoval();
      },
    });
  }
}
