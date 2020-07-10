import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import ComposerButton from './ComposerButton';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';
import ComposerState from '../states/ComposerState';

/**
 * The `Composer` component displays the composer. It can be loaded with a
 * content component with `load` and then its position/state can be altered with
 * `show`, `hide`, `close`, `minimize`, `fullScreen`, and `exitFullScreen`.
 */
export default class Composer extends Component {
  init() {
    this.state = this.props.state;
    this.prevPosition = this.state.position;
  }

  view() {
    const classes = {
      normal: this.state.position === ComposerState.PositionEnum.NORMAL,
      minimized: this.state.position === ComposerState.PositionEnum.MINIMIZED,
      fullScreen: this.state.position === ComposerState.PositionEnum.FULLSCREEN,
      active: this.state.active,
    };
    classes.visible = classes.normal || classes.minimized || classes.fullScreen;

    const showIfMinimized = this.state.position === ComposerState.PositionEnum.MINIMIZED ? this.state.show.bind(this.state) : undefined;

    return (
      <div className={'Composer ' + classList(classes)}>
        <div className="Composer-handle" config={this.configHandle.bind(this)} />
        <ul className="Composer-controls">{listItems(this.controlItems().toArray())}</ul>
        <div className="Composer-content" onclick={showIfMinimized}>
          {this.state.getBody().componentClass
            ? this.state.getBody().componentClass.component({ state: this.state, disabled: classes.minimized })
            : ''}
        </div>
      </div>
    );
  }

