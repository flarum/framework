import Base from 'simple-auth/authenticators/base';
import config from '../config/environment';

export default Base.extend({

  restore: function(data) {
    var container = this.container;
    return new Ember.RSVP.Promise(function(resolve, reject) {
      Ember.run.next(function() {
        container.lookup('store:main').find('user', data.userId).then(function(user) {
          resolve( { token: data.token, userId: data.userId, user: user } );
        });
      });
    });
  },

  authenticate: function(credentials) {
    var container = this.container;
    return new Ember.RSVP.Promise(function(resolve, reject) {
      Ember.$.ajax({
        url:  config.apiURL+'/auth/login',
        type: 'POST',
        data: { identification: credentials.identification, password: credentials.password }
      }).then(function(response) {
          container.lookup('store:main').find('user', response.userId).then(function(user) {
            resolve({ token: response.token, userId: response.userId, user: user });
          });
      }, function(xhr, status, error) {
          reject(xhr.responseJSON.errors);
      });
    });
  },

  // invalidate: function(data) {
  //     return new Ember.RSVP.Promise();
  // }
});