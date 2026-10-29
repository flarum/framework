import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import ResourceSchema from '../../../../src/common/models/ResourceSchema';
import {
  SCHEMA_VERSION,
  clearDescriptorCache,
  conditionHolds,
  isRequired,
  isWritable,
  loadDescriptor,
} from '../../../../src/common/resource/descriptor';
import type { RequiredCondition } from '../../../../src/common/resource/types';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

describe('loadDescriptor', () => {
  beforeAll(() => bootAdmin());
  beforeEach(() => clearDescriptorCache());

  test('core registers the resource-schemas model', () => {
    expect(app.store.models['resource-schemas']).toBe(ResourceSchema);
  });

  test('finds the resource schema through the store once per type and session', async () => {
    const payload = descriptor([fd('a', 'string')]);
    const request = jest.fn(() => Promise.resolve(schemaDocument(payload)));
    app.request = request as any;

    const first = await loadDescriptor('things');
    const second = await loadDescriptor('things');

    expect(first).toEqual(payload);
    expect(second).toBe(first);
    expect(request).toHaveBeenCalledTimes(1);
    expect((request.mock.calls[0] as any)[0]).toMatchObject({ method: 'GET', url: 'https://example.test/api/resource-schemas/things' });
    expect(app.store.getById<ResourceSchema>('resource-schemas', 'things')?.schemaVersion()).toBe(1);
  });

  test('rejects on 403 without memoizing, and retries on the next call', async () => {
    const request = jest
      .fn()
      .mockImplementationOnce(() => Promise.reject({ status: 403 }))
      .mockImplementationOnce(() => Promise.resolve(schemaDocument(descriptor([]))));
    app.request = request as any;

    await expect(loadDescriptor('things')).rejects.toEqual({ status: 403 });
    await expect(loadDescriptor('things')).resolves.toMatchObject({ schemaVersion: 1 });
    expect(request).toHaveBeenCalledTimes(2);
  });

  test('rejects a descriptor newer than the client supports, without memoizing', async () => {
    const request = jest
      .fn()
      .mockImplementationOnce(() => Promise.resolve(schemaDocument(descriptor([], { schemaVersion: SCHEMA_VERSION + 1 }))))
      .mockImplementationOnce(() => Promise.resolve(schemaDocument(descriptor([]))));
    app.request = request as any;

    await expect(loadDescriptor('things')).rejects.toThrow(
      `The "things" resource descriptor has schemaVersion ${SCHEMA_VERSION + 1}, but this client supports up to ${SCHEMA_VERSION}.`
    );
    await expect(loadDescriptor('things')).resolves.toMatchObject({ schemaVersion: SCHEMA_VERSION });
    expect(request).toHaveBeenCalledTimes(2);
  });

  test('suppresses the global error alert', async () => {
    const request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([]))));
    app.request = request as any;

    await loadDescriptor('things');

    expect(typeof (request.mock.calls[0] as any)[0].errorHandler).toBe('function');
  });

  test('a refetch does not keep keys the new descriptor omits', async () => {
    app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([], { defaultSort: '-joinedAt', sorts: ['joinedAt'] })))) as any;
    await loadDescriptor('things');

    clearDescriptorCache('things');
    app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([])))) as any;
    const fresh = await loadDescriptor('things');

    expect(fresh.defaultSort).toBeUndefined();
    expect(fresh.sorts).toBeUndefined();
  });

  test('a refetch does not change a descriptor handed out earlier', async () => {
    app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([fd('a', 'string')])))) as any;
    const earlier = await loadDescriptor('things');

    clearDescriptorCache('things');
    app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor([fd('b', 'string')])))) as any;
    await loadDescriptor('things');

    expect(earlier.fields.map((f) => f.name)).toEqual(['a']);
  });
});

describe('descriptor flags', () => {
  test('omitted writable and required flags are false', () => {
    const bare = { name: 'x', kind: 'string', label: 'X' } as const;

    expect(isWritable(bare, 'create')).toBe(false);
    expect(isRequired(bare, 'update')).toBe(false);
    expect(isWritable(fd('x', 'string', { writable: { update: true } }), 'update')).toBe(true);
    expect(isWritable(fd('x', 'string', { writable: { update: true } }), 'create')).toBe(false);
    expect(isRequired(fd('x', 'string', { required: { create: true } }), 'create')).toBe(true);
  });
});

