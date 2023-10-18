import app from '../app';
import Component from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import Alert from '../../common/components/Alert';
import Button from '../../common/components/Button';
import Icon from '../../common/components/Icon';

export default class Notices extends Component {
  private loading: boolean = false;
  private sent: boolean = false;

  view(): Mithril.Children {
    return <div className="App-notices">{this.items().toArray()}</div>;
  }

  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const user = app.session.user;

    if (user && !user.isEmailConfirmed()) {
      items.add(
        'emailConfirmation',
        <Alert
          dismissible={false}
          controls={[
            <Button className="Button Button--link" onclick={this.onclickEmailConfirmation.bind(this)} loading={this.loading} disabled={this.sent}>
              {this.sent
                ? [<Icon name={'fas fa-check'} />, ' ', app.translator.trans('core.forum.user_email_confirmation.sent_message')]
                : app.translator.trans('core.forum.user_email_confirmation.resend_button')}
            </Button>,
          ]}
          className="Alert--emailConfirmation"
          containerClassName="container"
        >
          {app.translator.trans('core.forum.user_email_confirmation.alert_message', { email: <strong>{user.email()}</strong> })}
        </Alert>,
        100
      );
    }

    return items;
  }

  onclickEmailConfirmation() {
    const user = app.session.user!;

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/send-confirmation',
      })
      .then(() => {
        this.loading = false;
        this.sent = true;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
