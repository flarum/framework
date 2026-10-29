import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import RequestError from '../../../../src/common/utils/RequestError';
import type ItemList from '../../../../src/common/utils/ItemList';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { patchField } from '../../../../src/common/resource/fieldsFromDescriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceForm, { focusFirstControl } from '../../../../src/common/resource/ResourceForm';
import ResourceFormState from '../../../../src/common/resource/ResourceFormState';
import type { Field, ResourceDescriptor } from '../../../../src/common/resource/types';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

class Thing extends Model {}

const things = descriptor([
  fd('title', 'string', { constraints: { maxLength: 120 }, required: { create: true } }),
  fd('price', 'number'),
  fd('locale', 'string', { writable: { create: true } }),
  fd('createdAt', 'datetime'),
  fd('meta', 'array'),
  fd('secret', 'string'),
  fd('category', 'toOne', { relationship: { types: ['categories'] } }),
]);

class ThingDefinition extends ResourceDefinition<Thing> {
  constructor() {
    super('things');
  }

  fields(): ItemList<Field<Thing>> {
    const fields = super.fields();

    patchField(fields, 'secret', { when: () => false });

    // Two fields writing one `translations` map.
    fields.add('nameDe', { label: 'Name (de)', input: { type: 'text' }, serialize: (v) => ({ translations: { de: { name: v } } }) });
    fields.add('urlDe', { label: 'URL (de)', input: { type: 'url' }, serialize: (v) => ({ translations: { de: { url: v } } }) });

    return fields;
  }
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

function serve(d: ResourceDescriptor) {
  app.request = jest.fn(() => Promise.resolve(schemaDocument(d))) as any;
}

describe('ResourceFormState', () => {
  let thing: Thing;
  let category: Model;

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
    app.store.models.categories = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    serve(things);
    category = app.store.pushObject({ type: 'categories', id: '9', attributes: { name: 'Knives' } } as any)!;
    thing = app.store.pushObject<Thing>({
      type: 'things',
      id: '1',
      attributes: { title: 'Knife', price: 12, locale: 'en', createdAt: '2026-01-01T10:00:00+00:00' },
      relationships: { category: { data: null } },
    } as any)!;
  });

