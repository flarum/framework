import { jest } from '@jest/globals';
import app from 'flarum/admin/app';
import AiPage from '../../../src/admin/components/AiPage';
import { CONNECTIONS_SETTING } from '../../../src/admin/utils/settings';
import { bootAdmin } from './setup';

const usage = { month: '2026-10', connections: [], features: [], members: [], days: [] };

function page() {
  const page = new AiPage();
  (page as any).extension = { id: 'flarum-ai', extra: { 'flarum-extension': { title: 'AI' } } };
  page.oninit({ attrs: { id: 'flarum-ai' } } as any);

  return page;
}

beforeEach(() => {
  bootAdmin({ [CONNECTIONS_SETTING]: '{"claude":{"driver":"anthropic"}}' });
  app.alerts.show = jest.fn() as any;
});

describe('the Usage tab', () => {
  it('loads the totals once, however often it is drawn', async () => {
    const request = jest.fn(() => Promise.resolve(usage));
    app.request = request as any;

    const ai = page();

    ai.pageTabContent('usage', {} as any);
    ai.pageTabContent('usage', {} as any);
    await Promise.resolve();
    await new Promise((resolve) => setTimeout(resolve));
    ai.pageTabContent('usage', {} as any);
    ai.pageTabContent('usage', {} as any);

    expect(request).toHaveBeenCalledTimes(1);
    expect(ai.usageState).toBe('loaded');
  });

  it('offers to try again after a failed load', async () => {
    const request = jest.fn(() => Promise.reject(new Error('Down')));
    app.request = request as any;

    const ai = page();

    ai.pageTabContent('usage', {} as any);
    await new Promise((resolve) => setTimeout(resolve));
    ai.pageTabContent('usage', {} as any);

    expect(ai.usageState).toBe('failed');
    expect(request).toHaveBeenCalledTimes(1);

    app.request = jest.fn(() => Promise.resolve(usage)) as any;
    await ai.loadUsage();

    expect(ai.usageState).toBe('loaded');
  });
});

describe('saving', () => {
  it('keeps no key on the page once saved', () => {
    const ai = page();

    ai.connections.claude = { driver: 'anthropic', key: 'sk-typed' };
    ai.sync();
    ai.onsaved();

    expect(app.data.settings[CONNECTIONS_SETTING]).toBe('{"claude":{"driver":"anthropic"}}');
    expect(JSON.stringify(ai.connections)).not.toContain('sk-typed');
    expect(ai.connections.claude.hasKey).toBe(true);
    expect(ai.isChanged()).toBe(0);
  });

  it('marks the tab with unsaved changes', () => {
    const ai = page();

    expect(ai.unsavedTabs().size).toBe(0);

    ai.updateFeatureLimit('acme.thing', '500');
    ai.setting('flarum-ai.member_daily_tokens')('10');

    expect([...ai.unsavedTabs()].sort()).toEqual(['usage']);
  });

  it("doesn't take a feature limit that isn't a whole number", () => {
    const ai = page();

    ai.updateFeatureLimit('acme.thing', '1e3');

    expect(ai.invalidFeatureLimits.has('acme.thing')).toBe(true);
    expect(ai.features['acme.thing']).toBeUndefined();
  });
});
