import Ember from 'ember';

export var PositionEnum = {
    HIDDEN: 'hidden',
    NORMAL: 'normal',
    MINIMIZED: 'minimized',
    FULLSCREEN: 'fullscreen'
};

export default Ember.Controller.extend(Ember.Evented, {
    content: null,
    position: PositionEnum.HIDDEN,

    visible: Ember.computed.or('normal', 'minimized', 'fullscreen'),
    normal: Ember.computed.equal('position', PositionEnum.NORMAL),
    minimized: Ember.computed.equal('position', PositionEnum.MINIMIZED),
    fullscreen: Ember.computed.equal('position', PositionEnum.FULLSCREEN),

    // Switch out the composer's content for a new component. The old
    // component will be given the opportunity to abort the switch. Note:
    // there appears to be a bug in Ember where the content binding won't
    // update in the view if we switch the value out immediately. As a
    // workaround, we set it to null, and then set it to its new value in the
    // next run loop iteration.
    switchContent: function(newContent) {
        var composer = this;
        this.confirmExit().then(function() {
            composer.set('content', null);
            Ember.run.next(function() {
                newContent.set('composer', composer);
                composer.set('content', newContent);
            });
        });
    },

    // Ask the content component if it's OK to close it, and give it the
    // opportunity to abort. The content component must respond to the
    // `willExit(abort)` action, and call `abort()` if we should not proceed.
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
        show: function() {
            var composer = this;

            // We do this in the next run loop because we need to wait for new
            // content to be switched in. See `switchContent` above.
            Ember.run.next(function() {
                composer.set('position', PositionEnum.NORMAL);
                composer.trigger('focus');
            });
        },

        hide: function() {
            this.set('position', PositionEnum.HIDDEN);
        },

        clearContent: function() {
            this.set('content', null);
        },

        close: function() {
            var composer = this;
            this.confirmExit().then(function() {
                composer.send('hide');
            });
        },

        minimize: function() {
            if (this.get('position') !== PositionEnum.HIDDEN) {
                this.set('position', PositionEnum.MINIMIZED);
            }
        },

        fullscreen: function() {
            if (this.get('position') !== PositionEnum.HIDDEN) {
                this.set('position', PositionEnum.FULLSCREEN);
                this.trigger('focus');
            }
        },

        exitFullscreen: function() {
            if (this.get('position') === PositionEnum.FULLSCREEN) {
                this.set('position', PositionEnum.NORMAL);
                this.trigger('focus');
            }
        }
    }

});
