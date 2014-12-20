import Ember from 'ember';

export default Ember.Component.extend({
 
	close: function() {
		this.sendAction('closeAction');
	}

});
