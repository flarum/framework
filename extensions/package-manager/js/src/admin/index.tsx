import { extend } from 'flarum/common/extend';
import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import LoadingModal from 'flarum/admin/components/LoadingModal';
import isExtensionEnabled from 'flarum/admin/utils/isExtensionEnabled';
import jumpToQueue from './utils/jumpToQueue';
import { AsyncBackendResponse } from './shims';
import ExtensionManagerState from './states/ExtensionManagerState';

export { default as extend } from './extend';

app.initializers.add('flarum-extension-manager', (app) => {
  app.extensionManager = new ExtensionManagerState();

  if (app.data['flarum-extension-manager.using_sync_queue']) {
    app.data.settings['flarum-extension-manager.queue_jobs'] = '0';
  }

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
