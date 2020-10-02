/**
 * The `ActiveRequests` class keeps track of pending XHR requests.
 */
export default class ActiveRequests extends Map {
  /**
   * Aborts a request. Aborted request promises neither get rejected
   * nor resolved, they remain pending.
   */
  abort(requestId: number) {
    const xhr: XMLHttpRequest = this.get(requestId);
    if (!xhr) return;
    xhr.abort();
    this.delete(requestId);
  }
}
