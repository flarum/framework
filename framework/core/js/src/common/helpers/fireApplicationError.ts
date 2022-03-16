import app from "../app";

/**
 * Fire a Flarum error which is shown in the JS console for everyone and in an alert for the admin.
 *
 * @param options
 * {
 *   userTitle: a user friendly title of the error, should be localized.
 *   consoleTitle: an error title that goes in the console, doesn't have to be localized.
 * }
 * @param errors
 */
export default function fireApplicationError(options: { userTitle: string, consoleTitle: string }, ...errors: any) {
  console.group(
    `%c${options.consoleTitle}`,
    'background-color: #d83e3e; color: #ffffff; font-weight: bold;'
  );
  console.error(...errors);
  console.groupEnd();

  if (app.session?.user?.isAdmin()) {
    app.alerts.show({ type: 'error' }, `${options.userTitle}`);
  }
}
