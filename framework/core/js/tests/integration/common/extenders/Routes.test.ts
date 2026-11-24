import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Routes from '../../../../src/common/extenders/Routes';
import UserPageResolver from '../../../../src/forum/resolvers/UserPageResolver';
import DiscussionPageResolver from '../../../../src/forum/resolvers/DiscussionPageResolver';
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

  /**
   * Both of core's own resolvers narrow the component they accept, which is
   * the only reason to write one. They have to be usable here.
   *
   * Note what this cannot check: the transform under Jest is Babel, which
   * strips types without reading them, so a signature that rejects these at
   * compile time still passes this test. The regression it guards is the
   * runtime wiring; the typing is guarded by `yarn check-typings`.
   */
  test.each([
    ['UserPageResolver', UserPageResolver],
    ['DiscussionPageResolver', DiscussionPageResolver],
  ])('a resolver that narrows its component is carried through (%s)', (_name, resolverClass) => {
    app.bootExtensions({
      test: {
        extend: [new Routes().add('nonexistent', '/nonexistent', null, resolverClass)],
      },
    });

    app.boot();

    expect(app.routes.nonexistent).toHaveProperty('resolverClass', resolverClass);
  });

  test('a route added without a resolver has none', () => {
    app.bootExtensions({
      test: {
        extend: [new Routes().add('nonexistent', '/nonexistent', null)],
      },
    });

    app.boot();

    expect(app.routes.nonexistent).not.toHaveProperty('resolverClass');
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

  test('removed route no longer works', () => {
    app.bootExtensions({
      first: {
        extend: [new Routes().add('removable', '/removable', null)],
      },
      second: {
        extend: [new Routes().remove('removable')],
      },
    });

    app.boot();

    expect(app.routes).not.toHaveProperty('removable');
    expect(() => app.route('removable')).toThrow();
  });

  test('a route added and removed by the same extender is removed', () => {
    app.bootExtensions({
      test: {
        extend: [new Routes().add('removableSame', '/removable-same', null).remove('removableSame')],
      },
    });

    app.boot();

    expect(app.routes).not.toHaveProperty('removableSame');
  });

  test('removing a route that does not exist does not throw', () => {
    app.bootExtensions({
      test: {
        extend: [new Routes().remove('neverRegistered')],
      },
    });

    expect(() => app.boot()).not.toThrow();
  });

  test('removing a route leaves other routes intact', () => {
    app.bootExtensions({
      test: {
        extend: [new Routes().add('kept', '/kept', null).add('dropped', '/dropped', null).remove('dropped')],
      },
    });

    app.boot();

    expect(app.route('kept')).toBe('/kept');
    expect(app.routes).not.toHaveProperty('dropped');
  });
});
