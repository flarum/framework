import Ember from 'ember';

export default Ember.Component.extend({

	tagName: 'header',
	classNames: ['hero', 'welcome-hero'],

	actions: {
		close: function() {
			this.$().slideUp();
		}
	}

});