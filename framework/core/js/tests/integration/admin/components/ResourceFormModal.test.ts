import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import ResourceFormModal from '../../../../src/admin/components/resource/ResourceFormModal';
import EditUserModal from '../../../../src/common/components/EditUserModal';
import { extend } from '../../../../src/common/extend';
import type User from '../../../../src/common/models/User';
import type ItemList from '../../../../src/common/utils/ItemList';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import { bootAdmin, schemaDocument } from '../../../unit/common/resource/fixtures';
import { usersDescriptor } from '../fixtures/usersDescriptor';

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

/** The fields `EditUserModal` offers an admin editing another user, besides its submit button. */
const EDIT_USER_MODAL_CORE_FIELDS = ['username', 'email', 'password', 'groups'];

function modalAttrs(extra: Record<string, unknown>) {
  return { state: {}, animateShow: (cb: () => void) => cb(), animateHide: jest.fn(), ...extra };
}

/** Render a modal and return the output plus the component instance. */
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

function fieldKeys(items: ItemList<unknown>): string[] {
  return Object.keys(items.toObject()).filter((key) => key !== 'submit');
}

describe('ResourceFormModal', () => {
  let user: User;
  let request: jest.Mock<any>;

  beforeAll(() => {
    bootAdmin();
  });

  beforeEach(() => {
    clearDescriptorCache();

    request = jest.fn((options: any) => {
      if (options.url.includes('/resource-schemas/')) return Promise.resolve(schemaDocument(usersDescriptor(), 'users'));
      if (options.url.endsWith('/groups')) return Promise.resolve({ data: [], links: {} });

      // Save: echo the sent attributes back.
      return Promise.resolve({ data: { type: 'users', id: '2', attributes: options.body.data.attributes ?? {} } });
    });
    app.request = request as any;

    user = app.store.pushObject<User>({
      type: 'users',
      id: '2',
      attributes: {
        username: 'normal',
        displayName: 'Normal',
        email: 'normal@machine.local',
        isEmailConfirmed: true,
        canEdit: true,
        canEditCredentials: true,
        canEditGroups: true,
        canDelete: true,
      },
      relationships: { groups: { data: [] } },
    } as any)!;
  });

  afterEach(() => jest.restoreAllMocks());

  test("its writable fields match EditUserModal's core fields", async () => {
    const { modal: resource } = await render<ResourceFormModal<User>>(
      ResourceFormModal,
      modalAttrs({ definition: new ResourceDefinition<User>('users'), model: user })
    );
    const { modal: legacy } = await render<EditUserModal>(EditUserModal, modalAttrs({ user }));

    // The reference: what EditUserModal offers today.
    expect(fieldKeys(legacy.fields())).toEqual(EDIT_USER_MODAL_CORE_FIELDS);

    const keys = fieldKeys(resource.fields());

    expect(keys).toEqual(expect.arrayContaining(EDIT_USER_MODAL_CORE_FIELDS));
    // EditUserModal sets isEmailConfirmed through the "Activate" button inside its email item.
    expect(keys.filter((key) => !EDIT_USER_MODAL_CORE_FIELDS.includes(key))).toEqual(['isEmailConfirmed']);
  });

  test('the create form has no token input (ui.widget hidden) and requires the credentials', async () => {
    const { out, modal } = await render<ResourceFormModal<User>>(
      ResourceFormModal,
      modalAttrs({ definition: new ResourceDefinition<User>('users'), model: null })
    );

    const keys = fieldKeys(modal.fields());

    expect(keys).not.toContain('token');
    expect(keys).toEqual(expect.arrayContaining(['username', 'email', 'password']));
    expect(out).not.toHaveElement('.Modal-body [name=token]');

    for (const name of ['username', 'email', 'password']) {
      expect(out).toHaveElement(`.Modal-body input[name=${name}][required]`);
    }
  });

  test('fields() is an ItemList that extend() can add to', async () => {
    const original = ResourceFormModal.prototype.fields;

    extend(ResourceFormModal.prototype, 'fields', function (items: ItemList<any>) {
      items.add('extra', m('div.Form-group.ExtraField', 'Extra'), 5);
    });

    try {
      const { out, modal } = await render<ResourceFormModal<User>>(
        ResourceFormModal,
        modalAttrs({ definition: new ResourceDefinition<User>('users'), model: user })
      );

      expect(modal.fields().has('extra')).toBe(true);
      expect(out).toHaveElement('.Modal-body .ExtraField');
      expect(out).toHaveElement('.Modal-body input[name=username]');
    } finally {
      // Undo the patch: restoreAllMocks does not know about extend().
      ResourceFormModal.prototype.fields = original;
    }
  });

  test('submitData() holds the changed fields only', async () => {
    const { out, modal } = await render<ResourceFormModal<User>>(
      ResourceFormModal,
      modalAttrs({ definition: new ResourceDefinition<User>('users'), model: user })
    );

    expect(modal.submitData()).toEqual({});

    // FormGroup's `bidi` inputs need patchMithril to write back; drive the field's stream.
    modal.formState!.stream('username')('renamed');
    out.redraw();

    expect(modal.submitData()).toEqual({ username: 'renamed' });

    out.trigger('form', 'submit', {} as Event);
    await flush();

    const save = request.mock.calls.map((call: any) => call[0]).find((options: any) => options.method === 'PATCH');

    expect(save.body.data.attributes).toEqual({ username: 'renamed' });
    expect(save.body.data.relationships).toBeUndefined();
  });

  test('onsaved is called with the saved model', async () => {
    const onsaved = jest.fn();
    const attrs = modalAttrs({ definition: new ResourceDefinition<User>('users'), model: user, onsaved });
    const { out, modal } = await render<ResourceFormModal<User>>(ResourceFormModal, attrs);

    modal.formState!.stream('username')('renamed');
    out.redraw();
    out.trigger('form', 'submit', {} as Event);
    await flush();
    await flush();

    expect(onsaved).toHaveBeenCalledWith(user);
    expect(user.username()).toBe('renamed');
    expect(attrs.animateHide).toHaveBeenCalled();
  });
});
