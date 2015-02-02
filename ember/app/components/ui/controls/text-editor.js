import Ember from 'ember';

import TaggedArray from '../../../utils/tagged-array';
import ActionButton from './action-button';

export default Ember.Component.extend({
	classNames: ['text-editor'],

	didInsertElement: function() {
		var controlItems = TaggedArray.create();
		this.trigger('populateControls', controlItems);
		this.set('controlItems', controlItems);

		var component = this;
		this.$('textarea').bind('keydown', 'meta+return', function() {
			component.send('submit');
		});
	},

	populateControls: function(controls) {
		var component = this;
		var submit = ActionButton.create({
			label: this.get('submitLabel'),
			className: 'btn btn-primary',
			action: function() {
				component.send('submit');
			}
		});
		controls.pushObjectWithTag(submit, 'submit');
	},

	actions: {
		submit: function() {
			this.sendAction('submit', this.get('value'));
		}
	}
});