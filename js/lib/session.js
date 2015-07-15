import mixin from 'flarum/utils/mixin';
import evented from 'flarum/utils/evented';

/**
 * The `Session` class defines the current user session. It stores a reference
 * to the current authenticated user, and provides methods to log in/out.
 *
 * @extends evented
 */
export default class Session extends mixin(class {}, evented) {
  constructor(token, user) {
    super();

    /**
     * The current authenticated user.
     *
     * @type {User|null}
     * @public
     */
    this.user = user;

    /**
     * The token that was used for authentication.
     *
     * @type {String|null}
     */
    this.token = token;
  }

  /**
   * Attempt to log in a user.
   *
   * @param {String} identification The username/email.
   * @param {String} password
   * @return {Promise}
   */
  login(identification, password) {
    const deferred = m.deferred();

    app.request({
      method: 'POST',
      url: app.forum.attribute('baseUrl') + '/login',
      data: {identification, password}
    }).then(
      // FIXME: reload the page on success. Somehow serialize what the user's
      // intention was, and then perform that intention after the page reloads.
      response => {
        this.token = response.token;

        app.store.find('users', response.userId).then(user => {
          this.user = user;
          this.trigger('loggedIn', user);
          deferred.resolve(user);
        });
      },

      response => {
        deferred.reject(response);
      }
    );

    return deferred.promise;
  }

  /**
   * Log the user out.
   */
  logout() {
    window.location = app.forum.attribute('baseUrl') + '/logout?token=' + this.token;
  }

  /**
   * Apply an authorization header with the current token to the given
   * XMLHttpRequest object.
   *
   * @param {XMLHttpRequest} xhr
   */
  authorize(xhr) {
    xhr.setRequestHeader('Authorization', 'Token ' + this.token);
  }
}
