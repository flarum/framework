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