describe('requiredWhen', () => {
  const holds = (rule: RequiredCondition['rule'], fields: string[], values: Record<string, unknown>, list?: string[]) =>
    conditionHolds({ rule, fields, ...(list ? { values: list } : {}) }, values);

  test('with: any listed field present; withAll: all present', () => {
    expect(holds('with', ['a', 'b'], { a: 'x' })).toBe(true);
    expect(holds('with', ['a', 'b'], {})).toBe(false);
    expect(holds('withAll', ['a', 'b'], { a: 'x' })).toBe(false);
    expect(holds('withAll', ['a', 'b'], { a: 'x', b: false })).toBe(true);
  });

  test('without: any absent; withoutAll: all absent', () => {
    expect(holds('without', ['a', 'b'], { a: 'x' })).toBe(true);
    expect(holds('without', ['a', 'b'], { a: 'x', b: 0 })).toBe(false);
    expect(holds('withoutAll', ['a', 'b'], { a: 'x' })).toBe(false);
    expect(holds('withoutAll', ['a', 'b'], {})).toBe(true);
  });

  test('an empty array, a blank string, null and a missing key count as absent', () => {
    for (const value of [[], '   ', null, undefined]) {
      expect(holds('with', ['a'], { a: value })).toBe(false);
      expect(holds('without', ['a'], { a: value })).toBe(true);
    }

    expect(holds('without', ['a'], {})).toBe(true);
  });

  test('if / unless compare as strings, booleans as 1/0 and true/false', () => {
    expect(holds('if', ['type'], { type: 'admin' }, ['admin', 'mod'])).toBe(true);
    expect(holds('if', ['type'], { type: 'member' }, ['admin', 'mod'])).toBe(false);
    expect(holds('if', ['n'], { n: 2 }, ['2'])).toBe(true);
    expect(holds('if', ['flag'], { flag: true }, ['1'])).toBe(true);
    expect(holds('if', ['flag'], { flag: false }, ['false'])).toBe(true);
    expect(holds('if', ['flag'], { flag: false }, ['1'])).toBe(false);
    expect(holds('unless', ['type'], { type: 'admin' }, ['admin'])).toBe(false);
    expect(holds('unless', ['type'], { type: 'member' }, ['admin'])).toBe(true);
    expect(holds('unless', ['type'], {}, ['admin'])).toBe(true);
  });

  test('ifAccepted / ifDeclined accept the string and boolean forms', () => {
    for (const value of ['yes', 'on', '1', 'true', 1, true, 'YES']) {
      expect(holds('ifAccepted', ['terms'], { terms: value })).toBe(true);
      expect(holds('ifDeclined', ['terms'], { terms: value })).toBe(false);
    }

    for (const value of ['no', 'off', '0', 'false', 0, false]) {
      expect(holds('ifDeclined', ['terms'], { terms: value })).toBe(true);
      expect(holds('ifAccepted', ['terms'], { terms: value })).toBe(false);
    }

    expect(holds('ifAccepted', ['terms'], {})).toBe(false);
    expect(holds('ifDeclined', ['terms'], {})).toBe(false);
  });

  test('isRequired() evaluates the conditions of the given mode only', () => {
    const d = fd('a', 'string', { requiredWhen: { create: [{ rule: 'without', fields: ['b'] }] } });

    expect(isRequired(d, 'create', {})).toBe(true);
    expect(isRequired(d, 'create', { b: 'x' })).toBe(false);
    expect(isRequired(d, 'update', {})).toBe(false);
    expect(isRequired(fd('a', 'string', { required: { update: true } }), 'create', {})).toBe(false);
  });

  test('isRequired() is true when any condition holds', () => {
    const d = fd('a', 'string', {
      requiredWhen: {
        update: [
          { rule: 'with', fields: ['b'] },
          { rule: 'if', fields: ['c'], values: ['x'] },
        ],
      },
    });

    expect(isRequired(d, 'update', { c: 'x' })).toBe(true);
    expect(isRequired(d, 'update', { b: 'y' })).toBe(true);
    expect(isRequired(d, 'update', { c: 'z' })).toBe(false);
  });

  test('requiredConditional alone does not make a field required', () => {
    expect(isRequired(fd('a', 'string', { requiredConditional: true }), 'create', {})).toBe(false);
  });
});
