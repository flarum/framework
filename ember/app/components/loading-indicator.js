import Ember from 'ember';

export default Ember.Component.extend({

	classNames: ['loading'],

	layout: Ember.Handlebars.compile('&nbsp;'),

	didInsertElement: function() {
		this.$().spin(this.get('size'));
	}

});
