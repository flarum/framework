import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import AdminNav from '../../../../src/admin/components/AdminNav';
import GroupListPage, { GroupDefinition } from '../../../../src/admin/components/GroupListPage';
import ResourceDeleteModal from '../../../../src/admin/components/resource/ResourceDeleteModal';
import ResourceFormModal from '../../../../src/admin/components/resource/ResourceFormModal';
import type Group from '../../../../src/common/models/Group';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { bootAdmin, schemaDocument } from '../../../unit/common/resource/fixtures';
import { groupsDescriptor } from '../fixtures/groupsDescriptor';

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

function modalAttrs(extra: Record<string, unknown>) {
  return { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...extra };
}

async function render<T>(component: any, attrs: Record<string, unknown>): Promise<{ out: any; modal: T }> {
  const oninit = jest.spyOn(component.prototype, 'oninit');
  const out = mq(component, attrs);
  const modal = oninit.mock.contexts[0] as T;

  oninit.mockRestore();
  await flush();
  await flush();
  out.redraw();

  return { out, modal };
}

function groupDoc(id: string, attributes: Record<string, unknown> = {}) {
  const builtIn = ['1', '2', '3'].includes(id);

  return {
    type: 'groups',
    id,
    attributes: {
      nameSingular: `Group ${id}`,
      namePlural: `Groups ${id}`,
      color: null,
      icon: null,
      isHidden: false,
      position: null,
      canEdit: true,
      canDelete: !builtIn,
      ...attributes,
    },
  };
}

function groupDocs() {
  return [
    groupDoc('1', { nameSingular: 'Admin', namePlural: 'Admins', color: '#B72A2A', icon: 'fas fa-wrench' }),
    groupDoc('2', { nameSingular: 'Guest', namePlural: 'Guests' }),
    groupDoc('3', { nameSingular: 'Member', namePlural: 'Members' }),
    groupDoc('4', { nameSingular: 'Mod', namePlural: 'Mods', color: '#80349E', icon: 'fas fa-bolt' }),
    groupDoc('10', { nameSingular: 'Ninja', namePlural: 'Ninjas', isHidden: true }),
  ];
}

