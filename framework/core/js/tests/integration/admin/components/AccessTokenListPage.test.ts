import { jest } from '@jest/globals';
import dayjs from 'dayjs';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import AccessTokenListPage, { DEFAULT_ACCESS_TOKEN_SORT } from '../../../../src/admin/components/AccessTokenListPage';
import AccessTokenDefinition from '../../../../src/admin/resource/definitions/AccessTokensDefinition';
import AccessTokenRevokeModal from '../../../../src/admin/components/AccessTokenRevokeModal';
import AdminNav from '../../../../src/admin/components/AdminNav';
import BulkResultModal from '../../../../src/admin/components/resource/BulkResultModal';
import ResourceActionModal from '../../../../src/admin/components/resource/ResourceActionModal';
import type AccessToken from '../../../../src/common/models/AccessToken';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import ViewPreferences from '../../../../src/common/resource/ViewPreferences';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { bootAdmin, schemaDocument } from '../../../unit/common/resource/fixtures';
import { accessTokensDescriptor } from '../fixtures/accessTokensDescriptor';

(globalThis as any).dayjs ??= dayjs;

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

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
    ViewPreferences.clear();
    window.localStorage.clear();
    window.history.replaceState(null, '', '/admin#/access-tokens');

    app.session.user!.pushAttributes({ isAdmin: true });
    app.request = jest.fn(() => Promise.resolve(schemaDocument(accessTokensDescriptor(), 'access-tokens'))) as any;

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

  test('without a sort in the URL, the saved view still applies, with the default sort', async () => {
    ViewPreferences.save('access-tokens', { filter: { type: 'developer' } });

    await rendered();

    expect(tokenCalls()[0]).toMatchObject({ sort: DEFAULT_ACCESS_TOKEN_SORT, filter: { type: 'developer' } });
  });

  test('a saved sort wins over the default', async () => {
    ViewPreferences.save('access-tokens', { sort: 'createdAt' });

    await rendered();

    expect(tokenCalls()[0].sort).toBe('createdAt');
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
    expect(out.find('tr .ResourceTable-revoke').length).toBe(2);
    // The current session's row has no actions: an empty cell and a disabled checkbox.
    expect(out.find('tr[data-id="1"] .ResourceTable-rowMenu').length).toBe(0);
    expect(out).toHaveElement('tr[data-id="1"] input.ResourceTable-select[disabled]');
    // The generic delete button is replaced by "Revoke".
    expect(out).not.toHaveElement('.ResourceTable-delete');
  });

  test('revoke opens the revoke modal, and a revoke reloads the list with a notice', async () => {
    const out = await rendered();
    const before = tokenCalls().length;

    out.click('tr .ResourceTable-revoke');

    expect(app.modal.show).toHaveBeenCalledTimes(1);

    const [modal, attrs] = (app.modal.show as jest.Mock<any>).mock.calls[0] as [unknown, any];

    expect(modal).toBe(AccessTokenRevokeModal);
    expect(attrs.model.id()).toBe('2');

    attrs.ondeleted(attrs.model);
    await flush();

    expect(app.alerts.show).toHaveBeenCalled();
    expect(tokenCalls().length).toBe(before + 1);
  });

  test('bulk revoke confirms in ResourceActionModal and revokes every token but the current session', async () => {
    const out = await rendered();

    out.first('.ResourceTable-selectAll').checked = true;
    out.trigger('.ResourceTable-selectAll', 'change');
    out.click('.ResourceTable-batchBar .ResourceTable-bulkRevoke');

    const [modal, attrs] = (app.modal.show as jest.Mock<any>).mock.calls.at(-1) as [unknown, any];

    expect(modal).toBe(ResourceActionModal);
    expect(attrs.action.key).toBe('revoke');

    const request = jest.fn((_options: any) => Promise.resolve());
    app.request = request as any;

    const dialog = mq(ResourceActionModal, { state: {}, animateShow: () => {}, animateHide: () => {}, ...attrs });

    expect(dialog).toContainRaw('Revoke 2 access tokens?');
    dialog.trigger('form', 'submit', {} as Event);
    await flush();
    await flush();

    const deletes = request.mock.calls.map(([options]) => options).filter((options: any) => options.method === 'DELETE');
    expect(deletes.map((options: any) => options.url).sort()).toEqual([
      'https://example.test/api/access-tokens/2',
      'https://example.test/api/access-tokens/3',
    ]);

    const [result, resultAttrs] = (app.modal.show as jest.Mock<any>).mock.calls.at(-1) as [unknown, any];
    expect(result).toBe(BulkResultModal);
    expect(resultAttrs.summary.skipped).toEqual([]);
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
    expect(new AccessTokenDefinition().label(2)).toBe('Access tokens');
    expect(new AccessTokenDefinition().recordLabel(tokens()[2])).toBe('Remembered session (Chrome on Mac)');
  });

  test('the definition comes from app.resources', () => {
    expect(app.resources.get('access-tokens')).toBeInstanceOf(AccessTokenDefinition);
  });

  test('filters come from the descriptor, with readable type labels', async () => {
    const out = await rendered();

    expect(out).toHaveElement('.ResourceTable-filterGroup--user');
    expect(out).toHaveElement('.ResourceTable-filterGroup--type');
    const definition = new AccessTokenDefinition();
    await definition.load();
    const type = definition.filters().get('type');

    expect((type.input as any).choices).toEqual([
      ['developer', 'Developer token'],
      ['session_remember', 'Remembered session'],
      ['session', 'Session'],
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
