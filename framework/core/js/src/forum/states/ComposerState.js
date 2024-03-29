import app from '../../forum/app';
import subclassOf from '../../common/utils/subclassOf';
import Stream from '../../common/utils/Stream';
import Component from '../../common/Component';

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
     * @type {number}
     */
    this.height = null;

    /**
     * The dynamic component being shown inside the composer.
     *
     * @type {Object}
     */
    this.body = { attrs: {} };

    /**
     * A reference to the text editor that allows text manipulation.
     *
     * @type {import('../../common/utils/EditorDriverInterface')|null}
     */
    this.editor = null;

    /**
     * If the composer was loaded and mounted.
     *
     * @type {boolean}
     */
    this.mounted = false;

    this.clear();
  }

  /**
   * Load a content component into the composer.
   *
   * @param {() => Promise<any & { default: typeof import('../components/ComposerBody') }> | typeof import('../components/ComposerBody').default} componentClass
   * @param {object} attrs
   */
  async load(componentClass, attrs) {
    if (!(componentClass.prototype instanceof Component)) {
      componentClass = (await componentClass()).default;
    }

    const body = { componentClass, attrs };

    if (this.preventExit()) return;

    // If we load a similar component into the composer, then Mithril will be
    // able to diff the old/new contents and some DOM-related state from the
    // old composer will remain. To prevent this from happening, we clear the
    // component and force a redraw, so that the new component will be working
    // on a blank slate.
    if (this.isVisible()) {
      this.clear();
      m.redraw.sync();
    }

    this.body = body;
  }

  /**
   * Clear the composer's content component.
   */
  clear() {
    this.position = ComposerState.Position.HIDDEN;
    this.body = { attrs: {} };
    this.onExit = null;

    this.fields = {
      content: Stream(''),
    };

    if (this.editor) {
      this.editor.destroy();
    }
    this.editor = null;
  }

  /**
   * Show the composer.
   */
  async show() {
    if (!this.mounted) {
      const Composer = (await import('../components/Composer')).default;
      m.mount(document.getElementById('composer'), { view: () => <Composer state={this} /> });
      this.mounted = true;
    }

    if (this.position === ComposerState.Position.NORMAL || this.position === ComposerState.Position.FULLSCREEN) return;

    this.position = ComposerState.Position.NORMAL;
    m.redraw.sync();
  }

  /**
   * Close the composer.
   */
  hide() {
    this.clear();
    m.redraw();
  }

  /**
   * Confirm with the user so they don't lose their content, then close the
   * composer.
   */
  close() {
    if (this.preventExit()) return;

    this.hide();
  }

  /**
   * Minimize the composer. Has no effect if the composer is hidden.
   */
  minimize() {
    if (!this.isVisible()) return;

    this.position = ComposerState.Position.MINIMIZED;
    m.redraw();
  }

  /**
   * Take the composer into fullscreen mode. Has no effect if the composer is
   * hidden.
   */
  fullScreen() {
    if (!this.isVisible()) return;

    this.position = ComposerState.Position.FULLSCREEN;
    m.redraw();
  }

  /**
   * Exit fullscreen mode.
   */
  exitFullScreen() {
    if (this.position !== ComposerState.Position.FULLSCREEN) return;

    this.position = ComposerState.Position.NORMAL;
    m.redraw();
  }

  /**
   * Determine whether the body matches the given component class and data.
   *
   * @param {object} type The component class to check against. Subclasses are accepted as well.
   * @param {object} data
   * @return {boolean}
   */
  bodyMatches(type, data = {}) {
    // Fail early when the body is of a different type
    if (!subclassOf(this.body.componentClass, type)) return false;

    // Now that the type is known to be correct, we loop through the provided
    // data to see whether it matches the data in the attributes for the body.
    return Object.keys(data).every((key) => this.body.attrs[key] === data[key]);
  }

  /**
   * Determine whether or not the Composer is visible.
   *
   * True when the composer is displayed on the screen and has a body component.
   * It could be open in "normal" or full-screen mode, or even minimized.
   *
   * @returns {boolean}
   */
  isVisible() {
    return this.position !== ComposerState.Position.HIDDEN;
  }

  /**
   * Determine whether or not the Composer is covering the screen.
   *
   * This will be true if the Composer is in full-screen mode on desktop,
   * or if we are on a mobile device, where we always consider the composer as full-screen..
   *
   * @return {boolean}
   */
  isFullScreen() {
    return this.position === ComposerState.Position.FULLSCREEN || app.screen() === 'phone';
  }

  /**
   * Check whether or not the user is currently composing a reply to a
   * discussion.
   *
   * @param {import('../../common/models/Discussion').default} discussion
   * @return {boolean}
   */
  composingReplyTo(discussion) {
    const ReplyComposer = flarum.reg.checkModule('core', 'forum/components/ReplyComposer');

    if (!ReplyComposer) return false;

    return this.isVisible() && this.bodyMatches(ReplyComposer, { discussion });
  }

  /**
   * Confirm with the user that they want to close the composer and lose their
   * content.
   *
   * @return {boolean} Whether or not the exit was cancelled.
   */
  preventExit() {
    if (!this.isVisible()) return;
    if (!this.onExit) return;

    if (this.onExit.callback()) {
      return !confirm(this.onExit.message);
    }
  }

  /**
   * Configure when / what to ask the user before closing the composer.
   *
   * The provided callback will be used to determine whether asking for
   * confirmation is necessary. If the callback returns true at the time of
   * closing, the provided text will be shown in a standard confirmation dialog.
   *
   * @param {() => boolean} callback
   * @param {string} message
   */
  preventClosingWhen(callback, message) {
    this.onExit = { callback, message };
  }

  /**
   * Minimum height of the Composer.
   * @returns {number}
   */
  minimumHeight() {
    return 200;
  }

  /**
   * Maxmimum height of the Composer.
   * @returns {number}
   */
  maximumHeight() {
    return $(window).height() - $('#header').outerHeight();
  }

  /**
   * Computed the composer's current height, based on the intended height, and
   * the composer's current state. This will be applied to the composer
   * content's DOM element.
   * @returns {number | string}
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
}

ComposerState.Position = {
  HIDDEN: 'hidden',
  NORMAL: 'normal',
  MINIMIZED: 'minimized',
  FULLSCREEN: 'fullScreen',
};

export default ComposerState;
