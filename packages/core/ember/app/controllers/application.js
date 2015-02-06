import Ember from 'ember';

export default Ember.Controller.extend({

	// The title of the forum.
	// TODO: Preload this value in the index.html payload from Laravel config.
	forumTitle: 'Flarum Demo Forum',

	// The title of the current page. This should be set as appropriate in
	// controllers/views.
	pageTitle: '',

	backButtonTarget: null,

	searchQuery: '',
	searchActive: false,

	alerts: [],

	actions: {
		search: function(query) {
			this.transitionToRoute('index', {queryParams: {searchQuery: query, sort: query ? 'relevance' : 'recent'}});
		},
		alert: function(message) {
			this.get('alerts').pushObject(message);
		},
		dismissAlert: function(message) {
			this.get('alerts').removeObject(message);
		},
		clearAlerts: function() {
			this.get('alerts').clear();
		}
	}
});
