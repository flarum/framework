import Ember from 'ember';
import Discussion from '../models/discussion';

export default Ember.Route.extend({

	setupController: function(controller, model) {
		controller.set('model', model);

		if ( ! model.get('length')) {
			controller.set('resultsLoading', true);
		
			controller.getResults().then(function(results) {
				controller
					.set('resultsLoading', false)
					.set('meta', results.get('meta'))
					.set('model.content', results);
			});
		}
	},

	model: function(params) {
		var model = Ember.ArrayProxy.create();

		return Ember.RSVP.resolve(model);
	},

	actions: {
		queryParamsDidChange: function(newParams, params) {
			var self = this;
			Ember.run.scheduleOnce('afterRender', function() {
				self.refresh();
			});
		}
	}

});
