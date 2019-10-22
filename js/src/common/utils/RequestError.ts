import Mithril from "mithril";

import Alert from "../components/Alert";

export default class RequestError {
    status: number;
    responseText: string;
    options: Mithril.RequestOptions;
    xhr: XMLHttpRequest;
    response?: JSON;
    alert?: Alert;

    constructor(status, responseText, options, xhr) {
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
