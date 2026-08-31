import app from 'flarum/forum/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Form from 'flarum/common/components/Form';

import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';

export interface IChangeNicknameModalAttrs extends IFormModalAttrs {
  user: User;
}

export default class ChangeNicknameModal<CustomAttrs extends IChangeNicknameModalAttrs = IChangeNicknameModalAttrs> extends FormModal<CustomAttrs> {
  nickname!: Stream<string>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
    this.nickname = Stream(this.attrs.user.displayName());
  }

  className() {
    return 'ChangeNicknameModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-nicknames.forum.change_nickname.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <Form className="Form--centered">
          <div className="Form-group">
            <input type="text" autocomplete="off" name="nickname" className="FormControl" bidi={this.nickname} disabled={this.loading} />
          </div>
          <div className="Form-group Form-controls">
            <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
              {app.translator.trans('flarum-nicknames.forum.change_nickname.submit_button')}
            </Button>
          </div>
        </Form>
      </div>
    );
  }

  onsubmit(e: Event) {
    e.preventDefault();

    if (this.nickname() === this.attrs.user.displayName()) {
      this.hide();
      return;
    }

    this.loading = true;

    this.attrs.user
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
