import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Model from '../../../../src/common/extenders/Model';
import Discussion from '../../../../src/common/models/Discussion';
import User from '../../../../src/common/models/User';
import app from '@flarum/core/src/forum/app';

beforeAll(() => bootstrapForum());

describe('Model extender', () => {
  const discussion = new Discussion({
    id: '1',
    type: 'discussions',
    attributes: {
      title: 'Discussion title',
      keanu: 'Reeves',
    },
    relationships: {
      posts: {
        data: [
          { id: '1', type: 'posts' },
          { id: '2', type: 'posts' },
          { id: '3', type: 'posts' },
          { id: '4', type: 'posts' },
        ],
      },
      idrisElba: {
        data: { id: '1', type: 'users' },
      },
      people: {
        data: [{ id: '1', type: 'users' }],
      },
    },
  });

  test('new attribute does not work if not defined', () => {
    app.boot();

    // @ts-ignore
    expect(() => discussion.keanu()).toThrow();
  });

  test('added route works', () => {
    app.bootExtensions({
      test: {
        extend: [new Model(Discussion).attribute<string>('keanu')],
      },
    });

    app.boot();

    // @ts-ignore
    expect(() => discussion.keanu()).not.toThrow();
    // @ts-ignore
    expect(discussion.keanu()).toBe('Reeves');
  });

  test('to one relationship does not work if not defined', () => {
    app.boot();

    // @ts-ignore
    expect(() => discussion.idrisElba()).toThrow();
  });

  test('added to one relationship works', () => {
    app.bootExtensions({
      test: {
        extend: [new Model(Discussion).hasOne<User>('idrisElba')],
      },
    });

    app.boot();

    // @ts-ignore
    expect(() => discussion.idrisElba()).not.toThrow();
    // @ts-ignore
    expect(discussion.idrisElba()).toBeInstanceOf(User);
  });

  test('to many relationship does not work if not defined', () => {
    app.boot();

    // @ts-ignore
    expect(() => discussion.people()).toThrow();
  });

  test('added to many relationship works', () => {
    app.bootExtensions({
      test: {
        extend: [new Model(Discussion).hasMany<User>('people')],
      },
    });

    app.boot();

    // @ts-ignore
    expect(() => discussion.people()).not.toThrow();
    // @ts-ignore
    expect(discussion.people()).toBeInstanceOf(Array);
    // @ts-ignore
    expect(discussion.people()[0]).toBeInstanceOf(User);
  });
});
