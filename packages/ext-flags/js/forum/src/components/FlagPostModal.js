import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

export default class FlagPostModal extends Modal {
  init() {
    super.init();

    this.reason = m.prop('');
    this.reasonDetail = m.prop('');
  }

  className() {
    return 'FlagPostModal Modal--small';
  }

  title() {
    return {app.translator.trans('flarum-flags.forum.flag_post.title')};
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('flarum-flags.forum.flag_post.reason_heading')}</label>
            <div>
              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'off_topic'} value="off_topic" onclick={m.withAttr('value', this.reason)}/>
                {app.translator.trans('flarum-flags.forum.flag_post.reason_off_topic_label')}
              </label>

              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'inappropriate'} value="inappropriate" onclick={m.withAttr('value', this.reason)}/>
                {app.translator.trans('flarum-flags.forum.flag_post.reason_inappropriate_label')}
              </label>

              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'spam'} value="spam" onclick={m.withAttr('value', this.reason)}/>
                {app.translator.trans('flarum-flags.forum.flag_post.reason_spam_label')}
              </label>

              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'other'} value="other" onclick={m.withAttr('value', this.reason)}/>
                {app.translator.trans('flarum-flags.forum.flag_post.reason_other_label')}
                {this.reason() === 'other' ? (
                  <textarea className="FormControl" value={this.reasonDetail()} oninput={m.withAttr('value', this.reasonDetail)}></textarea>
                ) : ''}
              </label>
            </div>
          </div>

          <div className="Form-group">
            <Button
              className="Button Button--primary"
              type="submit"
              loading={this.loading}
              disabled={!this.reason()}>
              {app.translator.trans('flarum-flags.forum.flag_post.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.store.createRecord('flags').save({
      reason: this.reason() === 'other' ? null : this.reason(),
      reasonDetail: this.reasonDetail(),
      relationships: {
        user: app.session.user,
        post: this.props.post
      }
    }).then(
      () => this.hide(),
      this.loaded.bind(this)
    );
  }
}
