import { jest } from '@jest/globals';
import dayjs from 'dayjs';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import UserListPage from '../../../../src/admin/components/UserListPage';
import CreateUserModal from '../../../../src/admin/components/CreateUserModal';
import ResourceDeleteModal from '../../../../src/admin/components/resource/ResourceDeleteModal';
import BulkResultModal from '../../../../src/admin/components/resource/BulkResultModal';
import AutocompleteDropdown from '../../../../src/common/components/AutocompleteDropdown';
import Button from '../../../../src/common/components/Button';
import EditUserModal from '../../../../src/common/components/EditUserModal';
import Modal from '../../../../src/common/components/Modal';
import { extend, override } from '../../../../src/common/extend';
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

      for (const method of [
        'headerItems',
        'actionItems',
        'columns',
        'userActionItems',
        'headerInfo',
        'loadPage',
        'goToPage',
        'createModal',
        'editModal',
        'bulkActionItems',
        'canExport',
      ]) {
        expect(typeof (UserListPage.prototype as any)[method]).toBe('function');
      }

      expect(entries(page.headerItems())).toEqual([
        ['search', 100],
        ['totalUsers', 90],
        ['actions', 80],
      ]);
      expect(entries(page.actionItems())).toEqual([['createUser', 100]]);
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
        ['editUser', 0],
        ['delete', -100],
      ]);
      expect(entries(page.userActionItems(user('3', { canDelete: false })))).toEqual([['editUser', 0]]);
      expect(page.headerInfo()).toMatchObject({ className: 'UserListPage', icon: 'fas fa-users' });
      expect(page.bulkActionItems({} as any).isEmpty()).toBe(true);
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

      // The header items render in the table's toolbar: totalUsers in the count slot, actions beside it.
      expect(out).toHaveElement('.ResourceTable-toolbarActions.UserListPage-header .ResourceTable-count[role=status] .UserListPage-totalUsers');
      expect(out).toHaveElement('.ResourceTable-toolbarActions.UserListPage-header .UserListPage-actions .UserListPage-createUserBtn');
      // `UserListPage-grid` is the table container only, as it was the grid section only.
      expect(out).toHaveElement('.ResourceTable.UserListPage-table');
      expect(out).not.toHaveElement('.ResourceTable.UserListPage-grid');
      expect(out).toHaveElement('.UserListPage-table > .ResourceTable-container.UserListPage-grid > table');
      expect(out).not.toHaveElement('.UserListPage-grid .ResourceTable-toolbar');
      expect(out).not.toHaveElement('.UserListPage-grid .ResourceTable-pagination');
      expect(out).toHaveElement('.UserListPage-grid.UserListPage-grid--loaded');
      expect(out).toHaveElement('th.UserListPage-grid-header[data-column-name=username]');
      expect(out).toHaveElement('td.UserListPage-grid-rowItem[data-user-id="2"][data-column-name=emailAddress] .UserList-email');
      expect(out).toHaveElement('tr[data-user-id="3"] td.UserListPage-grid-rowItem--shaded');
      expect(out).toHaveElement('.UserList-joinDate');

      // One actions column: the page's own `userActions`, not the table's.
      expect(out).toHaveElement('th[data-column-name=userActions]');
      expect(out).not.toHaveElement('.ResourceTable-actionsHeader');
      expect(out).not.toHaveElement('.ResourceTable-actions');
      expect(out.find('tr[data-user-id="2"] .UserList-userActions')).toHaveLength(1);

      // The page's search and create replace the table's own.
      expect(out).toHaveElement('.ResourceTable-toolbarView .ResourceTable-searchGroup input[type=search]');
      expect(out.find('input[type=search]')).toHaveLength(1);
      expect(out).not.toHaveElement('.ResourceTable-search');
      expect(out).not.toHaveElement('.ResourceTable-create');
      expect(out).not.toHaveElement('.ResourceTable-refresh');
      expect(out).not.toHaveElement('.ResourceTable-sortDropdown');
    });

    test("the descriptor's filters render in the toolbar; gambit-backed text filters are left to the search; gambits stay in filter[q]", async () => {
      jest.useFakeTimers();

      try {
        const out = mq(UserListPage);
        await jest.runAllTimersAsync();
        out.redraw();

        // `email:` is a users gambit, so the search box covers the email filter.
        expect(out).not.toHaveElement('.ResourceTable-toolbar .ResourceTable-filter--email');
        expect(out).toHaveElement('.ResourceTable-toolbar .ResourceTable-filter--group');

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

  test('an old-shape {name, content} column from an extension renders', async () => {
    patch(UserListPage.prototype, 'columns');
    extend(UserListPage.prototype, 'columns', (items) => {
      items.add('legacy', { name: 'L', content: (u: User) => `legacy-${u.username()}` });
    });

    const { out } = await rendered();

    expect(out).toHaveElement('th[data-column-name=legacy]');
    expect(out).not.toHaveElement('th[data-column-name=legacy] button');
    expect(out).toContainRaw('legacy-user2');
  });

  test('a headerItems item between search (100) and totalUsers (90) renders right after the search', async () => {
    patch(UserListPage.prototype, 'headerItems');
    extend(UserListPage.prototype, 'headerItems', (items) => {
      items.add('between', m('span', { className: 'ExtBetween' }, 'between'), 95);
      items.add('low', m('span', { className: 'ExtLow' }, 'low'), 85);
    });

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-toolbarView .ResourceTable-searchGroup + .ExtBetween');
    expect(out).not.toHaveElement('.UserListPage-header .ExtBetween');
    expect(out).toHaveElement('.UserListPage-header .ExtLow');
    expect(out).not.toHaveElement('.ResourceTable-toolbarView .ExtLow');
  });

  test('an extension userActionItems item (as gdpr adds export-data) renders in the row dropdown', async () => {
    patch(UserListPage.prototype, 'userActionItems');
    extend(UserListPage.prototype, 'userActionItems', (items) => {
      items.add('export-data', m(Button, { className: 'Button GdprExportButton' }, 'Export data'));
    });

    const { out } = await rendered();

    expect(out).toHaveElement('tr[data-user-id="2"] [data-column-name=userActions] .UserList-userActions .GdprExportButton');
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
      const page = oninit.mock.contexts[0] as UserListPage;
      oninit.mockRestore();
      await jest.runAllTimersAsync();
      out.redraw();

      const calls = find.mock.calls.length;

      out.setValue('.ResourceTable-toolbarView .ResourceTable-searchGroup input[type=search]', 'user3');
      page.onremove({ dom: document.createElement('div') } as any);
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
    expect((page as any).query).toBe('');
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

      out.click('tr[data-user-id="2"] .UserList-userActions .Dropdown-menu button');
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

    test('override: the stub modals open, and onCreated still bumps the user total', async () => {
      patch(UserListPage.prototype, 'createModal');
      patch(UserListPage.prototype, 'editModal');
      override(UserListPage.prototype, 'createModal', () => StubModal);
      override(UserListPage.prototype, 'editModal', () => StubModal);

      const { out } = await rendered();
      const u = app.store.getById<User>('users', '2')!;

      out.click('.UserListPage-createUserBtn');
      expect(app.modal.show).toHaveBeenLastCalledWith(StubModal, expect.objectContaining({ onCreated: expect.any(Function) }));

      const { onCreated } = (app.modal.show as jest.Mock).mock.calls.at(-1)![1] as { onCreated: (u: User) => void };
      onCreated(user('9'));
      out.redraw();

      expect(app.data.modelStatistics.users.total).toBe(4);
      expect(out).toContainRaw('Total users: 4');

      out.click('tr[data-user-id="2"] .UserList-userActions .Dropdown-menu button');
      expect(app.modal.show).toHaveBeenLastCalledWith(StubModal, expect.objectContaining({ user: u, onsaved: expect.any(Function) }));
    });
  });

  test('delete opens ResourceDeleteModal for deletable users and lowers the total', async () => {
    const { out } = await rendered();

    expect(out).not.toHaveElement('tr[data-user-id="3"] .UserList-deleteUser');

    out.click('tr[data-user-id="2"] .UserList-deleteUser');

    expect(app.modal.show).toHaveBeenLastCalledWith(
      ResourceDeleteModal,
      expect.objectContaining({ model: app.store.getById('users', '2'), ondeleted: expect.any(Function) })
    );

    const calls = find.mock.calls.length;
    const { ondeleted } = (app.modal.show as jest.Mock).mock.calls.at(-1)![1] as { ondeleted: () => void };
    ondeleted();
    await flush();

    expect(app.data.modelStatistics.users.total).toBe(2);
    expect(find.mock.calls.length).toBe(calls + 1);
  });

  test('a bulk delete where one row fails deletes the others, names the failed row and lowers the total by 2', async () => {
    patch(UserListPage.prototype, 'bulkActionItems');
    extend(UserListPage.prototype, 'bulkActionItems', function (this: UserListPage, items, context) {
      items.add('delete', (this as any).definition.bulkDelete(context));
    });
    respond(() => Promise.resolve(results([user('2'), user('3'), user('4', { displayName: 'Refused' })], 3)));

    const { out } = await rendered();
    const request = jest.fn((options: any) =>
      options.url.endsWith('/users/4') ? Promise.reject(Object.assign(new Error('403'), { status: 403 })) : Promise.resolve()
    );
    app.request = request as any;
    jest.spyOn(window, 'confirm').mockImplementation(() => true);
    respond(() => Promise.resolve(results([user('4', { displayName: 'Refused' })], 1)));

    const check = out.first('.ResourceTable-selectAll');
    check.checked = true;
    out.trigger('.ResourceTable-selectAll', 'change');
    out.click('.ResourceTable-bulk .ResourceTable-bulkDelete');
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

    expect(app.data.modelStatistics.users.total).toBe(1);
    expect(out).toContainRaw('Total users: 1');
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

    out.click('.ResourceTable-export');
    await flush();
    await flush();

    const lines = (await readBlob(blobs[0])).replace(/^﻿/, '').split('\r\n');

    expect(lines[1]).toBe(`'@evil,"'=HYPERLINK(""x"")",2021-01-01T00:00:00.000Z,,e@machine.local`);
  });

  describe('D11 defaults', () => {
    test('there is no checkbox column by default', async () => {
      const { out } = await rendered();

      expect(out).not.toHaveElement('.ResourceTable-selectAll');
      expect(out).not.toHaveElement('.ResourceTable-select');
    });

    test('a bulkActionItems item adds the checkbox column', async () => {
      patch(UserListPage.prototype, 'bulkActionItems');
      extend(UserListPage.prototype, 'bulkActionItems', (items) => {
        items.add('x', m(Button, { className: 'Button BulkX' }, 'X'));
      });

      const { out } = await rendered();

      expect(out).toHaveElement('th .ResourceTable-selectAll');
      expect(out).toHaveElement('tr[data-user-id="2"] input.ResourceTable-select');
      expect(out).toHaveElement('.ResourceTable-bulk .BulkX');
    });

    test('an admin sees "Export CSV"; a user with only searchUsers does not', async () => {
      expect((await rendered()).out).toHaveElement('.ResourceTable-export');

      app.session.user!.pushAttributes({ isAdmin: false });

      const { out, page } = await rendered();

      expect(page.canExport()).toBe(false);
      expect(out).not.toHaveElement('.ResourceTable-export');
    });

    test('the Columns dropdown has no checkbox for id, username or userActions', async () => {
      const { out } = await rendered();

      expect(out).toHaveElement('.ResourceTable-columns input[name="displayName"]');
      expect(out).toHaveElement('.ResourceTable-columns input[name="emailAddress"]');
      expect(out).not.toHaveElement('.ResourceTable-columns input[name="id"]');
      expect(out).not.toHaveElement('.ResourceTable-columns input[name="username"]');
      expect(out).not.toHaveElement('.ResourceTable-columns input[name="userActions"]');
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

      expect(lines[0].split(',').slice(1, 4)).toEqual(['Display name', 'Joined', 'Groups']);
      expect(lines[0].split(',')).toHaveLength(5);
      expect(lines[1]).toBe('user2,User 2,2021-01-01T00:00:00.000Z,Mod; Helper,user2@machine.local');
      expect(lines[2]).toBe('user3,User 3,2021-01-01T00:00:00.000Z,,three@machine.local');
    });
  });

  test('header items render in the toolbar, an extension item included', async () => {
    patch(UserListPage.prototype, 'headerItems');
    extend(UserListPage.prototype, 'headerItems', (items) => {
      items.add('extra', m('span', { className: 'HeaderExtra' }, 'Extra'), 85);
    });

    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-toolbarView .AutocompleteDropdown input[type=search]');
    expect(out).toHaveElement('.ResourceTable-toolbarActions .ResourceTable-count .UserListPage-totalUsers');
    expect(out).toHaveElement('.ResourceTable-toolbarActions .HeaderExtra');
    expect(out).toHaveElement('.ResourceTable-toolbarActions .UserListPage-actions');
  });

  test('a group filter with a gambit of the same key in the search is marked overridden', async () => {
    window.history.replaceState(null, '', '/admin#/users?filter%5Bgroup%5D=4&q=group%3AHelper');

    const { out } = await rendered();

    expect(lastParams().filter).toMatchObject({ group: '4' });
    expect(out).toHaveElement('.ResourceTable-subToolbar .ResourceTable-chip--group.ResourceTable-chip--overridden');
    expect(out).toContainRaw('Overridden by the search');
  });

  test('the count is the forum total, or the matching total while a filter narrows the list', async () => {
    const { out } = await rendered();

    expect(out).toContainRaw('Total users: 3');

    window.history.replaceState(null, '', '/admin#/users?filter%5Bgroup%5D=4');
    respond(() => Promise.resolve(results([user('2', {}, ['4'])], 1)));
    const narrowed = await rendered();

    expect(lastParams().filter).toMatchObject({ group: '4' });
    expect(narrowed.out).toContainRaw('1 matching user of 3');
  });

  test('searchCoveredFilters() is the toolbar policy: "all" leaves group to the group: gambit', async () => {
    const { out } = await rendered();

    expect(out).toHaveElement('.ResourceTable-filter--group');
    expect(out).not.toHaveElement('.ResourceTable-filter--email');

    patch(UserListPage.prototype, 'searchCoveredFilters');
    UserListPage.prototype.searchCoveredFilters = () => 'all';
    const all = await rendered();

    expect(all.out).not.toHaveElement('.ResourceTable-filter--group');
  });

  test('the group filter leaves out the guest group', async () => {
    const { page } = await rendered();
    const exclude = (page as any).definition.filters().get('group').exclude;

    expect(exclude(group('2', 'Guest'))).toBe(true);
    expect(exclude(group('4', 'Mod'))).toBe(false);
  });

  test('the displayName CSV value drops zero-width spaces', async () => {
    const { page } = await rendered();

    expect(page.columns().get('displayName').csv!(user('9', { displayName: 'a.\u200Bb' }))).toBe('a.b');
  });

  test('groups pickers have no search box (core groups have no searcher)', () => {
    jest.spyOn(app.store, 'find').mockImplementation(jest.fn(() => Promise.resolve(Object.assign([], { payload: { links: {} } }))) as any);

    const picker = mq(RelationInput, { type: 'groups', multiple: true, display: 'nameSingular', stream: Stream([]) });

    expect(RelationInput.isSearchable('groups')).toBe(false);
    expect(picker).not.toHaveElement('input.ResourceRelationInput-search');
  });
});
