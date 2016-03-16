import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';

/**
 * The `alertEmailConfirmation` initializer shows an Alert if loggend in
 * user's email is not confirmed
 *
 * @param {ForumApp} app
 */
export default function alertEmailConfirmation(app) {
  const user = app.session.user;

  if (!user || user.isActivated()) return;

  let alert;

  const resendButton = Button.component({
    className: 'Button Button--link',
    children: app.translator.trans('core.forum.user_confirmation.resend_button'),
    onclick: () => {
      app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/send-confirmation',
      }).then(() => app.alerts.dismiss(alert));
    }
  });

  app.alerts.show(
    alert = new Alert({
      type: 'error',
      dismissible: false,
      children: app.translator.trans('core.forum.user_confirmation.alert_message'),
      controls: [resendButton]
    })
  );
}
