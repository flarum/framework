import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import Stream from '../../../../src/common/utils/Stream';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { defaultFilterInputs } from '../../../../src/common/resource/inputs/defaults';
import InputFactory from '../../../../src/common/resource/inputs/InputFactory';
import RelationInput from '../../../../src/common/resource/inputs/RelationInput';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceForm from '../../../../src/common/resource/ResourceForm';
import { rangeLabel } from '../../../../src/common/resource/rangeFilterInput';
import {
  defaultFilterResolvers,
  defaultInputResolvers,
  formatRange,
  parseRange,
  relatedRecordLabel,
  resolveFilterInput,
  resolveInput,
} from '../../../../src/common/resource/resolvers';
import ItemList from '../../../../src/common/utils/ItemList';
import type { FieldDescriptor, InputContext, InputResolver, InputSpec } from '../../../../src/common/resource/types';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

class Thing extends Model {}

const things = descriptor([fd('title', 'string', { constraints: { maxLength: 120 } }), fd('color', 'string')]);

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

afterEach(() => jest.restoreAllMocks());

describe('InputFactory', () => {
  beforeAll(() => bootAdmin());

  test('renders a registered type through its renderer', () => {
    const factory = new InputFactory().register('swatch', (spec: InputSpec, ctx: InputContext) =>
      m('div.Swatch', { 'data-name': ctx.name }, spec.size)
    );
    const vnode = factory.render({ type: 'swatch', size: 'big' }, { name: 'color', stream: Stream('') }) as any;

    expect(vnode.tag).toBe('div');
    expect(vnode.attrs['data-name']).toBe('color');
  });

  test('register() replaces an existing renderer', () => {
    const factory = new InputFactory().register('swatch', () => 'first').register('swatch', () => 'second');

    expect(factory.render({ type: 'swatch' }, { name: 'color', stream: Stream('') })).toBe('second');
  });

  test('unregister() removes a renderer, falling back to FormGroup', () => {
    const factory = new InputFactory().register('text', () => 'custom');

    factory.unregister('text');

    expect(factory.has('text')).toBe(false);
    expect(() => factory.unregister('missing')).not.toThrow();
  });

  test('unregistered types fall back to FormGroup', () => {
    const out = mq({
      view: () => new InputFactory().render({ type: 'text' }, { name: 'title', label: 'Title', required: true, stream: Stream('Knife') }),
    });

    expect(out).toHaveElement('.Form-group input[name=title][required]');
    expect(out).toContainRaw('Title');
  });
});

describe('default filter inputs', () => {
  beforeAll(() => bootAdmin());

  const filters = new InputFactory(defaultFilterInputs());

  test('tri-state offers any / yes / no in that order', () => {
    const out = mq({ view: () => filters.render({ type: 'tri-state' }, { name: 'active', label: 'Active', stream: Stream('') }) });

    expect(out.find('option').map((o: any) => o.getAttribute('value'))).toEqual(['', '1', '0']);
    expect(out).toHaveElementAttr('select[name=active]', 'aria-label', 'Active');
  });

  test('relation bridges the id-valued stream to a single picker', () => {
    const vnode = filters.render({ type: 'relation', related: 'categories', display: 'title' }, { name: 'category', stream: Stream('') }) as any;

    expect(vnode.tag).toBe(RelationInput);
    expect(vnode.attrs).toMatchObject({ type: 'categories', multiple: false, nullable: true, display: 'title' });
  });

  test('enum offers any first, then the choices in order', () => {
    const out = mq({
      view: () =>
        filters.render(
          {
            type: 'enum',
            choices: [
              ['1', 'One'],
              ['0', 'Zero'],
            ],
          },
          { name: 'level', label: 'Level', stream: Stream('') }
        ),
    });

    expect(out.find('option').map((o: any) => o.getAttribute('value'))).toEqual(['', '1', '0']);
  });

  test('types without a renderer fall back to a bare input, not a FormGroup', () => {
    const definitionFilters = new ResourceDefinition('things').filterInputs();
    const out = mq({ view: () => definitionFilters.render({ type: 'date', debounce: false }, { name: 'from', label: 'From', stream: Stream('') }) });

    expect(out).toHaveElement('input[name=from][type=date][aria-label=From]');
    expect(out).not.toHaveElement('.Form-group');
    expect(out).not.toHaveElement('input[debounce]');
  });

  test('a relation filter from the URL fetches its model once, so the active filter shows', async () => {
    app.store.models.categories = Thing;
    const find = jest.fn(() =>
      Promise.resolve().then(() => app.store.pushPayload({ data: { type: 'categories', id: '5', attributes: { name: 'Drills' } } }))
    );
    jest.spyOn(app.store, 'find').mockImplementation(find as any);

    const stream = Stream('5');
    const render = () => filters.render({ type: 'relation', related: 'categories' }, { name: 'category', stream }) as any;

    expect(render().attrs.stream()).toBeNull();
    expect(render().attrs.stream()).toBeNull();
    await flush();

    expect(find).toHaveBeenCalledTimes(1);
    expect(find).toHaveBeenCalledWith('categories', '5');
    expect(render().attrs.stream()?.id()).toBe('5');
  });

  test('text types render a bare input labelled by placeholder', () => {
    const out = mq({ view: () => filters.render({ type: 'text' }, { name: 'brand', label: 'Brand', stream: Stream('') }) });

    expect(out).toHaveElementAttr('input[name=brand]', 'placeholder', 'Brand');
  });
});

