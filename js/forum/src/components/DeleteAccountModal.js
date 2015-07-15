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
    return 'modal-sm delete-account-modal';
  }

  title() {
    return 'Delete Account';
  }

  content() {
    return (
      <div className="modal-body">
        <div className="form-centered">
          <div className="help-text">
            <p>Hold up! If you delete your account, there&#39;s no going back. Keep in mind:</p>
            <ul>
              <li>Your username will be released, so someone else will be able to sign up with your name.</li>
              <li>All of your posts will remain, but no longer associated with your account.</li>
            </ul>
          </div>
          <div className="form-group">
            <input className="form-control"
              name="confirm"
              placeholder="Type &quot;DELETE&quot; to proceed"
              oninput={m.withAttr('value', this.confirmation)}/>
          </div>
          <div className="form-group">
            <button type="submit"
              className="btn btn-primary btn-block"
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
