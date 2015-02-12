import Ember from 'ember';

import ComposerReply from 'flarum/components/composer/composer-reply';
import ActionButton from 'flarum/components/ui/action-button';
import AlertMessage from 'flarum/components/ui/alert-message';
import UseComposerMixin from 'flarum/mixins/use-composer';

export default Ember.Controller.extend(Ember.Evented, UseComposerMixin, {
  needs: ['application'],
  composer: Ember.inject.controller('composer'),
  alerts: Ember.inject.controller('alerts'),

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
    var post = this.store.createRecord('post', {
      content: data.content,
      discussion: discussion
    });

    var controller = this;
    var stream = this.get('stream');
    return this.saveAndDismissComposer(post).then(function(post) {
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
          message: 'Your reply was posted.',
          buttons: [{
            label: 'View',
            action: function() {
              controller.transitionToRoute('discussion', post.get('discussion'), {queryParams: {start: post.get('number')}});
            }
          }]
        });
        controller.get('alerts').send('alert', message);
      }
    });
  },

  // Whenever we transition to a different discussion or the logged-in user
  // changes, we'll need the composer content to refresh next time the reply
  // button is clicked.
  clearComposerContent: Ember.observer('model', 'session.user', function() {
    this.set('composerContent', undefined);
  }),

  actions: {
    reply: function() {
      var discussion = this.get('model');
      var controller = this;
      this.showComposer(function() {
        return ComposerReply.create({
          user: controller.get('session.user'),
          discussion: discussion,
          submit: function(data) {
            controller.saveReply(discussion, data);
          }
        });
      });
    },

    // This action is called when the start position of the discussion
    // currently being viewed changes (i.e. when the user scrolls up/down
    // the post stream.)
    positionChanged: function(startNumber, endNumber) {
      this.set('start', startNumber);

      var discussion = this.get('model');
      if (endNumber > discussion.get('readNumber')) {
        discussion.set('readNumber', endNumber);
        discussion.save();
      }
    },

    postRemoved: function(post) {
      this.get('stream').removePost(post);
    },

    rename: function(title) {
      var discussion = this.get('model');
      discussion.set('title', title);
      discussion.save();
    },

    delete: function() {
      var controller = this;
      this.get('model').destroyRecord().then(function() {
        controller.transitionToRoute('index');
      });
    }
  }
});
