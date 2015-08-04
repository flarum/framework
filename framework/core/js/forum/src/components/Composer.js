import Component from 'flarum/Component';
import ItemList from 'flarum/utils/ItemList';
import ComposerButton from 'flarum/components/ComposerButton';
import listItems from 'flarum/helpers/listItems';
import classList from 'flarum/utils/classList';
import computed from 'flarum/utils/computed';

/**
 * The `Composer` component displays the composer. It can be loaded with a
 * content component with `load` and then its position/state can be altered with
 * `show`, `hide`, `close`, `minimize`, `fullScreen`, and `exitFullScreen`.
 */
class Composer extends Component {
  constructor(...args) {
    super(...args);

    /**
     * The composer's current position.
     *
     * @type {Composer.PositionEnum}
     */
    this.position = Composer.PositionEnum.HIDDEN;

    /**
     * The composer's previous position.
     *
     * @type {Composer.PositionEnum}
     */
    this.oldPosition = null;

    /**
     * The composer's intended height, which can be modified by the user
     * (by dragging the composer handle).
     *
     * @type {Integer}
     */
    this.height = null;

    /**
     * Computed the composer's current height, based on the intended height, and
     * the composer's current state. This will be applied to the composer's
     * content's DOM element.
     *
     * @return {Integer}
     */
    this.computedHeight = computed('height', 'position', (height, position) => {
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
      return Math.max(200, Math.min(height, $(window).height() - $('#header').outerHeight()));
    });
  }

  view() {
    const classes = {
      'minimized': this.position === Composer.PositionEnum.MINIMIZED,
      'fullScreen': this.position === Composer.PositionEnum.FULLSCREEN
    };
    classes.visible = this.position === Composer.PositionEnum.NORMAL || classes.minimized || classes.fullScreen;

    // If the composer is minimized, tell the composer's content component that
    // it shouldn't let the user interact with it. Set up a handler so that if
    // the content IS clicked, the composer will be shown.
    if (this.component) this.component.props.disabled = classes.minimized;

    const showIfMinimized = () => {
      if (this.position === Composer.PositionEnum.MINIMIZED) this.show();
      m.redraw.strategy('none');
    };

    return (
      <div className={'Composer ' + classList(classes)}>
        <div className="Composer-handle" config={this.configHandle.bind(this)}/>
        <ul className="Composer-controls">{listItems(this.controlItems().toArray())}</ul>
        <div className="Composer-content" onclick={showIfMinimized}>
          {this.component ? this.component.render() : ''}
        </div>
      </div>
    );
  }

  config(isInitialized, context) {
    this.updateHeight();

    if (isInitialized) return;

    // Since this component is a part of the global UI that persists between
    // routes, we will flag the DOM to be retained across route changes.
    context.retain = true;

    // Initialize the composer's intended height based on what the user has set
    // it at previously, or otherwise the composer's default height. After that,
    // we'll hide the composer.
    this.height = localStorage.getItem('composerHeight') || this.$().height();
    this.$().hide();

    // Whenever any of the inputs inside the composer are have focus, we want to
    // add a class to the composer to draw attention to it.
    this.$().on('focus blur', ':input', e => this.$().toggleClass('active', e.type === 'focusin'));

    // When the escape key is pressed on any inputs, close the composer.
    this.$().on('keydown', ':input', 'esc', () => this.close());

    // Don't let the user leave the page without first giving the composer's
    // component a chance to scream at the user to make sure they don't
    // unintentionally lose any contnet.
    window.onbeforeunload = () => {
      return (this.component && this.component.preventExit()) || null;
    };

    const handlers = {};

    $(window).on('resize', handlers.onresize = this.updateHeight.bind(this)).resize();

    $(document)
      .on('mousemove', handlers.onmousemove = this.onmousemove.bind(this))
      .on('mouseup', handlers.onmouseup = this.onmouseup.bind(this));

    context.onunload = () => {
      $(window).off('resize', handlers.onresize);

      $(document)
        .off('mousemove', handlers.onmousemove)
        .off('mouseup', handlers.onmouseup);
    };
  }

  /**
   * Add the necessary event handlers to the composer's handle so that it can
   * be used to resize the composer.
   *
   * @param {DOMElement} element
   * @param {Boolean} isInitialized
   */
  configHandle(element, isInitialized) {
    if (isInitialized) return;

    const composer = this;

    $(element).css('cursor', 'row-resize')
      .bind('dragstart mousedown', e => e.preventDefault())
      .mousedown(function(e) {
        composer.mouseStart = e.clientY;
        composer.heightStart = composer.$().height();
        composer.handle = $(this);
        $('body').css('cursor', 'row-resize');
      });
  }

