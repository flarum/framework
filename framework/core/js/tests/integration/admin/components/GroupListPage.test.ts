import { jest } from '@jest/globals';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import AdminNav from '../../../../src/admin/components/AdminNav';
import GroupListPage from '../../../../src/admin/components/GroupListPage';
import GroupDefinition from '../../../../src/admin/resource/definitions/GroupsDefinition';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceActionModal from '../../../../src/admin/components/resource/ResourceActionModal';
import ResourceDeleteModal from '../../../../src/admin/components/resource/ResourceDeleteModal';
import ResourceFormModal from '../../../../src/admin/components/resource/ResourceFormModal';
import type Group from '../../../../src/common/models/Group';
import extractText from '../../../../src/common/utils/extractText';
import RequestError from '../../../../src/common/utils/RequestError';
import ColumnPreferences from '../../../../src/common/resource/ColumnPreferences';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { awaitTables, bootAdmin, modalClass, schemaDocument } from '../../../unit/common/resource/fixtures';
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
  // The fixture server reports the first three groups as not deletable.
  const deletable = !['1', '2', '3'].includes(id);

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
      canDelete: deletable,
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
    await awaitTables(out);

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

      if (options.url.endsWith('/icon-image')) {
        const id = options.url.split('/').at(-2);
        const uploaded = options.method === 'POST';

        return Promise.resolve({
          data: {
            type: 'groups',
            id,
            attributes: { iconImageUrl: uploaded ? `https://example.test/icons/${id}.webp` : null, iconImageSrcset: null },
          },
        });
      }

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
    URL.createObjectURL = jest.fn(() => 'blob:preview') as any;
    URL.revokeObjectURL = jest.fn() as any;
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

    expect(headers).toEqual(['badge', 'nameSingular', 'namePlural', 'isHidden', 'actions']);
    expect(out).toHaveElement('tbody .Badge.Badge--group--1');
    expect(out).toContainRaw('Admins');

    // Unpaginated: no page parameters, no pagination, no search, no export.
    expect(listRequests()[0].params?.page).toBeUndefined();
    expect(out).not.toHaveElement('.ResourceTable-pagination');
    expect(out).not.toHaveElement('.ResourceTable-search');
    expect(out).not.toHaveElement('.ResourceTable-export');
    expect(out).toHaveElement('.ResourceTable-filterGroup--hidden');
  });

  test('it is a ResourceListPage: the header and the table in the padded wrapper', async () => {
    const out = await rendered();

    expect(out).toHaveElement('.AdminPage.GroupListPage .GroupListPage-header');
    expect(out).toHaveElement('.ResourceListPage .ResourceTable.GroupListPage-table');
  });

  test('rows follow canEdit/canDelete from the server: edit on all, delete only where canDelete', async () => {
    const out = await rendered();

    expect(out.find('.ResourceTable-edit').length).toBe(5);
    // Mod and Ninja only (the first delete is Mod's, see below).
    expect(out.find('.ResourceTable-delete').length).toBe(2);
  });

  test('rowAbilities.delete offers a bulk delete that confirms in ResourceActionModal; rows without canDelete cannot be selected', async () => {
    const out = await rendered();

    expect(out).toHaveElement('th .ResourceTable-selectAll');
    expect(out.find('tbody input.ResourceTable-select[disabled]').length).toBe(3);

    out.first('.ResourceTable-selectAll').checked = true;
    out.trigger('.ResourceTable-selectAll', 'change');
    expect(out.first('.ResourceTable-batchCount').textContent).toBe('2 items selected');
    out.click('.ResourceTable-batchBar .ResourceTable-bulkDelete');

    const [modal, attrs] = (app.modal.show as jest.Mock<any>).mock.calls.at(-1) as [unknown, any];
    const { out: dialog } = await render(ResourceActionModal, modalAttrs(attrs));

    expect(await modalClass(modal)).toBe(ResourceActionModal);
    expect(dialog).toContainRaw('Delete 2 entries?');
    expect(dialog).not.toHaveElement('.ResourceActionModal-skipped');
  });

  test('the page takes its definition from app.resources: the registered subclass, else a plain one', async () => {
    class CustomGroups extends GroupDefinition {
      label() {
        return 'Teams';
      }
    }

    app.resources.register('groups', CustomGroups);

    try {
      expect(app.resources.get('groups')).toBeInstanceOf(CustomGroups);

      const out = await rendered();
      out.click('.ResourceTable-create');

      expect((app.modal.show as jest.Mock<any>).mock.calls[0][1].definition).toBeInstanceOf(CustomGroups);
    } finally {
      app.resources.register('groups', GroupDefinition);
    }

    expect(app.resources.get('groups')).toBeInstanceOf(GroupDefinition);
    expect(app.resources.get('widgets').constructor).toBe(ResourceDefinition);
  });

  test('create opens the form modal; delete opens the delete modal naming the group', async () => {
    const out = await rendered();

    out.click('.ResourceTable-create');

    const [modal, attrs] = (app.modal.show as jest.Mock<any>).mock.calls[0] as [unknown, any];

    expect(await modalClass(modal)).toBe(ResourceFormModal);
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

    expect(extractText(modal.title())).toBe('Create Group');
    expect([new GroupDefinition().label(1), new GroupDefinition().label(2)]).toEqual(['Group', 'Groups']);
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

  describe('icon images', () => {
    const file = () => new File(['x'], 'crest.png', { type: 'image/png' });

    function choose(out: any, chosen: File) {
      const input = out.first('.ResourceForm-field--iconImage input[type=file]');

      Object.defineProperty(input, 'files', { value: [chosen], configurable: true });
      out.trigger('.ResourceForm-field--iconImage input[type=file]', 'change');
      out.redraw();
    }

    async function submit(out: any) {
      out.trigger('form', 'submit', {} as Event);
      await flush();
      await flush();
      await flush();
      out.redraw();
    }

    function iconImageRequests(): any[] {
      return request.mock.calls.map((call: any) => call[0]).filter((options: any) => options.url.endsWith('/icon-image'));
    }

    test("the badge column shows a group's icon image in its badge", async () => {
      request.mockImplementation((options: any) => {
        if (options.url.includes('/resource-schemas/')) return Promise.resolve(schemaDocument(groupsDescriptor(), 'groups'));

        return Promise.resolve({ data: [groupDoc('4', { icon: 'fas fa-bolt', iconImageUrl: 'https://example.test/icons/4.webp' })], links: {} });
      });

      const out = await rendered();

      expect(out).toHaveElement('tbody .Badge.Badge--group--4 .IconImage');
    });

    test('the image URL and srcset are neither inputs nor columns', async () => {
      const out = await rendered();
      const headers = out.find('th[data-column-name]').map((th: any) => th.getAttribute('data-column-name'));

      expect(headers).not.toContain('iconImageUrl');
      expect(headers).not.toContain('iconImageSrcset');

      const { out: form } = await render<ResourceFormModal<Group>>(ResourceFormModal, modalAttrs({ definition: new GroupDefinition(), model: null }));

      expect(form).not.toHaveElement('.ResourceForm-field--iconImageUrl');
      expect(form).not.toHaveElement('.ResourceForm-field--iconImageSrcset');
    });

    test('the edit form previews the current image after the icon, and removing it deletes it alone', async () => {
      request.mockImplementation((options: any) => {
        if (options.url.includes('/resource-schemas/')) return Promise.resolve(schemaDocument(groupsDescriptor(), 'groups'));
        if (options.url.endsWith('/icon-image')) return Promise.resolve({ data: { type: 'groups', id: '4', attributes: { iconImageUrl: null } } });

        return Promise.resolve({ data: [groupDoc('4', { iconImageUrl: 'https://example.test/icons/4.webp' })], links: {} });
      });
      await rendered();

      const mod = app.store.getById<Group>('groups', '4')!;
      const { out } = await render<ResourceFormModal<Group>>(ResourceFormModal, modalAttrs({ definition: new GroupDefinition(), model: mod }));

      const keys = out.find('.ResourceForm-field').map((el: any) => el.className.match(/ResourceForm-field--(\w+)/)[1]);

      expect(keys.indexOf('iconImage')).toBe(keys.indexOf('icon') + 1);
      expect(out.first('.ResourceForm-field--iconImage .IconImageInput-preview').getAttribute('src')).toBe('https://example.test/icons/4.webp');
      expect(out.first('.Form-controls .Button--primary').disabled).toBe(true);

      out.click('.ResourceForm-field--iconImage .Button--danger');
      out.redraw();
      expect(out).not.toHaveElement('.ResourceForm-field--iconImage .IconImageInput-preview');
      expect(out.first('.Form-controls .Button--primary').disabled).toBe(false);

      await submit(out);

      // Nothing else changed: no PATCH, just the DELETE.
      expect(requests('PATCH')).toEqual([]);
      expect(iconImageRequests().map((options) => [options.method, options.url])).toEqual([
        ['DELETE', 'https://example.test/api/groups/4/icon-image'],
      ]);
      expect(mod.iconImageUrl()).toBeNull();
    });

    test('creating a group uploads the chosen image to the new group once it exists', async () => {
      const onsaved = jest.fn();
      const { out, modal } = await render<ResourceFormModal<Group>>(
        ResourceFormModal,
        modalAttrs({ definition: new GroupDefinition(), model: null, onsaved })
      );
      const chosen = file();

      expect(out).toHaveElement('.ResourceForm-field--iconImage .IconImageInput');

      modal.formState!.stream('nameSingular')('Tester');
      modal.formState!.stream('namePlural')('Testers');
      choose(out, chosen);

      expect(out.first('.ResourceForm-field--iconImage .IconImageInput-preview').getAttribute('src')).toBe('blob:preview');

      await submit(out);

      const [create, upload] = requests('POST');

      expect(create.url).toMatch(/\/groups$/);
      expect(create.body.data.attributes).toEqual({ nameSingular: 'Tester', namePlural: 'Testers' });
      expect(upload.url).toBe('https://example.test/api/groups/20/icon-image');
      expect((upload.body as FormData).get('iconImage')).toBe(chosen);
      expect(onsaved).toHaveBeenCalled();
      expect(app.store.getById<Group>('groups', '20')!.iconImageUrl()).toBe('https://example.test/icons/20.webp');
    });

    test('a rejected upload shows under the field and keeps the form open; saving again uploads to the created group', async () => {
      let reject = true;
      const fallback = request.getMockImplementation()!;

      request.mockImplementation(async (options: any) => {
        if (options.url.endsWith('/icon-image') && reject) {
          reject = false;
          const error = new RequestError(
            422,
            JSON.stringify({ errors: [{ detail: 'The icon image must be an image.', source: { pointer: '/data/attributes/iconImage' } }] }),
            options,
            {} as XMLHttpRequest
          );
          await options.errorHandler?.(error);
          throw error;
        }

        return fallback(options);
      });

      const onsaved = jest.fn();
      const { out, modal } = await render<ResourceFormModal<Group>>(
        ResourceFormModal,
        modalAttrs({ definition: new GroupDefinition(), model: null, onsaved })
      );

      modal.formState!.stream('nameSingular')('Tester');
      modal.formState!.stream('namePlural')('Testers');
      choose(out, file());
      await submit(out);

      expect(onsaved).not.toHaveBeenCalled();
      expect(out.first('.ResourceForm-field--iconImage .ResourceForm-error').textContent).toBe('The icon image must be an image.');
      expect(out.first('.ResourceForm-field--iconImage .IconImageInput-preview').getAttribute('src')).toBe('blob:preview');

      await submit(out);

      expect(requests('POST').map((options) => options.url)).toEqual([
        'https://example.test/api/groups',
        'https://example.test/api/groups/20/icon-image',
        'https://example.test/api/groups/20/icon-image',
      ]);
      expect(onsaved).toHaveBeenCalledTimes(1);
    });
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
