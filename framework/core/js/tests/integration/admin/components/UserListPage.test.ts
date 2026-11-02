import { jest } from '@jest/globals';
import dayjs from 'dayjs';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import UserListPage from '../../../../src/admin/components/UserListPage';
import UserResourceDefinition from '../../../../src/admin/resource/definitions/UsersDefinition';
import CreateUserModal from '../../../../src/admin/components/CreateUserModal';
import ResourceDeleteModal from '../../../../src/admin/components/resource/ResourceDeleteModal';
import BulkResultModal from '../../../../src/admin/components/resource/BulkResultModal';
import ResourceActionModal from '../../../../src/admin/components/resource/ResourceActionModal';
import AutocompleteDropdown from '../../../../src/common/components/AutocompleteDropdown';
import Button from '../../../../src/common/components/Button';
import EditUserModal from '../../../../src/common/components/EditUserModal';
import Modal from '../../../../src/common/components/Modal';
import { extend, override } from '../../../../src/common/extend';
import { BooleanGambit } from '../../../../src/common/query/IGambit';
import type Model from '../../../../src/common/Model';
import type User from '../../../../src/common/models/User';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import RelationInput from '../../../../src/common/resource/inputs/RelationInput';
import type ItemList from '../../../../src/common/utils/ItemList';
import Stream from '../../../../src/common/utils/Stream';
import type { ResourceDescriptor } from '../../../../src/common/resource/types';
import { bootAdmin, schemaDocument } from '../../../unit/common/resource/fixtures';
import { usersDescriptor } from '../fixtures/usersDescriptor';

// Core's bundle exposes dayjs as a global (the join date column uses it); the jest setup doesn't.
(globalThis as any).dayjs ??= dayjs;

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

class StubModal extends Modal {
  className() {
    return 'StubModal';
  }

  title() {
    return 'Stub';
  }

  content() {
    return null;
  }
}

/** An ItemList's keys and priorities in render order. */
function entries(list: ItemList<unknown>): [string, number][] {
  return Object.entries(list.toObject())
    .map(([key, item], index) => ({ key, priority: item.priority, index }))
    .sort((a, b) => b.priority - a.priority || a.index - b.index)
    .map(({ key, priority }) => [key, priority]);
}

function results(models: Model[], total: number = models.length) {
  return Object.assign([...models], { payload: { data: [], meta: { page: { total } }, links: {} } });
}

function readBlob(blob: Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = () => reject(reader.error);
    reader.readAsText(blob);
  });
}

