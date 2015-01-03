import Ember from 'ember';

export default Ember.Route.extend({

    setupController: function(controller, model) {
        this.controllerFor('discussions').set('paneShowing', false);
        this.controllerFor('discussions').set('paned', false);
        this.controllerFor('application').set('showDiscussionStream', false);
        this._super(controller, model);
    }

});
