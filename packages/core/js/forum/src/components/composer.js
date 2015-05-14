import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import ActionButton from 'flarum/components/action-button';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/list-items';
import classList from 'flarum/utils/class-list';
import computed from 'flarum/utils/computed';

class Composer extends Component {
  constructor(props) {
    super(props);

    this.position = m.prop(Composer.PositionEnum.HIDDEN);
    this.height = m.prop();

    // Calculate the composer's current height, based on the intended height
    // (which is set when the resizing handle is dragged), and the composer's
    // current state.
    this.computedHeight = computed('height', 'position', function(height, position) {
      if (position === Composer.PositionEnum.MINIMIZED || position === Composer.PositionEnum.HIDDEN) {
        return '';
      } else if (position === Composer.PositionEnum.FULLSCREEN) {
        return $(window).height();
      } else {
        return Math.max(200, Math.min(height, $(window).height() - $('#header').outerHeight()));
      }
    });
  }

  view() {
    var classes = {
      'minimized': this.position() === Composer.PositionEnum.MINIMIZED,
      'full-screen': this.position() === Composer.PositionEnum.FULLSCREEN
    };
    classes.visible = this.position() === Composer.PositionEnum.NORMAL || classes.minimized || classes.fullScreen;

    if (this.component) this.component.props.disabled = classes.minimized;

    return m('div.composer', {config: this.onload.bind(this), className: classList(classes)}, [
      m('div.composer-handle', {config: this.configHandle.bind(this)}),
      m('ul.composer-controls', listItems(this.controlItems().toArray())),
      m('div.composer-content', {onclick: () => {
        if (this.position() === Composer.PositionEnum.MINIMIZED) this.show();
      }}, this.component ? this.component.view() : '')
    ]);
  }

  onload(element, isInitialized, context) {
    this.element(element);

    if (isInitialized) { return; }
    context.retain = true;

    // Hide the composer to begin with.
    this.height(localStorage.getItem('composerHeight') || this.$().height());
    this.$().hide();

    // Modulate the view's active property/class according to the focus
    // state of any inputs.
    this.$().on('focus blur', ':input', (e) => this.$().toggleClass('active', e.type === 'focusin'));

    // When the escape key is pressed on any inputs, close the composer.
    this.$().on('keydown', ':input', 'esc', () => this.close());

    context.onunload = this.ondestroy.bind(this);
    this.handlers = {};

    $(window).on('resize', this.handlers.onresize = this.onresize.bind(this)).resize();

    $(document)
      .on('mousemove', this.handlers.onmousemove = this.onmousemove.bind(this))
      .on('mouseup', this.handlers.onmouseup = this.onmouseup.bind(this));
  }

  configHandle(element, isInitialized) {
    if (isInitialized) { return; }

    var self = this;
    $(element).css('cursor', 'row-resize')
      .mousedown(function(e) {
        self.mouseStart = e.clientY;
        self.heightStart = self.$().height();
        self.handle = $(this);
        $('body').css('cursor', 'row-resize');
      }).bind('dragstart mousedown', function(e) {
        e.preventDefault();
      });
  }

  ondestroy() {
    $(window).off('resize', this.handlers.onresize);

    $(document)
      .off('mousemove', this.handlers.onmousemove)
      .off('mouseup', this.handlers.onmouseup);
  }

  updateHeight() {
    this.$().height(this.computedHeight());
    this.setContentHeight(this.computedHeight());
  }

  onresize() {
    this.updateHeight();
  }

  onmousemove(e) {
    if (!this.handle) { return; }

    // Work out how much the mouse has been moved, and set the height
    // relative to the old one based on that. Then update the content's
    // height so that it fills the height of the composer, and update the
    // body's padding.
    var deltaPixels = this.mouseStart - e.clientY;
    var height = this.heightStart + deltaPixels;
    this.height(height);
    this.updateHeight();
    this.updateBodyPadding();

    localStorage.setItem('composerHeight', height);
  }

  onmouseup(e) {
    if (!this.handle) { return; }
    this.handle = null;
    $('body').css('cursor', '');
  }

  preventExit() {
    return this.component && this.component.preventExit();
  }

