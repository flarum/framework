import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import ResourceTable, { type ColumnData } from '../../../../src/admin/components/resource/ResourceTable';
import ResourceFormModal from '../../../../src/admin/components/resource/ResourceFormModal';
import BulkResultModal from '../../../../src/admin/components/resource/BulkResultModal';
import ResourceActionModal from '../../../../src/admin/components/resource/ResourceActionModal';
import ResourceDeleteModal from '../../../../src/admin/components/resource/ResourceDeleteModal';
import RequestError from '../../../../src/common/utils/RequestError';
import Model from '../../../../src/common/Model';
import ItemList from '../../../../src/common/utils/ItemList';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import type { BulkRunner, BulkSummary } from '../../../../src/common/resource/BulkRunner';
import ResourceListState from '../../../../src/common/resource/ResourceListState';
import { bootAdmin, descriptor, fd, modalClass, schemaDocument } from '../../../unit/common/resource/fixtures';

class Thing extends Model {}

/** Two sortable fields and one filter. */
const things = descriptor([fd('name', 'string', { sortable: true }), fd('price', 'number', { sortable: true }), fd('notes', 'string')], {
  sorts: ['name', 'price'],
  filters: [{ key: 'status', kind: 'enum', enum: ['draft', 'live'] }],
  rowAbilities: { edit: 'canEdit', delete: 'canDelete' },
});

/** ResourceTable's search debounce, in ms. */
const SEARCH_DELAY = 300;

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

function thing(id: string, attributes: Record<string, unknown> = {}): Thing {
  return app.store.pushObject<Thing>({ type: 'things', id, attributes: { name: `Thing ${id}`, price: Number(id), ...attributes } } as any)!;
}

function results(models: Model[], meta: Record<string, unknown> = {}) {
  return Object.assign([...models], { payload: { data: [], meta, links: {} } });
}

function lastParams(find: jest.Mock<any>): Record<string, any> {
  return (find.mock.calls.at(-1) as any)[1];
}

