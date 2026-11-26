import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import DiscussionListItem from '../../../../src/forum/components/DiscussionListItem';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';
import type Discussion from '../../../../src/common/models/Discussion';
import type User from '../../../../src/common/models/User';
import mq from 'mithril-query';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.store.pushPayload({ data: { type: 'users', id: '50', attributes: { username: 'reader' } } });
  app.session.user = app.store.getById<User>('users', '50')!;
  app.store.pushPayload({
    data: {
      type: 'discussions',
      id: '100',
      attributes: { commentCount: 5, lastPostNumber: 5, lastReadPostNumber: 2, lastPostedAt: '2026-01-01T00:00:00Z' },
    },
  });
});

function renderReplyCount(sort?: string) {
  const item = Object.create(DiscussionListItem.prototype);
  item.attrs = { discussion: app.store.getById<Discussion>('discussions', '100')!, params: sort ? { sort } : {} };

  return mq({ view: () => item.replyCountItem() }).rootEl.querySelector('.DiscussionListItem-count') as HTMLElement;
}

describe('DiscussionListItem reply count by sort', () => {
  test('the most-replies sort shows the total replies, even when some are unread', () => {
    const sort = Object.entries(new DiscussionListState({}).sortMap()).find(([, order]) => order === '-commentCount')![0];
    const el = renderReplyCount(sort);

    expect(el.tagName).toBe('SPAN');
    expect(el.querySelector('.DiscussionListItem-stats-item-label [aria-hidden]')!.textContent).toBe('4');
    expect(el.querySelector('.visually-hidden')!.textContent).toBe('4 replies');
  });

  test.each([undefined, 'latest', 'newest'])('sort %s shows the unread replies with a mark-as-read button', (sort) => {
    const el = renderReplyCount(sort);

    expect(el.tagName).toBe('BUTTON');
    expect(el.querySelector('.DiscussionListItem-stats-item-label [aria-hidden]')!.textContent).toBe('3');
  });
});
