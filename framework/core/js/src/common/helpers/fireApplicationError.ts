import app from '../app';

/**
 * Fire a Flarum error which is shown in the JS console for everyone and in an alert for the admin.
 *
 * @param userTitle: a user friendly title of the error, should be localized.
 * @param consoleTitle: an error title that goes in the console, doesn't have to be localized.
 * @param error: the error.
 */
export default function fireApplicationError(userTitle: string, consoleTitle: string, error: any) {
  console.group(`%c${consoleTitle}`, 'background-color: #d83e3e; color: #ffffff; font-weight: bold;');
  console.error(error);
  console.groupEnd();

  if (app.session?.user?.isAdmin()) {
    app.alerts.show({ type: 'error' }, `${userTitle}`);
  }
}
