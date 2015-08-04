import Modal from 'flarum/components/Modal';

export default class AddExtensionModal extends Modal {
  className() {
    return 'AddExtensionModal Modal--small';
  }

  title() {
    return 'Add Extension';
  }

  content() {
    return (
      <div className="Modal-body">
        <p>One day in the not-too-distant future, this dialog will allow you to add an extension to your forum with ease. We're building an ecosystem as we speak!</p>
        <p>In the meantime, if you manage to get your hands on a new extension, simply drop it in your forum's <code>extensions</code> directory.</p>
        <p>If you're a developer, you can <a href="">read the docs</a> and have a go at building your own.</p>
      </div>
    );
  }
}
