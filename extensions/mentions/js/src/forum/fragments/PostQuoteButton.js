import app from 'flarum/forum/app';
import Fragment from 'flarum/common/Fragment';
import Icon from 'flarum/common/components/Icon';

import reply from '../utils/reply';

/**
 * The space kept between the button and the edges of the viewport.
 */
const VIEWPORT_MARGIN = 10;

/**
 * The space kept between the button and a selection made by touch. Mobile
 * browsers draw their own selection handles and text menu (Copy, Look Up, ...)
 * right next to the selection, on top of the page, and anything beneath them
 * can't be tapped. This clears the handles and a menu shown on the same side.
 */
const TOUCH_GAP = 70;

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
        onclick={() => {
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
   * Show the button for a selection made by touch: well below its end, or
   * well above its start when there is no room below. A selection taller
   * than the screen leaves room for neither, so the button is kept inside
   * the visible part of the page either way.
   *
   * @param {DOMRect} firstRect The client rect of the selection's first line.
   * @param {DOMRect} lastRect The client rect of the selection's last line.
   */
  showTouch(firstRect, lastRect) {
    const $this = this.$();
    const height = $this.outerHeight();
    const viewport = window.visualViewport;
    const viewportTop = viewport ? viewport.offsetTop : 0;
    const viewportBottom = viewport ? viewport.offsetTop + viewport.height : window.innerHeight;
    const maxTop = viewportBottom - height - VIEWPORT_MARGIN;

    let top = lastRect.bottom + TOUCH_GAP;

    if (top > maxTop) {
      top = firstRect.top - TOUCH_GAP - height;
    }

    top = Math.max(viewportTop + VIEWPORT_MARGIN, Math.min(top, maxTop));

    this.show(lastRect.right - $this.outerWidth(), $(window).scrollTop() + top);
  }

  hide() {
    this.$().hide();
    $(document).off('mouseup', this.hideHandler);
  }
}
