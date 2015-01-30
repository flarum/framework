import Ember from 'ember';

import AuthenticationControllerMixin from 'simple-auth/mixins/authentication-controller-mixin';

export default Ember.Controller.extend(AuthenticationControllerMixin, {

	authenticator: 'authenticator:flarum',

	actions: {
		authenticate: function() {
	      var data = this.getProperties('identification', 'password');
	      var controller = this;
	      this.set('error', null);
	      this.set('loading', true);
	      return this._super(data).then(function() {
	      	controller.send("sessionChanged");
	      }).catch(function(errors) {
	      	switch(errors[0].code) {
	      		case 'invalidLogin':
	      			controller.set('error', 'Your login details are incorrect.');
	      			break;

	      		default:
	      			controller.set('error', 'Something went wrong. (Error code: '+errors[0].code+')');
	      	}
	      }).finally(function() {
	      	controller.set('loading', false);
	      });
	    }
	 }
	
});