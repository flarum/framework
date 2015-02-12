import Ember from 'ember';

import AddCssClassToBody from 'flarum/mixins/add-css-class-to-body';

export default Ember.Route.extend(AddCssClassToBody, {
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
      controller.send('refresh');
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
      var application = this.controllerFor('application');
      if (application.get('backButtonTarget') === this.controllerFor('index')) {
        application.set('backButtonTarget', null);
      }

      this.controllerFor('composer').send('minimize');
      this.controllerFor('index').set('paned', false);
      this.controllerFor('index').set('paneShowing', false);
    }
  }
});
