import Ember from 'ember';

import PushesHistory from 'flarum-forum/mixins/pushes-history';

export default Ember.Route.extend(PushesHistory, {
  historyKey: 'user',

  model: function(params) {
    return this.store.find('user', params.username);
  },

  afterModel: function(model) {
    if (!model.get('joinTime')) {
      return model.reload();
    }
  }
});
