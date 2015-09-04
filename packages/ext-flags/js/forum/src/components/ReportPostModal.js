import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

export default class ReportPostModal extends Modal {
  constructor(...args) {
    super(...args);

    this.reason = m.prop('');
    this.reasonDetail = m.prop('');
  }

  className() {
    return 'ReportPostModal Modal--small';
  }

  title() {
    return 'Report Post';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>Choose a Reason</label>
            <div>
              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'off_topic'} value="off_topic" onclick={m.withAttr('value', this.reason)}/>
                Off-topic
              </label>

              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'inappropriate'} value="inappropriate" onclick={m.withAttr('value', this.reason)}/>
                Inappropriate
              </label>

              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'spam'} value="spam" onclick={m.withAttr('value', this.reason)}/>
                Spam
              </label>

              <label className="checkbox">
                <input type="radio" name="reason" checked={this.reason() === 'other'} value="other" onclick={m.withAttr('value', this.reason)}/>
                Other
                {this.reason() === 'other' ? (
                  <textarea className="FormControl" value={this.reasonDetail()} oninput={m.withAttr('value', this.reasonDetail)}></textarea>
                ) : ''}
              </label>
            </div>
          </div>

          <div className="Form-group">
            {Button.component({
              children: 'Report Post',
              className: 'Button Button--primary',
              loading: this.loading,
              disabled: !this.reason()
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.store.createRecord('reports').save({
      reason: this.reason() === 'other' ? null : this.reason(),
      reasonDetail: this.reasonDetail(),
      relationships: {
        user: app.session.user,
        post: this.props.post
      }
    }).then(
      () => this.hide(),
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }
}
