import Ember from 'ember';

export default Ember.Component.extend({
	classNames: ['loading-indicator'],

	layout: Ember.Handlebars.compile('&nbsp;'),
	size: 'small',

	didInsertElement: function() {
		var size = this.get('size');
		Ember.$.fn.spin.presets[size].zIndex = 'auto';
		this.$().spin(size);
	}
});
