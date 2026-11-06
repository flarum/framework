import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import RequestError from '../../../../src/common/utils/RequestError';
import type ItemList from '../../../../src/common/utils/ItemList';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { patchField } from '../../../../src/common/resource/fieldsFromDescriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceFormState from '../../../../src/common/resource/ResourceFormState';
import { ValidationError } from '../../../../src/common/resource/validation';
import type { Field, FieldError, FieldValidationContext } from '../../../../src/common/resource/types';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

class Thing extends Model {}

const t = (key: string, params: Record<string, unknown> = {}) => app.translator.trans(`core.lib.resource.validation.${key}`, params, true);

type LocaleMap = Record<string, string | null>;

const things = descriptor([
  fd('name', 'string', { required: { create: true, update: true }, constraints: { maxLength: 5 } }),
  fd('description', 'string', { constraints: { maxLength: 5 } }),
  fd('kind', 'string', { required: { create: true, update: true } }),
  fd('extra', 'string', { ui: { visibleWhen: [{ rule: 'if', fields: ['kind'], values: ['special'] }] } }),
  fd('guessed', 'string', { conditional: true }),
]);

/**
 * A products-like translated field: the form value is a locale map, `*` being the base row. The
 * base row is checked by the descriptor; every other locale by its constraints, and `name` is
 * required on a locale row that any translated field gives a value.
 */
function localeValidate(key: string) {
  return (value: LocaleMap, ctx: FieldValidationContext<Thing, LocaleMap>): string[] => {
    const initial = ctx.initial ?? {};
    const changed = (locale: string) => (value[locale] ?? null) !== (initial[locale] ?? null);
    const errors: string[] = [];

    if (ctx.includeUnchanged || changed('*')) errors.push(...ctx.check(value['*'] ?? null));

    const optional = { ...ctx.descriptor!, required: undefined };
    const required = { ...ctx.descriptor!, required: { create: true, update: true } };
    const rows = new Set<string>();

    ['name', 'description'].forEach((other) => Object.keys(ctx.form.stream<LocaleMap>(other)() ?? {}).forEach((l) => rows.add(l)));
    rows.delete('*');

    rows.forEach((locale) => {
      const now = value[locale] ?? null;

      if (now !== null && now !== '') {
        if (ctx.includeUnchanged || changed(locale)) errors.push(...ctx.check(now, optional).map((m) => `${locale}: ${m}`));
      } else if (key === 'name') {
        errors.push(...ctx.check(null, required).map((m) => `${locale}: ${m}`));
      }
    });

    return errors;
  };
}

class ThingDefinition extends ResourceDefinition<Thing> {
  constructor(private patch: (fields: ItemList<Field<Thing>>) => void = () => {}) {
    super('things');
  }

  fields(): ItemList<Field<Thing>> {
    const fields = super.fields();

    this.patch(fields);

    return fields;
  }
}

function translated(fields: ItemList<Field<Thing>>) {
  for (const key of ['name', 'description']) {
    patchField(fields, key, {
      value: (model) => (model ? { '*': model.attribute<string>(key) ?? null } : {}),
      validate: localeValidate(key) as Field<Thing>['validate'],
    } as Partial<Field<Thing>>);
  }
}

