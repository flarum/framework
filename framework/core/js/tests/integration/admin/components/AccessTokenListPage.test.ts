import { jest } from '@jest/globals';
import dayjs from 'dayjs';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import AccessTokenListPage, { AccessTokenDefinition, DEFAULT_ACCESS_TOKEN_SORT } from '../../../../src/admin/components/AccessTokenListPage';
import AccessTokenRevokeModal from '../../../../src/admin/components/AccessTokenRevokeModal';
import AdminNav from '../../../../src/admin/components/AdminNav';
import type AccessToken from '../../../../src/common/models/AccessToken';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import type { ResourceDescriptor } from '../../../../src/common/resource/types';
import { bootAdmin, schemaDocument } from '../../../unit/common/resource/fixtures';

(globalThis as any).dayjs ??= dayjs;

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

/** The admin's descriptor, as `AccessTokensSchemaTest` pins it on the server. */
function tokensDescriptor(): ResourceDescriptor {
  return {
    schemaVersion: 1,
    endpoint: '/access-tokens',
    capabilities: { list: true, create: true, delete: true },
    pagination: { defaultLimit: 20, maxLimit: 50 },
    sorts: ['createdAt', 'lastActivityAt'],
    filters: [
      { key: 'user', kind: 'relationship', related: 'users' },
      { key: 'type', kind: 'enum', enum: ['session', 'session_remember', 'developer'] },
    ],
    fields: [
      { name: 'userId', kind: 'integer', label: 'User id' },
      { name: 'user', kind: 'toOne', label: 'User', relationship: { types: ['users'] }, ui: { column: 90, display: 'username' } },
      { name: 'type', kind: 'string', label: 'Type', ui: { column: 80 } },
      { name: 'createdAt', kind: 'datetime', label: 'Created', sortable: true, ui: { column: 50 } },
      { name: 'lastActivityAt', kind: 'datetime', label: 'Last active', sortable: true, ui: { column: 40 } },
      { name: 'isCurrent', kind: 'boolean', label: 'Is current' },
      { name: 'isSessionToken', kind: 'boolean', label: 'Is session token' },
      { name: 'canDelete', kind: 'boolean', label: 'Can delete' },
      { name: 'title', kind: 'string', label: 'Title', writable: { create: true }, required: { create: true }, ui: { column: 75 } },
      { name: 'lastIpAddress', kind: 'string', label: 'IP address', ui: { column: 60 } },
      { name: 'device', kind: 'string', label: 'Device', ui: { column: 70 } },
    ],
  };
}

function results(models: unknown[], total: number = models.length) {
  return Object.assign([...models], { payload: { data: [], meta: { page: { total } }, links: {} } });
}

