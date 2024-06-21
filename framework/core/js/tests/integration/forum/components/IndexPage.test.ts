import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import IndexPage from '../../../../src/forum/components/IndexPage';
import { app } from '../../../../src/forum';
import mq from 'mithril-query';
import Discussion from '../../../../src/common/models/Discussion';
import { makeDiscussion } from '../../../factory';

beforeAll(() => bootstrapForum());

describe('IndexPage', () => {
  // mock app.store.find
  // @ts-ignore
  app.store.find = function () {
    return Promise.resolve([
      new Discussion(
        makeDiscussion({
          id: '1',
          attributes: {
            title: 'Discussion 1',
            slug: 'discussion-1',
          },
          relationships: {
            firstPost: {
              data: { type: 'posts', id: '2' },
            },
          },
        })
      ),
      new Discussion(
        makeDiscussion({
          id: '2',
          attributes: {
            title: 'Discussion 2',
            slug: 'discussion-2',
          },
          relationships: {
            firstPost: {
              data: { type: 'posts', id: '2' },
            },
          },
        })
      ),
    ]);
  };

  beforeAll(() => {
    app.boot();
    app.store.pushPayload({
      data: [
        {
          type: 'posts',
          id: '1',
          attributes: {
            content: 'Post 1',
            number: 1,
          },
          relationships: {
            discussion: {
              data: { type: 'discussions', id: '1' },
            },
            user: {
              data: { type: 'users', id: '1' },
            },
          },
        },
        {
          type: 'posts',
          id: '2',
          attributes: {
            content: 'Post 2',
            number: 1,
          },
          relationships: {
            discussion: {
              data: { type: 'discussions', id: '2' },
            },
            user: {
              data: { type: 'users', id: '1' },
            },
          },
        },
      ],
    });
  });

  test('renders', () => {
    const page = mq(IndexPage, {});

    // wait a tick for the store.find promise to resolve
    return new Promise((resolve) => setTimeout(resolve, 0)).then(() => {
      page.redraw();

      expect(page).toHaveElement('.IndexPage');
      expect(page).toHaveElement('.DiscussionList');
      expect(page).toHaveElement('.DiscussionListItem');
      expect(page).toContainRaw('Discussion 1');
      expect(page).toContainRaw('Discussion 2');
    });
  });
});