describe('UserListPage', () => {
  let find: jest.Mock<any>;
  let realFind: (...args: any[]) => Promise<unknown>;
  let descriptor: ResourceDescriptor;
  const restore: (() => void)[] = [];

  /** Patch a prototype method for one test; `afterEach` puts the original back. */
  function patch<T extends object>(proto: T, method: keyof T & string): void {
    const original = (proto as any)[method];
    restore.push(() => ((proto as any)[method] = original));
  }

  function group(id: string, nameSingular: string) {
    return app.store.pushObject({ type: 'groups', id, attributes: { nameSingular, namePlural: `${nameSingular}s`, color: null, icon: null } } as any);
  }

  function user(id: string, attributes: Record<string, unknown> = {}, groups: string[] = []): User {
    return app.store.pushObject<User>({
      type: 'users',
      id,
      attributes: {
        username: `user${id}`,
        displayName: `User ${id}`,
        email: `user${id}@machine.local`,
        joinTime: '2021-01-01T00:00:00Z',
        canEdit: true,
        canDelete: true,
        ...attributes,
      },
      relationships: { groups: { data: groups.map((g) => ({ type: 'groups', id: g })) } },
    } as any)!;
  }

  /** Answer `users` list requests with `fn`; other types go through the real store. */
  function respond(fn: (params: any) => Promise<unknown>): void {
    find.mockImplementation((type: string, ...args: any[]) => (type === 'users' ? fn(args[0]) : realFind(type, ...args)));
  }

  /** Answer the next `users` list request with a promise the test resolves. */
  function deferNext(): (value: unknown) => void {
    let resolve!: (value: unknown) => void;
    const previous = find.getMockImplementation()!;
    let used = false;

    find.mockImplementation((type: string, ...args: any[]) => {
      if (type !== 'users' || used) return previous(type, ...args);
      used = true;

      return new Promise((r) => (resolve = r));
    });

    return (value) => resolve(value);
  }

  function lastParams(): Record<string, any> {
    const calls = find.mock.calls.filter(([type]) => type === 'users');

    return (calls.at(-1) as any)[1];
  }

  /** Render the page; resolves once the descriptor and the first page are loaded. */
  async function rendered(): Promise<{ out: any; page: UserListPage }> {
    const oninit = jest.spyOn(UserListPage.prototype, 'oninit');
    const out = mq(UserListPage);
    const page = oninit.mock.contexts[0] as UserListPage;
    oninit.mockRestore();

    await flush();
    await flush();
    await flush();
    out.redraw();

    return { out, page };
  }

  beforeAll(() => {
    bootAdmin();
    group('4', 'Mod');
    group('5', 'Helper');
  });

  beforeEach(() => {
    clearDescriptorCache();
    ColumnPreferences.clear();
    window.localStorage.clear();
    window.history.replaceState(null, '', '/admin#/users');

    app.session.user!.pushAttributes({ isAdmin: true });
    app.data.modelStatistics = { users: { total: 3 } } as any;

    descriptor = usersDescriptor();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(descriptor, 'users'))) as any;

    // The descriptor loads through the real store (and the stubbed `app.request`); lists are stubbed.
    realFind = app.store.find.bind(app.store) as any;
    find = jest.fn();
    respond(() => Promise.resolve(results([user('2', {}, ['4', '5']), user('3', { canDelete: false, email: 'three@machine.local' })], 3)));
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    app.modal.show = jest.fn() as any;
    // mithril-query's DOM (domino) has no layout, so getBoundingClientRect() is missing.
    jest.spyOn(AutocompleteDropdown.prototype as any, 'updateMaxHeight').mockImplementation(() => {});
  });

  afterEach(() => {
    restore.splice(0).forEach((fn) => fn());
    jest.restoreAllMocks();
  });

  test('it renders', () => {
    const page = mq(UserListPage);

    expect(page).toHaveElement('.UserListPage');
  });

  describe('surface contract', () => {
    test('the public methods keep their names, keys and priorities', async () => {
      const { page } = await rendered();
      const u = user('2');

      for (const method of ['actionItems', 'columns', 'userActionItems', 'headerInfo', 'loadPage', 'goToPage', 'createModal', 'editModal']) {
        expect(typeof (UserListPage.prototype as any)[method]).toBe('function');
      }

      expect(entries(page.actionItems())).toEqual([['createUser', -100]]);
      expect(entries(page.columns())).toEqual([
        ['id', 100],
        ['username', 90],
        ['displayName', 85],
        ['joinDate', 80],
        ['groupBadges', 70],
        ['emailAddress', 70],
        ['userActions', -90],
      ]);
      expect(entries(page.userActionItems(u))).toEqual([
        ['edit', 1100],
        ['destructiveSeparator', -5000],
        ['delete', -9999.91],
      ]);
      expect(entries(page.userActionItems(user('3', { canDelete: false }))).map(([key]) => key)).toEqual(['edit', 'destructiveSeparator']);
      expect(page.headerInfo()).toMatchObject({ className: 'UserListPage', icon: 'fas fa-users' });
      expect((page as any).numPerPage).toBe(50);
    });

    test('columns carry their sorts, toggleability and CSV values', async () => {
      const { page } = await rendered();
      const columns = page.columns();
      const u = user('2', { email: 'two@machine.local' }, ['4', '5']);

      expect(columns.get('username').sort).toBe('username');
      expect(columns.get('joinDate').sort).toBe('joinedAt');
      expect(['id', 'username', 'userActions'].map((key) => columns.get(key).toggleable)).toEqual([false, false, false]);
      expect(columns.get('displayName').toggleable).toBeUndefined();
      expect(columns.get('emailAddress').csv!(u)).toBe('two@machine.local');
      expect(columns.get('groupBadges').csv!(u)).toBe('Mod; Helper');
      expect(columns.get('joinDate').csv!(u)).toBe('2021-01-01T00:00:00.000Z');
    });

    test('rows keep the UserListPage classes and data attributes, with exactly one actions column', async () => {
      const { out } = await rendered();

      // The shared toolbar: the table's search, filters, refresh and settings, then the page's "New User".
      expect(out).toHaveElement('.ResourceTable-toolbarActions .ResourceTable-refresh');
      expect(out).toHaveElement('.ResourceTable-toolbarActions .ResourceTable-settings');
      expect(out).toHaveElement('.ResourceTable-toolbarActions .UserListPage-createUserBtn.Button--primary');
      expect(out).not.toHaveElement('.ResourceTable-create');
      expect(out).not.toHaveElement('.UserListPage-totalUsers');
      expect(out).not.toHaveElement('.ResourceTable-sortDropdown');
      expect(out).toHaveElement(
        '.ResourceTable-toolbarView .ResourceTable-searchGroup .AutocompleteDropdown input.ResourceTable-search[type=search]'
      );
      expect(out.find('input[type=search]')).toHaveLength(1);
      // `UserListPage-grid` is the table container only.
      expect(out).toHaveElement('.ResourceTable.UserListPage-table');
      expect(out).not.toHaveElement('.ResourceTable.UserListPage-grid');
      expect(out).toHaveElement('.UserListPage-table > .ResourceTable-container.UserListPage-grid > table');
      expect(out).not.toHaveElement('.UserListPage-grid .ResourceTable-toolbar');
      expect(out).toHaveElement('.UserListPage-grid.UserListPage-grid--loaded');
      expect(out).toHaveElement('th.UserListPage-grid-header[data-column-name=username]');
      expect(out).toHaveElement('td.UserListPage-grid-rowItem[data-user-id="2"][data-column-name=emailAddress] .UserList-email');
      expect(out).not.toHaveElement('.UserListPage-grid-rowItem--shaded');

      // The email toggle reveals and hides one row's address.
      const email = '[data-user-id="2"][data-column-name=emailAddress] .UserList-email';
      expect(out).toHaveElementAttr(email, 'data-email-shown', 'false');
      expect(out).toHaveElementAttr(`${email} .UserList-emailAddress`, 'aria-hidden', 'true');
      out.click(`${email} .UserList-emailIconBtn`);
      expect(out).toHaveElementAttr(email, 'data-email-shown', 'true');
      expect(out).not.toHaveElementAttr(`${email} .UserList-emailAddress`, 'aria-hidden');
      expect(out).toHaveElement(`${email} .UserList-emailIconBtn .fa-eye`);
      expect(out).toHaveElementAttr('[data-user-id="3"][data-column-name=emailAddress] .UserList-email', 'data-email-shown', 'false');
      out.click(`${email} .UserList-emailIconBtn`);
      expect(out).toHaveElementAttr(email, 'data-email-shown', 'false');
      expect(out).toHaveElement(`${email} .UserList-emailIconBtn .fa-eye-slash`);
      expect(out).toHaveElement('.UserList-joinDate');

      // One actions column: the page's own `userActions`, with the shared overflow menu.
      expect(out).toHaveElement('th[data-column-name=userActions]');
      expect(out).not.toHaveElement('th[data-column-name=actions]');
      expect(out.find('tr[data-user-id="2"] .ResourceTable-rowMenu')).toHaveLength(1);
      expect(out).toHaveElementAttr('tr[data-user-id="2"] .ResourceTable-rowMenu .Dropdown-toggle', 'aria-label', 'Actions for User 2');

      // The pagination footer replaces the user count.
      expect(out.first('.ResourceTable-pageRange').textContent).toBe('1–2 of 3 items');
    });

    test("the descriptor's gambit-backed filters are left to the search; gambits stay in filter[q]", async () => {
      jest.useFakeTimers();

      try {
        const out = mq(UserListPage);
        await jest.runAllTimersAsync();
        out.redraw();

        // `email:` and `group:` are users gambits, so the search box covers both filters.
        expect(out).not.toHaveElement('.ResourceTable-toolbar .ResourceTable-filterGroup--email');
        expect(out).not.toHaveElement('.ResourceTable-toolbar .ResourceTable-filterGroup--group');

        out.setValue('.ResourceTable-toolbarView .ResourceTable-searchGroup input[type=search]', 'email:a@b.c');
        await jest.runAllTimersAsync();

        expect(lastParams()).toMatchObject({ filter: { q: 'email:a@b.c' }, page: { limit: 50, offset: 0 } });
      } finally {
        jest.useRealTimers();
      }
    });

    test('descriptor-only ui.column fields are appended; fields the page already shows are not', async () => {
      descriptor.fields.push({ name: 'suspendedUntil', kind: 'datetime', label: 'Suspended until', nullable: true, ui: { column: 60 } });

      const { out } = await rendered();

      expect(out).toHaveElement('th[data-column-name=suspendedUntil]');
      expect(out).not.toHaveElement('th[data-column-name=joinTime]');
      expect(out).not.toHaveElement('th[data-column-name=groups]');
      expect(out.find('thead th')).toHaveLength(8);
    });
  });

  test('a plain {name, content} column from an extension renders', async () => {
    patch(UserListPage.prototype, 'columns');
    extend(UserListPage.prototype, 'columns', (items) => {
      items.add('plain', { name: 'L', content: (u: User) => `plain-${u.username()}` });
    });

    const { out } = await rendered();

    expect(out).toHaveElement('th[data-column-name=plain]');
    expect(out).not.toHaveElement('th[data-column-name=plain] button');
    expect(out).toContainRaw('plain-user2');
  });

  test('an actionItems item from an extension renders in the toolbar before "New User"', async () => {
    patch(UserListPage.prototype, 'actionItems');
    extend(UserListPage.prototype, 'actionItems', (items) => {
      items.add('extra', m('span', { className: 'ExtExtra' }, 'Extra'));
    });

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-toolbarActions .ExtExtra + .UserListPage-createUserBtn');
  });

  test('an extension userActionItems item (as gdpr adds export-data) renders in the row dropdown', async () => {
    patch(UserListPage.prototype, 'userActionItems');
    extend(UserListPage.prototype, 'userActionItems', (items) => {
      items.add('export-data', m(Button, { className: 'Button GdprExportButton' }, 'Export data'));
    });

    const { out } = await rendered();

    expect(out).toHaveElement('tr[data-user-id="2"] [data-column-name=userActions] .ResourceTable-rowMenu .GdprExportButton');
  });

  describe('definition registry', () => {
    test('the page takes the registered users definition', async () => {
      const { page } = await rendered();

      expect((page as any).definition).toBeInstanceOf(UserResourceDefinition);
    });

    test('extend() on the definition prototype affects the page', async () => {
      patch(UserResourceDefinition.prototype, 'canExport');
      override(UserResourceDefinition.prototype, 'canExport', () => false);

      const { out } = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-export');
    });

    test("an extension's row action shows in the row menu between edit and delete and runs through the table", async () => {
      const run = jest.fn((_user: User) => Promise.resolve());

      patch(UserResourceDefinition.prototype, 'resourceActions');
      extend(UserResourceDefinition.prototype, 'resourceActions', function (items) {
        items.add('suspend', { key: 'suspend', label: 'Suspend', icon: 'fas fa-ban', scope: 'row', applies: (u: User) => u.id() === '2', run }, 50);
        items.add('poke', { key: 'poke', label: 'Poke', scope: 'bulk', run }, 40);
      });

      const { out, page } = await rendered();

      expect(out).toHaveElement('tr[data-user-id="2"] .ResourceTable-rowMenu .ResourceTable-suspend');
      expect(out).not.toHaveElement('tr[data-user-id="3"] .ResourceTable-suspend');
      expect(out).not.toHaveElement('.ResourceTable-rowMenu .ResourceTable-poke');
      expect(out.find('tr[data-user-id="2"] .ResourceTable-delete').length).toBe(1);
      expect(entries(page.userActionItems(user('2'))).map(([key]) => key)).toEqual(['edit', 'suspend', 'destructiveSeparator', 'delete']);

      out.click('tr[data-user-id="2"] .ResourceTable-suspend');
      await flush();

      expect(run).toHaveBeenCalledTimes(1);
      expect(run.mock.calls[0][0].id()).toBe('2');
      expect(app.modal.show).toHaveBeenLastCalledWith(BulkResultModal, expect.objectContaining({ summary: expect.anything() }));
    });

    test('row menu order: edit first, then the others by priority, then the destructive ones in danger style', async () => {
      patch(UserResourceDefinition.prototype, 'resourceActions');
      extend(UserResourceDefinition.prototype, 'resourceActions', function (items) {
        items.add('low', { key: 'low', label: 'Low', scope: 'row', run: () => Promise.resolve() }, 10);
        items.add('high', { key: 'high', label: 'High', destructive: true, scope: 'row', run: () => Promise.resolve() }, 60);
      });

      const { out, page } = await rendered();
      const menu = page.userActionItems(user('2'));

      expect(entries(menu).map(([key]) => key)).toEqual(['edit', 'low', 'destructiveSeparator', 'delete', 'high']);
      expect(out).toHaveElement('tr[data-user-id="2"] .ResourceTable-high.ResourceTable-menuAction--danger');
      expect(out).toHaveElement('tr[data-user-id="2"] .ResourceTable-delete.ResourceTable-menuAction--danger');
      expect(out).not.toHaveElement('tr[data-user-id="2"] .ResourceTable-low.ResourceTable-menuAction--danger');
      expect(out).toHaveElement('tr[data-user-id="2"] .ResourceTable-rowMenu li.Dropdown-separator');
    });

    test('a row action shows loading in the menu while it runs for that user', async () => {
      let finish: () => void = () => {};

      patch(UserResourceDefinition.prototype, 'resourceActions');
      extend(UserResourceDefinition.prototype, 'resourceActions', function (items) {
        items.add('ping', { key: 'ping', label: 'Ping', scope: 'row', run: () => new Promise<void>((resolve) => (finish = resolve)) });
      });

      const { out } = await rendered();

      out.click('tr[data-user-id="2"] .ResourceTable-ping');
      out.redraw();

      expect(out).toHaveElement('tr[data-user-id="2"] .ResourceTable-ping.loading');
      expect(out).not.toHaveElement('tr[data-user-id="3"] .ResourceTable-ping.loading');

      finish();
      await flush();
    });

    test('a confirming row action opens ResourceActionModal', async () => {
      patch(UserResourceDefinition.prototype, 'resourceActions');
      extend(UserResourceDefinition.prototype, 'resourceActions', function (items) {
        items.add('wipe', { key: 'wipe', label: 'Wipe', destructive: true, scope: 'row', run: () => Promise.resolve() });
      });

      const { out } = await rendered();

      out.click('tr[data-user-id="2"] .ResourceTable-wipe');

      expect(app.modal.show).toHaveBeenLastCalledWith(ResourceActionModal, expect.objectContaining({ models: [app.store.getById('users', '2')] }));
    });
  });

  test('?page=2 loads with offset 50', async () => {
    window.history.replaceState(null, '', '/admin#/users?page=2');

    const { page } = await rendered();

    expect(lastParams()).toMatchObject({ page: { offset: 50, limit: 50 } });
    expect((page as any).pageData.map((u: User) => u.id())).toEqual(['2', '3']);
  });

  test('?page=99 past the last page goes to the last page', async () => {
    window.history.replaceState(null, '', '/admin#/users?page=99');
    respond((params) => Promise.resolve(params.page.offset > 0 ? results([], 3) : results([user('2'), user('3')], 3)));

    const { page } = await rendered();

    expect(lastParams()).toMatchObject({ page: { offset: 0, limit: 50 } });
    expect((page as any).pageData.map((u: User) => u.id())).toEqual(['2', '3']);
    expect(window.location.hash).not.toContain('page=');
  });

  test('page 1 has no page param in the URL', async () => {
    const { page } = await rendered();

    page.goToPage(2);
    await flush();
    expect(window.location.hash).toContain('page=2');

    page.goToPage(1);
    await flush();
    expect(window.location.hash).toBe('#/users');
  });

  test('a search keeps the rows on screen (--loadingPage) until the new page arrives', async () => {
    let resolve = deferNext();

    jest.useFakeTimers();

    try {
      const out = mq(UserListPage);
      await jest.advanceTimersByTimeAsync(0);
      out.redraw();

      expect(out).toHaveElement('.UserListPage-grid.UserListPage-grid--loading');
      expect(out).not.toHaveElement('tr[data-user-id]');

      resolve(results([user('2'), user('3')], 3));
      await jest.advanceTimersByTimeAsync(0);
      out.redraw();

      expect(out).toHaveElement('.UserListPage-grid.UserListPage-grid--loaded');

      resolve = deferNext();
      out.setValue('.ResourceTable-toolbarView .ResourceTable-searchGroup input[type=search]', 'user3');
      await jest.advanceTimersByTimeAsync(300);
      out.redraw();

      expect(lastParams()).toMatchObject({ filter: { q: 'user3' } });
      expect(out).toHaveElement('.UserListPage-grid.UserListPage-grid--loadingPage');
      expect(out.find('tr[data-user-id]')).toHaveLength(2);

      resolve(results([user('3')], 1));
      await jest.advanceTimersByTimeAsync(0);
      out.redraw();

      expect(out).toHaveElement('.UserListPage-grid.UserListPage-grid--loaded');
      expect(out.find('tr[data-user-id]')).toHaveLength(1);
    } finally {
      jest.useRealTimers();
    }
  });

  test('onremove cancels a pending search', async () => {
    jest.useFakeTimers();

    try {
      const oninit = jest.spyOn(UserListPage.prototype, 'oninit');
      const out = mq(UserListPage);
      oninit.mockRestore();
      await jest.runAllTimersAsync();
      out.redraw();

      const calls = find.mock.calls.length;

      out.setValue('.ResourceTable-toolbarView .ResourceTable-searchGroup input[type=search]', 'user3');
      // Unmounting the page removes the table, which drops its pending search.
      m.render(out.rootEl, null);
      await jest.advanceTimersByTimeAsync(1000);

      expect(find.mock.calls.length).toBe(calls);
    } finally {
      jest.useRealTimers();
    }
  });

  test('loadPage is zero-based and goToPage one-based', async () => {
    const { page } = await rendered();

    await page.loadPage(2);
    expect(lastParams()).toMatchObject({ page: { offset: 100, limit: 50 } });

    page.goToPage(2);
    await flush();
    expect(lastParams()).toMatchObject({ page: { offset: 50, limit: 50 } });
    expect(window.location.hash).toContain('page=2');
  });

  test('clicking the username header sorts by username', async () => {
    const { out, page } = await rendered();

    out.click('th[data-column-name=username] button');
    await flush();

    expect(lastParams()).toMatchObject({ sort: 'username' });
    expect((page as any).list.getQuery()).toBe('');
  });

  describe('modal seams', () => {
    test('default: create opens CreateUserModal and edit opens EditUserModal', async () => {
      const { out, page } = await rendered();
      const u = app.store.getById<User>('users', '2')!;

      expect(page.createModal()).toBe(CreateUserModal);

      // A lazy loader, so EditUserModal stays its own chunk; it resolves to EditUserModal.
      const editModal = page.editModal(u) as any;
      expect(typeof editModal).toBe('function');
      expect(editModal.prototype instanceof Modal).toBe(false);
      expect(page.editModal(user('3'))).toBe(editModal);
      expect((await editModal()).default).toBe(EditUserModal);

      out.click('.UserListPage-createUserBtn');
      expect(app.modal.show).toHaveBeenLastCalledWith(CreateUserModal, expect.objectContaining({ onCreated: expect.any(Function) }));

      out.click('tr[data-user-id="2"] .ResourceTable-rowMenu .ResourceTable-edit');
      expect(app.modal.show).toHaveBeenLastCalledWith(editModal, expect.objectContaining({ user: u, onsaved: expect.any(Function) }));
    });

    test('extend(CreateUserModal.prototype, "fields") still takes effect', async () => {
      const { out } = await rendered();

      patch(CreateUserModal.prototype, 'fields');
      extend(CreateUserModal.prototype, 'fields', (items) => {
        items.add('extra', m('div.CreateUserModal-extra'));
      });

      out.click('.UserListPage-createUserBtn');

      const [component, attrs] = (app.modal.show as jest.Mock).mock.calls.at(-1) as [any, any];
      const modal = mq(component, { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...attrs });

      expect(modal).toHaveElement('.CreateUserModal-extra');
    });

    test('override: the stub modals open, and onCreated reloads the list', async () => {
      patch(UserListPage.prototype, 'createModal');
      patch(UserListPage.prototype, 'editModal');
      override(UserListPage.prototype, 'createModal', () => StubModal);
      override(UserListPage.prototype, 'editModal', () => StubModal);

      const { out } = await rendered();
      const u = app.store.getById<User>('users', '2')!;

      out.click('.UserListPage-createUserBtn');
      expect(app.modal.show).toHaveBeenLastCalledWith(StubModal, expect.objectContaining({ onCreated: expect.any(Function) }));

      const { onCreated } = (app.modal.show as jest.Mock).mock.calls.at(-1)![1] as { onCreated: (u: User) => void };
      const calls = find.mock.calls.length;
      onCreated(user('9'));
      await flush();

      expect(find.mock.calls.length).toBe(calls + 1);

      out.click('tr[data-user-id="2"] .ResourceTable-rowMenu .ResourceTable-edit');
      expect(app.modal.show).toHaveBeenLastCalledWith(StubModal, expect.objectContaining({ user: u, onsaved: expect.any(Function) }));
    });
  });

  test('delete opens ResourceDeleteModal for deletable users and reloads the list', async () => {
    const { out } = await rendered();

    expect(out).not.toHaveElement('tr[data-user-id="3"] .ResourceTable-delete');

    out.click('tr[data-user-id="2"] .ResourceTable-delete');

    expect(app.modal.show).toHaveBeenLastCalledWith(
      ResourceDeleteModal,
      expect.objectContaining({ model: app.store.getById('users', '2'), ondeleted: expect.any(Function) })
    );

    const calls = find.mock.calls.length;
    const { ondeleted } = (app.modal.show as jest.Mock).mock.calls.at(-1)![1] as { ondeleted: (u: User) => void };
    ondeleted(app.store.getById('users', '2')!);
    await flush();

    expect(find.mock.calls.length).toBe(calls + 1);
  });

  test('a bulk delete where one row fails deletes the others and names the failed row', async () => {
    patch(UserResourceDefinition.prototype, 'resourceActions');
    extend(UserResourceDefinition.prototype, 'resourceActions', function (items) {
      items.setContent('delete', { ...items.get('delete'), scope: 'both' });
    });
    respond(() => Promise.resolve(results([user('2'), user('3'), user('4', { displayName: 'Refused' })], 3)));

    const { out } = await rendered();
    const request = jest.fn((options: any) =>
      options.url.endsWith('/users/4') ? Promise.reject(Object.assign(new Error('403'), { status: 403 })) : Promise.resolve()
    );
    app.request = request as any;
    respond(() => Promise.resolve(results([user('4', { displayName: 'Refused' })], 1)));

    const check = out.first('.ResourceTable-selectAll');
    check.checked = true;
    out.trigger('.ResourceTable-selectAll', 'change');
    out.click('.ResourceTable-batchBar .ResourceTable-bulkDelete');

    const [confirmModal, confirmAttrs] = (app.modal.show as jest.Mock).mock.calls.at(-1) as [any, any];
    expect(confirmModal).toBe(ResourceActionModal);
    mq(ResourceActionModal, { state: {}, animateShow: () => {}, animateHide: jest.fn(), ...confirmAttrs }).trigger('form', 'submit', {} as Event);
    await flush();
    await flush();
    out.redraw();

    const deletes = request.mock.calls.map(([options]) => options).filter((options: any) => options.method === 'DELETE');
    expect(deletes).toHaveLength(3);

    const [component, attrs] = (app.modal.show as jest.Mock).mock.calls.at(-1) as [any, any];
    expect(component).toBe(BulkResultModal);
    expect(attrs.summary.ok.map((u: User) => u.id())).toEqual(['2', '3']);
    expect(attrs.summary.failed.map((f: any) => f.model.id())).toEqual(['4']);

    const modal = mq(BulkResultModal, { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...attrs });
    expect(modal).toHaveElement('.BulkResultModal-failed');
    expect(modal).toContainRaw('Refused');
  });

  test('CSV export guards formula-like usernames and display names', async () => {
    const blobs: Blob[] = [];
    (URL as any).createObjectURL = jest.fn((blob: Blob) => {
      blobs.push(blob);
      return 'blob:x';
    });
    (URL as any).revokeObjectURL = jest.fn();
    jest.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
    respond(() => Promise.resolve(results([user('7', { username: '@evil', displayName: '=HYPERLINK("x")', email: 'e@machine.local' })], 1)));

    const { out } = await rendered();

    out.click('.ResourceTable-settings .ResourceTable-export');
    await flush();
    await flush();

    const lines = (await readBlob(blobs[0])).replace(/^﻿/, '').split('\r\n');

    expect(lines[1]).toBe(`"'@evil","'=HYPERLINK(""x"")","2021-01-01T00:00:00.000Z","","e@machine.local"`);
  });

  describe('D11 defaults', () => {
    test('there is no checkbox column by default', async () => {
      const { out } = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-selectAll');
      expect(out).not.toHaveElement('.ResourceTable-select');
    });

    test('a bulk-scoped action adds the checkbox column', async () => {
      patch(UserResourceDefinition.prototype, 'resourceActions');
      extend(UserResourceDefinition.prototype, 'resourceActions', (items) => {
        items.add('x', { key: 'x', label: 'X', className: 'BulkX', scope: 'bulk', run: () => Promise.resolve() });
      });

      const { out } = await rendered();

      expect(out).toHaveElement('th .ResourceTable-selectAll');
      expect(out).toHaveElement('tr[data-user-id="2"] input.ResourceTable-select');

      out.first('tr[data-user-id="2"] input.ResourceTable-select').checked = true;
      out.trigger('tr[data-user-id="2"] input.ResourceTable-select', 'change');

      expect(out).toHaveElement('.ResourceTable-batchBar .BulkX');
      expect(out).not.toHaveElement('.ResourceTable-toolbarView');
    });

    test('an admin sees "Export CSV"; a user with only searchUsers does not', async () => {
      expect((await rendered()).out).toHaveElement('.ResourceTable-export');

      app.session.user!.pushAttributes({ isAdmin: false });

      const { out } = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-export');
    });

    test('the settings menu has no column checkbox for id, username or userActions', async () => {
      const { out } = await rendered();

      expect(out).toHaveElement('.ResourceTable-columnToggle input[name="displayName"]');
      expect(out).toHaveElement('.ResourceTable-columnToggle input[name="emailAddress"]');
      expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="id"]');
      expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="username"]');
      expect(out).not.toHaveElement('.ResourceTable-columnToggle input[name="userActions"]');
    });

    test('a CSV row holds the username, email and ;-joined group names', async () => {
      const blobs: Blob[] = [];
      (URL as any).createObjectURL = jest.fn((blob: Blob) => {
        blobs.push(blob);
        return 'blob:x';
      });
      (URL as any).revokeObjectURL = jest.fn();
      jest.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});

      const { out } = await rendered();

      out.click('.ResourceTable-export');
      await flush();
      await flush();

      expect(blobs).toHaveLength(1);

      const lines = (await readBlob(blobs[0])).replace(/^﻿/, '').split('\r\n');

      expect(lines[0].split(',').slice(1, 4)).toEqual(['"Display name"', '"Joined"', '"Groups"']);
      expect(lines[0].split(',')).toHaveLength(5);
      expect(lines[1]).toBe('"user2","User 2","2021-01-01T00:00:00.000Z","Mod; Helper","user2@machine.local"');
      expect(lines[2]).toBe('"user3","User 3","2021-01-01T00:00:00.000Z","","three@machine.local"');
    });
  });

  test('a group filter with a gambit of the same key in the search is marked overridden', async () => {
    window.history.replaceState(null, '', '/admin#/users?filter%5Bgroup%5D=4&q=group%3AHelper');

    const { out } = await rendered();

    expect(lastParams().filter).toMatchObject({ group: '4' });
    expect(out).toHaveElement('.ResourceTable-filterTags .ResourceTable-chip--group.ResourceTable-chip--overridden');
    expect(out).toContainRaw('Overridden by the search');
  });

  test('filters a gambit covers are left to the search box', async () => {
    const { out } = await rendered();

    expect(out).not.toHaveElement('.ResourceTable-filterGroup--group');
    expect(out).not.toHaveElement('.ResourceTable-filterGroup--email');
  });

  test('a search-covered group filter from the URL has no dropdown but a removable chip', async () => {
    window.history.replaceState(null, '', '/admin#/users?filter%5Bgroup%5D=4');

    const { out } = await rendered();

    expect(lastParams().filter).toMatchObject({ group: '4' });
    expect(out).not.toHaveElement('.ResourceTable-filterGroup--group');
    expect(out).toHaveElement('.ResourceTable-filterTags .ResourceTable-chip--group');

    out.click('.ResourceTable-chip--group button');
    await flush();
    await flush();
    out.redraw();

    expect(lastParams().filter ?? {}).not.toHaveProperty('group');
    expect(out).not.toHaveElement('.ResourceTable-chip--group');
  });

  test('the group filter labels groups like the groups field and leaves out the guest group', async () => {
    const { page } = await rendered();
    const filter = (page as any).definition.filters().get('group');
    const exclude = filter.exclude;

    // The `groups` field's display hint, though the filter is named `group`.
    expect(filter.input).toMatchObject({ type: 'relation', related: 'groups', display: 'nameSingular' });

    expect(exclude(group('2', 'Guest'))).toBe(true);
    expect(exclude(group('4', 'Mod'))).toBe(false);
  });

  test('the displayName CSV value drops zero-width spaces', async () => {
    const { page } = await rendered();

    expect(page.columns().get('displayName').csv!(user('9', { displayName: 'a.\u200Bb' }))).toBe('a.b');
  });

  describe('search filters', () => {
    const userGambits = () => app.search.gambits.for('users').filter((gambit) => gambit.enabled());
    const searchInput = (out: any) => out.find('.ResourceTable-toolbarView .ResourceTable-searchGroup input[type=search]')[0] as HTMLInputElement;

    test('it searches for the query in the URL, so other pages can link to a filtered list', async () => {
      window.history.replaceState(null, '', '/admin#/users?q=group%3AMods');

      const { out } = await rendered();

      expect(find.mock.calls.filter(([type]) => type === 'users')[0][1].filter).toEqual({ q: 'group:Mods' });
      expect(searchInput(out).value).toBe('group:Mods');
    });

    test('it keeps the query in the URL', async () => {
      const { out } = await rendered();

      out.setValue('.ResourceTable-toolbarView .ResourceTable-searchGroup input[type=search]', 'group:Mods');
      await new Promise((resolve) => setTimeout(resolve, 300));
      await flush();

      expect(new URLSearchParams(window.location.hash.split('?')[1]).get('q')).toBe('group:Mods');
    });

    test('it shows every user filter as a chip', async () => {
      const { out } = await rendered();
      const expected = userGambits().reduce((count, gambit: any) => {
        const key = gambit.suggestion().key;

        return count + (gambit.type === 'grouped' && key instanceof Array ? key.length : 1);
      }, 0);

      expect(userGambits().length).toBeGreaterThan(0);
      expect(out).toHaveElement('.ResourceTable-filterPanel .ResourceTable-filterGroup--search');
      expect(out.find('.ResourceTable-filterPanel .ResourceTable-searchFilter')).toHaveLength(expected);
    });

    test('a filter chip starts that filter in the search', async () => {
      const { out } = await rendered();
      const calls = find.mock.calls.length;
      const key = userGambits()[0].suggestion().key;
      // domino has no text selection.
      const setSelectionRange = ((searchInput(out) as any).setSelectionRange = jest.fn());

      out.click('.ResourceTable-filterPanel .ResourceTable-searchFilter');
      out.redraw();

      expect(searchInput(out).value).toBe(key + ':');
      expect(setSelectionRange).toHaveBeenCalledWith(key.length + 1, key.length + 1);

      // A key waits for its value: nothing is sent.
      await new Promise((resolve) => setTimeout(resolve, 300));
      expect(find.mock.calls.length).toBe(calls);
    });

    test('a complete filter chip searches for it, after the text already in the search', async () => {
      class SuspendedGambit extends BooleanGambit {
        key() {
          return 'suspended';
        }

        filterKey() {
          return 'suspended';
        }
      }

      const registered = app.search.gambits.gambits.users;
      app.search.gambits.gambits.users = [...registered, SuspendedGambit];
      restore.push(() => (app.search.gambits.gambits.users = registered));

      const { group } = new SuspendedGambit().suggestion();
      const chip = `${group}:suspended`;

      window.history.replaceState(null, '', '/admin#/users?q=user');

      const { out } = await rendered();

      out.click(`.ResourceTable-filterPanel .ResourceTable-searchFilter[data-filter="${chip}"]`);
      out.redraw();

      expect(searchInput(out).value).toBe(`user ${chip}`);

      await new Promise((resolve) => setTimeout(resolve, 300));
      await flush();

      expect(lastParams().filter).toEqual({ q: `user ${chip}` });
      expect(new URLSearchParams(window.location.hash.split('?')[1]).get('q')).toBe(`user ${chip}`);
    });

    test('the "Filter" panel opens for the search filters alone', async () => {
      descriptor.filters = [];

      const { out } = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-filterGroup--group');
      expect(out).toHaveElement('.ResourceTable-filterPanel .ResourceTable-filterGroup--search');
    });
  });

  test('groups pickers have no search box (core groups have no searcher)', () => {
    jest.spyOn(app.store, 'find').mockImplementation(jest.fn(() => Promise.resolve(Object.assign([], { payload: { links: {} } }))) as any);

    const picker = mq(RelationInput, { type: 'groups', multiple: true, display: 'nameSingular', stream: Stream([]) });

    expect(RelationInput.isSearchable('groups')).toBe(false);
    expect(picker).not.toHaveElement('input.ResourceRelationInput-search');
  });
});
