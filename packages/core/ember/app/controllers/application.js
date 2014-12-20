import Ember from 'ember';

// import NotificationMessage from '../models/notification-message';

export default Ember.Controller.extend({

	needs: ['discussions'],

	// The title of the forum.
	// TODO: Preload this value in the index.html payload from Laravel config.
	forumTitle: 'Ninetech Support Forum',
	// forumTitle: '<img src="tv.png" height="24" style="vertical-align: baseline; margin-right: 5px"> TV Addicts',
	// forumTitle: '<img src="gametoaid.png" height="50">',
	// forumTitle: '<i class="fa fa-stethoscope" style="font-size: 140%"></i>&nbsp; Med Students Forum',
	pageTitle: '',
	documentTitle: function() {
		return this.get('pageTitle') || this.get('forumTitle');
	}.property('pageTitle', 'forumTitle'),

	_updateTitle: function() {
		var parts = [this.get('forumTitle')];
		var pageTitle = this.get('pageTitle');
		if (pageTitle) parts.unshift(pageTitle);
		document.title = parts.join(' - ');
	}.observes('pageTitle', 'forumTitle'),

	searchQuery: '',
	searchActive: false,

	showDiscussionStream: false,

	// notificationMessage: NotificationMessage.create({text: 'Sorry, you do not have permission to do that!', class: 'message-warning'}), // currently displaying notification message object

	currentUser: null,

	actions: {

		hideMessage: function() {
			this.set('notificationMessage', null);
		},

		search: function(query) {
			this.transitionToRoute('discussions', {queryParams: {searchQuery: query, sort: query ? 'relevance' : 'recent'}});
		},

	}

});
