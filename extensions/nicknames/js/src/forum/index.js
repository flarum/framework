import { extend } from 'flarum/extend';
import Button from 'flarum/components/Button';
import EditUserModal from 'flarum/components/EditUserModal';
import SettingsPage from 'flarum/components/SettingsPage';
import extractText from 'flarum/utils/extractText';
import Stream from 'flarum/utils/Stream';
import NickNameModal from './components/NicknameModal';

app.initializers.add('flarum/nicknames', () => {
  extend(SettingsPage.prototype, 'accountItems', function (items) {
    items.add('changeNickname',
      <Button className="Button" onclick={() => app.modal.show(NickNameModal)}>
        {app.translator.trans('flarum-nicknames.forum.settings.change_nickname_button')}
      </Button>
    );
  });

  extend(EditUserModal.prototype, 'oninit', function () {
    this.nickname = Stream(this.attrs.user.displayName());
  });

  extend(EditUserModal.prototype, 'fields', function (items) {
    items.add('nickname',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-nicknames.forum.edit_user.password_heading')}</label>
        <input className="FormControl"
               placeholder={extractText(app.translator.trans('flarum-nicknames.forum.edit_user.password_text'))}
               bidi={this.nickname} />
      </div>, 100);
  });

  extend(EditUserModal.prototype, 'data', function (data) {
    const user = this.attrs.user;
    if (this.nickname() !== this.attrs.user.username()) {
      data.nickname = this.nickname();
    }
  });


});
