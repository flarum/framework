import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import ItemList from 'flarum/common/utils/ItemList';
import type User from 'flarum/common/models/User';
import mq from 'mithril-query';
import ConnectedApplications from '../../../src/forum/components/ConnectedApplications';
import OAuthConsent from '../../../src/forum/models/OAuthConsent';
import addToSecurityPage from '../../../src/forum/addToSecurityPage';
import { bootForum } from './setup';

const hoursAgo = (hours: number) => new Date(Date.now() - hours * 3600 * 1000).toISOString();

const consent = (
  id: string,
  clientName: string,
  clientSourceHost: string | null,
  lastUsedAt: string | null,
  lastIpAddress: string | null = null
) => ({
  type: 'oauth-consents',
  id,
  attributes: {
    clientId: id,
    clientName,
    clientSourceHost,
    clientLogoUrl: null,
    scopes: ['mcp:read'],
    createdAt: hoursAgo(24),
    lastUsedAt,
    lastIpAddress,
  },
});

const tick = () => new Promise((resolve) => setTimeout(resolve));

beforeEach(() => {
  bootForum();
  app.store.models['oauth-consents'] = OAuthConsent;
});

async function rendered(data = [consent('1', 'Claude', 'claude.ai', hoursAgo(1), '203.0.113.7'), consent('2', 'Sister forum', null, null)]) {
  const consents = app.store.pushPayload<OAuthConsent[]>({ data } as any);
  app.store.find = jest.fn(() => Promise.resolve(consents)) as any;

  const list = mq(ConnectedApplications);
  await tick();
  list.redraw();

  return list;
}

describe('the connected applications list', () => {
  it('shows each application as browser sessions are shown', async () => {
    const list = await rendered();
    const items = list.find('.AccessTokensList-item');

    expect(items).toHaveLength(2);
    expect(items[0].textContent).toContain('Claude');
    expect(items[0].textContent).toContain('from claude.ai');
    expect(items[1].textContent).toContain('Sister forum');
    expect(list.find('.AccessTokensList-item-actions .Button--danger')).toHaveLength(2);
  });

  it('shows where each application was last used from', async () => {
    const items = (await rendered()).find('.AccessTokensList-item');

    expect(items[0].textContent).toContain('203.0.113.7');
    expect(items[1].textContent).not.toContain('203.0.113.7');
  });

  it('offers to revoke them all when there is more than one', async () => {
    expect(await rendered()).toHaveElement('.ConnectedApplications-revokeAll');
    expect(await rendered([consent('1', 'Claude', 'claude.ai', null)])).not.toHaveElement('.ConnectedApplications-revokeAll');
  });

  it('revokes them all', async () => {
    const list = await rendered();

    (globalThis as any).confirm = jest.fn(() => true);
    const request = jest.fn((options: any) => Promise.resolve());
    app.request = request as any;
    app.alerts.show = jest.fn() as any;

    list.click('.ConnectedApplications-revokeAll');
    await tick();
    list.redraw();

    expect(request).toHaveBeenCalledWith(expect.objectContaining({ method: 'DELETE', url: app.forum.attribute('apiUrl') + '/oauth-consents' }));
    expect(list.find('.AccessTokensList-item')).toHaveLength(0);
    expect(app.alerts.show).toHaveBeenCalled();
  });

  it('revokes an application', async () => {
    const list = await rendered();

    (globalThis as any).confirm = jest.fn(() => true);
    app.request = jest.fn(() => Promise.resolve()) as any;
    app.alerts.show = jest.fn() as any;

    list.click('.AccessTokensList-item-actions .Button--danger');
    await tick();
    list.redraw();

    expect(list.find('.AccessTokensList-item')).toHaveLength(1);
    expect(list.find('.AccessTokensList-item')[0].textContent).toContain('Sister forum');
    expect(app.alerts.show).toHaveBeenCalled();
  });
});

describe('the Security page', () => {
  const user = (id: string) => app.store.pushPayload<User>({ data: { type: 'users', id, attributes: { username: 'user' + id } } } as any);

  beforeEach(() => {
    app.session.user = user('2');
  });

  it("lists the member's connected applications on their own page, between their sessions and logging out everywhere", () => {
    const items = new ItemList();
    items.add('sessions', 'Sessions', 20);
    items.add('globalLogout', 'Log out everywhere', 10);

    addToSecurityPage.call({ user: user('2') } as any, items);

    expect(items.toArray().map((item: any) => item.itemName)).toEqual(['sessions', 'oauthConnectedApplications', 'globalLogout']);
  });

  it("does not list them on someone else's, which moderators can open", () => {
    const items = new ItemList();

    addToSecurityPage.call({ user: user('3') } as any, items);

    expect(items.has('oauthConnectedApplications')).toBe(false);
  });
});
