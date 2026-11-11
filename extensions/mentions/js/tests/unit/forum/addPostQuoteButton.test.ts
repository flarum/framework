import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import { jest } from '@jest/globals';
import $ from 'jquery';
import addPostQuoteButton from '../../../src/forum/addPostQuoteButton';

const originalOncreate = CommentPost.prototype.oncreate;
const originalOnremove = CommentPost.prototype.onremove;
let renderedPost: any;

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
  CommentPost.prototype.onremove = function () {};
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
  CommentPost.prototype.onremove = originalOnremove;
});

beforeEach(() => {
  jest.useFakeTimers();
  document.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight' }));
});

afterEach(() => {
  if (renderedPost) CommentPost.prototype.onremove.call(renderedPost);
  renderedPost = undefined;
  jest.useRealTimers();
  // @ts-ignore jsdom has no getClientRects on a range; each test sets its own.
  delete Range.prototype.getClientRects;
});

/** A rendered post that the quote button has been added to. */
function renderPost(transformed = false): HTMLElement {
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

  document.body.innerHTML = '<article class="Post"><div class="Post-body"><p>Some text to quote</p></div></article><p class="Elsewhere">Other text</p>';
  if (transformed) {
    const item = document.createElement('div');
    item.className = 'PostStream-item';
    item.style.transform = 'scale(1.02)';
    document.querySelector('.Post')!.before(item);
    item.appendChild(document.querySelector('.Post')!);
  }

  const element = document.querySelector<HTMLElement>('.Post')!;

  renderedPost = { attrs: { post }, $: (selector?: string) => (selector ? $(element).find(selector) : $(element)) };
  CommentPost.prototype.oncreate.call(renderedPost);

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
  it('waits for mouse release on a touch-capable device', () => {
    const element = renderPost();
    const pointerdown = new Event('pointerdown', { bubbles: true });
    Object.defineProperty(pointerdown, 'pointerType', { value: 'mouse' });
    document.dispatchEvent(pointerdown);

    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);

    expect(document.querySelector('.PostQuoteButton')).toBeNull();

    $(element).trigger($.Event('mouseup', { clientX: 200, clientY: 330 }));
    jest.advanceTimersByTime(1);

    expect(quoteButton().style.top).toBe('325px');

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', shiftKey: true }));
    selectByTouch(element, [{ left: 40, right: 200, top: 400, bottom: 420 }]);
    expect(quoteButton().style.top).toBe('490px');

    document.dispatchEvent(pointerdown);
    const touch = new Event('pointerdown', { bubbles: true });
    Object.defineProperty(touch, 'pointerType', { value: 'touch' });
    document.dispatchEvent(touch);
    selectByTouch(element, [{ left: 40, right: 200, top: 500, bottom: 520 }]);
    expect(quoteButton().style.top).toBe('590px');
  });

  it('protects a primary quote press and hides the button after activation', () => {
    const element = renderPost();
    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);
    const button = quoteButton();

    for (const buttonNumber of [1, 2]) {
      const secondaryPress = new MouseEvent('pointerdown', { bubbles: true, cancelable: true, button: buttonNumber });
      button.dispatchEvent(secondaryPress);
      expect(secondaryPress.defaultPrevented).toBe(false);
    }

    const press = new MouseEvent('pointerdown', { bubbles: true, cancelable: true, button: 0 });
    button.dispatchEvent(press);
    expect(press.defaultPrevented).toBe(true);

    button.click();
    expect(button.style.display).toBe('none');
    expect(($ as any)._data(document, 'events')?.mouseup).toHaveLength(1);
  });

  it('shows a mouse selection when it is released outside the post', () => {
    const element = renderPost();
    const pointerdown = new Event('pointerdown', { bubbles: true });
    Object.defineProperty(pointerdown, 'pointerType', { value: 'mouse' });
    element.dispatchEvent(pointerdown);
    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);

    document.dispatchEvent(new MouseEvent('mouseup', { clientX: 250, clientY: 330 }));
    jest.advanceTimersByTime(1);

    expect(quoteButton()?.style.top).toBe('325px');
  });

  it('keeps the quote overlay outside transformed posts and removes it with the post', () => {
    const element = renderPost(true);
    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);

    expect(quoteButton().closest('.PostStream-item')).toBeNull();
    const container = quoteButton().parentElement!;

    CommentPost.prototype.onremove.call(renderedPost);
    expect(($ as any)._data(document, 'events')?.mouseup).toBeUndefined();
    expect(document.querySelector('.PostQuoteButton')).toBeNull();

    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);
    document.dispatchEvent(new MouseEvent('mouseup', { clientX: 200, clientY: 330 }));
    jest.advanceTimersByTime(1);
    expect(document.querySelector('.PostQuoteButton')).toBeNull();
    expect(container.querySelector('.PostQuoteButton')).toBeNull();
  });

  it('keeps using mouse positioning when a modifier is pressed during a drag', () => {
    const element = renderPost();
    const pointerdown = new Event('pointerdown', { bubbles: true });
    Object.defineProperty(pointerdown, 'pointerType', { value: 'mouse' });
    element.dispatchEvent(pointerdown);

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Shift', shiftKey: true }));
    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);
    expect(document.querySelector('.PostQuoteButton')).toBeNull();

    document.dispatchEvent(new MouseEvent('mouseup', { clientX: 200, clientY: 330 }));
    jest.advanceTimersByTime(1);
    expect(quoteButton().style.top).toBe('325px');
  });

  it('dismisses Quote after an unrelated control release', () => {
    const element = renderPost();
    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);
    const button = quoteButton();
    const pointerdown = new Event('pointerdown', { bubbles: true });
    Object.defineProperty(pointerdown, 'pointerType', { value: 'mouse' });
    document.querySelector('.Elsewhere')!.dispatchEvent(pointerdown);

    document.dispatchEvent(new MouseEvent('mouseup', { clientX: 200, clientY: 330 }));
    jest.advanceTimersByTime(1);

    expect(button.style.display).toBe('none');
  });

  it('does not re-place a touch selection on compatibility mouseup', () => {
    const element = renderPost();
    const pointerdown = new Event('pointerdown', { bubbles: true });
    Object.defineProperty(pointerdown, 'pointerType', { value: 'touch' });
    element.dispatchEvent(pointerdown);
    selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);
    const button = quoteButton();

    document.dispatchEvent(new MouseEvent('mouseup', { clientX: 200, clientY: 330 }));
    jest.advanceTimersByTime(1);

    expect(button.style.top).toBe('390px');
    expect(button.style.display).toBe('none');
  });

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

  // A triple click selects up to the start of the next paragraph, which
  // browsers report as an empty rectangle at the left edge of that line.
  it('ignores the empty rectangle of a selection ending at the start of a line', () => {
    const element = renderPost();

    selectAndRelease(
      element,
      [
        { left: 15, right: 300, top: 60, bottom: 80, width: 285 },
        { left: 15, right: 15, top: 100, bottom: 120, width: 0 },
      ],
      { clientX: 300, clientY: 70 }
    );

    expect(quoteButton().style.left).toBe(`${300 - BUTTON_WIDTH}px`);
    expect(quoteButton().style.top).toBe(`${80 + 5}px`);
  });

  // Selecting text elsewhere by touch fires no click to hide the button.
  it('hides when text elsewhere is selected by touch', () => {
    const element = renderPost();

    selectByTouch(element, [{ left: 40, right: 200, top: 60, bottom: 80 }]);
    expect(quoteButton().style.display).not.toBe('none');

    const range = document.createRange();
    range.selectNodeContents(document.querySelector('.Elsewhere')!);
    window.getSelection()!.removeAllRanges();
    window.getSelection()!.addRange(range);
    document.dispatchEvent(new Event('selectionchange'));
    jest.advanceTimersByTime(1);

    expect(quoteButton().style.display).toBe('none');
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

  // A long selection runs past the screen's edges, where the browser draws
  // its handles and text menu. The middle of the screen is clear of both.
  it('shows in the middle of the screen for a touch selection taller than it', () => {
    const element = renderPost();

    selectByTouch(element, [
      { left: 15, right: 360, top: -200, bottom: -180 },
      { left: 15, right: 200, top: 900, bottom: 920 },
    ]);

    expect(quoteButton().style.top).toBe(`${(10 + (VIEWPORT_HEIGHT - BUTTON_HEIGHT - 10)) / 2}px`);
  });

  // On a phone the navigation bar stays fixed over the top of the page. A
  // button under it can't be tapped: the tap lands on the bar instead.
  it('keeps clear of a fixed navigation bar at the top of the screen', () => {
    const element = renderPost();
    const navigation = $('<div id="app-navigation" style="position: fixed"></div>').prependTo(document.body)[0];
    navigation.getBoundingClientRect = () => ({ top: 0, bottom: 46 }) as DOMRect;

    selectByTouch(element, [
      { left: 15, right: 360, top: 130, bottom: 150 },
      { left: 15, right: 200, top: 620, bottom: 640 },
    ]);

    expect(parseFloat(quoteButton().style.top)).toBeGreaterThanOrEqual(46 + 10);
  });

  it('shows above a touch selection, below a fixed navigation bar, when there is room', () => {
    const element = renderPost();
    const navigation = $('<div id="app-navigation" style="position: fixed"></div>').prependTo(document.body)[0];
    navigation.getBoundingClientRect = () => ({ top: 0, bottom: 46 }) as DOMRect;

    selectByTouch(element, [
      { left: 15, right: 360, top: 200, bottom: 220 },
      { left: 15, right: 200, top: 620, bottom: 640 },
    ]);

    expect(quoteButton().style.top).toBe(`${200 - 70 - BUTTON_HEIGHT}px`);
  });

  // Core's composer resize and post scrubber drag end on a document mouseup.
  it('leaves document mouseup handlers registered elsewhere alone', () => {
    const element = renderPost();
    const otherHandler = jest.fn();
    $(document).on('mouseup', otherHandler);

    selectByTouch(element, [{ left: 40, right: 200, top: 60, bottom: 80 }]);
    $(document).trigger('mouseup');
    $(document).trigger('mouseup');

    expect(otherHandler).toHaveBeenCalledTimes(2);
    $(document).off('mouseup', otherHandler);
  });

  // Dragging a selection handle fires selectionchange over and over, showing
  // the button again each time without a click in between.
  it('stops listening for clicks once hidden, however many times it was shown', () => {
    const element = renderPost();

    selectByTouch(element, [{ left: 40, right: 200, top: 60, bottom: 80 }]);
    selectByTouch(element, [{ left: 40, right: 250, top: 60, bottom: 80 }]);
    $(document).trigger('mouseup');

    expect(quoteButton().style.display).toBe('none');
    expect(($ as any)._data(document, 'events')?.mouseup).toHaveLength(1);
  });

  // Browsers can report no rectangles for a selection, e.g. one that ends
  // between elements rather than inside text.
  it('does not throw for a selection with no client rects', () => {
    const element = renderPost();

    expect(() => selectAndRelease(element, [])).not.toThrow();
  });
});
