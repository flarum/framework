import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceFormState from '../../../../src/common/resource/ResourceFormState';
import { ValidationError, validateValue } from '../../../../src/common/resource/validation';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

class Thing extends Model {}

const t = (key: string, params: Record<string, unknown> = {}) => app.translator.trans(`core.lib.resource.validation.${key}`, params, true);

describe('validateValue', () => {
  beforeAll(() => bootAdmin({ debug: true }));
  afterEach(() => jest.restoreAllMocks());

  test('required.create with an empty value yields the required message', () => {
    const field = fd('title', 'string', { required: { create: true } });

    for (const value of ['', null, []]) {
      expect(validateValue(field, value, 'create')).toEqual([t('required')]);
    }

    expect(t('required')).not.toContain('core.lib.resource');
  });

  test('minLength and maxLength fail outside the bounds; an empty, non-required value passes every constraint', () => {
    const field = fd('username', 'string', {
      constraints: { minLength: 3, maxLength: 30, pattern: '^[a-z]+$', format: 'email', enum: ['abc'], minimum: 1, maximum: 2 },
    });
    const lengths = fd('username', 'string', { constraints: { minLength: 3, maxLength: 30 } });

    expect(validateValue(lengths, 'ab', 'create')).toEqual([t('min_length', { count: 3 })]);
    expect(validateValue(lengths, 'a'.repeat(31), 'create')).toEqual([t('max_length', { count: 30 })]);
    expect(validateValue(lengths, 'abc', 'create')).toEqual([]);

    for (const value of ['', null, undefined]) {
      expect(validateValue(field, value, 'create')).toEqual([]);
    }
  });

  test('pattern compiles with patternFlags', () => {
    const field = fd('username', 'string', { constraints: { pattern: '^(?![0-9]*$)[a-z0-9_-]+$', patternFlags: 'i' } });

    expect(validateValue(field, 'Simon', 'create')).toEqual([]);
    expect(validateValue(field, '123', 'create')).toEqual([t('pattern')]);
  });

  test('a pattern JS cannot compile is skipped, with one debug warning', () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    const field = fd('code', 'string', { constraints: { pattern: 'a++' } });

    expect(validateValue(field, 'b', 'create')).toEqual([]);
    expect(validateValue(field, 'c', 'update')).toEqual([]);
    expect(warn).toHaveBeenCalledTimes(1);
  });

  test('format email and url', () => {
    const email = fd('email', 'string', { constraints: { format: 'email' } });
    const url = fd('homepage', 'string', { constraints: { format: 'url' } });

    expect(validateValue(email, 'a@b', 'create')).toEqual([]);
    expect(validateValue(email, 'nope', 'create')).toEqual([t('email')]);
    expect(validateValue(url, 'https://x.y', 'create')).toEqual([]);
    expect(validateValue(url, 'x.y', 'create')).toEqual([t('url')]);
    expect(validateValue(url, 'nope', 'create')).toEqual([t('url')]);
  });

  test('enum and minimum', () => {
    expect(validateValue(fd('status', 'string', { constraints: { enum: ['a', 'b'] } }), 'c', 'create')).toEqual([t('enum')]);
    expect(validateValue(fd('rank', 'integer', { constraints: { minimum: 1 } }), 0, 'create')).toEqual([t('minimum', { min: 1 })]);
  });
});

describe('validateValue requirements', () => {
  beforeAll(() => bootAdmin());

  test('requiredWhen is evaluated against the given values', () => {
    const field = fd('a', 'string', { requiredWhen: { create: [{ rule: 'without', fields: ['b'] }] } });

    expect(validateValue(field, '', 'create')).toEqual([t('required')]);
    expect(validateValue(field, '', 'create', { values: { b: 'x' } })).toEqual([]);
    expect(validateValue(field, '', 'update')).toEqual([]);
  });

  test('requiredConditional yields no client-side error', () => {
    expect(validateValue(fd('a', 'string', { requiredConditional: true }), '', 'create')).toEqual([]);
  });

  test('filled rejects an empty value only when it is sent', () => {
    const field = fd('a', 'string', { constraints: { filled: true } });

    expect(validateValue(field, '', 'update')).toEqual([t('required')]);
    expect(validateValue(field, '', 'update', { sent: false })).toEqual([]);
    expect(validateValue(field, 'x', 'update')).toEqual([]);
  });
});