  test('an unchanged field is absent from the save data', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), thing);

    state.stream('title')('Big knife');

    expect(state.buildSaveData()).toEqual({ title: 'Big knife' });
  });

  test('shared() is one stream per key, never sent and kept across reset()', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), thing);
    const open = state.shared('advancedOpen', false);

    expect(state.shared('advancedOpen', true)).toBe(open);
    expect(open()).toBe(false);

    open(true);
    state.stream('title')('Big knife');

    expect(state.buildSaveData()).toEqual({ title: 'Big knife' });

    state.reset();

    expect(state.shared('advancedOpen', false)()).toBe(true);
  });

  test('changed container fields and a changed relationship deep-merge', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), thing);

    state.stream('nameDe')('Messer');
    state.stream('urlDe')('https://example.de');
    state.stream('category')(category);

    expect(state.buildSaveData()).toEqual({
      translations: { de: { name: 'Messer', url: 'https://example.de' } },
      relationships: { category },
    });
  });

  test('clearing a non-nullable number sends null for the server to reject; an untouched empty one is not sent', async () => {
    serve(descriptor([fd('price', 'number', { nullable: false }), fd('stock', 'integer', { nullable: false })]));
    app.store.pushObject({ type: 'things', id: '1', attributes: { price: 12, stock: null } } as any);

    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    state.stream('price')('');

    expect(state.buildSaveData()).toEqual({ price: null });
  });

  test('typing into an empty value and clearing it again is not a change', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), thing);

    state.stream('nameDe')('x');
    state.stream('nameDe')('');

    expect(state.buildSaveData()).toEqual({});
  });

  test('input:false, when()===false and fields not writable in this mode get no stream', async () => {
    const editing = await ResourceFormState.create(new ThingDefinition(), thing);
    const creating = await ResourceFormState.create(new ThingDefinition(), null);

    expect(Object.keys(editing.streams)).not.toContain('meta');
    expect(Object.keys(editing.streams)).not.toContain('secret');
    expect(Object.keys(editing.streams)).not.toContain('locale');
    expect(Object.keys(creating.streams)).toContain('locale');
  });

  test('a new record is created with the changed fields only, and the form adopts the stored record', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), null);
    const request = jest.fn((options: any) => Promise.resolve({ data: { type: 'things', id: '2', attributes: options.body.data.attributes } }));
    app.request = request as any;

    state.stream('title')('New');
    const saved = await state.save();

    expect((request.mock.calls[0] as any)[0]).toMatchObject({ method: 'POST', body: { data: { type: 'things', attributes: { title: 'New' } } } });
    expect(saved.attribute('title')).toBe('New');
    expect(saved).toBe(app.store.getById('things', '2'));
    expect(state.model).toBe(saved);
    expect(state.isCreating()).toBe(false);
    expect(state.dirtyKeys()).toEqual([]);
  });

  test('a new record starts from the descriptor defaults, which are sent only once changed', async () => {
    serve(
      descriptor([
        fd('channel', 'string', { default: 'web' }),
        fd('rank', 'integer', { default: 10 }),
        fd('listed', 'boolean', { default: false }),
        fd('note', 'string', { default: 'own getter' }),
      ])
    );

    class Defaults extends ResourceDefinition<Thing> {
      fields(): ItemList<Field<Thing>> {
        const fields = super.fields();

        // A field with its own getter owns its value's shape; the default is not applied.
        patchField(fields, 'note', { value: () => null });

        return fields;
      }
    }

    const state = await ResourceFormState.create(new Defaults('things'), null);

    expect(state.stream('channel')()).toBe('web');
    expect(state.stream('rank')()).toBe(10);
    expect(state.stream('listed')()).toBe(false);
    expect(state.stream('note')()).toBeNull();
    expect(state.buildSaveData()).toEqual({});

    state.stream('channel')('shop');

    expect(state.buildSaveData()).toEqual({ channel: 'shop' });
  });

  test('defaults apply to new records only, and never over a value the record already has', async () => {
    serve(descriptor([fd('title', 'string', { default: 'Untitled' })]));

    const editing = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);
    const prefilled = app.store.createRecord<Thing>('things', { attributes: { title: 'Draft' } });
    const creating = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), prefilled);

    expect(editing.stream('title')()).toBe('Knife');
    expect(creating.stream('title')()).toBe('Draft');
  });

  test('a 403 not-writable pointer lands on its field', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), thing);
    const body = { errors: [{ status: '403', detail: 'Field [createdAt] is not writable', source: { pointer: '/data/attributes/createdAt' } }] };

    app.request = jest.fn(async (options: any) => {
      const error = new RequestError(403, JSON.stringify(body), options, {} as XMLHttpRequest);
      await options.errorHandler?.(error);
      throw error;
    }) as any;

    state.stream('createdAt')('2026-02-02T10:00');

    await expect(state.save()).rejects.toBeInstanceOf(RequestError);
    expect(state.errors).toEqual({ createdAt: 'Field [createdAt] is not writable' });
  });

  test('every visible field with onerrors sees all raw errors after mapping', async () => {
    const seen: unknown[] = [];

    class HookDefinition extends ThingDefinition {
      fields(): ItemList<Field<Thing>> {
        const fields = super.fields();
        patchField(fields, 'title', { onerrors: (errors, form) => seen.push([errors.length, { ...form.errors }]) });
        patchField(fields, 'secret', { onerrors: () => seen.push('hidden') });

        return fields;
      }
    }

    const state = await ResourceFormState.create(new HookDefinition(), thing);
    const errors = [
      { detail: 'Too short', source: { pointer: '/data/attributes/title' } },
      { detail: 'Bad', source: { pointer: '/data/attributes/translations/de' } },
    ];

    app.request = jest.fn(async (options: any) => {
      const error = new RequestError(422, JSON.stringify({ errors }), options, {} as XMLHttpRequest);
      await options.errorHandler?.(error);
      throw error;
    }) as any;

    state.stream('title')('xyz');
    await expect(state.save()).rejects.toBeInstanceOf(RequestError);

    expect(seen).toEqual([[2, { title: 'Too short', '*': 'Bad' }]]);
  });

  test('422 errors are mapped and suppress the global alert; others fall back to it', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), thing);
    const handled: unknown[] = [];

    app.request = jest.fn(async (options: any) => {
      const error = new RequestError(
        options.method === 'PATCH' && state.stream('title')() === 'bad' ? 422 : 500,
        JSON.stringify({ errors: [{ detail: 'Too short', source: { pointer: '/data/attributes/title' } }] }),
        options,
        {} as XMLHttpRequest
      );
      handled.push(await options.errorHandler?.(error));
      throw error;
    }) as any;

    state.stream('title')('bad');
    await expect(state.save()).rejects.toBeDefined();
    expect(state.errors).toEqual({ title: 'Too short' });

    state.stream('title')('fine');
    await expect(state.save()).rejects.toBeDefined();
    expect(handled).toEqual([undefined, false]);
  });
});

