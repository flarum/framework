import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Extend from '../../../../src/common/extenders';
import app from '@flarum/core/src/forum/app';

beforeAll(() => bootstrapForum());

afterEach(() => {
  delete flarum.extensions['acme-other'];
  delete flarum.extensions['acme-another'];
});

describe('Conditional extender', () => {
  const routes = (name: string) => () => [new Extend.Routes().add(name, `/${name}`, null)];

  test('conditions are checked with the application before it boots', () => {
    let forumDuringCheck: unknown = null;

    const condition = jest.fn((received: typeof app) => {
      forumDuringCheck = received.forum;

      return true;
    });

    app.bootExtensions({
      test: {
        extend: [
          new Extend.Conditional()
            .when(condition, routes('beforeBoot'))
            .whenForumAttribute('canEditUserCredentials', true, routes('attributeBeforeBoot')),
        ],
      },
    });

    expect(condition).toHaveBeenCalledWith(app);
    expect(forumDuringCheck).toBeUndefined();
    expect(app.routes).toHaveProperty('beforeBoot');
    expect(app.routes).toHaveProperty('attributeBeforeBoot');
  });

  test('extenders apply when the condition is true', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().when(true, routes('whenTrue'))],
      },
    });

    app.boot();

    expect(app.routes).toHaveProperty('whenTrue');
  });

  test('extenders do not apply when the condition is false', () => {
    const extenders = jest.fn(routes('whenFalse'));

    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().when(() => false, extenders)],
      },
    });

    app.boot();

    expect(app.routes).not.toHaveProperty('whenFalse');
    expect(extenders).not.toHaveBeenCalled();
  });

  test('extenders apply when an extension is enabled', () => {
    flarum.extensions['acme-other'] = {};

    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenExtensionEnabled('acme-other', routes('enabled'))],
      },
    });

    app.boot();

    expect(app.routes).toHaveProperty('enabled');
  });

  test('extenders do not apply when an extension is disabled', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenExtensionEnabled('acme-other', routes('notEnabled'))],
      },
    });

    app.boot();

    expect(app.routes).not.toHaveProperty('notEnabled');
  });

  test('extenders apply when an extension is disabled and expected to be', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenExtensionDisabled('acme-other', routes('disabled'))],
      },
    });

    app.boot();

    expect(app.routes).toHaveProperty('disabled');
  });

  test('extenders apply when all extensions are enabled', () => {
    flarum.extensions['acme-other'] = {};
    flarum.extensions['acme-another'] = {};

    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenExtensionsEnabled(['acme-other', 'acme-another'], routes('allEnabled'))],
      },
    });

    app.boot();

    expect(app.routes).toHaveProperty('allEnabled');
  });

  test('extenders apply when all extensions are disabled', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenExtensionsDisabled(['acme-other', 'acme-another'], routes('allDisabled'))],
      },
    });

    app.boot();

    expect(app.routes).toHaveProperty('allDisabled');
  });

  test('extenders do not apply when only some extensions are enabled', () => {
    flarum.extensions['acme-other'] = {};

    app.bootExtensions({
      test: {
        extend: [
          new Extend.Conditional()
            .whenExtensionsEnabled(['acme-other', 'acme-another'], routes('someEnabled'))
            .whenExtensionsDisabled(['acme-other', 'acme-another'], routes('someDisabled')),
        ],
      },
    });

    app.boot();

    expect(app.routes).not.toHaveProperty('someEnabled');
    expect(app.routes).not.toHaveProperty('someDisabled');
  });

  test('extenders apply when a forum attribute matches', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenForumAttribute('canEditUserCredentials', true, routes('attributeMatches'))],
      },
    });

    app.boot();

    expect(app.routes).toHaveProperty('attributeMatches');
  });

  test('forum attributes are compared strictly', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenForumAttribute('canEditUserCredentials', 'true', routes('attributeLoose'))],
      },
    });

    app.boot();

    expect(app.routes).not.toHaveProperty('attributeLoose');
  });

  test('extenders do not apply when a forum attribute is missing', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Conditional().whenForumAttribute('acmeMissing', true, routes('attributeMissing'))],
      },
    });

    app.boot();

    expect(app.routes).not.toHaveProperty('attributeMissing');
  });
});
