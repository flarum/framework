import Ember from 'ember';

export default Ember.Component.extend({
	classNames: ['loading-indicator'],

	layout: Ember.Handlebars.compile('&nbsp;'),
	size: 'small',

	didInsertElement: function() {
		this.$().spin(this.get('size'));
	}
});
