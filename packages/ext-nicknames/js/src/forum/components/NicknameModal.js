import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';

export default class NicknameModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    this.nickname = Stream(app.session.user.displayName());
  }

  className() {
    return 'NickameModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-nicknames.forum.change_nickname.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input type="text" autocomplete="off" name="nickname" className="FormControl" bidi={this.nickname} disabled={this.loading} />
          </div>
          <div className="Form-group">
            {Button.component(
              {
                className: 'Button Button--primary Button--block',
                type: 'submit',
                loading: this.loading,
              },
              app.translator.trans('flarum-nicknames.forum.change_nickname.submit_button')
            )}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.nickname() === app.session.user.displayName()) {
      this.hide();
      return;
    }

    this.loading = true;

    app.session.user
      .save(
        { nickname: this.nickname() },
        {
          errorHandler: this.onerror.bind(this),
        }
      )
      .then(this.hide.bind(this))
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
