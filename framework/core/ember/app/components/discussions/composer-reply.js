import Ember from 'ember';

import TaggedArray from '../../utils/tagged-array';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.Component.extend(Ember.Evented, {
	layoutName: 'components/discussions/composer-body',

	placeholder: 'Write your reply...',
	submitLabel: 'Post Reply',
	value: '',

	didInsertElement: function() {
		var headerItems = TaggedArray.create();
		this.trigger('populateHeader', headerItems);
		this.set('headerItems', headerItems);
	},

	populateHeader: function(header) {
		var title = Ember.Component.create({
			tagName: 'h3',
			layout: precompileTemplate('Replying to <em>{{component.discussion.title}}</em>'),
			component: this
		});
		header.pushObjectWithTag(title, 'title');
	},

	actions: {
		submit: function(value) {
			this.get('submit').call(this, value);
		},
		willExit: function(abort) {
			if (this.get('value') && ! confirm('You have not posted your reply. Do you wish to discard it?')) {
				abort();
			}
		},
		reset: function() {
			this.set('loading', false);
			this.set('value', '');
		},
	}
});