describe('ResourceTable', () => {
  let find: jest.Mock<any>;

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(async () => {
    clearDescriptorCache();
    window.history.replaceState(null, '', '/admin#/things');
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
    // Load the descriptor through the real store before list requests are stubbed.
    await new ResourceDefinition('things').load();
    find = jest.fn(() => Promise.resolve(results([thing('1', { canEdit: true, canDelete: true }), thing('2', { canEdit: true, canDelete: false })])));
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    app.modal.show = jest.fn() as any;
    ColumnPreferences.clear();
    window.localStorage.clear();
  });

  afterEach(() => jest.restoreAllMocks());

  async function rendered(attrs: Record<string, unknown> = {}) {
    const out = mq(ResourceTable, { definition: new ResourceDefinition<Thing>('things'), ...attrs });

    await flush();
    await flush();
    out.redraw();

    return out;
  }

  test('sortable header cells are clickable and set the sort param', async () => {
    const out = await rendered();

    expect(out).toHaveElement('th[data-column-name=name] button.ResourceTable-sort');
    expect(out).toHaveElement('th[data-column-name=price] button.ResourceTable-sort');
    expect(out).toHaveElementAttr('th[data-column-name=name]', 'aria-sort', 'none');

    out.click('th[data-column-name=price] button');
    await flush();
    out.redraw();

    expect(lastParams(find)).toMatchObject({ sort: 'price' });
    expect(out).toHaveElementAttr('th[data-column-name=price]', 'aria-sort', 'ascending');

    out.click('th[data-column-name=price] button');
    await flush();

    expect(lastParams(find)).toMatchObject({ sort: '-price' });
  });

  test('typing in search debounces, then sets filter[q]', async () => {
    jest.useFakeTimers();

    try {
      const out = mq(ResourceTable, { definition: new ResourceDefinition<Thing>('things') });
      await jest.runAllTimersAsync();
      out.redraw();
      const calls = find.mock.calls.length;

      out.setValue('input.ResourceTable-search', 'kni');
      out.setValue('input.ResourceTable-search', 'knife');
      expect(find.mock.calls.length).toBe(calls);

      await jest.runAllTimersAsync();

      expect(find.mock.calls.length).toBe(calls + 1);
      expect(lastParams(find)).toMatchObject({ filter: { q: 'knife' } });
    } finally {
      jest.useRealTimers();
    }
  });

  test('a searchable type without gambits has no search filters section in the "Filter" panel', async () => {
    const out = await rendered();

    expect(app.search.gambits.for('things')).toHaveLength(0);
    expect(out).toHaveElement('input.ResourceTable-search');
    expect(out).toHaveElement('.ResourceTable-filterPanel .ResourceTable-filterGroup--status');
    expect(out).not.toHaveElement('.ResourceTable-filterGroup--search');
    expect(out).not.toHaveElement('.ResourceTable-searchFilter');
  });

  test('a row with canDelete:false has no delete action', async () => {
    const out = await rendered();

    expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-delete');
    expect(out).toHaveElement('tr[data-id="2"] .ResourceTable-edit');
    expect(out).not.toHaveElement('tr[data-id="2"] .ResourceTable-delete');
  });

  test('passing columns replaces the inferred columns and the actions column', async () => {
    const columns = new ItemList<ColumnData<Thing>>();
    columns.add('label', { name: 'Label', content: (m) => `#${m.id()} ${m.attribute('name')}`, className: 'Thing-label' }, 20);
    columns.add('price', { name: 'Cost', content: (m) => String(m.attribute('price')), sort: 'price' }, 10);

    const out = await rendered({ columns, className: 'Things-grid' });

    expect(out).not.toHaveElement('th[data-column-name=name]');
    expect(out).toHaveElement('th[data-column-name=label]');
    expect(out).toHaveElement('th[data-column-name=price] button.ResourceTable-sort');
    expect(out).toContainRaw('#2 Thing 2');
    expect(out).toContainRaw('Cost');
    expect(out).toHaveElement('td.Thing-label[data-column-name=label]');
    expect(out).toHaveElement('.ResourceTable.Things-grid');
    expect(out).not.toHaveElement('.ResourceTable-actions');
  });

  test('rowAttrs() and containerClassName() add to the row and the container', async () => {
    class MarkedTable extends ResourceTable<Thing> {
      rowAttrs(model: Thing) {
        return { 'data-thing': model.id(), className: 'Thing-row' };
      }

      containerClassName() {
        return 'Things-grid';
      }
    }

    const out = mq(MarkedTable, { definition: new ResourceDefinition<Thing>('things') });
    await flush();
    await flush();
    out.redraw();

    expect(out).toHaveElement('.ResourceTable-container.Things-grid');
    expect(out).toHaveElement('tr.ResourceTable-row.Thing-row[data-id="1"][data-thing="1"]');
  });

  test('each cell has data-column-name set to its ItemList key', async () => {
    const out = await rendered();

    expect(out).toHaveElement('tr[data-id="1"] td[data-column-name=name]');
    expect(out).toHaveElement('tr[data-id="1"] td[data-column-name=price]');
    expect(out).not.toHaveElement('td[data-column-name=notes]');
  });

  test('create and edit open ResourceFormModal', async () => {
    class CreatableDefinition extends ResourceDefinition<Thing> {
      canCreate(): boolean {
        return true;
      }
    }

    const out = await rendered({ definition: new CreatableDefinition('things') });

    out.click('.ResourceTable-create');
    expect(await modalClass((app.modal.show as jest.Mock<any>).mock.calls.at(-1)![0])).toBe(ResourceFormModal);
    expect(app.modal.show).toHaveBeenLastCalledWith(expect.any(Function), expect.objectContaining({ model: null }));

    out.click('tr[data-id="2"] .ResourceTable-edit');
    expect(await modalClass((app.modal.show as jest.Mock<any>).mock.calls.at(-1)![0])).toBe(ResourceFormModal);
    expect(app.modal.show).toHaveBeenLastCalledWith(expect.any(Function), expect.objectContaining({ model: app.store.getById('things', '2') }));
  });

  describe('loading', () => {
    test('the first load shows no rows; a sort keeps the rows on screen until the new page arrives', async () => {
      let resolve!: (value: unknown) => void;
      find.mockImplementationOnce(() => new Promise((r) => (resolve = r)));

      const out = mq(ResourceTable, { definition: new ResourceDefinition<Thing>('things') });
      await flush();
      out.redraw();

      expect(out).toHaveElement('.ResourceTable-container--loading');
      expect(out).not.toHaveElement('tr[data-id]');
      expect(out).not.toHaveElement('.ResourceTable-empty');

      resolve(results([thing('1'), thing('2')]));
      await flush();
      out.redraw();

      expect(out).not.toHaveElement('.ResourceTable-container--loading');
      expect(out.find('tr[data-id]')).toHaveLength(2);

      find.mockImplementationOnce(() => new Promise((r) => (resolve = r)));
      out.click('th[data-column-name=price] button');
      out.redraw();

      expect(out).toHaveElement('.ResourceTable-container--loading');
      expect(out.find('tr[data-id]')).toHaveLength(2);

      resolve(results([thing('3')]));
      await flush();
      out.redraw();

      expect(out).not.toHaveElement('.ResourceTable-container--loading');
      expect(out.find('tr[data-id]')).toHaveLength(1);
      expect(out).toHaveElement('tr[data-id="3"]');
    });
  });

  describe('lifecycle', () => {
    let rejections: unknown[];
    const onRejection = (reason: unknown) => rejections.push(reason);

    beforeEach(() => {
      rejections = [];
      process.on('unhandledRejection', onRejection);
    });

    afterEach(() => {
      process.off('unhandledRejection', onRejection);
    });

    /** Render and return the component instance too. */
    async function instance(attrs: Record<string, unknown> = {}) {
      const oninit = jest.spyOn(ResourceTable.prototype, 'oninit');
      const out = await rendered(attrs);
      const table = oninit.mock.contexts[0] as ResourceTable<Thing>;
      oninit.mockRestore();

      return { out, table };
    }

    test('a failed debounced search leaves no unhandled rejection', async () => {
      const out = await rendered();

      find.mockImplementation(() => Promise.reject(new Error('500')));
      out.setValue('input.ResourceTable-search', 'knife');
      await new Promise((r) => setTimeout(r, SEARCH_DELAY + 50));
      await flush();
      out.redraw();

      expect(rejections).toEqual([]);
      expect(lastParams(find)).toMatchObject({ filter: { q: 'knife' } });
    });

    test('onremove clears the pending search', async () => {
      const { out, table } = await instance();
      const calls = find.mock.calls.length;

      out.setValue('input.ResourceTable-search', 'knife');
      table.onremove({} as any);
      await new Promise((r) => setTimeout(r, SEARCH_DELAY + 50));

      expect(find.mock.calls.length).toBe(calls);
    });

    test('removing the table aborts a running export', async () => {
      const { table } = await instance();

      find.mockImplementation(() => new Promise(() => {}));
      table.exportCsv();

      const controller = table.csv.running!.controller;

      table.onremove({} as any);

      expect(controller.signal.aborted).toBe(true);
    });

    test('a running export shows its progress in the toolbar and cancels from there', async () => {
      const { out, table } = await instance();

      find.mockImplementation(() => new Promise(() => {}));
      table.exportCsv();
      out.redraw();

      expect(out).toHaveElement('.ResourceTable-toolbarActions .ResourceTable-exportProgress');
      expect(out).toHaveElement('.ResourceTable-export[disabled]');

      const controller = table.csv.running!.controller;
      out.click('.ResourceTable-exportProgress');

      expect(controller.signal.aborted).toBe(true);
    });

    test('a failed export shows the export_error alert and clears the progress', async () => {
      const { out, table } = await instance();
      const show = jest.spyOn(app.alerts, 'show').mockImplementation(() => 0 as any);
      jest.spyOn(console, 'error').mockImplementation(() => {});

      find.mockImplementation(() => Promise.reject(new Error('500')));
      const pending = table.exportCsv();
      out.redraw();

      expect(out).toHaveElement('.ResourceTable-exportProgress');

      await pending;
      out.redraw();

      expect(show).toHaveBeenCalledWith({ type: 'error' }, expect.anything());
      expect(show.mock.calls.at(-1)![1]).toEqual(app.translator.trans('core.admin.resource.table.export_error'));
      expect(table.csv.running).toBeNull();
      expect(out).not.toHaveElement('.ResourceTable-exportProgress');
    });
  });

  describe('column toggles', () => {
    function toggle(out: any, key: string, visible: boolean) {
      const selector = `.ResourceTable-columnToggle input[name="${key}"]`;
      out.first(selector).checked = visible;
      out.trigger(selector, 'change');
    }

    function customColumns(): ItemList<ColumnData<Thing>> {
      const columns = new ItemList<ColumnData<Thing>>();
      columns.add('label', { name: 'Label', content: (m) => `#${m.id()}`, toggleable: false }, 30);
      columns.add('name', { name: 'Name', content: (m) => String(m.attribute('name')) }, 20);
      columns.add('price', { name: 'Price', content: (m) => String(m.attribute('price')), hidden: true }, 10);

      return columns;
    }

    test('toggling a column off hides its header and cells and persists the choice', async () => {
      const out = await rendered();

      expect(out).toHaveElement('.ResourceTable-columnToggle input[name="price"]:checked');

      toggle(out, 'price', false);

      expect(out).not.toHaveElement('th[data-column-name=price]');
      expect(out).not.toHaveElement('td[data-column-name=price]');
      expect(out).toHaveElement('td[data-column-name=name]');
      expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="price"]:checked');
      expect(JSON.parse(window.localStorage.getItem('flarum.resourceTable.things.columns')!)).toEqual({ price: false });

      toggle(out, 'price', true);

      expect(out).toHaveElement('td[data-column-name=price]');
      expect(ColumnPreferences.load('things')).toEqual(new Map([['price', true]]));
    });

    test('a column with toggleable:false has no checkbox', async () => {
      const out = await rendered({ columns: customColumns() });

      expect(out).toHaveElement('th[data-column-name=label]');
      expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="label"]');
      expect(out).toHaveElement('.ResourceTable-columnToggle input[name="name"]');
    });

    test('a hidden:true column starts hidden', async () => {
      const out = await rendered({ columns: customColumns() });

      expect(out).not.toHaveElement('th[data-column-name=price]');
      expect(out).not.toHaveElement('td[data-column-name=price]');
      expect(out).toHaveElement('.ResourceTable-columnToggle input[name="price"]');
      expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="price"]:checked');

      toggle(out, 'price', true);

      expect(out).toHaveElement('td[data-column-name=price]');
      expect(ColumnPreferences.load('things')).toEqual(new Map([['price', true]]));
    });

    test('a hidden:true column added after the user made a choice starts hidden', async () => {
      // The user hid `name` while the table had no `price` column.
      ColumnPreferences.save('things', new Map([['name', false]]));

      const out = await rendered({ columns: customColumns() });

      expect(out).not.toHaveElement('th[data-column-name=name]');
      expect(out).not.toHaveElement('th[data-column-name=price]');
      expect(out).toHaveElement('th[data-column-name=label]');
      expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="price"]:checked');
    });

    test('a stored key for a removed column is ignored', async () => {
      ColumnPreferences.save(
        'things',
        new Map([
          ['gone', false],
          ['price', false],
          ['label', false],
        ])
      );

      const out = await rendered({ columns: customColumns() });

      expect(out).not.toHaveElement('th[data-column-name=gone]');
      expect(out).not.toHaveElement('th[data-column-name=price]');
      // Not toggleable, so a stored choice cannot hide it.
      expect(out).toHaveElement('th[data-column-name=label]');
      expect(out).toHaveElement('th[data-column-name=name]');

      toggle(out, 'name', false);

      // Ignored, not deleted: the stale and non-toggleable keys stay stored.
      expect(ColumnPreferences.load('things')).toEqual(
        new Map([
          ['gone', false],
          ['price', false],
          ['label', false],
          ['name', false],
        ])
      );
    });

    test("a column that is absent for a while keeps the user's choice", async () => {
      // The user hid `name`; then its extension was disabled, so the table has no `name` column.
      ColumnPreferences.save('things', new Map([['name', false]]));

      const out = await rendered({ columns: customColumns().remove('name') });

      toggle(out, 'price', true);

      expect(ColumnPreferences.load('things')).toEqual(
        new Map([
          ['name', false],
          ['price', true],
        ])
      );

      // The extension is back: `name` is still hidden, as the user left it.
      const back = await rendered({ columns: customColumns() });

      expect(back).not.toHaveElement('th[data-column-name=name]');
      expect(back).toHaveElement('th[data-column-name=price]');
    });
  });

  test('the actions column is the last column, not toggleable', async () => {
    const out = await rendered();

    expect(out).toHaveElement('th.ResourceTable-actions[data-column-name=actions] .visually-hidden');
    expect(out).toHaveElement('tr[data-id="1"] td.ResourceTable-actions[data-column-name=actions] .ResourceTable-edit');
    expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="actions"]');
  });

  describe('export', () => {
    test('the export button follows canExport()', async () => {
      expect(await rendered()).toHaveElement('.ResourceTable-export');

      class NoExport extends ResourceDefinition<Thing> {
        canExport(): boolean {
          return false;
        }
      }

      expect(await rendered({ definition: new NoExport('things') })).not.toHaveElement('.ResourceTable-export');
    });
  });

  describe('bulk select', () => {
    class BulkDefinition extends ResourceDefinition<Thing> {
      resourceActions() {
        return super
          .resourceActions()
          .add('archive', { key: 'archive', label: 'Archive', className: 'Things-archive', scope: 'bulk', run: () => Promise.resolve() });
      }
    }

    /** Offers the built-in delete on the selection too. */
    class DeleteDefinition extends ResourceDefinition<Thing> {
      resourceActions() {
        const items = super.resourceActions();

        items.setContent('delete', { ...items.get('delete'), scope: 'both' });

        return items;
      }
    }

    function check(out: any, selector: string, checked: boolean) {
      out.first(selector).checked = checked;
      out.trigger(selector, 'change');
    }

    /** Render the last `ResourceActionModal` shown and submit it. */
    async function confirmAction() {
      const [modal, attrs] = (app.modal.show as jest.Mock).mock.calls.at(-1) as [any, any];

      expect(await modalClass(modal)).toBe(ResourceActionModal);

      const out = mq(ResourceActionModal, { state: {}, animateShow: () => {}, animateHide: jest.fn(), ...attrs });
      out.trigger('form', 'submit', {} as Event);
      await flush();
      await flush();

      return out;
    }

    test('with no bulk actions there is no checkbox column', async () => {
      const out = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-selectAll');
      expect(out).not.toHaveElement('.ResourceTable-select');
      expect(out).not.toHaveElement('.ResourceTable-batchBar');
    });

    test('with one bulk action there is a checkbox per row', async () => {
      const out = await rendered({ definition: new BulkDefinition('things') });

      expect(out).toHaveElement('th .ResourceTable-selectAll');
      expect(out).toHaveElement('tr[data-id="1"] input.ResourceTable-select');
      expect(out).toHaveElement('tr[data-id="2"] input.ResourceTable-select');
      expect(out).not.toHaveElement('.ResourceTable-batchBar');
    });

    test('select-all selects the current page only', async () => {
      const definition = new BulkDefinition('things');
      const state = new ResourceListState<Thing>(definition);
      const out = await rendered({ definition, state });

      // Another page's record, already in the store, is not part of the page.
      thing('9');

      check(out, '.ResourceTable-selectAll', true);

      expect([...state.selection.ids].sort()).toEqual(['1', '2']);
      expect(out).toHaveElement('tr[data-id="1"] input.ResourceTable-select:checked');
      expect(out).toHaveElement('tr[data-id="2"] input.ResourceTable-select:checked');

      check(out, 'tr[data-id="2"] input.ResourceTable-select', false);

      expect([...state.selection.ids]).toEqual(['1']);
      expect(out).not.toHaveElement('.ResourceTable-selectAll:checked');

      check(out, '.ResourceTable-selectAll', false);

      expect(state.selection.ids.size).toBe(0);
    });

    test('changing page clears the selection', async () => {
      find.mockImplementation(() =>
        Promise.resolve(Object.assign([thing('1'), thing('2')], { payload: { data: [], meta: {}, links: { next: 'https://example.test/next' } } }))
      );

      const definition = new BulkDefinition('things');
      const state = new ResourceListState<Thing>(definition);
      const out = await rendered({ definition, state });

      check(out, 'tr[data-id="1"] input.ResourceTable-select', true);
      expect(state.selection.ids.size).toBe(1);

      out.click('.ResourceTable-next');
      await flush();
      out.redraw();

      expect(state.currentPage()).toBe(2);
      expect(state.selection.ids.size).toBe(0);
      expect(out).not.toHaveElement('input.ResourceTable-select:checked');
    });

    test('the bulk actions are built once per redraw', async () => {
      const definition = new BulkDefinition('things');
      const resourceActions = jest.spyOn(definition, 'resourceActions');
      const out = await rendered({ definition });

      resourceActions.mockClear();
      out.redraw();

      expect(resourceActions).toHaveBeenCalledTimes(1);
    });

    test("the table's bulkRunner() runs its bulk actions", async () => {
      const runner: BulkRunner = { run: jest.fn(() => Promise.resolve({ ok: [], failed: [] })) as any };

      class RunnerTable extends ResourceTable<Thing> {
        bulkRunner(): BulkRunner {
          return runner;
        }
      }

      const out = mq(RunnerTable, { definition: new BulkDefinition('things') });
      await flush();
      await flush();
      out.redraw();

      check(out, 'tr[data-id="1"] input.ResourceTable-select', true);
      out.click('.ResourceTable-batchBar .Things-archive');
      await flush();

      expect(runner.run).toHaveBeenCalledTimes(1);
    });

    test('a bulk delete whose reload fails leaves no unhandled rejection', async () => {
      const rejections: unknown[] = [];
      const onRejection = (reason: unknown) => rejections.push(reason);
      process.on('unhandledRejection', onRejection);

      try {
        const out = await rendered({ definition: new DeleteDefinition('things') });
        app.request = jest.fn(() => Promise.resolve()) as any;
        find.mockImplementation(() => Promise.reject(new Error('500')));

        check(out, 'tr[data-id="1"] input.ResourceTable-select', true);
        out.click('.ResourceTable-batchBar .ResourceTable-bulkDelete');
        await confirmAction();
        await flush();
        await new Promise((r) => setTimeout(r, 10));

        expect(await modalClass((app.modal.show as jest.Mock<any>).mock.calls.at(-1)![0])).toBe(BulkResultModal);
        expect(app.modal.show).toHaveBeenCalledWith(expect.any(Function), expect.anything());
        expect(rejections).toEqual([]);
      } finally {
        process.off('unhandledRejection', onRejection);
      }
    });

    test('select-all picks the deletable rows only, and a bulk delete deletes them', async () => {
      find.mockImplementation(() =>
        Promise.resolve(results([thing('1', { canDelete: true }), thing('2', { canDelete: false }), thing('3', { canDelete: true, name: 'Third' })]))
      );

      const out = await rendered({ definition: new DeleteDefinition('things') });
      const request = jest.fn((_options: any) => Promise.resolve());
      app.request = request as any;
      const confirm = jest.spyOn(window, 'confirm');

      check(out, '.ResourceTable-selectAll', true);

      expect(out).toHaveElement('tr[data-id="2"] input.ResourceTable-select[disabled]');
      expect(out).not.toHaveElement('tr[data-id="2"] input.ResourceTable-select:checked');
      expect(out).toHaveElement('.ResourceTable-selectAll:checked');

      out.click('.ResourceTable-batchBar .ResourceTable-bulkDelete');

      // The confirmation is a modal counting the deletable rows, never the native dialog.
      const dialog = await confirmAction();

      expect(dialog).toContainRaw('Delete 2 entries?');
      expect(confirm).not.toHaveBeenCalled();

      const deletes = request.mock.calls.map(([options]) => options).filter((options) => options.method === 'DELETE');
      expect(deletes.map((options) => options.url).sort()).toEqual(['https://example.test/api/things/1', 'https://example.test/api/things/3']);

      expect(await modalClass((app.modal.show as jest.Mock<any>).mock.calls.at(-1)![0])).toBe(BulkResultModal);
      expect(app.modal.show).toHaveBeenCalledWith(expect.any(Function), expect.objectContaining({ summary: expect.anything() }));

      const summary = (app.modal.show as jest.Mock).mock.calls.at(-1)![1] as { summary: BulkSummary<Thing> };
      expect(summary.summary.ok.map((m) => m.id())).toEqual(['1', '3']);
      expect(summary.summary.failed).toEqual([]);
      expect(summary.summary.skipped).toEqual([]);

      const modal = mq(BulkResultModal, { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...summary });

      expect(modal).toHaveElementAttr('.BulkResultModal-deleted', 'data-count', '2');
      expect(modal).not.toHaveElement('.BulkResultModal-skipped');
      expect(modal).not.toHaveElement('.BulkResultModal-failed');

      // The list was refreshed afterwards.
      expect(find.mock.calls.length).toBeGreaterThan(1);
    });
  });
  describe('layout', () => {
    class ArchiveDefinition extends ResourceDefinition<Thing> {
      resourceActions() {
        return super
          .resourceActions()
          .add('archive', { key: 'archive', label: 'Archive', icon: 'fas fa-archive', scope: 'bulk', run: () => Promise.resolve() });
      }
    }

    function check(out: any, selector: string, checked: boolean) {
      out.first(selector).checked = checked;
      out.trigger(selector, 'change');
    }

    test('selecting rows replaces the toolbar with the batch bar in place; Cancel brings the toolbar back', async () => {
      const out = await rendered({ definition: new ArchiveDefinition('things') });

      expect(out.find('.ResourceTable-toolbar')).toHaveLength(1);
      expect(out).not.toHaveElement('.ResourceTable-batchBar');
      expect(out.first('.ResourceTable-status[aria-live=polite]').textContent).toBe('');

      check(out, 'tr[data-id="1"] input.ResourceTable-select', true);
      check(out, 'tr[data-id="2"] input.ResourceTable-select', true);

      expect(out.find('.ResourceTable-toolbar')).toHaveLength(1);
      expect(out).toHaveElement('.ResourceTable-toolbar.ResourceTable-batchBar[role=group]');
      expect(out).not.toHaveElement('.ResourceTable-toolbarView');
      expect(out.first('.ResourceTable-batchCount').textContent).toBe('2 items selected');
      expect(out.first('.ResourceTable-status').textContent).toBe('2 items selected');
      expect(out).toHaveElement('.ResourceTable-batchActions .ResourceTable-bulkArchive.ResourceTable-batchButton');
      expect(out).toHaveElement('.ResourceTable-batchActions .ResourceTable-batchDivider + .ResourceTable-batchCancel');

      out.click('.ResourceTable-batchCancel');

      expect(out).not.toHaveElement('.ResourceTable-batchBar');
      expect(out).toHaveElement('.ResourceTable-toolbarView');
      expect(out).not.toHaveElement('input.ResourceTable-select:checked');
    });

    test('every row has one labelled overflow menu: edit first, then a separator and the destructive delete', async () => {
      const out = await rendered();

      expect(out.find('tr[data-id="1"] .ResourceTable-rowMenu')).toHaveLength(1);
      expect(out).toHaveElementAttr('tr[data-id="1"] .ResourceTable-rowMenu .Dropdown-toggle', 'aria-label', 'Actions for Thing 1');
      expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-rowMenu .Dropdown-toggle .fa-ellipsis-h');
      expect(out.find('tr[data-id="1"] .ResourceTable-rowMenu .Dropdown-menu > li').map((li: any) => li.className)).toEqual([
        'item-edit',
        'Dropdown-separator',
        'item-delete',
      ]);
      expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-delete.ResourceTable-menuAction--danger');
      // Row 2 may only be edited: no separator left over.
      expect(out).not.toHaveElement('tr[data-id="2"] .Dropdown-separator');
    });

    test('a row with no applicable action has an empty actions cell', async () => {
      find.mockImplementation(() => Promise.resolve(results([thing('1', { canEdit: false, canDelete: false })])));

      const out = await rendered();

      expect(out).toHaveElement('tr[data-id="1"] td[data-column-name=actions]');
      expect(out).not.toHaveElement('tr[data-id="1"] .ResourceTable-rowMenu');
    });

    test("a list with a total of several pages shows core's Pagination, which navigates", async () => {
      find.mockImplementation(() => Promise.resolve(results([thing('1'), thing('2')], { page: { total: 45 } })));
      window.history.replaceState(null, '', '/admin#/things?page=2');

      const definition = new ResourceDefinition<Thing>('things');
      const state = new ResourceListState<Thing>(definition, { pageSize: 20 });
      const out = await rendered({ definition, state });

      expect(out).toHaveElement('.ResourceTable-pagination .Pagination');
      expect(out).not.toHaveElement('.ResourceTable-prevNext');
      expect(out).toHaveElementAttr('.Pagination-input', 'value', '2');
      expect(out.first('.Pagination-pageNumber').textContent).toContain('of 3');

      out.click('.Pagination-next');
      await flush();

      expect(state.currentPage()).toBe(3);
      expect(lastParams(find)).toMatchObject({ page: { offset: 40, limit: 20 } });
    });

    test('a list without a total shows previous/next', async () => {
      find.mockImplementation(() =>
        Promise.resolve(Object.assign([thing('1'), thing('2')], { payload: { data: [], meta: {}, links: { next: 'https://example.test/next' } } }))
      );

      const out = await rendered();

      expect(out).toHaveElement('.ResourceTable-pagination.ResourceTable-prevNext');
      expect(out).not.toHaveElement('.Pagination');
      expect(out).toHaveElement('.ResourceTable-prev[disabled]');
      expect(out).not.toHaveElement('.ResourceTable-next[disabled]');
      expect(out.first('.ResourceTable-page').textContent).toBe('Page 1');
    });

    test('a single page shows no pagination', async () => {
      find.mockImplementation(() => Promise.resolve(results([thing('1'), thing('2')], { page: { total: 2 } })));

      const out = await rendered();

      expect(out).toHaveElement('tr[data-id="1"]');
      expect(out).not.toHaveElement('.ResourceTable-pagination');
    });

    test('the empty state offers create; with a search it offers "Clear filters", which clears the search', async () => {
      find.mockImplementation(() => Promise.resolve(results([], { page: { total: 0 } })));

      const empty = await rendered();

      expect(empty).toHaveElement('.ResourceTable-emptyState');
      expect(empty).toContainRaw('Nothing here yet');
      expect(empty).toHaveElement('.ResourceTable-emptyState .ResourceTable-emptyCreate');
      expect(empty).not.toHaveElement('.ResourceTable-pagination');

      window.history.replaceState(null, '', '/admin#/things?q=nothing');
      const filtered = await rendered();

      expect(filtered).toContainRaw('No results');
      expect(filtered).not.toHaveElement('.ResourceTable-emptyCreate');

      filtered.click('.ResourceTable-emptyClear');
      await flush();
      await flush();

      expect(lastParams(find).filter ?? {}).not.toHaveProperty('q');
    });
  });

  describe('declarative actions', () => {
    const abilities = descriptor([fd('name', 'string'), fd('price', 'number')], { rowAbilities: { edit: 'mayEdit', delete: 'mayDelete' } });

    /** Offers the built-in delete on the selection too. */
    class BulkDeleteDefinition extends ResourceDefinition<Thing> {
      resourceActions() {
        const items = super.resourceActions();

        items.setContent('delete', { ...items.get('delete'), scope: 'both' });

        return items;
      }
    }

    async function withAbilities(definition: ResourceDefinition<Thing> = new BulkDeleteDefinition('things')) {
      clearDescriptorCache();
      app.request = jest.fn(() => Promise.resolve(schemaDocument(abilities))) as any;
      // The descriptor loads through the real store.
      (app.store.find as any).mockRestore();
      await new ResourceDefinition('things').load();
      jest.spyOn(app.store, 'find').mockImplementation(find as any);
      find.mockImplementation(() =>
        Promise.resolve(
          results([
            thing('1', { mayEdit: true, mayDelete: true, canEdit: false, canDelete: false }),
            thing('2', { mayEdit: false, mayDelete: false, canEdit: true, canDelete: true }),
          ])
        )
      );

      return rendered({ definition });
    }

    function lastModal(): [any, any] {
      return (app.modal.show as jest.Mock).mock.calls.at(-1) as [any, any];
    }

    function selectAll(out: any) {
      out.first('.ResourceTable-selectAll').checked = true;
      out.trigger('.ResourceTable-selectAll', 'change');
    }

    test('the built-in delete is on rows only by default: no checkbox column', async () => {
      const out = await withAbilities(new ResourceDefinition<Thing>('things'));

      expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-delete');
      expect(out).not.toHaveElement('th .ResourceTable-selectAll');
      expect(out).not.toHaveElement('.ResourceTable-bulkDelete');
    });

    test('a row action running for a record shows loading on that row only and ignores a second click', async () => {
      let finish: () => void = () => {};
      const run = jest.fn(() => new Promise<void>((resolve) => (finish = resolve)));

      class PingDefinition extends ResourceDefinition<Thing> {
        resourceActions() {
          return super.resourceActions().add('ping', { key: 'ping', label: 'Ping', scope: 'row', run });
        }
      }

      const out = await withAbilities(new PingDefinition('things'));

      out.click('tr[data-id="1"] .ResourceTable-ping');
      out.redraw();

      expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-ping.loading');
      expect(out).not.toHaveElement('tr[data-id="2"] .ResourceTable-ping.loading');

      out.click('tr[data-id="1"] .ResourceTable-ping');
      expect(run).toHaveBeenCalledTimes(1);

      finish();
      await flush();
      out.redraw();

      expect(out).not.toHaveElement('tr[data-id="1"] .ResourceTable-ping.loading');
    });

    test('ResourceActionModal builds the form inputs once, not per redraw', () => {
      const definition = new ResourceDefinition<Thing>('things');
      const formInputs = jest.spyOn(definition, 'formInputs');
      const action = {
        key: 'tag',
        label: 'Tag',
        fields: () => new ItemList<any>().add('note', { label: 'Note', input: { type: 'text' } }),
        run: () => Promise.resolve(),
      };
      const dialog = mq(ResourceActionModal, {
        state: {},
        animateShow: () => {},
        animateHide: jest.fn(),
        definition,
        action,
        models: [thing('1', {})],
        context: { list: {}, runner: {}, showResult: () => {} },
      });

      dialog.redraw();
      dialog.redraw();

      expect(dialog).toHaveElement('input[name=note]');
      expect(formInputs).toHaveBeenCalledTimes(1);
    });

    test('without rowAbilities there is no edit or delete action', async () => {
      clearDescriptorCache();
      app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([fd('name', 'string')])))) as any;
      (app.store.find as any).mockRestore();
      await new ResourceDefinition('things').load();
      jest.spyOn(app.store, 'find').mockImplementation(find as any);

      const out = await rendered({ definition: new ResourceDefinition<Thing>('things') });

      expect(out).toHaveElement('tr[data-id="1"]');
      expect(out).not.toHaveElement('.ResourceTable-edit');
      expect(out).not.toHaveElement('.ResourceTable-delete');
      expect(out).not.toHaveElement('th .ResourceTable-selectAll');
    });

    test('edit and delete follow rowAbilities, not canEdit/canDelete; a bulk delete adds the checkbox column', async () => {
      const out = await withAbilities();

      expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-edit');
      expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-delete');
      expect(out).not.toHaveElement('tr[data-id="2"] .ResourceTable-edit');
      expect(out).not.toHaveElement('tr[data-id="2"] .ResourceTable-delete');
      expect(out).toHaveElement('th .ResourceTable-selectAll');
      // No bulk action applies to row 2, so its checkbox is disabled.
      expect(out).toHaveElement('tr[data-id="2"] input.ResourceTable-select[disabled]');
      expect(out).toHaveElementAttr(
        'tr[data-id="2"] input.ResourceTable-select',
        'title',
        app.translator.trans('core.admin.resource.table.not_selectable', { name: 'Thing 2' }, true)
      );

      selectAll(out);

      expect(out).toHaveElement('.ResourceTable-batchBar .ResourceTable-bulkDelete');
    });

    test('a row delete opens the delete modal; a bulk delete confirms in ResourceActionModal; select-all leaves the other row out', async () => {
      const out = await withAbilities();

      out.click('tr[data-id="1"] .ResourceTable-delete');
      expect(lastModal()[0]).toBe(ResourceDeleteModal);

      selectAll(out);
      out.click('.ResourceTable-batchBar .ResourceTable-bulkDelete');

      const [modal, attrs] = lastModal();
      expect(await modalClass(modal)).toBe(ResourceActionModal);

      const dialog = mq(ResourceActionModal, { state: {}, animateShow: () => {}, animateHide: jest.fn(), ...attrs });
      expect(dialog).toContainRaw('Delete 1 entry?');
      expect(attrs.models.map((m: Thing) => m.id())).toEqual(['1']);
      expect(dialog).not.toHaveElement('.ResourceActionModal-skipped');
    });

    test("a custom action's fields are collected in the modal and passed to run()", async () => {
      const run = jest.fn((_model: Thing, _values: Record<string, unknown>) => Promise.resolve());
      let tagStream: ((value?: unknown) => unknown) | null = null;

      class TagDefinition extends ResourceDefinition<Thing> {
        resourceActions() {
          const items = super.resourceActions();

          items.add('tag', {
            key: 'tag',
            label: 'Tag',
            icon: 'fas fa-tag',
            applies: (model: Thing) => model.id() === '1',
            fields: () =>
              new ItemList<any>()
                .add('note', { label: 'Note', input: { type: 'text' }, value: () => 'none' })
                .add('tag', { label: 'Tag name', input: (ctx: any) => ((tagStream = ctx.stream), m('span.TagInput')) }),
            run,
          });

          return items;
        }
      }

      const out = await withAbilities(new TagDefinition('things'));

      // A both-scoped action without open() runs through the modal from a row too.
      expect(out).toHaveElement('tr[data-id="1"] .ResourceTable-tag');
      expect(out).not.toHaveElement('tr[data-id="2"] .ResourceTable-tag');

      selectAll(out);
      out.click('.ResourceTable-batchBar .ResourceTable-bulkTag');

      const [, attrs] = lastModal();
      const done = jest.fn();
      const dialog = mq(ResourceActionModal, { state: {}, animateShow: () => {}, animateHide: jest.fn(), ...attrs, ondone: done });

      // The spec input renders through the definition's form inputs; the function input gets its stream.
      expect(dialog).toHaveElement('.ResourceActionModal-field--note input');
      expect(dialog).toHaveElement('.ResourceActionModal-field--tag .TagInput');
      tagStream!('red');
      dialog.trigger('form', 'submit', {} as Event);
      await flush();
      await flush();

      expect(run).toHaveBeenCalledTimes(1);
      expect(run.mock.calls[0][0].id()).toBe('1');
      expect(run.mock.calls[0][1]).toEqual({ note: 'none', tag: 'red' });

      const [result, resultAttrs] = lastModal();
      expect(await modalClass(result)).toBe(BulkResultModal);
      expect(resultAttrs.action.key).toBe('tag');
      expect(resultAttrs.summary.skipped).toEqual([]);
      expect(done).toHaveBeenCalledWith(resultAttrs.summary);
    });

    test('a non-destructive action without confirm or fields runs without a modal', async () => {
      const run = jest.fn(() => Promise.resolve());

      class TouchDefinition extends ResourceDefinition<Thing> {
        resourceActions() {
          return super.resourceActions().add('touch', { key: 'touch', label: 'Touch', scope: 'bulk' as const, run });
        }
      }

      const out = await withAbilities(new TouchDefinition('things'));

      expect(out).not.toHaveElement('tr .ResourceTable-touch');

      selectAll(out);
      out.click('.ResourceTable-batchBar .ResourceTable-bulkTouch');
      await flush();

      expect(run).toHaveBeenCalledTimes(2);
      expect(await modalClass(lastModal()[0])).toBe(BulkResultModal);
    });

    test('BulkResultModal groups failures by error code, then detail, one line each with the records', () => {
      const error = (errors: unknown[]) => new RequestError(422, JSON.stringify({ errors }), {} as any, {} as any);
      const [a, b, c, d] = ['11', '12', '13', '14'].map((id) => thing(id, { name: `Thing ${id}` }));
      const summary = {
        ok: [],
        skipped: [],
        failed: [
          { model: a, error: error([{ code: 'permission_denied', detail: 'You may not.' }]) },
          { model: b, error: error([{ code: 'permission_denied', detail: 'You may not.' }]) },
          { model: c, error: error([{ detail: 'Locked.' }]) },
          { model: d, error: new Error('network') },
        ],
      };

      const modal = mq(BulkResultModal, {
        state: {},
        animateShow: () => {},
        animateHide: jest.fn(),
        summary,
        action: { key: 'tag', label: 'Tag', run: async () => {} },
      });

      expect(modal.find('.BulkResultModal-failed details').length).toBe(3);
      expect(modal).toHaveElementAttr('details[data-key=permission_denied]', 'data-count', '2');
      expect(modal).toContainRaw('2 entries not processed: You may not.');
      expect(modal).toContainRaw('1 entry not processed: Locked.');
      expect(modal).toHaveElement('details[data-key=unknown] li[data-id="14"]');
      expect(modal).toContainRaw('Thing 12');
    });
  });
});
