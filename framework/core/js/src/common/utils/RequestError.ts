export default class RequestError {
  status: string;
  options: object;
  xhr: XMLHttpRequest;

  responseText: string | null;
  response: object | null;

  alert: any;

  constructor(status: string, responseText: string | null, options: object, xhr: XMLHttpRequest) {
    this.status = status;
    this.responseText = responseText;
    this.options = options;
    this.xhr = xhr;

    try {
      this.response = JSON.parse(responseText);
    } catch (e) {
      this.response = null;
    }

    this.alert = null;
  }
}