describe('form input factories', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
  });

  class ThingDefinition extends ResourceDefinition<Thing> {
    constructor() {
      super('things');
    }

    inputResolvers(): ItemList<InputResolver> {
      return super.inputResolvers().add('color', (d) => (d.name === 'color' ? { input: { type: 'swatch' } } : null), 100);
    }

    formInputs(): InputFactory {
      return super.formInputs().register('swatch', (_spec, ctx) => m('div.Swatch', { 'data-name': ctx.name }));
    }
  }

  test('the definition registers types for all its forms', async () => {
    const out = mq(ResourceForm, { definition: new ThingDefinition(), model: null });

    await flush();
    out.redraw();

    expect(out).toHaveElement('.ResourceForm-field--color .Swatch');
    expect(out).toHaveElement('input[name=title]');
  });

  test('fieldInput() can decide a field outright and defer the rest to the resolvers', async () => {
    class OverrideDefinition extends ResourceDefinition<Thing> {
      fieldInput(d: FieldDescriptor) {
        return d.name === 'title' ? { input: { type: 'textarea' as const } } : super.fieldInput(d);
      }
    }

    const fields = (await new OverrideDefinition('things').load()).fields();

    expect(fields.get('title').input).toEqual({ type: 'textarea' });
  });

  test('the inputs attr replaces the factory for one form', async () => {
    const inputs = new ThingDefinition().formInputs().register('text', (_spec, ctx) => m('div.Custom', { 'data-name': ctx.name }));
    const out = mq(ResourceForm, { definition: new ThingDefinition(), model: null, inputs });

    await flush();
    out.redraw();

    expect(out).toHaveElement('.ResourceForm-field--title .Custom');
    expect(out).not.toHaveElement('input[name=title]');
  });
});

