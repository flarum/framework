import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(icon, options) {
  return new Ember.Handlebars.SafeString('<i class="fa fa-fw fa-'+icon+' '+(options.hash.class || '')+'"></i>');
});

