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
import Select from '../../../common/components/Select';
import Separator from '../../../common/components/Separator';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import extractText from '../../../common/utils/extractText';
import {
  actionLabel,
  isActionRunning,
  isBulkAction,
  isRowAction,
  needsConfirmation,
  executeAction,
  partitionByAction,
  type ResourceAction,
  type ResourceActionContext,
} from '../../../common/resource/actions';
import { ConcurrentBulkRunner, type BulkActionContext, type BulkRunner, type BulkSummary } from '../../../common/resource/BulkRunner';
import ColumnPreferences from '../../../common/resource/ColumnPreferences';
import { renderColumn } from '../../../common/resource/field';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import ResourceListState from '../../../common/resource/ResourceListState';
import type { Field } from '../../../common/resource/types';
import type Mithril from 'mithril';
import CsvExport from './CsvExport';
import Drafts from './Drafts';
import floatMenus from './floatMenus';
import ordered from './ordered';
import ResourceDeleteModal, { type ResourceDeleteModalAttrs } from './ResourceDeleteModal';
import ResourceFilterBar from './ResourceFilterBar';
import type ModalManagerState from '../../../common/states/ModalManagerState';

/** What `app.modal.show()` takes: a modal class or a loader of one. */
type ModalClass = Parameters<ModalManagerState['show']>[0];

/** Row menu items of destructive actions sit below this priority, after a separator. */
const DESTRUCTIVE_BAND = -10000;

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
  /** Listed under "Columns" in the table settings, so the user can hide it. Default true. */
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
}

/**
 * The admin table for one resource type, laid out after Carbon's data table: one toolbar row
 * (search, "Filter" panel, refresh, table settings, create) that a batch action bar replaces while
 * rows are selected, the active filter tags, the table (sortable headers, one overflow menu of row
 * actions per row) and core's `Pagination` below it (previous/next when the list has no total). Columns come from the definition's fields (or the
 * `columns` attr), actions from `resourceActions()`. Every region is an `ItemList` for extensions.
 */
export default class ResourceTable<M extends Model = Model, Attrs extends ResourceTableAttrs<M> = ResourceTableAttrs<M>> extends Component<Attrs> {
  list!: ResourceListState<M>;
  /** Null until the descriptor is loaded. */
  fields: ItemList<Field<M>> | null = null;
  /** The filters' panel and tags (`filterBar()`); null until the descriptor is loaded. */
  bar: ResourceFilterBar<M> | null = null;
  loadError: boolean = false;
  /** The CSV export, which outlives the settings menu it is started from. */
  csv!: CsvExport<M>;
  /** Removes the row menus' fixed positioning listeners (`floatMenus`). */
  protected unfloatMenus: (() => void) | null = null;

  /** The user's explicit column choices (key to visible); read from `ColumnPreferences` on first use. */
  protected columnChoices: Map<string, boolean> | null = null;

  /** The search typed but not yet sent. */
  protected searchDraft = new Drafts();
  /** Search text shown but not sent until the user goes on typing: a gambit key awaiting its value (`group:`). */
  protected heldSearch: string | null = null;
  /** `definition.resourceActions()` for the view pass in progress, shared by every row. */
  protected viewResourceActions: ItemList<ResourceAction<M>> | null = null;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    const { definition, state, syncUrl, pageSize } = this.attrs;

    this.list = state ?? new ResourceListState<M>(definition, { syncUrl, pageSize });
    this.csv = new CsvExport<M>(this.list);