describe('ResourceForm', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    serve(things);
  });

  test('shows a loading state, then the fields with their hints', async () => {
    const out = mq(ResourceForm, { definition: new ThingDefinition(), model: null });

    expect(out).toHaveElement('.LoadingIndicator');

    await flush();
    out.redraw();

    expect(out).toHaveElementAttr('input[name=title]', 'maxlength', '120');
    expect(out).toHaveElementAttr('input[name=title]', 'required');
    expect(out).toHaveElement('input[name=price][type=number]');
    expect(out).not.toHaveElement('[name=meta]');
    expect(out).not.toHaveElement('[name=secret]');
    expect(out).toHaveElement('.ResourceForm-field--category .ResourceRelationInput');
  });

  test('autofocus focuses within the rendered form, once', async () => {
    const autofocus = jest.spyOn(ResourceForm.prototype, 'autofocus').mockImplementation(() => {});

    try {
      const plain = mq(ResourceForm, { definition: new ThingDefinition(), model: null });
      const out = mq(ResourceForm, { definition: new ThingDefinition(), model: null, autofocus: true });

      await flush();
      plain.redraw();
      out.redraw();
      out.redraw();

      expect(autofocus).toHaveBeenCalledTimes(1);
      const root = autofocus.mock.calls[0][0] as Element;
      expect(root.querySelector('input[name=title]')).not.toBeNull();
    } finally {
      autofocus.mockRestore();
    }
  });

  test('focusFirstControl leaves focus alone when it is already in a control of the form', () => {
    const root = document.createElement('form');
    root.innerHTML = '<input type="hidden" name="h"><input name="a"><input name="b">';
    document.body.appendChild(root);

    try {
      (root.querySelector('[name=b]') as HTMLElement).focus();
      focusFirstControl(root);
      expect(document.activeElement?.getAttribute('name')).toBe('b');

      (document.activeElement as HTMLElement).blur();
      focusFirstControl(root);
      expect(document.activeElement?.getAttribute('name')).toBe('a');

      // A select before the first text field is skipped.
      root.insertAdjacentHTML('afterbegin', '<select name="locale"><option>de</option></select>');
      (document.activeElement as HTMLElement).blur();
      focusFirstControl(root);
      expect(document.activeElement?.getAttribute('name')).toBe('a');
    } finally {
      root.remove();
    }
  });

  test('renders field errors under their field and the rest above submit', async () => {
    let state: ResourceFormState<Thing> | null = null;
    const out = mq(ResourceForm, { definition: new ThingDefinition(), onstate: (s: ResourceFormState<Thing>) => (state = s) });

    await flush();
    state!.errors = { title: 'Too short', '*': 'Something else' };
    out.redraw();

    expect(out).toContainRaw('Too short');
    expect(out).toHaveElement('.ResourceForm-field--title.has-error');
    expect(out).toHaveElement('.ResourceForm-errors');
    expect(out).toContainRaw('Something else');
  });

  test('a rejected descriptor shows the load error', async () => {
    app.request = jest.fn(() => Promise.reject({ status: 403 })) as any;

    const out = mq(ResourceForm, { definition: new ThingDefinition() });
    await flush();
    out.redraw();

    expect(out).toHaveElement('.ResourceForm--error');
    expect(out).not.toContainRaw('core.lib.resource');
  });
});
