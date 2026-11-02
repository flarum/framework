import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import AutocompleteDropdown from '../../../../src/common/components/AutocompleteDropdown';
import ResourceTable from '../../../../src/admin/components/resource/ResourceTable';
import Model from '../../../../src/common/Model';
import EmailGambit from '../../../../src/common/query/users/EmailGambit';
import { BooleanGambit } from '../../../../src/common/query/IGambit';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import ViewPreferences from '../../../../src/common/resource/ViewPreferences';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import { patchFilter } from '../../../../src/common/resource/fieldsFromDescriptor';
import type { Filter } from '../../../../src/common/resource/types';
import type ItemList from '../../../../src/common/utils/ItemList';
import { bootAdmin, descriptor, fd, schemaDocument } from '../../../unit/common/resource/fixtures';

class Gadget extends Model {}
class Owner extends Model {}

class ActiveGambit extends BooleanGambit {
  key() {
    return 'active';
  }

  canonicalPattern() {
    return 'is:(active)';
  }

  filterKey() {
    return 'active';
  }
}

/** Keeps the `active` filter's dropdown, though the `is:active` gambit covers it. */
class KeepActive extends ResourceDefinition<Gadget> {
  filters(): ItemList<Filter> {
    const filters = super.filters();

    patchFilter(filters, 'active', { hideWhenSearchable: false });

    return filters;
  }
}

const gadgets = descriptor([fd('name', 'string', { sortable: true }), fd('active', 'boolean')], {
  endpoint: '/gadgets',
  sorts: ['name'],
  filters: [
    { key: 'status', kind: 'enum', enum: ['draft', 'live'] },
    { key: 'active', kind: 'boolean' },
    { key: 'owner', kind: 'relationship', related: 'owners' },
    { key: 'email', kind: 'string' },
    { key: 'color', kind: 'string' },
    { key: 'size', kind: 'enum', enum: ['s', 'm'] },
  ],
});

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

function results(models: Model[], meta: Record<string, unknown> = {}) {
  return Object.assign([...models], { payload: { data: [], meta, links: {} } });
}

