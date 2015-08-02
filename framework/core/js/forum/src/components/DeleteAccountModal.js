import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

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
    return app.trans('core.delete_account');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="helpText">
            <p>{app.trans('core.delete_account_help')}</p>
            <ul>
              <li>{app.trans('core.username_will_be_released')}</li>
              <li>{app.trans('core.posts_will_remain')}</li>
            </ul>
          </div>
          <div className="Form-group">
            <input className="FormControl"
              name="confirm"
              placeholder="Type 'DELETE' to proceed"
              oninput={m.withAttr('value', this.confirmation)}/>
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              disabled: this.confirmation() !== 'DELETE',
              children: app.trans('core.delete_account')
            })}
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
