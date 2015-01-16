import Ember from 'ember';

export default Ember.Controller.extend({

	// The title of the forum.
	// TODO: Preload this value in the index.html payload from Laravel config.
	forumTitle: 'Ninetech Support Forum',

	// The title of the current page. This should be set as appropriate in
	// controllers/views.
	pageTitle: '',

	// When either the forum title or the page title changes, we want to
	// refresh the document's title.
	updateTitle: function() {
		var parts = [this.get('forumTitle')];
		var pageTitle = this.get('pageTitle');
		if (pageTitle) {
			parts.unshift(pageTitle);
		}
		document.title = parts.join(' - ');
	}.observes('pageTitle', 'forumTitle'),

	// Whether or not a pane is currently pinned to the side of the interface.
	panePinned: false,

	searchQuery: '',
	searchActive: false,

	actions: {
		search: function(query) {
			this.transitionToRoute('index', {queryParams: {searchQuery: query, sort: query ? 'relevance' : 'recent'}});
		}
	}
});
