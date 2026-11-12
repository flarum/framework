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
      { id: '2', type: 'tags', attributes: { slug: 'misc', name: 'Misc', position: null, isPrimary: false, isChild: false } },
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