describe('ResourceFormState validation', () => {
  let thing: Thing;

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    app.request = jest.fn(() =>
      Promise.resolve(
        schemaDocument(
          descriptor([
            fd('title', 'string', { required: { create: true, update: true }, constraints: { minLength: 3 } }),
            fd('slug', 'string', { conditional: true, constraints: { minLength: 3 } }),
          ])
        )
      )
    ) as any;
    thing = app.store.pushObject<Thing>({ type: 'things', id: '1', attributes: { title: '', slug: 'ok-slug' } } as any)!;
  });

  test('required.update with an unchanged value yields no error', async () => {
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    expect(state.validate()).toEqual({});
  });

  test('save() with an invalid field makes no request and sets the field errors', async () => {
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);
    const request = jest.fn(() => Promise.resolve({}));
    app.request = request as any;

    state.stream('title')('ab');

    await expect(state.save()).rejects.toBeInstanceOf(ValidationError);
    expect(request).not.toHaveBeenCalled();
    expect(state.errors).toEqual({ title: t('min_length', { count: 3 }) });
  });

  test('a conditional field with an invalid value does not block', async () => {
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);
    const request = jest.fn((options: any) => Promise.resolve({ data: { type: 'things', id: '1', attributes: options.body.data.attributes } }));
    app.request = request as any;

    state.stream('slug')('x');
    await state.save();

    expect(request).toHaveBeenCalledTimes(1);
    expect((request.mock.calls[0] as any)[0].body.data.attributes).toEqual({ slug: 'x' });
  });
});

describe('ResourceFormState requiredWhen', () => {
  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(() => {
    clearDescriptorCache();
    app.request = jest.fn(() =>
      Promise.resolve(
        schemaDocument(
          descriptor([
            fd('a', 'string', { requiredWhen: { create: [{ rule: 'without', fields: ['b'] }], update: [{ rule: 'without', fields: ['b'] }] } }),
            fd('b', 'string'),
            fd('c', 'string', { requiredConditional: true }),
            fd('d', 'string', { constraints: { filled: true } }),
            fd('hidden', 'string', { ui: { widget: 'hidden' } }),
            fd('e', 'string', { requiredWhen: { create: [{ rule: 'without', fields: ['hidden'] }] } }),
          ])
        )
      )
    ) as any;
  });

  test('isRequired() follows the sibling values', async () => {
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    expect(state.isRequired('a')).toBe(true);
    state.stream('b')('x');
    expect(state.isRequired('a')).toBe(false);
    state.stream('b')('');
    expect(state.isRequired('a')).toBe(true);
    expect(state.isRequired('c')).toBe(false);
  });

  test('a field without an input counts as absent', async () => {
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    expect('hidden' in state.values()).toBe(false);
    expect(state.isRequired('e')).toBe(true);
  });

  test('on update, the record values of fields without a stream count', async () => {
    const thing = app.store.pushObject<Thing>({ type: 'things', id: '7', attributes: { a: '', b: 'x', hidden: 'h' } } as any)!;
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    expect(state.values()).toMatchObject({ b: 'x', hidden: 'h' });
    expect(state.isRequired('a')).toBe(false);
  });

  test('validate() checks requiredWhen live; requiredConditional and unsent filled fields are left to the server', async () => {
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    expect(Object.keys(state.validate()).sort()).toEqual(['a', 'e']);

    state.stream('b')('x');
    expect(Object.keys(state.validate())).toEqual(['e']);
  });

  test('filled rejects a changed field emptied again only when it is sent', async () => {
    const thing = app.store.pushObject<Thing>({ type: 'things', id: '8', attributes: { b: 'x', d: 'set' } } as any)!;
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    state.stream('d')('');
    expect(state.validate()).toEqual({ d: [t('required')] });
  });

  test("validateField() clears a sibling's required error once it no longer applies", async () => {
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), null);

    await expect(state.save()).rejects.toBeInstanceOf(ValidationError);
    expect(state.errors.a).toBe(t('required'));

    state.stream('b')('x');
    state.validateField('b');
    expect(state.errors.a).toBeUndefined();
  });
});
