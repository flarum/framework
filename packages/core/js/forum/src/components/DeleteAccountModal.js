import Modal from 'flarum/components/Modal';

/**
 * The `DeleteAccountModal` component shows a modal dialog which allows the user
 * to delete their account.
 *
 * @todo require typing password instead of DELETE
 */
export default class DeleteAccountModal extends Modal {
  constructor(props) {
    super(props);

    /**
     * The value of the confirmation input.
     *
     * @type {Function}
     */
    this.confirmation = m.prop();
  }

  className() {
    return 'DeleteAccountModal Modal--small';
  }

  title() {
    return 'Delete Account';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="helpText">
            <p>Hold up! If you delete your account, there&#39;s no going back. Keep in mind:</p>
            <ul>
              <li>Your username will be released, so someone else will be able to sign up with your name.</li>
              <li>All of your posts will remain, but no longer associated with your account.</li>
            </ul>
          </div>
          <div className="Form-group">
            <input className="FormControl"
              name="confirm"
              placeholder="Type 'DELETE' to proceed"
              oninput={m.withAttr('value', this.confirmation)}/>
          </div>
          <div className="Form-group">
            <button type="submit"
              className="Button Button--primary Button--block"
              disabled={this.loading || this.confirmation() !== 'DELETE'}>
              Delete Account
            </button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.confirmation() !== 'DELETE') return;

    this.loading = true;

    app.session.user.delete().then(() => app.session.logout());
  }
}
