import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import PostComponent from 'flarum/forum/components/Post';
import Component from 'flarum/common/Component';
import { extend } from 'flarum/common/extend';
import { jest } from '@jest/globals';
import $ from 'jquery';
import addPostQuoteButton from '../../../src/forum/addPostQuoteButton';
import PostQuoteButton from '../../../src/forum/fragments/PostQuoteButton';

const originalOncreate = CommentPost.prototype.oncreate;
const originalOnremove = Object.getOwnPropertyDescriptor(CommentPost.prototype, 'onremove');
const originalComponentOnremove = Object.getOwnPropertyDescriptor(Component.prototype, 'onremove')!;
let renderedPost: any;
const originalVisualViewport = Object.getOwnPropertyDescriptor(window, 'visualViewport');

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
  if (originalOnremove) Object.defineProperty(CommentPost.prototype, 'onremove', originalOnremove);
  else delete (CommentPost.prototype as any).onremove;
  Object.defineProperty(Component.prototype, 'onremove', originalComponentOnremove);
});

beforeEach(() => {
  jest.useFakeTimers();
  document.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight' }));
});

afterEach(() => {
  if (renderedPost) renderedPost.onremove();
  renderedPost = undefined;
  jest.useRealTimers();
  // @ts-ignore jsdom has no getClientRects on a range; each test sets its own.
  delete Range.prototype.getClientRects;
  if (originalVisualViewport) Object.defineProperty(window, 'visualViewport', originalVisualViewport);
  else delete (window as any).visualViewport;
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

  renderedPost = Object.assign(Object.create(CommentPost.prototype), {
    attrs: { post },
    $: (selector?: string) => (selector ? $(element).find(selector) : $(element)),
  });
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
    const keyboardTop = quoteButton().style.top;
    expect(keyboardTop).not.toBe('325px');

    document.dispatchEvent(pointerdown);
    const touch = new Event('pointerdown', { bubbles: true });
    Object.defineProperty(touch, 'pointerType', { value: 'touch' });
    document.dispatchEvent(touch);
    selectByTouch(element, [{ left: 40, right: 200, top: 500, bottom: 520 }]);
    expect(quoteButton().style.top).not.toBe(keyboardTop);
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

    renderedPost.onremove();
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
    const top = button.style.top;

    document.dispatchEvent(new MouseEvent('mouseup', { clientX: 200, clientY: 330 }));
    jest.advanceTimersByTime(1);

    expect(button.style.top).toBe(top);
    expect(button.style.display).toBe('none');
  });

  it('cleans up when a parent hook was bound before mentions initialized', () => {
    const parentOnremove = Object.getOwnPropertyDescriptor(PostComponent.prototype, 'onremove');
    PostComponent.prototype.onremove = function () {
      originalComponentOnremove.value.call(this);
    };

    try {
      const element = renderPost();
      selectByTouch(element, [{ left: 40, right: 200, top: 300, bottom: 320 }]);
      renderedPost.onremove();
      expect(document.querySelector('.PostQuoteButton')).toBeNull();
      expect(($ as any)._data(document, 'events')?.mouseup).toBeUndefined();
    } finally {
      if (parentOnremove) Object.defineProperty(PostComponent.prototype, 'onremove', parentOnremove);
      else delete (PostComponent.prototype as any).onremove;
    }
  });

  it('preserves parent lifecycle extensions registered after mentions', () => {
    const element = renderPost();
    const parentOnremove = Object.getOwnPropertyDescriptor(PostComponent.prototype, 'onremove');
    extend(PostComponent.prototype, 'onremove', function () {
      this.$().attr('data-parent-cleanup', 'done');
    });

    try {
      renderedPost.onremove();
      expect(element.getAttribute('data-parent-cleanup')).toBe('done');
    } finally {
      if (parentOnremove) Object.defineProperty(PostComponent.prototype, 'onremove', parentOnremove);
      else delete (PostComponent.prototype as any).onremove;
    }
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

  it('sits beside a touch selection with room for the end handle', () => {
    const element = renderPost();

    selectByTouch(element, [
      { left: 15, right: 360, top: 280, bottom: 300 },
      { left: 0, right: 30, top: 320, bottom: 340 },
    ]);

    const top = parseFloat(quoteButton().style.top);
    expect(top).toBeLessThan(340);
    expect(top + BUTTON_HEIGHT).toBeGreaterThan(320);
    expect(parseFloat(quoteButton().style.left)).toBeGreaterThanOrEqual(46);
    expect(parseFloat(quoteButton().style.left)).toBeLessThanOrEqual(54);
  });

  it('keeps the entire touch button outside adjacent selected lines', () => {
    const element = renderPost();
    selectByTouch(element, [
      { left: 15, right: 360, top: 300, bottom: 320 },
      { left: 0, right: 30, top: 320, bottom: 340 },
    ]);

    expect(parseFloat(quoteButton().style.top)).toBeGreaterThanOrEqual(410);
  });

  it('uses the other side of a short selection near the right screen edge', () => {
    const element = renderPost();
    selectByTouch(element, [{ left: 300, right: 360, top: 300, bottom: 320 }]);

    const left = parseFloat(quoteButton().style.left);
    const top = parseFloat(quoteButton().style.top);
    expect(left).toBeGreaterThanOrEqual(10);
    expect(left + BUTTON_WIDTH).toBeLessThanOrEqual(284);
    expect(top).toBeLessThan(320);
    expect(top + BUTTON_HEIGHT).toBeGreaterThan(300);
  });

  it('keeps a full-line touch quote close below the highlight and between its endpoints', () => {
    const element = renderPost();
    selectByTouch(element, [{ left: 15, right: 360, top: 300, bottom: 320 }]);

    const left = parseFloat(quoteButton().style.left);
    const top = parseFloat(quoteButton().style.top);
    expect(left).toBeGreaterThanOrEqual(100);
    expect(left + BUTTON_WIDTH).toBeLessThanOrEqual(275);
    expect(top).toBeGreaterThanOrEqual(328);
    expect(top).toBeLessThanOrEqual(336);
  });

  it('retains menu clearance for a full line near the visual viewport top', () => {
    const element = renderPost();
    selectByTouch(element, [{ left: 15, right: 360, top: 40, bottom: 60 }]);

    expect(quoteButton().style.top).toBe('130px');
  });

  it('retains clearance when either edge of a directional run could be an endpoint', () => {
    const element = renderPost();
    selectByTouch(element, [
      { left: 15, right: 200, top: 300, bottom: 320 },
      { left: 200, right: 360, top: 300, bottom: 320 },
    ]);

    expect(quoteButton().style.top).toBe('390px');
  });

  it('retains the fallback when a centered quote would crowd the endpoints', () => {
    const element = renderPost();
    selectByTouch(element, [{ left: 110, right: 260, top: 300, bottom: 320 }]);

    expect(quoteButton().style.top).toBe('390px');
  });

  it('places a full-line quote above when the close-below position is off-screen', () => {
    const element = renderPost();
    selectByTouch(element, [{ left: 15, right: 360, top: 650, bottom: 670 }]);

    expect(quoteButton().style.top).toBe('544px');
  });

  it('clears both endpoints when inline formatting splits the selected line', () => {
    const element = renderPost();
    selectByTouch(element, [
      { left: 170, right: 200, top: 300, bottom: 320 },
      { left: 200, right: 260, top: 300, bottom: 320 },
    ]);

    const left = parseFloat(quoteButton().style.left);
    const top = parseFloat(quoteButton().style.top);
    expect(left + BUTTON_WIDTH).toBeLessThanOrEqual(154);
    expect(top).toBeLessThan(320);
    expect(top + BUTTON_HEIGHT).toBeGreaterThan(300);
  });

  it('keeps touch quotes inside the panned visual viewport after zooming', () => {
    const element = renderPost();
    Object.defineProperty(window, 'visualViewport', {
      configurable: true,
      value: { offsetLeft: 80, width: 180, offsetTop: 0, height: 700 },
    });
    selectByTouch(element, [{ left: 180, right: 240, top: 300, bottom: 320 }]);

    const left = parseFloat(quoteButton().style.left);
    expect(left).toBeGreaterThanOrEqual(90);
    expect(left + BUTTON_WIDTH).toBeLessThanOrEqual(250);
  });

  it('does not cover an earlier selected fragment on the last line', () => {
    const element = renderPost();
    selectByTouch(element, [
      { left: 170, right: 300, top: 300, bottom: 320 },
      { left: 15, right: 200, top: 400, bottom: 420 },
      { left: 200, right: 260, top: 400, bottom: 420 },
    ]);

    expect(parseFloat(quoteButton().style.top)).toBeGreaterThanOrEqual(480);
  });

  it('retains the off-text fallback for callers without complete line rectangles', () => {
    renderPost();
    const container = document.createElement('div');
    document.body.appendChild(container);
    const button = new PostQuoteButton(app.store.getById<Post>('posts', '1')!);
    m.render(container, button.render());

    try {
      button.showTouch(
        { left: 15, right: 360, top: 300, bottom: 320 } as DOMRect,
        { left: 0, right: 30, top: 320, bottom: 340 } as DOMRect
      );
      expect((container.firstElementChild as HTMLElement).style.top).toBe('410px');
    } finally {
      button.hide();
      m.render(container, null);
      container.remove();
    }
  });

  it('shows above a selection made by touch when there is no room below it', () => {
    const element = renderPost();

    selectByTouch(element, [
      { left: 15, right: 360, top: 580, bottom: 600 },
      { left: 15, right: 200, top: 700, bottom: 720 },
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
      { left: 15, right: 200, top: 700, bottom: 720 },
    ]);

    expect(parseFloat(quoteButton().style.top)).toBeGreaterThanOrEqual(46 + 10);
  });

  it('rejects an inline touch position underneath the fixed navigation bar', () => {
    const element = renderPost();
    const navigation = $('<div id="app-navigation" style="position: fixed"></div>').prependTo(document.body)[0];
    navigation.getBoundingClientRect = () => ({ top: 0, bottom: 46 }) as DOMRect;

    selectByTouch(element, [
      { left: 15, right: 200, top: 20, bottom: 40 },
      { left: 15, right: 200, top: 40, bottom: 60 },
    ]);

    expect(parseFloat(quoteButton().style.top)).toBeGreaterThanOrEqual(56);
  });

  it('shows above a touch selection, below a fixed navigation bar, when there is room', () => {
    const element = renderPost();
    const navigation = $('<div id="app-navigation" style="position: fixed"></div>').prependTo(document.body)[0];
    navigation.getBoundingClientRect = () => ({ top: 0, bottom: 46 }) as DOMRect;

    selectByTouch(element, [
      { left: 15, right: 360, top: 200, bottom: 220 },
      { left: 15, right: 200, top: 700, bottom: 720 },
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

  // Chrome ends a triple click on a post's last paragraph at the next place a
  // selection can start, past the post's controls, whose boxes are no place
  // for the button.
  it('shows the button by the post text of a triple click that runs past the body', () => {
    const element = renderPost();
    element.insertAdjacentHTML('beforeend', '<aside class="Post-actions"><button style="user-select: none">Reply</button></aside>');
    element.insertAdjacentHTML('afterend', '<div class="ReplyPlaceholder"><div class="Post-side"></div></div>');
    const body = element.querySelector('.Post-body')!;

    // @ts-ignore jsdom has no getClientRects on a range.
    Range.prototype.getClientRects = function (this: Range) {
      const text = { left: 40, right: 200, top: 300, bottom: 320 };

      return body.contains(this.endContainer) ? [text] : [text, { left: 40, right: 120, top: 400, bottom: 420 }];
    };

    const range = document.createRange();
    range.setStart(element.querySelector('p')!.firstChild!, 0);
    range.setEnd(document.querySelector('.ReplyPlaceholder .Post-side')!, 0);
    window.getSelection()!.removeAllRanges();
    window.getSelection()!.addRange(range);

    $(element).trigger($.Event('mouseup', { clientX: 200, clientY: 330 }));
    jest.advanceTimersByTime(1);

    expect(quoteButton().style.display).not.toBe('none');
    expect(quoteButton().style.top).toBe('325px');
  });

  // Browsers can report no rectangles for a selection, e.g. one that ends
  // between elements rather than inside text.
  it('does not throw for a selection with no client rects', () => {
    const element = renderPost();

    expect(() => selectAndRelease(element, [])).not.toThrow();
  });
});
