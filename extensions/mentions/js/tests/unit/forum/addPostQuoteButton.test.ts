import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import { jest } from '@jest/globals';
import $ from 'jquery';
import addPostQuoteButton from '../../../src/forum/addPostQuoteButton';

const originalOncreate = CommentPost.prototype.oncreate;

// A phone-sized viewport, and the size of the rendered button. jsdom does no
// layout, so both are set here.
const VIEWPORT_WIDTH = 375;
const VIEWPORT_HEIGHT = 700;
const BUTTON_WIDTH = 90;
const BUTTON_HEIGHT = 36;

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

  Object.defineProperty(document.documentElement, 'clientWidth', { configurable: true, value: VIEWPORT_WIDTH });
  Object.defineProperty(window, 'innerHeight', { configurable: true, value: VIEWPORT_HEIGHT });
  // jQuery only measures an element that has a box.
  const getClientRects = HTMLElement.prototype.getClientRects;
  HTMLElement.prototype.getClientRects = function () {
    return this.classList.contains('PostQuoteButton') ? ([{}] as any) : getClientRects.call(this);
  };
  Object.defineProperty(HTMLElement.prototype, 'offsetWidth', {
    configurable: true,
    get() {
      return this.classList.contains('PostQuoteButton') ? BUTTON_WIDTH : 0;
    },
  });
  Object.defineProperty(HTMLElement.prototype, 'offsetHeight', {
    configurable: true,
    get() {
      return this.classList.contains('PostQuoteButton') ? BUTTON_HEIGHT : 0;
    },
  });

  // Mobile browsers report a selection made by touch through selectionchange,
  // which the button only listens to on touch screens.
  (window as any).ontouchstart = null;
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

/** Select the post's text, reporting the given client rects for the selection. */
function select(element: HTMLElement, rects: Partial<DOMRect>[]) {
  // @ts-ignore
  Range.prototype.getClientRects = () => rects;

  const range = document.createRange();
  range.selectNodeContents(element.querySelector('p')!);
  window.getSelection()!.removeAllRanges();
  window.getSelection()!.addRange(range);
}

/** Select the post's text and release the mouse over it. */
function selectAndRelease(element: HTMLElement, rects: Partial<DOMRect>[], pointer = { clientX: 50, clientY: 50 }) {
  select(element, rects);

  $(element).trigger($.Event('mouseup', pointer));
  jest.advanceTimersByTime(1);
}

/** Select the post's text by touch, which only fires selectionchange. */
function selectByTouch(element: HTMLElement, rects: Partial<DOMRect>[]) {
  select(element, rects);

  document.dispatchEvent(new Event('selectionchange'));
  jest.advanceTimersByTime(1);
}

function quoteButton(): HTMLElement {
  return document.querySelector<HTMLElement>('.PostQuoteButton')!;
}

describe('the quote button', () => {
  it('shows above a selection', () => {
    const element = renderPost();

    selectAndRelease(element, [{ left: 40, right: 200, top: 60, bottom: 80 }]);

    expect(quoteButton().style.left).toBe('40px');
  });

  // The button is right-aligned with the end of a selection, so a selection
  // ending near the left edge of the screen used to put most of it off-screen.
  it('stays inside the viewport for a selection ending near its left edge', () => {
    const element = renderPost();

    selectAndRelease(element, [{ left: 0, right: 30, top: 60, bottom: 80 }], { clientX: 30, clientY: 90 });

    expect(quoteButton().style.left).toBe('10px');
  });

  it('stays inside the viewport for a selection starting near its right edge', () => {
    const element = renderPost();

    selectAndRelease(element, [{ left: 350, right: 370, top: 60, bottom: 80 }], { clientX: 350, clientY: 70 });

    expect(quoteButton().style.left).toBe(`${VIEWPORT_WIDTH - BUTTON_WIDTH - 10}px`);
  });

  // On touch screens the browser's own selection handles and text menu sit
  // right next to the selection, on top of the page, and cover what's beneath.
  it('shows well clear of a selection made by touch', () => {
    const element = renderPost();

    selectByTouch(element, [
      { left: 15, right: 360, top: 300, bottom: 320 },
      { left: 0, right: 30, top: 320, bottom: 340 },
    ]);

    expect(quoteButton().style.top).toBe(`${340 + 70}px`);
    expect(quoteButton().style.left).toBe('10px');
  });

  it('shows above a selection made by touch when there is no room below it', () => {
    const element = renderPost();

    selectByTouch(element, [
      { left: 15, right: 360, top: 580, bottom: 600 },
      { left: 15, right: 200, top: 600, bottom: 620 },
    ]);

    expect(quoteButton().style.top).toBe(`${580 - 70 - BUTTON_HEIGHT}px`);
  });

  // Dragging a selection handle fires selectionchange over and over, showing
  // the button again each time without a click in between.
  it('stops listening for clicks once hidden, however many times it was shown', () => {
    const element = renderPost();

    selectByTouch(element, [{ left: 40, right: 200, top: 60, bottom: 80 }]);
    selectByTouch(element, [{ left: 40, right: 250, top: 60, bottom: 80 }]);
    $(document).trigger('mouseup');

    expect(quoteButton().style.display).toBe('none');
    expect(($ as any)._data(document, 'events')?.mouseup).toBeUndefined();
  });

  // Browsers can report no rectangles for a selection, e.g. one that ends
  // between elements rather than inside text.
  it('does not throw for a selection with no client rects', () => {
    const element = renderPost();

    expect(() => selectAndRelease(element, [])).not.toThrow();
  });
});
