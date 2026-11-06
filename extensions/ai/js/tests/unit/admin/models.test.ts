import { jest } from '@jest/globals';
import app from 'flarum/admin/app';
import { ModelLists, modelStatus } from '../../../src/admin/utils/models';
import { bootAdmin } from './setup';

const offered = [
  { id: 'claude-new', name: 'Claude New', deprecatedAt: null, retiresAt: null },
  { id: 'claude-old', name: 'Claude Old', deprecatedAt: '2026-09-01T00:00:00+00:00', retiresAt: '2027-01-05T00:00:00+00:00' },
];

beforeEach(() => bootAdmin());

describe('modelStatus', () => {
  it('says whether the provider offers a model, and whether it is going away', () => {
    expect(modelStatus(offered, 'claude-new')).toBe('offered');
    expect(modelStatus(offered, 'claude-old')).toBe('deprecated');
    expect(modelStatus(offered, 'claude-gone')).toBe('missing');
  });

  it("doesn't know when the list hasn't been loaded or can't be", () => {
    expect(modelStatus(undefined, 'claude-new')).toBe('unknown');
    expect(modelStatus(null, 'claude-new')).toBe('unknown');
  });
});

describe('ModelLists', () => {
  it("asks for each connection's list once, until asked to refresh it", async () => {
    const request = jest.fn(() => Promise.resolve({ connection: 'claude', models: offered, checkedAt: '2026-11-06T12:00:00+00:00' }));
    app.request = request as any;

    const lists = new ModelLists();

    await lists.load('claude');
    await lists.load('claude');

    expect(request).toHaveBeenCalledTimes(1);
    expect((request.mock.calls[0] as any)[0].url).toBe(app.forum.attribute('apiUrl') + '/ai/connections/claude/models');
    expect(lists.get('claude')).toEqual({ status: 'loaded', models: offered });

    await lists.load('claude', true);

    expect(request).toHaveBeenCalledTimes(2);
    expect((request.mock.calls[1] as any)[0].params).toEqual({ refresh: 1 });
  });

  it('remembers a provider that failed, or that has no list', async () => {
    app.request = jest.fn(() => Promise.reject(new Error('Down'))) as any;

    const lists = new ModelLists();
    await lists.load('claude');

    expect(lists.get('claude').status).toBe('failed');

    app.request = jest.fn(() => Promise.resolve({ connection: 'azure', models: null, checkedAt: '' })) as any;
    await lists.load('azure');

    expect(lists.get('azure')).toEqual({ status: 'loaded', models: null });
  });
});
