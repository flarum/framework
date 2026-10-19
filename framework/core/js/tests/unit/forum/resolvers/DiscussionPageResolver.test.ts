import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import PageState from '../../../../src/common/states/PageState';
import DiscussionPage from '../../../../src/forum/components/DiscussionPage';
import DiscussionPageResolver from '../../../../src/forum/resolvers/DiscussionPageResolver';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

beforeEach(() => {
  jest.useFakeTimers();

  // @ts-expect-error the global mithril stub carries no router
  m.route = { param: (key?: string) => (key ? ({ id: '1' } as Record<string, string>)[key] : { id: '1' }) };
});

afterEach(() => {
  jest.useRealTimers();
  DiscussionPageResolver.scrollToPostNumber = null;
});

/**
 * Route to another post of the discussion that is open, as following a link to
 * it from within the page does.
 */
function routeWithinTheDiscussion(near: number): void {
  const resolver = new DiscussionPageResolver(DiscussionPage, 'discussion.near');

  resolver.onmatch({ id: '1', near } as any, '', '');
  resolver.render({ attrs: {} } as any);
}

describe('DiscussionPageResolver', () => {
  test('scrolls the open discussion to the post routed to', () => {
    const goToNumber = jest.fn();

    app.current = new PageState(DiscussionPage);
    app.current.set('stream', { goToNumber });

    routeWithinTheDiscussion(32);
    jest.runAllTimers();

    expect(goToNumber).toHaveBeenCalledWith(32);
  });

  // Once it has loaded, the page goes to the post in the address itself.
  test('leaves a discussion that is still loading to go to the post itself', () => {
    app.current = new PageState(DiscussionPage);

    routeWithinTheDiscussion(32);

    expect(() => jest.runAllTimers()).not.toThrow();
  });
});
