import Composer from 'flarum/components/Composer';
import computed from 'flarum/utils/computed';

export default class ComposerHeight {
  constructor() {
    /**
     * The composer's intended height, which can be modified by the user
     * (by dragging the composer handle).
     *
     * @type {Integer}
     */
    this.height = null;

    /**
     * Temporary copy of the position when calling `computedHeight` so we can use the `computed` utility with both variables
     * @type {null}
     */
    this.position = null;

    /**
     * Hidden computed function in order to use a position parameter in computedHeight()
     * @returns {Integer|String}
     */
    this.computedHeightWithPosition = computed('height', 'position', (height, position) => {
      console.log('computing');
      // If the composer is minimized, then we don't want to set a height; we'll
      // let the CSS decide how high it is. If it's fullscreen, then we need to
      // make it as high as the window.
      if (position === Composer.PositionEnum.MINIMIZED) {
        return '';
      } else if (position === Composer.PositionEnum.FULLSCREEN) {
        return $(window).height();
      }

      // Otherwise, if it's normal or hidden, then we use the intended height.
      // We don't let the composer get too small or too big, though.
      return Math.max(this.minimumHeight(), Math.min(height, this.maximumHeight()));
    });
  }

  /**
   * Initialize default Composer height.
   * This method should be run after the DOM has been created.
   */
  init() {
    this.height = localStorage.getItem('composerHeight');

    if (!this.height) {
      this.height = this.defaultHeight();
    }
  }

  /**
   * Default height of the Composer in case none is saved.
   * @returns {Integer}
   */
  defaultHeight() {
    return $('.Composer').height();
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
   * Computes the Composer height based on the current state.
   * @param {Composer.PositionEnum} position
   * @returns {Integer|String}
   */
  computedHeight(position) {
    this.position = position;

    return this.computedHeightWithPosition();
  }

  /**
   * Save a new Composer height.
   * @param {Integer} height
   */
  setHeight(height) {
    this.height = height;

    localStorage.setItem('composerHeight', this.height);
  }
}
