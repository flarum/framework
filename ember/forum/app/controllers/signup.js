import Ember from 'ember';

import ModalController from 'flarum/mixins/modal-controller';

export default Ember.Controller.extend(ModalController, {
  emailProviderName: Ember.computed('welcomeUser.email', function() {
    if (!this.get('welcomeUser.email')) { return; }
    return this.get('welcomeUser.email').split('@')[1];
  }),

  emailProviderUrl: Ember.computed('emailProviderName', function() {
    return 'http://'+this.get('emailProviderName');
  }),

  welcomeStyle: Ember.computed('welcomeUser.color', function() {
    return 'background:'+this.get('welcomeUser.color');
  }),

  actions: {
    submit: function() {
      var data = this.getProperties('username', 'email', 'password');
      var controller = this;
      this.set('error', null);
      this.set('loading', true);

      var user = this.store.createRecord('user', data);

      return user.save().then(function(user) {
        controller.set('welcomeUser', user);
        controller.set('loading', false);
        controller.send('saveState');
      }, function(reason) {
        controller.set('loading', false);
      });
    }
  }
});
