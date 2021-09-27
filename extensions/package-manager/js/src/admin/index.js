import { extend } from 'flarum/common/extend';
import app from 'flarum/admin/app';
import Alert from 'flarum/common/components/Alert';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import LoadingModal from 'flarum/admin/components/LoadingModal';
import Installer from "./components/Installer";
import Updater from "./components/Updater";
import isExtensionEnabled from 'flarum/admin/utils/isExtensionEnabled';

app.initializers.add('sycho-package-manager', (app) => {
  app.extensionData
    .for('sycho-package-manager')
    .registerSetting(() => {
      if (!app.data.isRequiredDirectoriesWritable) {
        return (
          <div className="Form-group">
            <Alert type="warning" dismissible={false}>{app.translator.trans('sycho-package-manager.admin.file_permissions')}</Alert>
          </div>
        );
      }
    })
    .registerSetting(() => {
      if (app.data.isRequiredDirectoriesWritable) {
        return (
          <Installer />
        );
      }
    })
    .registerSetting(() => {
      if (app.data.isRequiredDirectoriesWritable) {
        return (
          <Updater />
        );
      }
    });

  extend(ExtensionPage.prototype, 'topItems', function (items) {
    if (this.extension.id === 'sycho-package-manager' || isExtensionEnabled(this.extension.id)) {
      return;
    }

    items.add(
      'remove',
      <Button
        className="Button Button--danger"
        icon="fas fa-times"
        onclick={() => {
          app.modal.show(LoadingModal);

          app.request({
            url: `${app.forum.attribute('apiUrl')}/package-manager/extensions/${this.extension.id}`,
            method: 'DELETE',
          }).then(() => {
            app.alerts.show({ type: 'success' }, app.translator.trans('sycho-package-manager.admin.extensions.successful_remove'));
            window.location = app.forum.attribute('adminUrl');
          }).finally(() => {
            app.modal.close();
          });
        }}>
        Remove
      </Button>
    );
  });
});
