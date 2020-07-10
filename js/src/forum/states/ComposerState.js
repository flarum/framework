import subclassOf from '../../common/utils/subclassOf';
import ReplyComposer from '../components/ReplyComposer';

class ComposerState {
  constructor() {
    /**
     * The composer's current position.
     *
     * @type {ComposerState.Position}
     */
    this.position = ComposerState.Position.HIDDEN;

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

    /**
     * The dynamic component being shown inside the composer.
     *
     * @type {Object}
     */
    this.body = { attrs: {} };

    /**
     * The DOM node containing a text editor within the composer, if any.
     *
     * @type {jQuery|null}
     */
    this.$texteditor = null;

    this.clear();

    /**
     * @deprecated BC layer, remove in Beta 15.
     */
    this.component = this;
    this.editor = this;
  }

  /**
   * Load a content component into the composer.
   *
   * @param {ComposerBody} componentClass
   * @public
   */
  load(componentClass, attrs) {
    const body = { componentClass, attrs };

    if (this.preventExit()) return;

    // If we load a similar component into the composer, then Mithril will be
    // able to diff the old/new contents and some DOM-related state from the
    // old composer will remain. To prevent this from happening, we clear the
    // component and force a redraw, so that the new component will be working
    // on a blank slate.
    if (this.body.componentClass) {
      this.clear();
      m.redraw(true);
    }

    this.body = body;
  }

  /**
   * Clear the composer's content component.
   */
  clear() {
    this.body = { attrs: {} };
    this.$texteditor = null;

    this.fields = {
      content: m.prop(''),
    };

    /**
     * @deprecated BC layer, remove in Beta 15.
     */
    this.content = this.fields.content;
    this.value = this.fields.content;
  }

  /**
   * Show the composer.
   *
   * @public
   */
  show() {
    if (this.position === ComposerState.Position.NORMAL || this.position === ComposerState.Position.FULLSCREEN) return;

    this.position = ComposerState.Position.NORMAL;
    m.redraw();
  }

  /**
   * Close the composer.
   *
   * @public
   */
  hide() {
    this.position = ComposerState.Position.HIDDEN;
    this.clear();
    m.redraw();
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
    if (this.position === ComposerState.Position.HIDDEN) return;

    this.position = ComposerState.Position.MINIMIZED;
    m.redraw();
  }

  /**
   * Take the composer into fullscreen mode. Has no effect if the composer is
   * hidden.
   *
   * @public
   */
  fullScreen() {
    if (this.position === ComposerState.Position.HIDDEN) return;

    this.position = ComposerState.Position.FULLSCREEN;
    m.redraw();
  }

  /**
   * Exit fullscreen mode.
   *
   * @public
   */
  exitFullScreen() {
    if (this.position === ComposerState.Position.FULLSCREEN) {
      this.position = ComposerState.Position.NORMAL;
      m.redraw();
    }
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
    if (!subclassOf(this.body.componentClass, type)) return false;

    // Now that the type is known to be correct, we loop through the provided
    // data to see whether it matches the data in the attributes for the body.
    return Object.keys(data).every((key) => this.body.attrs[key] === data[key]);
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
    return this.position === ComposerState.Position.FULLSCREEN || this.onMobile();
  }

  /**
   * Determine whether we are on mobile.
   *
   * @return {Boolean}
   * @public
   */
  onMobile() {
    // 767 is the mobile screen cutoff defined in the less variables file
    return window.innerWidth <= 767;
  }

  /**
   * Check whether or not the user is currently composing a reply to a
   * discussion.
   *
   * @param {Discussion} discussion
   * @return {Boolean}
   */
  composingReplyTo(discussion) {
    return this.bodyMatches(ReplyComposer, { discussion }) && this.position !== ComposerState.Position.HIDDEN;
  }

  /**
   * Confirm with the user that they want to close the composer and lose their
   * content.
   *
   * @return {Boolean} Whether or not the exit was cancelled.
   */
  preventExit() {
    if (this.body.componentClass) {
      const preventExit = this.bodyPreventExit();

      if (preventExit) {
        return !confirm(preventExit);
      }
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
    if (this.position === ComposerState.Position.MINIMIZED) {
      return '';
    } else if (this.position === ComposerState.Position.FULLSCREEN) {
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

ComposerState.Position = {
  HIDDEN: 'hidden',
  NORMAL: 'normal',
  MINIMIZED: 'minimized',
  FULLSCREEN: 'fullScreen',
};

export default ComposerState;
