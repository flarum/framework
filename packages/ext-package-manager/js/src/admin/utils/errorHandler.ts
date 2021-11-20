import app from 'flarum/admin/app';

export default function (e: any) {
  const error = e.response.errors[0];

  if (!['composer_command_failure', 'extension_already_installed', 'extension_not_installed'].includes(error.code)) {
    throw e;
  }

  switch (error.code) {
    case 'composer_command_failure':
      if (error.guessed_cause) {
        app.alerts.show({type: 'error'}, app.translator.trans(`flarum-package-manager.admin.exceptions.guessed_cause.${error.guessed_cause}`))
        app.modal.close();
      }
      break;

    case 'extension_already_installed':
      app.alerts.show({ type: 'error' }, app.translator.trans('flarum-package-manager.admin.exceptions.extension_already_installed'));
      app.modal.close();
      break;

    case 'extension_not_installed':
      app.alerts.show({ type: 'error' }, app.translator.trans('flarum-package-manager.admin.exceptions.extension_not_installed'));
      app.modal.close();
  }
}
