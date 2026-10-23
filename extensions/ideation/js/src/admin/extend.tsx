import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

const key = (k: string) => 'flarum-ideation.github.' + k;

export default [
  new Extend.Admin()
    .setting(() => ({
      setting: key('enabled'),
      type: 'boolean',
      label: app.translator.trans('flarum-ideation.admin.github.enabled'),
      help: app.translator.trans('flarum-ideation.admin.github.enabled_help'),
    }))
    .setting(() => ({
      setting: key('repos'),
      type: 'textarea',
      label: app.translator.trans('flarum-ideation.admin.github.repos'),
      help: app.translator.trans('flarum-ideation.admin.github.repos_help'),
      placeholder: 'flarum/framework\nflarum/*',
    }))
    .setting(() => ({
      setting: key('token'),
      type: 'password',
      label: app.translator.trans('flarum-ideation.admin.github.token'),
      help: app.translator.trans('flarum-ideation.admin.github.token_help', {
        a: <a href="https://github.com/settings/personal-access-tokens/new" target="_blank" rel="noopener noreferrer" />,
      }),
    }))
    .setting(() => ({
      setting: key('secret'),
      type: 'password',
      label: app.translator.trans('flarum-ideation.admin.github.secret'),
      help: app.translator.trans('flarum-ideation.admin.github.secret_help', {
        url: <code>{app.forum.attribute('apiUrl') + '/ideation/github/webhook'}</code>,
        a: <a href="https://docs.github.com/en/webhooks/using-webhooks/creating-webhooks" target="_blank" rel="noopener noreferrer" />,
      }),
    }))
    .setting(() => ({
      setting: key('auto_progress'),
      type: 'boolean',
      label: app.translator.trans('flarum-ideation.admin.github.auto_progress'),
      help: app.translator.trans('flarum-ideation.admin.github.auto_progress_help'),
    }))
    .setting(() => ({
      setting: key('auto_ship'),
      type: 'boolean',
      label: app.translator.trans('flarum-ideation.admin.github.auto_ship'),
      help: app.translator.trans('flarum-ideation.admin.github.auto_ship_help'),
    }))

    .permission(
      () => ({
        icon: 'fas fa-lightbulb',
        label: app.translator.trans('flarum-ideation.admin.permissions.post_ideas'),
        permission: 'ideation.postIdeas',
      }),
      'start'
    )
    .permission(
      () => ({
        icon: 'fas fa-chevron-up',
        label: app.translator.trans('flarum-ideation.admin.permissions.vote'),
        permission: 'discussion.ideationVote',
      }),
      'reply'
    )
    .permission(
      () => ({
        icon: 'fas fa-map-signs',
        label: app.translator.trans('flarum-ideation.admin.permissions.moderate'),
        permission: 'discussion.ideationModerate',
      }),
      'moderate'
    ),
];
