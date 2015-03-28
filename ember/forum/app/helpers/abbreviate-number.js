import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(number) {
  return new Ember.Handlebars.SafeString(''+number);
});

