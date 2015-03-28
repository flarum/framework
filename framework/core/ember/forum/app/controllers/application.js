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

  history: null,

  init: function() {
    this._super();
    this.set('history', []);
    this.pushHistory('index', '/');
  },

  pushHistory: function(name, url) {
    var url = url || this.get('target.url');
    var last = this.get('history').get('lastObject');
    if (last && last.name === name) {
      last.url = url;
    } else {
      this.get('history').pushObject({name: name, url: url});
    }
  },

  popHistory: function(name) {
    var last = this.get('history').get('lastObject');
    if (last && last.name === name) {
      this.get('history').popObject();
    }
  },

  canGoBack: Ember.computed('history.length', function() {
    return this.get('history.length') > 1;
  }),

  actions: {
    goBack: function() {
      this.get('history').popObject();
      var history = this.get('history').get('lastObject');
      this.transitionToRoute.call(this, history.url);
    },
    search: function(query) {
      this.transitionToRoute('index', {queryParams: {searchQuery: query, sort: query ? 'relevance' : 'recent'}});
    },
    toggleDrawer: function() {
      this.toggleProperty('drawerShowing');
    }
  }
});
