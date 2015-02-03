import Ember from 'ember';

import PostStream from '../models/post-stream';
import ComposerReply from '../components/discussions/composer-reply';
import ActionButton from '../components/ui/controls/action-button';
import AlertMessage from '../components/alert-message';

export default Ember.ObjectController.extend(Ember.Evented, {

    needs: ['application', 'composer'],
    
    queryParams: ['start'],
    start: '1',
    searchQuery: '',

    loaded: false,
    stream: null,

    setup: function(discussion) {
        this.set('model', discussion);

        // Set up the post stream object. It needs to know about the discussion
        // its representing the posts for, and we also need to inject the Ember
        // data store.
        var stream = PostStream.create();
        stream.set('discussion', discussion);
        stream.set('store', this.get('store'));
        this.set('stream', stream);

        // Next, we need to load a list of the discussion's post IDs into the
        // post stream object. If we don't already have this information, we'll
        // need to reload the discussion model.
        var promise = discussion.get('posts') ? Ember.RSVP.resolve(discussion) : discussion.reload();

        // When we know we have the post IDs, we can set up the post stream with
        // them. Then the view will trigger the stream to load as it sees fit.
        var controller = this;
        promise.then(function(discussion) {
            stream.setup(discussion.get('postIds'));
            controller.set('loaded', true);
        });
    },

    // Save a reply. This may be called by a composer-reply component that was
    // set up on a different discussion, so we require a discussion model to
    // be explicitly passed rather than using the controller's implicit one.
    saveReply: function(discussion, content) {
        var controller = this;
        var composer = this.get('controllers.composer');
        var stream = this.get('stream');

        composer.set('content.loading', true);
        controller.get('controllers.application').send('clearAlerts');

        var post = this.store.createRecord('post', {
            content: content,
            discussion: discussion
        });

        return post.save().then(function(post) {
            composer.send('hide');

            // If we're currently viewing the discussion which this reply was
            // made in, then we can add the post to the end of the post
            // stream.
            discussion.set('posts', discussion.get('posts')+','+post.get('id'));
            if (discussion == controller.get('model')) {
                stream.set('ids', discussion.get('postIds'));
                stream.addPostToEnd(post);
            } else {
                // Otherwise, we'll create an alert message to inform the user
                // that their reply has been posted, containing a button which
                // will transition to their new post when clicked.
                var message = AlertMessage.create({
                    type: 'success',
                    message: 'Your reply was posted.'
                });
                message.on('populateControls', function(controls) {
                    controls.pushObjectWithTag(ActionButton.extend({
                        label: 'View',
                        action: function() {
                            controller.transitionToRoute('discussion', post.get('discussion'), {queryParams: {start: post.get('number')}});
                            message.send('dismiss');
                        }
                    }), 'view');
                });
                controller.get('controllers.application').send('alert', message);
            }
        },
        function(reason) {
            var errors = reason.errors;
            for (var i in reason.errors) {
                var message = AlertMessage.create({
                    type: 'warning',
                    message: reason.errors[i]
                });
                controller.get('controllers.application').send('alert', message);
            }
        })
        .finally(function() {
            composer.set('content.loading', false);
        });
    },

    actions: {
        reply: function() {
            var controller = this;
            var discussion = this.get('model');
            var composer = this.get('controllers.composer');

            // If the composer is already set up for this discussion, then we
            // don't need to change its content - we can just show it.
            if (composer.get('content.discussion') != discussion) {
                composer.switchContent(ComposerReply.create({
                    user: controller.get('session.user'),
                    discussion: discussion,
                    submit: function(value) {
                        controller.saveReply(discussion, value);
                    }
                }));
            }

            composer.send('show');
        },

        // This action is called when the start position of the discussion
        // currently being viewed changes (i.e. when the user scrolls up/down
        // the post stream.)
        updateStart: function(start) {
            this.set('start', start);
        }
    }
});
