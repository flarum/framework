import app from 'flarum/admin/app';

export default function (e: any) {
  app.extensionManager.control.setLoading(null);

  const error = e.response.errors[0];

  if (!['composer_command_failure', 'extension_already_installed', 'extension_not_installed'].includes(error.code)) {
    throw e;
  }

  app.alerts.clear();

  switch (error.code) {
    case 'composer_command_failure':
      if (error.guessed_cause) {
        app.alerts.show({ type: 'error' }, app.translator.trans(`flarum-extension-manager.admin.exceptions.guessed_cause.${error.guessed_cause}`));
        app.modal.close();
      } else {
        app.alerts.show({ type: 'error' }, app.translator.trans('flarum-extension-manager.admin.exceptions.composer_command_failure'));
      }
      break;

    case 'extension_already_installed':
      app.alerts.show({ type: 'error' }, app.translator.trans('flarum-extension-manager.admin.exceptions.extension_already_installed'));
      app.modal.close();
      break;

    case 'extension_not_installed':
      app.alerts.show({ type: 'error' }, app.translator.trans('flarum-extension-manager.admin.exceptions.extension_not_installed'));
      app.modal.close();
  }
}
