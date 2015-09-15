/**
 * The `Session` class defines the current user session. It stores a reference
 * to the current authenticated user, and provides methods to log in/out.
 */
export default class Session {
  constructor(token, user) {
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
     * @public
     */
    this.token = token;
  }

  /**
   * Attempt to log in a user.
   *
   * @param {String} identification The username/email.
   * @param {String} password
   * @return {Promise}
   * @public
   */
  login(identification, password) {
    return app.request({
      method: 'POST',
      url: app.forum.attribute('baseUrl') + '/login',
      data: {identification, password}
    })
      .then(() => window.location.reload());
  }

  /**
   * Log the user out.
   *
   * @public
   */
  logout() {
    window.location = app.forum.attribute('baseUrl') + '/logout?token=' + this.token;
  }

  /**
   * Apply an authorization header with the current token to the given
   * XMLHttpRequest object.
   *
   * @param {XMLHttpRequest} xhr
   * @public
   */
  authorize(xhr) {
    if (this.token) {
      xhr.setRequestHeader('Authorization', 'Token ' + this.token);
    }
  }
}
