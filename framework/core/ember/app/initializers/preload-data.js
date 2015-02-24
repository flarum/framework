import Ember from 'ember';

export default {
  name: 'preload-data',
  after: 'store',
  initialize: function(container) {
    var store = container.lookup('store:main');
    if (!Ember.isEmpty(FLARUM_DATA)) {
      store.pushPayload(FLARUM_DATA);
    }
    if (!Ember.isEmpty(FLARUM_SESSION)) {
      FLARUM_SESSION.user = store.getById('user', FLARUM_SESSION.userId);
      container.lookup('simple-auth-session:main').setProperties({
          isAuthenticated: true,
          authenticator: 'authenticator:flarum',
          content: FLARUM_SESSION
      });
    }
  }
};
