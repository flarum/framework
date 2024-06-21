import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Routes from '../../../../src/common/extenders/Routes';
import app from '@flarum/core/src/forum/app';

beforeAll(() => bootstrapForum());

describe('Routes extender', () => {
  test('non added route does not work', () => {
    app.boot();

    expect(() => app.route('nonexistent')).toThrow();
  });

  test('added route works', () => {
    app.bootExtensions({
      test: {
        extend: [new Routes().add('nonexistent', '/nonexistent', null)],
      },
    });

    app.boot();

    expect(() => app.route('nonexistent')).not.toThrow();
    expect(app.route('nonexistent')).toBe('/nonexistent');
  });

  test('added route helper works', () => {
    app.bootExtensions({
      test: {
        extend: [new Routes().helper('nonexistent', () => '/nonexistent')],
      },
    });

    app.boot();

    // @ts-ignore
    expect(() => app.route.nonexistent()).not.toThrow();
    // @ts-ignore
    expect(app.route.nonexistent()).toBe('/nonexistent');
  });
});
