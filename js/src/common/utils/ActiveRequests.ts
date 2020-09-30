export default class ActiveRequests extends Map {
  abort(requestId: number) {
    const xhr: XMLHttpRequest = this.get(requestId);
    if (!xhr) return;
    xhr.abort();
    this.delete(requestId);
  }
}
