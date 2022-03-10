import app from '../../forum/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import LogInModal from './LogInModal';
import Button from '../../common/components/Button';
import LogInButtons from './LogInButtons';
import extractText from '../../common/utils/extractText';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';

export interface ISignupModalAttrs extends IInternalModalAttrs {
  username?: string;
  email?: string;
  password?: string;
  token?: string;
  provided?: string[];
}

export type SignupBody = {
  username: string;
  email: string;
} & ({ token: string } | { password: string });

export default class SignUpModal<CustomAttrs extends ISignupModalAttrs = ISignupModalAttrs> extends Modal<CustomAttrs> {
  /**
   * The value of the username input.
   */
  username!: Stream<string>;

  /**
   * The value of the email input.
   */
  email!: Stream<string>;

  /**
   * The value of the password input.
   */
  password!: Stream<string>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.username = Stream(this.attrs.username || '');
    this.email = Stream(this.attrs.email || '');
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

  isProvided(field: string): boolean {
    return this.attrs.provided?.includes(field) ?? false;
  }

  body() {
    return [!this.attrs.token && <LogInButtons />, <div className="Form Form--centered">{this.fields().toArray()}</div>];
  }

  fields() {
    const items = new ItemList();

    const usernameLabel = extractText(app.translator.trans('core.forum.sign_up.username_placeholder'));
    const emailLabel = extractText(app.translator.trans('core.forum.sign_up.email_placeholder'));
    const passwordLabel = extractText(app.translator.trans('core.forum.sign_up.password_placeholder'));

    items.add(
      'username',
      <div className="Form-group">
        <input
          className="FormControl"
          name="username"
          type="text"
          placeholder={usernameLabel}
          aria-label={usernameLabel}
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
          placeholder={emailLabel}
          aria-label={emailLabel}
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
            autocomplete="new-password"
            placeholder={passwordLabel}
            aria-label={passwordLabel}
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

  onsubmit(e: SubmitEvent) {
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
   */
  submitData(): SignupBody {
    const authData = this.attrs.token ? { token: this.attrs.token } : { password: this.password() };

    const data = {
      username: this.username(),
      email: this.email(),
      ...authData,
    };

    return data;
  }
}
