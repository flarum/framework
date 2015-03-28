import Ember from 'ember';

import ModalView from 'flarum/mixins/modal-view';

export default Ember.View.extend(ModalView, {
  classNames: ['modal-dialog', 'modal-sm', 'modal-login'],
  templateName: 'login',

  didInsertElement: function() {
    this.get('controller.session').on('sessionAuthenticationSucceeded', this, this.hide);

    this.get('controller').on('refocus', this, this.refocus);
  },

  refocus: function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      this.$('input[name=password]').select();
    });
  },

  willDestroyElement: function() {
    this.get('controller.session').off('sessionAuthenticationSucceeded', this, this.hide);

    this.get('controller').off('refocus', this, this.refocus);
  }
});
