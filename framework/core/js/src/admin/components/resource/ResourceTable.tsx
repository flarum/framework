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
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import { ConcurrentBulkRunner, type BulkActionContext, type BulkRunner, type BulkSummary } from '../../../common/resource/BulkRunner';
import ColumnPreferences from '../../../common/resource/ColumnPreferences';
import { renderColumn } from '../../../common/resource/field';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import ResourceListState from '../../../common/resource/ResourceListState';
import type { Field } from '../../../common/resource/types';
import type Mithril from 'mithril';
import BulkResultModal from './BulkResultModal';
import ColumnsDropdown from './ColumnsDropdown';
import Drafts from './Drafts';
import ExportCsvButton from './ExportCsvButton';
import floatMenus from './floatMenus';
import ordered from './ordered';
import ResourceDeleteModal, { type ResourceDeleteModalAttrs } from './ResourceDeleteModal';
import CompactFilterBar, { ALWAYS_QUERY, PHONE_QUERY } from './CompactFilterBar';
import ResourceFilterBar from './ResourceFilterBar';
import ResourceFormModal from './ResourceFormModal';
import type ModalManagerState from '../../../common/states/ModalManagerState';

/** What `app.modal.show()` takes: a modal class or a loader of one. */
type ModalClass = Parameters<ModalManagerState['show']>[0];

/**
 * One table column. `{name, content}` is the shape of `UserListPage.columns()`; the rest is
 * optional.
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
  /** Hidden until the user shows it. A stored choice for this column wins. */
  hidden?: boolean;
  /**
   * The column's CSV value. Without it, the CSV takes the raw attribute when the column key is a
   * descriptor field, else leaves the column out; rendered content is never exported.
   */
  csv?: (model: M) => string | number | null;
}

/**
 * Shallow-merge `patch` into the column at `key`. No-op if the key is absent.
 */
export function patchColumn<M extends Model>(columns: ItemList<ColumnData<M>>, key: string, patch: Partial<ColumnData<M>>): void {
  if (!columns.has(key)) return;

  columns.setContent(key, { ...columns.get(key), ...patch });
}

export interface ResourceTableAttrs<M extends Model = Model> extends ComponentAttrs {
  definition: ResourceDefinition<M>;
  /** An existing list state; one is created (and owned) otherwise. */
  state?: ResourceListState<M>;
  /** Sync sort, search, filters and page with the URL. Default true. */
  syncUrl?: boolean;
  pageSize?: number;
  /**
   * Replaces the columns inferred from the descriptor, the actions column included; the keys become
   * `data-column-name`.
   */
  columns?: ItemList<ColumnData<M>>;
  /** Added to the root element's class. */
  className?: string;
  /**
   * The filters as one "Filters (n)" dropdown: `true` always, `false` never, unset at phone width.
   * Read by `filterBar()`.
   */
  compactFilters?: boolean;
}

/**
 * The admin table for one resource type: columns from the definition's fields (or the `columns`
 * attr), header and dropdown sorting over the descriptor's sorts, debounced search, the filter bar
 * (`filterBar()`), numbered (core `Pagination`) or prev/next pagination, and create/edit/delete
 * gated by `canCreate()` and the rows' `canEdit`/`canDelete` attributes. Every region is an
 * `ItemList` for extensions.
 */
export default class ResourceTable<M extends Model = Model, Attrs extends ResourceTableAttrs<M> = ResourceTableAttrs<M>> extends Component<Attrs> {
  list!: ResourceListState<M>;
  /** Null until the descriptor is loaded. */
  fields: ItemList<Field<M>> | null = null;
  /** The filters' toolbar items and chips (`filterBar()`); null until the descriptor is loaded. */
  bar: ResourceFilterBar<M> | null = null;
  loadError: boolean = false;
  /** Removes the row menus' fixed positioning listeners (`floatMenus`). */
  protected unfloatMenus: (() => void) | null = null;

  /** The user's explicit column choices (key to visible); read from `ColumnPreferences` on first use. */
  protected columnChoices: Map<string, boolean> | null = null;

  /** The search typed but not yet sent. */
  protected searchDraft = new Drafts();
  /** `bulkActionItems()` for the view pass in progress, so the toolbar and the table share one build. */
  protected viewBulkActions: ItemList<Mithril.Children> | null = null;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    const { definition, state, syncUrl, pageSize } = this.attrs;

    this.list = state ?? new ResourceListState<M>(definition, { syncUrl, pageSize });

