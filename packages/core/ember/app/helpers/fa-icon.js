import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(icon, options) {
	return new Handlebars.SafeString('<i class="fa fa-icon fa-'+icon+' '+(options.hash.class || '')+'"></i>');
});

