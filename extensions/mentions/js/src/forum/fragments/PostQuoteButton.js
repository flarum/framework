import app from 'flarum/forum/app';
import Fragment from 'flarum/common/Fragment';
import Icon from 'flarum/common/components/Icon';

import reply from '../utils/reply';

/**
 * The space kept between the button and the edges of the viewport.
 */
const VIEWPORT_MARGIN = 10;

/**
 * Fallback clearance for a native menu when the button cannot fit on the
 * selected line without covering its text.
 */
const TOUCH_GAP = 70;
const TOUCH_HANDLE_GAP = 16;

/**
 * The bottom edge of the header that stays fixed over the top of the page
 * (on a phone, the navigation bar), or 0 when there is none.
 */
function fixedHeaderBottom() {
  return $('#header, #app-navigation')
    .toArray()
    .filter((header) => getComputedStyle(header).position === 'fixed')
    .reduce((bottom, header) => Math.max(bottom, header.getBoundingClientRect().bottom), 0);
}

export default class PostQuoteButton extends Fragment {
  constructor(post) {
    super();

    this.post = post;
    this.hideHandler = this.hide.bind(this);
  }

  view() {
    return (
      <button
        className="Button PostQuoteButton"
        type="button"
        onpointerdown={(event) => {
          // Preserve the selection and prevent mouseup from hiding the button
          // before the touch-generated click can activate it.
          if (event.button === 0) event.preventDefault();
        }}
        onclick={() => {
          this.hide();
          // If the user is not logged in, the promise rejects, and a login modal shows up.
          // Since that's already handled, we dont need to show an error message in the console.
          reply(this.post, this.content).catch(() => {});
        }}
      >
        <Icon name="fas fa-quote-left" className="Button-icon" />
        {app.translator.trans('flarum-mentions.forum.post.quote_button')}
      </button>
    );
  }

  show(left, top) {
    const $this = this.$().show();
    const parentOffset = $this.offsetParent().offset();

    // A selection can start or end close to the edge of the screen, which
    // would leave the button partly off it. Keep it inside the viewport.
    const maxLeft = document.documentElement.clientWidth - $this.outerWidth() - VIEWPORT_MARGIN;
    left = Math.max(VIEWPORT_MARGIN, Math.min(left, maxLeft));

    $this.css('left', left - parentOffset.left).css('top', top - parentOffset.top);

    // Showing the button again without a click in between (as dragging a
    // selection handle does) must not add another listener.
    $(document).off('mouseup', this.hideHandler).on('mouseup', this.hideHandler);
  }

  showStart(left, top) {
    const $this = this.$();

    this.show(left, $(window).scrollTop() + top - $this.outerHeight() - 5);
  }

  showEnd(right, bottom) {
    const $this = this.$();

    this.show(right - $this.outerWidth(), $(window).scrollTop() + bottom + 5);
  }

  /**
   * Show the button beside the selected line, clear of its end handle.
   * A single line can also use the space just below its middle. Otherwise,
   * keep the larger clearance above or below for the native menu.
   *
   * A long selection, which can run past the edges of the screen, leaves
   * room for neither. The button then goes in the middle of the screen,
   * away from the handles and text menu at the selection's ends. Pinning
   * it to the top of the screen put it under the header, where a tap
   * landed on the header instead.
   *
   * @param {DOMRect} firstRect The client rect of the selection's first line.
   * @param {DOMRect} lastRect The client rect of the selection's last line.
   * @param {ReadonlyArray<DOMRect>} [rects] Complete selection rectangles for side placement.
   */
  showTouch(firstRect, lastRect, rects = []) {
    const $this = this.$();
    const height = $this.outerHeight();
    const viewport = window.visualViewport;
    const viewportTop = viewport ? viewport.offsetTop : 0;
    const viewportBottom = viewport ? viewport.offsetTop + viewport.height : window.innerHeight;
    const minTop = Math.max(viewportTop, fixedHeaderBottom()) + VIEWPORT_MARGIN;
    const maxTop = viewportBottom - height - VIEWPORT_MARGIN;
    const fits = (top) => top >= minTop && top <= maxTop;

    const width = $this.outerWidth();
    const inlineTop = lastRect.top + (lastRect.bottom - lastRect.top - height) / 2;
    const viewportLeft = viewport ? viewport.offsetLeft : 0;
    const viewportRight = viewportLeft + (viewport ? viewport.width : document.documentElement.clientWidth);
    const minLeft = viewportLeft + VIEWPORT_MARGIN;
    const maxLeft = viewportRight - width - VIEWPORT_MARGIN;
    // Keep the whole button clear of every selected fragment it passes beside.
    let lineLeft = lastRect.left;
    let lineRight = lastRect.right;
    for (const rect of rects) {
      if (rect.top < inlineTop + height && rect.bottom > inlineTop) {
        lineLeft = Math.min(lineLeft, rect.left);
        lineRight = Math.max(lineRight, rect.right);
      }
    }
    const candidates = [lineRight + TOUCH_HANDLE_GAP, lineLeft - width - TOUCH_HANDLE_GAP];
    const inlineLeft = candidates.find((left) => left >= minLeft && left <= maxLeft);

    // Preserve off-text placement for callers without complete line geometry.
    if (rects.length && fits(inlineTop) && inlineLeft !== undefined) {
      this.show(inlineLeft, $(window).scrollTop() + inlineTop);
      return;
    }

    // A single selected line leaves space below its middle, away from both
    // endpoints. Either edge of an endpoint rectangle can hold a bidi handle;
    // keep the larger clearance when either possible edge is too close.
    const singleLine = rects.length && rects.every((rect) => rect.top < lastRect.bottom && rect.bottom > lastRect.top);
    const closeBelow = Math.max(...rects.map((rect) => rect.bottom)) + 12;
    const centeredLeft = Math.max(minLeft, Math.min((lineLeft + lineRight - width) / 2, maxLeft));
    if (
      singleLine &&
      firstRect.top - viewportTop >= TOUCH_GAP &&
      fits(closeBelow) &&
      [firstRect.left, firstRect.right, lastRect.left, lastRect.right].every(
        (edge) => edge <= centeredLeft - 2 * TOUCH_HANDLE_GAP || edge >= centeredLeft + width + 2 * TOUCH_HANDLE_GAP
      )
    ) {
      this.show(centeredLeft, $(window).scrollTop() + closeBelow);
      return;
    }

    const below = lastRect.bottom + TOUCH_GAP;
    const above = firstRect.top - TOUCH_GAP - height;
    let top;

    if (fits(below)) {
      top = below;
    } else if (fits(above)) {
      top = above;
    } else {
      top = Math.max(minTop, (minTop + maxTop) / 2);
    }

    const left = Math.max(minLeft, Math.min(lastRect.right - width, maxLeft));
    this.show(left, $(window).scrollTop() + top);
  }

  hide() {
    this.$().hide();
    $(document).off('mouseup', this.hideHandler);
  }
}
