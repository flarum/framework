import app from "@flarum/core/src/forum/app";
import jsYaml from "js-yaml";
import fs from "fs";
import jquery from "jquery";
import m from "mithril";
import ForumApplication from "@flarum/core/src/forum/ForumApplication";
import flatten from "flat";

beforeAll(() => {
  ForumApplication.prototype.mount = () => {};

  window.$ = jquery;
  window.m = m;
  window.flarum = { extensions: {} };
  app.load({
    apiDocument: null,
    locale: "en",
    locales: {},
    resources: [
      {
        type: "forums",
        id: "1",
        attributes: {},
      },
    ],
    session: {
      userId: 0,
      csrfToken: "test",
    },
  });
  app.translator.addTranslations(
    flatten(jsYaml.load(fs.readFileSync("../locale/core.yml", "utf8")))
  );
  app.bootExtensions(window.flarum.extensions);
  app.boot();
});
