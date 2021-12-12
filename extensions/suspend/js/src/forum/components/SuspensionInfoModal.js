import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import fullTime from 'flarum/common/helpers/fullTime';
import { isPermanentSuspensionDate, localStorageKey } from '../helpers/suspensionHelper';

export default class SuspensionInfoModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    this.message = this.attrs.message;
    this.until = this.attrs.until;
  }

  className() {
    return 'SuspensionInfoModal Modal';
  }

  title() {
    return app.translator.trans('flarum-suspend.forum.suspension_info.title');
  }

  content() {
    const timespan = isPermanentSuspensionDate(new Date(this.until))
      ? app.translator.trans('flarum-suspend.forum.suspension_info.indefinite')
      : app.translator.trans('flarum-suspend.forum.suspension_info.limited', { date: fullTime(this.until) });

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{this.message}</p>
          <p className="helpText">{timespan}</p>

          <div className="Form-group">
            <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
              {app.translator.trans('flarum-suspend.forum.suspension_info.dismiss_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  hide() {
    localStorage.setItem(localStorageKey(), this.attrs.until.getTime());
    this.attrs.state.close();
  }
}
