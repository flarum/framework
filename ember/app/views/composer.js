import Ember from 'ember';

import { PositionEnum } from '../controllers/composer';
import ActionButton from '../components/ui/controls/action-button';
import TaggedArray from '../utils/tagged-array';

var $ = Ember.$;

export default Ember.View.extend(Ember.Evented, {
    classNames: ['composer'],
    classNameBindings: [
        'minimized',
        'fullscreen',
        'active'
    ],

    position: Ember.computed.alias('controller.position'),
    visible: Ember.computed.alias('controller.visible'),
    normal: Ember.computed.alias('controller.normal'),
    minimized: Ember.computed.alias('controller.minimized'),
    fullscreen: Ember.computed.alias('controller.fullscreen'),

    // Calculate the composer's current height, based on the intended height
    // (which is set when the resizing handle is dragged), and the composer's
    // current state.
    computedHeight: function() {
        if (this.get('minimized')) {
            return '';
        } else if (this.get('fullscreen')) {
            return $(window).height();
        } else {
            return Math.max(200, Math.min(this.get('height'), $(window).height() - $('#header').outerHeight()));
        }
    }.property('height', 'minimized', 'fullscreen'),

    didInsertElement: function() {
        var view = this;
        var controller = this.get('controller');

        // Hide the composer to begin with.
        this.set('height', this.$().height());
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
        var anchorScroll = $(window).scrollTop() + $(window).height() >= $(document).height();

        var func = animate ? 'animate' : 'css';
        var paddingBottom = this.get('visible') ? this.get('computedHeight') - Ember.$('#footer').outerHeight(true) : 0;
        $('#main')[func]({paddingBottom: paddingBottom}, 'fast');

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
    updateContentHeight: function() {
        var content = this.$('.composer-content');
        this.$('.flexible-height').height(this.get('computedHeight')
            - parseInt(content.css('padding-top'))
            - parseInt(content.css('padding-bottom'))
            - this.$('.composer-header').outerHeight(true)
            - this.$('.text-editor-controls').outerHeight(true));
    },

    // ------------------------------------------------------------------------
    // OBSERVERS
    // ------------------------------------------------------------------------

    // Whenever the composer is minimized or goes to/from fullscreen, we need
    // to re-populate the control buttons, because their configuration depends
    // on the composer's current state.
    refreshControls: function() {
        var controlItems = TaggedArray.create();
        this.trigger('populateControls', controlItems);
        this.set('controlItems', controlItems);
    }.observes('minimized', 'fullscreen'),

    // Whenever the composer's computed height changes, update the DOM to
    // reflect it.
    updateHeight: function() {
        if (!this.$()) { return; }

        var view = this;
        Ember.run.scheduleOnce('afterRender', function() {
            view.$().height(view.get('computedHeight'));
            view.updateContentHeight();
        });
    }.observes('computedHeight'),

    positionWillChange: function() {
        this.set('oldPosition', this.get('position'));
    }.observesBefore('position'),

    // Whenever the composer's display state changes, update the DOM to slide
    // it in or out.
    positionDidChange: function() {
        var $composer = this.$();
        if (!$composer) { return; }
        var view = this;

        // At this stage, the position property has just changed, and the
        // class name hasn't been altered in the DOM. So, we can grab the
        // composer's current height which we might want to animate from.
        // After the DOM has updated, we animate to its new height.
        var oldHeight = $composer.height();

        Ember.run.scheduleOnce('afterRender', function() {
            var newHeight = $composer.height();

            switch (view.get('position')) {
                case PositionEnum.HIDDEN:
                    $composer.animate({bottom: -oldHeight}, 'fast', function() {
                        $composer.hide();
                    });
                    break;

                case PositionEnum.NORMAL:
                    if (view.get('oldPosition') !== PositionEnum.FULLSCREEN) {
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

            if (view.get('position') !== PositionEnum.FULLSCREEN) {
                view.updateBodyPadding(true);
            }
            view.updateContentHeight();
        });
    }.observes('position'),

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
        view.set('height', event.data.heightStart + deltaPixels);
        view.updateContentHeight();
        view.updateBodyPadding();
    },

    mouseWasReleased: function(event) {
        if (! event.data.handle) { return; }
        event.data.handle = null;
        $('body').css('cursor', '');
    },

    focus: function() {
        this.$().find(':input:enabled:visible:first').focus();
    },

    populateControls: function(controls) {
        var controller = this.get('controller');
        var addControl = function(action, icon, title) {
            var control = ActionButton.create({
                icon: icon,
                title: title,
                className: 'btn btn-icon btn-link',
                action: function() {
                    controller.send(action);
                }
            });
            controls.pushObjectWithTag(control, action);
        };

        if (this.get('fullscreen')) {
            addControl('exitFullscreen', 'compress', 'Exit Full Screen');
        } else {
            if (! this.get('minimized')) {
                addControl('minimize', 'minus minimize', 'Minimize');
                addControl('fullscreen', 'expand', 'Full Screen');
            }
            addControl('close', 'times', 'Close');
        }
    }
});
