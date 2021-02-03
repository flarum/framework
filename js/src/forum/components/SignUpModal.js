import Modal from '../../common/components/Modal';
import LogInModal from './LogInModal';
import Button from '../../common/components/Button';
import LogInButtons from './LogInButtons';
import extractText from '../../common/utils/extractText';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';

/**
 * The `SignUpModal` component displays a modal dialog with a singup form.
 *
 * ### Attrs
 *
 * - `username`
 * - `email`
 * - `password`
 * - `token` An email token to sign up with.
 */
export default class SignUpModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The value of the username input.
     *
     * @type {Function}
     */
    this.username = Stream(this.attrs.username || '');

    /**
     * The value of the email input.
     *
     * @type {Function}
     */
    this.email = Stream(this.attrs.email || '');

    /**
     * The value of the password input.
     *
     * @type {Function}
     */
    this.password = Stream(this.attrs.password || '');
  }

  className() {
    return 'Modal--small SignUpModal';
  }

  title() {
    return app.translator.trans('core.forum.sign_up.title');
  }

  content() {
    return [<div className="Modal-body">{this.body()}</div>, <div className="Modal-footer">{this.footer()}</div>];
  }

  isProvided(field) {
    return this.attrs.provided && this.attrs.provided.indexOf(field) !== -1;
  }

  body() {
    return [this.attrs.token ? '' : <LogInButtons />, <div className="Form Form--centered">{this.fields().toArray()}</div>];
  }

  fields() {
    const items = new ItemList();

    items.add(
      'username',
      <div className="Form-group">
        <input
          className="FormControl"
          name="username"
          type="text"
          placeholder={extractText(app.translator.trans('core.forum.sign_up.username_placeholder'))}
          bidi={this.username}
          disabled={this.loading || this.isProvided('username')}
        />
      </div>,
      30
    );

    items.add(
      'email',
      <div className="Form-group">
        <input
          className="FormControl"
          name="email"
          type="email"
          placeholder={extractText(app.translator.trans('core.forum.sign_up.email_placeholder'))}
          bidi={this.email}
          disabled={this.loading || this.isProvided('email')}
        />
      </div>,
      20
    );

    if (!this.attrs.token) {
      items.add(
        'password',
        <div className="Form-group">
          <input
            className="FormControl"
            name="password"
            type="password"
            placeholder={extractText(app.translator.trans('core.forum.sign_up.password_placeholder'))}
            bidi={this.password}
            disabled={this.loading}
          />
        </div>,
        10
      );
    }

    items.add(
      'submit',
      <div className="Form-group">
        <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
          {app.translator.trans('core.forum.sign_up.submit_button')}
        </Button>
      </div>,
      -10
    );

    return items;
  }

  footer() {
    return [
      <p className="SignUpModal-logIn">{app.translator.trans('core.forum.sign_up.log_in_text', { a: <a onclick={this.logIn.bind(this)} /> })}</p>,
    ];
  }

  /**
   * Open the log in modal, prefilling it with an email/username/password if
   * the user has entered one.
   *
   * @public
   */
  logIn() {
    const attrs = {
      identification: this.email() || this.username(),
      password: this.password(),
    };

    app.modal.show(LogInModal, attrs);
  }

  onready() {
    if (this.attrs.username && !this.attrs.email) {
      this.$('[name=email]').select();
    } else {
      this.$('[name=username]').select();
    }
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    const body = this.submitData();

    app
      .request({
        url: app.forum.attribute('baseUrl') + '/register',
        method: 'POST',
        body,
        errorHandler: this.onerror.bind(this),
      })
      .then(() => window.location.reload(), this.loaded.bind(this));
  }

  /**
   * Get the data that should be submitted in the sign-up request.
   *
   * @return {Object}
   * @protected
   */
  submitData() {
    const data = {
      username: this.username(),
      email: this.email(),
    };

    if (this.attrs.token) {
      data.token = this.attrs.token;
    } else {
      data.password = this.password();
    }

    return data;
  }
}
