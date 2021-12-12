import app from '../common/app';
import User from './models/User';
import { FlarumRequestOptions } from './Application';

export type LoginParams = {
  /**
   * The username/email
   */
  identification: string;
  password: string;
  remember: boolean;
};

/**
 * The `Session` class defines the current user session. It stores a reference
 * to the current authenticated user, and provides methods to log in/out.
 */
export default class Session {
  /**
   * The current authenticated user.
   */
  user: User | null;

  /**
   * The CSRF token.
   */
  csrfToken: string;

  constructor(user: User | null, csrfToken: string) {
    this.user = user;
    this.csrfToken = csrfToken;
  }

  /**
   * Attempt to log in a user.
   */
  login(body: LoginParams, options: Omit<FlarumRequestOptions<any>, 'url' | 'body' | 'method'> = {}) {
    return app.request({
      method: 'POST',
      url: `${app.forum.attribute('baseUrl')}/login`,
      body,
      ...options,
    });
  }

  /**
   * Log the user out.
   */
  logout() {
    window.location.href = `${app.forum.attribute('baseUrl')}/logout?token=${this.csrfToken}`;
  }
}
