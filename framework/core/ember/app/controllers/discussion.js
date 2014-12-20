import Ember from 'ember';

import PostStream from '../models/post-stream';

export default Ember.ObjectController.extend(Ember.Evented, {

    needs: ['application', 'composer'],
    
    queryParams: ['start'],
    start: '1',
    searchQuery: '',

    loaded: false,
    postStream: null,

    setup: function(discussion) {
        this.set('model', discussion);

        // Set up the post stream object. It needs to know about the discussion
        // its representing the posts for, and we also need to inject the Ember
        // data store.
        var postStream = PostStream.create();
        postStream.set('discussion', discussion);
        postStream.set('store', this.get('store'));
        this.set('postStream', postStream);

        // Next, we need to load a list of the discussion's post IDs into the
        // post stream object. If we don't already have this information, we'll
        // need to reload the discussion model.
        var promise = discussion.get('posts') ? Ember.RSVP.resolve(discussion) : discussion.reload();

        // When we know we have the post IDs, we can set up the post stream with
        // them. Then we're ready to load some posts!
        var controller = this;
        promise.then(function(discussion) {
            postStream.setup(discussion.get('postIds'));
            controller.set('loaded', true);
            controller.send('jumpToNumber', controller.get('start'));
        });
    },

    actions: {

        reply: function() {
            this.set('controllers.composer.showing', true);
            this.set('controllers.composer.title', 'Replying to <em>'+this.get('model.title')+'</em>');
        },

        jumpToNumber: function(number) {
            // In some instances, we might be given a placeholder start index
            // value. We need to convert this into a numerical value.
            switch (number) {
                case 'last':
                    number = this.get('model.lastPostNumber');
                    break;

                case 'unread':
                    number = this.get('model.readNumber') + 1;
                    break;
            }

            number = Math.max(number, 1);

            // Let's start by telling our listeners that we're going to load
            // posts near this number. The discussion view will listen and
            // consequently scroll down to the appropriate position in the
            // discussion.
            this.trigger('loadingNumber', number);

            // Now we have to actually make sure the posts around this new start
            // position are loaded. We will tell our listeners when they are.
            // Again, the view will scroll down to the appropriate post.
            var controller = this;
            this.get('postStream').loadNearNumber(number).then(function() {
                Ember.run.scheduleOnce('afterRender', function() {
                    controller.trigger('loadedNumber', number);
                });
            });
        },

        jumpToIndex: function(index) {
            // Let's start by telling our listeners that we're going to load
            // posts at this index. The discussion view will listen and
            // consequently scroll down to the appropriate position in the
            // discussion.
            this.trigger('loadingIndex', index);

            // Now we have to actually make sure the posts around this index are
            // loaded. We will tell our listeners when they are. Again, the view
            // will scroll down to the appropriate post.
            var controller = this;
            this.get('postStream').loadNearIndex(index).then(function() {
                Ember.run.scheduleOnce('afterRender', function() {
                    controller.trigger('loadedIndex', index);
                });
            });
        },

        loadRange: function(start, end, backwards) {
            this.get('postStream').loadRange(start, end, backwards);
        }
    }
});