describe('Field.validate', () => {
  let thing: Thing;

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
    thing = app.store.pushObject<Thing>({ type: 'things', id: '1', attributes: { name: 'Knife', description: null, kind: 'plain' } } as any)!;
  });

  afterEach(() => jest.restoreAllMocks());

  test('replaces the descriptor check', async () => {
    const state = await ResourceFormState.create(new ThingDefinition((fields) => patchField(fields, 'name', { validate: () => ['custom'] })), null);

    state.stream('name')('far too long');
    state.stream('kind')('plain');

    expect(state.validate()).toEqual({ name: ['custom'] });
  });

  test('an empty return passes even where the descriptor check would fail', async () => {
    const state = await ResourceFormState.create(new ThingDefinition((fields) => patchField(fields, 'name', { validate: () => [] })), null);

    state.stream('kind')('plain');

    expect(state.validate()).toEqual({});
  });

  test('gets the form, key, descriptor, mode, initial, values, includeUnchanged and dirty', async () => {
    const seen: FieldValidationContext<Thing, unknown>[] = [];
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) =>
        patchField(fields, 'name', {
          validate: (value, ctx) => {
            seen.push({ ...ctx, value } as any);

            return [];
          },
        })
      ),
      thing
    );

    state.stream('name')('Fork');
    state.stream('kind')('other');
    state.validate();

    expect(seen).toHaveLength(1);
    expect(seen[0]).toMatchObject({
      value: 'Fork',
      key: 'name',
      form: state,
      descriptor: things.fields[0],
      mode: 'update',
      initial: 'Knife',
      values: { name: 'Fork', kind: 'other' },
      includeUnchanged: false,
      dirty: true,
    });
  });

  test("ctx.check() yields core's messages for a sub-value, against any descriptor", async () => {
    let checked: string[][] = [];
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) =>
        patchField(fields, 'name', {
          validate: (_value, ctx) => {
            checked = [ctx.check('toolong'), ctx.check(null), ctx.check(null, { ...ctx.descriptor!, required: undefined })];

            return [];
          },
        })
      ),
      null
    );

    state.validate();

    expect(checked).toEqual([[t('max_length', { count: 5 })], [t('required')], []]);
  });

  test('is called for an unchanged field while updating, which decides by includeUnchanged and dirty', async () => {
    const calls: Array<[boolean, boolean]> = [];
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) =>
        patchField(fields, 'description', {
          validate: (_value, ctx) => {
            calls.push([ctx.includeUnchanged, ctx.dirty]);

            return [];
          },
        })
      ),
      thing
    );

    state.validate();

    expect(calls).toEqual([[false, false]]);
  });

  test('a new locale row needs a name although the name field itself is unchanged', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(translated), thing);

    state.stream<LocaleMap>('description')({ '*': null, de: 'Klin' });

    expect(state.isDirty('name')).toBe(false);
    expect(state.validate()).toEqual({ name: [`de: ${t('required')}`] });
  });

  test('a locale value is checked by the constraints; the unchanged base row is not', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(translated), thing);

    state.stream<LocaleMap>('name')({ '*': 'Knife', de: 'Messerchen' });

    expect(state.validate()).toEqual({ name: [`de: ${t('max_length', { count: 5 })}`] });
  });

  test('hidden and conditional fields are not validated', async () => {
    const validate = jest.fn(() => ['nope']);
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) => {
        patchField(fields, 'extra', { validate });
        patchField(fields, 'guessed', { validate });
      }),
      null
    );

    state.stream('name')('Knife');
    state.stream('kind')('plain');

    expect(state.validate()).toEqual({});
    expect(validate).not.toHaveBeenCalled();
  });

  test('a field without a descriptor can validate', async () => {
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) =>
        fields.add('code', { label: 'Code', input: { type: 'text' }, serialize: () => ({}), validate: (v) => (v ? [] : ['Enter a code']) })
      ),
      thing
    );

    expect(state.validate()).toEqual({ code: ['Enter a code'] });
  });

  test('its messages land in form.errors joined by newlines', async () => {
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) => patchField(fields, 'name', { validate: () => ['one', 'two'] })),
      thing
    );

    await expect(state.save()).rejects.toBeInstanceOf(ValidationError);
    expect(state.errors).toEqual({ name: 'one\ntwo' });
  });
});

