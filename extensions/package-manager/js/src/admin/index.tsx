import { extend } from 'flarum/common/extend';
import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import LoadingModal from 'flarum/admin/components/LoadingModal';
import isExtensionEnabled from 'flarum/admin/utils/isExtensionEnabled';
import SettingsPage from './components/SettingsPage';
import Task from './models/Task';
import jumpToQueue from './utils/jumpToQueue';
import extractText from 'flarum/common/utils/extractText';
import { AsyncBackendResponse } from './shims';
import ExtensionManagerState from './states/ExtensionManagerState';

app.initializers.add('flarum-extension-manager', (app) => {
  app.store.models['extension-manager-tasks'] = Task;

  app.extensionManager = new ExtensionManagerState();

  if (app.data['flarum-extension-manager.using_sync_queue']) {
    app.data.settings['flarum-extension-manager.queue_jobs'] = '0';
  }

  app.extensionData
    .for('flarum-extension-manager')
    .registerSetting({
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
    })
    .registerSetting({
      setting: 'flarum-extension-manager.task_retention_days',
      label: app.translator.trans('flarum-extension-manager.admin.settings.task_retention_days'),
      help: app.translator.trans('flarum-extension-manager.admin.settings.task_retention_days_help'),
      type: 'number',
    })
    .registerPage(SettingsPage);

  extend(ExtensionPage.prototype, 'topItems', function (items) {
    if (this.extension.id === 'flarum-extension-manager' || isExtensionEnabled(this.extension.id)) {
      return;
    }

    items.add(
      'remove',
      <Button
        className="Button Button--danger"
        icon="fas fa-times"
        onclick={() => {
          app.modal.show(LoadingModal);

          app
            .request<AsyncBackendResponse | null>({
              url: `${app.forum.attribute('apiUrl')}/extension-manager/extensions/${this.extension.id}`,
              method: 'DELETE',
            })
            .then((response) => {
              if (response?.processing) {
                jumpToQueue();
              } else {
                app.alerts.show({ type: 'success' }, app.translator.trans('flarum-extension-manager.admin.extensions.successful_remove'));
                window.location = app.forum.attribute('adminUrl');
              }
            })
            .finally(() => {
              app.modal.close();
            });
        }}
      >
        {app.translator.trans('flarum-extension-manager.admin.extensions.remove')}
      </Button>
    );
  });
});
