import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import ItemList from '../../../../src/common/utils/ItemList';
import Stream from '../../../../src/common/utils/Stream';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { fieldsFromDescriptor, patchField, patchFilter } from '../../../../src/common/resource/fieldsFromDescriptor';
import { defaultInputResolvers, serializer, toLocalDateTime, WIDGET_ALIASES } from '../../../../src/common/resource/resolvers';
import { defaultFormInputs } from '../../../../src/common/resource/inputs/defaults';
import InputFactory from '../../../../src/common/resource/inputs/InputFactory';
import RelationInput from '../../../../src/common/resource/inputs/RelationInput';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import type { Field, Filter, InputResolver } from '../../../../src/common/resource/types';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

function inputOf(field: Field<any>): any {
  return field.input;
}

function single(d: ReturnType<typeof fd>) {
  return fieldsFromDescriptor(descriptor([d]), defaultInputResolvers()).get(d.name);
}

describe('fieldsFromDescriptor', () => {
  beforeAll(() => bootAdmin());

  test('string → text input with maxlength', () => {
    expect(inputOf(single(fd('title', 'string', { constraints: { maxLength: 120 } })))).toMatchObject({ type: 'text', maxlength: 120 });
  });

  test('constraints.enum → enum input over the values, in order', () => {
    const input = inputOf(single(fd('status', 'string', { constraints: { enum: ['2', '10', 'draft'] }, nullable: false })));

    expect(input).toEqual({
      type: 'enum',
      choices: [
        ['2', '2'],
        ['10', '10'],
        ['draft', 'draft'],
      ],
      nullable: false,
    });
  });

  test('nullable enum offers an empty choice first, even before integer-like values', () => {
    const input = inputOf(single(fd('status', 'string', { constraints: { enum: ['1', '0'] } })));
    const out = mq({ view: () => new InputFactory(defaultFormInputs()).render(input, { name: 'status', label: 'Status', stream: Stream(null) }) });

    expect(out.find('option').map((o: any) => o.getAttribute('value'))).toEqual(['', '1', '0']);
    expect(out).toHaveElementAttr('label', 'for', 'ResourceForm-input-status');
    expect(out).toHaveElement('select#ResourceForm-input-status');
  });

  test('a required enum without a value shows a disabled blank choice', () => {
    const input = inputOf(single(fd('status', 'string', { constraints: { enum: ['draft', 'live'] }, nullable: false })));
    const factory = new InputFactory(defaultFormInputs());
    const empty = mq({ view: () => factory.render(input, { name: 'status', stream: Stream(null) }) });
    const set = mq({ view: () => factory.render(input, { name: 'status', stream: Stream('live') }) });

    expect(empty.find('option').map((o: any) => o.getAttribute('value'))).toEqual(['', 'draft', 'live']);
    expect(empty).toHaveElement('option[value=""][disabled]');
    expect(set.find('option').map((o: any) => o.getAttribute('value'))).toEqual(['draft', 'live']);
  });

  test('url and email formats → typed inputs', () => {
    expect(inputOf(single(fd('homepage', 'string', { constraints: { format: 'url' } }))).type).toBe('url');
    expect(inputOf(single(fd('contact', 'string', { constraints: { format: 'email' } }))).type).toBe('email');
  });

  test('long strings → textarea', () => {
    expect(inputOf(single(fd('body', 'string', { constraints: { maxLength: 65535 } }))).type).toBe('textarea');
  });

  test('integer and number → number input with min/max', () => {
    expect(inputOf(single(fd('stock', 'integer', { constraints: { minimum: 0, maximum: 10 } })))).toMatchObject({
      type: 'number',
      min: 0,
      max: 10,
      step: 1,
    });
    expect(inputOf(single(fd('price', 'number')))).toMatchObject({ type: 'number', step: 'any' });
  });

  test('boolean → switch', () => {
    expect(inputOf(single(fd('active', 'boolean'))).type).toBe('switch');
  });

  test('date and datetime → date / datetime-local', () => {
    expect(inputOf(single(fd('releasedOn', 'date'))).type).toBe('date');
    expect(inputOf(single(fd('publishedAt', 'datetime'))).type).toBe('datetime-local');
  });

  test('an empty value is "" for a non-nullable string, else null', () => {
    for (const kind of ['integer', 'number', 'date', 'datetime'] as const) {
      expect(single(fd('x', kind, { nullable: true })).serialize!('')).toEqual({ x: null });
      expect(single(fd('x', kind, { nullable: false })).serialize!('')).toEqual({ x: null });
    }

    expect(single(fd('x', 'string', { nullable: true })).serialize!('')).toEqual({ x: null });
    expect(single(fd('x', 'string', { nullable: false })).serialize!('')).toEqual({ x: '' });
    expect(single(fd('x', 'integer')).serialize!('3')).toEqual({ x: 3 });
  });

  test('array has no input', () => {
    expect(single(fd('meta', 'array')).input).toBe(false);
  });

  test('toOne → single relation picker over relationship.types[0], labelled by name', () => {
    const input = inputOf(single(fd('category', 'toOne', { relationship: { types: ['categories'] } })));

    expect(input).toMatchObject({ type: 'relation', related: 'categories', multiple: false, display: 'name' });

    const group = new InputFactory(defaultFormInputs()).render(input, { name: 'category', stream: Stream(null) }) as any;
    const vnode = group.children.find((child: any) => child?.tag === RelationInput);

    expect(vnode.attrs).toMatchObject({ type: 'categories', multiple: false, display: 'name' });
  });

  test('toMany → multiple relation picker, display from ui.display', () => {
    const input = inputOf(single(fd('groups', 'toMany', { relationship: { types: ['groups'] }, ui: { display: 'nameSingular' } })));

    expect(input).toMatchObject({ type: 'relation', related: 'groups', multiple: true, display: 'nameSingular' });
  });

  test('a relation without a type has no input', () => {
    expect(single(fd('owner', 'toOne', { relationship: { types: [] } })).input).toBe(false);
  });

  test('non-writable fields have no input', () => {
    expect(single(fd('createdAt', 'datetime', { writable: undefined })).input).toBe(false);
  });

  test('create-only fields keep their input', () => {
    expect(inputOf(single(fd('locale', 'string', { writable: { create: true } }))).type).toBe('text');
  });

  test('visible:false with writable.create is input-only, with no column', () => {
    const field = single(fd('password', 'string', { visible: false, writable: { create: true }, sortable: true, ui: { column: true } }));

    expect(inputOf(field).type).toBe('text');
    expect(field.column).toBe(false);
  });

  test('label and descriptor are set', () => {
    const field = single(fd('title', 'string', { label: 'Product title' }));

    expect(field.label).toBe('Product title');
    expect(field.descriptor?.name).toBe('title');
  });

  test('a column when sortable (sorting by the field name) or ui.column; else none', () => {
    const fields = fieldsFromDescriptor(
      descriptor([fd('title', 'string', { sortable: true }), fd('note', 'string', { ui: { column: true } }), fd('body', 'string')]),
      defaultInputResolvers()
    );

    expect(fields.get('title').column).toMatchObject({ sort: 'title' });
    expect(fields.get('note').column).not.toBe(false);
    expect((fields.get('note').column as any).sort).toBeUndefined();
    expect(fields.get('body').column).toBe(false);
  });

  test('a numeric ui.column is a column with that priority', () => {
    expect(single(fd('username', 'string', { ui: { column: 90 } })).column).toEqual({ priority: 90 });
  });

  test('ui.column false hides a sortable column', () => {
    expect(single(fd('title', 'string', { sortable: true, ui: { column: false } })).column).toBe(false);
  });

  test("ui.widget 'hidden' gives no form input, even when writable", () => {
    const field = single(fd('token', 'string', { visible: false, writable: { create: true }, ui: { widget: 'hidden' } }));

    expect(field.input).toBe(false);
    expect(field.column).toBe(false);
  });

  test("ui.widget 'color' → the colour preview input, an empty value sent as null", () => {
    const field = single(fd('color', 'string', { nullable: true, writable: { create: true, update: true }, ui: { widget: 'color' } }));

    expect(inputOf(field)).toEqual({ type: 'color-preview' });
    expect(field.serialize!('')).toEqual({ color: null });
    expect(field.serialize!('#aabbcc')).toEqual({ color: '#aabbcc' });
    // The inputs read a string, so a missing value starts empty.
    expect(field.value!(null)).toBe('');
  });

  test("ui.widget 'icon' → the icon input", () => {
    const field = single(fd('icon', 'string', { nullable: true, writable: { update: true }, ui: { widget: 'icon' } }));

    expect(inputOf(field)).toEqual({ type: 'icon' });
    expect(field.serialize!('')).toEqual({ icon: null });
  });

  test('another ui.widget value is the input type, for a renderer an extension registers', () => {
    expect(inputOf(single(fd('bio', 'string', { writable: { update: true }, constraints: { maxLength: 500 }, ui: { widget: 'markdown' } })))).toEqual(
      {
        type: 'markdown',
        maxlength: 500,
      }
    );
  });

  test('ui.widget only applies to strings', () => {
    expect(inputOf(single(fd('count', 'integer', { writable: { update: true }, ui: { widget: 'color' } })))).toMatchObject({ type: 'number' });
  });

  test('ui.placeholder lands on the input spec', () => {
    expect(inputOf(single(fd('title', 'string', { writable: { update: true }, ui: { placeholder: 'e.g. Mods' } })))).toMatchObject({
      type: 'text',
      placeholder: 'e.g. Mods',
    });
    expect(inputOf(single(fd('icon', 'string', { writable: { update: true }, ui: { widget: 'icon', placeholder: 'fas fa-bolt' } })))).toEqual({
      type: 'icon',
      placeholder: 'fas fa-bolt',
    });
  });

  test('WIDGET_ALIASES maps color to color-preview', () => {
    expect(WIDGET_ALIASES).toEqual({ color: 'color-preview' });
  });

  test('declaration order; ui.priority is ignored', () => {
    const fields = fieldsFromDescriptor(
      descriptor([fd('a', 'string'), fd('b', 'string'), fd('c', 'string', { ui: { priority: 10000 } })]),
      defaultInputResolvers()
    );

    expect(fields.toArray().map((f) => f.descriptor!.name)).toEqual(['a', 'b', 'c']);
  });
});

