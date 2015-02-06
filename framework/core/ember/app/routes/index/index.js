import Ember from 'ember';

import AddCssClassToBodyMixin from '../../mixins/add-css-class-to-body';

export default Ember.Route.extend(AddCssClassToBodyMixin, {
	cachedModel: null,

	model: function() {
		if (!this.get('cachedModel')) {
			this.set('cachedModel', Ember.ArrayProxy.create());
		}
		return Ember.RSVP.resolve(this.get('cachedModel'));
	},

	setupController: function(controller, model) {
		controller.set('model', model);

		if (!model.get('length')) {
			controller.set('resultsLoading', true);
			controller.getResults().then(function(results) {
				controller
					.set('resultsLoading', false)
					.set('meta', results.get('meta'))
					.set('model.content', results);
			});
		}
	},

	deactivate: function() {
		this._super();
		this.controllerFor('application').set('backButtonTarget', this.controllerFor('index'));
	},
	
    actions: {
    	refresh: function() {
    		this.set('cachedModel', null);
    		this.refresh();
    	},

	    didTransition: function() {
	    	// @todo only if it's not a new discussion
	    	this.controllerFor('composer').send('minimize');
	    	this.controllerFor('index').set('paned', false);
	        this.controllerFor('index').set('paneShowing', false);
	    }
	}
});
