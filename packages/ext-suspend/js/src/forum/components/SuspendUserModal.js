import app from 'flarum/forum/app';
import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

import Stream from 'flarum/utils/Stream';
import withAttr from 'flarum/utils/withAttr';
import ItemList from 'flarum/common/utils/ItemList';
import { getPermanentSuspensionDate } from '../helpers/suspensionHelper';

export default class SuspendUserModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    let until = this.attrs.user.suspendedUntil();
    const reason = this.attrs.user.suspendReason();
    const message = this.attrs.user.suspendMessage();
    let status = null;

    if (new Date() > until) until = null;

    if (until) {
      if (until.getFullYear() === 9999) status = 'indefinitely';
      else status = 'limited';
    }

    this.status = Stream(status);
    this.reason = Stream(reason);
    this.message = Stream(message);
    this.daysRemaining = Stream(status === 'limited' && -dayjs().diff(until, 'days') + 1);
  }

  className() {
    return 'SuspendUserModal Modal--medium';
  }

  title() {
    return app.translator.trans('flarum-suspend.forum.suspend_user.title', { user: this.attrs.user });
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('flarum-suspend.forum.suspend_user.status_heading')}</label>
            <div>{this.formItems().toArray()}</div>
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary" loading={this.loading} type="submit">
              {app.translator.trans('flarum-suspend.forum.suspend_user.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  radioItems() {
    const items = new ItemList();

    items.add(
      'not-suspended',
      <label className="checkbox">
        <input type="radio" name="status" checked={!this.status()} value="" onclick={withAttr('value', this.status)} />
        {app.translator.trans('flarum-suspend.forum.suspend_user.not_suspended_label')}
      </label>,
      100
    );

    items.add(
      'indefinitely',
      <label className="checkbox">
        <input type="radio" name="status" checked={this.status() === 'indefinitely'} value="indefinitely" onclick={withAttr('value', this.status)} />
        {app.translator.trans('flarum-suspend.forum.suspend_user.indefinitely_label')}
      </label>,
      90
    );

    items.add(
      'time-suspension',
      <label className="checkbox SuspendUserModal-days">
        <input
          type="radio"
          name="status"
          checked={this.status() === 'limited'}
          value="limited"
          onclick={(e) => {
            this.status(e.target.value);
            m.redraw.sync();
            this.$('.SuspendUserModal-days-input input').select();
            e.redraw = false;
          }}
        />
        {app.translator.trans('flarum-suspend.forum.suspend_user.limited_time_label')}
        {this.status() === 'limited' && (
          <div className="SuspendUserModal-days-input">
            <input type="number" min="0" value={this.daysRemaining()} oninput={withAttr('value', this.daysRemaining)} className="FormControl" />
            {app.translator.trans('flarum-suspend.forum.suspend_user.limited_time_days_text')}
          </div>
        )}
      </label>,
      80
    );

    return items;
  }

  formItems() {
    const items = new ItemList();

    items.add('radioItems', <div className="Form-group">{this.radioItems().toArray()}</div>, 100);

    items.add(
      'reason',
      <div className="Form-group">
        <label>
          {app.translator.trans('flarum-suspend.forum.suspend_user.reason')}
          <textarea
            className="FormControl"
            bidi={this.reason}
            placeholder={app.translator.trans('flarum-suspend.forum.suspend_user.placeholder_optional')}
            rows="2"
          />
        </label>
      </div>,
      90
    );

    items.add(
      'message',
      <div className="Form-group">
        <label>
          {app.translator.trans('flarum-suspend.forum.suspend_user.display_message')}
          <textarea
            className="FormControl"
            bidi={this.message}
            placeholder={app.translator.trans('flarum-suspend.forum.suspend_user.placeholder_optional')}
            rows="2"
          />
        </label>
      </div>,
      80
    );

    return items;
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    let suspendedUntil = null;
    switch (this.status()) {
      case 'indefinitely':
        suspendedUntil = getPermanentSuspensionDate();
        break;

      case 'limited':
        suspendedUntil = dayjs().add(this.daysRemaining(), 'days').toDate();
        break;

      default:
      // no default
    }

    this.attrs.user
      .save({ suspendedUntil, suspendReason: this.reason(), suspendMessage: this.message() })
      .then(() => this.hide(), this.loaded.bind(this));
  }
}
