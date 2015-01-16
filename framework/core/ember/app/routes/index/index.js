import Ember from 'ember';

export default Ember.Route.extend({

	// When we enter the discussions list view, we no longer want the
	// discussions list to be in pane mode.
    setupController: function(controller, model) {
        this.controllerFor('index').set('paned', false);
        this.controllerFor('index').set('paneShowing', false);
        this._super(controller, model);
    }

});
