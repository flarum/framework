import { jest } from '@jest/globals';
import { bulkDeleteAction } from '../../../../src/common/resource/bulkDelete';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import ResourceTable, { type ColumnData } from '../../../../src/admin/components/resource/ResourceTable';
import ExportCsvButton from '../../../../src/admin/components/resource/ExportCsvButton';
import ResourceFormModal from '../../../../src/admin/components/resource/ResourceFormModal';
import BulkResultModal from '../../../../src/admin/components/resource/BulkResultModal';
import Button from '../../../../src/common/components/Button';
import Model from '../../../../src/common/Model';
import ItemList from '../../../../src/common/utils/ItemList';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import type { BulkActionContext, BulkSummary } from '../../../../src/common/resource/BulkRunner';
import ResourceListState, { type ResourceSelection } from '../../../../src/common/resource/ResourceListState';
import { bootAdmin, descriptor, fd, schemaDocument } from '../../../unit/common/resource/fixtures';

class Thing extends Model {}

/** Two sortable fields and one filter. */
const things = descriptor([fd('name', 'string', { sortable: true }), fd('price', 'number', { sortable: true }), fd('notes', 'string')], {
  sorts: ['name', 'price'],
  filters: [{ key: 'status', kind: 'enum', enum: ['draft', 'live'] }],
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
    expect(app.modal.show).toHaveBeenLastCalledWith(ResourceFormModal, expect.objectContaining({ model: null }));

    out.click('tr[data-id="2"] .ResourceTable-edit');
    expect(app.modal.show).toHaveBeenLastCalledWith(ResourceFormModal, expect.objectContaining({ model: app.store.getById('things', '2') }));
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

    /** An `ExportCsvButton` over the table's list and visible columns. */
    async function exportButton() {
      const { table } = await instance();
      const oninit = jest.spyOn(ExportCsvButton.prototype, 'oninit');
      const out = mq(ExportCsvButton, { list: table.list, columns: () => table.visibleColumns() });
      const button = oninit.mock.contexts[0] as ExportCsvButton<Thing>;
      oninit.mockRestore();

      return { out, button };
    }

    test('removing the export button aborts a running export', async () => {
      const { button } = await exportButton();

      find.mockImplementation(() => new Promise(() => {}));
      button.export();

      const controller = button.exporting!.controller;

      button.onremove({} as any);

      expect(controller.signal.aborted).toBe(true);
    });

    test('a failed export shows the export_error alert and clears exporting', async () => {
      const { out, button } = await exportButton();
      const show = jest.spyOn(app.alerts, 'show').mockImplementation(() => 0 as any);
      jest.spyOn(console, 'error').mockImplementation(() => {});

      find.mockImplementation(() => Promise.reject(new Error('500')));
      const pending = button.export();
      out.redraw();

      expect(out).toHaveElement('.ResourceTable-export--running');

      await pending;
      out.redraw();

      expect(show).toHaveBeenCalledWith({ type: 'error' }, expect.anything());
      expect(show.mock.calls.at(-1)![1]).toEqual(app.translator.trans('core.admin.resource.table.export_error'));
      expect(button.exporting).toBeNull();
      expect(out).not.toHaveElement('.ResourceTable-export--running');
    });
  });

  describe('column toggles', () => {
    function toggle(out: any, key: string, visible: boolean) {
      const selector = `.ResourceTable-columns input[name="${key}"]`;
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

      expect(out).toHaveElement('.ResourceTable-columns input[name="price"]:checked');

      toggle(out, 'price', false);

      expect(out).not.toHaveElement('th[data-column-name=price]');
      expect(out).not.toHaveElement('td[data-column-name=price]');
      expect(out).toHaveElement('td[data-column-name=name]');
      expect(out).not.toHaveElement('.ResourceTable-columns input[name="price"]:checked');
      expect(JSON.parse(window.localStorage.getItem('flarum.resourceTable.things.columns')!)).toEqual({ price: false });

      toggle(out, 'price', true);

      expect(out).toHaveElement('td[data-column-name=price]');
      expect(ColumnPreferences.load('things')).toEqual(new Map([['price', true]]));
    });

    test('a column with toggleable:false has no checkbox', async () => {
      const out = await rendered({ columns: customColumns() });

      expect(out).toHaveElement('th[data-column-name=label]');
      expect(out).not.toHaveElement('.ResourceTable-columns input[name="label"]');
      expect(out).toHaveElement('.ResourceTable-columns input[name="name"]');
    });

    test('a hidden:true column starts hidden', async () => {
      const out = await rendered({ columns: customColumns() });

      expect(out).not.toHaveElement('th[data-column-name=price]');
      expect(out).not.toHaveElement('td[data-column-name=price]');
      expect(out).toHaveElement('.ResourceTable-columns input[name="price"]');
      expect(out).not.toHaveElement('.ResourceTable-columns input[name="price"]:checked');

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
      expect(out).not.toHaveElement('.ResourceTable-columns input[name="price"]:checked');
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

      // Ignored, not deleted (spec D11): the stale and non-toggleable keys stay stored.
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
    expect(out).not.toHaveElement('.ResourceTable-columns input[name="actions"]');
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
      bulkActions(selection: ResourceSelection, context: BulkActionContext<Thing>) {
        const items = super.bulkActions(selection, context);

        items.add('archive', m(Button, { className: 'Button Things-archive' }, 'Archive'));

        return items;
      }
    }

    class DeleteDefinition extends ResourceDefinition<Thing> {
      bulkActions(selection: ResourceSelection, context: BulkActionContext<Thing>) {
        return super.bulkActions(selection, context).add('delete', bulkDeleteAction(context));
      }
    }

    function check(out: any, selector: string, checked: boolean) {
      out.first(selector).checked = checked;
      out.trigger(selector, 'change');
    }

    test('with no bulk actions there is no checkbox column', async () => {
      const out = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-selectAll');
      expect(out).not.toHaveElement('.ResourceTable-select');
      expect(out).not.toHaveElement('.ResourceTable-bulk');
    });

    test('with one bulk action there is a checkbox per row', async () => {
      const out = await rendered({ definition: new BulkDefinition('things') });

      expect(out).toHaveElement('th .ResourceTable-selectAll');
      expect(out).toHaveElement('tr[data-id="1"] input.ResourceTable-select');
      expect(out).toHaveElement('tr[data-id="2"] input.ResourceTable-select');
      expect(out).toHaveElement('.ResourceTable-bulk .Things-archive');
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
      const bulkActions = jest.spyOn(definition, 'bulkActions');
      const out = await rendered({ definition });

      bulkActions.mockClear();
      out.redraw();

      expect(bulkActions).toHaveBeenCalledTimes(1);
    });

    test('a bulk delete whose reload fails leaves no unhandled rejection', async () => {
      const rejections: unknown[] = [];
      const onRejection = (reason: unknown) => rejections.push(reason);
      process.on('unhandledRejection', onRejection);

      try {
        const out = await rendered({ definition: new DeleteDefinition('things') });
        app.request = jest.fn(() => Promise.resolve()) as any;
        jest.spyOn(window, 'confirm').mockImplementation(() => true);
        find.mockImplementation(() => Promise.reject(new Error('500')));

        check(out, 'tr[data-id="1"] input.ResourceTable-select', true);
        out.click('.ResourceTable-bulk .ResourceTable-bulkDelete');
        await flush();
        await flush();
        await new Promise((r) => setTimeout(r, 10));

        expect(app.modal.show).toHaveBeenCalledWith(BulkResultModal, expect.anything());
        expect(rejections).toEqual([]);
      } finally {
        process.off('unhandledRejection', onRejection);
      }
    });

    test('bulkDelete deletes the deletable selected rows and reports the skipped one', async () => {
      find.mockImplementation(() =>
        Promise.resolve(results([thing('1', { canDelete: true }), thing('2', { canDelete: false }), thing('3', { canDelete: true, name: 'Third' })]))
      );

      const out = await rendered({ definition: new DeleteDefinition('things') });
      const request = jest.fn((_options: any) => Promise.resolve());
      app.request = request as any;
      const confirm = jest.spyOn(window, 'confirm').mockImplementation(() => true);

      check(out, '.ResourceTable-selectAll', true);
      out.click('.ResourceTable-bulk .ResourceTable-bulkDelete');
      await flush();
      await flush();

      expect(confirm).toHaveBeenCalledTimes(1);

      const deletes = request.mock.calls.map(([options]) => options).filter((options) => options.method === 'DELETE');
      expect(deletes.map((options) => options.url).sort()).toEqual(['https://example.test/api/things/1', 'https://example.test/api/things/3']);

      expect(app.modal.show).toHaveBeenCalledWith(BulkResultModal, expect.objectContaining({ summary: expect.anything() }));

      const summary = (app.modal.show as jest.Mock).mock.calls.at(-1)![1] as { summary: BulkSummary<Thing> };
      expect(summary.summary.ok.map((m) => m.id())).toEqual(['1', '3']);
      expect(summary.summary.failed).toEqual([]);
      expect(summary.summary.skipped.map((m) => m.id())).toEqual(['2']);

      const modal = mq(BulkResultModal, { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...summary });

      expect(modal).toHaveElementAttr('.BulkResultModal-deleted', 'data-count', '2');
      expect(modal).toHaveElementAttr('.BulkResultModal-skipped', 'data-count', '1');
      expect(modal).toContainRaw('Thing 2');
      expect(modal).not.toHaveElement('.BulkResultModal-failed');

      // The list was refreshed afterwards.
      expect(find.mock.calls.length).toBeGreaterThan(1);
    });
  });
});