    definition
      .load()
      .then(() => {
        this.fields = definition.fields();
        // The bar before this redraw, so the first loaded render shows the filters; `finally`
        // redraws again once the rows arrive.
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
    this.csv.cancel();
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

    this.viewResourceActions = this.attrs.definition.resourceActions();

    try {
      return <div className={className}>{this.items().toArray()}</div>;
    } finally {
      this.viewResourceActions = null;
    }
  }

  /** The definition's actions of the current view pass, else a fresh `resourceActions()`. */
  protected currentResourceActions(): ItemList<ResourceAction<M>> {
    return this.viewResourceActions ?? this.attrs.definition.resourceActions();
  }

  /**
   * The table's regions: `toolbar` (the batch bar while rows are selected), `status` (a polite live
   * region announcing the selection), `filterTags` (only while a filter is set), `table`,
   * `pagination`.
   */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const count = this.list.selectedModels().length;

    items.add(
      'toolbar',
      count ? (
        <div
          className="ResourceTable-toolbar ResourceTable-batchBar"
          role="group"
          aria-label={app.translator.trans('core.admin.resource.table.bulk_label', {}, true)}
        >
          {this.batchItems().toArray()}
        </div>
      ) : (
        <div className="ResourceTable-toolbar">{this.toolbarItems().toArray()}</div>
      ),
      100
    );

    items.add(
      'status',
      <div className="ResourceTable-status visually-hidden" role="status" aria-live="polite">
        {count ? app.translator.trans('core.admin.resource.table.selected', { count }) : null}
      </div>,
      90
    );

    const tags = this.bar?.chips();

    if (tags) items.add('filterTags', <div className="ResourceTable-filterTags">{tags}</div>, 75);

    items.add('table', this.tableView(), 50);
    items.add('pagination', this.paginationView(), 0);

    return items;
  }

  /** `view` (search and the "Filter" panel) on the left, `actions` (`actionItems()`) on the right. */
  toolbarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('view', <div className="ResourceTable-toolbarView">{this.viewItems().toArray()}</div>, 100);
    items.add('actions', <div className="ResourceTable-toolbarActions">{this.actionItems().toArray()}</div>, 50);