  render() {
    // @todo this function's logic could probably use some reworking. The
    //     following line is bad because it prevents focusing on the composer
    //     input when the composer is shown when it's already being shown
    if (this.position() === this.oldPosition) { return; }

    var $composer = this.$();
    var oldHeight = $composer.is(':visible') ? $composer.height() : 0;

    if (this.position() !== Composer.PositionEnum.HIDDEN) {
      m.redraw(true);
    }

    this.updateHeight();
    var newHeight = $composer.height();

    switch (this.position()) {
      case Composer.PositionEnum.HIDDEN:
        $composer.css({height: oldHeight}).animate({bottom: -newHeight}, 'fast', () => {
          $composer.hide();
          this.clear();
          m.redraw();
        });
        break;

      case Composer.PositionEnum.NORMAL:
        if (this.oldPosition !== Composer.PositionEnum.FULLSCREEN) {
          $composer.show();
          $composer.css({height: oldHeight}).animate({bottom: 0, height: newHeight}, 'fast', this.component.focus.bind(this.component));
        } else {
          this.component.focus();
        }
        break;

      case Composer.PositionEnum.MINIMIZED:
        $composer.css({height: oldHeight}).animate({height: newHeight}, 'fast', this.component.focus.bind(this.component));
        break;
    }

    if (this.position() !== Composer.PositionEnum.FULLSCREEN) {
      this.updateBodyPadding(true);
    } else {
      this.component.focus();
    }
    $('body').toggleClass('composer-open', this.position() !== Composer.PositionEnum.HIDDEN);
    this.oldPosition = this.position();
    this.setContentHeight(this.computedHeight());
  }

  // Update the amount of padding-bottom on the body so that the page's
  // content will still be visible above the composer when the page is
  // scrolled right to the bottom.
  updateBodyPadding(animate) {
    // Before we change anything, work out if we're currently scrolled
    // right to the bottom of the page. If we are, we'll want to anchor
    // the body's scroll position to the bottom after we update the
    // padding.
    var scrollTop = $(window).scrollTop();
    var anchorScroll = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();

    var func = animate ? 'animate' : 'css';
    var paddingBottom = this.position() !== Composer.PositionEnum.HIDDEN ? this.computedHeight() - parseInt($('#page').css('padding-bottom')) : 0;
    $('#content')[func]({paddingBottom}, 'fast');

    if (anchorScroll) {
      if (animate) {
        $('html, body').stop(true).animate({scrollTop: $(document).height()}, 'fast');
      } else {
        $('html, body').scrollTop($(document).height());
      }
    }
  }

  // Update the height of the stuff inside of the composer. There should be
  // an element with the class .flexible-height — this element is intended
  // to fill up the height of the composer, minus the space taken up by the
  // composer's header/footer/etc.
  setContentHeight(height) {
    var content = this.$('.composer-content');
    this.$('.flexible-height').height(height -
      parseInt(content.css('padding-top')) -
      parseInt(content.css('padding-bottom')) -
      this.$('.composer-header').outerHeight(true) -
      this.$('.text-editor-controls').outerHeight(true));
  }

  load(component) {
    if (!this.preventExit()) {
      this.component = component;
    }
  }

  clear() {
    this.component = null;
  }

  show() {
    if ([Composer.PositionEnum.MINIMIZED, Composer.PositionEnum.HIDDEN].indexOf(this.position()) !== -1) {
      this.position(Composer.PositionEnum.NORMAL);
    }
    // work around https://github.com/lhorie/mithril.js/issues/603
    setTimeout(() => this.render());
  }

  hide() {
    this.position(Composer.PositionEnum.HIDDEN);
    this.render();
  }

  close() {
    if (!this.preventExit()) {
      this.hide();
    }
  }

  minimize() {
    if (this.position() !== Composer.PositionEnum.HIDDEN) {
      this.position(Composer.PositionEnum.MINIMIZED);
      this.render();
    }
  }

  fullScreen() {
    if (this.position() !== Composer.PositionEnum.HIDDEN) {
      this.position(Composer.PositionEnum.FULLSCREEN);
      this.render();
    }
  }

  exitFullScreen() {
    if (this.position() === Composer.PositionEnum.FULLSCREEN) {
      this.position(Composer.PositionEnum.NORMAL);
      this.render();
    }
  }

  control(props) {
    props.className = 'btn btn-icon btn-link';
    return ActionButton.component(props);
  }

  controlItems() {
    var items = new ItemList();

    if (this.position() === Composer.PositionEnum.FULLSCREEN) {
      items.add('exitFullScreen', this.control({ icon: 'compress', title: 'Exit Full Screen', onclick: this.exitFullScreen.bind(this) }));
    } else {
      if (this.position() !== Composer.PositionEnum.MINIMIZED) {
        items.add('minimize', this.control({ icon: 'minus minimize', title: 'Minimize', onclick: this.minimize.bind(this) }));
        items.add('fullScreen', this.control({ icon: 'expand', title: 'Full Screen', onclick: this.fullScreen.bind(this) }));
      }
      items.add('close', this.control({ icon: 'times', title: 'Close', wrapperClass: 'back-control', onclick: this.close.bind(this) }));
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
