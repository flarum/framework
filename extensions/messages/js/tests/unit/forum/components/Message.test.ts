import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Comment from 'flarum/forum/components/Comment';
import ItemList from 'flarum/common/utils/ItemList';
import Message from '../../../../src/forum/components/Message';

beforeAll(() => bootstrapForum());

describe('Message content', () => {
  test('passes the message to Comment', () => {
    const message = { isHidden: () => false, contentHtml: () => '<p>Hi</p>', user: () => undefined };
    const fake = { attrs: { message }, headerItems: () => new ItemList(), isEditing: () => false };

    const content = (Message.prototype as any).content.call(fake);
    const comment = content.find((vnode: any) => vnode?.tag === Comment);

    expect(comment.attrs.post).toBe(message);
  });
});
