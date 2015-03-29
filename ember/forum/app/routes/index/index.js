import Ember from 'ember';

import AddCssClassToBody from 'flarum-forum/mixins/add-css-class-to-body';
import PushesHistory from 'flarum-forum/mixins/pushes-history';

export default Ember.Route.extend(AddCssClassToBody, PushesHistory, {
  historyKey: 'index',

  cachedModel: null,

  model: function() {
    if (!this.get('cachedModel')) {
      this.set('cachedModel', Ember.ArrayProxy.create());
    }
    return Ember.RSVP.resolve(this.get('cachedModel'));
  },

  setupController: function(controller, model) {
    this._super(controller, model);

    if (!model.get('length')) {
      controller.send('loadResults');
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
