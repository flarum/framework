import Ember from 'ember';
import ApplicationRouteMixin from 'simple-auth/mixins/application-route-mixin';

export default Ember.Route.extend(ApplicationRouteMixin, {

	actions: {
		login: function() {
			this.controllerFor('login').set('error', null);
			this.render('login', {
				into: 'application',
				outlet: 'modal'
			});
		},

		closeModal: function() {
			this.disconnectOutlet({
				outlet: 'modal',
				parentView: 'application'
			});
		},

		  sessionChanged: function() {
		    this.refresh();
		  }
	}

});