import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import NotificationList from 'flarum/components/NotificationList';

export default class NotificationsDropdown extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the notifications dropdown is visible.
     *
     * @type {Boolean}
     */
    this.showing = false;
  }

  view() {
    const user = app.session.user;
    const unread = user.unreadNotificationsCount();

    return (
      <div className="Dropdown NotificationsDropdown">
        <a href="javascript:;"
          className={'Dropdown-toggle Button Button--flat NotificationsDropdown-button' + (unread ? ' unread' : '')}
          data-toggle="dropdown"
          onclick={this.onclick.bind(this)}>
          <span className="Button-icon">{unread || icon('bell')}</span>
          <span className="Button-label">Notifications</span>
        </a>
        <div className="Dropdown-menu Dropdown-menu--right">
          {this.showing ? NotificationList.component() : ''}
        </div>
      </div>
    );
  }

  onclick() {
    if (app.drawer.isOpen()) {
      m.route(app.route('notifications'));
    } else {
      this.showing = true;
    }
  }
}
