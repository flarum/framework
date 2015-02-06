import Ember from 'ember';

import TaggedArray from '../../utils/tagged-array';
import { PositionEnum } from '../../controllers/composer';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.Component.extend(Ember.Evented, {
	layoutName: 'components/discussions/composer-body',

	submitLabel: 'Post Discussion',
	titlePlaceholder: 'Discussion Title',
	placeholder: '',
	title: '',
	content: '',
	submit: null,
	loading: false,

	disabled: Ember.computed.equal('composer.position', PositionEnum.MINIMIZED),

	didInsertElement: function() {
		var controls = TaggedArray.create();
		this.trigger('populateControls', controls);
		this.set('controls', controls);
	},

	populateControls: function(controls) {
		var title = Ember.Component.create({
			tagName: 'h3',
			layout: precompileTemplate('{{ui/controls/text-input value=component.title class="form-control" placeholder=component.titlePlaceholder disabled=component.disabled}}'),
			component: this
		});
		controls.pushObjectWithTag(title, 'title');
	},

	actions: {
		submit: function(content) {
			this.get('submit')({
				title: this.get('title'),
				content: content
			});
		},

		willExit: function(abort) {
			// If the user has typed something, prompt them before exiting
			// this composer state.
			if ((this.get('title') || this.get('content')) && !confirm('You have not posted your discussion. Do you wish to discard it?')) {
				abort();
			}
		},

		reset: function() {
			this.set('loading', false);
			this.set('content', '');
		}
	}
});