describe('RelationInput', () => {
  beforeAll(() => bootAdmin());

  test('Enter in the search box searches at once and does not submit the form', async () => {
    const find = jest.fn(() => Promise.resolve([]));
    jest.spyOn(app.store, 'find').mockImplementation(find as any);

    const input = new RelationInput();
    input.attrs = { type: 'categories', multiple: false, display: 'name', stream: Stream(null) } as any;
    input.query = 'kni';

    const enter = { key: 'Enter', preventDefault: jest.fn() } as unknown as KeyboardEvent;
    const other = { key: 'a', preventDefault: jest.fn() } as unknown as KeyboardEvent;

    input.onsearchkeydown(other);
    input.onsearchkeydown(enter);
    await flush();

    expect(other.preventDefault).not.toHaveBeenCalled();
    expect(enter.preventDefault).toHaveBeenCalled();
    expect(find).toHaveBeenCalledWith('categories', { filter: { q: 'kni' }, page: { offset: 0, limit: 20 } });
  });

  test('a slow earlier search does not overwrite newer results', async () => {
    const first = { id: () => '1' } as any;
    const second = { id: () => '2' } as any;
    let resolveFirst: (page: { items: any[]; hasMore: boolean }) => void = () => {};

    const input = new RelationInput();
    input.attrs = { type: 'categories', multiple: false, display: 'name', stream: Stream(null) } as any;
    input.fetchPage = jest
      .fn<() => Promise<{ items: any[]; hasMore: boolean }>>()
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFirst = resolve)))
      .mockImplementationOnce(() => Promise.resolve({ items: [second], hasMore: false }));

    const slow = input.loadItems();
    await input.loadItems();
    resolveFirst({ items: [first], hasMore: true });
    await slow;

    expect(input.items).toEqual([second]);
    expect(input.isLoading).toBe(false);
  });

  test('pages through results: the next link offers "load more", which appends the next page', async () => {
    const model = (id: string) => ({ id: () => id, attribute: () => `Item ${id}` }) as any;
    const pages: Record<number, any> = {
      0: Object.assign([model('1'), model('2')], { payload: { links: { next: '…' } } }),
      2: Object.assign([model('2'), model('3')], { payload: { links: {} } }),
    };
    // No descriptor for the type: the picker pages, as it does for a paginated type.
    const find = jest.fn((type: string, params: any) =>
      type === 'resource-schemas' ? Promise.reject({ status: 404 }) : Promise.resolve(pages[params.page.offset])
    );
    jest.spyOn(app.store, 'find').mockImplementation(find as any);

    const out = mq(RelationInput, { type: 'categories', multiple: true, display: 'name', stream: Stream([]), pageSize: 2 });
    await flush();
    out.redraw();

    expect(find).toHaveBeenLastCalledWith('categories', { page: { offset: 0, limit: 2 } });
    expect(out.find('.ResourceRelationInput-item')).toHaveLength(2);
    expect(out).toHaveElement('.ResourceRelationInput-more');

    out.click('.ResourceRelationInput-more');
    await flush();
    out.redraw();

    expect(find).toHaveBeenLastCalledWith('categories', { page: { offset: 2, limit: 2 } });
    // Rows shifting between pages are not listed twice.
    expect(out.find('.ResourceRelationInput-item')).toHaveLength(3);
    expect(out).not.toHaveElement('.ResourceRelationInput-more');
  });

  test('without fulltext search on the related type, there is no search box', async () => {
    jest.spyOn(app.store, 'find').mockImplementation(jest.fn(() => Promise.resolve(Object.assign([], { payload: { links: {} } }))) as any);

    const searchable = mq(RelationInput, { type: 'categories', multiple: false, display: 'name', stream: Stream(null) });
    const plain = mq(RelationInput, { type: 'groups', multiple: false, display: 'name', stream: Stream(null), search: false });

    expect(searchable).toHaveElement('input.ResourceRelationInput-search');
    expect(plain).not.toHaveElement('input.ResourceRelationInput-search');
    expect(plain).toHaveElement('select');
  });

  test('a type registered as unsearchable (core groups, registered by the admin user list) has no search box unless the spec asks for one', () => {
    jest.spyOn(app.store, 'find').mockImplementation(jest.fn(() => Promise.resolve(Object.assign([], { payload: { links: {} } }))) as any);

    const groups = mq(RelationInput, { type: 'groups', multiple: true, display: 'nameSingular', stream: Stream([]) });
    const forced = mq(RelationInput, { type: 'groups', multiple: true, display: 'nameSingular', stream: Stream([]), search: true });

    expect(RelationInput.isSearchable('groups')).toBe(false);
    expect(RelationInput.isSearchable('categories')).toBe(true);
    expect(groups).not.toHaveElement('input.ResourceRelationInput-search');
    expect(forced).toHaveElement('input.ResourceRelationInput-search');
  });

  test('the loading row is not selectable', () => {
    const input = new RelationInput();
    input.attrs = { type: 'categories', multiple: false, display: 'name', stream: Stream(null) } as any;
    input.isLoading = true;

    expect(input.options().at(-1)).toMatchObject({ value: 'loading', disabled: true });
  });

  test('the single picker keeps the loaded order, integer-like ids included', async () => {
    const model = (id: string) => ({ id: () => id, attribute: () => `Item ${id}` }) as any;
    jest
      .spyOn(app.store, 'find')
      .mockImplementation(jest.fn(() => Promise.resolve(Object.assign([model('12'), model('3'), model('7')], { payload: { links: {} } }))) as any);

    const out = mq(RelationInput, {
      type: 'categories',
      multiple: false,
      nullable: true,
      placeholder: 'None',
      display: 'name',
      stream: Stream(null),
    });
    out.trigger('select', 'focus', {} as Event);
    await flush();
    out.redraw();

    expect(out.find('option').map((o: any) => o.getAttribute('value'))).toEqual(['', '12', '3', '7']);
    expect(out.first('option').textContent).toBe('None');
  });

  test('a pending search hides "Load more" until its first page lands', () => {
    const input = new RelationInput();
    input.attrs = { type: 'categories', multiple: true, display: 'name', stream: Stream([]) } as any;
    input.hasMore = true;

    input.search('dri');

    expect(input.hasMore).toBe(false);
  });
});

