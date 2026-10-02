import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import humanTime from 'flarum/common/helpers/humanTime';

/** Review all reports about an account and dismiss them together. */
export default class UserFlagsModal extends FormModal {
  oninit(vnode) {
    super.oninit(vnode);
    this.loading = true;
    this.flags = [];

    app.store
      .find('users', this.attrs.user.id(), { include: 'flags,flags.user' })
      .then((user) => {
        this.flags = (user.flags() || []).filter(Boolean);
      })
      .catch(this.onerror.bind(this))
      .then(this.loaded.bind(this));
  }

  className() {
    return 'UserFlagsModal Modal--medium';
  }

  title() {
    return app.translator.trans('flarum-flags.forum.user_flags.title', { user: this.attrs.user });
  }

  content() {
    return (
      <div className="Modal-body">
        {this.loading ? (
          <LoadingIndicator />
        ) : this.flags.length ? (
          <ul>
            {this.flags.map((flag) => (
              <li key={flag.id()}>
                <p>
                  {app.translator.trans('flarum-flags.forum.user_flags.flagged_by_text', {
                    user: flag.user() || null,
                    time: humanTime(flag.createdAt()),
                  })}
                  {flag.reason() && <strong> {app.translator.trans(`flarum-flags.forum.flag_user.reason_${flag.reason()}_label`)}</strong>}
                </p>
                {flag.reasonDetail() && <p>{flag.reasonDetail()}</p>}
              </li>
            ))}
          </ul>
        ) : (
          <p>{app.translator.trans('flarum-flags.forum.user_flags.empty_text')}</p>
        )}
        <div className="Form-group Form-controls">
          <Button className="Button Button--primary" type="submit" loading={this.loading} disabled={this.loading || !this.flags.length}>
            {app.translator.trans('flarum-flags.forum.user_flags.dismiss_button')}
          </Button>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    if (this.loading || !this.flags.length) return;
    this.loading = true;

    return app.flags
      .dismissUser(this.attrs.user)
      .then(() => this.hide())
      .catch(this.onerror.bind(this))
      .then(this.loaded.bind(this));
  }
}
