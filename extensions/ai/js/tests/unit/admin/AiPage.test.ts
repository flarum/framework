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

    ai.updateFeatureNumber('acme.thing', 'daily_tokens', '500');
    ai.setting('flarum-ai.member_daily_tokens')('10');

    expect([...ai.unsavedTabs()].sort()).toEqual(['usage']);
  });

  it("doesn't take a feature limit that isn't a whole number", () => {
    const ai = page();

    ai.updateFeatureNumber('acme.thing', 'daily_tokens', '1e3');

    expect(ai.isInvalid('acme.thing', 'daily_tokens')).toBe(true);
    expect(ai.features['acme.thing']).toBeUndefined();
  });

  it("takes a feature's temperature and longest reply", () => {
    const ai = page();

    ai.updateFeatureNumber('acme.thing', 'temperature', '0.4');
    ai.updateFeatureNumber('acme.thing', 'max_tokens', '2048');

    expect(ai.features['acme.thing']).toEqual({ temperature: 0.4, max_tokens: 2048 });

    ai.updateFeatureNumber('acme.thing', 'temperature', '');

    expect(ai.features['acme.thing']).toEqual({ max_tokens: 2048 });
  });

  it("doesn't take a temperature or longest reply that isn't one", () => {
    const ai = page();

    ai.updateFeatureNumber('acme.thing', 'temperature', '3');
    ai.updateFeatureNumber('acme.thing', 'max_tokens', '1.5');

    expect(ai.isInvalid('acme.thing', 'temperature')).toBe(true);
    expect(ai.isInvalid('acme.thing', 'max_tokens')).toBe(true);
    expect(ai.features['acme.thing']).toBeUndefined();

    ai.updateFeatureNumber('acme.thing', 'temperature', '1');

    expect(ai.isInvalid('acme.thing', 'temperature')).toBe(false);
  });
});

describe('model issues', () => {
  const issue = {
    connection: 'claude',
    model: 'claude-x',
    status: 'missing',
    since: '2026-11-06T12:00:00+00:00',
    retiresAt: null,
    features: ['acme.thing'],
  };

  it("are listed for the features they're about", () => {
    (app.data as any)['flarum-ai'].modelIssues = [issue];

    const ai = page();

    expect(ai.featureIssues('acme.thing')).toEqual([issue]);
    expect(ai.featureIssues('acme.other')).toEqual([]);
  });

  it('are checked again when the admin asks', async () => {
    const request = jest.fn(() => Promise.resolve({ issues: [issue], unchecked: ['local'], unlisted: ['azure'] }));
    app.request = request as any;

    const ai = page();
    await ai.checkModels();

    expect((request.mock.calls[0] as any)[0]).toMatchObject({ method: 'POST', url: app.forum.attribute('apiUrl') + '/ai/models/check' });
    expect((app.data as any)['flarum-ai'].modelIssues).toEqual([issue]);
    expect(ai.uncheckedConnections).toEqual(['local']);
    expect(ai.unlistedConnections).toEqual(['azure']);
    expect(ai.checkingModels).toBe(false);
  });

  it('are loaded again once saved, and so are the model lists', async () => {
    const request = jest.fn(() => Promise.resolve({ issues: [] }));
    app.request = request as any;
    (app.data as any)['flarum-ai'].modelIssues = [issue];

    const ai = page();
    const lists = ai.modelLists;
    ai.onsaved();
    await new Promise((resolve) => setTimeout(resolve));

    expect((request.mock.calls[0] as any)[0]).toMatchObject({ method: 'GET', url: app.forum.attribute('apiUrl') + '/ai/models/issues' });
    expect((app.data as any)['flarum-ai'].modelIssues).toEqual([]);
    expect(ai.modelLists).not.toBe(lists);
  });
});

describe('the Features tab', () => {
  const feature = { key: 'acme.thing', label: 'acme.thing_label', description: null, extension: null, permission: 'ai.feature.acme.thing' };

  it('keys every section, as Mithril needs of a list where any is keyed', () => {
    (app.data as any)['flarum-ai'].features = [feature];

    const sections = page().featureSections() as any[];

    expect(sections.length).toBe(2);
    expect(sections.every((section) => section.key != null)).toBe(true);
  });

  it("keys the Usage tab's limits alike when warning about guests, as Mithril needs", () => {
    (app.data as any)['flarum-ai'].features = [feature];
    app.data.permissions = { [feature.permission]: ['2'] } as any;

    const children = (page().limitsSection() as any).children.flat(Infinity).filter((child: any) => child && typeof child === 'object');
    const keyed = children.filter((child: any) => child.key != null);

    expect(children.length).toBeGreaterThan(1);
    expect(keyed.length === 0 || keyed.length === children.length).toBe(true);
  });

  it("says when a feature's temperature is left out, as its model refuses one", () => {
    (app.data as any)['flarum-ai'].temperatureIgnored = [{ feature: 'acme.thing', connection: 'claude', model: 'claude-new' }];
    (app.data as any)['flarum-ai'].defaultModels = { claude: 'claude-new' };

    const ai = page();
    ai.setting('flarum-ai.default_connection')('claude');

    expect(ai.temperatureIgnored('acme.thing', { model: 'claude-new', temperature: 0.3 })).toBe(true);
    expect(ai.temperatureIgnored('acme.thing', { model: 'claude-other', temperature: 0.3 })).toBe(false);
    expect(ai.temperatureIgnored('acme.thing', { model: 'claude-new' })).toBe(false);
    expect(ai.temperatureIgnored('acme.other', { model: 'claude-new', temperature: 0.3 })).toBe(false);
    // The connection's default, when the feature names no model.
    expect(ai.temperatureIgnored('acme.thing', { temperature: 0.3 })).toBe(true);
  });

  it('offers a connection changed on the page only once it is saved, region and all', () => {
    app.data.settings['flarum-ai.connections'] = '{"aws":{"driver":"bedrock","region":"eu-central-1"}}';

    const ai = page();

    expect(ai.savedConnection('aws')).toBe('aws');

    ai.connections.aws = { driver: 'bedrock', region: 'us-east-1' };
    expect(ai.savedConnection('aws')).toBeNull();
  });

  it("shows the note on the feature's temperature field", () => {
    (app.data as any)['flarum-ai'].features = [feature];
    (app.data as any)['flarum-ai'].temperatureIgnored = [{ feature: 'acme.thing', connection: 'claude', model: 'claude-new' }];

    const ai = page();
    ai.setting('flarum-ai.default_connection')('claude');
    ai.features['acme.thing'] = { model: 'claude-new', temperature: 0.3 };

    expect(JSON.stringify(ai.featureSection(feature as any))).toContain('flarum-ai.admin.features.temperature_ignored_text');

    ai.features['acme.thing'] = { model: 'claude-other', temperature: 0.3 };

    expect(JSON.stringify(ai.featureSection(feature as any))).not.toContain('flarum-ai.admin.features.temperature_ignored_text');
  });

  it('warns when guests can use a feature with no daily limit of their own', () => {
    (app.data as any)['flarum-ai'].features = [feature];
    app.data.permissions = { [feature.permission]: ['2'] } as any;

    const ai = page();

    expect(ai.guestsUnlimited()).toBe(true);

    ai.setting('flarum-ai.guest_daily_tokens')('5000');

    expect(ai.guestsUnlimited()).toBe(false);

    ai.setting('flarum-ai.guest_daily_tokens')('');
    app.data.permissions = { [feature.permission]: ['3'] } as any;

    expect(ai.guestsUnlimited()).toBe(false);
  });
});
