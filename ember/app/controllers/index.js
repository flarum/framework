import Ember from 'ember';

import DiscussionResult from '../models/discussion-result';
import PostResult from '../models/post-result';
import PaneableMixin from '../mixins/paneable';

export default Ember.Controller.extend(Ember.Evented, PaneableMixin, {
	needs: ['application', 'composer', 'index/index', 'discussion'],

	index: Ember.computed.alias('controllers.index/index'),

	paneDisabled: Ember.computed.not('index.model.length'),

	actions: {
		transitionFromBackButton: function() {
			this.transitionToRoute('index');
		},

		loadMore: function() {
			this.get('index').send('loadMore');
		},

		newDiscussion: function() {
			var composer = this.get('controllers.composer');
            composer.set('minimized', false);
            composer.set('showing', true);
            composer.set('title', 'Discussion Title'); // needs to be editable
            composer.set('delegate', this);
		}
	}
});