  config(isInitialized, context) {
    // Set the height of the Composer element and its contents on each redraw,
    // so that they do not lose it if their DOM elements are recreated.
    this.updateHeight();

    if (this.prevPosition !== this.state.position) {
      // Execute if exitFullScreen() is called
      if (this.state.position !== ComposerState.PositionEnum.FULLSCREEN && this.prevPosition === ComposerState.PositionEnum.FULLSCREEN) {
        this.focus();
      }
      // Execute if hide() is called
      else if (this.state.position === ComposerState.PositionEnum.HIDDEN) {
        // Animate the composer sliding down off the bottom edge of the viewport.
        // Only when the animation is completed, update the Composer state flag and
        // other elements on the page.
        this.$()
          .stop(true)
          .animate({ bottom: -this.$().height() }, 'fast', () => {
            this.$().hide();
            this.hideBackdrop();
            this.updateBodyPadding();
          });
      }
      // Execute if minimize() is called
      else if (this.state.position === ComposerState.PositionEnum.MINIMIZED) {
        this.animateToPosition(ComposerState.PositionEnum.MINIMIZED);

        this.$().css('top', 'auto');
        this.hideBackdrop();
      }
      // Execute if fullscreen() is called
      else if (this.state.position === ComposerState.PositionEnum.FULLSCREEN) {
        this.focus();
      }
      // Execute when show() is called
      else if (this.state.position === ComposerState.PositionEnum.NORMAL) {
        this.animateToPosition(ComposerState.PositionEnum.NORMAL).then(() => this.focus());

        if (this.state.onMobile()) {
          this.$().css('top', $(window).scrollTop());
          this.showBackdrop();
        }
      }

      this.prevPosition = this.state.position;
    }

    if (isInitialized) return;

    // Since this component is a part of the global UI that persists between
    // routes, we will flag the DOM to be retained across route changes.
    context.retain = true;

    this.initializeHeight();
    this.$().hide().css('bottom', -this.state.computedHeight());

    // Whenever any of the inputs inside the composer are have focus, we want to
    // add a class to the composer to draw attention to it.
    this.$().on('focus blur', ':input', (e) => {
      this.state.active = e.type === 'focusin';
      m.redraw();
    });

    // When the escape key is pressed on any inputs, close the composer.
    this.$().on('keydown', ':input', 'esc', () => this.close());

    // Don't let the user leave the page without first giving the composer's
    // component a chance to scream at the user to make sure they don't
    // unintentionally lose any contnet.
    window.onbeforeunload = () => {
      return (this.state.bodyPreventExit && this.state.bodyPreventExit()) || undefined;
    };

    const handlers = {};

    $(window)
      .on('resize', (handlers.onresize = this.updateHeight.bind(this)))
      .resize();

    $(document)
      .on('mousemove', (handlers.onmousemove = this.onmousemove.bind(this)))
      .on('mouseup', (handlers.onmouseup = this.onmouseup.bind(this)));

    context.onunload = () => {
      $(window).off('resize', handlers.onresize);

      $(document).off('mousemove', handlers.onmousemove).off('mouseup', handlers.onmouseup);
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

    $(element)
      .css('cursor', 'row-resize')
      .bind('dragstart mousedown', (e) => e.preventDefault())
      .mousedown(function (e) {
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
    this.changeHeight(this.heightStart + deltaPixels);

    // Update the body's padding-bottom so that no content on the page will ever
    // get permanently hidden behind the composer. If the user is already
    // scrolled to the bottom of the page, then we will keep them scrolled to
    // the bottom after the padding has been updated.
    const scrollTop = $(window).scrollTop();
    const anchorToBottom = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();
    this.updateBodyPadding(anchorToBottom);
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
   * Draw focus to the text editor.
   */
  focus() {
    this.$(':input:not(button):enabled:visible:first').focus();
  }

  /**
   * Initialize default Composer height.
   */
  initializeHeight() {
    this.state.height = localStorage.getItem('composerHeight');

    if (!this.state.height) {
      this.state.height = this.defaultHeight();
    }
  }

  /**
   * Save a new Composer height and update the DOM.
   * @param {Integer} height
   */
  changeHeight(height) {
    this.state.height = height;
    this.updateHeight();

    localStorage.setItem('composerHeight', this.state.height);
  }

  /**
   * Update the DOM to reflect the composer's current height. This involves
   * setting the height of the composer's root element, and adjusting the height
   * of any flexible elements inside the composer's body.
   */
  updateHeight() {
    const height = this.state.computedHeight();
    const $flexible = this.$('.Composer-flexible');

    this.$().height(height);

    if ($flexible.length) {
      const headerHeight = $flexible.offset().top - this.$().offset().top;
      const paddingBottom = parseInt($flexible.css('padding-bottom'), 10);
      const footerHeight = this.$('.Composer-footer').outerHeight(true);

      $flexible.height(this.$().outerHeight() - headerHeight - paddingBottom - footerHeight);
    }
  }

  /**
   * Default height of the Composer in case none is saved.
   * @returns {Integer}
   */
  defaultHeight() {
    return this.$().height();
  }

  /**
   * Update the amount of padding-bottom on the body so that the page's
   * content will still be visible above the composer when the page is
   * scrolled right to the bottom.
   */
  updateBodyPadding() {
    const visible =
      this.state.position !== ComposerState.PositionEnum.HIDDEN &&
      this.state.position !== ComposerState.PositionEnum.MINIMIZED &&
      this.$().css('position') !== 'absolute';

    const paddingBottom = visible ? this.state.computedHeight() - parseInt($('#app').css('padding-bottom'), 10) : 0;

    $('#content').css({ paddingBottom });
  }

  /**
   * Animate the Composer into the given position.
   *
   * @param {ComposerState.PositionEnum} position
   */
  animateToPosition(position) {
    // Before we redraw the composer to its new state, we need to save the
    // current height of the composer, as well as the page's scroll position, so
    // that we can smoothly transition from the old to the new state.
    const $composer = this.$().stop(true);
    const oldHeight = $composer.outerHeight();
    const scrollTop = $(window).scrollTop();

    m.redraw(true);

    // Now that we've redrawn and the composer's DOM has been updated, we want
    // to update the composer's height. Once we've done that, we'll capture the
    // real value to use as the end point for our animation later on.
    $composer.show();
    this.updateHeight();

    const newHeight = $composer.outerHeight();

    if (this.prevPosition === ComposerState.PositionEnum.HIDDEN) {
      $composer.css({ bottom: -newHeight, height: newHeight });
    } else {
      $composer.css({ height: oldHeight });
    }

    const animation = $composer.animate({ bottom: 0, height: newHeight }, 'fast').promise();

    this.updateBodyPadding();
    $(window).scrollTop(scrollTop);
    return animation;
  }

  /**
   * Show the Composer backdrop.
   */
  showBackdrop() {
    this.$backdrop = $('<div/>').addClass('composer-backdrop').appendTo('body');
  }

  /**
   * Hide the Composer backdrop.
   */
  hideBackdrop() {
    if (this.$backdrop) this.$backdrop.remove();
  }

  /**
   * Build an item list for the composer's controls.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    if (this.state.position === ComposerState.PositionEnum.FULLSCREEN) {
      items.add(
        'exitFullScreen',
        ComposerButton.component({
          icon: 'fas fa-compress',
          title: app.translator.trans('core.forum.composer.exit_full_screen_tooltip'),
          onclick: this.state.exitFullScreen.bind(this.state),
        })
      );
    } else {
      if (this.state.position !== ComposerState.PositionEnum.MINIMIZED) {
        items.add(
          'minimize',
          ComposerButton.component({
            icon: 'fas fa-minus minimize',
            title: app.translator.trans('core.forum.composer.minimize_tooltip'),
            onclick: this.state.minimize.bind(this.state),
            itemClassName: 'App-backControl',
          })
        );

        items.add(
          'fullScreen',
          ComposerButton.component({
            icon: 'fas fa-expand',
            title: app.translator.trans('core.forum.composer.full_screen_tooltip'),
            onclick: this.state.fullScreen.bind(this.state),
          })
        );
      }

      items.add(
        'close',
        ComposerButton.component({
          icon: 'fas fa-times',
          title: app.translator.trans('core.forum.composer.close_tooltip'),
          onclick: this.state.close.bind(this.state),
        })
      );
    }

    return items;
  }
}
