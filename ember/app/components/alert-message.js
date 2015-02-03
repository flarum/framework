import Ember from 'ember';

import TaggedArray from '../utils/tagged-array';
import ActionButton from 'flarum/components/ui/controls/action-button';

export default Ember.Component.extend(Ember.Evented, {
	message: '',
	type: '',
	dismissable: true,

	layoutName: 'components/alert-message',
	classNames: ['alert'],
	classNameBindings: ['classForType'],

	classForType: function() {
		return 'alert-'+this.get('type');
	}.property('type'),

	didInsertElement: function() {
		var controls = TaggedArray.create();
		this.trigger('populateControls', controls);
		this.set('controls', controls);
	},

	populateControls: function(controls) {
		if (this.get('dismissable')) {
			var component = this;
			var dismiss = ActionButton.create({
				icon: 'times',
				className: 'btn btn-icon btn-link',
				action: function() {
					component.send('dismiss');
				}
			});
			controls.pushObjectWithTag(dismiss, 'dismiss');
		}
	},
 
 	actions: {
		dismiss: function() {
			this.sendAction('dismiss', this);
		}
	}
});
