import app from '../../app';
import Component, { type ComponentAttrs } from '../../../common/Component';
import Alert from '../../../common/components/Alert';
import Button from '../../../common/components/Button';
import Dropdown from '../../../common/components/Dropdown';
import Icon from '../../../common/components/Icon';
import LoadingIndicator from '../../../common/components/LoadingIndicator';
import Pagination from '../../../common/components/Pagination';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import extractText from '../../../common/utils/extractText';
import type Stream from '../../../common/utils/Stream';
import { SequentialBulkRunner, type BulkActionContext, type BulkRunner, type BulkSummary } from '../../../common/resource/BulkRunner';
import ColumnPreferences from '../../../common/resource/ColumnPreferences';
import { exportCsv } from '../../../common/resource/csv';
import { renderColumn } from '../../../common/resource/field';
import { TEXT_FILTER_TYPES } from '../../../common/resource/inputs/defaults';
import type InputFactory from '../../../common/resource/inputs/InputFactory';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import ResourceListState from '../../../common/resource/ResourceListState';
import type { Field, Filter } from '../../../common/resource/types';
import type Mithril from 'mithril';
import BulkResultModal from './BulkResultModal';
import ColumnsDropdown from './ColumnsDropdown';
import recordLabel from './recordLabel';
import ResourceDeleteModal from './ResourceDeleteModal';
import ResourceFormModal from './ResourceFormModal';

/**
 * One table column. `{name, content}` is the shape `UserListPage.columns()` has always used; the rest
 * is optional.
 *
 * @experimental
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

/**
 * @experimental
 */
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
  /** Offer the CSV export. Default `definition.canExport()`. */
  exportable?: boolean;
  /**
   * Append the actions column (`rowActions()`: edit, delete, the definition's actions). Default
   * true. Pass false when `columns` brings its own actions column, so there is exactly one.
   */
  actionsColumn?: boolean;
}

const t = (key: string, params: Record<string, unknown> = {}) => app.translator.trans(`core.admin.resource.table.${key}`, params);
const text = (key: string, params: Record<string, unknown> = {}) => extractText(t(key, params));

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
 *
 * @experimental
 */
export default class ResourceTable<M extends Model = Model, Attrs extends ResourceTableAttrs<M> = ResourceTableAttrs<M>> extends Component<Attrs> {
  static SEARCH_DELAY = 300;

  list!: ResourceListState<M>;
  filterInputs!: InputFactory;
  /** Null until the descriptor is loaded. */
  fields: ItemList<Field<M>> | null = null;
  /** Built once the descriptor is loaded; `definition.filters()` rebuilds every field. */
  filters: ItemList<Filter> | null = null;
  loadError: boolean = false;
  /** The running export, if any. */
  exporting: { controller: AbortController; rows: number; total: number | null } | null = null;

  /** The user's explicit column choices (key to visible); read from `ColumnPreferences` on first use. */
  protected columnChoices: Map<string, boolean> | null = null;

  /** Uncommitted text in the search and text filters, so typing isn't reverted by the debounce. */
  protected drafts: Record<string, string> = {};
  protected timers: Record<string, ReturnType<typeof setTimeout>> = {};

  /** `bulkActionItems()` for the view pass in progress, so the toolbar and the table share one build. */
  protected viewBulkActions: ItemList<Mithril.Children> | null = null;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    const { definition, state, syncUrl, pageSize } = this.attrs;

