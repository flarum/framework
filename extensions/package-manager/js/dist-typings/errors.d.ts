import type RequestError from 'flarum/common/utils/RequestError';
import type { ErrorPayload } from './types';
/** The body of a refused request, whatever shape the server sent it in. */
export declare function errorPayload(e: unknown): ErrorPayload;
/**
 * The server's own explanation of a refusal, as plain text, or null if it gave none.
 *
 * The server's words are kept on purpose: every refusal it sends names the
 * situation — a host too small, a run already going, a value that is not a
 * hostname — and "could not save" throws away the only part anybody can act on.
 */
export declare function serverMessage(e: unknown): string | null;
/**
 * An `errorHandler` for `app.request` that shows why a request failed through
 * Flarum's alerts: the server's words when it sent any, otherwise the string
 * `fallbackKey` names. It replaces Flarum's generic alert rather than adding a
 * second one beside it.
 */
export declare function alertError(fallbackKey: string): (e: RequestError) => void;
/**
 * An `errorHandler` for a request that repeats on a timer, such as the run
 * panel's polling: core's alert for the first failure, and nothing more until
 * `clear()` says the requests are getting through again.
 *
 * Core's own handler fires on every failed request, so an expired session or a
 * proxy answering 502 added one alert per poll, and in debug mode opened its
 * error modal over the page each time. Core's alert is reused as it is, so the
 * message and, in debug mode, the button that opens the details stay core's.
 * Dismissing it does not bring it back during the same run of failures.
 */
export declare function alertOnce(): {
    handler: (e: RequestError) => void;
    clear: () => void;
};
