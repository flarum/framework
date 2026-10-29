import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import AutocompleteDropdown from '../../../../src/common/components/AutocompleteDropdown';
import ResourceTable from '../../../../src/admin/components/resource/ResourceTable';
import ResourceFilterBar from '../../../../src/admin/components/resource/ResourceFilterBar';
import CompactFilterBar, { ALWAYS_QUERY } from '../../../../src/admin/components/resource/CompactFilterBar';
import Model from '../../../../src/common/Model';
import EmailGambit from '../../../../src/common/query/users/EmailGambit';
import { BooleanGambit } from '../../../../src/common/query/IGambit';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
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

  test('each filter is a dropdown button with a visible "{label}: {value}" label', async () => {
    const { out } = await rendered();

    expect(out.first('.ResourceTable-filter--status .Dropdown-toggle').textContent).toContain('Status: Any');
    expect(out).toHaveElementAttr('.ResourceTable-filter--status .Dropdown-toggle', 'aria-label', 'Status: Any, change filter');
    expect(out.find('.ResourceTable-filter--status .Dropdown-menu button')).toHaveLength(3);
    expect(out).toHaveElement('.ResourceTable-filter--status .Dropdown-menu li.active button[data-value=""]');

    out.click('.ResourceTable-filter--status button[data-value=live]');
    await settle(out);

    expect(lastParams()).toMatchObject({ filter: { status: 'live' }, page: { offset: 0 } });
    expect(out.first('.ResourceTable-filter--status .Dropdown-toggle').textContent).toContain('Status: live');
  });

  test('the search is a labelled core Input, wrapped in the gambit autocomplete when the type has gambits', async () => {
    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-toolbarView .AutocompleteDropdown .Input input.ResourceTable-search[type=search]');
    expect(out).toHaveElement('.ResourceTable-searchGroup label.visually-hidden[for="ResourceTable-search-gadgets"]');
    expect(out).toHaveElement('.ResourceTable-toolbarActions .ResourceTable-count[role=status][aria-live=polite]');
    expect(out).toContainRaw('1 entry');
  });

  test('boolean "No" is sent as the negated key and round-trips through the URL', async () => {
    const { out } = await rendered({ definition: new KeepActive('gadgets') });

    out.click('.ResourceTable-filter--active button[data-value="0"]');
    await settle(out);

    expect(lastParams().filter).toEqual({ '-active': '1' });
    expect(urlQuery().get('filter[-active]')).toBe('1');
    expect(urlQuery().get('filter[active]')).toBeNull();

    out.click('.ResourceTable-filter--active button[data-value="1"]');
    await settle(out);

    expect(lastParams().filter).toEqual({ active: '1' });

    window.history.replaceState(null, '', '/admin#/gadgets?filter%5B-active%5D=1');
    const reloaded = (await rendered({ definition: new KeepActive('gadgets') })).out;

    expect(lastParams().filter).toEqual({ '-active': '1' });
    expect(reloaded.first('.ResourceTable-filter--active .Dropdown-toggle').textContent).toContain('Active: No');
  });

  test('by default every filter that a gambit of the same key covers is left out; uncovered ones stay', async () => {
    const { out } = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-filter--email');
    expect(out).not.toHaveElement('.ResourceTable-filter--active');
    expect(out).toHaveElement('.ResourceTable-filter--status');
    expect(out).toHaveElement('.ResourceTable-filter--color .ResourceTable-filterControl input');
  });

  test("hideWhenSearchable: false keeps a covered filter's dropdown", async () => {
    const { out, table } = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-filter--active');

    table.bar!.filters.get('active').hideWhenSearchable = false;
    out.redraw();

    expect(out).toHaveElement('.ResourceTable-filter--active');
    expect(out).not.toHaveElement('.ResourceTable-filter--email');
  });

  test('filters past the first three go under "More filters"; picking one adds its button', async () => {
    const { out } = await rendered({ definition: new KeepActive('gadgets') });

    expect(out.find('.ResourceTable-toolbarView .ResourceTable-filter')).toHaveLength(3);
    expect(out).not.toHaveElement('.ResourceTable-filter--color');
    expect(out.find('.ResourceTable-moreFilters .ResourceTable-moreFilter')).toHaveLength(2);

    out.click('.ResourceTable-moreFilter[data-filter=size]');
    out.redraw();

    expect(out).toHaveElement('.ResourceTable-toolbarView .ResourceTable-filter--size');
    expect(out.find('.ResourceTable-moreFilters .ResourceTable-moreFilter')).toHaveLength(1);
  });

  test('a bar\'s inlineFilters() sets how many filters show before "More filters"', async () => {
    class OneInlineBar extends ResourceFilterBar<Gadget> {
      inlineFilters(): number {
        return 1;
      }
    }

    class OneInlineTable extends ResourceTable<Gadget> {
      filterBar() {
        return new OneInlineBar({ definition: this.attrs.definition, list: this.list, filters: this.attrs.definition.filters() });
      }
    }

    const out = mq(OneInlineTable, { definition: new KeepActive('gadgets') });
    await settle(out);

    expect(out.find('.ResourceTable-toolbarView .ResourceTable-filter')).toHaveLength(1);
    expect(out.find('.ResourceTable-moreFilters .ResourceTable-moreFilter')).toHaveLength(4);
  });

  test('a filter with a value is always on the toolbar', async () => {
    window.history.replaceState(null, '', '/admin#/gadgets?filter%5Bsize%5D=s');

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-filter--status');
    expect(out).toHaveElement('.ResourceTable-filter--size.is-active');
    expect(out).not.toHaveElement('.ResourceTable-moreFilters');
  });

  test('active filters show as chips; one can be removed, "Clear all" removes all', async () => {
    window.history.replaceState(null, '', '/admin#/gadgets?filter%5Bstatus%5D=live&filter%5B-active%5D=1&q=knife');

    const { out } = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-subToolbar .ResourceTable-chip--email');
    expect(out.find('.ResourceTable-subToolbar .ResourceTable-chip')).toHaveLength(2);
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

  test('a filter shown because it has a value stays on the toolbar when set back to Any', async () => {
    window.history.replaceState(null, '', '/admin#/gadgets?filter%5Bsize%5D=s');

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-filter--size.is-active');

    out.click('.ResourceTable-filter--size button[data-value=""]');
    await settle(out);

    expect(lastParams().filter).toBeUndefined();
    expect(out).toHaveElement('.ResourceTable-filter--size');
    expect(out).not.toHaveElement('.ResourceTable-moreFilters .ResourceTable-moreFilter[data-filter="size"]');
  });

  test('a relationship menu whose records failed to load shows an error row with Retry', async () => {
    const { out, table } = await rendered();
    const options = table.bar!.relationOptions('owner', 'owners');

    find.mockImplementationOnce(() => Promise.reject(new Error('nope')));
    await options.loadItems().catch(() => {});
    out.redraw();

    expect(options.failed).toBe(true);
    expect(out).toHaveElement('.ResourceTable-filter--owner .ResourceTable-filterError');
    expect(out).not.toHaveElement('.ResourceTable-filter--owner .ResourceTable-filterMore');

    out.click('.ResourceTable-filter--owner .ResourceTable-filterRetry');
    await settle(out);

    expect(options.failed).toBe(false);
    expect(out).not.toHaveElement('.ResourceTable-filter--owner .ResourceTable-filterError');
    expect(out).toHaveElement('.ResourceTable-filter--owner button[data-value="8"]');
  });

  test('accessible names start with the visible text', async () => {
    const { out } = await rendered();

    expect(out.first('.ResourceTable-filter--status .Dropdown-toggle').getAttribute('aria-label')).toMatch(/^Status: Any/);
    expect(out.first('.ResourceTable-moreFilters .Dropdown-toggle').getAttribute('aria-label')).toMatch(/^More filters/);
  });

  test('"Columns" and "Export CSV" are in the second row', async () => {
    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-subToolbar .ResourceTable-display .ResourceTable-columns');
    expect(out).toHaveElement('.ResourceTable-subToolbar .ResourceTable-display .ResourceTable-export');
    expect(out).not.toHaveElement('.ResourceTable-toolbar .ResourceTable-columns');
    expect(out).not.toHaveElement('.ResourceTable-toolbar .ResourceTable-export');
  });

  test('a relationship filter lists the related records, with "Any" first', async () => {
    const { out, table } = await rendered();

    await table.bar!.relationOptions('owner', 'owners').loadItems();
    out.redraw();

    expect(lastParams('owners')).toMatchObject({ page: { offset: 0 } });
    expect(out).toHaveElement('.ResourceTable-filter--owner .ResourceTable-filterSearch input[type=search]');
    expect(out.find('.ResourceTable-filter--owner .Dropdown-menu button[data-value]')).toHaveLength(3);

    out.click('.ResourceTable-filter--owner button[data-value="8"]');
    await settle(out);

    expect(lastParams().filter).toEqual({ owner: '8' });
    expect(out.first('.ResourceTable-filter--owner .Dropdown-toggle').textContent).toContain('Owner: Owner 8');
    expect(out).toHaveElement('.ResourceTable-filter--owner li.active button[data-value="8"]');
  });

  test("a relationship filter's exclude leaves records out of its menu", async () => {
    const { out, table } = await rendered();

    table.bar!.filters.get('owner').exclude = (model) => model.id() === '8';
    await table.bar!.relationOptions('owner', 'owners').loadItems();
    out.redraw();

    expect(out.find('.ResourceTable-filter--owner .Dropdown-menu button[data-value]')).toHaveLength(2);
    expect(out).not.toHaveElement('.ResourceTable-filter--owner button[data-value="8"]');
  });

  describe('at phone width', () => {
    let listeners: Array<() => void>;
    let phone: boolean;

    beforeEach(() => {
      listeners = [];
      phone = true;
      (window as any).matchMedia = jest.fn(() => ({
        get matches() {
          return phone;
        },
        addEventListener: (_: string, listener: () => void) => listeners.push(listener),
        removeEventListener: (_: string, listener: () => void) => listeners.splice(listeners.indexOf(listener), 1),
      }));
    });

    afterEach(() => {
      delete (window as any).matchMedia;
    });

    test('a wider viewport turns the compact menu back into the filter dropdowns, and removal stops listening', async () => {
      const { out, table } = await rendered();

      expect(out).toHaveElement('.ResourceTable-filtersMenu');
      expect(window.matchMedia).toHaveBeenCalledWith('(max-width: 767.98px)');

      phone = false;
      listeners.forEach((listener) => listener());
      out.redraw();

      expect(out).not.toHaveElement('.ResourceTable-filtersMenu');
      expect(out).toHaveElement('.ResourceTable-filter--status');

      table.bar!.destroy();

      expect(listeners).toHaveLength(0);
    });

    test('compactFilters: false keeps the plain bar at phone width', async () => {
      const { out, table } = await rendered({ compactFilters: false });

      expect(table.bar).not.toBeInstanceOf(CompactFilterBar);
      expect(out).not.toHaveElement('.ResourceTable-filtersMenu');
      expect(out).toHaveElement('.ResourceTable-filter--status');
    });

    test('compactFilters: true is compact at any width', async () => {
      phone = false;
      (window as any).matchMedia = jest.fn((query: string) => ({
        matches: query === ALWAYS_QUERY || phone,
        addEventListener() {},
        removeEventListener() {},
      }));

      const { out } = await rendered({ compactFilters: true });

      expect(window.matchMedia).toHaveBeenCalledWith(ALWAYS_QUERY);
      expect(out).toHaveElement('.ResourceTable-filtersMenu');
    });

    test('unset, compactFilters follows the viewport', async () => {
      phone = false;

      const { out } = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-filtersMenu');
    });

    test('compact mode collapses the filters into one "Filters (n)" dropdown of labelled sections', async () => {
      window.history.replaceState(null, '', '/admin#/gadgets?filter%5Bstatus%5D=live');

      const { out } = await rendered({ definition: new KeepActive('gadgets') });

      expect(out).not.toHaveElement('.ResourceTable-toolbarView .ResourceTable-filter');
      expect(out).not.toHaveElement('.ResourceTable-moreFilters');
      expect(out.first('.ResourceTable-filtersMenu .Dropdown-toggle').textContent).toContain('Filters (1)');
      expect(out.find('.ResourceTable-filtersMenu .ResourceTable-filterGroup')).toHaveLength(5);
      expect(out.first('.ResourceTable-filterGroup--status .ResourceTable-filterGroupLabel').textContent).toBe('Status');
      expect(out).toHaveElement('.ResourceTable-subToolbar .ResourceTable-chip--status');

      // The same choice items as the desktop menus: no native selects, no RelationInput.
      expect(out).not.toHaveElement('.ResourceTable-toolbar select');
      expect(out).not.toHaveElement('.ResourceTable-toolbar .ResourceRelationInput');
      expect(out).toHaveElement('.ResourceTable-filterGroupItem--status.active button[data-value="live"]');
      expect(out.find('.ResourceTable-filterGroupItem--active button[data-value]').map((b: any) => b.getAttribute('data-value'))).toEqual([
        '',
        '1',
        '0',
      ]);
      expect(out).toHaveElement('.ResourceTable-filterGroupItem--color input');
    });

    test('compact mode lists relationship records as choices and sets the filter', async () => {
      const { out, table } = await rendered();

      await table.bar!.relationOptions('owner', 'owners').loadItems();
      out.redraw();

      expect(out.first('.ResourceTable-filterGroup--owner .ResourceTable-filterGroupLabel').textContent).toBe('Owner');
      expect(out).toHaveElement('.ResourceTable-filterGroupItem--owner button[data-value=""]');

      expect(out).toHaveElement('.ResourceTable-filterGroupItem--owner button[data-value="7"]');
      expect(out).toHaveElement('.ResourceTable-filterGroupItem--owner button[data-value="8"]');

      out.click('.ResourceTable-filterGroupItem--owner button[data-value="8"]');
      await settle(out);

      expect(lastParams().filter).toEqual({ owner: '8' });
      expect(out.first('.ResourceTable-filtersMenu .Dropdown-toggle').textContent).toContain('Filters (1)');
    });
  });
});
