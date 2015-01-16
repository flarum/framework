import Ember from 'ember';

export default Ember.Component.extend({

	tagName: 'header',
	classNames: ['hero', 'welcome-hero'],

	didInsertElement: function() {
		var hero = this.$();
		hero.find('.close').click(function() {
        	hero.slideUp();
        });
	}

});