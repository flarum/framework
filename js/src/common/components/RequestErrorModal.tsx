import type RequestError from '../utils/RequestError';
import Modal, { IInternalModalAttrs } from './Modal';

export interface IRequestErrorModalAttrs extends IInternalModalAttrs {
  error: RequestError;
  formattedError: string[];
}

export default class RequestErrorModal<CustomAttrs extends IRequestErrorModalAttrs = IRequestErrorModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'RequestErrorModal Modal--large';
  }

  title() {
    return this.attrs.error.xhr ? `${this.attrs.error.xhr.status} ${this.attrs.error.xhr.statusText}` : '';
  }

  content() {
    const { error, formattedError } = this.attrs;

    let responseText;

    // If the error is already formatted, just add line endings;
    // else try to parse it as JSON and stringify it with indentation
    if (formattedError.length) {
      responseText = formattedError.join('\n\n');
    } else if (error.response) {
      responseText = JSON.stringify(error.response, null, 2);
    } else {
      responseText = error.responseText;
    }

    return (
      <div className="Modal-body">
        <pre>
          {this.attrs.error.options.method} {this.attrs.error.options.url}
          <br />
          <br />
          {responseText}
        </pre>
      </div>
    );
  }
}
