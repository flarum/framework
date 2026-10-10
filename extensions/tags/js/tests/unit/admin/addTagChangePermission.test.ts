import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import PermissionGrid from 'flarum/admin/components/PermissionGrid';
import extractText from 'flarum/common/utils/extractText';
import mq from 'mithril-query';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

import addTagChangePermission from '../../../src/admin/addTagChangePermission';

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
  addTagChangePermission();
});

const trans = (key: string) => extractText(app.translator.trans(`core.admin.permissions_controls.${key}`));

/** The "Allow tag editing" dropdown, as the Permissions page renders it. */
function dropdown() {
  const entry = PermissionGrid.prototype.startItems.call({}).get('allowTagChange') as any;

  return mq({ view: () => entry.setting() });
}

/** What the dropdown's button says the setting is. */
const shown = () => dropdown().first('.Button-labelText').textContent;

/**
 * Until an option is saved, authors can't change their discussions' tags, so
 * the dropdown must not claim otherwise.
 */
describe('Allow tag editing', () => {
  afterEach(() => {
    delete app.data.settings.allow_tag_change;
  });

  it('shows Never until an option is saved', () => {
    expect(shown()).toBe(trans('allow_never_button'));
  });

  it('can be set back to Never', () => {
    app.data.settings.allow_tag_change = '0';

    expect(shown()).toBe(trans('allow_never_button'));
  });

  it('shows a saved option', () => {
    app.data.settings.allow_tag_change = '-1';

    expect(shown()).toBe(trans('allow_indefinitely_button'));
  });
});
