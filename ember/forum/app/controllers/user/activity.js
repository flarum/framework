import Ember from 'ember';

export default Ember.Controller.extend({
  needs: ['user'],

  queryParams: ['filter'],
  filter: '',

  resultsLoading: false,

  moreResults: true,

  loadCount: 10,

  getResults: function(start) {
    var type;
    switch (this.get('filter')) {
      case 'discussions':
        type = 'discussion';
        break;

      case 'posts':
        type = 'post';
        break;
    }
    var controller = this;
    return this.store.find('activity', {
      users: this.get('controllers.user.model.id'),
      type: type,
      start: start,
      count: this.get('loadCount')
    }).then(function(results) {
      controller.set('moreResults', results.get('length') >= controller.get('loadCount'));
      return results;
    });
  },

  paramsDidChange: Ember.observer('filter', function() {
    if (this.get('model') && !this.get('resultsLoading')) {
      Ember.run.once(this, this.loadResults);
    }
  }),

  loadResults: function() {
    this.send('loadResults');
  },

  actions: {
    loadResults: function() {
      var controller = this;
      controller.get('model').set('content', []);
      controller.set('resultsLoading', true);
      controller.getResults().then(function(results) {
        controller
          .set('resultsLoading', false)
          .set('meta', results.get('meta'))
          .set('model.content', results);
      });
    },

    loadMore: function() {
      var controller = this;
      this.set('resultsLoading', true);
      this.getResults(this.get('model.length')).then(function(results) {
        controller.get('model.content').addObjects(results);
        controller.set('meta', results.get('meta'));
        controller.set('resultsLoading', false);
      });
    },
  }
});
