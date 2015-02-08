import Ember from 'ember';

import DiscussionResult from '../models/discussion-result';
import PostResult from '../models/post-result';
import PaneableMixin from '../mixins/paneable';
import ComposerDiscussion from '../components/discussions/composer-discussion';
import AlertMessage from '../components/alert-message';

export default Ember.Controller.extend(Ember.Evented, PaneableMixin, {
	needs: ['application', 'composer', 'alerts', 'index/index', 'discussion'],

	index: Ember.computed.alias('controllers.index/index'),

	paneDisabled: Ember.computed.not('index.model.length'),

	saveDiscussion: function(data) {
        var controller = this;
        var composer = this.get('controllers.composer');
        var stream = this.get('stream');

        composer.set('content.loading', true);
        controller.get('controllers.alerts').send('clearAlerts');

        var discussion = this.store.createRecord('discussion', {
            title: data.title,
            content: data.content
        });

        return discussion.save().then(function(discussion) {
            composer.send('hide');
            controller.get('index').set('model', null).send('refresh');
            controller.transitionToRoute('discussion', discussion);
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
		transitionFromBackButton: function() {
			this.transitionToRoute('index');
		},

		loadMore: function() {
			this.get('index').send('loadMore');
		},

		newDiscussion: function() {
            var controller = this;
            var composer = this.get('controllers.composer');

            // If the composer is already set up for starting a discussion, then we
            // don't need to change its content - we can just show it.
            if (!(composer.get('content') instanceof ComposerDiscussion)) {
                composer.switchContent(ComposerDiscussion.create({
                    user: controller.get('session.user'),
                    submit: function(data) {
                        controller.saveDiscussion(data);
                    }
                }));
            }

            composer.send('show');
        }
	}
});