describe('ResourceTable toolbar', () => {
  let find: jest.Mock<any>;
  let owners: Model[];

  function lastParams(type: string = 'gadgets'): Record<string, any> {
    return (find.mock.calls.filter(([t]) => t === type).at(-1) as any)[1];
  }

  function urlQuery(): URLSearchParams {
    return new URLSearchParams(window.location.hash.split('?', 2)[1] ?? '');
  }

  beforeAll(() => {
    bootAdmin();
    app.store.models.gadgets = Gadget;
    app.store.models.owners = Owner;
  });

  beforeEach(async () => {
    clearDescriptorCache();
    window.history.replaceState(null, '', '/admin#/gadgets');
    app.request = jest.fn(() => Promise.resolve(schemaDocument(gadgets, 'gadgets'))) as any;
    await new ResourceDefinition('gadgets').load();

    const gadget = app.store.pushObject<Gadget>({ type: 'gadgets', id: '1', attributes: { name: 'G1' } } as any)!;
    owners = ['7', '8'].map((id) => app.store.pushObject({ type: 'owners', id, attributes: { name: `Owner ${id}` } } as any)!);

    find = jest.fn((type: string) => Promise.resolve(type === 'owners' ? results(owners) : results([gadget], { page: { total: 1 } })));
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    ColumnPreferences.clear();
    ViewPreferences.clear();
    window.localStorage.clear();
    (app.search.gambits.gambits as any).gadgets = [EmailGambit, ActiveGambit];
    // mithril-query's DOM (domino) has no layout.
    jest.spyOn(AutocompleteDropdown.prototype as any, 'updateMaxHeight').mockImplementation(() => {});
  });

  afterEach(() => {
    delete (app.search.gambits.gambits as any).gadgets;
    jest.restoreAllMocks();
  });

  async function rendered(attrs: Record<string, unknown> = {}) {
    const oninit = jest.spyOn(ResourceTable.prototype, 'oninit');
    const out = mq(ResourceTable, { definition: new ResourceDefinition<Gadget>('gadgets'), ...attrs });
    const table = oninit.mock.contexts[0] as ResourceTable<Gadget>;
    oninit.mockRestore();

    await flush();
    await flush();
    out.redraw();

    return { out, table };
  }

  async function settle(out: any) {
    await flush();
    await flush();
    out.redraw();
  }

  test('the table and its list state share one filters() build', async () => {
    const build = jest.spyOn(ResourceDefinition.prototype, 'filters');
    const { table } = await rendered();

    expect(build).toHaveBeenCalledTimes(1);
    expect(table.bar!.filters).toBe(table.list.filters());
  });

  test('the filter bar exists at the redraw after the descriptor loads, and the rows load redraws again', async () => {
    const barAtRedraw: boolean[] = [];
    let table: ResourceTable<Gadget> | null = null;
    const oninit = jest.spyOn(ResourceTable.prototype, 'oninit');
    jest.spyOn(m, 'redraw').mockImplementation((() => barAtRedraw.push(!!table?.bar)) as any);

    const out = mq(ResourceTable, { definition: new ResourceDefinition<Gadget>('gadgets') });
    table = oninit.mock.contexts[0] as ResourceTable<Gadget>;
    await flush();
    await flush();
    out.redraw();

    expect(barAtRedraw.length).toBeGreaterThanOrEqual(2);
    expect(barAtRedraw.every(Boolean)).toBe(true);
    expect(out).toHaveElement('.ResourceTable-filterPanel .Dropdown-toggle');
  });

  test('the filters are one "Filter" button whose panel has a labelled section per filter', async () => {
    const { out } = await rendered({ definition: new KeepActive('gadgets') });

    expect(out.find('.ResourceTable-toolbarView .ResourceTable-filterPanel')).toHaveLength(1);
    expect(out.first('.ResourceTable-filterPanel .Dropdown-toggle').textContent).toContain('Filter');
    expect(out).not.toHaveElement('.ResourceTable-filterCount');
    expect(out).toHaveElementAttr('.ResourceTable-filterPanel .Dropdown-toggle', 'aria-label', 'Filter');
    // Five described filters, then the gadgets' gambits as search filter chips.
    expect(out.find('.ResourceTable-filterPanel .ResourceTable-filterGroup')).toHaveLength(6);
    expect(out.find('.ResourceTable-filterPanel .ResourceTable-filterGroup').at(-1).className).toContain('ResourceTable-filterGroup--search');
    expect(out.first('.ResourceTable-filterGroup--status .ResourceTable-filterGroupLabel').textContent).toBe('Status');
    expect(out.find('.ResourceTable-filterGroupItem--status button')).toHaveLength(3);
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--status.active button[data-value=""]');
    expect(out.find('.ResourceTable-filterGroupItem--active button[data-value]').map((b: any) => b.getAttribute('data-value'))).toEqual([
      '',
      '1',
      '0',
    ]);
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--color .ResourceTable-filterControl input');

    out.click('.ResourceTable-filterGroupItem--status button[data-value=live]');
    await settle(out);

    expect(lastParams()).toMatchObject({ filter: { status: 'live' }, page: { offset: 0 } });
    expect(out).toHaveElement('.ResourceTable-filterPanel .Dropdown-toggle.is-active');
    expect(out.first('.ResourceTable-filterCount').textContent).toBe('1');
    expect(out).toHaveElementAttr('.ResourceTable-filterPanel .Dropdown-toggle', 'aria-label', 'Filter, 1 active');
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--status.active button[data-value=live]');
  });

  test('the search is a labelled core Input, wrapped in the gambit autocomplete when the type has gambits', async () => {
    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-toolbarView .AutocompleteDropdown .Input input.ResourceTable-search[type=search]');
    expect(out).toHaveElement('.ResourceTable-searchGroup label.visually-hidden[for="ResourceTable-search-gadgets"]');
  });

  test('one toolbar row: search and Filter left; refresh, settings and create right; no sort dropdown or count', async () => {
    const { out } = await rendered();

    expect(out.find('.ResourceTable-toolbar')).toHaveLength(1);
    expect(out).toHaveElement('.ResourceTable-toolbarView .ResourceTable-searchGroup');
    expect(out).toHaveElement('.ResourceTable-toolbarView .ResourceTable-filterPanel');
    expect(out).toHaveElement('.ResourceTable-toolbarActions .ResourceTable-refresh');
    expect(out).toHaveElement('.ResourceTable-toolbarActions .ResourceTable-settings');
    expect(out).not.toHaveElement('.ResourceTable-sortDropdown');
    expect(out).not.toHaveElement('.ResourceTable-count');
    expect(out).not.toHaveElement('.ResourceTable-filterTags');
  });

  test('boolean "No" is sent as the negated key and round-trips through the URL', async () => {
    const { out } = await rendered({ definition: new KeepActive('gadgets') });

    out.click('.ResourceTable-filterGroupItem--active button[data-value="0"]');
    await settle(out);

    expect(lastParams().filter).toEqual({ '-active': '1' });
    expect(urlQuery().get('filter[-active]')).toBe('1');
    expect(urlQuery().get('filter[active]')).toBeNull();

    out.click('.ResourceTable-filterGroupItem--active button[data-value="1"]');
    await settle(out);

    expect(lastParams().filter).toEqual({ active: '1' });

    window.history.replaceState(null, '', '/admin#/gadgets?filter%5B-active%5D=1');
    const reloaded = (await rendered({ definition: new KeepActive('gadgets') })).out;

    expect(lastParams().filter).toEqual({ '-active': '1' });
    expect(reloaded).toContainRaw('Active: No');
    expect(reloaded).toHaveElement('.ResourceTable-filterGroupItem--active.active button[data-value="0"]');
  });

  test('by default every filter that a gambit of the same key covers is left out; uncovered ones stay', async () => {
    const { out } = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-filterGroup--email');
    expect(out).not.toHaveElement('.ResourceTable-filterGroup--active');
    expect(out).toHaveElement('.ResourceTable-filterGroup--status');
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--color .ResourceTable-filterControl input');
  });

  test("hideWhenSearchable: false keeps a covered filter's dropdown", async () => {
    const { out, table } = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-filterGroup--active');

    table.bar!.filters.get('active').hideWhenSearchable = false;
    out.redraw();

    expect(out).toHaveElement('.ResourceTable-filterGroup--active');
    expect(out).not.toHaveElement('.ResourceTable-filterGroup--email');
  });

  test('active filters show as tags under the toolbar; one can be removed, "Clear filters" removes all', async () => {
    window.history.replaceState(null, '', '/admin#/gadgets?filter%5Bstatus%5D=live&filter%5B-active%5D=1&q=knife');

    const { out } = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-filterTags .ResourceTable-chip--email');
    expect(out.find('.ResourceTable-filterTags .ResourceTable-chip')).toHaveLength(2);
    expect(out).toContainRaw('Status: live');
    expect(out).toContainRaw('Active: No');
    expect(out).toHaveElementAttr('.ResourceTable-chip--status button', 'aria-label', 'Remove filter Status');

    out.click('.ResourceTable-chip--status button');
    await settle(out);

    expect(lastParams().filter).toEqual({ q: 'knife', '-active': '1' });
    expect(out.find('.ResourceTable-chip')).toHaveLength(1);

    out.click('.ResourceTable-clearFilters');
    await settle(out);

    expect(lastParams().filter).toEqual({ q: 'knife' });
    expect(out).not.toHaveElement('.ResourceTable-chip');
    expect(out).not.toHaveElement('.ResourceTable-clearFilters');
    expect(out).not.toHaveElement('.ResourceTable-filterTags');
  });

  test('a gambit in the search marks the chip of the same key overridden', async () => {
    window.history.replaceState(null, '', '/admin#/gadgets?filter%5Bemail%5D=a%40b.c&q=email%3Ax%40y.z');

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-chip--email.ResourceTable-chip--overridden');
    expect(out).toHaveElementAttr('.ResourceTable-chip--email .ResourceTable-chipText', 'title', 'Overridden by the search');
  });

  test('a negated gambit overrides the chip that sends the same negated key', async () => {
    window.history.replaceState(null, '', '/admin#/gadgets?filter%5B-active%5D=1&q=-is%3Aactive');

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-chip--active.ResourceTable-chip--overridden');
    expect(out).not.toHaveElement('.ResourceTable-chip--active.ResourceTable-chip--conflict');
  });

  test('a gambit of the opposite sign marks the chip as conflicting, not overridden', async () => {
    window.history.replaceState(null, '', '/admin#/gadgets?filter%5B-active%5D=1&q=is%3Aactive');

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-chip--active.ResourceTable-chip--conflict');
    expect(out).not.toHaveElement('.ResourceTable-chip--active.ResourceTable-chip--overridden');
    expect(out).toHaveElementAttr('.ResourceTable-chip--active .ResourceTable-chipText', 'title', 'Conflicts with the search');
  });

  test('a relationship menu whose records failed to load shows an error row with Retry', async () => {
    const { out, table } = await rendered();
    const options = table.bar!.relationOptions('owner', 'owners');

    const served = find.getMockImplementation()!;
    find.mockImplementation((type: string, ...rest: unknown[]) => (type === 'owners' ? Promise.reject(new Error('nope')) : served(type, ...rest)));
    await options.loadItems().catch(() => {});
    find.mockImplementation(served);
    out.redraw();

    expect(options.failed).toBe(true);
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--owner .ResourceTable-filterError');
    expect(out).not.toHaveElement('.ResourceTable-filterGroupItem--owner .ResourceTable-filterMore');

    out.click('.ResourceTable-filterGroupItem--owner .ResourceTable-filterRetry');
    await settle(out);

    expect(options.failed).toBe(false);
    expect(out).not.toHaveElement('.ResourceTable-filterGroupItem--owner .ResourceTable-filterError');
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--owner button[data-value="8"]');
  });

  test('the settings menu holds the column toggles, "Export CSV" and the phone sort select', async () => {
    const { out } = await rendered();

    expect(out).toHaveElementAttr('.ResourceTable-settings .Dropdown-toggle', 'aria-label', 'Table settings');
    expect(out).toHaveElement('.ResourceTable-settings .ResourceTable-columnToggle input[name=name]');
    expect(out).toHaveElement('.ResourceTable-settings .ResourceTable-export');
    expect(out).toHaveElement('.ResourceTable-settings li.ResourceTable-sortItem select');
    expect(out).not.toHaveElement('.ResourceTable-toolbarView .ResourceTable-export');

    out.first('.ResourceTable-sortItem select').value = '-name';
    out.trigger('.ResourceTable-sortItem select', 'change');
    await settle(out);

    expect(lastParams()).toMatchObject({ sort: '-name' });
  });

  test('a relationship filter lists the related records, with "Any" first', async () => {
    const { out, table } = await rendered();

    await table.bar!.relationOptions('owner', 'owners').loadItems();
    out.redraw();

    expect(lastParams('owners')).toMatchObject({ page: { offset: 0 } });
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--owner .ResourceTable-filterSearch input[type=search]');
    expect(out.find('.ResourceTable-filterGroupItem--owner button[data-value]')).toHaveLength(3);

    out.click('.ResourceTable-filterGroupItem--owner button[data-value="8"]');
    await settle(out);

    expect(lastParams().filter).toEqual({ owner: '8' });
    expect(out).toContainRaw('Owner: Owner 8');
    expect(out).toHaveElement('.ResourceTable-filterGroupItem--owner.active button[data-value="8"]');
  });

  test("a relationship filter's exclude leaves records out of its menu", async () => {
    const { out, table } = await rendered();

    table.bar!.filters.get('owner').exclude = (model) => model.id() === '8';
    await table.bar!.relationOptions('owner', 'owners').loadItems();
    out.redraw();

    expect(out.find('.ResourceTable-filterGroupItem--owner button[data-value]')).toHaveLength(2);
    expect(out).not.toHaveElement('.ResourceTable-filterGroupItem--owner button[data-value="8"]');
  });

  describe('described filter options', () => {
    const widgets = descriptor(
      [
        fd('name', 'string', { sortable: true }),
        fd('secret', 'string', { sortable: true, ui: { hidden: true } }),
        fd('status', 'string', { constraints: { enum: ['draft', 'live'] }, enumLabels: { draft: 'Draft', live: 'Live' } }),
      ],
      {
        endpoint: '/widgets',
        sorts: ['name', 'secret'],
        filters: [
          { key: 'status', kind: 'enum', enum: ['draft', 'live'], multiple: true },
          { key: 'scope', kind: 'enum', enum: ['mine', 'all'], label: 'Scope', required: true, default: 'mine' },
          { key: 'joined', kind: 'date', range: true, label: 'Joined' },
          { key: 'posts', kind: 'number', range: true },
        ],
      }
    );

    async function widgetTable() {
      app.store.models.widgets = Gadget;
      clearDescriptorCache('widgets');
      find.mockImplementation(((type: string) =>
        Promise.resolve(type === 'resource-schemas' ? { payload: schemaDocument(widgets, 'widgets') } : results([], { page: { total: 0 } }))) as any);

      return rendered({ definition: new ResourceDefinition<Gadget>('widgets') });
    }

    const widgetParams = () => lastParams('widgets');

    test('a multiple filter toggles values into a comma list, one chip per value with its label', async () => {
      const { out } = await widgetTable();

      out.click('.ResourceTable-filterGroupItem--status button[data-value=draft]');
      await settle(out);
      out.click('.ResourceTable-filterGroupItem--status button[data-value=live]');
      await settle(out);

      expect(widgetParams().filter).toMatchObject({ status: 'draft,live' });
      expect(out.find('.ResourceTable-chip--status')).toHaveLength(2);
      expect(out).toContainRaw('Status: Draft');

      out.click('.ResourceTable-chip--status button');
      await settle(out);

      expect(widgetParams().filter).toMatchObject({ status: 'live' });
    });

    test('a required filter has its default, no "Any", no remove button, and survives "Clear filters"', async () => {
      const { out } = await widgetTable();

      expect(widgetParams().filter).toEqual({ scope: 'mine' });
      expect(out).toHaveElement('.ResourceTable-filterGroup--scope');
      expect(out).not.toHaveElement('.ResourceTable-filterGroupItem--scope button[data-value=""]');
      expect(out).toHaveElement('.ResourceTable-chip--scope.ResourceTable-chip--required');
      expect(out).not.toHaveElement('.ResourceTable-chip--scope button');
      expect(out).not.toHaveElement('.ResourceTable-clearFilters');

      out.click('.ResourceTable-filterGroupItem--status button[data-value=draft]');
      await settle(out);
      out.click('.ResourceTable-clearFilters');
      await settle(out);

      expect(widgetParams().filter).toEqual({ scope: 'mine' });
    });

    test('a range filter reads "from – …" on its chip and is sent as from..to', async () => {
      window.history.replaceState(null, '', '/admin#/widgets?filter%5Bjoined%5D=2024-01-01..&filter%5Bposts%5D=..10');

      const { out } = await widgetTable();

      expect(widgetParams().filter).toEqual({ scope: 'mine', joined: '2024-01-01..', posts: '..10' });
      expect(out).toContainRaw('Joined: 2024-01-01 – …');
      expect(out).toContainRaw('Posts: … – 10');
    });

    test('a ui.hidden column starts hidden until the user shows it', async () => {
      const { out, table } = await widgetTable();

      expect(out).not.toHaveElement('th[data-column-name=secret]');
      expect(out).toHaveElement('.ResourceTable-columnToggle input[name=secret]:not([checked])');

      table.toggleColumn('secret', true);
      out.redraw();

      expect(out).toHaveElement('th[data-column-name=secret]');
      expect(ColumnPreferences.load('widgets').get('secret')).toBe(true);
    });
  });
});
