import Ember from 'ember';

import ModalView from 'flarum/mixins/modal-view';

export default Ember.View.extend(ModalView, {
  classNames: ['modal-dialog', 'modal-sm', 'modal-signup'],
  templateName: 'signup',

  didInsertElement: function() {
  }
});
