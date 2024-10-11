import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import extractText from 'flarum/common/utils/extractText';
import SettingsPage from './components/SettingsPage';
import Task from './models/Task';
import ExternalExtension from './models/ExternalExtension';

export default [
  new Extend.Store() //
    .add('extension-manager-tasks', Task)
    .add('external-extensions', ExternalExtension),

  new Extend.Admin()
    .setting(() => ({
      setting: 'flarum-extension-manager.queue_jobs',
      label: app.translator.trans('flarum-extension-manager.admin.settings.queue_jobs'),
      help: m.trust(
        extractText(
          app.translator.trans('flarum-extension-manager.admin.settings.queue_jobs_help', {
            basic_impl_link: 'https://discuss.flarum.org/d/28151-database-queue-the-simplest-queue-even-for-shared-hosting',
            adv_impl_link: 'https://discuss.flarum.org/d/21873-redis-sessions-cache-queues',
            php_version: `<strong>${app.data.phpVersion}</strong>`,
            folder_perms_link: 'https://docs.flarum.org/install#folder-ownership',
          })
        )
      ),
      type: 'boolean',
      disabled: app.data['flarum-extension-manager.using_sync_queue'],
    }))
    .setting(() => ({
      setting: 'flarum-extension-manager.task_retention_days',
      label: app.translator.trans('flarum-extension-manager.admin.settings.task_retention_days'),
      help: app.translator.trans('flarum-extension-manager.admin.settings.task_retention_days_help'),
      type: 'number',
    }))
    .page(SettingsPage),
];
