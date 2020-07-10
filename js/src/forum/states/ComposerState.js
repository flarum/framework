import evented from '../../common/utils/evented';
import subclassOf from '../../common/utils/subclassOf';
import Composer from '../instances/Composer';
import ReplyComposer from '../components/ReplyComposer';

class ComposerState {
  constructor() {
    /**
     * The composer's current position.
     *
     * @type {ComposerState.PositionEnum}
     */
    this.position = ComposerState.PositionEnum.HIDDEN;

    /**
     * The composer's intended height, which can be modified by the user
     * (by dragging the composer handle).
     *
     * @type {Integer}
     */
    this.height = null;

    /**
     * Whether or not the composer currently has focus.
     *
     * @type {Boolean}
     */
    this.active = false;

    this.clear();

    /**
     * @deprecated BC layer, remove in Beta 15.
     */
    this.component = this;
    this.editor = this;
  }

  getBody() {
    return this.body;
  }

  /**
   * Determine whether or not the Composer is covering the screen.
   *
   * This will be true if the Composer is in full-screen mode on desktop,
   * or if we are on a mobile device, where we always consider the composer as full-screen..
   *
   * @return {Boolean}
   * @public
   */
  isFullScreen() {
    // 767 is the mobile screen cutoff defined in the less variables file
    return this.position === ComposerState.PositionEnum.FULLSCREEN || window.innerWidth <= 767;
  }

  /**
   * Determine whether the body matches the given component class and data.
   *
   * @param {object} type The component class to check against. Subclasses are
   *                      accepted as well.
   * @param {object} data
   * @return {boolean}
   */
  bodyMatches(type, data = {}) {
    // Fail early when the page is of a different type
    if (!subclassOf(this.body.getClass(), type)) return false;

    // Now that the type is known to be correct, we loop through the provided
    // data to see whether it matches the data in the attributes for the body.
    return Object.keys(data).every((key) => this.body.getAttrs()[key] === data[key]);
  }

  /**
   * Check whether or not the user is currently composing a reply to a
   * discussion.
   *
   * @param {Discussion} discussion
   * @return {Boolean}
   */
  composingReplyTo(discussion) {
    return this.bodyMatches(ReplyComposer, { discussion }) && this.position !== ComposerState.PositionEnum.HIDDEN;
  }

  /**
   * Confirm with the user that they want to close the composer and lose their
   * content.
   *
   * @return {Boolean} Whether or not the exit was cancelled.
   */
  preventExit() {
    if (this.body.initialized()) {
      const preventExit = this.bodyPreventExit();

      if (preventExit) {
        return !confirm(preventExit);
      }
    }
  }

  /**
   * Load a content component into the composer.
   *
   * @param {ComposerBody} bodyClass
   * @public
   */
  load(bodyClass, bodyAttrs) {
    const body = new Composer(bodyClass, bodyAttrs);

    if (this.preventExit()) return;

    // If we load a similar component into the composer, then Mithril will be
    // able to diff the old/new contents and some DOM-related state from the
    // old composer will remain. To prevent this from happening, we clear the
    // component and force a redraw, so that the new component will be working
    // on a blank slate.
    if (this.body.initialized()) {
      this.clear();
      m.redraw(true);
    }

    this.body = body;
  }

  /**
   * Clear the composer's content component.
   *
   * @public
   */
  clear() {
    this.body = new Composer(null);
    this.fields = {
      content: m.prop(''),
    };

    this.$texteditor = null;

    /**
     * @deprecated BC layer, remove in Beta 15.
     */
    this.content = this.fields.content;
    this.value = this.fields.content;
  }

  focus() {
    this.trigger('focus');
  }

  /**
   * Show the composer.
   *
   * @public
   */
  show() {
    if (this.position === ComposerState.PositionEnum.NORMAL || this.position === ComposerState.PositionEnum.FULLSCREEN) {
      return;
    }

    this.trigger('show');

    if (this.isFullScreen()) {
      this.focus();
    }
  }

