import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

const t = (k: string, p?: any) => app.translator.trans('flarum-ideation.admin.' + k, p);
const key = (k: string) => 'flarum-ideation.github.' + k;

export default [
  new Extend.Admin()
    .setting(() => ({ setting: key('enabled'), type: 'boolean', label: t('github.enabled'), help: t('github.enabled_help') }))
    .setting(() => ({
      setting: key('repos'),
      type: 'textarea',
      label: t('github.repos'),
      help: t('github.repos_help'),
      placeholder: 'flarum/framework\nflarum/*',
    }))
    .setting(() => ({
      setting: key('token'),
      type: 'password',
      label: t('github.token'),
      help: t('github.token_help', {
        a: <a href="https://github.com/settings/personal-access-tokens/new" target="_blank" rel="noopener noreferrer" />,
      }),
    }))
    .setting(() => ({
      setting: key('secret'),
      type: 'password',
      label: t('github.secret'),
      help: t('github.secret_help', {
        url: <code>{app.forum.attribute('apiUrl') + '/ideation/github/webhook'}</code>,
        a: <a href="https://docs.github.com/en/webhooks/using-webhooks/creating-webhooks" target="_blank" rel="noopener noreferrer" />,
      }),
    }))
    .setting(() => ({ setting: key('auto_progress'), type: 'boolean', label: t('github.auto_progress'), help: t('github.auto_progress_help') }))
    .setting(() => ({ setting: key('auto_ship'), type: 'boolean', label: t('github.auto_ship'), help: t('github.auto_ship_help') }))

    .permission(() => ({ icon: 'fas fa-lightbulb', label: t('permissions.post_ideas'), permission: 'ideation.postIdeas' }), 'start')
    .permission(() => ({ icon: 'fas fa-chevron-up', label: t('permissions.vote'), permission: 'ideation.vote' }), 'reply')
    .permission(() => ({ icon: 'fas fa-map-signs', label: t('permissions.moderate'), permission: 'ideation.moderate' }), 'moderate'),
];
