import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Extend from '../../../../src/common/extenders';
import Discussion from '../../../../src/common/models/Discussion';
import Model from '../../../../src/common/Model';
import app from '@flarum/core/src/forum/app';

beforeAll(() => bootstrapForum());

describe('Store extender', () => {
  const discussion = new Discussion({
    id: '1',
    type: 'discussions',
    attributes: {
      title: 'Discussion title',
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
      potato: {
        data: { id: '1', type: 'potatoes' },
      },
    },
  });

  const pushPotato = () =>
    app.store.pushPayload({
      data: {
        type: 'potatoes',
        id: '1',
        attributes: {},
      },
    });

  test('new model type does not work if not defined', () => {
    app.boot();

    expect(pushPotato).toThrow();

    // @ts-ignore
    expect(() => discussion.potato()).toThrow();
  });

  test('added route works', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Store().add('potatoes', Potato), new Extend.Model(Discussion).hasOne<Potato>('potato')],
      },
    });

    pushPotato();

    app.boot();

    // @ts-ignore
    expect(() => discussion.potato()).not.toThrow();
    // @ts-ignore
    expect(discussion.potato()).toBeInstanceOf(Potato);
  });
});

class Potato extends Model {}
