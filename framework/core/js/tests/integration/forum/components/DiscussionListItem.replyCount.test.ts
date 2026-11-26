import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import DiscussionListItem from '../../../../src/forum/components/DiscussionListItem';
import type Discussion from '../../../../src/common/models/Discussion';
import type User from '../../../../src/common/models/User';
import mq from 'mithril-query';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.store.pushPayload({ data: { type: 'users', id: '50', attributes: { username: 'reader' } } });
});

beforeEach(() => {
  app.session.user = undefined;
});

afterEach(() => jest.restoreAllMocks());

let nextId = 100;

function discussion(attributes: Record<string, unknown>): Discussion {
  const id = String(nextId++);

  app.store.pushPayload({ data: { type: 'discussions', id, attributes: { lastPostedAt: '2026-01-01T00:00:00Z', ...attributes } } });

  return app.store.getById<Discussion>('discussions', id)!;
}

function signIn() {
  app.session.user = app.store.getById<User>('users', '50')!;
}

function renderReplyCount(discussion: Discussion) {
  const item = Object.create(DiscussionListItem.prototype);
  item.attrs = { discussion, params: {} };

  return mq({ view: () => item.replyCountItem() }).rootEl.querySelector('.DiscussionListItem-count') as HTMLElement;
}

function accessibleName(el: HTMLElement) {
  return el.getAttribute('aria-label') || el.querySelector('.visually-hidden')!.textContent;
}

describe('DiscussionListItem reply count accessible label', () => {
  test('a guest hears the total replies', () => {
    const el = renderReplyCount(discussion({ commentCount: 3, lastPostNumber: 3 }));

    expect(el.tagName).toBe('SPAN');
    expect(accessibleName(el)).toBe('2 replies');
  });

  test('a user who has read the discussion hears the total replies', () => {
    signIn();
    const el = renderReplyCount(discussion({ commentCount: 3, lastPostNumber: 3, lastReadPostNumber: 3 }));

    expect(el.tagName).toBe('SPAN');
    expect(accessibleName(el)).toBe('2 replies');
  });

  test('the unread button is named by its unread replies', () => {
    signIn();
    const el = renderReplyCount(discussion({ commentCount: 6, lastPostNumber: 6, lastReadPostNumber: 4 }));

    expect(el.tagName).toBe('BUTTON');
    expect(accessibleName(el)).toBe('2 unread replies. Mark unread replies as read.');
  });

  test('the total is announced when the list forces the replies count', () => {
    signIn();
    jest.spyOn(DiscussionListItem.prototype, 'showRepliesCount').mockReturnValue(true);
    const el = renderReplyCount(discussion({ commentCount: 6, lastPostNumber: 6, lastReadPostNumber: 4 }));

    expect(el.tagName).toBe('SPAN');
    expect(accessibleName(el)).toBe('5 replies');
  });
});
