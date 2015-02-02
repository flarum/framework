import Ember from 'ember';

export default Ember.Route.extend({

	queryParams: {
		start: {replace: true}
	},

	model: function(params) {
		return this.store.find('discussion', params.id);
	},

	resetController: function(controller) {
		// Whenever we exit the discussion view, or transition to a different
		// discussion, we want to reset the query params so that they don't stick.
		controller.set('start', '1');
		controller.set('searchQuery', '');
		controller.set('loaded', false);
		controller.set('stream', null);
	},

	setupController: function(controller, model) {
		controller.setup(model);

		// Tell the discussions controller that the discussions list should be
		// displayed as a pane, hidden on the side of the screen. Also set the
		// application back button's target as the discussions controller.
		this.controllerFor('index').set('paned', true);
		this.controllerFor('application').set('backButtonTarget', this.controllerFor('index'));
	},

	actions: {

		queryParamsDidChange: function(params) {
			// If the ?start param has changed, we want to tell the view to
			// tell the streamContent component to jump to this start point.
			// We postpone running this code until the next run loop because
			// when transitioning directly from one discussion to another,
			// queryParamsDidChange is fired before the controller is reset.
			// Thus, controller.loaded would still be true and the
			// startWasChanged event would be triggered inappropriately.
			var controller = this.get('controller'),
			    oldStart = parseInt(this.get('controller.start'));
			Ember.run.next(function() {
				if (! params.start || ! controller || ! controller.get('loaded') || parseInt(params.start) === oldStart) {
					return;
				}
				controller.trigger('startWasChanged', params.start);
			});
		},

		willTransition: function() {
			// When we transition into a new discussion, we want to hide the
			// discussions list pane. This means that when the user selects a
			// different discussion within the pane, the pane will slide away.
			this.controllerFor('index').set('paneShowing', false);
		},

	    didTransition: function() {
	    	this.controllerFor('composer').send('minimize');
	    }

	}
});
