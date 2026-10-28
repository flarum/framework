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