    this.list = state ?? new ResourceListState<M>(definition, { syncUrl, pageSize });
    this.filterInputs = this.attrs.filterInputs ?? definition.filterInputs();

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
    this.exporting?.controller.abort();
  }

  view(): Mithril.Children {
    const className = classList('ResourceTable', `ResourceTable--${this.attrs.definition.type}`, this.attrs.className);

    if (this.loadError) {
      return (
        <div className={classList(className, 'ResourceTable-error')}>
          <Alert type="error" dismissible={false}>
            {t('load_error')}
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
   * The table's regions: `toolbar`, `table`, `pagination`.
   *
   * @experimental
   */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('toolbar', <div className="ResourceTable-toolbar">{this.toolbarItems().toArray()}</div>, 100);
    items.add('table', this.tableView(), 50);
    items.add('pagination', this.paginationView(), 0);

    return items;
  }

  // ---------------------------------------------------------------------------------------------
  // Toolbar

  /**
   * `view` (search, sort, filters), `actions` and, when the definition has bulk actions, `bulk`.
   *
   * @experimental
   */
  toolbarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('view', <div className="ResourceTable-toolbarView">{this.viewItems().toArray()}</div>, 100);
    items.add('actions', <div className="ResourceTable-toolbarActions">{this.actionItems().toArray()}</div>, 50);

    const bulk = this.currentBulkActions();

    if (!bulk.isEmpty()) {
      items.add('bulk', this.bulkView(bulk), 25);
    }

    return items;
  }

  /** The bulk toolbar: the selected count, then the bulk actions. */
  bulkView(actions: ItemList<Mithril.Children>): Mithril.Children {
    const count = this.list.selectedModels().length;

    return (
      <div className="ResourceTable-bulk" role="group" aria-label={text('bulk_label')}>
        <span className="ResourceTable-selectedCount" aria-live="polite">
          {t('selected', { count })}
        </span>
        {actions.toArray()}
      </div>
    );
  }

  /**
   * The definition's bulk actions for the current selection. While empty, the table has no checkbox
   * column.
   *
   * @experimental
   */
  bulkActionItems(): ItemList<Mithril.Children> {
    return this.attrs.definition.bulkActions(this.list.selection, this.bulkContext());
  }

  /**
   * What the definition's bulk actions run with: this table's list, `bulkRunner()`, and
   * `BulkResultModal` for the outcome.
   *
   * @experimental
   */
  bulkContext(): BulkActionContext<M> {
    return {
      list: this.list,
      runner: this.bulkRunner(),
      showResult: (summary: BulkSummary<M>) => app.modal.show(BulkResultModal, { summary }),
    };
  }

  /**
   * The runner bulk actions use: one request per record, 3 at a time.
   *
   * @experimental
   */
  bulkRunner(): BulkRunner {
    return new SequentialBulkRunner();
  }

  /**
   * Search (when the type is searchable), sort, one control per filter, the "Columns" dropdown (when
   * a column is toggleable) and the CSV export (when `exportable`).
   *
   * @experimental
   */
  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.attrs.definition.descriptor?.search !== false) {
      items.add(
        'search',
        <input
          className="FormControl ResourceTable-search"
          type="search"
          value={this.draft('q', () => this.list.getQuery())}
          placeholder={text('search_placeholder')}
          aria-label={text('search_label')}
          oninput={(e: InputEvent) => this.debounce('q', (e.target as HTMLInputElement).value, (v) => this.list.setQuery(v))}
        />,
        100
      );
    }

    if (this.list.sorts().length) {
      items.add('sort', this.sortDropdown(), 90);
    }

    ordered(this.filters ?? new ItemList<Filter>()).forEach(([key, filter], index) => {
      items.add(`filter-${key}`, this.filterView(key, filter), 80 - index);
    });

    const toggleable = ordered(this.columns()).filter(([, column]) => column.toggleable !== false);

    if (toggleable.length) {
      items.add(
        'columns',
        <ColumnsDropdown
          columns={toggleable.map(([key, column]) => ({ key, name: column.name, visible: this.isColumnVisible(key, column) }))}
          onchange={(key: string, visible: boolean) => this.toggleColumn(key, visible)}
        />,
        -10
      );
    }

    if (this.attrs.exportable ?? this.attrs.definition.canExport()) {
      items.add('export', this.exportButton(), -20);
    }

    return items;
  }

  /**
   * Refresh, create (when `canCreate()`), then the definition's toolbar items.
   *
   * @experimental
   */
  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'refresh',
      <Button
        className="Button Button--icon ResourceTable-refresh"
        icon="fas fa-sync"
        title={text('refresh_button')}
        aria-label={text('refresh_button')}
        disabled={this.list.isLoading()}
        onclick={() => this.reload()}
      />,
      100
    );

    if (this.attrs.definition.canCreate()) {
      items.add(
        'create',
        <Button className="Button Button--primary ResourceTable-create" icon="fas fa-plus" onclick={() => this.create()}>
          {t('create_button')}
        </Button>,
        90
      );
    }

    return items.merge(this.attrs.definition.toolbar(this.list));
  }

  /** The sort dropdown: the default order, then each descriptor sort ascending and descending. */
  sortDropdown(): Mithril.Children {
    const current = this.list.getSort();
    const options: [string, Mithril.Children][] = [['', t('sort_default')]];

    this.list.sorts().forEach((sort) => {
      const label = this.sortLabel(sort);
      options.push([sort, t('sort_ascending', { label })]);
      options.push([`-${sort}`, t('sort_descending', { label })]);
    });

    const active = options.find(([value]) => value === current) ?? options[0];

    return (
      <Dropdown
        className="ResourceTable-sortDropdown"
        buttonClassName="Button"
        label={active[1]}
        accessibleToggleLabel={text('sort_label')}
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

  /** One filter control, rendered by the filter input factory. */
  filterView(key: string, filter: Filter): Mithril.Children {
    const stream = this.filterStream(key, filter);
    const input =
      typeof filter.input === 'function'
        ? filter.input({ stream })
        : this.filterInputs.render(filter.input, { name: key, stream, label: filter.label });

    return (
      <div className={classList('ResourceTable-filter', `ResourceTable-filter--${key}`)} title={extractText(filter.label)}>
        {input}
      </div>
    );
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

        if (debounced) this.debounce(draftKey, value, (v) => this.list.setFilter(key, v));
        else this.list.setFilter(key, value);

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
   *
   * @experimental
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
      >
        <table className="ResourceTable-table">
          <caption className="visually-hidden">{this.attrs.definition.label(2)}</caption>
          <thead>
            <tr>
              {selectable && this.selectAllView()}
              {columns.map(([key, column]) => this.headerView(key, column))}
              {actions && (
                <th scope="col" className="ResourceTable-actionsHeader">
                  <span className="visually-hidden">{t('actions_label')}</span>
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
          aria-label={text('select_all_label')}
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
          aria-label={text('select_label', { name: recordLabel(model) })}
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
   *
   * @experimental
   */
  rowActions(model: M): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (model.attribute('canEdit')) {
      items.add(
        'edit',
        <Button
          className="Button Button--icon ResourceTable-edit"
          icon="fas fa-pencil-alt fa-fw"
          title={text('edit_button')}
          aria-label={text('edit_button')}
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
          title={text('delete_button')}
          aria-label={text('delete_button')}
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
          {t(filtered ? 'no_results' : 'empty')}
        </td>
      </tr>
    );
  }

  // ---------------------------------------------------------------------------------------------
  // Pagination

  /** Core's `Pagination` and the total when the API sends one, else previous/next. */
  paginationView(): Mithril.Children {
    const list = this.list;
    const page = list.currentPage();
    const pages = list.pageCount();

    if (pages !== null) {
      return (
        <div className="ResourceTable-pagination">
          <span className="ResourceTable-total">{t('total', { count: list.totalItems })}</span>
          {pages > 1 && <Pagination total={list.totalItems!} perPage={list.pageSize!} currentPage={page} onChange={(p: number) => list.goto(p)} />}
        </div>
      );
    }

    if (!list.hasPrev() && !list.hasNext() && page <= 1) return null;

    return (
      <nav className="ResourceTable-pagination ResourceTable-prevNext" aria-label={text('pagination_label')}>
        <Button
          className="Button ResourceTable-prev"
          icon="fas fa-chevron-left"
          disabled={page <= 1 || list.isLoading()}
          onclick={() => list.goto(page - 1)}
        >
          {t('previous_button')}
        </Button>
        <span className="ResourceTable-page">{t('page', { page })}</span>
        <Button className="Button ResourceTable-next" disabled={!list.hasNext() || list.isLoading()} onclick={() => list.goto(page + 1)}>
          {t('next_button')}
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
            ? t('export_progress_total', { count: exporting.rows, total: exporting.total })
            : t('export_progress', { count: exporting.rows })}
        </Button>
      );
    }

    return (
      <Button className="Button ResourceTable-export" icon="fas fa-file-csv" onclick={() => this.exportCsv()}>
        {t('export_button')}
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
        if (truncated) app.alerts.show({ type: 'warning' }, t('export_truncated', { count: rows }));
      })
      .catch((error) => {
        if (error?.name === 'AbortError') return;

        console.error(`ResourceTable(${this.attrs.definition.type}): export failed`, error);
        app.alerts.show({ type: 'error' }, t('export_error'));
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

  /** `ResourceDeleteModal` for the record; deleting the last row of a page goes back one page. */
  delete(model: M): void {
    app.modal.show(ResourceDeleteModal, {
      definition: this.attrs.definition,
      model,
      ondeleted: () => {
        const page = this.list.currentPage();

        this.reload(this.list.getItems().length <= 1 && page > 1 ? page - 1 : page);
      },
    });
  }
}
