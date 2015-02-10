import Ember from 'ember';

import ModalController from 'flarum/mixins/modal-controller';

export default Ember.Controller.extend(ModalController, {
  actions: {
    submit: function() {
      var data = this.getProperties('username', 'email', 'password');
      var controller = this;
      this.set('error', null);
      this.set('loading', true);

      var user = this.store.createRecord('user', data);

      return user.save().then(function() {
        controller.get('session').authenticate('authenticator:flarum', {
          identification: data.email,
          password: data.password
        }).then(function() {
          controller.send('closeModal');
          controller.send('sessionChanged');
          controller.set('loading', false);
        });
      }, function(reason) {
        controller.set('loading', false);
      });
    }
  }
});
