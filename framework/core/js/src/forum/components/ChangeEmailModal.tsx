import app from '../../forum/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
import ItemList from '../../common/utils/ItemList';

/**
 * The `ChangeEmailModal` component shows a modal dialog which allows the user
 * to change their email address.
 */
export default class ChangeEmailModal<CustomAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Modal<CustomAttrs> {
  /**
   * The value of the email input.
   */
  email!: Stream<string>;

  /**
   * The value of the password input.
   */
  password!: Stream<string>;

  /**
   * Whether or not the email has been changed successfully.
   */
  success: boolean = false;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.email = Stream(app.session.user!.email() || '');
    this.password = Stream('');
  }

  className() {
    return 'ChangeEmailModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.change_email.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">{this.fields().toArray()}</div>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.success) {
      items.add(
        'help',
        <p className="helpText">
          {app.translator.trans('core.forum.change_email.confirmation_message', {
            email: <strong>{this.email()}</strong>,
          })}
        </p>
      );

      items.add(
        'dismiss',
        <div className="Form-group">
          <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
            {app.translator.trans('core.forum.change_email.dismiss_button')}
          </Button>
        </div>
      );
    } else {
      items.add(
        'email',
        <div className="Form-group">
          <input
            type="email"
            name="email"
            className="FormControl"
            placeholder={app.session.user!.email()}
            bidi={this.email}
            disabled={this.loading}
          />
        </div>
      );

      items.add(
        'password',
        <div className="Form-group">
          <input
            type="password"
            name="password"
            className="FormControl"
            autocomplete="current-password"
            placeholder={app.translator.trans('core.forum.change_email.confirm_password_placeholder')}
            bidi={this.password}
            disabled={this.loading}
          />
        </div>
      );

      items.add(
        'submit',
        <div className="Form-group">
          <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
            {app.translator.trans('core.forum.change_email.submit_button')}
          </Button>
        </div>
      );
    }

    return items;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    // If the user hasn't actually entered a different email address, we don't
    // need to do anything. Woot!
    if (this.email() === app.session.user!.email()) {
      this.hide();
      return;
    }

    this.loading = true;
    this.alertAttrs = null;

    app.session
      .user!.save(this.requestAttributes(), {
        errorHandler: this.onerror.bind(this),
        meta: { password: this.password() },
      })
      .then(() => {
        this.success = true;
      })
      .catch(() => {})
      .then(this.loaded.bind(this));
  }

  requestAttributes() {
    return { email: this.email() };
  }

  onerror(error: RequestError) {
    if (error.status === 401 && error.alert) {
      error.alert.content = app.translator.trans('core.forum.change_email.incorrect_password_message');
    }

    super.onerror(error);
  }
}
