import Ember from 'ember';

import DiscussionResult from '../../models/discussion-result';
import PostResult from '../../models/post-result';

export default Ember.Controller.extend({
	needs: ['application'],

	queryParams: ['sort', 'show', {searchQuery: 'q'}, 'filter'],
	sort: 'recent',
	show: 'discussions',
	filter: '',
	searchQuery: '',

	meta: null,
	resultsLoading: false,

	sortOptions: [
		{key: 'recent', label: 'Recent', sort: 'recent'},
		{key: 'replies', label: 'Replies', sort: '-replies'},
		{key: 'newest', label: 'Newest', sort: '-created'},
		{key: 'oldest', label: 'Oldest', sort: 'created'},
	],

	terminalPostType: function() {
		return ['newest', 'oldest'].indexOf(this.get('sort')) !== -1 ? 'start' : 'last';
	}.property('sort'),

	countType: function() {
		return this.get('sort') === 'replies' ? 'replies' : 'unread';
	}.property('sort'),

	moreResults: function() {
		return !!this.get('meta.moreUrl');
	}.property('meta.moreUrl'),

	getResults: function(start) {
		var searchQuery = this.get('searchQuery');
		var sort = this.get('sort');
		var sortOptions = this.get('sortOptions');
		var sortOption = sortOptions.findBy('key', sort) || sortOptions.objectAt(0);

		var params = {
			sort: sortOption.sort,
			q: searchQuery,
			start: start
		};

		if (this.get('show') === 'posts') {
			if (searchQuery) {
				params.include = 'relevantPosts';
			} else if (sort === 'created') {
				params.include = 'startPost,startUser';
			} else {
				params.include = 'lastPost,lastUser';
			}
		}

		return this.store.find('discussion', params).then(function(discussions) {
			var results = Ember.A();
			discussions.forEach(function(discussion) {
				var relevantPosts = Ember.A();
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

	searchQueryDidChange: function() {
		this.get('controllers.application').set('searchQuery', this.get('searchQuery'));
		this.get('controllers.application').set('searchActive', !! this.get('searchQuery'));

		var sortOptions = this.get('sortOptions');

		if (this.get('searchQuery') && sortOptions[0].sort !== 'relevance') {
			sortOptions.unshiftObject({key: 'relevance', label: 'Relevance', sort: 'relevance'});
		} else if (!this.get('searchQuery') && sortOptions[0].sort === 'relevance') {
			sortOptions.shiftObject();
		}
	}.observes('searchQuery'),

	paramsDidChange: function() {
		if (this.get('model')) {
			this.send('refresh');
		}
	}.observes('sort', 'show', 'searchQuery'),

	actions: {
		loadMore: function() {
			var controller = this;
			this.set('resultsLoading', true);
			this.getResults(this.get('model.length')).then(function(results) {
				controller.get('model').addObjects(results);
				controller.set('meta', results.get('meta'));
				controller.set('resultsLoading', false);
			});
		}
	}
});
