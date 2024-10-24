import Drawer from '@flarum/core/src/common/utils/Drawer';
import { makeUser } from '@flarum/core/tests/factory';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';

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
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync('../locale/core.yml', 'utf8'))));
  app.drawer = new Drawer();
}
