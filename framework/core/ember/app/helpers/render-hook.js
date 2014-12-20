import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(name, options) {
	return new Handlebars.SafeString('');
});

