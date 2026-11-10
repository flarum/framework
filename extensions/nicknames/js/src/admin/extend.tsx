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
    .section(
      () => ({
        id: 'registration',
        icon: 'fas fa-user-plus',
        label: app.translator.trans('flarum-nicknames.admin.sections.registration.label'),
        description: app.translator.trans('flarum-nicknames.admin.sections.registration.description'),
      }),
      20
    )
    .section(
      () => ({
        id: 'validation',
        icon: 'fas fa-check-double',
        label: app.translator.trans('flarum-nicknames.admin.sections.validation.label'),
        description: app.translator.trans('flarum-nicknames.admin.sections.validation.description'),
      }),
      10
    )
    .setting(() => ({
      setting: 'flarum-nicknames.set_on_registration',
      section: 'registration',
      type: 'boolean',
      label: app.translator.trans('flarum-nicknames.admin.settings.set_on_registration_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.random_username',
      section: 'registration',
      type: 'boolean',
      label: app.translator.trans('flarum-nicknames.admin.settings.random_username_label'),
      help: app.translator.trans('flarum-nicknames.admin.settings.random_username_help'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.unique',
      section: 'validation',
      type: 'boolean',
      label: app.translator.trans('flarum-nicknames.admin.settings.unique_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.regex',
      section: 'validation',
      type: 'text',
      label: app.translator.trans('flarum-nicknames.admin.settings.regex_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.min',
      section: 'validation',
      type: 'number',
      label: app.translator.trans('flarum-nicknames.admin.settings.min_label'),
    }))
    .setting(() => ({
      setting: 'flarum-nicknames.max',
      section: 'validation',
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