  /**
   * Resize the composer according to mouse movement.
   *
   * @param {Event} e
   */
  onmousemove(e) {
    if (!this.handle) return;

    // Work out how much the mouse has been moved, and set the height
    // relative to the old one based on that. Then update the content's
    // height so that it fills the height of the composer, and update the
    // body's padding.
    const deltaPixels = this.mouseStart - e.clientY;
    this.height = this.heightStart + deltaPixels;
    this.updateHeight();

    // Update the body's padding-bottom so that no content on the page will ever
    // get permanently hidden behind the composer. If the user is already
    // scrolled to the bottom of the page, then we will keep them scrolled to
    // the bottom after the padding has been updated.
    const scrollTop = $(window).scrollTop();
    const anchorToBottom = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();
    this.updateBodyPadding(anchorToBottom);

    localStorage.setItem('composerHeight', this.height);
  }

  /**
   * Finish resizing the composer when the mouse is released.
   */
  onmouseup() {
    if (!this.handle) return;

    this.handle = null;
    $('body').css('cursor', '');
  }

  /**
   * Update the DOM to reflect the composer's current height. This involves
   * setting the height of the composer's root element, and adjusting the height
   * of any flexible elements inside the composer's body.
   */
  updateHeight() {
    // TODO: update this in a way that is independent of the TextEditor being
    // present.
    const height = this.computedHeight();
    const $flexible = this.$('.TextEditor-flexible');

    this.$().height(height);

    if ($flexible.length) {
      const headerHeight = $flexible.offset().top - this.$().offset().top;
      const paddingBottom = parseInt($flexible.css('padding-bottom'), 10);
      const footerHeight = this.$('.TextEditor-controls').outerHeight(true);

      $flexible.height(height - headerHeight - paddingBottom - footerHeight);
    }
  }

  /**
   * Update the amount of padding-bottom on the body so that the page's
   * content will still be visible above the composer when the page is
   * scrolled right to the bottom.
   */
  updateBodyPadding() {
    const visible = this.position !== Composer.PositionEnum.HIDDEN &&
                    this.position !== Composer.PositionEnum.MINIMIZED;

    const paddingBottom = visible
      ? this.computedHeight() - parseInt($('#app').css('padding-bottom'), 10)
      : 0;
    $('#content').css({paddingBottom});
  }

  /**
   * Update (and animate) the DOM to reflect the composer's current state.
   */
  update() {
    // Before we redraw the composer to its new state, we need to save the
    // current height of the composer, as well as the page's scroll position, so
    // that we can smoothly transition from the old to the new state.
    const $composer = this.$().stop(true);
    const oldHeight = $composer.is(':visible') ? $composer.outerHeight() : 0;
    const scrollTop = $(window).scrollTop();

    m.redraw(true);

    // Now that we've redrawn and the composer's DOM has been updated, we want
    // to update the composer's height. Once we've done that, we'll capture the
    // real value to use as the end point for our animation later on.
    $composer.show();
    this.updateHeight();

    const newHeight = $composer.outerHeight();

    switch (this.position) {
      case Composer.PositionEnum.NORMAL:
        // If the composer is being opened, we will make it visible and animate
        // it growing/sliding up from the bottom of the viewport. Or if the user
        // has just exited fullscreen mode, we will simply tell the content to
        // take focus.
        if (this.oldPosition !== Composer.PositionEnum.FULLSCREEN) {
          $composer.show()
            .css({height: oldHeight})
            .animate({bottom: 0, height: newHeight}, 'fast', this.component.focus.bind(this.component));

          if ($composer.css('position') === 'absolute') {
            $composer.css('top', $(window).scrollTop());

            this.$backdrop = $('<div/>')
              .addClass('composer-backdrop')
              .appendTo('body');
          }
        } else {
          this.component.focus();
        }
        break;

      case Composer.PositionEnum.MINIMIZED:
        // If the composer has been minimized, we will animate it shrinking down
        // to its new smaller size.
        $composer.css({top: 'auto', height: oldHeight})
          .animate({height: newHeight}, 'fast');

        if (this.$backdrop) this.$backdrop.remove();
        break;

      case Composer.PositionEnum.HIDDEN:
        // If the composer has been hidden, then we will animate it sliding down
        // beyond the edge of the viewport. Once the animation is complete, we
        // un-draw the composer's component.
        $composer.css({top: 'auto', height: oldHeight})
          .animate({bottom: -newHeight}, 'fast', () => {
            $composer.hide();
            this.clear();
            m.redraw();
          });

        if (this.$backdrop) this.$backdrop.remove();
        break;

      case Composer.PositionEnum.FULLSCREEN:
        this.component.focus();
        break;

      default:
        // no default
    }

    // Provided the composer isn't in fullscreen mode, we'll want to update the
    // body's padding to make sure all of the page's content can still be seen.
    // Plus, we'll scroll back to where we were before the composer was opened,
    // as its opening may have changed the content of the page.
    if (this.position !== Composer.PositionEnum.FULLSCREEN) {
      this.updateBodyPadding();
      $('html, body').scrollTop(scrollTop);
    }

    this.oldPosition = this.position;
  }

