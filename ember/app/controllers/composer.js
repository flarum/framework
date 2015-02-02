import Ember from 'ember';

export default Ember.Controller.extend(Ember.Evented, {

    needs: ['index', 'application'],

    content: null,

    showing: false,
    minimized: false,
    fullScreen: false,

    switchContent: function(newContent) {
        var composer = this;
        this.confirmExit().then(function() {
            composer.set('content', null);
            Ember.run.next(function() {
                composer.set('content', newContent);
            });
        });
    },

    confirmExit: function() {
        var composer = this;
        var promise = new Ember.RSVP.Promise(function(resolve, reject) {
            var content = composer.get('content');
            if (content) {
                content.send('willExit', reject);
            }
            resolve();
        });
        return promise;
    },

    actions: {
        close: function() {
            var composer = this;
            this.confirmExit().then(function() {
                composer.send('hide');
            });
        },
        hide: function() {
            this.set('showing', false);
            this.set('fullScreen', false);
            var content = this.get('content');
            if (content) {
                content.send('reset');
            }
        },
        minimize: function() {
            this.set('minimized', true);
        	this.set('fullScreen', false);
        },
        show: function() {
            var composer = this;
            Ember.run.next(function() {
                composer.set('showing', true);
            	composer.set('minimized', false);
                composer.trigger('focus');
            });
        },
        fullScreen: function() {
            this.set('fullScreen', true);
            this.set('minimized', false);
            this.trigger('focus');
        },
        exitFullScreen: function() {
            this.set('fullScreen', false);
            this.trigger('focus');
        }
    }

});