describe('input resolvers', () => {
  test('a higher-priority resolver wins', () => {
    const resolvers = defaultInputResolvers();
    resolvers.add('custom', (d) => (d.name === 'title' ? { input: { type: 'color-preview' } } : null), 1000);

    const fields = fieldsFromDescriptor(descriptor([fd('title', 'string'), fd('other', 'string')]), resolvers);

    expect(inputOf(fields.get('title')).type).toBe('color-preview');
    expect(inputOf(fields.get('other')).type).toBe('text');
  });

  test('null falls through to the next resolver', () => {
    const resolvers = new ItemList<InputResolver>();
    resolvers.add('never', () => null, 100);
    resolvers.add('fallback', () => ({ input: { type: 'tel' } }), 0);

    expect(inputOf(fieldsFromDescriptor(descriptor([fd('phone', 'string')]), resolvers).get('phone')).type).toBe('tel');
  });
});

describe('patchField', () => {
  test('merges shallowly and keeps other keys', () => {
    const fields = fieldsFromDescriptor(descriptor([fd('url', 'string', { sortable: true })]), defaultInputResolvers());
    const render = () => 'link';

    patchField(fields, 'url', { column: { render } });

    const field = fields.get('url');
    expect(field.column).toEqual({ render });
    expect(inputOf(field).type).toBe('text');
    expect(field.label).toBe('Url');
  });

  test('is a no-op for an absent key', () => {
    const fields = fieldsFromDescriptor(descriptor([fd('a', 'string')]), defaultInputResolvers());

    patchField(fields, 'missing', { label: 'x' });

    expect(fields.has('missing')).toBe(false);
  });
});

