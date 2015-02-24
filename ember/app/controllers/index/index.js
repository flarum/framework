import Ember from 'ember';

import DiscussionResult from 'flarum/models/discussion-result';
import PostResult from 'flarum/models/post-result';

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

  terminalPostType: Ember.computed('sort', function() {
    return ['newest', 'oldest'].indexOf(this.get('sort')) !== -1 ? 'start' : 'last';
  }),

  countType: Ember.computed('sort', function() {
    return this.get('sort') === 'replies' ? 'replies' : 'unread';
  }),

  moreResults: Ember.computed.bool('meta.moreUrl'),

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

    // var results = Ember.RSVP.resolve(FLARUM_DATA.discussions);

    return this.store.find('discussion', params).then(function(discussions) {
      var results = Ember.A();
      discussions.forEach(function(discussion) {
        var relevantPosts = Ember.A();
        // discussion.get('relevantPosts.content').forEach(function(post) {
        //  relevantPosts.pushObject(PostResult.create(post));
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

  searchQueryDidChange: Ember.observer('searchQuery', function() {
    var searchQuery = this.get('searchQuery');
    this.get('controllers.application').setProperties({
      searchQuery: searchQuery,
      searchActive: !!searchQuery
    });

    var sortOptions = this.get('sortOptions');

    if (this.get('searchQuery') && sortOptions[0].sort !== 'relevance') {
      sortOptions.unshiftObject({key: 'relevance', label: 'Relevance', sort: 'relevance'});
    } else if (!this.get('searchQuery') && sortOptions[0].sort === 'relevance') {
      sortOptions.shiftObject();
    }
  }),

  paramsDidChange: Ember.observer('sort', 'show', 'searchQuery', function() {
    if (this.get('model')) {
      this.send('refresh');
    }
  }),

  actions: {
    loadMore: function() {
      var controller = this;
      this.set('resultsLoading', true);
      this.getResults(this.get('model.length')).then(function(results) {
        controller.get('model').addObjects(results);
        controller.set('meta', results.get('meta'));
        controller.set('resultsLoading', false);
      });
    },

    discussionRemoved: function(discussion) {
      var model = this.get('model');
      model.removeObject(model.findBy('content', discussion));
    },

    refresh: function() {
      var controller = this;
      controller.set('model', Ember.ArrayProxy.create());
      controller.set('resultsLoading', true);
      controller.getResults().then(function(results) {
        controller
          .set('resultsLoading', false)
          .set('meta', results.get('meta'))
          .set('model.content', results);
      });
    }
  }
});
