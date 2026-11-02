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

  test('format email and uri', () => {
    const email = fd('email', 'string', { constraints: { format: 'email' } });
    const url = fd('homepage', 'string', { constraints: { format: 'uri' } });

    expect(validateValue(email, 'a@b', 'create')).toEqual([]);
    expect(validateValue(email, 'nope', 'create')).toEqual([t('email')]);
    expect(validateValue(url, 'https://x.y', 'create')).toEqual([]);
    expect(validateValue(url, 'x.y', 'create')).toEqual([t('url')]);
    expect(validateValue(url, 'nope', 'create')).toEqual([t('url')]);
  });

  test('patterns compile in unicode mode, deduping the u flag', () => {
    const letters = fd('name', 'string', { constraints: { pattern: '^\\p{L}+$' } });
    const flagged = fd('name', 'string', { constraints: { pattern: '^\\p{Lu}+$', patternFlags: 'iu' } });

    expect(validateValue(letters, 'Zoë', 'create')).toEqual([]);
    expect(validateValue(letters, 'a1', 'create')).toEqual([t('pattern')]);
    expect(validateValue(flagged, 'ÄB', 'create')).toEqual([]);
    expect(validateValue(fd('e', 'string', { constraints: { pattern: '^.$' } }), '😀', 'create')).toEqual([]);
  });

  test('a pattern invalid in unicode mode is skipped', () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    const field = fd('code', 'string', { constraints: { pattern: '^\\-x$' } });

    expect(validateValue(field, 'anything', 'create')).toEqual([]);
    expect(warn).toHaveBeenCalledTimes(1);
  });

  test('every one of several patterns must match; an uncompilable one is skipped', () => {
    jest.spyOn(console, 'warn').mockImplementation(() => {});
    const field = fd('code', 'string', { constraints: { patterns: [{ pattern: '^[a-z]+$' }, { pattern: 'X', flags: 'i' }, { pattern: '(' }] } });

    expect(validateValue(field, 'abxc', 'create')).toEqual([]);
    expect(validateValue(field, 'abc', 'create')).toEqual([t('pattern')]);
    expect(validateValue(field, 'Abx', 'create')).toEqual([t('pattern')]);
  });

  test('formats: uri, uuid, ulid, ip, ipv4, ipv6, hex-color; unknown formats pass', () => {
    const check = (format: string, value: string) => validateValue(fd('f', 'string', { constraints: { format } }), value, 'create');

    expect(check('uri', 'https://x.y')).toEqual([]);
    expect(check('uri', 'x.y')).toEqual([t('url')]);
    // `new URL()` rejects an out-of-range IPv4-looking host; Laravel's rule does not.
    expect(check('uri', 'http://999.1.1.1/path')).toEqual([]);
    expect(check('uri', 'http://exa mple.com')).toEqual([t('url')]);
    expect(check('uuid', '123E4567-e89b-12d3-a456-426614174000')).toEqual([]);
    expect(check('uuid', '123e4567-e89b-12d3-a456')).toEqual([t('uuid')]);
    expect(check('ulid', '01ARZ3NDEKTSV4RRFFQ69G5FAV')).toEqual([]);
    expect(check('ulid', '81ARZ3NDEKTSV4RRFFQ69G5FAV')).toEqual([t('ulid')]);
    expect(check('ulid', '01ARZ3NDEKTSV4RRFFQ69G5FAU')).toEqual([t('ulid')]);
    expect(check('ipv4', '192.168.0.255')).toEqual([]);
    expect(check('ipv4', '256.1.1.1')).toEqual([t('ipv4')]);
    expect(check('ipv4', '::1')).toEqual([t('ipv4')]);
    expect(check('ipv6', '2001:db8::ff00:42:8329')).toEqual([]);
    expect(check('ipv6', '::ffff:192.0.2.1')).toEqual([]);
    expect(check('ipv6', '2001:db8:::1')).toEqual([t('ipv6')]);
    expect(check('ipv6', '1.2.3.4')).toEqual([t('ipv6')]);
    expect(check('ip', '1.2.3.4')).toEqual([]);
    expect(check('ip', '::1')).toEqual([]);
    expect(check('ip', 'localhost')).toEqual([t('ip')]);

    for (const value of ['#abc', '#AABBCC', '#abcd', '#aabbccdd']) expect(check('hex-color', value)).toEqual([]);
    for (const value of ['abc', '#ab', '#abcde', '#gggggg']) expect(check('hex-color', value)).toEqual([t('hex_color')]);

    expect(check('mac_address', 'nope')).toEqual([]);
  });

  test('exclusive bounds and multipleOf', () => {
    const field = fd('n', 'number', { constraints: { exclusiveMinimum: 0, exclusiveMaximum: 1, multipleOf: 0.1 } });

    expect(validateValue(field, 0.3, 'create')).toEqual([]);
    expect(validateValue(field, '0.7', 'create')).toEqual([]);
    expect(validateValue(field, 0, 'create')).toEqual([t('exclusive_minimum', { min: 0 })]);
    expect(validateValue(field, 1, 'create')).toEqual([t('exclusive_maximum', { max: 1 })]);
    expect(validateValue(field, 0.35, 'create')).toEqual([t('multiple_of', { value: 0.1 })]);
    expect(validateValue(fd('n', 'integer', { constraints: { multipleOf: 5 } }), 15, 'create')).toEqual([]);
    expect(validateValue(fd('n', 'integer', { constraints: { multipleOf: 5 } }), 12, 'create')).toEqual([t('multiple_of', { value: 5 })]);
  });

  test('accepted and declined', () => {
    const accepted = fd('terms', 'boolean', { constraints: { accepted: true } });
    const declined = fd('spam', 'boolean', { constraints: { declined: true } });

    for (const value of [true, 'yes', 'on', 1, '1', 'true']) expect(validateValue(accepted, value, 'create')).toEqual([]);
    for (const value of [false, 'no', 0, 'nah']) expect(validateValue(accepted, value, 'create')).toEqual([t('accepted')]);
    for (const value of [false, 'no', 'off', 0, '0', 'false']) expect(validateValue(declined, value, 'create')).toEqual([]);
    for (const value of [true, 'yes', 1]) expect(validateValue(declined, value, 'create')).toEqual([t('declined')]);
  });

  test('minItems, maxItems, uniqueItems and items.enum', () => {
    const field = fd('tags', 'array', { constraints: { minItems: 2, maxItems: 3, uniqueItems: true, items: { enum: ['a', 'b', 'c', 'd'] } } });

    expect(validateValue(field, ['a', 'b'], 'create')).toEqual([]);
    expect(validateValue(field, ['a'], 'create')).toEqual([t('min_items', { count: 2 })]);
    expect(validateValue(field, ['a', 'b', 'c', 'd'], 'create')).toEqual([t('max_items', { count: 3 })]);
    expect(validateValue(field, ['a', 'a'], 'create')).toEqual([t('unique_items')]);
    expect(validateValue(field, ['a', 'x'], 'create')).toEqual([t('items_enum')]);
  });

  test('uniqueItems compares models by identity, not their serialized data', () => {
    const field = fd('owners', 'array', { constraints: { uniqueItems: true } });
    app.store.models.things = Thing;
    const a = app.store.pushObject<Thing>({ type: 'things', id: '41', attributes: { name: 'A' } } as any)!;
    const b = app.store.pushObject<Thing>({ type: 'things', id: '42', attributes: { name: 'A' } } as any)!;

    expect(validateValue(field, [a, b], 'create')).toEqual([]);
    expect(validateValue(field, [a, a], 'create')).toEqual([t('unique_items')]);
  });

  test('an integer field holds an integer; numeric strings count', () => {
    const field = fd('rank', 'integer');

    for (const value of [3, -2, '42', ' 7 ', '+1']) expect(validateValue(field, value, 'create')).toEqual([]);
    for (const value of [1.5, '1.5', 'abc', '1e3']) expect(validateValue(field, value, 'create')).toEqual([t('integer')]);
    expect(validateValue(fd('ratio', 'number'), 1.5, 'create')).toEqual([]);
  });

  test('constraintsOnUpdate replaces constraints in update mode', () => {
    const field = fd('title', 'string', { constraints: { minLength: 5, filled: true }, constraintsOnUpdate: { maxLength: 3 } });

    expect(validateValue(field, 'abcd', 'create')).toEqual([t('min_length', { count: 5 })]);
    expect(validateValue(field, 'abcd', 'update')).toEqual([t('max_length', { count: 3 })]);
    expect(validateValue(field, 'ab', 'update')).toEqual([]);
    expect(validateValue(field, '', 'update')).toEqual([]);
    expect(validateValue(field, '', 'create')).toEqual([t('required')]);
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

  test('on update, only the changed fields count, as only those are sent', async () => {
    const thing = app.store.pushObject<Thing>({ type: 'things', id: '7', attributes: { a: 'set', b: 'x', hidden: 'h' } } as any)!;
    const state = await ResourceFormState.create(new ResourceDefinition<Thing>('things'), thing);

    expect(state.values()).toEqual({});
    expect(state.isRequired('a')).toBe(true);

    state.stream('a')('y');
    expect(state.values()).toEqual({ a: 'y' });
    expect(state.validate()).toEqual({});

    state.stream('a')('');
    expect(state.validate()).toEqual({ a: [t('required')] });

    state.stream('b')('z');
    expect(state.values()).toEqual({ a: '', b: 'z' });
    expect(state.validate()).toEqual({});
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

describe('validateValue custom messages', () => {
  beforeAll(() => bootAdmin());

  test('a constraint with a server message uses it; the others keep the generic one', () => {
    const field = fd('username', 'string', {
      required: { create: true },
      constraints: { minLength: 3, maxLength: 5, pattern: '^[a-z]+$', messages: { pattern: 'Lowercase letters only.', required: 'Pick a name.' } },
    });

    expect(validateValue(field, '', 'create')).toEqual(['Pick a name.']);
    expect(validateValue(field, 'A1', 'create')).toEqual([t('min_length', { count: 3 }), 'Lowercase letters only.']);
  });

  test('constraintsOnUpdate brings its own messages on update', () => {
    const field = fd('age', 'integer', {
      constraints: { minimum: 18, messages: { minimum: 'Adults only.' } },
      constraintsOnUpdate: { minimum: 21 },
    });

    expect(validateValue(field, 10, 'create')).toEqual(['Adults only.']);
    expect(validateValue(field, 10, 'update')).toEqual([t('minimum', { min: 21 })]);
  });

  test('format, enum and filled messages', () => {
    const field = fd('email', 'string', {
      constraints: { format: 'email', filled: true, messages: { format: 'Not an email.', filled: 'Do not clear it.' } },
    });
    const kind = fd('kind', 'string', { constraints: { enum: ['a'], messages: { enum: 'Pick a or nothing.' } } });

    expect(validateValue(field, 'nope', 'create')).toEqual(['Not an email.']);
    expect(validateValue(field, '', 'create')).toEqual(['Do not clear it.']);
    expect(validateValue(kind, 'b', 'create')).toEqual(['Pick a or nothing.']);
  });
});
