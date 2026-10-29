import Drawer from '@flarum/core/src/common/utils/Drawer';
import { makeUser } from '../../factory';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import path from 'path';

export default function bootstrap(Application, app, payload = {}) {
  Application.prototype.mount = () => {};

  app.load({
    apiDocument: null,
    locale: 'en',
    locales: {},
    resources: [
      {
        type: 'forums',
        id: '1',
        attributes: {
          canEditUserCredentials: true,
        },
      },
      makeUser({
        id: '1',
        attributes: {
          id: 1,
          username: 'admin',
          displayName: 'Admin',
          email: 'admin@machine.local',
        },
      }),
    ],
    session: {
      userId: 1,
      csrfToken: 'test',
    },
    ...payload,
  });

  app.translator.setLocale('en');
  // core's locale file lives at <core>/locale/core.yml. `__FLARUM_CORE_DIR__`
  // points at core's `js` dir (set by the jest config), so step up one level.
  const coreLocale = path.join(__FLARUM_CORE_DIR__, '..', 'locale', 'core.yml');
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(coreLocale, 'utf8'))));
  app.drawer = new Drawer();
}