describe('GroupListPage', () => {
  let request: jest.Mock<any>;

  function requests(method: string): any[] {
    return request.mock.calls.map((call: any) => call[0]).filter((options: any) => (options.method ?? 'GET') === method);
  }

  function listRequests(): any[] {
    return requests('GET').filter((options: any) => /\/groups$/.test(options.url));
  }

  async function rendered(): Promise<any> {
    const out = mq(GroupListPage);

    await flush();
    await flush();
    await flush();
    out.redraw();

    return out;
  }

  beforeAll(() => bootAdmin());

  beforeEach(() => {
    clearDescriptorCache();
    ColumnPreferences.clear();
    window.localStorage.clear();
    window.history.replaceState(null, '', '/admin#/groups');
    app.store.all<Group>('groups').forEach((group) => app.store.remove(group));

    request = jest.fn((options: any) => {
      if (options.url.includes('/resource-schemas/')) return Promise.resolve(schemaDocument(groupsDescriptor(), 'groups'));

      if (options.method === 'POST') {
        return Promise.resolve({ data: groupDoc('20', { canDelete: true, ...options.body.data.attributes }) });
      }

      if (options.method === 'PATCH') {
        const id = options.url.split('/').pop();
        return Promise.resolve({ data: { type: 'groups', id, attributes: options.body.data.attributes } });
      }

      if (options.method === 'DELETE') return Promise.resolve(null);

      return Promise.resolve({ data: groupDocs(), links: {} });
    });
    app.request = request as any;
    app.modal.show = jest.fn() as any;
  });

  afterEach(() => jest.restoreAllMocks());

  test('it is a core route linked in the nav between Permissions and Appearance', () => {
    expect(app.routes.groups).toMatchObject({ path: '/groups', component: GroupListPage });

    const items = new AdminNav().items();

    expect(items.has('groups')).toBe(true);
    expect(items.getPriority('groups')).toBeLessThan(items.getPriority('permissions'));
    expect(items.getPriority('groups')).toBeGreaterThan(items.getPriority('appearance'));
  });

  test('it lists every group in one request, with badge, names and hidden columns', async () => {
    const out = await rendered();

    expect(out).toHaveElement('.GroupListPage');
    expect(out).toHaveElement('.ResourceTable--groups');
    expect(out.find('tbody tr').length).toBe(5);

    const headers = out.find('th[data-column-name]').map((th: any) => th.getAttribute('data-column-name'));

    expect(headers).toEqual(['badge', 'nameSingular', 'namePlural', 'isHidden']);
    expect(out).toHaveElement('tbody .Badge.Badge--group--1');
    expect(out).toContainRaw('Admins');

    // Unpaginated: no page parameters, no pager, no search, no export.
    expect(listRequests()[0].params?.page).toBeUndefined();
    expect(out).not.toHaveElement('.ResourceTable-pagination');
    expect(out).not.toHaveElement('.ResourceTable-search');
    expect(out).not.toHaveElement('.ResourceTable-export');
    expect(out).toHaveElement('.ResourceTable-filter--hidden');
  });

  test('built-in groups have edit but no delete; other groups have both', async () => {
    const out = await rendered();

    expect(out.find('.ResourceTable-edit').length).toBe(5);
    // Mod and Ninja only (the first delete is Mod's, see below).
    expect(out.find('.ResourceTable-delete').length).toBe(2);
  });

  test('create opens the form modal; delete opens the delete modal naming the group', async () => {
    const out = await rendered();

    out.click('.ResourceTable-create');

    const [modal, attrs] = (app.modal.show as jest.Mock<any>).mock.calls[0] as [unknown, any];

    expect(modal).toBe(ResourceFormModal);
    expect(attrs.model).toBeNull();
    expect(attrs.definition).toBeInstanceOf(GroupDefinition);

    out.find('.ResourceTable-delete')[0].click();

    const [deleteModal, deleteAttrs] = (app.modal.show as jest.Mock<any>).mock.calls[1] as [unknown, any];

    expect(deleteModal).toBe(ResourceDeleteModal);
    expect(deleteAttrs.model.id()).toBe('4');
    expect(deleteAttrs.definition.recordLabel(deleteAttrs.model)).toBe('Mod');
  });

  test('the create form: required names, colour and icon inputs, a switch; sends only what was filled', async () => {
    const onsaved = jest.fn();
    const { out, modal } = await render<ResourceFormModal<Group>>(
      ResourceFormModal,
      modalAttrs({ definition: new GroupDefinition(), model: null, onsaved })
    );

    expect(modal.title()).toEqual(app.translator.trans('core.admin.groups.create_title'));
    expect(out).toHaveElement('input[name=nameSingular][required][placeholder="Singular (e.g. Mod)"]');
    expect(out).toHaveElement('input[name=namePlural][required]');
    expect(out).toHaveElement('.ResourceForm-field--color .ColorInput');
    expect(out).toHaveElement('.ResourceForm-field--icon .IconInput');
    expect(out).toHaveElement('.ResourceForm-field--isHidden .Checkbox--switch');
    expect(out).toHaveElement('.ResourceForm-field--icon .helpText');
    expect(out).not.toHaveElement('[name=position]');
    expect(out).not.toHaveElement('.ResourceForm-field--badge');

    modal.formState!.stream('nameSingular')('Tester');
    modal.formState!.stream('namePlural')('Testers');
    modal.formState!.stream('color')('#123456');
    out.redraw();
    out.trigger('form', 'submit', {} as Event);
    await flush();
    await flush();

    const [save] = requests('POST');

    expect(save.body.data.attributes).toEqual({ nameSingular: 'Tester', namePlural: 'Testers', color: '#123456' });
    expect(onsaved).toHaveBeenCalled();
    expect(app.store.all<Group>('groups').map((group) => group.nameSingular())).toContain('Tester');
  });

  test('editing a built-in group: titled by its name, a colour change sends the colour only', async () => {
    await rendered();

    const admin = app.store.getById<Group>('groups', '1')!;
    const { out, modal } = await render<ResourceFormModal<Group>>(ResourceFormModal, modalAttrs({ definition: new GroupDefinition(), model: admin }));

    expect(out).toContainRaw('Edit Admin');

    modal.formState!.stream('color')('#000000');
    out.redraw();
    out.trigger('form', 'submit', {} as Event);
    await flush();
    await flush();

    const [save] = requests('PATCH');

    // The translated names are never written back.
    expect(save.body.data.attributes).toEqual({ color: '#000000' });
  });

  test('clearing the colour sends null', async () => {
    await rendered();

    const mod = app.store.getById<Group>('groups', '4')!;
    const { out, modal } = await render<ResourceFormModal<Group>>(ResourceFormModal, modalAttrs({ definition: new GroupDefinition(), model: mod }));

    modal.formState!.stream('color')('');
    out.redraw();
    out.trigger('form', 'submit', {} as Event);
    await flush();
    await flush();

    expect(requests('PATCH')[0].body.data.attributes).toEqual({ color: null });
  });

  test('deleting a group removes it from the store', async () => {
    await rendered();

    const ninja = app.store.getById<Group>('groups', '10')!;
    const ondeleted = jest.fn();
    const { out } = await render<ResourceDeleteModal<Group>>(
      ResourceDeleteModal,
      modalAttrs({ definition: new GroupDefinition(), model: ninja, ondeleted })
    );

    expect(out).toContainRaw('Ninja');

    out.trigger('form', 'submit', {} as Event);
    await flush();
    await flush();

    expect(requests('DELETE')[0].url).toMatch(/\/groups\/10$/);
    expect(ondeleted).toHaveBeenCalled();
    expect(app.store.getById('groups', '10')).toBeUndefined();
  });
});