    definition
      .load()
      .then(() => {
        this.fields = definition.fields();
        this.bar = this.filterBar();
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

    this.searchDraft.discard();
    this.bar?.destroy();
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
   * The runner the bulk actions run with. Default: one request per record, 3 at a time.
   */
  bulkRunner(): BulkRunner {
    return new ConcurrentBulkRunner();
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
   * The filter bar for the loaded filters, built once: its state (drafts, promoted filters, relation
   * menus) lives as long as the table. Follows the `compactFilters` attr: a plain
   * `ResourceFilterBar` for `false`, an always compact `CompactFilterBar` for `true`, and unset the
   * `CompactFilterBar` that collapses into one "Filters (n)" dropdown at phone width.
   */
  filterBar(): ResourceFilterBar<M> {
    const attrs = { definition: this.attrs.definition, list: this.list, filters: this.attrs.definition.filters() };
    const compact = this.attrs.compactFilters;

    if (compact === false) return new ResourceFilterBar<M>(attrs);

    return new CompactFilterBar<M>(attrs, compact ? ALWAYS_QUERY : PHONE_QUERY);
  }

  /**
   * Search (`search`, when the type is searchable), sort, then the filter bar's `items()`.
   */
  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.isSearchable()) {
      items.add('search', this.searchView(), 100);
    }

    if (this.list.sorts().length) {
      items.add('sort', this.sortDropdown(), 90);
    }

    return this.bar ? items.merge(this.bar.items()) : items;
  }

  /**
   * The row under the toolbar: the active filter chips and "Clear all" (`filters`), then `display`
   * ("Columns" and "Export CSV").
   */
  subToolbarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const chips = this.bar?.chips();

    if (chips) items.add('filters', chips, 100);

    const display = this.displayItems();

    if (!display.isEmpty()) {
      items.add('display', <div className="ResourceTable-display">{display.toArray()}</div>, 50);
    }

    return items;
  }

  /**
   * The "Columns" dropdown (when a column is toggleable) and the CSV export (when
   * `definition.canExport()`).
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
      items.add('export', <ExportCsvButton list={this.list} columns={() => this.visibleColumns()} />, 10);
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
    const value = this.searchDraft.get('q', () => this.list.getQuery());
    const onchange = (v: string) => this.searchDraft.set('q', v, (q) => this.list.setQuery(q));

    const input = (
      <Input
        className="ResourceTable-searchInput"
        type="search"
        prefixIcon="fas fa-search"
        clearable={true}
        loading={this.searchDraft.has('q') || this.list.isLoading()}
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
        {this.bar?.gambits().length ? (
          <GambitsAutocompleteDropdown resource={type} query={value} onchange={onchange}>
            {input}
          </GambitsAutocompleteDropdown>
        ) : (
          input
        )}
      </div>
    );
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
   * The columns: the `columns` attr, else one per field with a column, by the column's priority,
   * then `actions` (`rowActions()`).
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

    columns.add(
      'actions',
      {
        name: <span className="visually-hidden">{app.translator.trans('core.admin.resource.table.actions_label')}</span>,
        content: (model) => this.rowActions(model).toArray(),
        className: 'ResourceTable-actions',
        toggleable: false,
      },
      -100
    );

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
  storedChoices(): Map<string, boolean> {
    return (this.columnChoices ??= ColumnPreferences.load(this.attrs.definition.type));
  }

  /** Extra classes for the table's container (`.ResourceTable-container`). Default: none. */
  containerClassName(): string {
    return '';
  }

  /** The table: header, rows (or the empty state) and a loading overlay. */
  tableView(): Mithril.Children {
    const columns = this.visibleColumns();
    const models = this.list.getItems();
    const loading = this.list.isLoading();
    const initial = this.list.isInitialLoading() && !models.length;
    const selectable = !this.currentBulkActions().isEmpty();

    return (
      <div
        className={classList('ResourceTable-container', loading && 'ResourceTable-container--loading', this.containerClassName())}
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
            </tr>
          </thead>
          <tbody>
            {initial
              ? null
              : models.length
                ? models.map((model) => this.rowView(model, columns, selectable))
                : this.emptyView(columns.length + (selectable ? 1 : 0))}
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
   * Extra attributes for one record's `<tr>`; a `className` joins the row's own. Default: none.
   */
  rowAttrs(_model: M): Record<string, unknown> {
    return {};
  }

  /**
   * One record's row: the checkbox when `selectable`, a cell per column (`data-column-name` is the
   * column key).
   */
  rowView(model: M, columns: [string, ColumnData<M>][], selectable: boolean = false): Mithril.Children {
    const selected = selectable && this.list.isSelected(model.id()!);
    const { className, ...attrs } = this.rowAttrs(model);

    return (
      <tr {...attrs} key={model.id()} data-id={model.id()} className={classList('ResourceTable-row', selected && 'is-selected', className as string)}>
        {selectable && this.selectView(model)}
        {columns.map(([key, column]) => (
          <td data-column-name={key} className={column.className} {...(column.attrs?.(model) ?? {})}>
            {column.content(model)}
          </td>
        ))}
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

  /** Reload the current page, keeping the rows on screen. */
  reload(page: number = this.list.currentPage()): Promise<void> {
    return this.list.goto(Math.max(1, page));
  }

  /** `ResourceFormModal` for a new record. */
  create(): void {
    app.modal.show(ResourceFormModal, { definition: this.attrs.definition, model: null, onsaved: () => this.reload() });
  }

  /** `ResourceFormModal` for the record. */
  edit(model: M): void {
    app.modal.show(ResourceFormModal, { definition: this.attrs.definition, model, onsaved: () => this.reload() });
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
