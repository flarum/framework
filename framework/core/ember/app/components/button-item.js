import Ember from 'ember';

export default Ember.View.extend({
	title: '',
	icon: '',
	class: '',
	action: null,

	tagName: 'a',
	classNames: ['btn'],
	classNameBindings: ['class', 'disabled'],

	layout: Ember.Handlebars.compile('{{#if view.icon}}{{fa-icon view.icon class="fa-fw"}} {{/if}}<span>{{view.title}}</span>'),

	click: function() {
		this.action();
	}
});
