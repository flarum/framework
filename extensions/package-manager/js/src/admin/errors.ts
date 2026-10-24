import app from 'flarum/admin/app';
import type RequestError from 'flarum/common/utils/RequestError';
import type { ErrorPayload } from './types';

/** The body of a refused request, whatever shape the server sent it in. */
export function errorPayload(e: unknown): ErrorPayload {
  const response = (e as RequestError | null)?.response;

  return response && typeof response === 'object' ? (response as ErrorPayload) : {};
}

/**
 * The server's own explanation of a refusal, as plain text, or null if it gave none.
 *
 * The server's words are kept on purpose: every refusal it sends names the
 * situation — a host too small, a run already going, a value that is not a
 * hostname — and "could not save" throws away the only part anybody can act on.
 */
export function serverMessage(e: unknown): string | null {
  const body = errorPayload(e);
  const details = (body.errors || []).map((error) => error.detail).filter((detail): detail is string => !!detail);

  if (details.length) return details.join(' ');

  // A refusal sent as one of this extension's keys, relative to its admin strings.
  if (body.errorKey) {
    return app.translator.trans('flarum-extension-manager.admin.' + body.errorKey, body.errorParams || {}, true);
  }

  return body.error || null;
}

/**
 * An `errorHandler` for `app.request` that shows why a request failed through
 * Flarum's alerts: the server's words when it sent any, otherwise the string
 * `fallbackKey` names. It replaces Flarum's generic alert rather than adding a
 * second one beside it.
 */
export function alertError(fallbackKey: string) {
  return (e: RequestError): void => {
    app.alerts.show({ type: 'error' }, serverMessage(e) || app.translator.trans(fallbackKey, {}, true));
  };
}
