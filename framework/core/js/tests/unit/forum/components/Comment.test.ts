import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import Comment from '../../../../src/forum/components/Comment';
import ItemList from '../../../../src/common/utils/ItemList';
import { app } from '../../../../src/forum';
import { override } from '../../../../src/common/extend';

beforeAll(() => bootstrapForum());

describe('Comment user card', () => {
  const attrs = (overrides: Record<string, unknown> = {}) => ({
    headerItems: new ItemList(),
    user: undefined,
    cardVisible: true,
    isEditing: false,
    isHidden: false,
    contentHtml: '<p>Hello</p>',
    ...overrides,
  });

  // The card stays visible while its user goes away: deleting a user from
  // their hover card removes the model from the store, so the post's user()
  // becomes undefined before the card has finished closing. A post by a user
  // who was deleted earlier has a user() of false.
  test.each([
    ['undefined', undefined],
    ['false', false],
  ])('no card is rendered, and nothing throws, when the user is %s', (_label, user) => {
    expect(() => mq(Comment, attrs({ user }))).not.toThrow();
    expect(mq(Comment, attrs({ user }))).not.toHaveElement('.UserCard');
  });

  test('the card is rendered for a user', () => {
    const [user] = [
      app.store.pushPayload({
        data: { type: 'users', id: '2', attributes: { username: 'ann', displayName: 'Ann' } },
      }),
    ] as any[];

    expect(mq(Comment, attrs({ user }))).toHaveElement('.UserCard');
  });

  test('no card is rendered while it is not visible', () => {
    expect(mq(Comment, attrs({ cardVisible: false }))).not.toHaveElement('.UserCard');
  });
});

describe('Comment search highlighting', () => {
  const attrs = (overrides: Record<string, unknown> = {}) => ({
    headerItems: new ItemList(),
    user: undefined,
    cardVisible: false,
    isEditing: false,
    isHidden: false,
    contentHtml: '<p>An example <a href="https://example.com/photo.png">photo</a></p>',
    ...overrides,
  });

  test('marks matching words in the post text', () => {
    const rendered = mq(Comment, attrs({ search: 'example' }));

    expect(rendered).toHaveElement('.Post-body mark');
    expect(rendered.find('.Post-body mark')[0].textContent).toBe('example');
  });

  test('leaves attributes untouched', () => {
    const rendered = mq(Comment, attrs({ search: 'example png' }));

    expect(rendered.find('.Post-body a')[0].getAttribute('href')).toBe('https://example.com/photo.png');
  });

  test('renders the content as-is without a search', () => {
    expect(mq(Comment, attrs())).not.toHaveElement('.Post-body mark');
  });
});

describe('Comment contentHtml hook', () => {
  const attrs = (overrides: Record<string, unknown> = {}) => ({
    headerItems: new ItemList(),
    user: undefined,
    cardVisible: false,
    isEditing: false,
    isHidden: false,
    contentHtml: '<p>A <img src="photo.png"></p>',
    ...overrides,
  });

  const original = Comment.prototype.contentHtml;
  let calls = 0;

  beforeEach(() => {
    calls = 0;
    override(Comment.prototype, 'contentHtml', function (this: Comment, orig: () => string) {
      calls++;
      return orig().replace(/<img ([^>]*)>/g, '<a class="Lightbox" href="photo.png"><img $1></a> gallery');
    });
  });

  afterEach(() => {
    Comment.prototype.contentHtml = original;
  });

  test('renders the transformed HTML', () => {
    expect(mq(Comment, attrs())).toHaveElement('.Post-body a.Lightbox img');
  });

  test('transforms before search highlighting', () => {
    const rendered = mq(Comment, attrs({ search: 'gallery' }));

    expect(rendered.find('.Post-body mark')[0].textContent).toBe('gallery');
    expect(rendered).toHaveElement('.Post-body a.Lightbox img');
  });

  test('is not called while the post is being edited', () => {
    const rendered = mq(Comment, attrs({ isEditing: true }));

    expect(calls).toBe(0);
    expect(rendered).not.toHaveElement('.Post-body img');
    expect(rendered).toHaveElement('.Post-body .Post-preview');
  });

  test('passes an empty string when the post has no HTML', () => {
    let received: unknown;
    Comment.prototype.contentHtml = original;
    override(Comment.prototype, 'contentHtml', function (this: Comment, orig: () => string) {
      return (received = orig());
    });

    mq(Comment, attrs({ contentHtml: null }));

    expect(received).toBe('');
  });

  test('keeps the original HTML when an override fails', () => {
    Comment.prototype.contentHtml = original;
    override(Comment.prototype, 'contentHtml', function () {
      throw new Error('broken transform');
    });

    const rendered = mq(Comment, attrs());

    expect(rendered).toHaveElement('.Post-body img');
    expect(rendered).not.toHaveElement('.Post-body a.Lightbox');
  });
});
