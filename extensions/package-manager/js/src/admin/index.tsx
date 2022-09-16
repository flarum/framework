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
import PackageManagerState from './states/PackageManagerState';
import Alert from "@flarum/core/src/common/components/Alert";

app.initializers.add('flarum-package-manager', (app) => {
  app.store.models['package-manager-tasks'] = Task;

  app.packageManager = new PackageManagerState();

  app.extensionData
    .for('flarum-package-manager')
    .registerSetting(() => (
      <div className="Form-group">
        <Alert type="warning" dismissible={false}>
          {app.translator.trans('flarum-package-manager.admin.settings.access_warning')}
        </Alert>
      </div>
    ))
    .registerSetting({
      setting: 'flarum-package-manager.queue_jobs',
      label: app.translator.trans('flarum-package-manager.admin.settings.queue_jobs'),
      help: m.trust(
        extractText(
          app.translator.trans('flarum-package-manager.admin.settings.queue_jobs_help', {
            basic_impl_link: 'https://discuss.flarum.org/d/28151-database-queue-the-simplest-queue-even-for-shared-hosting',
            adv_impl_link: 'https://discuss.flarum.org/d/21873-redis-sessions-cache-queues',
            php_version: `<strong>${app.data.phpVersion}</strong>`,
            folder_perms_link: 'https://docs.flarum.org/install#folder-ownership',
          })
        )
      ),
      default: false,
      type: 'boolean',
      disabled: app.data['flarum-package-manager.using_sync_queue'],
    })
    .registerPage(SettingsPage);

  extend(ExtensionPage.prototype, 'topItems', function (items) {
    if (this.extension.id === 'flarum-package-manager' || isExtensionEnabled(this.extension.id)) {
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
              url: `${app.forum.attribute('apiUrl')}/package-manager/extensions/${this.extension.id}`,
              method: 'DELETE',
            })
            .then((response) => {
              if (response?.processing) {
                jumpToQueue();
              } else {
                app.alerts.show({ type: 'success' }, app.translator.trans('flarum-package-manager.admin.extensions.successful_remove'));
                window.location = app.forum.attribute('adminUrl');
              }
            })
            .finally(() => {
              app.modal.close();
            });
        }}
      >
        Remove
      </Button>
    );
  });
});
