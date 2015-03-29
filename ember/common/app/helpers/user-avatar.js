import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(user, options) {
  var attributes = 'class="avatar '+(options.hash.class || '')+'"';
  var content = '';

  if (user) {
    var username = user.get('username') || '?';

    if (typeof options.hash.title === 'undefined') {
      options.hash.title = Ember.Handlebars.Utils.escapeExpression(username);
    }
    attributes += ' title="'+options.hash.title+'"';

    var avatarUrl = user.get('avatarUrl');
    if (avatarUrl) {
      return new Ember.Handlebars.SafeString('<img src="'+avatarUrl+'" '+attributes+'>');
    }

    content = username.charAt(0).toUpperCase();
    attributes += ' style="background:'+user.get('color')+'"';
  }

  return new Ember.Handlebars.SafeString('<span '+attributes+'>'+content+'</span>');
}, 'avatarUrl', 'username', 'color');

