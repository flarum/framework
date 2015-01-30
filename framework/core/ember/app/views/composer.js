import Ember from 'ember';

export default Ember.View.extend({

    classNames: ['composer'],

    classNameBindings: ['controller.showing:showing', 'controller.minimized:minimized', 'active'],

    showingChanged: function() {
        if (! this.$()) {
            return;
        }

        var view = this;
        Ember.run.scheduleOnce('afterRender', function() {
            view.$().css('bottom', view.get('controller.showing') ? -view.$().outerHeight() : 0);
            view.$().animate({bottom: view.get('controller.showing') ? 0 : -view.$().outerHeight()}, 'fast', function() {
                if (view.get('controller.showing')) {
                    Ember.$(this).find('textarea').focus();
                }
            });
            view.updateBottomPadding();
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
            view.updateBottomPadding();
            view.$().css('height', oldHeight).animate({height: newHeight}, 'fast', function() {
                view.$().css('height', '');
                if (! view.get('controller.minimized')) {
                    view.$('textarea').focus();
                }
            });
        });
    }.observes('controller.minimized'),

    didInsertElement: function() {
        this.showingChanged();
        this.minimizedChanged();

        var controller = this.get('controller');
        this.$('.composer-content').click(function() {
            if (controller.get('minimized')) {
                controller.send('show');
            }
        });

        var view = this;
        this.$('textarea').focus(function() {
            view.set('active', true);
        }).blur(function() {
            view.set('active', false);
        });
    },

    updateBottomPadding: function() {
        Ember.$('#main').animate({paddingBottom: this.get('controller.showing') ? this.$().outerHeight() - Ember.$('#footer').outerHeight(true) : 0}, 'fast');
    }

});
