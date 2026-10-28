import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import ResourceSchema from '../../../../src/common/models/ResourceSchema';
import { SCHEMA_VERSION, clearDescriptorCache, isRequired, isWritable, loadDescriptor } from '../../../../src/common/resource/descriptor';
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
