import Ember from 'ember';

import ComposerReply from '../components/discussions/composer-reply';
import ActionButton from '../components/ui/controls/action-button';
import AlertMessage from '../components/alert-message';

export default Ember.Controller.extend(Ember.Evented, {
    needs: ['application', 'alerts', 'composer'],
    
    queryParams: ['start'],
    start: '1',
    searchQuery: '',

    loaded: false,
    stream: null,

    // Save a reply. This may be called by a composer-reply component that was
    // set up on a different discussion, so we require a discussion model to
    // be explicitly passed rather than using the controller's implicit one.
    // @todo break this down into bite-sized functions so that extensions can
    // easily override where they please.
    saveReply: function(discussion, data) {
        var controller = this;
        var composer = this.get('controllers.composer');
        var stream = this.get('stream');

        composer.set('content.loading', true);
        controller.get('controllers.alerts').send('clearAlerts');

        var post = this.store.createRecord('post', {
            content: data.content,
            discussion: discussion
        });

        return post.save().then(function(post) {
            composer.send('hide');

            discussion.setProperties({
                lastTime: post.get('time'),
                lastUser: post.get('user'),
                lastPost: post,
                lastPostNumber: post.get('number'),
                commentsCount: discussion.get('commentsCount') + 1,
                readTime: post.get('time'),
                readNumber: post.get('number')
            });

            // If we're currently viewing the discussion which this reply was
            // made in, then we can add the post to the end of the post
            // stream.
            if (discussion == controller.get('model') && stream) {
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
                    controls.pushObjectWithTag(ActionButton.create({
                        label: 'View',
                        action: function() {
                            controller.transitionToRoute('discussion', post.get('discussion'), {queryParams: {start: post.get('number')}});
                            message.send('dismiss');
                        }
                    }), 'view');
                });
                controller.get('controllers.alerts').send('alert', message);
            }
        },
        function(reason) {
            var errors = reason.errors;
            for (var i in reason.errors) {
                var message = AlertMessage.create({
                    type: 'warning',
                    message: reason.errors[i]
                });
                controller.get('controllers.alerts').send('alert', message);
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
            if (!(composer.get('content') instanceof ComposerReply) || composer.get('content.discussion') !== discussion) {
                composer.switchContent(ComposerReply.create({
                    user: controller.get('session.user'),
                    discussion: discussion,
                    submit: function(data) {
                        controller.saveReply(discussion, data);
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
