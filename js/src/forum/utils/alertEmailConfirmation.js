import Alert from '../../common/components/Alert';
import Button from '../../common/components/Button';
import icon from '../../common/helpers/icon';
import Component from '../../common/Component';

/**
 * Shows an alert if the user has not yet confirmed their email address.
 *
 * @param {ForumApplication} app
 */
export default function alertEmailConfirmation(app) {
  const user = app.session.user;

  if (!user || user.isEmailConfirmed()) return;

  class ResendButton extends Component {
    oninit(vnode) {
      super.oninit(vnode);

      this.loading = false;
      this.disabled = false;

      this.content = app.translator.trans('core.forum.user_email_confirmation.resend_button');
    }

    view() {
      return (
        <Button class="Button Button--link" onclick={this.onclick.bind(this)} loading={this.loading} disabled={this.disabled}>
          {this.content}
        </Button>
      );
    }

    onclick() {
      this.loading = true;
      m.redraw();

      app
        .request({
          method: 'POST',
          url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/send-confirmation',
        })
        .then(() => {
          this.loading = false;
          this.content = [icon('fas fa-check'), ' ', app.translator.trans('core.forum.user_email_confirmation.sent_message')];
          this.disabled = true;
          m.redraw();
        })
        .catch(() => {
          this.loading = false;
          m.redraw();
        });
    }
  }

  class ContainedAlert extends Alert {
    view(vnode) {
      const vdom = super.view(vnode);

      return { ...vdom, children: [<div className="container">{vdom.children}</div>] };
    }
  }

  m.mount($('<div/>').insertBefore('#content')[0], {
    view: () =>
      ContainedAlert.component(
        {
          dismissible: false,
          controls: [ResendButton.component()],
        },
        app.translator.trans('core.forum.user_email_confirmation.alert_message', { email: <strong>{user.email()}</strong> })
      ),
  });
}
