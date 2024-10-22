import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import Alert from 'flarum/common/components/Alert';
import Link from 'flarum/common/components/Link';

export default [
  new Extend.Admin()
    .customSetting(function () {
      if (app.data.settings.display_name_driver === 'nickname') return;

      return (
        <div className="Form-group">
          <Alert dismissible={false}>
            {app.translator.trans('flarum-nicknames.admin.wrong_driver', { a: <Link href={app.route('basics')}></Link> })}
          </Alert>
        </div>
      );
    })
    .setting(() => ({
      setting: 'flarum-nicknames.set_on_registration',
      type: 'boolean',
      label: app.translator.trans('flarum-nicknames.admin.settings.set_on_registration_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.random_username',
      type: 'boolean',
      label: app.translator.trans('flarum-nicknames.admin.settings.random_username_label'),
      help: app.translator.trans('flarum-nicknames.admin.settings.random_username_help'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.unique',
      type: 'boolean',
      label: app.translator.trans('flarum-nicknames.admin.settings.unique_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.regex',
      type: 'text',
      label: app.translator.trans('flarum-nicknames.admin.settings.regex_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.min',
      type: 'number',
      label: app.translator.trans('flarum-nicknames.admin.settings.min_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.max',
      type: 'number',
      label: app.translator.trans('flarum-nicknames.admin.settings.max_label'),
    }))
    .permission(
      () => ({
        icon: 'fas fa-user-tag',
        label: app.translator.trans('flarum-nicknames.admin.permissions.edit_own_nickname_label'),
        permission: 'user.editOwnNickname',
      }),
      'start'
    ),
];
