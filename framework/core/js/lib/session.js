import mixin from 'flarum/utils/mixin';
import evented from 'flarum/utils/evented';

export default class Session extends mixin(class {}, evented) {
  constructor() {
    super();
    this.user = m.prop();
    this.token = m.prop();
  }

  login(identification, password) {
    var deferred = m.deferred();
    var self = this;
    m.request({
      method: 'POST',
      url: app.config['base_url']+'/login',
      data: {identification, password},
      background: true
    }).then(function(response) {
      self.token(response.token);
      m.startComputation();
      app.store.find('users', response.userId).then(function(user) {
        self.user(user);
        deferred.resolve(user);
        self.trigger('loggedIn', user);
        m.endComputation();
      });
    }, function(response) {
      deferred.reject(response);
    });
    return deferred.promise;
  }

  logout() {
    window.location = app.config.baseURL+'/logout';
  }

  authorize(xhr) {
    xhr.setRequestHeader('Authorization', 'Token '+this.token());
  }
}
