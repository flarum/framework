import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(user, options) {
  var username;
  if (user) {
    username = user.get('username');
  }
  username = username || '[deleted]';

  return new Ember.Handlebars.SafeString('<span class="username">'+Ember.Handlebars.Utils.escapeExpression(username)+'</span>');
});

