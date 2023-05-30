import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';

import Stream from 'flarum/common/utils/Stream';
import withAttr from 'flarum/common/utils/withAttr';
import ItemList from 'flarum/common/utils/ItemList';

export default class FlagPostModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    this.success = false;

    this.reason = Stream('');
    this.reasonDetail = Stream('');
  }

  className() {
    return 'FlagPostModal Modal--medium';
  }

  title() {
    return app.translator.trans('flarum-flags.forum.flag_post.title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('flarum-flags.forum.flag_post.confirmation_message')}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('flarum-flags.forum.flag_post.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <div>{this.flagReasons().toArray()}</div>
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary Button--block" type="submit" loading={this.loading} disabled={!this.reason()}>
              {app.translator.trans('flarum-flags.forum.flag_post.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  flagReasons() {
    const items = new ItemList();
    const guidelinesUrl = app.forum.attribute('guidelinesUrl');

    items.add(
      'off-topic',
      <label className="checkbox">
        <input type="radio" name="reason" checked={this.reason() === 'off_topic'} value="off_topic" onclick={withAttr('value', this.reason)} />
        <strong>{app.translator.trans('flarum-flags.forum.flag_post.reason_off_topic_label')}</strong>
        {app.translator.trans('flarum-flags.forum.flag_post.reason_off_topic_text')}
        {this.reason() === 'off_topic' && (
          <textarea
            className="FormControl"
            placeholder={app.translator.trans('flarum-flags.forum.flag_post.reason_details_placeholder')}
            value={this.reasonDetail()}
            oninput={withAttr('value', this.reasonDetail)}
          ></textarea>
        )}
      </label>,
      70
    );

    items.add(
      'inappropriate',
      <label className="checkbox">
        <input
          type="radio"
          name="reason"
          checked={this.reason() === 'inappropriate'}
          value="inappropriate"
          onclick={withAttr('value', this.reason)}
        />
        <strong>{app.translator.trans('flarum-flags.forum.flag_post.reason_inappropriate_label')}</strong>
        {app.translator.trans('flarum-flags.forum.flag_post.reason_inappropriate_text', {
          a: guidelinesUrl ? <a href={guidelinesUrl} target="_blank" /> : undefined,
        })}
        {this.reason() === 'inappropriate' && (
          <textarea
            className="FormControl"
            placeholder={app.translator.trans('flarum-flags.forum.flag_post.reason_details_placeholder')}
            value={this.reasonDetail()}
            oninput={withAttr('value', this.reasonDetail)}
          ></textarea>
        )}
      </label>,
      60
    );

    items.add(
      'spam',
      <label className="checkbox">
        <input type="radio" name="reason" checked={this.reason() === 'spam'} value="spam" onclick={withAttr('value', this.reason)} />
        <strong>{app.translator.trans('flarum-flags.forum.flag_post.reason_spam_label')}</strong>
        {app.translator.trans('flarum-flags.forum.flag_post.reason_spam_text')}
        {this.reason() === 'spam' && (
          <textarea
            className="FormControl"
            placeholder={app.translator.trans('flarum-flags.forum.flag_post.reason_details_placeholder')}
            value={this.reasonDetail()}
            oninput={withAttr('value', this.reasonDetail)}
          ></textarea>
        )}
      </label>,
      50
    );

    items.add(
      'other',
      <label className="checkbox">
        <input type="radio" name="reason" checked={this.reason() === 'other'} value="other" onclick={withAttr('value', this.reason)} />
        <strong>{app.translator.trans('flarum-flags.forum.flag_post.reason_other_label')}</strong>
        {this.reason() === 'other' && (
          <textarea className="FormControl" value={this.reasonDetail()} oninput={withAttr('value', this.reasonDetail)}></textarea>
        )}
      </label>,
      10
    );

    return items;
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.store
      .createRecord('flags')
      .save(
        {
          reason: this.reason() === 'other' ? null : this.reason(),
          reasonDetail: this.reasonDetail(),
          relationships: {
            user: app.session.user,
            post: this.attrs.post,
          },
        },
        { errorHandler: this.onerror.bind(this) }
      )
      .then(() => (this.success = true))
      .catch(() => {})
      .then(this.loaded.bind(this));
  }
}
