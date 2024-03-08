import app from '@flarum/core/src/forum/app';
import ForumApplication from '@flarum/core/src/forum/ForumApplication';
import jsYaml from 'js-yaml';
import fs from 'fs';
import jquery from 'jquery';
import m from 'mithril';
import flatten from 'flat';
import './test-matchers';

// Boot the Flarum app.
function bootApp() {
  ForumApplication.prototype.mount = () => {};
  window.flarum = { extensions: {} };
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
      {
        type: 'users',
        id: '1',
        attributes: {
          id: 1,
          username: 'admin',
          displayName: 'Admin',
          email: 'admin@machine.local',
          joinTime: '2021-01-01T00:00:00Z',
          isEmailConfirmed: true,
        },
      },
    ],
    session: {
      userId: 1,
      csrfToken: 'test',
    },
  });
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync('../locale/core.yml', 'utf8'))));
  app.bootExtensions(window.flarum.extensions);
  app.boot();
}

beforeAll(() => {
  window.$ = jquery;
  window.m = m;

  bootApp();
});
