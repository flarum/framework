import Ember from 'ember';

import DiscussionResult from '../models/discussion-result';
import PostResult from '../models/post-result';
import PaneableMixin from '../mixins/paneable';

export default Ember.ArrayController.extend(Ember.Evented, PaneableMixin, {

	needs: ['application', 'composer'],

	count: function() {
		return this.get('model.length');
	}.property('model.@each'),

	queryParams: ['sort', 'show', {searchQuery: 'q'}, 'filter'],
	sort: 'recent',
	show: 'discussions',
	filter: '',

	searchQuery: '',
	loadingMore: false,

	sortOptions: [
		{sort: 'recent', label: 'Recent'},
		{sort: 'replies', label: 'Replies'},
		{sort: 'newest', label: 'Newest'},
		{sort: 'oldest', label: 'Oldest'},
	],

	terminalPostType: function() {
		return ['newest', 'oldest'].indexOf(this.get('sort')) != -1 ? 'start' : 'last';
	}.property('sort'),

	countType: function() {
		return this.get('sort') == 'replies' ? 'replies' : 'unread';
	}.property('sort'),

	discussionsCount: function() {
		return this.get('model.length');
	}.property('@each'),

	resultsLoading: false,

	start: 0,

	moreResults: function() {
		return !! this.get('meta.moreUrl');
	}.property('meta.moreUrl'),

	meta: null,

	getResults: function(start) {
		var sort = this.get('sort');
		// var order = this.get('order');
		var order;
		var show = this.get('show');
		var searchQuery = this.get('searchQuery');

		if (sort == 'newest') {
			sort = 'created';
			order = 'desc';
		} else if (sort == 'oldest') {
			sort = 'created';
		}
		else if (sort == 'recent') {
			sort = '';
		}
		else if (sort == 'replies') {
			order = 'desc';
		}

		var params = {
			sort: (order == 'desc' ? '-' : '')+sort,
			q: searchQuery,
			start: start
		};

		if (show == 'posts') {
			if (searchQuery) params.include = 'relevantPosts';
			else if (sort == 'created') params.include = 'startPost,startUser';
			else params.include = 'lastPost,lastUser';
		}

		return this.store.find('discussion', params).then(function(discussions) {
			var results = Em.A();
			discussions.forEach(function(discussion) {
				var relevantPosts = Em.A();
				// discussion.get('relevantPosts.content').forEach(function(post) {
				// 	relevantPosts.pushObject(PostResult.create(post));
				// });
				results.pushObject(DiscussionResult.create({
					content: discussion,
					relevantPosts: relevantPosts,
					lastPost: PostResult.create(discussion.get('lastPost')),
					startPost: PostResult.create(discussion.get('startPost'))
				}));
				results.set('meta', discussions.get('meta'));
			});
			return results;
		});
	},

	actions: {
		loadMore: function() {
			var self = this;
			this.set('start', this.get('length'));
			this.set('resultsLoading', true);

			this.getResults(this.get('start')).then(function(results) {
				self.get('model').addObjects(results);
				self.set('meta', results.get('meta'));
				self.set('resultsLoading', false);
			});
		},

		transitionFromBackButton: function() {
			this.transitionToRoute('index');
		}
	},

	searchQueryDidChange: function(q) {
		this.get('controllers.application').set('searchQuery', this.get('searchQuery'));
		this.get('controllers.application').set('searchActive', !! this.get('searchQuery'));

		var sortOptions = this.get('sortOptions');

		if (this.get('searchQuery') && sortOptions[0].sort != 'relevance') {
			sortOptions.unshiftObject({sort: 'relevance', label: 'Relevance'});
		}
		else if ( ! this.get('searchQuery') && sortOptions[0].sort == 'relevance') {
			sortOptions.shiftObject();
		}
	}.observes('searchQuery'),

	paramsDidChange: function(show) {
		this.set('start', 0);
	}.observes('show', 'sort', 'searchQuery')

});