  /**
   * Confirm with the user that they want to close the composer and lose their
   * content.
   *
   * @return {Boolean} Whether or not the exit was cancelled.
   */
  preventExit() {
    if (this.component) {
      const preventExit = this.component.preventExit();

      if (preventExit) {
        return !confirm(preventExit);
      }
    }
  }

  /**
   * Load a content component into the composer.
   *
   * @param {Component} component
   * @public
   */
  load(component) {
    if (this.preventExit()) return;

    // If we load a similar component into the composer, then Mithril will be
    // able to diff the old/new contents and some DOM-related state from the
    // old composer will remain. To prevent this from happening, we clear the
    // component and force a redraw, so that the new component will be working
    // on a blank slate.
    if (this.component) {
      this.clear();
      m.redraw(true);
    }

    this.component = component;
  }

  /**
   * Clear the composer's content component.
   *
   * @public
   */
  clear() {
    this.component = null;
  }

  /**
   * Show the composer.
   *
   * @public
   */
  show() {
    // If the composer is hidden or minimized, we'll need to update its
    // position. Otherwise, if the composer is already showing (whether it's
    // fullscreen or not), we can leave it as is.
    if ([Composer.PositionEnum.MINIMIZED, Composer.PositionEnum.HIDDEN].indexOf(this.position) !== -1) {
      this.position = Composer.PositionEnum.NORMAL;
    }

    this.update();
  }

  /**
   * Close the composer.
   *
   * @public
   */
  hide() {
    this.position = Composer.PositionEnum.HIDDEN;
    this.update();
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
    if (this.position !== Composer.PositionEnum.HIDDEN) {
      this.position = Composer.PositionEnum.MINIMIZED;
      this.update();
    }
  }

  /**
   * Take the composer into fullscreen mode. Has no effect if the composer is
   * hidden.
   *
   * @public
   */
  fullScreen() {
    if (this.position !== Composer.PositionEnum.HIDDEN) {
      this.position = Composer.PositionEnum.FULLSCREEN;
      this.update();
    }
  }

  /**
   * Exit fullscreen mode.
   *
   * @public
   */
  exitFullScreen() {
    if (this.position === Composer.PositionEnum.FULLSCREEN) {
      this.position = Composer.PositionEnum.NORMAL;
      this.update();
    }
  }

  /**
   * Build an item list for the composer's controls.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    if (this.position === Composer.PositionEnum.FULLSCREEN) {
      items.add('exitFullScreen', ComposerButton.component({
        icon: 'compress',
        title: app.trans('core.exit_full_screen'),
        onclick: this.exitFullScreen.bind(this)
      }));
    } else {
      if (this.position !== Composer.PositionEnum.MINIMIZED) {
        items.add('minimize', ComposerButton.component({
          icon: 'minus minimize',
          title: app.trans('core.minimize'),
          onclick: this.minimize.bind(this),
          itemClassName: 'App-backControl'
        }));

        items.add('fullScreen', ComposerButton.component({
          icon: 'expand',
          title: app.trans('core.full_screen'),
          onclick: this.fullScreen.bind(this)
        }));
      }

      items.add('close', ComposerButton.component({
        icon: 'times',
        title: app.trans('core.close'),
        onclick: this.close.bind(this)
      }));
    }

    return items;
  }
}

Composer.PositionEnum = {
  HIDDEN: 'hidden',
  NORMAL: 'normal',
  MINIMIZED: 'minimized',
  FULLSCREEN: 'fullScreen'
};

export default Composer;