describe('filter and field resolvers', () => {
  beforeAll(() => bootAdmin());

  const filterInput = (filter: Parameters<typeof resolveFilterInput>[0], d: FieldDescriptor | null = null) =>
    resolveFilterInput(filter, d, defaultFilterResolvers()) as InputSpec;

  test('enum choices are labelled by enumLabels, raw values otherwise', () => {
    const field = fd('status', 'string', { constraints: { enum: ['draft', 'live'] }, enumLabels: { draft: 'Draft' } });

    expect((resolveInput(field, defaultInputResolvers()).input as InputSpec).choices).toEqual([
      ['draft', 'Draft'],
      ['live', 'live'],
    ]);
    expect(filterInput({ key: 'status', kind: 'enum', enum: ['draft', 'live'], enumLabels: { live: 'Live' } }, field).choices).toEqual([
      ['draft', 'draft'],
      ['live', 'Live'],
    ]);
    // Without its own labels a filter takes its same-named field's.
    expect(filterInput({ key: 'status', kind: 'enum', enum: ['draft', 'live'] }, field).choices).toEqual([
      ['draft', 'Draft'],
      ['live', 'live'],
    ]);
  });

  test('a multiple enum or relationship filter is marked multiple', () => {
    expect(filterInput({ key: 'status', kind: 'enum', enum: ['a'], multiple: true })).toMatchObject({ type: 'enum', multiple: true });
    expect(filterInput({ key: 'group', kind: 'relationship', related: 'groups', multiple: true })).toEqual({
      type: 'relation',
      related: 'groups',
      multiple: true,
    });
  });

  test('date and number filters get their input, a range filter the range input', () => {
    expect(filterInput({ key: 'joined', kind: 'date' })).toEqual({ type: 'date' });
    expect(filterInput({ key: 'posts', kind: 'number' })).toEqual({ type: 'number' });
    expect(filterInput({ key: 'joined', kind: 'date', range: true })).toEqual({ type: 'range', kind: 'date' });
    expect(filterInput({ key: 'posts', kind: 'number', range: true })).toEqual({ type: 'range', kind: 'number', debounce: true });
  });

  test('relation inputs leave display unset without ui.display', () => {
    const plain = fd('category', 'toOne', { relationship: { types: ['categories'] } });
    const named = fd('category', 'toOne', { relationship: { types: ['categories'] }, ui: { display: 'title' } });

    expect(resolveInput(plain, defaultInputResolvers()).input).not.toHaveProperty('display');
    expect(resolveInput(named, defaultInputResolvers()).input).toMatchObject({ display: 'title' });
  });

  test('ui.hidden starts a column hidden, unless the resolver dropped it', () => {
    expect(resolveInput(fd('secret', 'string', { ui: { hidden: true } }), defaultInputResolvers()).column).toEqual({ hidden: true });
    expect(resolveInput(fd('secret', 'string'), defaultInputResolvers()).column).toBeUndefined();

    const resolvers = new ItemList<InputResolver>().add('none', () => ({ column: false }));
    expect(resolveInput(fd('secret', 'string', { ui: { hidden: true } }), resolvers).column).toBe(false);
  });

  test('a related record reads as its display attribute, else a common name, else its id', () => {
    app.store.models.labelled = Thing;
    let id = 0;
    const record = (attributes: Record<string, unknown>) => app.store.pushObject({ type: 'labelled', id: String(++id), attributes } as any)!;

    expect(relatedRecordLabel(record({ code: 'X1', name: 'Named' }), 'code')).toBe('X1');
    expect(relatedRecordLabel(record({ code: '', title: 'Titled' }), 'code')).toBe('Titled');
    expect(relatedRecordLabel(record({ title: '', username: 'jo' }))).toBe('jo');
    expect(relatedRecordLabel(record({ title: '', username: '' }))).toBe('4');
  });

  test('range values parse and format as from..to, either end empty', () => {
    expect(parseRange('2024-01-01..2024-12-31')).toEqual(['2024-01-01', '2024-12-31']);
    expect(parseRange('..10')).toEqual(['', '10']);
    expect(parseRange('5')).toEqual(['5', '5']);
    expect(parseRange('')).toEqual(['', '']);
    expect(formatRange('2024-01-01', '')).toBe('2024-01-01..');
    expect(formatRange('', '')).toBe('');
    expect(rangeLabel('2024-01-01..')).toContain('2024-01-01');
  });

  test('the range input writes from..to to the stream', () => {
    const stream = Stream('');
    const inputs = new ResourceDefinition('things').filterInputs();
    const out = mq({ view: () => inputs.render({ type: 'range', kind: 'date' }, { name: 'joined', label: 'Joined', stream }) });

    expect(out).toHaveElement('input[name="joined[from]"][type=date]');
    out.setValue('input[name="joined[from]"]', '2024-01-01');
    expect(stream()).toBe('2024-01-01..');

    out.redraw();
    out.setValue('input[name="joined[to]"]', '2024-02-01');
    expect(stream()).toBe('2024-01-01..2024-02-01');

    const numbers = Stream('..10');
    const range = mq({ view: () => inputs.render({ type: 'range', kind: 'number' }, { name: 'posts', stream: numbers }) });

    expect(range).toHaveElementAttr('input[name="posts[to]"]', 'value', '10');
    range.setValue('input[name="posts[to]"]', '');
    expect(numbers()).toBe('');
  });
});
