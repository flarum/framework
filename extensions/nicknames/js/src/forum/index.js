import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';
import Stream from 'flarum/common/utils/Stream';
import NickNameModal from './components/NicknameModal';

export { default as extend } from './extend';

app.initializers.add('flarum-nicknames', () => {
  extend('flarum/forum/components/SettingsPage', 'accountItems', function (items) {
    if (app.forum.attribute('displayNameDriver') !== 'nickname') return;

    if (this.user.canEditNickname()) {
      items.add(
        'changeNickname',
        <Button className="Button" onclick={() => app.modal.show(NickNameModal)}>
          {app.translator.trans('flarum-nicknames.forum.settings.change_nickname_button')}
        </Button>
      );
    }
  });

  extend('flarum/common/components/EditUserModal', 'oninit', function () {
    this.nickname = Stream(this.attrs.user.displayName());
  });

  extend('flarum/common/components/EditUserModal', 'fields', function (items) {
    if (app.forum.attribute('displayNameDriver') !== 'nickname') return;

    if (!this.attrs.user.canEditNickname()) return;

    items.add(
      'nickname',
      <div className="Form-group">
        <label>{app.translator.trans('flarum-nicknames.forum.edit_user.nicknames_heading')}</label>
        <input
          className="FormControl"
          placeholder={extractText(app.translator.trans('flarum-nicknames.forum.edit_user.nicknames_text'))}
          bidi={this.nickname}
        />
      </div>,
      100
    );
  });

  extend('flarum/common/components/EditUserModal', 'data', function (data) {
    if (app.forum.attribute('displayNameDriver') !== 'nickname') return;

    if (!this.attrs.user.canEditNickname()) return;

    if (this.nickname() !== this.attrs.user.displayName()) {
      data.nickname = this.nickname();
    }
  });

  extend('flarum/forum/components/SignUpModal', 'oninit', function () {
    if (app.forum.attribute('displayNameDriver') !== 'nickname') return;

    this.nickname = Stream(this.attrs.nickname || this.attrs.username || '');
  });

  extend('flarum/forum/components/SignUpModal', 'onready', function () {
    if (app.forum.attribute('displayNameDriver') !== 'nickname') return;

    if (app.forum.attribute('setNicknameOnRegistration') && app.forum.attribute('randomizeUsernameOnRegistration')) {
      this.$('[name=nickname]').select();
    }
  });

  extend('flarum/forum/components/SignUpModal', 'fields', function (items) {
    if (app.forum.attribute('displayNameDriver') !== 'nickname') return;

    if (app.forum.attribute('setNicknameOnRegistration')) {
      items.add(
        'nickname',
        <div className="Form-group">
          <input
            className="FormControl"
            name="nickname"
            type="text"
            placeholder={extractText(app.translator.trans('flarum-nicknames.forum.sign_up.nickname_placeholder'))}
            bidi={this.nickname}
            disabled={this.loading || this.isProvided('nickname')}
            required={app.forum.attribute('randomizeUsernameOnRegistration')}
          />
        </div>,
        25
      );

      if (app.forum.attribute('randomizeUsernameOnRegistration')) {
        items.remove('username');
      }
    }
  });

  extend('flarum/forum/components/SignUpModal', 'submitData', function (data) {
    if (app.forum.attribute('displayNameDriver') !== 'nickname') return;

    if (app.forum.attribute('setNicknameOnRegistration')) {
      data.nickname = this.nickname();
      if (app.forum.attribute('randomizeUsernameOnRegistration')) {
        const arr = new Uint32Array(2);
        crypto.getRandomValues(arr);
        data.username = arr.join('');
      }
    }
  });
});