  /**
   * Close the composer.
   *
   * @public
   */
  hide() {
    const $composer = $('.js-Composer');

    // Animate the composer sliding down off the bottom edge of the viewport.
    // Only when the animation is completed, update the Composer state flag and
    // other elements on the page.
    $composer.stop(true).animate({ bottom: -$composer.height() }, 'fast', () => {
      this.position = ComposerState.PositionEnum.HIDDEN;
      this.clear();
      m.redraw();

      this.trigger('hide');
    });
  }

  /**
   * Confirm with the user so they don't lose their content, then close the
   * composer.
   *
   * @public
   */
  close() {
    if (!this.preventExit()) {
      this.hide();
    }
  }

  /**
   * Minimize the composer. Has no effect if the composer is hidden.
   *
   * @public
   */
  minimize() {
    if (this.position === ComposerState.PositionEnum.HIDDEN) return;

    this.trigger('minimize');
  }

  /**
   * Take the composer into fullscreen mode. Has no effect if the composer is
   * hidden.
   *
   * @public
   */
  fullScreen() {
    if (this.position !== ComposerState.PositionEnum.HIDDEN) {
      this.position = ComposerState.PositionEnum.FULLSCREEN;
      m.redraw();
      this.trigger('updateHeight');
      this.focus();
    }
  }

  /**
   * Exit fullscreen mode.
   *
   * @public
   */
  exitFullScreen() {
    if (this.position === ComposerState.PositionEnum.FULLSCREEN) {
      this.position = ComposerState.PositionEnum.NORMAL;
      m.redraw();
      this.trigger('updateHeight');
      this.focus();
    }
  }

  /**
   * Minimum height of the Composer.
   * @returns {Integer}
   */
  minimumHeight() {
    return 200;
  }

  /**
   * Maxmimum height of the Composer.
   * @returns {Integer}
   */
  maximumHeight() {
    return $(window).height() - $('#header').outerHeight();
  }

  /**
   * Computed the composer's current height, based on the intended height, and
   * the composer's current state. This will be applied to the composer's
   * content's DOM element.
   * @returns {Integer|String}
   */
  computedHeight() {
    // If the composer is minimized, then we don't want to set a height; we'll
    // let the CSS decide how high it is. If it's fullscreen, then we need to
    // make it as high as the window.
    if (this.position === ComposerState.PositionEnum.MINIMIZED) {
      return '';
    } else if (this.position === ComposerState.PositionEnum.FULLSCREEN) {
      return $(window).height();
    }

    // Otherwise, if it's normal or hidden, then we use the intended height.
    // We don't let the composer get too small or too big, though.
    return Math.max(this.minimumHeight(), Math.min(this.height, this.maximumHeight()));
  }

  /**
   * Set the value of the text editor.
   *
   * @param {String} value
   */
  setValue(value) {
    this.$texteditor.val(value).trigger('input');
  }

  /**
   * Set the selected range of the textarea.
   *
   * @param {Integer} start
   * @param {Integer} end
   */
  setSelectionRange(start, end) {
    const $textarea = this.$texteditor;

    if (!$textarea.length) return;

    $textarea[0].setSelectionRange(start, end);
    $textarea.focus();
  }

  /**
   * Get the selected range of the textarea.
   *
   * @return {Array}
   */
  getSelectionRange() {
    const $textarea = this.$texteditor;

    if (!$textarea.length) return [0, 0];

    return [$textarea[0].selectionStart, $textarea[0].selectionEnd];
  }

  /**
   * Insert content into the textarea at the position of the cursor.
   *
   * @param {String} insert
   */
  insertAtCursor(insert) {
    const textarea = this.$texteditor[0];
    const value = this.fields.content();
    const index = textarea ? textarea.selectionStart : value.length;

    this.setValue(value.slice(0, index) + insert + value.slice(index));

    // Move the textarea cursor to the end of the content we just inserted.
    if (textarea) {
      const pos = index + insert.length;
      this.setSelectionRange(pos, pos);
    }

    textarea.dispatchEvent(new CustomEvent('input', { bubbles: true, cancelable: true }));
  }
}

ComposerState.PositionEnum = {
  HIDDEN: 'hidden',
  NORMAL: 'normal',
  MINIMIZED: 'minimized',
  FULLSCREEN: 'fullScreen',
};

Object.assign(ComposerState.prototype, evented);

export default ComposerState;
