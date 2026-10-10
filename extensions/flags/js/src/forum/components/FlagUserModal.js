import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Form from 'flarum/common/components/Form';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import withAttr from 'flarum/common/utils/withAttr';
import ItemList from 'flarum/common/utils/ItemList';
import haptic from 'flarum/common/utils/haptic';

/** Report an account without requiring a post to exist. */
export default class FlagUserModal extends FormModal {
  oninit(vnode) {
    super.oninit(vnode);
    this.success = false;
    this.reason = Stream('');
    this.reasonDetail = Stream('');
  }

  className() {
    return 'FlagUserModal FlagPostModal Modal--medium';
  }

  title() {
    return app.translator.trans('flarum-flags.forum.flag_user.title', { user: this.attrs.user });
  }

  content() {
    return (
      <div className="Modal-body">
        <Form className="Form--centered">
          {this.success ? (
            <p className="helpText">{app.translator.trans('flarum-flags.forum.flag_user.confirmation_message')}</p>
          ) : (
            <div className="Form-group">{this.flagReasons().toArray()}</div>
          )}
          <div className="Form-group Form-controls">
            {this.success ? (
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('flarum-flags.forum.flag_user.dismiss_button')}
              </Button>
            ) : (
              <Button className="Button Button--primary Button--block" type="submit" loading={this.loading} disabled={!this.reason()}>
                {app.translator.trans('flarum-flags.forum.flag_user.submit_button')}
              </Button>
            )}
          </div>
        </Form>
      </div>
    );
  }

  flagReasons() {
    const items = new ItemList();
    const guidelinesUrl = app.forum.attribute('guidelinesUrl');

    ['inappropriate', 'spam', 'other'].forEach((reason, index) => {
      const selected = this.reason() === reason;

      items.add(
        reason,
        <label className="checkbox">
          <input type="radio" name="reason" checked={selected} value={reason} onclick={withAttr('value', this.reason)} />
          <strong>{app.translator.trans(`flarum-flags.forum.flag_user.reason_${reason}_label`)}</strong>
          {reason === 'inappropriate' &&
            (guidelinesUrl
              ? app.translator.trans('flarum-flags.forum.flag_user.reason_inappropriate_text', {
                  a: <a href={guidelinesUrl} target="_blank" rel="noopener noreferrer" />,
                })
              : app.translator.trans('flarum-flags.forum.flag_user.reason_inappropriate_text_no_guidelines'))}
          {reason === 'spam' && app.translator.trans('flarum-flags.forum.flag_user.reason_spam_text')}
          {selected && (
            <textarea
              className="FormControl"
              placeholder={app.translator.trans('flarum-flags.forum.flag_user.reason_details_placeholder')}
              value={this.reasonDetail()}
              oninput={withAttr('value', this.reasonDetail)}
            />
          )}
        </label>,
        60 - index * 10
      );
    });

    return items;
  }

  onsubmit(e) {
    e.preventDefault();
    if (this.loading || this.success || !this.reason()) return;

    haptic('warning');
    this.loading = true;

    return app.store
      .createRecord('flags')
      .save(
        {
          reason: this.reason() === 'other' ? null : this.reason(),
          reasonDetail: this.reasonDetail(),
          relationships: { targetUser: this.attrs.user },
        },
        { errorHandler: this.onerror.bind(this) }
      )
      .then(() => {
        this.success = true;
      })
      .catch(() => {})
      .then(this.loaded.bind(this));
  }
}
