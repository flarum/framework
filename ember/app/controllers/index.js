import Ember from 'ember';

import DiscussionResult from 'flarum/models/discussion-result';
import PostResult from 'flarum/models/post-result';
import Paneable from 'flarum/mixins/paneable';
import ComposerDiscussion from 'flarum/components/composer/composer-discussion';
import AlertMessage from 'flarum/components/ui/alert-message';
import UseComposer from 'flarum/mixins/use-composer';

export default Ember.Controller.extend(UseComposer, Paneable, {
  needs: ['application', 'index/index', 'discussion'],
  composer: Ember.inject.controller('composer'),
  alerts: Ember.inject.controller('alerts'),

  index: Ember.computed.alias('controllers.index/index'),

  paneDisabled: Ember.computed.not('index.model.length'),

  saveDiscussion: function(data) {
    var discussion = this.store.createRecord('discussion', {
      title: data.title,
      content: data.content
    });

    var controller = this;
    return this.saveAndDismissComposer(discussion).then(function(discussion) {
      controller.get('index').send('loadResults');
      controller.transitionToRoute('discussion', discussion);
    });
  },

  actions: {
    transitionFromBackButton: function() {
      this.transitionToRoute('index');
    },

    loadMore: function() {
      this.get('index').send('loadMore');
    },

    markAllAsRead: function() {
      var user = this.get('session.user');
      user.set('readTime', new Date);
      user.save();
    },

    newDiscussion: function() {
      var controller = this;
      this.showComposer(function() {
        return ComposerDiscussion.create({
          user: controller.get('session.user'),
          submit: function(data) {
            controller.saveDiscussion(data);
          }
        });
      });
    },

    discussionRemoved: function(discussion) {
      if (this.get('controllers.discussion.model') === discussion) {
        this.transitionToRoute('index');
      }
      this.get('index').send('discussionRemoved', discussion);
    }
  }
});
