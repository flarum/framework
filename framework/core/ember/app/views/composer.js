import Ember from 'ember';

import ActionButton from '../components/ui/controls/action-button';
import TaggedArray from '../utils/tagged-array';

export default Ember.View.extend(Ember.Evented, {

    classNames: ['composer'],

    classNameBindings: [
        'controller.showing:showing',
        'controller.minimized:minimized',
        'controller.fullScreen:fullscreen',
        'active'
    ],

    computedHeight: function() {
        if (this.get('controller.minimized') || this.get('controller.fullScreen')) {
            return '';
        } else {
            return Math.max(200, Math.min(this.get('height'), $(window).height() - $('#header').outerHeight()));
        }
    }.property('height', 'controller.minimized', 'controller.fullScreen'),

    updateHeight: function() {
        if (! this.$()) {
            return;
        }

        var view = this;
        Ember.run.scheduleOnce('afterRender', function() {
            view.$().height(view.get('computedHeight'));
            view.updateTextareaHeight();
            // view.updateBottomPadding();
        });
    }.observes('computedHeight'),

    showingChanged: function() {
        if (! this.$()) {
            return;
        }

        var view = this;
        if (view.get('controller.showing')) {
                view.$().show();
            }
        Ember.run.scheduleOnce('afterRender', function() {
            view.$().css('bottom', view.get('controller.showing') ? -view.$().outerHeight() : 0);
            view.$().animate({bottom: view.get('controller.showing') ? 0 : -view.$().outerHeight()}, 'fast', function() {
                if (view.get('controller.showing')) {
                    view.focus();
                } else {
                    view.$().hide();
                }
            });
            view.updateBottomPadding(true);
        });
    }.observes('controller.showing'),

    minimizedChanged: function() {
        if (! this.$() || ! this.get('controller.showing')) {
            return;
        }

        var view = this;
        var oldHeight = this.$().height();
        Ember.run.scheduleOnce('afterRender', function() {
            var newHeight = view.$().height();
            view.updateBottomPadding(true);
            view.$().css('height', oldHeight).animate({height: newHeight}, 'fast', function() {
                if (! view.get('controller.minimized')) {
                    view.focus();
                }
            });
        });
    }.observes('controller.minimized'),

    fullScreenChanged: function() {
        if (! this.$()) {
            return;
        }

        var view = this;
        Ember.run.scheduleOnce('afterRender', function() {
            view.updateTextareaHeight();
        });
    }.observes('controller.fullScreen'),

    didInsertElement: function() {
        this.$().hide();

        var controller = this.get('controller');
        this.$('.composer-content').click(function() {
            if (controller.get('minimized')) {
                controller.send('show');
            }
        });

        var view = this;
        this.$().on('focus', ':input', function() {
            view.set('active', true);
        }).on('blur', ':input', function() {
            view.set('active', false);
        });

        this.set('height', this.$().height());

        controller.on('focus', this, this.focus);

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
    },

    willDestroyElement: function() {
        $(window)
            .off('resize', this.windowWasResized);

        $(document)
            .off('mousemove', this.mouseWasMoved)
            .off('mouseup', this.mouseWasReleased);
    },

    windowWasResized: function(event) {
        var view = event.data.view;
        view.notifyPropertyChange('computedHeight');
    },

    mouseWasMoved: function(event) {
        if (! event.data.handle) {
            return;
        }
        var view = event.data.view;

        var deltaPixels = event.data.mouseStart - event.clientY;
        view.set('height', event.data.heightStart + deltaPixels);
        view.updateTextareaHeight();
        view.updateBottomPadding();
    },

    mouseWasReleased: function(event) {
        if (! event.data.handle) {
            return;
        }
        event.data.handle = null;
        $('body').css('cursor', '');
    },

    refreshControls: function() {
        var controlItems = TaggedArray.create();
        this.trigger('populateControls', controlItems);
        this.set('controlItems', controlItems);
    }.observes('controller.minimized', 'controller.fullScreen'),

    populateControls: function(controls) {
        var controller = this.get('controller');

        if (controller.get('fullScreen')) {
            var exit = ActionButton.create({
                icon: 'compress',
                title: 'Exit Full Screen',
                className: 'btn btn-icon btn-link',
                action: function() {
                    controller.send('exitFullScreen');
                }
            });
            controls.pushObjectWithTag(exit, 'exit');
        } else {
            if (! controller.get('minimized')) {
                var minimize = ActionButton.create({
                    icon: 'minus',
                    title: 'Minimize',
                    className: 'btn btn-icon btn-link btn-minimize',
                    action: function() {
                        controller.send('minimize');
                    }
                });
                controls.pushObjectWithTag(minimize, 'minimize');

                var fullScreen = ActionButton.create({
                    icon: 'expand',
                    title: 'Full Screen',
                    className: 'btn btn-icon btn-link',
                    action: function() {
                        controller.send('fullScreen');
                    }
                });
                controls.pushObjectWithTag(fullScreen, 'fullScreen');
            }

            var close = ActionButton.create({
                icon: 'times',
                title: 'Close',
                className: 'btn btn-icon btn-link',
                action: function() {
                    controller.send('close');
                }
            });
            controls.pushObjectWithTag(close, 'close');
        }
    },

    focus: function() {
        this.$().find(':input:enabled:visible:first').focus();
    },

    updateBottomPadding: function(animate) {
        var top = $(document).height() - $(window).height();
        var isBottom = $(window).scrollTop() >= top;

        $('#main')[animate ? 'animate' : 'css']({paddingBottom: this.get('controller.showing') ? this.$().outerHeight() - Ember.$('#footer').outerHeight(true) : 0}, 'fast');

        if (isBottom) {
            if (animate) {
                $('html, body').animate({scrollTop: $(document).height()}, 'fast');
            } else {
                $('html, body').scrollTop($(document).height());
            }
        }
    },

    updateTextareaHeight: function() {
        var content = this.$('.composer-content');
        this.$('textarea').height((this.get('computedHeight') || this.$().height())
            - parseInt(content.css('padding-top'))
            - parseInt(content.css('padding-bottom'))
            - this.$('.composer-header').outerHeight(true)
            - this.$('.text-editor-controls').outerHeight(true));
    }

});
