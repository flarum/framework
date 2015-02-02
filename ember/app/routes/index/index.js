import Ember from 'ember';

import AddCssClassToBodyMixin from '../../mixins/add-css-class-to-body';

export default Ember.Route.extend(AddCssClassToBodyMixin, {

	// When we enter the discussions list view, we no longer want the
	// discussions list to be in pane mode.
    setupController: function(controller, model) {
        this.controllerFor('index').set('paned', false);
        this.controllerFor('index').set('paneShowing', false);
        this._super(controller, model);
    },

    actions: {
	    didTransition: function() {
	    	// @todo only if it's not a new discussion
	    	this.controllerFor('composer').send('minimize');
	    }
	}
});