describe('ResourceDefinition', () => {
  beforeAll(() => bootAdmin());
  beforeEach(() => clearDescriptorCache());

  test('fields() before load() throws', () => {
    expect(() => new ResourceDefinition('things').fields()).toThrow(/load\(\)/);
  });

  test('canCreate follows capabilities.create', async () => {
    app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([], { capabilities: { list: true, create: true } })))) as any;
    expect((await new ResourceDefinition('things').load()).canCreate()).toBe(true);

    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([], { capabilities: { list: true, create: false } })))) as any;
    expect((await new ResourceDefinition('things').load()).canCreate()).toBe(false);

    expect(new ResourceDefinition('things').canCreate()).toBe(false);
  });

  test('label humanizes the type', () => {
    expect(new ResourceDefinition('product-categories').label(2)).toBe('Product categories');
  });
});

describe('patchFilter', () => {
  test('merges shallowly; a no-op for an absent key', () => {
    const filters = new ItemList<Filter>().add('type', { label: 'Type', input: { type: 'text' }, negatable: true });

    patchFilter(filters, 'type', { input: { type: 'enum', choices: [] } });
    patchFilter(filters, 'missing', { label: 'Missing' });

    expect(filters.get('type')).toEqual({ label: 'Type', input: { type: 'enum', choices: [] }, negatable: true });
    expect(filters.has('missing')).toBe(false);
  });
});

describe('resolver helpers', () => {
  test('serializer() converts a value and sends an empty one as emptyValue, or null when nullable or without one', () => {
    expect(serializer(fd('x', 'integer'), Number)!('3')).toEqual({ x: 3 });
    expect(serializer(fd('x', 'string', { nullable: false }), (v) => v, '')!('')).toEqual({ x: '' });
    expect(serializer(fd('x', 'string', { nullable: true }), (v) => v, '')!('')).toEqual({ x: null });
    expect(serializer(fd('x', 'integer'))!(null)).toEqual({ x: null });
  });

  test('toLocalDateTime() gives the local YYYY-MM-DDTHH:mm, null for empty or invalid', () => {
    expect(toLocalDateTime(new Date(2026, 0, 2, 3, 4).toISOString())).toBe('2026-01-02T03:04');
    expect(toLocalDateTime('')).toBeNull();
    expect(toLocalDateTime('nope')).toBeNull();
  });
});
