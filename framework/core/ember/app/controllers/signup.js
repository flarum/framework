import Ember from 'ember';

import AuthenticationControllerMixin from 'simple-auth/mixins/authentication-controller-mixin';
import ModalControllerMixin from '../mixins/modal-controller';

export default Ember.Controller.extend(ModalControllerMixin, AuthenticationControllerMixin, {
	authenticator: 'authenticator:flarum',

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
