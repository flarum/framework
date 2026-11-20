import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import CommentPost from '../../../../src/forum/components/CommentPost';
import ItemList from '../../../../src/common/utils/ItemList';

beforeAll(() => bootstrapForum());

describe('CommentPost search', () => {
  const post = { isHidden: () => false, contentHtml: () => '<p>Hi</p>', user: () => undefined };

  const commentFor = (params: unknown) => {
    const fake = { attrs: { post, params }, headerItems: () => new ItemList(), isEditing: () => false, cardVisible: false };

    return (CommentPost.prototype as any).content.call(fake)[0];
  };

  test('passes the search phrase from its params to Comment', () => {
    expect(commentFor({ q: 'example' }).attrs.search).toBe('example');
  });

  test('passes no search phrase without params', () => {
    expect(commentFor(undefined).attrs.search).toBeUndefined();
  });

  test('passes the post to Comment', () => {
    expect(commentFor(undefined).attrs.post).toBe(post);
  });
});
