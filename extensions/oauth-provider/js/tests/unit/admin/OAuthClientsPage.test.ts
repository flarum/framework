import { jest } from '@jest/globals';
import app from 'flarum/admin/app';
import mq from 'mithril-query';
import OAuthClientsPage from '../../../src/admin/components/OAuthClientsPage';
import OAuthClient from '../../../src/admin/models/OAuthClient';
import { bootAdmin } from './setup';

const CIMD = 'https://claude.ai/oauth/claude-code-client-metadata';

const client = (id: string, name: string, source: string, connectionCount: number, lastUsedAt: string | null) => ({
  type: 'oauth-provider-clients',
  id,
  attributes: {
    name,
    source,
    connectionCount,
    lastUsedAt,
    confidential: source === 'admin',
    revoked: false,
    redirectUris: ['https://app.example.com/cb'],
  },
});

async function page() {
  app.store.models['oauth-provider-clients'] = OAuthClient;

  const clients = app.store.pushPayload<OAuthClient[]>({
    data: [
      client('sister-forum', 'Sister forum', 'admin', 0, null),
      client(CIMD, 'Claude Code', 'metadata_document', 3, new Date(Date.now() - 2 * 3600 * 1000).toISOString()),
      client('dcr-1', 'Codex', 'dynamic', 1, null),
    ],
  } as any);

  app.store.find = jest.fn(() => Promise.resolve(clients)) as any;

  const page = new OAuthClientsPage();
  page.oninit({ attrs: { id: 'flarum-oauth-provider' } } as any);
  await new Promise((resolve) => setTimeout(resolve));

  return page;
}

const tab = (page: OAuthClientsPage, id: string) => mq({ view: () => page.pageTabContent(id, {} as any) });

const names = (rendered: any) => Array.from(rendered.find('.OAuthClientsList-name') as Element[]).map((el) => el.textContent);

describe('the client tabs', () => {
  beforeEach(() => bootAdmin());

  it('lists the clients an admin added under Applications, and the rest under Self-registered', async () => {
    const clients = await page();

    expect(names(tab(clients, 'applications'))).toEqual([expect.stringContaining('Sister forum')]);
    expect(names(tab(clients, 'self_registered'))).toEqual([expect.stringContaining('Claude Code'), expect.stringContaining('Codex')]);
  });

  it('says how many members connected each client, linking to them in the users list', async () => {
    const selfRegistered = tab(await page(), 'self_registered');

    const link = selfRegistered.find('.OAuthClientsList-connections a')[0] as HTMLAnchorElement;

    expect(link.textContent).toContain('3 members');
    expect(link.getAttribute('href')).toContain('/users?q=' + encodeURIComponent('connected:' + CIMD));
  });

  it('does not link to an empty list', async () => {
    const applications = tab(await page(), 'applications');

    expect(applications.find('.OAuthClientsList-connections a')).toHaveLength(0);
    expect(applications.find('.OAuthClientsList-connections')[0].textContent).toContain('No members');
  });

  it('says when a client was last used, or that it never was', async () => {
    const clients = await page();

    expect(tab(clients, 'self_registered').find('.OAuthClientsList-lastUsed')[0].textContent).toContain('Last used');
    expect(tab(clients, 'applications').find('.OAuthClientsList-lastUsed')[0].textContent).toContain('Never used');
  });
});

describe('switching a kind of client off', () => {
  it('offers to add a client while admin-added applications are allowed', async () => {
    bootAdmin();

    const applications = tab(await page(), 'applications');

    expect(applications).toHaveElement('.OAuthClientsPage-add');
    expect(applications).not.toHaveElement('.OAuthClientsPage-off');
  });

  it('hides Add client and says so while admin-added applications are switched off', async () => {
    bootAdmin({ 'flarum-oauth-provider.allow_admin_clients': '0' });

    const applications = tab(await page(), 'applications');

    expect(applications).not.toHaveElement('.OAuthClientsPage-add');
    expect(applications).toHaveElement('.OAuthClientsPage-off');
  });

  it('says so while self-registered applications are switched off', async () => {
    bootAdmin({ 'flarum-oauth-provider.allow_self_registered_clients': '0' });

    const clients = await page();

    expect(tab(clients, 'self_registered')).toHaveElement('.OAuthClientsPage-off');
    expect(tab(clients, 'applications')).not.toHaveElement('.OAuthClientsPage-off');
  });
});
