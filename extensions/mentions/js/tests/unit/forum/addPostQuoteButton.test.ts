import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import { jest } from '@jest/globals';
import $ from 'jquery';
import addPostQuoteButton from '../../../src/forum/addPostQuoteButton';

const originalOncreate = CommentPost.prototype.oncreate;

beforeAll(() => {
  bootstrapForum();
  app.boot();

  // Only the quote button's own hook runs: the stand-in post below is not a
  // real CommentPost.
  CommentPost.prototype.oncreate = function () {};
  addPostQuoteButton();

  // jsdom has no innerText. The quote helper reads it from a detached clone,
  // where it is the same as textContent.
  Object.defineProperty(HTMLElement.prototype, 'innerText', {
    configurable: true,
    get() {
      return this.textContent;
    },
  });
});

afterAll(() => {
  CommentPost.prototype.oncreate = originalOncreate;
});

beforeEach(() => jest.useFakeTimers());

afterEach(() => {
  jest.useRealTimers();
  // @ts-ignore jsdom has no getClientRects on a range; each test sets its own.
  delete Range.prototype.getClientRects;
});

/** A rendered post that the quote button has been added to. */
function renderPost(): HTMLElement {
  app.store.pushPayload({
    data: {
      type: 'posts',
      id: '1',
      attributes: { number: 1, contentType: 'comment', isHidden: false },
      relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
    },
    included: [{ type: 'discussions', id: '1', attributes: { canReply: true } }],
  } as any);

  const post = app.store.getById<Post>('posts', '1')!;

  document.body.innerHTML = '<article class="Post"><div class="Post-body"><p>Some text to quote</p></div></article>';
  const element = document.querySelector<HTMLElement>('.Post')!;

  CommentPost.prototype.oncreate.call({ attrs: { post }, $: (selector?: string) => (selector ? $(element).find(selector) : $(element)) } as any);

  return element;
}

/** Select the post's text and release the mouse over it. */
function selectAndRelease(element: HTMLElement, rects: Partial<DOMRect>[]) {
  // @ts-ignore
  Range.prototype.getClientRects = () => rects;

  const range = document.createRange();
  range.selectNodeContents(element.querySelector('p')!);
  window.getSelection()!.removeAllRanges();
  window.getSelection()!.addRange(range);

  $(element).trigger($.Event('mouseup', { clientX: 50, clientY: 50 }));
  jest.advanceTimersByTime(1);
}

describe('the quote button', () => {
  it('shows above a selection', () => {
    const element = renderPost();

    selectAndRelease(element, [{ left: 40, right: 200, top: 60, bottom: 80 }]);

    expect(document.querySelector<HTMLElement>('.PostQuoteButton')!.style.left).toBe('40px');
  });

  // Browsers can report no rectangles for a selection, e.g. one that ends
  // between elements rather than inside text.
  it('does not throw for a selection with no client rects', () => {
    const element = renderPost();

    expect(() => selectAndRelease(element, [])).not.toThrow();
  });
});