    return items;
  }

  /**
   * The batch bar's items: `count` ("{count} items selected"), then `actions` (the bulk action
   * buttons, a divider and "Cancel").
   */
  batchItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const count = this.list.selectedModels().length;

    items.add(
      'count',
      <span className="ResourceTable-batchCount">{app.translator.trans('core.admin.resource.table.selected', { count })}</span>,
      100
    );

    items.add(
      'actions',
      <div className="ResourceTable-batchActions">
        {this.bulkActionItems().toArray()}
        <span className="ResourceTable-batchDivider" aria-hidden="true" />
        <Button className="Button ResourceTable-batchButton ResourceTable-batchCancel" onclick={() => this.list.clearSelection()}>
          {app.translator.trans('core.admin.resource.table.cancel_selection_button')}
        </Button>
      </div>,
      50
    );

    return items;
  }

  /** The bulk-scoped `resourceActions()` entries. While empty, the table has no checkbox column. */
  bulkActions(): ResourceAction<M>[] {
    return ordered(this.currentResourceActions())
      .map(([, action]) => action)
      .filter((action) => isBulkAction(action));
  }

  /** Whether any bulk action `applies()` to the record, so its checkbox is enabled. */
  isSelectable(model: M): boolean {
    return this.bulkActions().some((action) => !action.applies || action.applies(model));
  }

  /** A batch bar button per bulk-scoped `resourceActions()` entry. */
  bulkActionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    Object.entries(this.currentResourceActions().toObject()).forEach(([key, item]) => {
      const action = item.content as ResourceAction<M>;

      if (isBulkAction(action)) items.add(key, this.bulkActionButton(action), item.priority);
    });

    return items;
  }

  /** The batch bar button of a bulk action, labelled for the selection's size. */
  bulkActionButton(action: ResourceAction<M>): Mithril.Children {
    const count = this.list.selectedModels().length;
    const key = action.key.charAt(0).toUpperCase() + action.key.slice(1);

    return (
      <Button
        className={classList('Button', 'ResourceTable-batchButton', 'ResourceTable-bulkAction', `ResourceTable-bulk${key}`, action.className)}
        icon={action.icon}
        disabled={!count}
        loading={isActionRunning(this.list, action.key)}
        onclick={() => this.runAction(action, this.list.selectedModels()).catch(() => {})}
      >
        {actionLabel(action, count)}
      </Button>
    );
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
      showResult: (summary: BulkSummary<M>, action?: ResourceAction<M>) =>
        app.modal.show(() => import('./BulkResultModal'), { summary, definition: this.attrs.definition, action }),
      runAction: (action: ResourceAction<M>, models: M[]) => this.runAction(action, models),
    };
  }

  /** `bulkContext()` plus this table's `edit()` and `delete()`, for the actions' `open()`. */
  actionContext(): ResourceActionContext<M> {
    return { ...this.bulkContext(), edit: (model: M) => this.edit(model), delete: (model: M) => this.delete(model) };
  }

  /**
   * Run an action over `models`: in `ResourceActionModal` when it asks first (destructive, a
   * `confirm` or `fields`) and applies to any of them, else straight away. Resolves to the summary
   * (also shown in `BulkResultModal`), or null when cancelled or there was nothing to run for.
   */
  runAction(action: ResourceAction<M>, models: M[]): Promise<BulkSummary<M> | null> {
    if (!models.length || !action.run) return Promise.resolve(null);

    const context = this.bulkContext();
    const { applicable, skipped } = partitionByAction(action, models);

    if (!applicable.length || !needsConfirmation(action)) {
      return executeAction(action, applicable, skipped, {}, context);
    }

    return new Promise((resolve) => {
      app.modal.show(() => import('./ResourceActionModal'), { definition: this.attrs.definition, action, models, context, ondone: resolve });
    });
  }

  /**
   * The filter bar for the loaded filters, built once: its state (drafts, relation menus) lives as
   * long as the table. Override it to use a subclass.
   */
  filterBar(): ResourceFilterBar<M> {
    return new ResourceFilterBar<M>({ definition: this.attrs.definition, list: this.list, addToSearch: (filter) => this.addToSearch(filter) });
  }

  /** The text of the search box: typed, held or sent. */
  searchText(): string {
    return this.searchDraft.get('q', () => this.heldSearch ?? this.list.getQuery());
  }

  /**
   * Append a gambit to the search. A complete one (`is:suspended`) searches, debounced like typing;
   * a key (`group:`) waits in the focused search box, caret at the end, for its value.
   */
  addToSearch(filter: string): void {
    const current = this.searchText().trim();
    const q = current ? `${current} ${filter}` : filter;

    if (!filter.endsWith(':')) {
      this.heldSearch = null;
      this.searchDraft.set('q', q, (value) => this.list.setQuery(value));

      return;
    }

    this.searchDraft.discard();
    this.heldSearch = q;
    m.redraw.sync();

    const input = this.element?.querySelector<HTMLInputElement>(`#ResourceTable-search-${this.attrs.definition.type}`);

    input?.focus();
    input?.setSelectionRange(q.length, q.length);
  }

  /** Search (`search`, when the type is searchable), then the filter bar's `items()` ("Filter"). */
  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.isSearchable()) {
      items.add('search', this.searchView(), 100);
    }

    return this.bar ? items.merge(this.bar.items()) : items;
  }

  /**
   * The right of the toolbar: the running export's progress (`export`), `refresh`, the table
   * `settings` menu, the definition's `toolbar(list)` items, and `create` (when `canCreate()`) last.
   */
  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const exporting = this.csv.running;

    if (exporting) {
      items.add(
        'export',
        <Button className="Button ResourceTable-exportProgress" icon="fas fa-times" onclick={() => this.csv.cancel()}>
          {exporting.total
            ? app.translator.trans('core.admin.resource.table.export_progress_total', { count: exporting.rows, total: exporting.total })
            : app.translator.trans('core.admin.resource.table.export_progress', { count: exporting.rows })}
        </Button>,
        110
      );
    }

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

    const settings = this.settingsItems();

    if (!settings.isEmpty()) items.add('settings', this.settingsView(settings), 90);

    items.merge(this.attrs.definition.toolbar(this.list));

    if (this.attrs.definition.canCreate()) {
      items.add(
        'create',
        <Button className="Button Button--primary ResourceTable-create" icon="fas fa-plus" onclick={() => this.create()}>
          {app.translator.trans('core.admin.resource.table.create_button')}
        </Button>,
        -100
      );
    }

    return items;
  }

  /** The table settings menu: an icon button over `settingsItems()`. */
  settingsView(items: ItemList<Mithril.Children>): Mithril.Children {
    return (
      <Dropdown
        className="ResourceTable-settings"
        buttonClassName="Button Button--icon ResourceTable-settingsToggle"
        menuClassName="Dropdown-menu--right"
        icon="fas fa-sliders-h"
        caretIcon=""
        label=""
        buttonAttrs={{ title: app.translator.trans('core.admin.resource.table.settings_label', {}, true) }}
        accessibleToggleLabel={app.translator.trans('core.admin.resource.table.settings_label', {}, true)}
        helperText={null}
      >
        {items.toArray()}
      </Dropdown>
    );
  }

  /**
   * The table settings: the `columns` toggles (when a column is toggleable), `export` ("Export
   * CSV", when `definition.canExport()`) and `sort` (a sort select, shown at phone width only, where
   * the sortable headers may be scrolled away). Flat menu items, so core's phone sheet styling applies.
   */
  settingsItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const toggleable = ordered(this.columns()).filter(([, column]) => column.toggleable !== false);

    if (toggleable.length) {
      items.add('columnsSeparator', <Separator />, 51);
      items.add(
        'columnsHeader',
        Object.assign(<div className="ResourceTable-settingsHeading">{app.translator.trans('core.admin.resource.table.columns_heading')}</div>, {
          itemClassName: 'Dropdown-header',
        }),
        50
      );

      toggleable.forEach(([key, column], index) => {
        const visible = this.isColumnVisible(key, column);

        items.add(
          `column-${key}`,
          <label className="checkbox ResourceTable-columnToggle" onclick={(e: MouseEvent) => e.stopPropagation()}>
            <input
              type="checkbox"
              name={key}
              checked={visible}
              onchange={(e: Event) => this.toggleColumn(key, (e.target as HTMLInputElement).checked)}
            />
            {column.name}
          </label>,
          49 - index / 100
        );
      });
    }

    if (this.attrs.definition.canExport()) {
      items.add('exportSeparator', <Separator />, 11);
      items.add(
        'export',
        <Button className="ResourceTable-export" icon="fas fa-file-csv" disabled={!!this.csv.running} onclick={() => this.exportCsv()}>
          {app.translator.trans('core.admin.resource.table.export_button')}
        </Button>,
        10
      );
    }

    if (this.list.sorts().length) {
      items.add('sortSeparator', <Separator />, 1);
      items.add('sort', this.sortSelect(), 0);
    }

    return items;
  }

  /** Download the visible columns of every matching record; see `CsvExport`. */
  exportCsv(): Promise<void> {
    return this.csv.start(this.visibleColumns());
  }

  /** The sort select of the settings menu, for phones: the default order, then each sort both ways. */
  sortSelect(): Mithril.Children {
    const options: Record<string, string> = { '': extractText(app.translator.trans('core.admin.resource.table.sort_default')) };

    this.list.sorts().forEach((sort) => {
      const label = extractText(this.sortLabel(sort));
      options[sort] = extractText(app.translator.trans('core.admin.resource.table.sort_ascending', { label }));
      options[`-${sort}`] = extractText(app.translator.trans('core.admin.resource.table.sort_descending', { label }));
    });

    const id = `ResourceTable-sort-${this.attrs.definition.type}`;

    return Object.assign(
      <div className="ResourceTable-sortSelect" onclick={(e: MouseEvent) => e.stopPropagation()}>
        <label className="ResourceTable-settingsHeading" for={id}>
          {app.translator.trans('core.admin.resource.table.sort_label')}
        </label>
        <Select
          id={id}
          className="FormControl"
          options={options}
          value={this.list.getSort()}
          onchange={(value: string) => this.list.setSort(value)}
        />
      </div>,
      { itemClassName: 'ResourceTable-sortItem' }
    );
  }

  /** Whether the descriptor offers `filter[q]`. */
  isSearchable(): boolean {
    return !!this.attrs.definition.descriptor?.search;
  }

  /** The search box: core `Input`, inside the gambit autocomplete when the type has gambits. */
  searchView(): Mithril.Children {
    const type = this.attrs.definition.type;
    const id = `ResourceTable-search-${type}`;
    const value = this.searchText();
    const onchange = (v: string) => {
      this.heldSearch = null;
      this.searchDraft.set('q', v, (q) => this.list.setQuery(q));
    };

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
   * then `actions` (`rowActionsMenu()`).
   */
  columns(): ItemList<ColumnData<M>> {
    if (this.attrs.columns) return this.attrs.columns;

    const columns = new ItemList<ColumnData<M>>();

    Object.entries(this.fields!.toObject()).forEach(([key, item]) => {
      const field = item.content as Field<M>;

      if (!field.column) return;

      columns.add(
        key,
        { name: field.label, content: (model) => renderColumn(key, field, model), sort: field.column.sort, hidden: field.column.hidden },
        field.column.priority ?? item.priority
      );
    });

    columns.add(
      'actions',
      {
        name: <span className="visually-hidden">{app.translator.trans('core.admin.resource.table.actions_label')}</span>,
        content: (model) => this.rowActionsMenu(model),
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
    const selectable = this.bulkActions().length > 0;

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

  /** The header checkbox: selects every selectable record of the page. */
  selectAllView(): Mithril.Children {
    const selectable = (model: M) => this.isSelectable(model);
    const any = this.list.getItems().some(selectable);
    const all = this.list.isPageSelected(selectable);

    return (
      <th scope="col" className="ResourceTable-selectHeader">
        <input
          type="checkbox"
          className="ResourceTable-selectAll"
          aria-label={app.translator.trans('core.admin.resource.table.select_all_label', {}, true)}
          checked={all}
          indeterminate={!all && this.list.selectedModels().length > 0}
          disabled={!any}
          onchange={(e: Event) => this.list.setPageSelected((e.target as HTMLInputElement).checked, selectable)}
        />
      </th>
    );
  }

  /** A record's checkbox; disabled, with the reason as its title, when no bulk action applies to it. */
  selectView(model: M): Mithril.Children {
    const id = model.id()!;
    const name = this.attrs.definition.recordLabel(model);
    const enabled = this.isSelectable(model);

    return (
      <td className="ResourceTable-selectCell">
        <input
          type="checkbox"
          className="ResourceTable-select"
          aria-label={app.translator.trans('core.admin.resource.table.select_label', { name }, true)}
          title={enabled ? undefined : app.translator.trans('core.admin.resource.table.not_selectable', { name }, true)}
          checked={enabled && this.list.isSelected(id)}
          disabled={!enabled}
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
    const icon = { ascending: 'fas fa-arrow-up', descending: 'fas fa-arrow-down', none: 'fas fa-arrows-alt-v' }[direction];

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
   * The items of a record's overflow menu: a menu button per row-scoped `resourceActions()` entry
   * that applies to the record. `edit` comes first, then the others by priority, then a separator
   * and the destructive ones (their priorities offset into a band below -10000). An item an
   * extension adds at an ordinary priority lands among the non-destructive ones.
   */
  rowActionItems(model: M): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    Object.entries(this.currentResourceActions().toObject()).forEach(([key, item]) => {
      const action = item.content as ResourceAction<M>;

      if (!isRowAction(action) || (action.applies && !action.applies(model))) return;

      const priority = action.destructive ? DESTRUCTIVE_BAND + item.priority / 1000 : key === 'edit' ? 1000 + item.priority : item.priority;

      items.add(key, this.rowActionButton(action, model), priority);
    });

    // listItems() drops a separator that ends up first or last.
    items.add('destructiveSeparator', <Separator />, DESTRUCTIVE_BAND / 2);

    return items;
  }

  /**
   * The overflow menu ("⋯") of a record's actions, `rowActionItems()` unless `items` are given;
   * nothing when there are none.
   */
  rowActionsMenu(model: M, items: ItemList<Mithril.Children> = this.rowActionItems(model)): Mithril.Children {
    const entries = items.toArray().filter((item) => !(item && typeof item === 'object' && 'tag' in item && item.tag === Separator));

    if (!entries.length) return null;

    return (
      <Dropdown
        className="ResourceTable-rowMenu"
        buttonClassName="Button Button--icon Button--flat ResourceTable-rowMenuToggle"
        menuClassName="Dropdown-menu--right"
        icon="fas fa-ellipsis-h"
        caretIcon=""
        label=""
        accessibleToggleLabel={app.translator.trans(
          'core.admin.resource.table.row_actions_label',
          { name: this.attrs.definition.recordLabel(model) },
          true
        )}
        helperText={null}
      >
        {items.toArray()}
      </Dropdown>
    );
  }

  /** What clicking a row action does: its `open()` when it has one, else `runAction()` for the record. */
  triggerAction(action: ResourceAction<M>, model: M): void {
    if (action.open) action.open(model, this.actionContext());
    else this.runAction(action, [model]).catch(() => {});
  }

  /** The menu button of a row action, see `triggerAction()`; loading while it runs for the record. */
  rowActionButton(action: ResourceAction<M>, model: M): Mithril.Children {
    return (
      <Button
        className={classList(`ResourceTable-${action.key}`, action.destructive && 'ResourceTable-menuAction--danger', action.className)}
        icon={action.icon ?? 'fas fa-bolt'}
        loading={isActionRunning(this.list, action.key, model)}
        onclick={() => this.triggerAction(action, model)}
      >
        {actionLabel(action, 1)}
      </Button>
    );
  }

  /** Whether a search or a filter narrows the list. */
  isFiltered(): boolean {
    const params = this.list.getParams();

    return !!params.q || !!Object.keys(params.filter ?? {}).length;
  }

  /**
   * The row shown when the page has no records: a centred empty state, "No results" with "Clear
   * filters" while a search or filter narrows the list, else "Nothing here yet" with create.
   */
  emptyView(colspan: number): Mithril.Children {
    const filtered = this.isFiltered();

    return (
      <tr className="ResourceTable-emptyRow">
        <td colSpan={colspan} className="ResourceTable-empty">
          <div className="ResourceTable-emptyState">
            <Icon name={filtered ? 'fas fa-search' : 'far fa-folder-open'} className="ResourceTable-emptyIcon" />
            <h3 className="ResourceTable-emptyTitle">
              {filtered
                ? app.translator.trans('core.admin.resource.table.no_results_title')
                : app.translator.trans('core.admin.resource.table.empty_title')}
            </h3>
            <p className="ResourceTable-emptyDescription">
              {filtered
                ? app.translator.trans('core.admin.resource.table.no_results_description')
                : app.translator.trans('core.admin.resource.table.empty_description', { label: this.attrs.definition.label(2) })}
            </p>
            {filtered ? (
              <Button className="Button ResourceTable-emptyClear" onclick={() => this.clearSearchAndFilters()}>
                {app.translator.trans('core.admin.resource.table.clear_filters_button')}
              </Button>
            ) : (
              this.attrs.definition.canCreate() && (
                <Button className="Button Button--primary ResourceTable-emptyCreate" icon="fas fa-plus" onclick={() => this.create()}>
                  {app.translator.trans('core.admin.resource.table.create_button')}
                </Button>
              )
            )}
          </div>
        </td>
      </tr>
    );
  }

  /** Clear the search and every filter that is not required. */
  clearSearchAndFilters(): Promise<void> {
    this.searchDraft.discard();
    this.heldSearch = null;
    this.bar?.drafts.discard();

    const q = this.list.getQuery();

    return this.list
      .clearFilters()
      .then(() => (q ? this.list.setQuery('') : undefined))
      .catch(() => {});
  }

  /**
   * Core's `Pagination` when the list knows its total and has more than one page; previous/next for
   * a list without a total; nothing for a single page.
   */
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
    app.modal.show(() => import('./ResourceFormModal'), { definition: this.attrs.definition, model: null, onsaved: () => this.reload() });
  }

  /** `ResourceFormModal` for the record. */
  edit(model: M): void {
    app.modal.show(() => import('./ResourceFormModal'), { definition: this.attrs.definition, model, onsaved: () => this.reload() });
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
