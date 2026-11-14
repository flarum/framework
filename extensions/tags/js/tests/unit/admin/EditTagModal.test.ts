import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

import EditTagModal from '../../../src/admin/components/EditTagModal';
import Tag from '../../../src/common/models/Tag';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapAdmin();
  } finally {
    process.chdir(cwd);
  }

  app.boot();

  (app.store.models as any).tags = Tag;

  app.store.pushPayload({
    data: [
      { id: '1', type: 'tags', attributes: { slug: 'general', name: 'General', position: 0, isPrimary: true, isChild: false } },
      { id: '2', type: 'tags', attributes: { slug: 'misc', storedSlug: 'misc', name: 'Misc', position: null, isPrimary: false, isChild: false } },
    ],
  });
});

/** What the modal would send on save, opened with the given attributes. */
function submitData(attrs: Record<string, unknown>) {
  const modal = new (EditTagModal as any)();
  modal.oninit({ attrs: { state: app.modal, ...attrs } });

  return modal.submitData();
}

/**
 * Whether a tag is primary is decided when it's created, and changed only by
 * dragging it between the lists on the Tags page. The modal has no control
 * for it, so saving an existing tag must leave it as it is.
 */
describe('EditTagModal', () => {
  it('creates a primary tag', () => {
    expect(submitData({ primary: true }).isPrimary).toBe(true);
  });

  it('creates a secondary tag', () => {
    expect(submitData({ primary: false }).isPrimary).toBe(false);
  });

  it('leaves an existing primary tag primary', () => {
    expect(submitData({ model: app.store.getById('tags', '1') })).not.toHaveProperty('isPrimary');
  });

  it('leaves an existing secondary tag secondary', () => {
    expect(submitData({ model: app.store.getById('tags', '2') })).not.toHaveProperty('isPrimary');
  });
});

/**
 * Typing a name suggests the slug for a new tag. An existing tag's slug is in
 * links and search results, so it changes only when the admin edits it.
 */
describe('EditTagModal slug suggestion', () => {
  function open(attrs: Record<string, unknown> = {}) {
    const modal = new (EditTagModal as any)();
    modal.oninit({ attrs: { state: app.modal, ...attrs } });
    (modal as any).attrs = { state: app.modal, ...attrs };

    const field = (key: string) =>
      modal
        .fields()
        .get(key)
        .children.find((child: any) => child.tag === 'input');
    const type = (key: string, value: string) => field(key).attrs.oninput({ target: { value } });

    return { modal, type };
  }

  it('suggests a slug from a new tag name, in any script', () => {
    const { modal, type } = open();

    type('name', 'Tecnología');
    expect(modal.slug()).toBe('tecnologia');

    type('name', 'Новости');
    expect(modal.slug()).toBe('новости');
  });

  it('stops suggesting once the slug has been edited, and resumes when it is cleared', () => {
    const { modal, type } = open();

    type('name', 'Tecnología');
    type('slug', 'tech');
    type('name', 'Tecnología y más');
    expect(modal.slug()).toBe('tech');

    type('slug', '');
    type('name', 'Noticias');
    expect(modal.slug()).toBe('noticias');
  });

  it('leaves the slug of an existing tag alone when it is renamed', () => {
    const { modal, type } = open({ model: app.store.getById('tags', '2') });

    type('name', 'Новости');
    expect(modal.submitData().slug).toBe('misc');
  });
});
