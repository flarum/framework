import Ember from 'ember';

import TaggedArray from '../../utils/tagged-array';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.Component.extend(Ember.Evented, {
	layoutName: 'components/discussions/composer-body',

	submitLabel: 'Save Changes',
	placeholder: '',
	content: Ember.computed.oneWay('post.content'),
	originalContent: Ember.computed.oneWay('post.content'),
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
			layout: precompileTemplate('Editing Post #{{component.post.number}} in <em>{{discussion.title}}</em>'),
			discussion: this.get('post.discussion'),
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
			if (this.get('content') !== this.get('originalContent') && ! confirm('You have not saved your post. Do you wish to discard your changes?')) {
				abort();
			}
		}
	}
});