describe('Field.onclienterrors', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
  });

  afterEach(() => jest.restoreAllMocks());

  test('every visible field hears of a failed client validation, with the messages by field key', async () => {
    const seen: unknown[] = [];
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) => {
        patchField(fields, 'description', { onclienterrors: (errors, form) => seen.push(['description', errors, { ...form.errors }]) });
        patchField(fields, 'extra', { onclienterrors: () => seen.push('hidden') });
      }),
      null
    );

    state.stream('description')('far too long');

    await expect(state.save()).rejects.toBeInstanceOf(ValidationError);

    const errors = {
      name: [{ message: t('required') }],
      description: [{ message: t('max_length', { count: 5 }) }],
      kind: [{ message: t('required') }],
    };

    expect(seen).toEqual([['description', errors, { name: t('required'), description: t('max_length', { count: 5 }), kind: t('required') }]]);
  });

  test('a validate() error with a path keeps it for the hook, while form.errors shows its message', async () => {
    const seen: Record<string, FieldError[]>[] = [];
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) =>
        patchField(fields, 'name', {
          validate: () => ['whole', { message: 'German (de): too long', path: 'de' }],
          onclienterrors: (errors) => seen.push(errors),
        })
      ),
      null
    );

    state.stream('kind')('plain');

    await expect(state.save()).rejects.toBeInstanceOf(ValidationError);

    expect(state.errors).toEqual({ name: 'whole\nGerman (de): too long' });
    expect(seen).toEqual([{ name: [{ message: 'whole' }, { message: 'German (de): too long', path: 'de' }] }]);
    expect(state.fieldErrors('name')).toEqual([{ message: 'whole' }, { message: 'German (de): too long', path: 'de' }]);
  });

  test('fieldErrors() follows validateField()', async () => {
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) => patchField(fields, 'name', { validate: (v) => (v === 'bad' ? [{ message: 'Bad', path: 'fr' }] : []) })),
      null
    );

    state.stream('name')('bad');
    state.validateField('name');
    expect(state.fieldErrors('name')).toEqual([{ message: 'Bad', path: 'fr' }]);

    state.stream('name')('good');
    state.validateField('name');
    expect(state.fieldErrors('name')).toEqual([]);
    expect(state.fieldErrors('kind')).toEqual([]);
  });

  test('is not called when the form is valid', async () => {
    const hook = jest.fn();
    const state = await ResourceFormState.create(new ThingDefinition((fields) => patchField(fields, 'name', { onclienterrors: hook })), null);

    app.request = jest.fn(() => Promise.resolve({ data: { type: 'things', id: '5', attributes: { name: 'Fork', kind: 'plain' } } })) as any;
    state.stream('name')('Fork');
    state.stream('kind')('plain');

    await state.save();

    expect(hook).not.toHaveBeenCalled();
  });

  test('server errors reach onerrors only, resolved by key with their path', async () => {
    const client = jest.fn();
    const server = jest.fn();
    const state = await ResourceFormState.create(
      new ThingDefinition((fields) => patchField(fields, 'name', { onclienterrors: client, onerrors: server, errorKeys: ['names'] })),
      null
    );
    const errors = [
      { detail: 'Taken', source: { pointer: '/data/attributes/name' } },
      { detail: 'Too long', source: { pointer: '/data/attributes/names/de' } },
    ];

    reject(errors);
    state.stream('name')('Fork');
    state.stream('kind')('plain');

    await expect(state.save()).rejects.toBeInstanceOf(RequestError);

    const resolved = [
      { message: 'Taken', pointer: '/data/attributes/name', error: errors[0] },
      { message: 'Too long', path: 'de', pointer: '/data/attributes/names/de', error: errors[1] },
    ];

    expect(client).not.toHaveBeenCalled();
    expect(server).toHaveBeenCalledWith({ name: resolved }, state);
    expect(state.errors).toEqual({ name: 'Taken\nToo long' });
    expect(state.fieldErrors('name')).toEqual(resolved);
  });

  test('a server error is kept by validateField() until a client error replaces it, and cleared by the next save', async () => {
    const state = await ResourceFormState.create(new ThingDefinition(), null);

    reject([{ detail: 'Taken', source: { pointer: '/data/attributes/name' } }]);
    state.stream('name')('Fork');
    state.stream('kind')('plain');
    await expect(state.save()).rejects.toBeInstanceOf(RequestError);

    state.validateField('name');
    state.validateField('kind');
    expect(state.errors).toEqual({ name: 'Taken' });
    expect(state.fieldErrors('name')).toEqual([expect.objectContaining({ message: 'Taken' })]);

    state.stream('name')('far too long');
    state.validateField('name');
    expect(state.fieldErrors('name')).toEqual([{ message: t('max_length', { count: 5 }) }]);

    state.stream('name')('Fork');
    state.validateField('name');
    expect(state.errors).toEqual({});
    expect(state.fieldErrors('name')).toEqual([]);
  });
});

function reject(errors: unknown[]): void {
  app.request = jest.fn(async (options: any) => {
    const error = new RequestError(422, JSON.stringify({ errors }), options, {} as XMLHttpRequest);
    await options.errorHandler?.(error);
    throw error;
  }) as any;
}
