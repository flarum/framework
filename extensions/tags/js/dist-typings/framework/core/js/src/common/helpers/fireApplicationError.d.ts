/**
 * Fire a Flarum error which is shown in the JS console for everyone and in an alert for the admin.
 *
 * @param userTitle: a user friendly title of the error, should be localized.
 * @param consoleTitle: an error title that goes in the console, doesn't have to be localized.
 * @param error: the error.
 */
export default function fireApplicationError(userTitle: string, consoleTitle: string, error: any): void;
