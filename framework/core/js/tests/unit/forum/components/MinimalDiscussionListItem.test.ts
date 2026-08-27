import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import DiscussionListItem from '../../../../src/forum/components/DiscussionListItem';
import MinimalDiscussionListItem from '../../../../src/forum/components/MinimalDiscussionListItem';
import ItemList from '../../../../src/common/utils/ItemList';
import humanTime from '../../../../src/common/utils/humanTime';

beforeAll(() => bootstrapForum());

describe('author tooltip', () => {
  const discussionCreatedAt = new Date('2020-01-01T00:00:00Z');
  const postCreatedAt = new Date('2024-06-01T00:00:00Z');

  const author = { id: () => '2', username: () => 'alice', displayName: () => 'Alice', slug: () => 'alice' };

  const discussion: any = {
    createdAt: () => discussionCreatedAt,
    user: () => author,
    badges: () => new ItemList(),
  };

  function tooltipCall(Klass: any, attrs: any) {
    const calls: Array<[string, any]> = [];
    const original = app.translator.trans;
    // @ts-ignore
    app.translator.trans = (key: string, params: any) => {
      calls.push([key, params]);
      return key;
    };

    try {
      const item = Object.create(Klass.prototype);
      item.attrs = attrs;
      item.authorItems();
    } finally {
      app.translator.trans = original;
    }

    const call = calls.find(([key]) => key.includes('_text'));

    return { key: call?.[0], params: call?.[1] };
  }

  test('a discussion result says "started" with the discussion time', () => {
    const { key, params } = tooltipCall(DiscussionListItem, { discussion });

    expect(key).toBe('core.forum.discussion_list.started_text');
    expect(params.ago).toBe(humanTime(discussionCreatedAt));
  });

  test('a post result says "replied" with the post time', () => {
    const { key, params } = tooltipCall(MinimalDiscussionListItem, {
      discussion,
      author,
      post: { createdAt: () => postCreatedAt },
    });

    expect(key).toBe('core.forum.discussion_list.replied_text');
    expect(params.ago).toBe(humanTime(postCreatedAt));
  });
});
