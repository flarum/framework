import Ember from 'ember';

import ModalViewMixin from '../mixins/modal-view';

export default Ember.View.extend(ModalViewMixin, {
	classNames: ['modal-dialog', 'modal-sm', 'modal-signup'],
	templateName: 'signup',

	didInsertElement: function() {
	}
});
