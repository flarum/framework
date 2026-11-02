import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import Button from 'flarum/common/components/Button';
import FormGroup from 'flarum/common/components/FormGroup';
import type AdminPage from 'flarum/admin/components/AdminPage';
import saveSettings from 'flarum/admin/utils/saveSettings';

const key = (k: string) => 'flarum-ideation.github.' + k;

const HELP = {
  token: () =>
    app.translator.trans('flarum-ideation.admin.github.token_help', {
      a: <a href="https://github.com/settings/personal-access-tokens/new" target="_blank" rel="noopener noreferrer" />,
    }),
  secret: () =>
    app.translator.trans('flarum-ideation.admin.github.secret_help', {
      url: <code>{app.forum.attribute('apiUrl') + '/ideation/github/webhook'}</code>,
      a: <a href="https://docs.github.com/en/webhooks/using-webhooks/creating-webhooks" target="_blank" rel="noopener noreferrer" />,
    }),
};

/**
 * The token and the webhook secret never reach this page: the server sends
 * only whether each is saved. Leaving the field empty keeps the saved one;
 * "Remove" sends null, which the server takes as removing it.
 */
function secretSetting(name: 'token' | 'secret') {
  return function (this: AdminPage) {
    const saved = app.data.ideationGithubSecrets as Record<string, boolean> | undefined;
    const isSaved = !!saved?.[name];

    const remove = () => {
      saveSettings({ [key(name)]: null }).then(() => {
        saved![name] = false;
        app.data.settings[key(name)] = '';
        this.setting(key(name))('');
        m.redraw();
      });
    };

    return (
      <div className="IdeationSecretSetting">
        <FormGroup
          type="password"
          stream={this.setting(key(name))}
          label={app.translator.trans('flarum-ideation.admin.github.' + name)}
          help={HELP[name]()}
          placeholder={isSaved ? app.translator.trans('flarum-ideation.admin.github.saved_placeholder', {}, true) : undefined}
          autocomplete="new-password"
        />
        {isSaved ? (
          <Button className="Button Button--link" onclick={remove}>
            {app.translator.trans('flarum-ideation.admin.github.remove_saved')}
          </Button>
        ) : null}
      </div>
    );
  };
}

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
    .customSetting(secretSetting('token'))
    .customSetting(secretSetting('secret'))
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
