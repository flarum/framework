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
    constructor(user: User | null, csrfToken: string);
    /**
     * Attempt to log in a user.
     */
    login(body: LoginParams, options?: Omit<FlarumRequestOptions<any>, 'url' | 'body' | 'method'>): Promise<any>;
    /**
     * Log the user out.
     */
    logout(): void;
}
