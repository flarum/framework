import Modal from 'flarum/components/Modal';

export default class RequestErrorModal extends Modal {
  className() {
    return 'RequestErrorModal Modal--large';
  }

  title() {
    return this.props.error.message;
  }

  content() {
    let responseText;

    try {
      responseText = JSON.stringify(JSON.parse(this.props.error.responseText), null, 2);
    } catch (e) {
      responseText = this.props.error.responseText;
    }

    return <div className="Modal-body">
      <pre>{responseText}</pre>
    </div>;
  }
}
