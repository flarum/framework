import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(number, options) {
	return new Ember.Handlebars.SafeString(''+number);
});

