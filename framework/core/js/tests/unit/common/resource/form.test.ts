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

  describe('a field saved through its own request', () => {
    /** A pending upload: not plain data, compared by `isChanged()`, sent by `afterSave()`. */
    class Pending {
      /** A file to upload, null to remove the current one, undefined to leave it. */
      constructor(public change?: string | null) {}
    }

    function uploadDefinition(afterSave: Field<Thing, Pending>['afterSave']) {
      return new (class extends ThingDefinition {
        fields() {
          const fields = super.fields();

          fields.add('upload', {
            label: 'Upload',
            input: () => null,
            value: () => new Pending(),
            serialize: () => ({}),
            isChanged: (value: Pending) => value.change !== undefined,
            afterSave,
          } as Field<Thing, Pending> as Field<Thing>);

          return fields;
        }
      })();
    }

    test('isChanged() decides whether the value is a change, in place of comparing snapshots', async () => {
      const state = await ResourceFormState.create(uploadDefinition(undefined), thing);

      state.stream('upload')(new Pending());
      expect(state.dirtyKeys()).toEqual([]);

      // Removing snapshots like leaving it (undefined and null both become null).
      state.stream('upload')(new Pending(null));
      expect(state.dirtyKeys()).toEqual(['upload']);
    });

    test('afterSave() runs once a new record exists, with the stored record and the value before the reset', async () => {
      const afterSave = jest.fn(async () => {});
      const state = await ResourceFormState.create(uploadDefinition(afterSave), null);
      app.request = jest.fn((options: any) =>
        Promise.resolve({ data: { type: 'things', id: '2', attributes: options.body.data.attributes } })
      ) as any;

      state.stream('title')('New');
      const pending = new Pending('a.png');
      state.stream('upload')(pending);
      const saved = await state.save();

      expect(afterSave).toHaveBeenCalledTimes(1);
      expect((afterSave.mock.calls[0] as any[])[0]).toBe(saved);
      expect((afterSave.mock.calls[0] as any[])[1]).toBe(pending);
      expect(state.dirtyKeys()).toEqual([]);
    });

    test('afterSave() is not called for an unchanged value', async () => {
      const afterSave = jest.fn(async () => {});
      const state = await ResourceFormState.create(uploadDefinition(afterSave), thing);
      app.request = jest.fn((options: any) =>
        Promise.resolve({ data: { type: 'things', id: '1', attributes: options.body.data.attributes } })
      ) as any;

      state.stream('title')('Renamed');
      await state.save();

      expect(afterSave).not.toHaveBeenCalled();
    });

    test('a failed afterSave() rejects the save, keeps the change and the created record, and maps its 422 onto the field', async () => {
      const request = jest.fn(async (options: any) => {
        if (options.url.endsWith('/upload')) {
          const error = new RequestError(
            422,
            JSON.stringify({ errors: [{ detail: 'Not an image', source: { pointer: '/data/attributes/upload' } }] }),
            options,
            {} as XMLHttpRequest
          );
          await options.errorHandler?.(error);
          throw error;
        }

        return { data: { type: 'things', id: '2', attributes: options.body?.data?.attributes ?? {} } };
      });
      const state = await ResourceFormState.create(
        uploadDefinition(
          (model, _value, { errorHandler }) => app.request({ method: 'POST', url: `/things/${model.id()}/upload`, errorHandler }) as any
        ),
        null
      );
      app.request = request as any;

      state.stream('title')('New');
      const pending = new Pending('a.png');
      state.stream('upload')(pending);

      await expect(state.save()).rejects.toBeInstanceOf(RequestError);
      expect(state.errors).toEqual({ upload: 'Not an image' });
      expect(state.isCreating()).toBe(false);
      expect(state.stream('upload')()).toBe(pending);
      expect(state.dirtyKeys()).toEqual(['upload']);

      // The retry uploads to the created record, which has nothing else to send, rather than creating another one.
      await expect(state.save()).rejects.toBeInstanceOf(RequestError);
      expect(request.mock.calls.map((call: any) => [call[0].method, call[0].url])).toEqual([
        ['POST', 'https://example.test/api/things'],
        ['POST', '/things/2/upload'],
        ['POST', '/things/2/upload'],
      ]);
    });
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

  test('every visible field with onerrors sees all resolved errors by key after mapping', async () => {
    const seen: unknown[] = [];

    class HookDefinition extends ThingDefinition {
      fields(): ItemList<Field<Thing>> {
        const fields = super.fields();
        patchField(fields, 'title', { onerrors: (errors, form) => seen.push([errors, { ...form.errors }]) });
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

    expect(seen).toEqual([
      [
        {
          title: [{ message: 'Too short', pointer: '/data/attributes/title', error: errors[0] }],
          '*': [{ message: 'Bad', pointer: '/data/attributes/translations/de', error: errors[1] }],
        },
        { title: 'Too short', '*': 'Bad' },
      ],
    ]);
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

  test('the required hint and error follow a requiredWhen sibling on redraw', async () => {
    serve(
      descriptor([
        fd('a', 'string', { requiredWhen: { create: [{ rule: 'without', fields: ['b'] }] } }),
        fd('b', 'string'),
        fd('c', 'string', { requiredConditional: true }),
      ])
    );

    let state!: ResourceFormState<Thing>;
    const out = mq(ResourceForm, {
      definition: new ResourceDefinition<Thing>('things'),
      model: null,
      onstate: (s: ResourceFormState<Thing>) => (state = s),
    });

    await flush();
    out.redraw();

    expect(out).toHaveElementAttr('input[name=a]', 'required');
    expect(out).not.toHaveElementAttr('input[name=c]', 'required');

    await expect(state.save()).rejects.toThrow();
    out.redraw();
    expect(out).toHaveElement('.ResourceForm-field--a.has-error');
    expect(state.errors.c).toBeUndefined();

    state.stream('b')('x');
    state.validateField('b');
    out.redraw();
    expect(out).not.toHaveElementAttr('input[name=a]', 'required');
    expect(out).not.toHaveElement('.ResourceForm-field--a.has-error');

    state.stream('b')('');
    out.redraw();
    expect(out).toHaveElementAttr('input[name=a]', 'required');

    await expect(state.save()).rejects.toThrow();
    out.redraw();
    expect(out).toHaveElement('.ResourceForm-field--a.has-error');
  });

  test('a field error is an alert the invalid control points at', async () => {
    serve(
      descriptor([
        fd('name', 'string', { required: { create: true, update: true } }),
        fd('kind', 'string', { required: { create: true, update: true }, constraints: { enum: ['a', 'b'] } }),
      ])
    );

    let state!: ResourceFormState<Thing>;
    const out = mq(ResourceForm, {
      definition: new ResourceDefinition<Thing>('things'),
      model: null,
      onstate: (s: ResourceFormState<Thing>) => (state = s),
    });

    await flush();
    out.redraw();

    expect(out).not.toHaveElement('input[name=name][aria-invalid]');

    await expect(state.save()).rejects.toThrow();
    out.redraw();

    for (const [key, control] of [
      ['name', 'input'],
      ['kind', 'select'],
    ]) {
      const error = out.first(`.ResourceForm-field--${key} .ResourceForm-error`);

      expect(error.getAttribute('role')).toBe('alert');
      expect(error.id).toBe(`${state.domId}-${key}-error`);
      expect(out.first(`${control}[name=${key}]`).getAttribute('aria-invalid')).toBe('true');
      expect(out.first(`${control}[name=${key}]`).getAttribute('aria-describedby')).toBe(error.id);
    }
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

describe('ResourceFormState visibility', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => clearDescriptorCache());

  const conditional = () =>
    descriptor([
      fd('kind', 'string', { constraints: { enum: ['plain', 'link'] } }),
      fd('url', 'string', {
        required: { create: true, update: true },
        constraints: { format: 'uri' },
        ui: { visibleWhen: [{ rule: 'if', fields: ['kind'], values: ['link'] }] },
      }),
    ]);

  test('a ui.visibleWhen field shows while a condition holds; hidden, it is neither validated nor sent', async () => {
    serve(conditional());

    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    expect(state.visibleFields().has('url')).toBe(false);
    expect(state.streams.url).toBeDefined();
    expect(state.validate()).toEqual({});

    state.stream('url')('not a url');
    state.stream('kind')('plain');

    expect(state.validateField('url')).toEqual([]);
    expect(state.buildSaveData()).toEqual({ kind: 'plain' });
    expect(state.values()).toEqual({ kind: 'plain' });

    state.stream('kind')('link');

    expect(state.visibleFields().has('url')).toBe(true);
    expect(Object.keys(state.validate())).toEqual(['url']);
    expect(state.buildSaveData()).toEqual({ kind: 'link', url: 'not a url' });
  });

  test('a hidden field counts as absent for the others: a visibleWhen chain A → B → C hides together', async () => {
    serve(
      descriptor([
        fd('a', 'string'),
        fd('b', 'string', { ui: { visibleWhen: [{ rule: 'if', fields: ['a'], values: ['yes'] }] } }),
        fd('c', 'string', { ui: { visibleWhen: [{ rule: 'with', fields: ['b'] }] } }),
      ])
    );

    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    state.stream('a')('yes');
    state.stream('b')('filled');

    expect(Object.keys(state.visibleFields().toObject())).toEqual(['a', 'b', 'c']);
    expect(state.liveValues()).toEqual({ a: 'yes', b: 'filled', c: null });

    // `b` keeps its value but is hidden, so `c` loses its condition too.
    state.stream('a')('no');

    expect(Object.keys(state.visibleFields().toObject())).toEqual(['a']);
    expect(state.isVisible('c')).toBe(false);
    expect(state.liveValues()).toEqual({ a: 'no' });
    expect(state.buildSaveData()).toEqual({ a: 'no' });
  });

  test('while updating, a sibling recheck clears the error of a field reverted to its unchanged value', async () => {
    serve(descriptor([fd('name', 'string', { constraints: { minLength: 3 } }), fd('bio', 'string')]));

    const thing = app.store.pushObject<Thing>({ type: 'things', id: '6', attributes: { name: 'ab', bio: '' } } as any)!;
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    state.stream('name')('x');
    state.validateField('name');
    expect(state.errors.name).toBeDefined();

    // Back to the stored value: not sent, so not the client's to reject.
    state.stream('name')('ab');
    state.stream('bio')('hi');
    state.validateField('bio');

    expect(state.errors.name).toBeUndefined();
  });

  test('ui.visibleWhen follows unchanged values while updating', async () => {
    serve(conditional());

    const thing = app.store.pushObject<Thing>({ type: 'things', id: '5', attributes: { kind: 'link', url: 'https://a.b' } } as any)!;
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    expect(state.visibleFields().has('url')).toBe(true);
    expect(state.dirtyKeys()).toEqual([]);
  });

  test('when() gets the live values; a one-argument when() keeps working', async () => {
    serve(descriptor([fd('a', 'string'), fd('b', 'string'), fd('c', 'string')]));

    const seen: Array<Record<string, unknown> | undefined> = [];

    class Definition extends ResourceDefinition<Thing> {
      fields(): ItemList<Field<Thing>> {
        const fields = super.fields();

        patchField(fields, 'b', {
          when: (_m, values) => {
            seen.push(values);

            return !values || values.a === 'show';
          },
        });
        patchField(fields, 'c', { when: (m) => m === null });

        return fields;
      }
    }

    const state = await ResourceFormState.create(new Definition('things'), null);

    expect(Object.keys(state.streams)).toEqual(['a', 'b', 'c']);
    expect(seen[0]).toBeUndefined();
    expect(Object.keys(state.visibleFields().toObject())).toEqual(['a', 'c']);

    state.stream('a')('show');

    expect(Object.keys(state.visibleFields().toObject())).toEqual(['a', 'b', 'c']);
    expect(seen[seen.length - 1]).toEqual({ a: 'show', b: null, c: null });
  });
});

describe('ResourceForm rendering hints', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => clearDescriptorCache());

  async function renderForm(d: ResourceDescriptor, model: Thing | null = null) {
    serve(d);

    let state!: ResourceFormState<Thing>;
    const out = mq(ResourceForm, {
      definition: new ResourceDefinition<Thing>('things'),
      model,
      onstate: (s: ResourceFormState<Thing>) => (state = s),
    });

    await flush();
    out.redraw();

    return { out, state };
  }

  test('the form is novalidate; ui.help shows as the help text; ui.markRequired marks without enforcing', async () => {
    const { out, state } = await renderForm(
      descriptor([
        fd('title', 'string', { ui: { help: 'Shown in lists.' } }),
        fd('code', 'string', { requiredConditional: true, ui: { markRequired: true } }),
      ])
    );

    expect((out.rootEl as HTMLElement).querySelector('form.ResourceForm')!.hasAttribute('novalidate')).toBe(true);
    expect(out).toContainRaw('Shown in lists.');
    expect(out).toHaveElementAttr('input[name=code]', 'required');
    expect(out).toHaveElement('.ResourceForm-field--code.ResourceForm-field--required');
    expect(state.validate()).toEqual({});
  });

  test('native attributes follow the constraints of the mode', async () => {
    const d = descriptor([
      fd('title', 'string', { constraints: { minLength: 2, maxLength: 10 }, constraintsOnUpdate: { maxLength: 20 } }),
      fd('count', 'integer', { constraints: { minimum: 1, maximum: 9 } }),
      fd('price', 'number', { constraints: { multipleOf: 0.5 } }),
      fd('ratio', 'number'),
    ]);

    const { out } = await renderForm(d);

    expect(out).toHaveElementAttr('input[name=title]', 'minlength', '2');
    expect(out).toHaveElementAttr('input[name=title]', 'maxlength', '10');
    expect(out).toHaveElementAttr('input[name=count]', 'min', '1');
    expect(out).toHaveElementAttr('input[name=count]', 'max', '9');
    expect(out).toHaveElementAttr('input[name=count]', 'step', '1');
    expect(out).toHaveElementAttr('input[name=price]', 'step', '0.5');
    expect(out).toHaveElementAttr('input[name=ratio]', 'step', 'any');
    expect(out).not.toHaveElementAttr('input[name=title]', 'required');

    clearDescriptorCache();
    const thing = app.store.pushObject<Thing>({ type: 'things', id: '7', attributes: { title: 'x' } } as any)!;
    const editing = await renderForm(d, thing);

    expect(editing.out).toHaveElementAttr('input[name=title]', 'maxlength', '20');
  });

  test('ui.group renders fieldsets after the ungrouped fields, in first-appearance order', async () => {
    const { out } = await renderForm(
      descriptor([
        fd('a', 'string', { ui: { group: 'Second' } }),
        fd('b', 'string'),
        fd('c', 'string', { ui: { group: 'Third' } }),
        fd('d', 'string', { ui: { group: 'Second' } }),
        fd('e', 'string'),
      ])
    );

    const form = out.rootEl as HTMLElement;
    const order = [...form.querySelectorAll('legend, input')].map((el) => (el.tagName === 'LEGEND' ? el.textContent : (el as HTMLInputElement).name));

    expect(order).toEqual(['b', 'e', 'Second', 'a', 'd', 'Third', 'c']);
    expect(form.querySelectorAll('fieldset.ResourceForm-group')).toHaveLength(2);
    expect(form.querySelector('.ResourceForm-submit')!.compareDocumentPosition(form.querySelector('fieldset')!)).toBe(
      Node.DOCUMENT_POSITION_PRECEDING
    );
  });
});
