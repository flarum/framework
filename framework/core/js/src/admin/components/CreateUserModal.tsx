import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import extractText from '../../common/utils/extractText';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
import Switch from '../../common/components/Switch';
import { generateRandomString } from '../../common/utils/string';

export interface ICreateUserModalAttrs extends IInternalModalAttrs {
  username?: string;
  email?: string;
  password?: string;
  token?: string;
  provided?: string[];
}

export type SignupBody = {
  username: string;
  email: string;
  isEmailConfirmed: boolean;
  password: string;
};

export default class CreateUserModal<CustomAttrs extends ICreateUserModalAttrs = ICreateUserModalAttrs> extends Modal<CustomAttrs> {
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
  password!: Stream<string | null>;

  /**
   * Whether email confirmation is required after signing in.
   */
  requireEmailConfirmation!: Stream<boolean>;

  /**
   * Keeps the modal open after the user is created to facilitate creating
   * multiple users at once.
   */
  bulkAdd!: Stream<boolean>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.username = Stream('');
    this.email = Stream('');
    this.password = Stream<string | null>('');
    this.requireEmailConfirmation = Stream(false);
    this.bulkAdd = Stream(false);
  }

  className() {
    return 'Modal--small CreateUserModal';
  }

  title() {
    return app.translator.trans('core.admin.create_user.title');
  }

  content() {
    return (
      <>
        <div className="Modal-body">{this.body()}</div>
      </>
    );
  }

  body() {
    return (
      <>
        <div className="Form Form--centered">{this.fields().toArray()}</div>
      </>
    );
  }

  fields() {
    const items = new ItemList();

    const usernameLabel = extractText(app.translator.trans('core.admin.create_user.username_placeholder'));
    const emailLabel = extractText(app.translator.trans('core.admin.create_user.email_placeholder'));
    const emailConfirmationLabel = extractText(app.translator.trans('core.admin.create_user.email_confirmed_label'));
    const useRandomPasswordLabel = extractText(app.translator.trans('core.admin.create_user.use_random_password'));
    const passwordLabel = extractText(app.translator.trans('core.admin.create_user.password_placeholder'));

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
          disabled={this.loading}
        />
      </div>,
      100
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
          disabled={this.loading}
        />
      </div>,
      80
    );

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
          disabled={this.loading || this.password() === null}
        />
      </div>,
      60
    );

    items.add(
      'emailConfirmation',
      <div className="Form-group">
        <Switch
          name="emailConfirmed"
          state={this.requireEmailConfirmation()}
          onchange={(checked: boolean) => this.requireEmailConfirmation(checked)}
          disabled={this.loading}
        >
          {emailConfirmationLabel}
        </Switch>
      </div>,
      40
    );

    items.add(
      'useRandomPassword',
      <div className="Form-group">
        <Switch
          name="useRandomPassword"
          state={this.password() === null}
          onchange={(enabled: boolean) => {
            this.password(enabled ? null : '');
          }}
          disabled={this.loading}
        >
          {useRandomPasswordLabel}
        </Switch>
      </div>,
      20
    );

    items.add(
      'submit',
      <div className="Form-group">
        <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
          {app.translator.trans('core.admin.create_user.submit_button')}
        </Button>
      </div>,
      0
    );

    items.add(
      'submitAndAdd',
      <div className="Form-group">
        <Button className="Button Button--block" onclick={() => this.bulkAdd(true) && this.onsubmit()} disabled={this.loading}>
          {app.translator.trans('core.admin.create_user.submit_and_create_another_button')}
        </Button>
      </div>,
      -20
    );

    return items;
  }

  onready() {
    this.$('[name=username]').trigger('select');
  }

  onsubmit(e: SubmitEvent | null = null) {
    e?.preventDefault();

    this.loading = true;

    app
      .request({
        url: app.forum.attribute('apiUrl') + '/users',
        method: 'POST',
        body: { data: { attributes: this.submitData() } },
        errorHandler: this.onerror.bind(this),
      })
      .then(() => {
        if (this.bulkAdd()) {
          this.resetData();
        } else {
          this.hide();
        }
      })
      .finally(() => {
        this.bulkAdd(false);
        this.loaded();
      });
  }

  /**
   * Get the data that should be submitted in the sign-up request.
   */
  submitData(): SignupBody {
    const data = {
      username: this.username(),
      email: this.email(),
      isEmailConfirmed: !this.requireEmailConfirmation(),
      password: this.password() ?? generateRandomString(32),
    };

    return data;
  }

  resetData() {
    this.username('');
    this.email('');
    this.password('');
  }
}
