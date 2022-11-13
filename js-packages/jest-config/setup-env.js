import app from '@flarum/core/src/forum/app';
import ForumApplication from '@flarum/core/src/forum/ForumApplication';
import jsYaml from 'js-yaml';
import fs from 'fs';
import jquery from 'jquery';
import m from 'mithril';
import flatten from 'flat';

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
        attributes: {},
      },
    ],
    session: {
      userId: 0,
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
