/**
 * The `Session` class defines the current user session. It stores a reference
 * to the current authenticated user, and provides methods to log in/out.
 */
export default class Session {
    constructor(user: any, csrfToken: any);
    /**
     * The current authenticated user.
     *
     * @type {User|null}
     * @public
     */
    public user: any | null;
    /**
     * The CSRF token.
     *
     * @type {String|null}
     * @public
     */
    public csrfToken: string | null;
    /**
     * Attempt to log in a user.
     *
     * @param {String} identification The username/email.
     * @param {String} password
     * @param {Object} [options]
     * @return {Promise}
     * @public
     */
    public login(body: any, options?: Object | undefined): Promise<any>;
    /**
     * Log the user out.
     *
     * @public
     */
    public logout(): void;
}
