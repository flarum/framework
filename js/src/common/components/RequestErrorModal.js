import Modal from './Modal';

export default class RequestErrorModal extends Modal {
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
    if (formattedError) {
      responseText = formattedError.join('\n\n');
    } else {
      try {
        const json = error.response || JSON.parse(error.responseText);

        responseText = JSON.stringify(json, null, 2);
      } catch (e) {
        responseText = error.responseText;
      }
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
