import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import RequestError from 'flarum/common/utils/RequestError';

import addNewActivity from '../../../../src/forum/extend/Discussion/NewActivity';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  // Only the realtime handlers are under test, not the page they're added to.
  jest.spyOn(DiscussionPage.prototype, 'oninit').mockImplementation(() => {});
  addNewActivity();
});

afterEach(() => jest.clearAllMocks());

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

const requestError = () => new RequestError(0, '', { url: '/api/discussions/1' } as any, {} as any);

/** A discussion page showing discussion 1, read to the end. */
function page() {
  const page: any = Object.create(DiscussionPage.prototype);

  page.discussion = { id: () => '1', commentCount: () => 1 };
  page.stream = { viewingEnd: () => true, syncEnd: jest.fn(() => Promise.resolve()) };
  page.oninit({});

  jest.spyOn(app.store, 'pushPayload').mockReturnValue({ id: () => '1' } as any);

  return page;
}

describe('realtime activity on a discussion page', () => {
  test.each(['websocketEventPosted', 'websocketEventStreamUpdate'])('%s brings new posts into view for a reader at the end', async (handler) => {
    const discussionPage = page();
    jest.spyOn(app.store, 'find').mockImplementation(() => Promise.resolve(discussionPage.discussion));

    discussionPage[handler]({});

    await settle();

    expect(app.store.find).toHaveBeenCalledWith('discussions', '1');
    expect(discussionPage.stream.syncEnd).toHaveBeenCalled();
  });

  test.each(['websocketEventPosted', 'websocketEventStreamUpdate'])('%s leaves a reader mid-discussion where they are', async (handler) => {
    const discussionPage = page();
    discussionPage.stream.viewingEnd = () => false;
    jest.spyOn(app.store, 'find').mockImplementation(() => Promise.resolve(discussionPage.discussion));

    discussionPage[handler]({});

    await settle();

    expect(app.store.find).toHaveBeenCalledWith('discussions', '1');
    expect(discussionPage.stream.syncEnd).not.toHaveBeenCalled();
  });

  test.each(['websocketEventPosted', 'websocketEventStreamUpdate'])(
    "%s when the discussion can't be reloaded leaves the error to the request",
    async (handler) => {
      const discussionPage = page();
      jest.spyOn(app.store, 'find').mockImplementation(() => Promise.reject(requestError()));

      discussionPage[handler]({});

      await settle();

      expect(discussionPage.stream.syncEnd).not.toHaveBeenCalled();
    }
  );

  test.each(['websocketEventPosted', 'websocketEventStreamUpdate'])(
    "%s when the new posts can't be loaded leaves the error to the request",
    async (handler) => {
      const discussionPage = page();
      jest.spyOn(app.store, 'find').mockImplementation(() => Promise.resolve(discussionPage.discussion));
      discussionPage.stream.syncEnd.mockImplementation(() => Promise.reject(requestError()));

      discussionPage[handler]({});

      await settle();

      expect(discussionPage.stream.syncEnd).toHaveBeenCalled();
    }
  );

  describe('in a tab in the background', () => {
    beforeEach(() => {
      jest.spyOn(document, 'hasFocus').mockReturnValue(false);
      jest.spyOn(app, 'setTitleCount').mockImplementation(() => {});
    });

    test('a new post counts in the title', async () => {
      const discussionPage = page();
      jest.spyOn(app.store, 'find').mockImplementation(() => Promise.resolve(discussionPage.discussion));

      discussionPage.websocketEventPosted({});

      await settle();

      expect(app.setTitleCount).toHaveBeenCalled();
    });

    test("a reload that fails leaves the title's count as it was", async () => {
      const discussionPage = page();
      jest.spyOn(app.store, 'find').mockImplementation(() => Promise.reject(requestError()));

      discussionPage.websocketEventPosted({});

      await settle();

      expect(app.setTitleCount).not.toHaveBeenCalled();
    });
  });
});
