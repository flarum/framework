import Ember from 'ember';

import TaggedArray from '../../utils/tagged-array';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.Component.extend(Ember.Evented, {
	layoutName: 'components/discussions/composer-body',

	submitLabel: 'Post Reply',
	placeholder: '',
	content: '',
	submit: null,
	loading: false,

	didInsertElement: function() {
		var controls = TaggedArray.create();
		this.trigger('populateControls', controls);
		this.set('controls', controls);
	},

	populateControls: function(controls) {
		var title = Ember.Component.create({
			tagName: 'h3',
			layout: precompileTemplate('Replying to <em>{{component.discussion.title}}</em>'),
			component: this
		});
		controls.pushObjectWithTag(title, 'title');
	},

	actions: {
		submit: function(content) {
			this.get('submit')({
				content: content
			});
		},

		willExit: function(abort) {
			// If the user has typed something, prompt them before exiting
			// this composer state.
			if (this.get('content') && ! confirm('You have not posted your reply. Do you wish to discard it?')) {
				abort();
			}
		},

		reset: function() {
			this.set('loading', false);
			this.set('content', '');
		}
	}
});
