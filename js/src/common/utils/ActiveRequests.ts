/**
 * The `ActiveRequests` class keeps track of pending XHR requests.
 */
export default class ActiveRequests extends Map {
  abort(requestId: number) {
    const xhr: XMLHttpRequest = this.get(requestId);
    if (!xhr) return;
    xhr.abort();
    this.delete(requestId);
  }
}