describe('AccessTokenListPage', () => {
  let find: jest.Mock<any>;
  let realFind: (...args: any[]) => Promise<unknown>;

  function user(id: string, username: string) {
    return app.store.pushObject({ type: 'users', id, attributes: { username, slug: username, displayName: username } } as any);
  }

  function token(id: string, attributes: Record<string, unknown>, userId: string = '2'): AccessToken {
    return app.store.pushObject<AccessToken>({
      type: 'access-tokens',
      id,
      attributes: {
        userId: Number(userId),
        type: 'session',
        title: null,
        createdAt: '2026-01-01T00:00:00Z',
        lastActivityAt: '2026-01-02T00:00:00Z',
        lastIpAddress: '127.0.0.1',
        device: 'Chrome on Mac',
        isCurrent: false,
        isSessionToken: true,
        canDelete: true,
        ...attributes,
      },
      relationships: { user: { data: { type: 'users', id: userId } } },
    } as any)!;
  }

  function tokens(): AccessToken[] {
    return [
      token('1', { isCurrent: true, canDelete: false }, '1'),
      token('2', { type: 'developer', title: 'Backup script', isSessionToken: false, lastActivityAt: null }),
      token('3', { type: 'session_remember' }),
    ];
  }

  function tokenCalls(): any[] {
    return find.mock.calls.filter(([type]) => type === 'access-tokens').map(([, params]) => params);
  }

  async function rendered(): Promise<any> {
    const out = mq(AccessTokenListPage);

    await flush();
    await flush();
    await flush();
    out.redraw();

    return out;
  }

  beforeAll(() => {
    bootAdmin();
    user('1', 'admin');
    user('2', 'normal');
  });

  beforeEach(() => {
    clearDescriptorCache();
    ColumnPreferences.clear();
    window.localStorage.clear();
    window.history.replaceState(null, '', '/admin#/access-tokens');

    app.session.user!.pushAttributes({ isAdmin: true });
    app.request = jest.fn(() => Promise.resolve(schemaDocument(tokensDescriptor(), 'access-tokens'))) as any;

    realFind = app.store.find.bind(app.store) as any;
    find = jest.fn((type: string, ...args: any[]) => (type === 'access-tokens' ? Promise.resolve(results(tokens())) : realFind(type, ...args)));
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    app.modal.show = jest.fn() as any;
    app.alerts.show = jest.fn() as any;
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test('it is a core route linked in the nav next to Users', () => {
    expect(app.routes.accessTokens).toMatchObject({ path: '/access-tokens', component: AccessTokenListPage });

    const items = new AdminNav().items();

    expect(items.has('accessTokens')).toBe(true);
    expect(items.getPriority('accessTokens')).toBeLessThan(items.getPriority('userList'));
    expect(items.getPriority('accessTokens')).toBeGreaterThan(items.has('advanced') ? items.getPriority('advanced') : 40);
  });

  test('it renders the header and the table', async () => {
    const out = await rendered();

    expect(out).toHaveElement('.AccessTokenListPage');
    expect(out).toContainRaw('Access Tokens');
    expect(out).toHaveElement('.ResourceTable--access-tokens');
    expect(out.find('tbody tr').length).toBe(3);
  });

  test('it loads the users and sorts by the most recent activity by default', async () => {
    await rendered();

    expect(tokenCalls()[0]).toMatchObject({ include: 'user', sort: DEFAULT_ACCESS_TOKEN_SORT });
    expect(window.location.hash).toContain('sort=-lastActivityAt');
  });

  test('a sort in the URL wins over the default', async () => {
    window.history.replaceState(null, '', '/admin#/access-tokens?sort=createdAt');

    await rendered();

    expect(tokenCalls()[0].sort).toBe('createdAt');
  });

  test('columns: user, type, title, device, IP, dates; never the token', async () => {
    const out = await rendered();

    const headers = out.find('th[data-column-name]').map((th: any) => th.getAttribute('data-column-name'));

    expect(headers).toEqual(['user', 'type', 'title', 'device', 'lastIpAddress', 'createdAt', 'lastActivityAt', 'actions']);
    expect(out).not.toHaveElement('[data-column-name=token]');

    expect(out).toHaveElement('a.AccessTokenList-user[href$="/u/normal"]');
    expect(out).toContainRaw('Remembered session');
    expect(out).toContainRaw('Developer token');
    expect(out).toContainRaw('Backup script');
    expect(out).toHaveElement('.IPAddress');
    expect(out).toHaveElement('time[data-humantime]');
    expect(out).toHaveElement('.AccessTokenList-never');
  });

  test('the current session is marked and cannot be revoked; other tokens can', async () => {
    const out = await rendered();

    expect(out).toHaveElement('.AccessTokenList-current');
    expect(out.find('.AccessTokenList-revoke').length).toBe(2);
    expect(out.find('.AccessTokenList-currentLock').length).toBe(1);
    // The generic delete button is replaced by "Revoke".
    expect(out).not.toHaveElement('.ResourceTable-delete');
  });

  test('revoke opens the revoke modal, and a revoke reloads the list with a notice', async () => {
    const out = await rendered();
    const before = tokenCalls().length;

    out.click('.AccessTokenList-revoke');

    expect(app.modal.show).toHaveBeenCalledTimes(1);

    const [modal, attrs] = (app.modal.show as jest.Mock<any>).mock.calls[0] as [unknown, any];

    expect(modal).toBe(AccessTokenRevokeModal);
    expect(attrs.model.id()).toBe('2');

    attrs.ondeleted(attrs.model);
    await flush();

    expect(app.alerts.show).toHaveBeenCalled();
    expect(tokenCalls().length).toBe(before + 1);
  });

  test('the revoke modal says revoke and names the token and its user', () => {
    const t = app.store.getById<AccessToken>('access-tokens', '2') ?? tokens()[1];
    const modal = mq(AccessTokenRevokeModal, {
      definition: new AccessTokenDefinition(),
      model: t,
      state: { close: () => {} },
      animateShow: () => {},
      animateHide: () => {},
    } as any);

    expect(modal).toContainRaw('Revoke Access Token');
    expect(modal).toContainRaw('Backup script');
    expect(modal).toContainRaw('normal');
    expect(modal).toHaveElement('.AccessTokenRevokeModal-submit');
    expect(new AccessTokenDefinition().recordLabel(tokens()[2])).toBe('Remembered session (Chrome on Mac)');
  });

  test('filters come from the descriptor, with readable type labels', async () => {
    const out = await rendered();

    expect(out).toHaveElement('.ResourceTable-filter--user');
    expect(out).toHaveElement('.ResourceTable-filter--type');
    const definition = new AccessTokenDefinition();
    await definition.load();
    const type = definition.filters().get('type');

    expect((type.input as any).choices).toEqual([
      ['session', 'Session'],
      ['session_remember', 'Remembered session'],
      ['developer', 'Developer token'],
    ]);
    expect(definition.filters().get('user').input).toMatchObject({ type: 'relation', related: 'users' });
  });

  test('no search box: the descriptor has no fulltext search', async () => {
    const out = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-search');
    expect(out).not.toHaveElement('.ResourceTable-searchInput');
  });

  test('no create button; export for administrators only', async () => {
    let out = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-create');
    expect(out).toHaveElement('.ResourceTable-export');

    app.session.user!.pushAttributes({ isAdmin: false });
    clearDescriptorCache();
    out = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-export');
  });
});
