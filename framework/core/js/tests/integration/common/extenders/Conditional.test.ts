import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Extend from '../../../../src/common/extenders';
import app from '@flarum/core/src/forum/app';

beforeAll(() => bootstrapForum());

afterEach(() => {
  delete flarum.extensions['acme-other'];
});

describe('Conditional extender', () => {
  const routes = (name: string) => () => [new Extend.Routes().add(name, `/${name}`, null)];

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
});
