import Ember from 'ember';

import PostStream from '../models/post-stream';
import ComposerReply from '../components/discussions/composer-reply';
import ActionButton from '../components/ui/controls/action-button';

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

    saveReply: function(discussion, content) {
        var controller = this;
        var composer = this.get('controllers.composer');
        var stream = this.get('stream');

        composer.set('content.loading', true);

        var post = this.store.createRecord('post', {
            content: content,
            discussion: discussion
        });

        var promise = post.save().then(function(post) {
            if (discussion == controller.get('model')) {
                discussion.set('posts', discussion.get('posts')+','+post.get('id'));
                stream.set('ids', controller.get('model.postIds'));
                stream.addPostToEnd(post);
            }
            composer.send('hide');
        }, function(reason) {
            var error = reason.errors[0].detail;
            alert(error);
        });

        promise.finally(function() {
            composer.set('content.loading', false);
        });

        return promise;
    },

    actions: {
        reply: function() {
            var controller = this;
            var discussion = this.get('model');
            var composer = this.get('controllers.composer');
            if (composer.get('content.discussion') != discussion) {
                composer.switchContent(ComposerReply.create({
                    user: controller.get('session.user'),
                    discussion: discussion,
                    submit: function(value) {
                        controller.saveReply(this.get('discussion'), value);
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
