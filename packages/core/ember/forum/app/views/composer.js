import Ember from 'ember';

import { PositionEnum } from 'flarum/controllers/composer';
import HasItemLists from 'flarum/mixins/has-item-lists';

var $ = Ember.$;

export default Ember.View.extend(HasItemLists, {
  classNames: ['composer'],
  classNameBindings: ['visible', 'minimized', 'fullscreen', 'active'],
  itemLists: ['controls'],

  position: Ember.computed.alias('controller.position'),
  visible: Ember.computed.alias('controller.visible'),
  normal: Ember.computed.alias('controller.normal'),
  minimized: Ember.computed.alias('controller.minimized'),
  fullscreen: Ember.computed.alias('controller.fullscreen'),

  // Calculate the composer's current height, based on the intended height
  // (which is set when the resizing handle is dragged), and the composer's
  // current state.
  computedHeight: Ember.computed('height', 'minimized', 'fullscreen', function() {
    if (this.get('minimized')) {
      return '';
    } else if (this.get('fullscreen')) {
      return $(window).height();
    } else {
      return Math.max(200, Math.min(this.get('height'), $(window).height() - $('#header').outerHeight()));
    }
  }),

  didInsertElement: function() {
    var view = this;
    var controller = this.get('controller');

    // Hide the composer to begin with.
    this.set('height', localStorage.getItem('composerHeight') || this.$().height());
    this.$().hide();

    // If the composer is minimized, allow the user to click anywhere on
    // it to show it.
    this.$('.composer-content').click(function() {
      if (view.get('minimized')) {
        controller.send('show');
      }
    });

    // Modulate the view's active property/class according to the focus
    // state of any inputs.
    this.$().on('focus', ':input', function() {
      view.set('active', true);
    }).on('blur', ':input', function() {
      view.set('active', false);
    });

    // Focus on the first input when the controller wants to focus.
    controller.on('focus', this, this.focus);

    // Set up the handle so that the composer can be resized.
    $(window).on('resize', {view: this}, this.windowWasResized).resize();

    var dragData = {view: this};
    this.$('.composer-handle').css('cursor', 'row-resize')
      .mousedown(function(e) {
        dragData.mouseStart = e.clientY;
        dragData.heightStart = view.$().height();
        dragData.handle = $(this);
        $('body').css('cursor', 'row-resize');
      }).bind('dragstart mousedown', function(e) {
        e.preventDefault();
      });

    $(document)
      .on('mousemove', dragData, this.mouseWasMoved)
      .on('mouseup', dragData, this.mouseWasReleased);

    // When the escape key is pressed on any inputs, close the composer.
    this.$().on('keydown', ':input', 'esc', function() {
      controller.send('close');
    });
  },

  willDestroyElement: function() {
    $(window).off('resize', this.windowWasResized);

    $(document)
      .off('mousemove', this.mouseWasMoved)
      .off('mouseup', this.mouseWasReleased);
  },

  // Update the amount of padding-bottom on the body so that the page's
  // content will still be visible above the composer when the page is
  // scrolled right to the bottom.
  updateBodyPadding: function(animate) {
    // Before we change anything, work out if we're currently scrolled
    // right to the bottom of the page. If we are, we'll want to anchor
    // the body's scroll position to the bottom after we update the
    // padding.
    var scrollTop = $(window).scrollTop();
    var anchorScroll = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();

    var func = animate ? 'animate' : 'css';
    var paddingBottom = this.get('visible') ? this.get('computedHeight') - parseInt($('#page').css('padding-bottom')) : 0;
    $('#content')[func]({paddingBottom: paddingBottom}, 'fast');

    if (anchorScroll) {
      if (animate) {
        $('html, body').animate({scrollTop: $(document).height()}, 'fast');
      } else {
        $('html, body').scrollTop($(document).height());
      }
    }
  },

  // Update the height of the stuff inside of the composer. There should be
  // an element with the class .flexible-height — this element is intended
  // to fill up the height of the composer, minus the space taken up by the
  // composer's header/footer/etc.
  setContentHeight: function(height) {
    var content = this.$('.composer-content');
    this.$('.flexible-height').height(height -
      parseInt(content.css('padding-top')) -
      parseInt(content.css('padding-bottom')) -
      this.$('.composer-header').outerHeight(true) -
      this.$('.text-editor-controls').outerHeight(true));
  },

  // ------------------------------------------------------------------------
  // OBSERVERS
  // ------------------------------------------------------------------------

  // Whenever the composer's computed height changes, update the DOM to
  // reflect it.
  updateHeight: Ember.observer('computedHeight', function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      this.$().height(this.get('computedHeight'));
    });
  }),

  updateContentHeight: Ember.observer('computedHeight', 'controller.content', function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      this.setContentHeight(this.get('computedHeight'));
    });
  }),

  updateBody: Ember.observer('visible', function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      $('body').toggleClass('composer-open', this.get('visible'));
    });
  }),

  // Whenever the composer's display state changes, update the DOM to slide
  // it in or out.
  positionDidChange: Ember.observer('position', function() {
    // At this stage, the position property has just changed, and the
    // class name hasn't been altered in the DOM. So, we can grab the
    // composer's current height which we might want to animate from.
    // After the DOM has updated, we animate to its new height.
    var $composer = this.$();
    var oldHeight = $composer ? $composer.height() : 0;

    Ember.run.scheduleOnce('afterRender', this, function() {
      var $composer = this.$();
      var newHeight = $composer.height();
      var view = this;

      switch (this.get('position')) {
        case PositionEnum.HIDDEN:
          $composer.css({height: oldHeight}).animate({bottom: -newHeight}, 'fast', function() {
            $composer.hide();
            view.get('controller').send('clearContent');
          });
          break;

        case PositionEnum.NORMAL:
          if (this.get('oldPosition') !== PositionEnum.FULLSCREEN) {
            $composer.show();
            $composer.css({height: oldHeight}).animate({bottom: 0, height: newHeight}, 'fast', function() {
              view.focus();
            });
          }
          break;

        case PositionEnum.MINIMIZED:
          $composer.css({height: oldHeight}).animate({height: newHeight}, 'fast', function() {
            view.focus();
          });
          break;
      }

      $composer.css('overflow', '');

      if (this.get('position') !== PositionEnum.FULLSCREEN) {
        this.updateBodyPadding(true);
      }
      this.setContentHeight(this.get('computedHeight'));
      this.set('oldPosition', this.get('position'));
    });
  }),

  // ------------------------------------------------------------------------
  // LISTENERS
  // ------------------------------------------------------------------------

  windowWasResized: function(event) {
    // Force a recalculation of the computed height, because its value
    // depends on the window's height.
    var view = event.data.view;
    view.notifyPropertyChange('computedHeight');
  },

  mouseWasMoved: function(event) {
    if (! event.data.handle) { return; }
    var view = event.data.view;

    // Work out how much the mouse has been moved, and set the height
    // relative to the old one based on that. Then update the content's
    // height so that it fills the height of the composer, and update the
    // body's padding.
    var deltaPixels = event.data.mouseStart - event.clientY;
    var height = event.data.heightStart + deltaPixels;
    view.set('height', height);
    view.updateBodyPadding();

    localStorage.setItem('composerHeight', height);
  },

  mouseWasReleased: function(event) {
    if (! event.data.handle) { return; }
    event.data.handle = null;
    $('body').css('cursor', '');
  },

  focus: function() {
    if (this.$().is(':hidden')) { return; }

    Ember.run.scheduleOnce('afterRender', this, function() {
        this.$().find(':input:enabled:visible:first').focus();
    });
  },

  populateControls: function(items) {
    var view = this;
    var addControl = function(tag, title, icon) {
      return view.addActionItem(items, tag, null, icon).reopen({className: 'btn btn-icon btn-link', title: title});
    };

    if (this.get('fullscreen')) {
      addControl('exitFullscreen', 'Exit Full Screen', 'compress');
    } else {
      if (!this.get('minimized')) {
        addControl('minimize', 'Minimize', 'minus minimize');
        addControl('fullscreen', 'Full Screen', 'expand');
      }
      addControl('close', 'Close', 'times').reopen({listItemClass: 'back-control'});
    }
  },

  refreshControls: Ember.observer('position', function() {
    this.initItemList('controls');
  })
});
