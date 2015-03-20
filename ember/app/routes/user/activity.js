import Ember from 'ember';

import PushesHistory from 'flarum/mixins/pushes-history';

export default Ember.Route.extend(PushesHistory, {
  historyKey: 'user',

  model: function() {
    return Ember.RSVP.resolve(Ember.ArrayProxy.create());
  },

  setupController: function(controller, model) {
    this._super(controller, model);

    controller.send('loadResults');
  }
});
